#!/bin/bash

echo "=== Fixing Docker Production Environment ==="

# SSH into server and fix Docker environment
ssh root@listerino.com << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking Docker network..."
docker network ls

echo "2. Checking if containers are running..."
docker ps -a

echo "3. Restarting Docker Compose..."
docker-compose down
docker-compose up -d

echo "4. Waiting for containers to start..."
sleep 10

echo "5. Checking container connectivity..."
docker exec laravel-app ping -c 1 db || echo "Failed to ping db container"

echo "6. Updating .env file with correct database host..."
docker exec laravel-app bash -c "cd /var/www && sed -i 's/DB_HOST=.*/DB_HOST=db/' .env"

echo "7. Clearing all caches..."
docker exec laravel-app bash -c "cd /var/www && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear"

echo "8. Testing database connection..."
docker exec laravel-app bash -c "cd /var/www && php artisan tinker --execute=\"DB::connection()->getPdo();\""

echo "9. Checking logs..."
docker logs --tail 20 laravel-app

echo "=== Done! ==="
ENDSSH