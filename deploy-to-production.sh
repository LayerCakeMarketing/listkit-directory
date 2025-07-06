#!/bin/bash

# Production Deployment Script
# WARNING: This will OVERWRITE production database!

echo "🚀 Starting production deployment..."
echo "⚠️  WARNING: This will OVERWRITE the production database!"
read -p "Are you sure you want to continue? (yes/no) " -n 3 -r
echo
if [[ ! $REPLY =~ ^yes$ ]]
then
    echo "Deployment cancelled."
    exit 1
fi

# Configuration
SERVER="listkit"  # SSH config alias
PRODUCTION_DIR="/var/www/listkit"
DB_USER="listkit_user"
DB_NAME="listkit"
DB_HOST="localhost"
LOCAL_BACKUP="local_database_backup.sql"

echo "📥 Pulling latest code..."
ssh $SERVER "cd $PRODUCTION_DIR && git pull origin main"

echo "📦 Installing dependencies..."
ssh $SERVER "cd $PRODUCTION_DIR && composer install --optimize-autoloader --no-dev && npm install"

echo "🔨 Building production assets..."
ssh $SERVER "cd $PRODUCTION_DIR && npm run build"

echo "💾 Backing up production database..."
ssh $SERVER "pg_dump -U $DB_USER -h $DB_HOST $DB_NAME > $PRODUCTION_DIR/production_backup_$(date +%Y%m%d_%H%M%S).sql"

echo "⚠️  Uploading and importing local database (this will OVERWRITE production data)..."
scp $LOCAL_BACKUP $SERVER:$PRODUCTION_DIR/
ssh $SERVER "cd $PRODUCTION_DIR && psql -U $DB_USER -h $DB_HOST $DB_NAME < $LOCAL_BACKUP"

echo "🔄 Running migrations..."
ssh $SERVER "cd $PRODUCTION_DIR && php artisan migrate --force"

echo "🧹 Clearing and rebuilding caches..."
ssh $SERVER "cd $PRODUCTION_DIR && php artisan optimize:clear && php artisan optimize && php artisan storage:link"

echo "🔐 Setting permissions..."
ssh $SERVER "cd $PRODUCTION_DIR && chmod -R 755 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache public/storage"

echo "🔄 Restarting PHP-FPM..."
ssh $SERVER "sudo service php8.3-fpm restart"

echo "✅ Deployment complete!"
echo "🌐 Your site should now be live with the new changes."
echo "📁 Production database backup saved as: production_backup_*.sql"