#!/bin/bash

echo "=== Fixing Production Database Connection ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking current .env in container..."
docker exec listerino_app cat /var/www/.env | grep DB_

echo "2. Fixing database host in .env..."
docker exec listerino_app bash -c "cd /var/www && sed -i 's/DB_HOST=.*/DB_HOST=listerino_db/' .env"

echo "3. Verifying the change..."
docker exec listerino_app cat /var/www/.env | grep DB_HOST

echo "4. Clearing Laravel caches..."
docker exec listerino_app bash -c "cd /var/www && php artisan config:clear && php artisan cache:clear"

echo "5. Testing database connection..."
docker exec listerino_app bash -c "cd /var/www && php artisan tinker --execute=\"try { DB::connection()->getPdo(); echo 'Database connected successfully!'; } catch (Exception \\\$e) { echo 'Database connection failed: ' . \\\$e->getMessage(); }\""

echo "6. Testing container connectivity..."
docker exec listerino_app ping -c 1 listerino_db

echo "7. Checking API endpoints..."
docker exec listerino_app bash -c "cd /var/www && curl -s -o /dev/null -w '%{http_code}' http://localhost/api/home"

echo "=== Done! ==="
ENDSSH