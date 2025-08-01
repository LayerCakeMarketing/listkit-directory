#!/bin/bash

# Restore User-Related Data After Merge
# This restores users, channels, lists, and posts that were lost during the merge

DB_NAME="illum_local"
DB_USER="ericslarson"
BACKUP_FILE="backups/illum_local_2025_07_28_2358_before_production_merge.sql"

echo "Restoring user-related data..."

# Create a temporary SQL file with the data we need
cat > /tmp/restore_user_data.sql << 'EOF'
BEGIN;

-- First ensure we have the users (already done but let's verify)
SELECT 'Current users: ' || COUNT(*) FROM users;

-- Now restore the other tables from the SQL dump
EOF

# Extract channels data
echo "-- Channels data" >> /tmp/restore_user_data.sql
sed -n '/^COPY public\.channels/,/^\\\.$/p' $BACKUP_FILE | sed '1d;$d' | while IFS= read -r line; do
    echo "INSERT INTO channels VALUES (${line//	/', '});" | sed "s/''/NULL/g" >> /tmp/restore_user_data.sql
done

# Extract lists data  
echo "-- Lists data" >> /tmp/restore_user_data.sql
sed -n '/^COPY public\.lists/,/^\\\.$/p' $BACKUP_FILE | head -20 | sed '1d;$d' | while IFS= read -r line; do
    # Parse the tab-delimited data more carefully
    echo "$line" >> /tmp/lists_raw.txt
done

# Extract posts data
echo "-- Posts data" >> /tmp/restore_user_data.sql  
sed -n '/^COPY public\.posts/,/^\\\.$/p' $BACKUP_FILE | sed '1d;$d' | while IFS= read -r line; do
    echo "$line" >> /tmp/posts_raw.txt
done

echo "COMMIT;" >> /tmp/restore_user_data.sql

# Alternative approach - use pg_dump format
echo "Using direct COPY commands..."

psql -U $DB_USER $DB_NAME << SQL
BEGIN;

-- Import channels
COPY channels FROM STDIN;
$(sed -n '/^COPY public\.channels/,/^\\\.$/p' $BACKUP_FILE | sed '1d;$d')
\.

-- Import lists
COPY lists FROM STDIN;
$(sed -n '/^COPY public\.lists/,/^\\\.$/p' $BACKUP_FILE | sed '1d;$d')
\.

-- Import posts
COPY posts FROM STDIN;
$(sed -n '/^COPY public\.posts/,/^\\\.$/p' $BACKUP_FILE | sed '1d;$d')
\.

-- Update sequences
SELECT setval('channels_id_seq', COALESCE((SELECT MAX(id) FROM channels), 1));
SELECT setval('lists_id_seq', COALESCE((SELECT MAX(id) FROM lists), 1));
SELECT setval('posts_id_seq', COALESCE((SELECT MAX(id) FROM posts), 1));

COMMIT;
SQL

echo ""
echo "Verification:"
psql -U $DB_USER $DB_NAME -c "
SELECT 'Users: ' || COUNT(*) FROM users
UNION ALL  
SELECT 'Channels: ' || COUNT(*) FROM channels
UNION ALL
SELECT 'Lists: ' || COUNT(*) FROM lists
UNION ALL
SELECT 'Posts: ' || COUNT(*) FROM posts
UNION ALL
SELECT 'Directory Entries: ' || COUNT(*) FROM directory_entries
UNION ALL
SELECT 'Regions: ' || COUNT(*) FROM regions;"

echo ""
echo "User data restoration complete!"
echo "You should now be able to log in with:"
echo "- admin@example.com / password"
echo "- eric@layercakemarketing.com / password"