#!/bin/bash

# Backup script for Listerino
# Run this as a cron job for regular backups

set -e

# Configuration
BACKUP_DIR="/var/backups/listerino"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_CONTAINER="listerino_db"
APP_DIR="/var/www/listerino"

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

echo "Starting backup at $(date)"

# Backup database
echo "Backing up database..."
docker exec $DB_CONTAINER pg_dump -U listerino listerino | gzip > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# Backup uploaded files
echo "Backing up uploaded files..."
tar -czf "$BACKUP_DIR/storage_backup_$TIMESTAMP.tar.gz" -C "$APP_DIR" storage/app/public

# Backup .env file
echo "Backing up environment file..."
cp "$APP_DIR/.env" "$BACKUP_DIR/env_backup_$TIMESTAMP"

# Keep only last 7 days of backups
echo "Cleaning old backups..."
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed at $(date)"
echo "Backup files:"
ls -lh $BACKUP_DIR/*$TIMESTAMP*