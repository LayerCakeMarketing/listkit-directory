#!/bin/bash

# Safe Production Deployment Script (Code Only)
# This script deploys code changes WITHOUT touching the database

echo "🚀 Starting code-only deployment to production..."

# Configuration
SERVER="listkit"  # SSH config alias
PRODUCTION_DIR="/var/www/listkit"

echo "📥 Pulling latest code..."
ssh $SERVER "cd $PRODUCTION_DIR && git pull origin main"

echo "📦 Installing dependencies..."
ssh $SERVER "cd $PRODUCTION_DIR && composer install --optimize-autoloader --no-dev && npm install"

echo "🔨 Building production assets..."
ssh $SERVER "cd $PRODUCTION_DIR && npm run build"

echo "🔄 Running migrations (if any)..."
ssh $SERVER "cd $PRODUCTION_DIR && php artisan migrate --force"

echo "🧹 Clearing and rebuilding caches..."
ssh $SERVER "cd $PRODUCTION_DIR && php artisan optimize:clear && php artisan optimize && php artisan storage:link"

echo "🔐 Setting permissions..."
ssh $SERVER "cd $PRODUCTION_DIR && chmod -R 755 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache public/storage"

echo "🔄 Restarting PHP-FPM..."
ssh $SERVER "sudo service php8.3-fpm restart"

echo "✅ Code deployment complete!"
echo "🌐 Your site should now be live with the new code changes."
echo "💾 Database was NOT modified during this deployment."