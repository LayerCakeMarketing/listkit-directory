#!/bin/bash
# Complete deployment script - Updates EVERYTHING (code + database)

echo "🚀 COMPLETE DEPLOYMENT TO PRODUCTION"
echo "===================================="
echo "This will:"
echo "  - Update all code"
echo "  - Run ALL migrations"
echo "  - Update database structure"
echo "  - Clear all caches"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}📤 Step 1: Uploading frontend build...${NC}"
scp -r frontend/dist root@137.184.113.161:/tmp/frontend-dist-complete || {
    echo -e "${RED}Failed to upload frontend${NC}"
    exit 1
}

echo -e "${GREEN}✓ Frontend uploaded${NC}"
echo ""
echo -e "${YELLOW}🚀 Step 2: Connecting to server for deployment...${NC}"

ssh root@137.184.113.161 << 'ENDSSH'
set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}📍 Working directory: /var/www/listerino${NC}"
cd /var/www/listerino

echo ""
echo -e "${YELLOW}💾 Creating database backup...${NC}"
mkdir -p /backups
BACKUP_FILE="/backups/complete-deploy-$(date +%Y%m%d-%H%M%S).sql"
docker exec listerino_db pg_dump -U listerino listerino > "$BACKUP_FILE"
echo -e "${GREEN}✓ Backup saved to: $BACKUP_FILE${NC}"

echo ""
echo -e "${YELLOW}📥 Pulling latest code from GitHub...${NC}"
git fetch origin main
git reset --hard origin/main
CURRENT_COMMIT=$(git rev-parse --short HEAD)
echo -e "${GREEN}✓ Updated to commit: $CURRENT_COMMIT${NC}"

echo ""
echo -e "${YELLOW}📦 Deploying frontend...${NC}"
rm -rf frontend/dist
mv /tmp/frontend-dist-complete frontend/dist
docker cp frontend/dist/. listerino_app:/app/frontend/dist/
echo -e "${GREEN}✓ Frontend deployed${NC}"

echo ""
echo -e "${YELLOW}🔄 Running ALL database migrations...${NC}"
echo "This includes:"
echo "  - Sections support for lists"
echo "  - PostGIS geospatial fields"
echo "  - Saved collections"
echo "  - Marketing pages"
echo ""
docker exec -w /app listerino_app php artisan migrate --force
echo -e "${GREEN}✓ All migrations completed${NC}"

echo ""
echo -e "${YELLOW}🗺️ Enabling PostGIS extension...${NC}"
docker exec -u postgres listerino_db psql -d listerino -c 'CREATE EXTENSION IF NOT EXISTS postgis;' 2>/dev/null || echo "PostGIS already enabled"
docker exec -u postgres listerino_db psql -d listerino -c 'SELECT PostGIS_version();'
echo -e "${GREEN}✓ PostGIS enabled${NC}"

echo ""
echo -e "${YELLOW}🧹 Clearing ALL caches...${NC}"
docker exec -w /app listerino_app php artisan config:clear
docker exec -w /app listerino_app php artisan cache:clear
docker exec -w /app listerino_app php artisan route:clear
docker exec -w /app listerino_app php artisan view:clear
docker exec -w /app listerino_app php artisan event:clear
echo -e "${GREEN}✓ All caches cleared${NC}"

echo ""
echo -e "${YELLOW}🔧 Optimizing application...${NC}"
docker exec -w /app listerino_app php artisan optimize
echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${YELLOW}🔐 Setting correct permissions...${NC}"
docker exec listerino_app chown -R www-data:www-data /app/storage /app/bootstrap/cache
docker exec listerino_app chmod -R 775 /app/storage /app/bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}🐳 Restarting containers for clean state...${NC}"
cd /var/www/listerino
docker-compose -f docker-compose.production.yml restart
sleep 10
echo -e "${GREEN}✓ Containers restarted${NC}"

echo ""
echo -e "${YELLOW}📊 Verifying deployment...${NC}"
echo "Container status:"
docker ps | grep listerino

echo ""
echo "Application info:"
docker exec -w /app listerino_app php artisan about | grep -E "Environment|URL|Laravel|Cache|Database|Session"

echo ""
echo -e "${YELLOW}🔍 Checking critical features...${NC}"
# Check if PostGIS is working
echo -n "PostGIS: "
docker exec -u postgres listerino_db psql -d listerino -t -c "SELECT COUNT(*) FROM pg_extension WHERE extname='postgis';" | tr -d ' '

# Check migrations
echo -n "Migrations: "
docker exec -w /app listerino_app php artisan migrate:status | grep -c "Ran" || echo "Check manually"

echo ""
echo -e "${GREEN}✅ DEPLOYMENT COMPLETED!${NC}"
echo ""
echo "📝 Post-deployment tasks:"
echo "  1. Clear browser cache (Ctrl+Shift+R)"
echo "  2. Test new features:"
echo "     - List sections: /mylists/{id}/edit"
echo "     - Map view: /places/map"
echo "     - Admin lists: /admin/lists"
echo "  3. Check logs if issues: docker logs listerino_app"
ENDSSH

echo ""
echo "🎉 Complete deployment finished!"
echo "🌐 Visit: https://listerino.com"