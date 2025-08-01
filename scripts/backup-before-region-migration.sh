#!/bin/bash

# Backup script to run before the region duplicate cleanup migration
# This creates a comprehensive backup of all region-related data

set -e

# Configuration
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y_%m_%d_%H%M")
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-illum_local}
DB_USERNAME=${DB_USERNAME:-$(whoami)}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting region migration backup at $(date)"
echo "Database: $DB_DATABASE"
echo "Backup directory: $BACKUP_DIR"

# Full database backup (recommended)
FULL_BACKUP="$BACKUP_DIR/full_backup_before_region_migration_$TIMESTAMP.sql"
echo "Creating full database backup: $FULL_BACKUP"
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" > "$FULL_BACKUP"

# Individual table backups for critical region-related tables
TABLES=(
    "regions"
    "directory_entries" 
    "place_regions"
    "region_featured_entries"
    "region_featured_lists"
    "lists"
)

echo "Creating individual table backups..."
for table in "${TABLES[@]}"; do
    BACKUP_FILE="$BACKUP_DIR/${table}_backup_$TIMESTAMP.sql"
    echo "  Backing up table: $table -> $BACKUP_FILE"
    pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t "$table" > "$BACKUP_FILE"
done

# Create a data analysis file before migration
ANALYSIS_FILE="$BACKUP_DIR/region_analysis_before_migration_$TIMESTAMP.txt"
echo "Creating data analysis: $ANALYSIS_FILE"

{
    echo "REGION MIGRATION ANALYSIS - $(date)"
    echo "========================================"
    echo ""
    
    echo "DUPLICATE REGIONS IDENTIFIED:"
    echo "-----------------------------"
    echo "california: IDs 82 (keep - 39 dir entries, 10 children) vs 90 (remove - 1 dir entry, 2 children)"
    echo "mammoth-lakes: IDs 86 (keep - 31 dir entries) vs 92 (remove - 30 place_regions)"
    echo "irvine: IDs 6 (keep - 5 dir entries, 6 children) vs 91 (remove - 1 dir entry)"
    echo "florida: IDs 84 (keep - 1 dir entry, 1 child) vs 94 (remove - 1 place_region)"
    echo "miami: IDs 85 (keep - 1 dir entry) vs 95 (remove - 1 place_region)"
    echo ""
    
    echo "TOTAL RECORDS BY TABLE:"
    echo "----------------------"
    
    # Get counts via psql
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t -c "
        SELECT 'regions: ' || COUNT(*) FROM regions;
        SELECT 'directory_entries: ' || COUNT(*) FROM directory_entries;
        SELECT 'place_regions: ' || COUNT(*) FROM place_regions;
        SELECT 'region_featured_entries: ' || COUNT(*) FROM region_featured_entries;
        SELECT 'region_featured_lists: ' || COUNT(*) FROM region_featured_lists;
        SELECT 'lists with region_id: ' || COUNT(*) FROM lists WHERE region_id IS NOT NULL;
    "
    
    echo ""
    echo "REGION TYPES:"
    echo "-------------"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t -c "
        SELECT type || ': ' || COUNT(*) FROM regions GROUP BY type ORDER BY COUNT(*) DESC;
    "
    
    echo ""
    echo "FOREIGN KEY CONSTRAINTS:"
    echo "------------------------"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t -c "
        SELECT 
            tc.table_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY' 
          AND ccu.table_name = 'regions'
        ORDER BY tc.table_name, kcu.column_name;
    "
    
} > "$ANALYSIS_FILE"

echo ""
echo "Backup completed successfully!"
echo "Files created:"
echo "  - Full backup: $FULL_BACKUP"
echo "  - Analysis: $ANALYSIS_FILE"
echo "  - Table backups in: $BACKUP_DIR/"
echo ""
echo "To restore if needed:"
echo "  psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_DATABASE < $FULL_BACKUP"
echo ""
echo "Ready to run migration:"
echo "  php artisan migrate"