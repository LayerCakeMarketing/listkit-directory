#!/bin/bash

# Deployment script for Listerino to Digital Ocean
# This will completely replace the production site with local development

set -e  # Exit on error

echo "🚀 Starting deployment to Digital Ocean..."

# Variables
DO_IP="137.184.113.161"
DO_USER="root"
GITHUB_REPO="https://github.com/lcreative777/listerino.git"
REMOTE_APP_DIR="/var/www/listerino"
REMOTE_BACKUP_DIR="/var/backups/listerino"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}⚠️  WARNING: This will completely replace the production site!${NC}"
echo "Target server: $DO_IP"
echo "Repository: $GITHUB_REPO"
read -p "Are you sure you want to continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

# Step 1: Commit and push local changes to GitHub
echo -e "\n${GREEN}📝 Step 1: Committing and pushing local changes...${NC}"
git add -A
git commit -m "Deployment update - $TIMESTAMP" || echo "No changes to commit"
git push origin main || { echo -e "${RED}Failed to push to GitHub${NC}"; exit 1; }

# Step 2: Create deployment script on server
echo -e "\n${GREEN}🔧 Step 2: Preparing server deployment script...${NC}"

ssh $DO_USER@$DO_IP << 'ENDSSH'
set -e

echo "Creating deployment script on server..."

cat > /tmp/deploy.sh << 'DEPLOY_SCRIPT'
#!/bin/bash
set -e

# Variables
APP_DIR="/var/www/listerino"
BACKUP_DIR="/var/backups/listerino"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
GITHUB_REPO="https://github.com/lcreative777/listerino.git"

echo "🔄 Starting production deployment..."

# Step 1: Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Step 2: Backup existing site (if it exists)
if [ -d "$APP_DIR" ]; then
    echo "📦 Backing up existing site..."
    cd $APP_DIR
    
    # Stop containers if they're running
    docker-compose down || true
    
    # Create backup
    tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='storage/logs/*' \
        --exclude='storage/app/public/*' \
        --exclude='.git' \
        .
    
    # Backup database
    if docker ps -a | grep -q postgres; then
        echo "💾 Backing up database..."
        docker exec postgres pg_dump -U listerino listerino > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql" || true
    fi
    
    # Remove old app directory
    cd /
    rm -rf $APP_DIR
fi

# Step 3: Clone fresh repository
echo "📥 Cloning repository..."
mkdir -p $APP_DIR
cd $APP_DIR
git clone $GITHUB_REPO .

# Step 4: Copy environment files
echo "🔧 Setting up environment files..."

# Backend .env
cat > .env << 'ENV'
APP_NAME=Listerino
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=http://137.184.113.161

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=your_secure_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

CLOUDFLARE_ACCOUNT_ID=your_cloudflare_account_id
CLOUDFLARE_API_TOKEN=your_cloudflare_api_token
CLOUDFLARE_IMAGES_ACCOUNT_HASH=your_account_hash
CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net/your_account_hash

SPA_MODE=false
ENV

# Frontend .env
cat > frontend/.env << 'FRONTEND_ENV'
VITE_APP_NAME=Listerino
VITE_API_URL=http://137.184.113.161:8000
FRONTEND_ENV

# Docker environment
cat > .env.docker << 'DOCKER_ENV'
# Docker environment settings
APP_PORT=8000
DB_PORT=5432
REDIS_PORT=6379
DOCKER_ENV

# Step 5: Create docker-compose.yml for production
echo "🐳 Creating production Docker configuration..."
cat > docker-compose.yml << 'DOCKER_COMPOSE'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: listerino_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./storage:/var/www/storage
    networks:
      - listerino
    depends_on:
      - postgres
      - redis
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=postgres
      - REDIS_HOST=redis

  nginx:
    image: nginx:alpine
    container_name: listerino_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    networks:
      - listerino
    depends_on:
      - app

  postgres:
    image: postgres:15
    container_name: listerino_postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: listerino
      POSTGRES_USER: listerino
      POSTGRES_PASSWORD: your_secure_password_here
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - listerino
    ports:
      - "5432:5432"

  redis:
    image: redis:alpine
    container_name: listerino_redis
    restart: unless-stopped
    networks:
      - listerino
    ports:
      - "6379:6379"

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: listerino_frontend
    restart: unless-stopped
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules
    networks:
      - listerino
    depends_on:
      - app
    environment:
      - VITE_API_URL=http://137.184.113.161:8000

networks:
  listerino:
    driver: bridge

volumes:
  postgres_data:
DOCKER_COMPOSE

# Step 6: Create Nginx configuration
mkdir -p docker/nginx/conf.d
cat > docker/nginx/conf.d/listerino.conf << 'NGINX_CONF'
server {
    listen 80;
    server_name 137.184.113.161;
    root /var/www/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONF

# Step 7: Create production Dockerfile
cat > Dockerfile << 'DOCKERFILE'
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Install and build frontend
WORKDIR /var/www/frontend
RUN npm ci && npm run build

WORKDIR /var/www

# Create storage directories and set permissions
RUN mkdir -p storage/logs storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate application key
RUN php artisan key:generate

EXPOSE 9000
CMD ["php-fpm"]
DOCKERFILE

# Create frontend Dockerfile
cat > frontend/Dockerfile << 'FRONTEND_DOCKERFILE'
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
FRONTEND_DOCKERFILE

# Step 8: Build and start containers
echo "🏗️ Building and starting Docker containers..."
docker-compose build
docker-compose up -d

# Step 9: Run migrations and seeders
echo "🗄️ Setting up database..."
sleep 10  # Wait for services to be ready

docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache

# Step 10: Set final permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

echo "✅ Deployment complete!"
echo "🌐 Site is now available at: http://137.184.113.161"
echo "📱 Frontend (dev): http://137.184.113.161:5173"
echo "🔧 API: http://137.184.113.161:8000"

# Show running containers
echo -e "\n📊 Running containers:"
docker-compose ps

# Keep only last 5 backups
echo -e "\n🧹 Cleaning old backups..."
ls -t $BACKUP_DIR/backup_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f || true
ls -t $BACKUP_DIR/db_backup_*.sql 2>/dev/null | tail -n +6 | xargs rm -f || true

DEPLOY_SCRIPT

chmod +x /tmp/deploy.sh
ENDSSH

# Step 3: Execute deployment on server
echo -e "\n${GREEN}🚀 Step 3: Executing deployment on server...${NC}"
ssh $DO_USER@$DO_IP 'bash /tmp/deploy.sh'

# Step 4: Update .env with real values
echo -e "\n${GREEN}🔐 Step 4: Updating environment variables...${NC}"
echo -e "${YELLOW}Please update the following in the server's .env file:${NC}"
echo "1. APP_KEY (run: php artisan key:generate)"
echo "2. Database password"
echo "3. Cloudflare credentials"
echo "4. Email settings"
echo ""
echo "You can edit them by running:"
echo "ssh $DO_USER@$DO_IP 'nano /var/www/listerino/.env'"

echo -e "\n${GREEN}✅ Deployment script completed!${NC}"
echo "🌐 Your site should be available at: http://$DO_IP"
echo ""
echo "📝 Post-deployment checklist:"
echo "1. Update .env file with production values"
echo "2. Set up SSL certificate (optional)"
echo "3. Configure firewall rules"
echo "4. Set up monitoring"
echo "5. Configure automated backups"