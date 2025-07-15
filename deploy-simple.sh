#!/bin/bash

# Simple deployment script for Listerino to Digital Ocean
# Uses existing docker-compose configuration

set -e

echo "🚀 Starting simple deployment to Digital Ocean..."

# Variables
DO_IP="137.184.113.161"
DO_USER="root"
LOCAL_DIR="/Users/ericslarson/directory-app"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}This will deploy your local development to production${NC}"
echo "Target: $DO_USER@$DO_IP"
read -p "Continue? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Step 1: Copy project files
echo -e "\n${GREEN}📦 Copying project files to server...${NC}"
rsync -avz --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude '.git' \
    --exclude 'storage/logs/*' \
    --exclude 'storage/app/public/*' \
    --exclude '.env' \
    --exclude 'hot' \
    --exclude 'frontend/node_modules' \
    --exclude 'frontend/dist' \
    --exclude '.DS_Store' \
    $LOCAL_DIR/ $DO_USER@$DO_IP:/var/www/listerino/

# Step 2: Copy environment files
echo -e "\n${GREEN}🔧 Setting up environment...${NC}"
scp $LOCAL_DIR/.env.docker $DO_USER@$DO_IP:/var/www/listerino/.env.docker

# Step 3: Create production environment file
ssh $DO_USER@$DO_IP << 'EOF'
cd /var/www/listerino

# Copy local env as template if production env doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || true
fi

# Update key production settings
sed -i 's/APP_ENV=local/APP_ENV=production/g' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
sed -i "s|APP_URL=.*|APP_URL=http://137.184.113.161|g" .env

# Frontend env
cat > frontend/.env << 'FRONTEND_ENV'
VITE_APP_NAME=Listerino
VITE_API_URL=http://137.184.113.161:8001
FRONTEND_ENV

echo "✅ Environment files created"
EOF

# Step 4: Deploy with Docker
echo -e "\n${GREEN}🐳 Starting Docker deployment...${NC}"
ssh $DO_USER@$DO_IP << 'EOF'
cd /var/www/listerino

# Stop existing containers
docker-compose down || true

# Build and start containers
docker-compose up -d --build

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 15

# Run Laravel setup
echo "🔧 Running Laravel setup..."
docker-compose exec -T app composer install --optimize-autoloader
docker-compose exec -T app php artisan key:generate
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan db:seed --force
docker-compose exec -T app php artisan storage:link
docker-compose exec -T app php artisan optimize:clear
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache

# Build frontend
echo "📦 Building frontend..."
docker-compose exec -T frontend npm install
docker-compose exec -T frontend npm run build || echo "Frontend build optional"

# Set permissions
docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache

echo "✅ Docker deployment complete!"
docker-compose ps
EOF

echo -e "\n${GREEN}✅ Deployment complete!${NC}"
echo "🌐 Your site is available at:"
echo "   Main: http://$DO_IP:8001"
echo "   Frontend Dev: http://$DO_IP:5174"
echo ""
echo "📝 Next steps:"
echo "1. SSH into server: ssh $DO_USER@$DO_IP"
echo "2. Update .env with production values"
echo "3. Set up domain and SSL"
echo "4. Configure firewall: ufw allow 8001 && ufw allow 5174"