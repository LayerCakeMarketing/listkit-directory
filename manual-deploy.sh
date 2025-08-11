#!/bin/bash
# Manual deployment script for listerino.com
# Run this directly on the production server

echo "🚀 Starting manual deployment for listerino.com..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Exit on error
set -e

# Function to handle errors
error_exit() {
    echo -e "${RED}❌ Error: $1${NC}" 1>&2
    exit 1
}

# Check if we're in the right directory
if [ ! -d "/var/www/listerino" ]; then
    error_exit "Not in /var/www/listerino directory"
fi

cd /var/www/listerino

echo -e "${YELLOW}📦 Creating backup...${NC}"
mkdir -p /backups
docker exec listerino_db pg_dump -U listerino listerino > /backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql || echo "Warning: Backup failed"

echo -e "${YELLOW}📥 Pulling latest code from GitHub...${NC}"
git fetch origin main || error_exit "Failed to fetch from GitHub"
git reset --hard origin/main || error_exit "Failed to reset to origin/main"

echo -e "${YELLOW}🔍 Checking for environment file...${NC}"
if [ -f .env.production ]; then
    cp .env.production .env
    echo "✓ Production environment loaded"
else
    echo "⚠️  No .env.production found, using existing .env"
fi

echo -e "${YELLOW}🐳 Checking Docker containers...${NC}"
docker ps | grep listerino || error_exit "Docker containers not running"

# Use docker-compose.production.yml explicitly
echo -e "${YELLOW}🐳 Updating Docker containers...${NC}"
if [ -f docker-compose.production.yml ]; then
    docker-compose -f docker-compose.production.yml pull
    docker-compose -f docker-compose.production.yml up -d
else
    docker-compose pull
    docker-compose up -d
fi

# Wait for services
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 10

# Get the correct container name
APP_CONTAINER="listerino_app"
echo "Using app container: $APP_CONTAINER"

# Check if container exists
docker ps | grep $APP_CONTAINER || error_exit "App container not found"

echo -e "${YELLOW}🔄 Running database migrations...${NC}"
docker exec -w /app $APP_CONTAINER php artisan migrate --force || error_exit "Migrations failed"

echo -e "${YELLOW}🗺️ Enabling PostGIS extension...${NC}"
docker exec -u postgres listerino_db psql -d listerino -c 'CREATE EXTENSION IF NOT EXISTS postgis;' || echo "PostGIS may already be enabled"

echo -e "${YELLOW}🧹 Clearing caches...${NC}"
docker exec -w /app $APP_CONTAINER php artisan config:clear
docker exec -w /app $APP_CONTAINER php artisan cache:clear
docker exec -w /app $APP_CONTAINER php artisan route:clear
docker exec -w /app $APP_CONTAINER php artisan view:clear

echo -e "${YELLOW}🔧 Optimizing application...${NC}"
docker exec -w /app $APP_CONTAINER php artisan optimize

echo -e "${YELLOW}🔐 Setting permissions...${NC}"
docker exec $APP_CONTAINER chown -R www-data:www-data /app/storage /app/bootstrap/cache

echo -e "${YELLOW}📊 Checking application status...${NC}"
docker exec -w /app $APP_CONTAINER php artisan about

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"

# Health check
echo -e "${YELLOW}🏥 Running health check...${NC}"
sleep 5
curl -f https://listerino.com/api/health && echo -e "${GREEN}✓ Health check passed${NC}" || echo -e "${RED}⚠️ Health check failed${NC}"

echo -e "${YELLOW}📝 Recent logs:${NC}"
docker logs --tail 20 $APP_CONTAINER