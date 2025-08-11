#!/bin/bash
# Direct deployment script - bypasses GitHub Actions entirely

echo "🚀 Direct deployment to listerino.com (bypassing GitHub Actions)"
echo "=================================================="

# Check if frontend dist exists
if [ ! -d "frontend/dist" ]; then
    echo "❌ Error: frontend/dist not found. Run 'cd frontend && npm run build' first"
    exit 1
fi

echo "✅ Frontend build found"
echo ""
echo "📤 Step 1: Uploading frontend to server..."
scp -r frontend/dist root@137.184.113.161:/tmp/frontend-dist-new || {
    echo "❌ Failed to upload frontend. Check SSH access."
    exit 1
}

echo ""
echo "📥 Step 2: Deploying on server..."
ssh root@137.184.113.161 << 'ENDSSH' || {
    echo "❌ SSH connection failed"
    exit 1
}
set -e

echo "📍 Working in /var/www/listerino..."
cd /var/www/listerino

echo ""
echo "📥 Pulling latest code from GitHub..."
git fetch origin main
git reset --hard origin/main
echo "Current commit: $(git rev-parse --short HEAD)"

echo ""
echo "📦 Deploying frontend files..."
rm -rf frontend/dist
mv /tmp/frontend-dist-new frontend/dist
docker cp frontend/dist/. listerino_app:/app/frontend/dist/

echo ""
echo "🔄 Running database migrations..."
docker exec -w /app listerino_app php artisan migrate --force

echo ""
echo "🗺️ Ensuring PostGIS is enabled..."
docker exec -u postgres listerino_db psql -d listerino -c 'CREATE EXTENSION IF NOT EXISTS postgis;' 2>/dev/null || echo "PostGIS already enabled"

echo ""
echo "🧹 Clearing all caches..."
docker exec -w /app listerino_app php artisan config:clear
docker exec -w /app listerino_app php artisan cache:clear
docker exec -w /app listerino_app php artisan route:clear
docker exec -w /app listerino_app php artisan view:clear

echo ""
echo "🔧 Optimizing application..."
docker exec -w /app listerino_app php artisan optimize

echo ""
echo "🔐 Setting permissions..."
docker exec listerino_app chown -R www-data:www-data /app/storage /app/bootstrap/cache

echo ""
echo "📊 Container status:"
docker ps | grep listerino

echo ""
echo "✅ Deployment completed!"
ENDSSH

echo ""
echo "🎉 Deployment finished! Check https://listerino.com"
echo ""
echo "📝 Post-deployment checklist:"
echo "  - Clear browser cache (Ctrl+Shift+R)"
echo "  - Test list sections at /mylists/{id}/edit"
echo "  - Test map view at /places/map"
echo "  - Check admin lists at /admin/lists"