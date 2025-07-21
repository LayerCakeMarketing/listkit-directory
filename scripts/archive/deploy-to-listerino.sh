#!/bin/bash

# Deployment Script for listerino.com
# This script deploys the application to the new DigitalOcean server

set -e

echo "🚀 Starting deployment to listerino.com..."

# Configuration
SERVER_IP="137.184.113.161"
SERVER_USER="root"  # Change this if you're using a different user
PRODUCTION_DIR="/var/www/listerino"
DB_NAME="listerino"
DB_USER="listerino_user"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we can connect to the server
echo "🔗 Testing connection to server..."
if ! ssh -o ConnectTimeout=5 ${SERVER_USER}@${SERVER_IP} "echo 'Connection successful'"; then
    echo -e "${RED}❌ Cannot connect to server. Please check your SSH configuration.${NC}"
    echo "Add this to your ~/.ssh/config:"
    echo ""
    echo "Host listerino"
    echo "    HostName ${SERVER_IP}"
    echo "    User ${SERVER_USER}"
    echo "    IdentityFile ~/.ssh/your_key"
    exit 1
fi

# Function to run commands on server
run_on_server() {
    ssh ${SERVER_USER}@${SERVER_IP} "$1"
}

# Pull latest code
echo "📥 Pulling latest code..."
run_on_server "cd ${PRODUCTION_DIR} && git pull origin main"

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
run_on_server "cd ${PRODUCTION_DIR} && composer install --optimize-autoloader --no-dev"

# Install NPM dependencies
echo "📦 Installing NPM dependencies..."
run_on_server "cd ${PRODUCTION_DIR} && npm ci"

# Build production assets
echo "🔨 Building production assets..."
run_on_server "cd ${PRODUCTION_DIR} && npm run build"

# Run database migrations
echo "🗄️ Running database migrations..."
run_on_server "cd ${PRODUCTION_DIR} && php artisan migrate --force"

# Clear and optimize caches
echo "🧹 Clearing and optimizing caches..."
run_on_server "cd ${PRODUCTION_DIR} && \
    php artisan optimize:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan optimize"

# Create storage link
echo "🔗 Ensuring storage link exists..."
run_on_server "cd ${PRODUCTION_DIR} && php artisan storage:link --force || true"

# Set permissions
echo "🔐 Setting correct permissions..."
run_on_server "cd ${PRODUCTION_DIR} && \
    chmod -R 755 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache public/storage"

# Restart services
echo "🔄 Restarting services..."
run_on_server "sudo systemctl restart php8.3-fpm && sudo nginx -s reload"

echo -e "${GREEN}✅ Deployment to listerino.com complete!${NC}"
echo "🌐 Your site should now be live at https://listerino.com"

# Optional: Run a health check
echo ""
echo "🏥 Running health check..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://listerino.com)
if [ $HTTP_STATUS -eq 200 ] || [ $HTTP_STATUS -eq 301 ] || [ $HTTP_STATUS -eq 302 ]; then
    echo -e "${GREEN}✅ Site is responding correctly (HTTP ${HTTP_STATUS})${NC}"
else
    echo -e "${YELLOW}⚠️  Site returned HTTP ${HTTP_STATUS} - you may want to check the logs${NC}"
fi