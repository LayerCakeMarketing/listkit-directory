#!/bin/bash
set -e

echo "🔄 Deploying Cloudflare authentication fix to listerino.com..."

# Server details
SERVER="root@137.184.113.161"
REMOTE_PATH="/var/www/listerino"

# Files to update
FILES_TO_UPDATE=(
    "app/Services/CloudflareImageService.php"
    "app/Http/Controllers/Admin/MediaController.php"
)

echo "📤 Uploading updated files..."
for file in "${FILES_TO_UPDATE[@]}"; do
    echo "   - Uploading $file"
    scp "$file" "$SERVER:$REMOTE_PATH/$file"
done

echo "🔧 Running post-deployment tasks on server..."
ssh "$SERVER" << 'EOF'
cd /var/www/listerino

# Clear caches
echo "🧹 Clearing caches..."
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart PHP-FPM to ensure changes take effect
echo "🔄 Restarting services..."
systemctl restart php8.3-fpm

echo "✅ Deployment complete!"
EOF

echo "🌐 Cloudflare authentication fix deployed to https://listerino.com"
echo "📸 Test the fix at: https://listerino.com/admin/media"