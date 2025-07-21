# Production Deployment Plan for Listerino.com

## Overview
This plan outlines the safest approach to deploy your development version to production on DigitalOcean. The production server has Docker installed, which we'll use for the database and Redis, while the Laravel application will run with PHP-FPM/Nginx.

## Pre-Deployment Checklist

### 1. Backup Current Production (if exists)
```bash
# SSH into production
ssh root@137.184.113.161

# Backup current database (if exists)
docker exec listerino_db pg_dump -U listerino listerino > /backups/production_backup_$(date +%Y%m%d_%H%M%S).sql

# Backup current code (if exists)
tar -czf /backups/code_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/listerino --exclude=node_modules --exclude=vendor
```

### 2. Prepare Production Environment File
Create `.env.production` locally:
```bash
cd /Users/ericslarson/directory-app
cp .env.example .env.production
```

Edit `.env.production`:
```env
APP_NAME=Listerino
APP_ENV=production
APP_KEY=base64:YOUR_PRODUCTION_KEY_HERE
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://listerino.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.listerino.com
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis
CACHE_PREFIX=listerino_cache_

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=YOUR_POSTMARK_TOKEN
MAIL_PASSWORD=YOUR_POSTMARK_TOKEN
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@listerino.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
VITE_API_URL="${APP_URL}"

CLOUDFLARE_ACCOUNT_ID=YOUR_CLOUDFLARE_ACCOUNT_ID
CLOUDFLARE_API_TOKEN=YOUR_CLOUDFLARE_API_TOKEN
CLOUDFLARE_IMAGES_ACCOUNT_HASH=YOUR_CLOUDFLARE_IMAGES_HASH
CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net/YOUR_HASH

SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com
SESSION_DOMAIN=.listerino.com
```

## Step 1: Prepare Local Code for Production

### 1.1 Build Frontend Assets
```bash
cd /Users/ericslarson/directory-app/frontend
npm install
npm run build
cd ..
```

### 1.2 Ensure Production Dependencies
```bash
# Update composer dependencies
composer install --optimize-autoloader --no-dev

# Generate optimized autoload files
composer dump-autoload --optimize
```

### 1.3 Commit All Changes
```bash
git add .
git commit -m "Build: Production deployment with all recent changes

- Place workflow improvements
- Admin interface updates
- Frontend preview component
- Database optimizations"

git push origin main
```

## Step 2: Database Export and Preparation

### 2.1 Export Development Database
```bash
# Export full database structure and data
pg_dump -h localhost -U ericslarson -d illum_local --no-owner --no-privileges > development_database.sql

# Create a separate schema-only export
pg_dump -h localhost -U ericslarson -d illum_local --schema-only --no-owner --no-privileges > development_schema.sql

# Export essential seed data only (not test data)
pg_dump -h localhost -U ericslarson -d illum_local \
  --data-only \
  --no-owner \
  --no-privileges \
  -t categories \
  -t regions \
  -t settings \
  > development_seed_data.sql
```

### 2.2 Prepare Migration Script
Create `database/production_migration.sql`:
```sql
-- Production database migration script
-- This ensures clean migration without conflicts

-- 1. Drop existing schema if needed (CAREFUL!)
-- DROP SCHEMA public CASCADE;
-- CREATE SCHEMA public;

-- 2. Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "postgis";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- 3. Import will happen via script
```

## Step 3: Production Server Setup

### 3.1 SSH into Production
```bash
ssh root@137.184.113.161
```

### 3.2 Create Directory Structure
```bash
# Create necessary directories
mkdir -p /var/www/listerino
mkdir -p /var/log/listerino
mkdir -p /backups/listerino
mkdir -p /etc/nginx/ssl
```

### 3.3 Set Up Docker Services (Database & Redis)
Create `/var/www/listerino/docker-compose.yml`:
```yaml
version: '3.8'

services:
  db:
    image: postgis/postgis:15-3.4
    container_name: listerino_db
    restart: unless-stopped
    environment:
      POSTGRES_DB: listerino
      POSTGRES_USER: listerino
      POSTGRES_PASSWORD: YOUR_SECURE_DB_PASSWORD
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - listerino_network

  redis:
    image: redis:alpine
    container_name: listerino_redis
    restart: unless-stopped
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    ports:
      - "6379:6379"
    networks:
      - listerino_network

volumes:
  postgres_data:
  redis_data:

networks:
  listerino_network:
    driver: bridge
```

Start Docker services:
```bash
cd /var/www/listerino
docker-compose up -d
```

### 3.4 Clone Repository
```bash
cd /var/www/listerino

# Clone if not exists
git clone https://github.com/lcreative777/listerino.git .

# Or pull latest if exists
git pull origin main
```

### 3.5 Install PHP and Required Extensions
```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php
apt update

# Install PHP 8.3 and extensions
apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
  php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-redis php8.3-bcmath \
  php8.3-intl php8.3-imagick

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
```

### 3.6 Configure Nginx
Create `/etc/nginx/sites-available/listerino.com`:
```nginx
server {
    listen 80;
    server_name listerino.com www.listerino.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name listerino.com www.listerino.com;
    root /var/www/listerino/public;

    ssl_certificate /etc/letsencrypt/live/listerino.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/listerino.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    index index.php;

    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

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

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:
```bash
ln -s /etc/nginx/sites-available/listerino.com /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 3.7 SSL Certificate
```bash
# Install Certbot
apt install certbot python3-certbot-nginx

# Get SSL certificate
certbot --nginx -d listerino.com -d www.listerino.com
```

## Step 4: Deploy Application

### 4.1 Install Dependencies
```bash
cd /var/www/listerino

# Copy production environment file
cp .env.production .env

# Generate application key
php artisan key:generate

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install and build frontend
cd frontend
npm ci --production
npm run build
cd ..
```

### 4.2 Set Permissions
```bash
# Set proper ownership
chown -R www-data:www-data /var/www/listerino

# Set directory permissions
find /var/www/listerino -type d -exec chmod 755 {} \;
find /var/www/listerino -type f -exec chmod 644 {} \;

# Set special permissions for Laravel
chmod -R 775 /var/www/listerino/storage
chmod -R 775 /var/www/listerino/bootstrap/cache
```

## Step 5: Database Migration (Zero Downtime)

### 5.1 Import Database Schema
```bash
# Copy SQL files to server
scp development_schema.sql root@137.184.113.161:/tmp/
scp development_seed_data.sql root@137.184.113.161:/tmp/

# On production server
cd /var/www/listerino

# Import schema
docker exec -i listerino_db psql -U listerino listerino < /tmp/development_schema.sql

# Run Laravel migrations to ensure everything is up to date
php artisan migrate --force

# Import seed data
docker exec -i listerino_db psql -U listerino listerino < /tmp/development_seed_data.sql
```

### 5.2 Optimize Database
```bash
# Run optimizations
docker exec listerino_db psql -U listerino listerino -c "VACUUM ANALYZE;"

# Create indexes for performance
php artisan db:index
```

## Step 6: Final Production Setup

### 6.1 Laravel Optimizations
```bash
cd /var/www/listerino

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run queue workers (if using queues)
# php artisan queue:work --daemon
```

### 6.2 Create Cron Jobs
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/listerino && php artisan schedule:run >> /dev/null 2>&1

# Add backup job
0 2 * * * docker exec listerino_db pg_dump -U listerino listerino | gzip > /backups/listerino/db_$(date +\%Y\%m\%d).sql.gz
```

### 6.3 Set Up Monitoring
```bash
# Install monitoring tools
apt install htop ncdu

# Set up log rotation
cat > /etc/logrotate.d/listerino << EOF
/var/www/listerino/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
EOF
```

## Step 7: Deployment Script for Future Updates

Create `/var/www/listerino/deploy.sh`:
```bash
#!/bin/bash
set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Build frontend
cd frontend
npm ci --production
npm run build
cd ..

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache

echo "Deployment complete!"
```

Make it executable:
```bash
chmod +x /var/www/listerino/deploy.sh
```

## Step 8: Verify Deployment

### 8.1 Health Checks
```bash
# Check Laravel
curl https://listerino.com/api/health

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Check Redis
php artisan tinker
>>> Redis::ping();
>>> exit
```

### 8.2 Monitor Logs
```bash
# Laravel logs
tail -f /var/www/listerino/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log
```

## Rollback Plan

If something goes wrong:
```bash
# 1. Restore database
docker exec -i listerino_db psql -U listerino listerino < /backups/production_backup_TIMESTAMP.sql

# 2. Restore code
cd /var/www
mv listerino listerino_failed
tar -xzf /backups/code_backup_TIMESTAMP.tar.gz

# 3. Clear caches
cd /var/www/listerino
php artisan cache:clear
php artisan config:clear

# 4. Restart services
systemctl restart php8.3-fpm
systemctl restart nginx
```

## Post-Deployment Checklist

- [ ] Verify homepage loads
- [ ] Test user registration/login
- [ ] Check place creation/editing
- [ ] Verify image uploads work
- [ ] Test search functionality
- [ ] Check admin panel access
- [ ] Verify SSL certificate
- [ ] Monitor error logs for 30 minutes
- [ ] Set up uptime monitoring
- [ ] Configure backup verification

## Notes

1. **Database Credentials**: Make sure to use strong passwords in production
2. **Environment Variables**: Double-check all API keys and tokens
3. **Monitoring**: Consider setting up tools like New Relic or Sentry
4. **Backups**: Verify automated backups are working
5. **Security**: Run security audit after deployment

Remember to update DNS records if needed:
- A record: listerino.com → 137.184.113.161
- A record: www.listerino.com → 137.184.113.161