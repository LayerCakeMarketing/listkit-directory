#!/bin/bash
# Quick Deployment Script for Listerino.com
# This script handles the immediate deployment needs

set -e

echo "🚀 Starting Listerino Production Deployment..."

# Configuration
PRODUCTION_SERVER="root@137.184.113.161"
PRODUCTION_PATH="/var/www/listerino"
LOCAL_PATH="/Users/ericslarson/directory-app"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Step 1: Build Frontend Locally
print_status "Building frontend assets..."
cd $LOCAL_PATH/frontend
npm install
npm run build
cd $LOCAL_PATH

# Step 2: Commit and Push to GitHub
print_status "Committing changes to Git..."
git add .
git commit -m "Deploy: Production deployment $(date +%Y-%m-%d)" || true
git push origin main

# Step 3: Create database export
print_status "Exporting development database..."
pg_dump -h localhost -U ericslarson -d illum_local \
    --no-owner \
    --no-privileges \
    --clean \
    --if-exists \
    > $LOCAL_PATH/database/production_deploy.sql

# Step 4: Copy files to production
print_status "Copying database export to production..."
scp $LOCAL_PATH/database/production_deploy.sql $PRODUCTION_SERVER:/tmp/

print_status "Copying environment file..."
scp $LOCAL_PATH/.env.production $PRODUCTION_SERVER:$PRODUCTION_PATH/.env

# Step 5: Execute deployment on production
print_status "Executing deployment on production server..."

ssh $PRODUCTION_SERVER << 'ENDSSH'
set -e

echo "📦 On production server..."

cd /var/www/listerino

# Pull latest code
echo "→ Pulling latest code..."
git pull origin main

# Install dependencies
echo "→ Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Build frontend
echo "→ Building frontend..."
cd frontend
npm ci --production
npm run build
cd ..

# Set up database if needed
echo "→ Setting up database..."
docker-compose up -d

# Wait for database to be ready
sleep 5

# Import database
echo "→ Importing database..."
docker exec -i listerino_db psql -U listerino -c "SELECT 1" || {
    echo "Creating database..."
    docker exec listerino_db createdb -U listerino listerino
}

# Import the SQL file
docker exec -i listerino_db psql -U listerino listerino < /tmp/production_deploy.sql

# Run migrations
echo "→ Running migrations..."
php artisan migrate --force

# Clear and optimize caches
echo "→ Optimizing application..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "→ Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart services
echo "→ Restarting services..."
systemctl restart php8.3-fpm || systemctl restart php-fpm
systemctl reload nginx

echo "✅ Production deployment complete!"
ENDSSH

# Step 6: Verify deployment
print_status "Verifying deployment..."
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" https://listerino.com/api/health || echo "000")

if [ "$HEALTH_CHECK" = "200" ]; then
    print_status "Health check passed! Site is live at https://listerino.com"
else
    print_error "Health check failed! HTTP status: $HEALTH_CHECK"
    print_warning "Please check the server logs"
fi

# Cleanup
rm -f $LOCAL_PATH/database/production_deploy.sql

echo ""
echo "🎉 Deployment process complete!"
echo ""
echo "Next steps:"
echo "1. Visit https://listerino.com to verify the site"
echo "2. Check logs: ssh $PRODUCTION_SERVER 'tail -f $PRODUCTION_PATH/storage/logs/laravel.log'"
echo "3. Monitor for any errors in the first 30 minutes"