# Docker Production Fix Guide

## The Root Issue
The Docker containers cannot resolve the 'db' hostname, causing all database queries to fail with 500 errors.

## Quick Fix Steps

### 1. SSH into the server
```bash
ssh root@listerino.com
```

### 2. Check Docker status
```bash
cd /var/www/listerino
docker ps -a
docker network ls
```

### 3. Restart Docker Compose
```bash
docker-compose down
docker-compose up -d
```

### 4. Fix the .env file inside the container
```bash
# Copy correct .env to container
docker exec laravel-app bash -c "cat > /var/www/.env << 'EOF'
APP_NAME=\"Listerino\"
APP_ENV=production
APP_KEY=base64:1YA8nhjDSrRM7PqEjFitF/K704J3ZLUn5FsbhcPnODk=
APP_DEBUG=false
APP_URL=https://listerino.com
SPA_MODE=true

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=laravel-db
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

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=\"noreply@listerino.com\"
MAIL_FROM_NAME=\"Listerino\"

CLOUDFLARE_ACCOUNT_ID=edff4095a6c22cfeaf4e1d474be5fe1e
CLOUDFLARE_IMAGES_TOKEN=e31e4a0da90ec617f0c2b16e47e5a30b68ce3
CLOUDFLARE_EMAIL=eric@layercakemarketing.com
CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A

MAX_FILE_UPLOAD_SIZE=10
ASYNC_UPLOAD_THRESHOLD=3

IMAGEKIT_PUBLIC_KEY=public_s3zNiaelt2ivZUmfqY9iVz0gq+I=
IMAGEKIT_PRIVATE_KEY=private_2PBj7AQ/EJ6sFpciq7zYE9WkaB8=
IMAGEKIT_URL_ENDPOINT=https://ik.imagekit.io/listerinolistkit
IMAGEKIT_ID=listerinolistkit
EOF"
```

### 5. Clear all caches
```bash
docker exec laravel-app bash -c "cd /var/www && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize"
```

### 6. Test database connection
```bash
# This should return "true" if connected
docker exec laravel-app bash -c "cd /var/www && php artisan tinker --execute=\"echo DB::connection()->getPdo() ? 'Database connected!' : 'Database not connected';\""
```

### 7. Check if APIs work
```bash
# Test home API
curl -H "Accept: application/json" https://listerino.com/api/home

# Test profile API
curl -H "Accept: application/json" https://listerino.com/api/@layercake
```

## Alternative Fix: Use Container Name

If 'db' doesn't work, try using the full container name 'laravel-db':

```bash
docker exec laravel-app bash -c "sed -i 's/DB_HOST=.*/DB_HOST=laravel-db/' /var/www/.env"
docker exec laravel-app bash -c "cd /var/www && php artisan config:clear"
```

## Verify Container Networking

```bash
# Check if containers can see each other
docker exec laravel-app ping -c 1 laravel-db
docker exec laravel-app cat /etc/hosts
```

## If All Else Fails

The nuclear option - recreate everything:

```bash
cd /var/www/listerino
docker-compose down -v  # Warning: This removes volumes!
docker-compose up -d
# Then restore database from backup
```

## Expected Results

After fixing:
- https://listerino.com/home should load feed data
- https://listerino.com/@layercake should show profile
- No more 500 errors in console
- Login page should work properly