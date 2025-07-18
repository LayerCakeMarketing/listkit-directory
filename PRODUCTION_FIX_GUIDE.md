# Production Fix Guide

## The Issue
The login page is showing a 500 error due to:
1. Database connection trying to connect to 127.0.0.1 instead of 'db' container
2. Encryption key mismatch
3. Frontend not properly deployed

## Manual Fix Steps

### Step 1: SSH into the server
```bash
ssh root@listerino.com
```

### Step 2: Fix the environment file
```bash
cd /var/www/listerino

# Backup current .env
cp .env .env.backup

# Create new .env with correct Docker settings
cat > .env << 'EOF'
APP_NAME="Listerino"
APP_ENV=production
APP_KEY=base64:1YA8nhjDSrRM7PqEjFitF/K704J3ZLUn5FsbhcPnODk=
APP_DEBUG=false
APP_URL=https://listerino.com
SPA_MODE=true

LOG_CHANNEL=stack
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

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="Listerino"

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
EOF
```

### Step 3: Copy .env to app container
```bash
docker cp .env laravel-app:/var/www/.env
```

### Step 4: Clear all caches
```bash
docker exec laravel-app bash -c "cd /var/www && \
    rm -rf bootstrap/cache/*.php && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize"
```

### Step 5: Set permissions
```bash
docker exec laravel-app bash -c "cd /var/www && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache"
```

### Step 6: Deploy frontend files
From your local machine, run:
```bash
# In the directory-app/frontend folder
scp dist.tar.gz root@listerino.com:/tmp/
```

Then on the server:
```bash
cd /tmp
tar -xzf dist.tar.gz
docker cp dist/. laravel-nginx:/var/www/public/
rm -rf dist dist.tar.gz
```

### Step 7: Restart containers
```bash
docker restart laravel-app laravel-nginx
```

### Step 8: Verify
```bash
# Check container status
docker ps | grep laravel

# Test endpoints
curl -I https://listerino.com/login
curl -I https://listerino.com/api/admin/marketing-pages/special/login

# Check logs if still having issues
docker logs --tail 50 laravel-app
```

## Alternative: If Docker networking is broken

If the 'db' hostname still doesn't resolve, you may need to recreate the Docker network:

```bash
# Stop containers
docker-compose down

# Recreate network and start containers
docker-compose up -d

# Then repeat steps 3-7 above
```

## Files that need to be present

Make sure these files are deployed:
- `/var/www/listerino/app/Http/Controllers/Api/Admin/LoginPageController.php`
- `/var/www/listerino/app/Models/LoginPageSettings.php`
- Frontend dist files in nginx container

## Expected Result

After these fixes:
1. Login page should load without 500 error
2. Background image should be saved and displayed properly
3. All API endpoints should work
4. No CORS errors