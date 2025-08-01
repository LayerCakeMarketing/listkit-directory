#!/bin/bash

# Final Production Data Merge Script
# This uses a simpler approach - export and import only the needed columns

echo "=== Final Production Data Merge ==="
echo ""

# Configuration
DB_NAME="illum_local"
DB_USER="ericslarson"
TEMP_DB="temp_production"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}This will import production regions and directory_entries.${NC}"
echo "Your development data (likes, comments, reposts, chains) will be preserved."
read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "1. Creating final backup..."
pg_dump -U $DB_USER $DB_NAME > backups/illum_local_$(date +%Y_%m_%d_%H%M)_final_before_merge.sql
echo -e "${GREEN}✓ Backup created${NC}"

echo ""
echo "2. Exporting production data with only common columns..."

# Get common columns for regions
psql -U $DB_USER -d $DB_NAME -t -A -c "
SELECT string_agg(column_name, ',' ORDER BY ordinal_position) 
FROM information_schema.columns 
WHERE table_name = 'regions' 
AND table_schema = 'public'
AND column_name IN (
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name = 'regions' 
    AND table_schema = 'public' 
    AND table_catalog = '$TEMP_DB'
)" > /tmp/region_columns.txt

REGION_COLS=$(cat /tmp/region_columns.txt)

# Get common columns for directory_entries
psql -U $DB_USER -d $DB_NAME -t -A -c "
SELECT string_agg(column_name, ',' ORDER BY ordinal_position) 
FROM information_schema.columns 
WHERE table_name = 'directory_entries' 
AND table_schema = 'public'
AND column_name IN (
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name = 'directory_entries' 
    AND table_schema = 'public' 
    AND table_catalog = '$TEMP_DB'
)" > /tmp/entry_columns.txt

ENTRY_COLS=$(cat /tmp/entry_columns.txt)

# Export regions with type casting
psql -U $DB_USER $TEMP_DB -c "
COPY (
    SELECT id, name, type, parent_id, created_at, updated_at, 
           level, slug, metadata::text, cached_place_count
    FROM regions
    ORDER BY id
) TO '/tmp/regions_export.csv' WITH CSV HEADER;
"

# Export directory_entries (simplified)
psql -U $DB_USER $TEMP_DB -c "
COPY (
    SELECT id, title, slug, description, type, category_id, region_id, 
           created_at, updated_at, status, is_featured, is_verified
    FROM directory_entries
    WHERE id IS NOT NULL
    ORDER BY id
) TO '/tmp/entries_export.csv' WITH CSV HEADER;
"

echo -e "${GREEN}✓ Data exported${NC}"

echo ""
echo "3. Importing to local database..."

# Create import script
cat > /tmp/import_data.sql << 'EOF'
BEGIN;

-- Backup development data
CREATE TEMP TABLE backup_likes AS SELECT * FROM likes;
CREATE TEMP TABLE backup_comments AS SELECT * FROM comments;
CREATE TEMP TABLE backup_reposts AS SELECT * FROM reposts;
CREATE TEMP TABLE backup_chains AS SELECT * FROM list_chains;
CREATE TEMP TABLE backup_chain_items AS SELECT * FROM list_chain_items;
CREATE TEMP TABLE backup_claims AS SELECT * FROM claims;

-- Disable constraints
SET session_replication_role = 'replica';

-- Clear regions and directory_entries
TRUNCATE TABLE regions CASCADE;
TRUNCATE TABLE directory_entries CASCADE;

-- Import regions (basic columns only)
CREATE TEMP TABLE temp_regions (
    id bigint,
    name varchar(255),
    type varchar(255),
    parent_id bigint,
    created_at timestamp,
    updated_at timestamp,
    level integer,
    slug varchar(255),
    metadata text,
    cached_place_count integer
);

\copy temp_regions FROM '/tmp/regions_export.csv' WITH CSV HEADER

INSERT INTO regions (id, name, type, parent_id, created_at, updated_at, level, slug, metadata, cached_place_count)
SELECT id, name, type, parent_id, created_at, updated_at, level, slug, 
       CASE WHEN metadata = '' OR metadata IS NULL THEN NULL ELSE metadata::jsonb END,
       cached_place_count
FROM temp_regions;

-- Import directory_entries (basic columns only)
CREATE TEMP TABLE temp_entries (
    id bigint,
    title varchar(255),
    slug varchar(255),
    description text,
    type varchar(255),
    category_id bigint,
    region_id bigint,
    created_at timestamp,
    updated_at timestamp,
    status varchar(255),
    is_featured boolean,
    is_verified boolean
);

\copy temp_entries FROM '/tmp/entries_export.csv' WITH CSV HEADER

INSERT INTO directory_entries (id, title, slug, description, type, category_id, region_id, 
                              created_at, updated_at, status, is_featured, is_verified, created_by_user_id)
SELECT id, title, slug, description, type, category_id, region_id,
       created_at, updated_at, 
       COALESCE(status, 'draft'),
       COALESCE(is_featured, false),
       COALESCE(is_verified, false),
       1  -- Default to admin user
FROM temp_entries;

-- Restore development data
INSERT INTO likes SELECT * FROM backup_likes;
INSERT INTO comments SELECT * FROM backup_comments;
INSERT INTO reposts SELECT * FROM backup_reposts;
INSERT INTO list_chains SELECT * FROM backup_chains;
INSERT INTO list_chain_items SELECT * FROM backup_chain_items;
INSERT INTO claims SELECT * FROM backup_claims;

-- Re-enable constraints
SET session_replication_role = 'origin';

-- Update sequences
SELECT setval('regions_id_seq', COALESCE((SELECT MAX(id) FROM regions), 1));
SELECT setval('directory_entries_id_seq', COALESCE((SELECT MAX(id) FROM directory_entries), 1));

COMMIT;
EOF

psql -U $DB_USER $DB_NAME < /tmp/import_data.sql

echo ""
echo "4. Verifying import..."
psql -U $DB_USER $DB_NAME -c "
SELECT 'Regions' as type, COUNT(*) as count FROM regions
UNION ALL
SELECT 'Directory Entries', COUNT(*) FROM directory_entries
UNION ALL
SELECT 'Likes', COUNT(*) FROM likes
UNION ALL
SELECT 'Comments', COUNT(*) FROM comments
UNION ALL
SELECT 'Reposts', COUNT(*) FROM reposts
UNION ALL
SELECT 'List Chains', COUNT(*) FROM list_chains
UNION ALL
SELECT 'Claims', COUNT(*) FROM claims;"

echo ""
echo "5. Cleaning up..."
rm -f /tmp/regions_export.csv /tmp/entries_export.csv /tmp/import_data.sql
rm -f /tmp/region_columns.txt /tmp/entry_columns.txt
dropdb --if-exists $TEMP_DB

echo ""
echo -e "${GREEN}=== Import Complete ===${NC}"
echo "Your database now contains:"
echo "- Production regions (with proper hierarchy)"
echo "- Production directory entries"
echo "- All your development features preserved"
echo ""
echo "Latest backup: backups/illum_local_$(date +%Y_%m_%d_%H%M)_final_before_merge.sql"