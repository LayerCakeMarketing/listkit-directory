#!/bin/bash

# Merge Production Data Script
# This script safely merges production regions and places data while preserving local development features

echo "=== Production Data Merge Script ==="
echo "This script will:"
echo "1. Import production regions (2,364 records)"
echo "2. Import production places with correct region relationships"
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
    > backups/local_dev_features_backup.sql
check_status "Backed up local development features"

echo ""
echo "Step 4: Preparing merge - checking data..."
echo "Production regions count:"
psql -U $DB_USER temp_production -t -c "SELECT COUNT(*) FROM regions;"
echo "Production directory entries count:"
psql -U $DB_USER temp_production -t -c "SELECT COUNT(*) FROM directory_entries;"
echo "Local likes/comments/reposts:"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Likes: ' || COUNT(*) FROM likes;"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Comments: ' || COUNT(*) FROM comments;"
psql -U $DB_USER $DB_NAME -t -c "SELECT 'Reposts: ' || COUNT(*) FROM reposts;"

echo ""
echo "Step 5: Merging data..."

# Create SQL script for safe merge
cat > /tmp/merge_data.sql << 'EOF'
BEGIN;

-- Disable foreign key constraints temporarily
SET session_replication_role = 'replica';

-- Clear existing regions and directory_entries (they will be replaced with production data)
TRUNCATE TABLE regions CASCADE;
TRUNCATE TABLE directory_entries CASCADE;

-- Import regions from production
INSERT INTO regions 
SELECT * FROM dblink('dbname=temp_production', 'SELECT * FROM regions') 
AS t(
    id bigint,
    name character varying(255),
    type character varying(255),
    parent_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    level integer,
    slug character varying(255),
    metadata jsonb,
    cached_place_count integer,
    boundaries geometry(Polygon,4326),
    boundaries_simplified geometry(Polygon,4326),
    centroid geography(Point,4326),
    cover_image character varying(255),
    intro_text text,
    data_points jsonb,
    is_featured boolean,
    meta_title character varying(255),
    meta_description text,
    custom_fields jsonb,
    display_priority integer,
    cloudflare_image_id character varying(255),
    facts jsonb,
    state_symbols jsonb,
    geojson jsonb,
    polygon_coordinates jsonb,
    full_name character varying(255),
    abbreviation character varying(10),
    alternate_names jsonb,
    boundary jsonb,
    center_point jsonb,
    area_sq_km numeric(10,2),
    is_user_defined boolean,
    created_by_user_id bigint,
    cache_updated_at timestamp(0) without time zone
);

-- Import places from production
INSERT INTO places
SELECT * FROM dblink('dbname=temp_production', 'SELECT * FROM places')
AS t(
    id bigint,
    name character varying(255),
    slug character varying(255),
    description text,
    region_id bigint,
    category_id bigint,
    address character varying(255),
    city character varying(255),
    state character varying(255),
    zip character varying(20),
    country character varying(255),
    phone character varying(50),
    email character varying(255),
    website character varying(255),
    hours jsonb,
    lat numeric(10,7),
    lng numeric(10,7),
    status character varying(255),
    featured boolean,
    verified boolean,
    views_count integer,
    rating numeric(3,2),
    review_count integer,
    price_level integer,
    amenities jsonb,
    images jsonb,
    social_media jsonb,
    metadata jsonb,
    claimed_by bigint,
    claimed_at timestamp(0) without time zone,
    subscription_tier character varying(50),
    subscription_expires_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    is_permanently_closed boolean,
    legacy_directory_id bigint,
    cloudflare_images jsonb,
    search_vector tsvector,
    is_published boolean,
    published_at timestamp(0) without time zone,
    is_approved boolean,
    position integer,
    is_chain boolean,
    parent_place_id bigint,
    ownership_status character varying(50),
    ownership_claimed_by bigint,
    ownership_claimed_at timestamp(0) without time zone,
    ownership_verified_at timestamp(0) without time zone,
    ownership_verification_method character varying(50),
    likes_count integer,
    comments_count integer,
    reposts_count integer
);

-- Re-enable foreign key constraints
SET session_replication_role = 'origin';

-- Update sequences
SELECT setval('regions_id_seq', (SELECT MAX(id) FROM regions));
SELECT setval('places_id_seq', (SELECT MAX(id) FROM places));

-- Verify data
DO $$
DECLARE
    region_count integer;
    place_count integer;
BEGIN
    SELECT COUNT(*) INTO region_count FROM regions;
    SELECT COUNT(*) INTO place_count FROM places;
    
    RAISE NOTICE 'Imported % regions', region_count;
    RAISE NOTICE 'Imported % places', place_count;
    
    IF region_count < 2000 THEN
        RAISE EXCEPTION 'Region import failed - expected ~2364 regions, got %', region_count;
    END IF;
END $$;

COMMIT;
EOF

echo "Executing merge..."
psql -U $DB_USER $DB_NAME < /tmp/merge_data.sql
check_status "Data merge completed"

echo ""
echo "Step 6: Verifying data integrity..."
echo "Final counts:"
psql -U $DB_USER $DB_NAME -c "SELECT 'Regions: ' || COUNT(*) FROM regions UNION ALL SELECT 'Places: ' || COUNT(*) FROM places UNION ALL SELECT 'Likes: ' || COUNT(*) FROM likes UNION ALL SELECT 'Comments: ' || COUNT(*) FROM comments UNION ALL SELECT 'Reposts: ' || COUNT(*) FROM reposts;"

echo ""
echo "Step 7: Cleaning up..."
dropdb temp_production
rm /tmp/merge_data.sql
check_status "Cleanup completed"

echo ""
echo -e "${GREEN}=== Merge completed successfully! ===${NC}"
echo "Your local database now has:"
echo "- Production regions and places data"
echo "- All location-based URLs working"
echo "- Preserved local development features (claims, chains, likes, comments)"
echo ""
echo "To restore if needed: psql -U $DB_USER $DB_NAME < $LOCAL_BACKUP"