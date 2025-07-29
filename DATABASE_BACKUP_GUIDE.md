# Database Backup & Restore Guide

This guide documents the database backup process and current restore points.

## Current Database Information

- **Database Name**: illum_local
- **Database Type**: PostgreSQL 15+
- **Database User**: ericslarson (update with your username)

## Latest Backup Points

### Development Checkpoint - July 29, 2025, 06:32 AM PDT
- **File**: `backups/illum_local_2025_07_29_0632_dev_checkpoint.sql`
- **Description**: Stable development checkpoint with:
  - All core features implemented
  - Business claiming system
  - List chains functionality
  - Social features (likes, comments, reposts)
  - Admin notification system
  - Complete region hierarchy
  - Sample data from seeders

## Backup Commands

### Create a Backup
```bash
# Basic backup
pg_dump -U your_username illum_local > backups/backup_name.sql

# Timestamped backup (recommended)
pg_dump -U your_username illum_local > backups/illum_local_$(date +%Y_%m_%d_%H%M)_description.sql

# Compressed backup for large databases
pg_dump -U your_username illum_local | gzip > backups/illum_local_$(date +%Y_%m_%d_%H%M).sql.gz
```

### Backup Naming Convention
Format: `illum_local_YYYY_MM_DD_HHMM_description.sql`

Examples:
- `illum_local_2025_07_29_0632_dev_checkpoint.sql`
- `illum_local_2025_07_29_1200_before_major_refactor.sql`
- `illum_local_2025_07_29_1500_production_sync.sql`

## Restore Commands

### Restore from Backup

**WARNING**: This will completely replace your current database!

```bash
# First, recreate the database
dropdb illum_local
createdb illum_local

# Then restore from backup
psql -U your_username illum_local < backups/illum_local_2025_07_29_0632_dev_checkpoint.sql

# For compressed backups
gunzip < backups/backup_name.sql.gz | psql -U your_username illum_local
```

### Partial Restore (Advanced)
To restore specific tables only:
```bash
# Extract specific tables from backup
pg_restore -t table_name backup_file.sql > table_only.sql

# Apply to database
psql -U your_username illum_local < table_only.sql
```

## Backup Strategy

### Development Environment
1. **Before Major Changes**: Always backup before:
   - Running destructive migrations
   - Major refactoring
   - Experimenting with data
   
2. **Regular Checkpoints**: Create backups at:
   - End of each development session
   - After implementing major features
   - Before merging significant branches

3. **Naming Best Practices**:
   - Use descriptive suffixes: `_before_auth_refactor`, `_stable_v1`, etc.
   - Include timestamp for chronological ordering
   - Keep a log of what each backup contains

### Backup Verification
After creating a backup, verify it:
```bash
# Check file size (should be > 200KB for a populated database)
ls -lh backups/illum_local_2025_07_29_0632_dev_checkpoint.sql

# View first few lines
head -20 backups/illum_local_2025_07_29_0632_dev_checkpoint.sql

# Count tables in backup
grep -c "CREATE TABLE" backups/illum_local_2025_07_29_0632_dev_checkpoint.sql
```

## Automated Backup Script

Create a script for regular backups:

```bash
#!/bin/bash
# save as: scripts/backup-db.sh

DB_NAME="illum_local"
DB_USER="your_username"
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y_%m_%d_%H%M)

# Create backup
pg_dump -U $DB_USER $DB_NAME > $BACKUP_DIR/${DB_NAME}_${TIMESTAMP}_auto.sql

# Keep only last 10 backups
ls -t $BACKUP_DIR/${DB_NAME}_*_auto.sql | tail -n +11 | xargs rm -f 2>/dev/null

echo "Backup created: ${DB_NAME}_${TIMESTAMP}_auto.sql"
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   # Grant permissions
   psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE illum_local TO your_username;"
   ```

2. **Database Doesn't Exist**
   ```bash
   createdb -U your_username illum_local
   ```

3. **Connection Refused**
   ```bash
   # Check if PostgreSQL is running
   pg_isready
   
   # Start PostgreSQL (macOS)
   brew services start postgresql@15
   ```

4. **Backup File Too Large**
   ```bash
   # Use compression
   pg_dump -U your_username illum_local | gzip > backup.sql.gz
   
   # Split large files
   split -b 100m backup.sql backup_part_
   ```

## Important Backup Files History

1. **illum_local_2025_07_29_0632_dev_checkpoint.sql**
   - Latest stable development checkpoint
   - All features working
   - Good restore point for development

2. **illum_local_2025_07_28_2350_dev_checkpoint.sql**
   - Previous checkpoint
   - Before latest social features

3. **illum_local_2025_07_18.sql**
   - Older backup
   - Basic features only

## Production Considerations

For production databases:
1. Use `pg_dump` with additional flags:
   ```bash
   pg_dump -U username -h hostname -p 5432 -d database_name -v -Fc > backup.dump
   ```

2. Set up automated daily backups via cron
3. Store backups in multiple locations (local + cloud)
4. Test restore process regularly
5. Document what data each backup contains

---

Last Updated: July 29, 2025
Current Stable Checkpoint: `illum_local_2025_07_29_0632_dev_checkpoint.sql`