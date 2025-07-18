#!/bin/bash

# Comprehensive fix for production issues
set -e

echo "=== Fixing Production Environment ==="

# Step 1: Deploy backend controllers
echo "1. Deploying backend controllers..."
./copy-files-to-server.sh app/Http/Controllers/Api/Admin/LoginPageController.php
./copy-files-to-server.sh app/Models/LoginPageSettings.php

# Step 2: Build frontend with production env
echo "2. Building frontend for production..."
cd frontend
if [ ! -f .env.production ]; then
    echo "Creating .env.production..."
    cat > .env.production << EOF
VITE_API_URL=https://listerino.com
VITE_APP_NAME=Listerino
VITE_CLOUDFLARE_ACCOUNT_HASH=nCX0WluV4kb4MYRWgWWi4A
EOF
fi

npm run build
cd ..

# Step 3: Deploy frontend build
echo "3. Deploying frontend build..."
scp -r frontend/dist/* root@listerino.com:/tmp/frontend-dist/

# Step 4: Fix production environment on server
echo "4. Fixing production environment on server..."
ssh root@listerino.com << 'ENDSSH'
set -e

cd /var/www/listerino

# Backup current .env
echo "Backing up current .env..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S) || true

# Create correct .env for Docker environment
echo "Creating correct .env file..."
cat > .env << 'EOF'
APP_NAME="Listerino"
APP_ENV=production
APP_KEY=base64:1YA8nhjDSrRM7PqEjFitF/K704J3ZLUn5FsbhcPnODk=
APP_DEBUG=false
APP_URL=https://listerino.com
SPA_MODE=true

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.listerino.com
SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=true

CORS_ALLOWED_ORIGINS=https://listerino.com,https://www.listerino.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="${APP_NAME}"

# Cloudflare Images Configuration
CLOUDFLARE_ACCOUNT_ID=edff4095a6c22cfeaf4e1d474be5fe1e
CLOUDFLARE_IMAGES_TOKEN=e31e4a0da90ec617f0c2b16e47e5a30b68ce3
CLOUDFLARE_EMAIL=eric@layercakemarketing.com
CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A

# File Upload Settings
MAX_FILE_UPLOAD_SIZE=10
ASYNC_UPLOAD_THRESHOLD=3

# ImageKit.io 
IMAGEKIT_PUBLIC_KEY=public_s3zNiaelt2ivZUmfqY9iVz0gq+I=
IMAGEKIT_PRIVATE_KEY=private_2PBj7AQ/EJ6sFpciq7zYE9WkaB8=
IMAGEKIT_URL_ENDPOINT=https://ik.imagekit.io/listerinolistkit
IMAGEKIT_ID=listerinolistkit
EOF

# Copy .env to app container
echo "Copying .env to app container..."
docker cp .env laravel-app:/var/www/.env

# Copy frontend files to nginx container
echo "Copying frontend files..."
docker cp /tmp/frontend-dist/. laravel-nginx:/var/www/public/

# Clear all caches in the app container
echo "Clearing caches..."
docker exec laravel-app bash -c "cd /var/www && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize && \
    rm -rf bootstrap/cache/*.php"

# Ensure storage permissions
echo "Setting permissions..."
docker exec laravel-app bash -c "cd /var/www && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache"

# Restart containers
echo "Restarting containers..."
docker restart laravel-app laravel-nginx

# Wait for containers to start
sleep 5

# Check container status
echo "Container status:"
docker ps | grep laravel

# Test endpoints
echo "Testing endpoints..."
echo "Login page:"
curl -s -o /dev/null -w "%{http_code}" https://listerino.com/login
echo ""
echo "API endpoint:"
curl -s -o /dev/null -w "%{http_code}" https://listerino.com/api/admin/marketing-pages/special/login
echo ""

# Check logs
echo "Recent app logs:"
docker logs --tail 10 laravel-app

# Clean up
rm -rf /tmp/frontend-dist

echo "Production fix complete!"
ENDSSH

echo "=== All done! ==="