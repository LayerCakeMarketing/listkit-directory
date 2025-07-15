#!/bin/bash
# Server Cleanup Script - Removes old backup files and patches
# Safe to run via cron - logs all deletions

# Configuration
CLEANUP_AGE_DAYS=7
LOG_DIR="/root/maintenance"
LOG_FILE="$LOG_DIR/cleanup.log"
DRY_RUN=false

# Parse command line arguments
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true ;;
        *) echo "Unknown parameter: $1"; exit 1 ;;
    esac
    shift
done

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to safely delete files
safe_delete() {
    local file="$1"
    local file_size=$(stat -c%s "$file" 2>/dev/null || echo "0")
    local file_date=$(stat -c%y "$file" 2>/dev/null | cut -d' ' -f1 || echo "unknown")
    
    if [ "$DRY_RUN" = true ]; then
        log_message "[DRY RUN] Would delete: $file (size: $file_size bytes, date: $file_date)"
    else
        if rm -f "$file"; then
            log_message "Deleted: $file (size: $file_size bytes, date: $file_date)"
        else
            log_message "ERROR: Failed to delete: $file"
        fi
    fi
}

# Start cleanup
log_message "=== Starting cleanup process (dry_run: $DRY_RUN) ==="

# Count files before cleanup
TOTAL_FILES=0
DELETED_FILES=0

# Clean up root directory
log_message "Cleaning /root directory..."
while IFS= read -r -d '' file; do
    ((TOTAL_FILES++))
    safe_delete "$file"
    ((DELETED_FILES++))
done < <(find /root -maxdepth 1 -type f \( -name "*.sql" -o -name "*.gz" -o -name "*.tar" -o -name "*.patch" -o -name "*.tar.gz" \) -mtime +$CLEANUP_AGE_DAYS -print0 2>/dev/null)

# Clean up /tmp directory
log_message "Cleaning /tmp directory..."
while IFS= read -r -d '' file; do
    ((TOTAL_FILES++))
    safe_delete "$file"
    ((DELETED_FILES++))
done < <(find /tmp -maxdepth 1 -type f \( -name "*.sql" -o -name "*.patch" -o -name "*.sh" -o -name "listerino-*" -o -name "listkit-*" \) -mtime +$CLEANUP_AGE_DAYS -print0 2>/dev/null)

# Clean up old Laravel logs (keep last 30 days)
log_message "Cleaning old Laravel logs..."
for www_dir in /var/www/*/storage/logs; do
    if [ -d "$www_dir" ]; then
        while IFS= read -r -d '' file; do
            ((TOTAL_FILES++))
            safe_delete "$file"
            ((DELETED_FILES++))
        done < <(find "$www_dir" -name "laravel-*.log" -mtime +30 -print0 2>/dev/null)
    fi
done

# Clean up old nginx logs (keep last 30 days)
log_message "Cleaning old nginx logs..."
while IFS= read -r -d '' file; do
    ((TOTAL_FILES++))
    safe_delete "$file"
    ((DELETED_FILES++))
done < <(find /var/log/nginx -name "*.log.*.gz" -mtime +30 -print0 2>/dev/null)

# Summary
if [ "$DRY_RUN" = true ]; then
    log_message "=== DRY RUN completed. Would delete $DELETED_FILES out of $TOTAL_FILES old files ==="
else
    log_message "=== Cleanup completed. Deleted $DELETED_FILES out of $TOTAL_FILES old files ==="
fi

# Rotate cleanup log if it's getting large (>10MB)
if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE" 2>/dev/null || echo 0) -gt 10485760 ]; then
    mv "$LOG_FILE" "$LOG_FILE.old"
    gzip "$LOG_FILE.old"
    log_message "Log file rotated due to size"
fi

exit 0