#!/bin/bash

# DigitalOcean Ubuntu 22.04 Setup Script for Laravel
# Run as root: bash setup-ubuntu.sh

set -e

echo "==================================="
echo "Laravel Server Setup Script"
echo "==================================="

# Variables
DB_NAME="listkit_db"
DB_USER="listkit_user"
DB_PASSWORD=$(openssl rand -base64 32)
APP_PATH="/var/www/listkit"
DOMAIN="listk.it"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Starting server setup...${NC}"

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt update && apt upgrade -y

# Install basic packages
echo -e "${YELLOW}Installing basic packages...${NC}"
apt install -y curl git nginx supervisor redis-server unzip software-properties-common

# Install PHP 8.3
echo -e "${YELLOW}Installing PHP 8.3...${NC}"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-gd php8.3-bcmath php8.3-redis php8.3-intl

# Install Composer
echo -e "${YELLOW}Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Node.js
echo -e "${YELLOW}Installing Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Install PostgreSQL
echo -e "${YELLOW}Installing PostgreSQL...${NC}"
sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | tee /etc/apt/trusted.gpg.d/pgdg.asc &>/dev/null
apt update
apt install -y postgresql-15 postgresql-client-15

# Configure PostgreSQL
echo -e "${YELLOW}Configuring PostgreSQL...${NC}"
sudo -u postgres psql <<EOF
CREATE DATABASE ${DB_NAME};
CREATE USER ${DB_USER} WITH ENCRYPTED PASSWORD '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
ALTER DATABASE ${DB_NAME} OWNER TO ${DB_USER};
EOF

# Optimize PostgreSQL for 4GB RAM
cat >> /etc/postgresql/15/main/postgresql.conf <<EOF

# Performance tuning
shared_buffers = 1GB
effective_cache_size = 3GB
work_mem = 10MB
maintenance_work_mem = 256MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
EOF

systemctl restart postgresql

# Create Laravel user
echo -e "${YELLOW}Creating laravel user...${NC}"
if ! id "laravel" &>/dev/null; then
    adduser --disabled-password --gecos "" laravel
    usermod -aG sudo laravel
    rsync --archive --chown=laravel:laravel ~/.ssh /home/laravel
fi

# Configure Nginx
echo -e "${YELLOW}Configuring Nginx...${NC}"
cat > /etc/nginx/sites-available/listkit <<'EOF'
server {
    listen 80;
    server_name listk.it www.listk.it;
    root /var/www/listkit/public;

    index index.php;
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

    client_max_body_size 100M;
}
EOF

ln -sf /etc/nginx/sites-available/listkit /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx

# Configure PHP
echo -e "${YELLOW}Optimizing PHP...${NC}"
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 100M/g' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/g' /etc/php/8.3/fpm/php.ini
systemctl restart php8.3-fpm

# Configure firewall
echo -e "${YELLOW}Configuring firewall...${NC}"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Create application directory
echo -e "${YELLOW}Setting up application directory...${NC}"
mkdir -p ${APP_PATH}
chown laravel:laravel ${APP_PATH}

# Create .env template
cat > ${APP_PATH}/.env.production <<EOF
APP_NAME="ListKit"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@${DOMAIN}"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

chown laravel:laravel ${APP_PATH}/.env.production

# Create deployment script
cat > ${APP_PATH}/deploy.sh <<'EOF'
#!/bin/bash
cd /var/www/listkit

echo "Starting deployment..."

# Maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R laravel:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart queue workers
sudo supervisorctl restart all

# Exit maintenance mode
php artisan up

echo "Deployment completed!"
EOF

chmod +x ${APP_PATH}/deploy.sh
chown laravel:laravel ${APP_PATH}/deploy.sh

# Create supervisor config for queue workers
cat > /etc/supervisor/conf.d/listkit-worker.conf <<EOF
[program:listkit-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_PATH}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_PATH}/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Create backup script
cat > /home/laravel/backup.sh <<'EOF'
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/home/laravel/backups"
mkdir -p $BACKUP_DIR

# Backup database
pg_dump -U listkit_user listkit_db > $BACKUP_DIR/db_backup_$TIMESTAMP.sql

# Backup uploads
tar -czf $BACKUP_DIR/uploads_backup_$TIMESTAMP.tar.gz /var/www/listkit/storage/app/public

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

chmod +x /home/laravel/backup.sh
chown laravel:laravel /home/laravel/backup.sh

# Output credentials
echo -e "${GREEN}==================================="
echo "Setup completed successfully!"
echo "==================================="
echo ""
echo "Database Credentials:"
echo "Database: ${DB_NAME}"
echo "Username: ${DB_USER}"
echo "Password: ${DB_PASSWORD}"
echo ""
echo "Next steps:"
echo "1. Clone your repository to ${APP_PATH}"
echo "2. Copy .env.production to .env and update APP_KEY"
echo "3. Run: php artisan key:generate"
echo "4. Run: php artisan migrate"
echo "5. Install SSL: certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "Save these credentials securely!"
echo -e "===================================${NC}"