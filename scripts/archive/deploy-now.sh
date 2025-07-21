#!/bin/bash
# Immediate deployment script for Listerino production

set -e

echo "🚀 Deploying Listerino to Production..."
echo "======================================"

# Configuration
SERVER="root@137.184.113.161"
GITHUB_REPO="https://github.com/lcreative777/listerino.git"

# Step 1: Copy files to server
echo "📤 Copying database and environment files..."
scp database/production_deploy.sql $SERVER:/tmp/
scp .env.production $SERVER:/tmp/

# Step 2: SSH and execute deployment
ssh $SERVER << 'DEPLOY_SCRIPT'
set -e

echo "🔧 Setting up production environment..."

# Create directories
mkdir -p /var/www/listerino
mkdir -p /backups/listerino
cd /var/www/listerino

# Clone or update repository
if [ ! -d ".git" ]; then
    echo "📥 Cloning repository..."
    git clone https://github.com/lcreative777/listerino.git .
else
    echo "📥 Updating repository..."
    git pull origin main
fi

# Install PHP and dependencies if not present
if ! command -v php &> /dev/null; then
    echo "📦 Installing PHP..."
    apt update
    apt install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt update
    apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-pgsql \
        php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd \
        php8.3-redis php8.3-bcmath php8.3-intl nginx
fi

# Install Composer if not present
if ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install Node.js if not present
if ! command -v node &> /dev/null; then
    echo "📦 Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt install -y nodejs
fi

# Copy environment file
cp /tmp/.env.production /var/www/listerino/.env

# Create docker-compose.yml for database and Redis
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  db:
    image: postgis/postgis:15-3.4
    container_name: listerino_db
    restart: unless-stopped
    environment:
      POSTGRES_DB: listerino
      POSTGRES_USER: listerino
      POSTGRES_PASSWORD: SecurePassword2025!
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:alpine
    container_name: listerino_redis
    restart: unless-stopped
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    ports:
      - "6379:6379"

volumes:
  postgres_data:
  redis_data:
EOF

# Start Docker services
echo "🐳 Starting Docker services..."
docker-compose up -d

# Wait for database
echo "⏳ Waiting for database..."
sleep 10

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Import database
echo "💾 Importing database..."
docker exec -i listerino_db psql -U listerino postgres -c "DROP DATABASE IF EXISTS listerino;"
docker exec -i listerino_db psql -U listerino postgres -c "CREATE DATABASE listerino;"
docker exec -i listerino_db psql -U listerino listerino < /tmp/production_deploy.sql

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force

# Build frontend
echo "🎨 Building frontend..."
cd frontend
npm ci --production
npm run build
cd ..

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/listerino
chmod -R 775 storage bootstrap/cache

# Configure Nginx
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/listerino.com << 'NGINX_CONFIG'
server {
    listen 80;
    server_name listerino.com www.listerino.com;
    root /var/www/listerino/public;

    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONFIG

# Enable site
ln -sf /etc/nginx/sites-available/listerino.com /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl restart nginx
systemctl restart php8.3-fpm

# Clear Laravel caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment complete!"
DEPLOY_SCRIPT

echo ""
echo "🎉 Deployment finished!"
echo ""
echo "Next steps:"
echo "1. Set up SSL: ssh $SERVER 'certbot --nginx -d listerino.com -d www.listerino.com'"
echo "2. Visit: http://listerino.com"
echo "3. Monitor logs: ssh $SERVER 'tail -f /var/www/listerino/storage/logs/laravel.log'"