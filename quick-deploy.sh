#!/bin/bash
# Quick manual deployment if GitHub Actions fails

echo "🚀 Quick deployment to listerino.com"

# First, copy frontend from local
echo "📤 Uploading frontend build..."
scp -r frontend/dist root@137.184.113.161:/tmp/frontend-dist

# Then SSH and deploy
ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

# Pull latest code
echo "📥 Pulling latest code..."
git fetch origin main
git reset --hard origin/main

# Copy frontend to container
echo "📦 Deploying frontend..."
rm -rf frontend/dist
mv /tmp/frontend-dist frontend/dist
docker cp frontend/dist/. listerino_app:/app/frontend/dist/

# Run migrations
echo "🔄 Running migrations..."
docker exec -w /app listerino_app php artisan migrate --force

# Enable PostGIS
echo "🗺️ Enabling PostGIS..."
docker exec -u postgres listerino_db psql -d listerino -c 'CREATE EXTENSION IF NOT EXISTS postgis;' || true

# Clear caches
echo "🧹 Clearing caches..."
docker exec -w /app listerino_app php artisan optimize:clear
docker exec -w /app listerino_app php artisan optimize

# Check status
echo "✅ Deployment complete!"
docker ps | grep listerino
ENDSSH

echo "🎉 Done! Check https://listerino.com"