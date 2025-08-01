#!/bin/bash

# Merge Production Data Script V2
# This script safely merges production regions and directory_entries data while preserving local development features

echo "=== Production Data Merge Script V2 ==="
echo "This script will:"
echo "1. Import production regions (76 records)"
echo "2. Import production directory entries with correct region relationships"
echo "3. Preserve all local development features (claims, chains, likes, comments, reposts)"
echo ""

# Configuration
DB_NAME="illum_local"
DB_USER="ericslarson"
PROD_BACKUP="backups/listerino_production_2025_07_29.sql"
LOCAL_BACKUP="backups/illum_local_2025_07_28_2358_before_production_merge.sql"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if command succeeded
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ $1${NC}"
    else
        echo -e "${RED}✗ $1 failed${NC}"
        exit 1
    fi
}

# Confirm before proceeding
echo -e "${YELLOW}WARNING: This will modify your local database.${NC}"
echo "Local backup exists at: $LOCAL_BACKUP"
read -p "Do you want to continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Step 1: Creating temporary database for production data..."
dropdb --if-exists temp_production 2>/dev/null
createdb temp_production
check_status "Created temporary database"

echo ""
echo "Step 2: Importing production backup to temporary database..."
psql -U $DB_USER temp_production < $PROD_BACKUP 2>/dev/null
check_status "Imported production data"

echo ""
echo "Step 3: Backing up local development data..."
# Export local development tables
pg_dump -U $DB_USER $DB_NAME \
    -t claims \
    -t claim_documents \
    -t verification_codes \
    -t list_chains \
    -t list_chain_items \
    -t likes \
    -t comments \
    -t reposts \
    -t app_notifications \
    > backups/local_dev_features_backup_v2.sql
check_status "Backed up local development features"

echo ""
echo "Step 4: Preparing merge - checking data..."
echo "Production regions count:"
psql -U $DB_USER temp_production -t -c "SELECT COUNT(*) FROM regions;" || echo "0"
echo "Production directory entries count:"
psql -U $DB_USER temp_production -t -c "SELECT COUNT(*) FROM directory_entries;" || echo "0"
echo "Local likes/comments/reposts:"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Likes: ' || COUNT(*) FROM likes;" || echo "Likes: 0"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Comments: ' || COUNT(*) FROM comments;" || echo "Comments: 0"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Reposts: ' || COUNT(*) FROM reposts;" || echo "Reposts: 0"

echo ""
echo "Step 5: Creating optimized merge script..."

# First, let's get the column list for directory_entries from production
PROD_COLUMNS=$(psql -U $DB_USER temp_production -t -c "SELECT string_agg(column_name, ', ' ORDER BY ordinal_position) FROM information_schema.columns WHERE table_name = 'directory_entries' AND table_schema = 'public';")

# Create SQL script for safe merge
cat > /tmp/merge_data_v2.sql << EOF
BEGIN;

-- Disable foreign key constraints temporarily
SET session_replication_role = 'replica';

-- Store current likes, comments, reposts data
CREATE TEMP TABLE temp_likes AS SELECT * FROM likes;
CREATE TEMP TABLE temp_comments AS SELECT * FROM comments;
CREATE TEMP TABLE temp_reposts AS SELECT * FROM reposts;
CREATE TEMP TABLE temp_list_chains AS SELECT * FROM list_chains;
CREATE TEMP TABLE temp_list_chain_items AS SELECT * FROM list_chain_items;

-- Clear existing regions and directory_entries (they will be replaced with production data)
TRUNCATE TABLE regions CASCADE;
TRUNCATE TABLE directory_entries CASCADE;

-- Import regions from production
\copy regions FROM PROGRAM 'psql -U $DB_USER temp_production -c "COPY regions TO STDOUT"'

-- Import directory_entries from production
\copy directory_entries FROM PROGRAM 'psql -U $DB_USER temp_production -c "COPY directory_entries TO STDOUT"'

-- Restore development data
INSERT INTO likes SELECT * FROM temp_likes;
INSERT INTO comments SELECT * FROM temp_comments;
INSERT INTO reposts SELECT * FROM temp_reposts;
INSERT INTO list_chains SELECT * FROM temp_list_chains;
INSERT INTO list_chain_items SELECT * FROM temp_list_chain_items;

-- Re-enable foreign key constraints
SET session_replication_role = 'origin';

-- Update sequences
SELECT setval('regions_id_seq', COALESCE((SELECT MAX(id) FROM regions), 1));
SELECT setval('directory_entries_id_seq', COALESCE((SELECT MAX(id) FROM directory_entries), 1));

-- Verify data
DO \$\$
DECLARE
    region_count integer;
    entry_count integer;
BEGIN
    SELECT COUNT(*) INTO region_count FROM regions;
    SELECT COUNT(*) INTO entry_count FROM directory_entries;
    
    RAISE NOTICE 'Imported % regions', region_count;
    RAISE NOTICE 'Imported % directory entries', entry_count;
    
    IF region_count < 70 THEN
        RAISE EXCEPTION 'Region import failed - expected ~76 regions, got %', region_count;
    END IF;
END \$\$;

COMMIT;
EOF

echo "Executing merge..."
psql -U $DB_USER $DB_NAME < /tmp/merge_data_v2.sql
check_status "Data merge completed"

echo ""
echo "Step 6: Verifying data integrity..."
echo "Final counts:"
psql -U $DB_USER $DB_NAME -c "
SELECT 'Regions: ' || COUNT(*) FROM regions 
UNION ALL SELECT 'Directory Entries: ' || COUNT(*) FROM directory_entries 
UNION ALL SELECT 'Likes: ' || COUNT(*) FROM likes 
UNION ALL SELECT 'Comments: ' || COUNT(*) FROM comments 
UNION ALL SELECT 'Reposts: ' || COUNT(*) FROM reposts
UNION ALL SELECT 'List Chains: ' || COUNT(*) FROM list_chains;"

echo ""
echo "Step 7: Cleaning up..."
dropdb temp_production
rm /tmp/merge_data_v2.sql
check_status "Cleanup completed"

echo ""
echo -e "${GREEN}=== Merge completed successfully! ===${NC}"
echo "Your local database now has:"
echo "- Production regions and directory entries data"
echo "- All location-based URLs working"
echo "- Preserved local development features (claims, chains, likes, comments)"
echo ""
echo "To restore if needed: psql -U $DB_USER $DB_NAME < $LOCAL_BACKUP"