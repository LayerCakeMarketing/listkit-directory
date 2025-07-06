#!/bin/bash

# Server Setup Script for listerino.com
# Run this on the new DigitalOcean server (137.184.113.161)

set -e

echo "🚀 Starting server setup for listerino.com..."

# Update system packages
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install required packages
echo "📦 Installing required packages..."
apt install -y nginx postgresql postgresql-contrib php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-intl \
    php8.3-zip php8.3-bcmath php8.3-redis git curl unzip certbot python3-certbot-nginx

# Install Composer
echo "🎼 Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Install Node.js and npm
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Create web directory
echo "📁 Creating web directory..."
mkdir -p /var/www/listerino
chown -R www-data:www-data /var/www/listerino

# Configure PostgreSQL
echo "🐘 Configuring PostgreSQL..."
sudo -u postgres psql <<EOF
CREATE DATABASE listerino;
CREATE USER listerino_user WITH ENCRYPTED PASSWORD 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE listerino TO listerino_user;
ALTER DATABASE listerino OWNER TO listerino_user;
\q
EOF

# Configure PHP-FPM
echo "⚙️ Configuring PHP-FPM..."
cat > /etc/php/8.3/fpm/pool.d/listerino.conf <<'EOF'
[listerino]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm-listerino.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php/listerino-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 64M
EOF

# Create Nginx configuration
echo "🌐 Creating Nginx configuration..."
cat > /etc/nginx/sites-available/listerino <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name listerino.com www.listerino.com;
    
    # Redirect all HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name listerino.com www.listerino.com;
    
    root /var/www/listerino/public;
    index index.php index.html index.htm;
    
    # SSL configuration will be added by Certbot
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logs
    access_log /var/log/nginx/listerino.access.log;
    error_log /var/log/nginx/listerino.error.log;
    
    # File upload size
    client_max_body_size 64M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm-listerino.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable the site
ln -sf /etc/nginx/sites-available/listerino /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t

# Create log directory for PHP
mkdir -p /var/log/php
chown www-data:www-data /var/log/php

# Set up firewall
echo "🔥 Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Clone repository (if not already cloned)
echo "📥 Setting up repository..."
if [ ! -d "/var/www/listerino/.git" ]; then
    cd /var/www
    git clone https://github.com/LayerCakeMarketing/listkit-directory.git listerino
    chown -R www-data:www-data listerino
fi

# Create .env file
echo "📝 Creating .env file..."
cat > /var/www/listerino/.env <<'ENV_EOF'
APP_NAME="Listerino"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_WITH_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://listerino.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listerino_user
DB_PASSWORD=CHANGE_THIS_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.listerino.com

SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com

BROADCAST_CONNECTION=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="${APP_NAME}"

# Cloudflare configuration (update with your values)
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ACCOUNT_HASH=
VITE_CLOUDFLARE_ACCOUNT_HASH="${CLOUDFLARE_ACCOUNT_HASH}"
ENV_EOF

# Set permissions
chown www-data:www-data /var/www/listerino/.env
chmod 600 /var/www/listerino/.env

# Restart services
echo "🔄 Restarting services..."
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart postgresql

# Set up SSL certificate
echo "🔒 Setting up SSL certificate..."
certbot --nginx -d listerino.com -d www.listerino.com --non-interactive --agree-tos --email your-email@example.com

# Create deployment key
echo "🔑 Creating deployment SSH key..."
if [ ! -f /root/.ssh/listerino_deploy ]; then
    ssh-keygen -t ed25519 -f /root/.ssh/listerino_deploy -N ""
fi
echo "Add this public key to your GitHub repository deploy keys:"
cat /root/.ssh/listerino_deploy.pub

# Configure Git to use deploy key
cat > /root/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile /root/.ssh/listerino_deploy
    StrictHostKeyChecking no
EOF

echo "✅ Server setup complete!"
echo ""
echo "⚠️  IMPORTANT: Next steps:"
echo "1. Update the database password in PostgreSQL and .env file"
echo "2. Add the deployment key to your GitHub repository:"
echo "   https://github.com/LayerCakeMarketing/listkit-directory/settings/keys"
echo "3. Run the initial deployment"
echo "4. Generate a new APP_KEY with: php artisan key:generate"