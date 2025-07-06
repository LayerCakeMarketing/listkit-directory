#!/bin/bash

echo "🔧 Fixing production templates..."

# Clear everything
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Remove compiled files
echo "🗑️  Removing compiled files..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*
rm -rf public/build

# Rebuild
echo "🔨 Rebuilding assets..."
npm run build

# Recreate caches
echo "📦 Recreating caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
echo "🔐 Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache public/build
chmod -R 755 storage bootstrap/cache

# Restart services
echo "🔄 Restarting services..."
sudo service php8.2-fpm restart

echo "✅ Template fix complete!"
echo "🌐 Please clear your browser cache (Ctrl+F5) and try again."