#!/bin/bash
# Production deployment script for existing Listerino server

set -e

echo "🚀 Deploying to Listerino Production..."
echo "======================================"

# Configuration
SERVER="root@137.184.113.161"
LOCAL_PATH="/Users/ericslarson/directory-app"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}✓${NC} Building frontend locally..."
cd $LOCAL_PATH/frontend
npm run build
cd $LOCAL_PATH

echo -e "${GREEN}✓${NC} Creating database export..."
pg_dump -h localhost -U ericslarson -d illum_local \
    --no-owner --no-privileges --clean --if-exists \
    > database/production_deploy.sql

echo -e "${GREEN}✓${NC} Copying files to server..."
scp database/production_deploy.sql $SERVER:/tmp/
scp .env.production $SERVER:/tmp/
scp -r frontend/dist $SERVER:/tmp/frontend-dist

echo -e "${GREEN}✓${NC} Executing deployment on server..."
ssh $SERVER << 'REMOTE_SCRIPT'
set -e

cd /var/www/listerino

# Since the repo already exists, let's update it properly
echo "→ Updating code from existing repository..."
# First, stash any local changes
git stash || true
# Then pull from the correct remote
git pull listerino main || {
    echo "→ Git pull failed, trying to fix..."
    git remote remove origin || true
    git remote add origin git@github.com:lcreative777/listerino.git
    git fetch origin
    git reset --hard origin/main
}

# Copy environment file
echo "→ Updating environment configuration..."
cp /tmp/.env.production .env

# Update dependencies
echo "→ Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Copy pre-built frontend
echo "→ Copying frontend build..."
rm -rf frontend/dist
cp -r /tmp/frontend-dist frontend/dist

# Import database
echo "→ Importing database..."
# Check if database exists
docker exec listerino_db psql -U listerino -lqt | cut -d \| -f 1 | grep -qw listerino || {
    docker exec listerino_db createdb -U listerino listerino
}

# Import the database
docker exec -i listerino_db psql -U listerino listerino < /tmp/production_deploy.sql

# Run migrations
echo "→ Running migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "→ Optimizing application..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "→ Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Copy frontend files to Docker container if needed
if docker ps | grep -q listerino_app; then
    echo "→ Updating Docker container..."
    docker cp frontend/dist/. listerino_app:/var/www/html/frontend/dist/
fi

# Restart services
echo "→ Restarting services..."
docker-compose restart || true
systemctl restart nginx || true

echo "✅ Deployment complete!"

# Show current status
echo ""
echo "Current Docker containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

REMOTE_SCRIPT

# Cleanup
rm -f database/production_deploy.sql

echo ""
echo -e "${GREEN}🎉 Deployment process complete!${NC}"
echo ""
echo "Important next steps:"
echo "1. Update DNS if needed (listerino.com → 137.184.113.161)"
echo "2. Set up SSL: ssh $SERVER 'certbot --nginx -d listerino.com -d www.listerino.com'"
echo "3. Test the site: https://listerino.com"
echo "4. Monitor logs: ssh $SERVER 'docker logs -f listerino_app'"
echo ""
echo -e "${YELLOW}Note:${NC} The app is currently running on HTTP. Set up SSL for production use."