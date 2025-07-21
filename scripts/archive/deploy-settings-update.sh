#!/bin/bash

# Deploy settings update to production
echo "Deploying settings feature to listerino.com..."

# SSH into the server and execute commands
ssh root@137.184.113.161 << 'EOF'
cd /var/www/listerino

echo "Pulling latest changes from GitHub..."
git pull origin main

echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running database migration for settings table..."
php artisan migrate --force

echo "Clearing caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Building frontend assets..."
npm install
npm run build

echo "Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx

echo "Deployment complete!"
EOF

echo "Settings feature deployed to listerino.com"