#!/bin/bash

# Simple deployment script for listerino.com after migration

set -e

SERVER="root@137.184.113.161"
DEPLOY_PATH="/var/www/listerino"

echo "🚀 Deploying to listerino.com..."

# Pull latest code
echo "📥 Pulling latest code..."
ssh $SERVER "cd $DEPLOY_PATH && git pull origin main"

# Install dependencies
echo "📦 Installing dependencies..."
ssh $SERVER "cd $DEPLOY_PATH && composer install --optimize-autoloader --no-dev && npm ci"

# Build assets
echo "🔨 Building assets..."
ssh $SERVER "cd $DEPLOY_PATH && npm run build"

# Run migrations
echo "🗄️ Running migrations..."
ssh $SERVER "cd $DEPLOY_PATH && php artisan migrate --force"

# Clear caches
echo "🧹 Clearing caches..."
ssh $SERVER "cd $DEPLOY_PATH && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"

# Set permissions
echo "🔐 Setting permissions..."
ssh $SERVER "chown -R www-data:www-data $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache"

# Restart services
echo "🔄 Restarting services..."
ssh $SERVER "systemctl restart php8.3-fpm && nginx -s reload"

echo "✅ Deployment complete!"