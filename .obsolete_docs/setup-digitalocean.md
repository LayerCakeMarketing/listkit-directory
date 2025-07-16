# DigitalOcean Laravel Deployment Guide for listk.it

## Prerequisites
- DigitalOcean account
- Domain name (listk.it) 
- GitHub repository
- SSH key pair

## Step 1: Create DigitalOcean Droplet

### Droplet Configuration:
1. **Choose an image**: Ubuntu 22.04 (LTS) x64
2. **Choose a plan**: 
   - Start with: Premium AMD - $24/mo (4GB RAM, 2 vCPUs, 80GB SSD)
   - Can upgrade later as needed
3. **Choose datacenter**: Select closest to your users
4. **Authentication**: Add your SSH key
5. **Hostname**: `listkit-app`

## Step 2: Initial Server Setup

```bash
# SSH into your server
ssh root@YOUR_SERVER_IP

# Create a new user
adduser laravel
usermod -aG sudo laravel

# Copy SSH key to new user
rsync --archive --chown=laravel:laravel ~/.ssh /home/laravel

# Switch to new user
su - laravel
```

## Step 3: Install Required Software

```bash
# Update package list
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y curl git nginx supervisor redis-server unzip

# Install PHP 8.3 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-gd php8.3-bcmath php8.3-redis php8.3-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PostgreSQL 15
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo tee /etc/apt/trusted.gpg.d/pgdg.asc &>/dev/null
sudo apt update
sudo apt install -y postgresql-15 postgresql-client-15
```

## Step 4: Configure PostgreSQL

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE listkit_db;
CREATE USER listkit_user WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE listkit_db TO listkit_user;
\q

# Update PostgreSQL configuration for better performance
sudo nano /etc/postgresql/15/main/postgresql.conf
```

Add these optimizations:
```
# Performance tuning for 4GB RAM server
shared_buffers = 1GB
effective_cache_size = 3GB
work_mem = 10MB
maintenance_work_mem = 256MB
```

## Step 5: Configure Nginx

```bash
# Create Nginx configuration
sudo nano /etc/nginx/sites-available/listkit
```

Add this configuration:
```nginx
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
}
```

```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/listkit /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Step 6: Set Up Laravel Application

```bash
# Create application directory
sudo mkdir -p /var/www/listkit
sudo chown laravel:laravel /var/www/listkit
cd /var/www/listkit

# Clone repository
git clone git@github.com:yourusername/listkit-directory.git .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Set up environment
cp .env.example .env
nano .env
```

Update .env file:
```env
APP_NAME="ListKit"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://listk.it

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=listkit_db
DB_USERNAME=listkit_user
DB_PASSWORD=your_secure_password_here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add other necessary configs
```

```bash
# Generate application key
php artisan key:generate

# Set permissions
sudo chown -R laravel:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 7: Set Up SSL with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d listk.it -d www.listk.it

# Auto-renewal is set up automatically
```

## Step 8: Configure Supervisor for Queue Workers

```bash
sudo nano /etc/supervisor/conf.d/listkit-worker.conf
```

Add:
```ini
[program:listkit-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/listkit/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/listkit/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start listkit-worker:*
```

## Step 9: Set Up Deployment Script

Create `/var/www/listkit/deploy.sh`:
```bash
#!/bin/bash
cd /var/www/listkit

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

# Restart services
sudo supervisorctl restart listkit-worker:*
sudo systemctl reload php8.3-fpm
```

Make it executable:
```bash
chmod +x deploy.sh
```

## Step 10: Configure Firewall

```bash
# Set up UFW firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
```

## Step 11: Set Up Automated Backups

```bash
# Create backup script
nano /home/laravel/backup.sh
```

Add:
```bash
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
```

```bash
chmod +x /home/laravel/backup.sh

# Add to crontab
crontab -e
# Add: 0 2 * * * /home/laravel/backup.sh
```

## Monitoring and Maintenance

### Install monitoring
```bash
# Install htop for system monitoring
sudo apt install -y htop

# Install Laravel Telescope (optional)
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Log rotation
Logs are automatically rotated by Laravel and system logrotate.

### Security updates
```bash
# Set up automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

## DNS Configuration

Point your domain to the DigitalOcean droplet:
- A record: `@` → YOUR_SERVER_IP
- A record: `www` → YOUR_SERVER_IP

## Performance Optimizations

1. **Enable OPcache**:
   ```bash
   sudo nano /etc/php/8.3/fpm/conf.d/10-opcache.ini
   ```
   Set: `opcache.enable=1`

2. **Configure Redis for sessions/cache**:
   Update `.env`:
   ```
   SESSION_DRIVER=redis
   CACHE_DRIVER=redis
   ```

3. **Enable HTTP/2** (already enabled with Nginx)

## Troubleshooting

- Check logs: `/var/www/listkit/storage/logs/laravel.log`
- Nginx logs: `/var/log/nginx/error.log`
- PHP logs: `/var/log/php8.3-fpm.log`

## Next Steps

1. Set up monitoring (New Relic, Datadog, or DigitalOcean monitoring)
2. Configure email service (SendGrid, Mailgun, or Amazon SES)
3. Set up CDN for assets (Cloudflare or DigitalOcean Spaces CDN)
4. Implement database backups to DigitalOcean Spaces