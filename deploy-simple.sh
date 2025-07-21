#!/bin/bash
# Direct file transfer deployment for Listerino production
# This bypasses git issues by directly transferring files

set -e

echo "🚀 Direct Deployment to Listerino Production"
echo "=========================================="

# Configuration
SERVER="root@137.184.113.161"
LOCAL_PATH="/Users/ericslarson/directory-app"
REMOTE_PATH="/var/www/listerino"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}✓${NC} Preparing deployment package..."

# Create deployment directory
cd $LOCAL_PATH
mkdir -p deployment_temp

# Ensure frontend is built
if [ ! -d "frontend/dist" ]; then
    echo -e "${YELLOW}Building frontend first...${NC}"
    cd frontend && npm run build && cd ..
fi

# Create deployment archive (excluding unnecessary files)
echo -e "${GREEN}✓${NC} Creating deployment archive..."
tar -czf deployment_temp/app_${TIMESTAMP}.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/app/*' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='deployment_temp' \
    --exclude='tests' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='*.log' \
    --exclude='database/*.sql' \
    --exclude='*.tar.gz' \
    --exclude='deploy-*.sh' \
    --exclude='check-*.sh' \
    --exclude='fix-*.sh' \
    --exclude='*.md' \
    .

# Copy files to server
echo -e "${GREEN}✓${NC} Transferring files to server..."
scp deployment_temp/app_${TIMESTAMP}.tar.gz $SERVER:/tmp/
scp database/production_deploy.sql $SERVER:/tmp/
scp .env.production $SERVER:/tmp/

# Deploy on server
echo -e "${GREEN}✓${NC} Executing deployment on server..."
ssh $SERVER << REMOTE_COMMANDS
set -e

echo "→ Creating backup of current deployment..."
mkdir -p /backups
if [ -d "$REMOTE_PATH" ]; then
    tar -czf /backups/listerino_backup_${TIMESTAMP}.tar.gz -C $REMOTE_PATH .
fi

echo "→ Extracting new deployment..."
mkdir -p $REMOTE_PATH
cd $REMOTE_PATH

# Extract new files
tar -xzf /tmp/app_${TIMESTAMP}.tar.gz

# Copy environment file
cp /tmp/.env.production .env

# Install PHP dependencies
echo "→ Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Create necessary directories
mkdir -p storage/app/public
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
echo "→ Setting permissions..."
chown -R www-data:www-data .
chmod -R 775 storage bootstrap/cache

# Import database
echo "→ Importing database..."
# Check if PostgreSQL is running in Docker
if docker ps | grep -q listerino_db; then
    echo "  Using Docker PostgreSQL..."
    docker exec -i listerino_db psql -U listerino listerino < /tmp/production_deploy.sql || {
        echo "  Database might not exist, creating it..."
        docker exec listerino_db psql -U listerino postgres -c "CREATE DATABASE listerino;" || true
        docker exec -i listerino_db psql -U listerino listerino < /tmp/production_deploy.sql
    }
else
    echo "  Using local PostgreSQL..."
    sudo -u postgres psql listerino < /tmp/production_deploy.sql || {
        echo "  Database might not exist, creating it..."
        sudo -u postgres createdb listerino || true
        sudo -u postgres psql listerino < /tmp/production_deploy.sql
    }
fi

# Run migrations
echo "→ Running migrations..."
php artisan migrate --force

# Clear and optimize
echo "→ Optimizing application..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

# Ensure frontend dist has correct permissions
chown -R www-data:www-data frontend/dist

# Configure Nginx if needed
if [ ! -f /etc/nginx/sites-available/listerino.com ]; then
    echo "→ Configuring Nginx..."
    cat > /etc/nginx/sites-available/listerino.com << 'NGINX_CONF'
server {
    listen 80;
    server_name listerino.com www.listerino.com 137.184.113.161;
    root $REMOTE_PATH/public;

    index index.php;
    charset utf-8;

    # SPA support for frontend routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX_CONF

    ln -sf /etc/nginx/sites-available/listerino.com /etc/nginx/sites-enabled/
    nginx -t
fi

# Restart services
echo "→ Restarting services..."
systemctl restart php8.3-fpm || systemctl restart php-fpm || true
systemctl reload nginx || true

# Cleanup
rm -f /tmp/app_${TIMESTAMP}.tar.gz
rm -f /tmp/production_deploy.sql
rm -f /tmp/.env.production

echo "✅ Deployment complete!"

# Show status
echo ""
echo "Service Status:"
systemctl status nginx --no-pager | head -5 || true
echo ""
if docker ps | grep -q listerino; then
    docker ps --format "table {{.Names}}\t{{.Status}}" | grep listerino || true
fi

REMOTE_COMMANDS

# Cleanup local files
rm -rf deployment_temp

echo ""
echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo ""
echo "Important next steps:"
echo "1. Test the site: https://listerino.com"
echo "2. Set up SSL if needed: ssh $SERVER 'certbot --nginx -d listerino.com -d www.listerino.com'"
echo "3. Monitor logs: ssh $SERVER 'tail -f $REMOTE_PATH/storage/logs/laravel.log'"
echo ""

# Quick health check
echo -e "${YELLOW}Running health check...${NC}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://137.184.113.161 || echo "Failed")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo -e "${GREEN}✓ Site is responding (HTTP $HTTP_STATUS)${NC}"
else
    echo -e "${RED}✗ Site health check failed (HTTP $HTTP_STATUS)${NC}"
    echo "  Check server logs for details"
fi