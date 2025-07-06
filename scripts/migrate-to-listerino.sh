#!/bin/bash

# Migration script to move from listkit to listerino
# This handles the complete migration including database and files

set -e

echo "🚀 Migration from listkit to listerino"
echo "====================================="

# Step 1: Set up new Git repository
echo "📦 Step 1: Setting up new GitHub repository..."
echo "Current remote: $(git remote get-url origin)"

read -p "Add new remote 'listerino'? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git remote add listerino https://github.com/lcreative777/listerino.git || echo "Remote 'listerino' already exists"
    echo "✅ Remote added. Pushing current code..."
    git push -u listerino main || git push -u listerino master:main
fi

# Step 2: Create server migration script
echo "📝 Creating server migration script..."
cat > /tmp/server-migration.sh <<'MIGRATION_SCRIPT'
#!/bin/bash
set -e

echo "🔄 Starting server migration..."

# Backup current installation
echo "💾 Backing up current installation..."
sudo tar -czf /root/listkit-backup-$(date +%Y%m%d_%H%M%S).tar.gz /var/www/listkit
sudo -u postgres pg_dump listkit > /root/listkit-db-backup-$(date +%Y%m%d_%H%M%S).sql

# Create new directory
echo "📁 Creating new directory structure..."
sudo mkdir -p /var/www/listerino
sudo chown -R www-data:www-data /var/www/listerino

# Clone new repository
echo "📥 Cloning from new repository..."
cd /var/www
sudo -u www-data git clone https://github.com/lcreative777/listerino.git listerino-temp
sudo mv listerino-temp/* listerino-temp/.[^.]* /var/www/listerino/ 2>/dev/null || true
sudo rm -rf listerino-temp

# Copy storage and other persistent data
echo "📋 Copying persistent data..."
sudo cp -r /var/www/listkit/storage/* /var/www/listerino/storage/ 2>/dev/null || true
sudo cp -r /var/www/listkit/public/storage/* /var/www/listerino/public/storage/ 2>/dev/null || true

# Rename database
echo "🗄️ Migrating database..."
sudo -u postgres psql <<EOF
-- Create new database
CREATE DATABASE listerino WITH TEMPLATE listkit OWNER listkit_user;
-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE listerino TO listkit_user;
-- Update user (optional - keeping same user is fine)
EOF

# Create new .env file
echo "📝 Creating new environment file..."
cat > /var/www/listerino/.env <<'ENV_EOF'
APP_NAME="Listerino"
APP_ENV=production
APP_KEY=base64:1LKyMrIvqL7Us6XX+49k//dbuVqGugyPfQngMZrulI0=
APP_DEBUG=false
APP_URL=https://listerino.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listkit_user
DB_PASSWORD=qkcHS02rRlswbpoX6KZyigLJIkrpK+HRSgxFqKX5js8=

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
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="${APP_NAME}"

# Copy Cloudflare settings from old .env if needed
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ACCOUNT_HASH=
VITE_CLOUDFLARE_ACCOUNT_HASH="${CLOUDFLARE_ACCOUNT_HASH}"
ENV_EOF

# Set permissions
sudo chown www-data:www-data /var/www/listerino/.env
sudo chmod 600 /var/www/listerino/.env

# Update Nginx configuration
echo "🌐 Updating Nginx configuration..."
sudo cat > /etc/nginx/sites-available/listerino <<'NGINX_EOF'
server {
    listen 80;
    listen [::]:80;
    server_name listerino.com www.listerino.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name listerino.com www.listerino.com;
    
    root /var/www/listerino/public;
    index index.php index.html index.htm;
    
    # SSL configuration (will be updated by certbot)
    ssl_certificate /etc/letsencrypt/live/listk.it/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/listk.it/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Logs
    access_log /var/log/nginx/listerino.access.log;
    error_log /var/log/nginx/listerino.error.log;
    
    client_max_body_size 64M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_EOF

# Enable new site
sudo ln -sf /etc/nginx/sites-available/listerino /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/listkit

# Install dependencies
echo "📦 Installing dependencies..."
cd /var/www/listerino
sudo -u www-data composer install --optimize-autoloader --no-dev
sudo -u www-data npm ci
sudo -u www-data npm run build

# Run migrations
echo "🗄️ Running migrations..."
sudo -u www-data php artisan migrate --force

# Create storage link
sudo -u www-data php artisan storage:link --force

# Clear caches
echo "🧹 Clearing caches..."
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Set final permissions
sudo chmod -R 755 /var/www/listerino/storage /var/www/listerino/bootstrap/cache
sudo chown -R www-data:www-data /var/www/listerino

# Get new SSL certificate
echo "🔒 Getting SSL certificate for listerino.com..."
sudo certbot --nginx -d listerino.com -d www.listerino.com --non-interactive --agree-tos --email admin@listerino.com || echo "SSL setup needs manual intervention"

# Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.3-fpm
sudo nginx -s reload

echo "✅ Migration complete!"
echo ""
echo "⚠️  IMPORTANT: Next steps:"
echo "1. Update Cloudflare credentials in /var/www/listerino/.env"
echo "2. Test the site at https://listerino.com"
echo "3. Once verified, you can remove old installation: sudo rm -rf /var/www/listkit"
echo "4. Drop old database (after verification): sudo -u postgres dropdb listkit"
MIGRATION_SCRIPT

echo "✅ Migration script created!"
echo ""
echo "📋 Next steps:"
echo "1. Copy script to server: scp /tmp/server-migration.sh root@137.184.113.161:/tmp/"
echo "2. SSH to server: ssh root@137.184.113.161"
echo "3. Run migration: bash /tmp/server-migration.sh"
echo ""
echo "This will:"
echo "- Backup current listkit installation"
echo "- Create new listerino setup"
echo "- Migrate database (keeping data intact)"
echo "- Update Nginx configuration"
echo "- Keep the same database user/password"