#!/bin/bash
# Fix Docker environment configuration

cd /var/www/listerino

# Create correct .env for Docker environment
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

# Copy to container
docker cp .env laravel-app:/var/www/.env

# Clear caches
docker exec laravel-app bash -c "cd /var/www && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear"

# Restart containers
docker restart laravel-app laravel-nginx

echo "Environment fixed!"