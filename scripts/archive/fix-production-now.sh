#!/bin/bash

echo "=== Fixing Production Issues ==="

# First copy the fixed controller
scp app/Http/Controllers/Api/HomeController.php root@137.184.113.161:/tmp/

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking Docker containers status..."
docker ps --format "table {{.Names}}\t{{.Status}}"

echo -e "\n2. Fixing Docker networking..."
# Restart Docker compose to recreate network
docker-compose restart

echo -e "\n3. Waiting for containers..."
sleep 10

echo -e "\n4. Testing database connection..."
docker exec listerino_app bash -c "cd /app && php artisan tinker --execute=\"try { \\$pdo = DB::connection()->getPdo(); echo 'Database connected successfully!'; } catch (Exception \\$e) { echo 'Database error: ' . \\$e->getMessage(); }\""

echo -e "\n5. Deploying fixed HomeController..."
docker cp /tmp/HomeController.php listerino_app:/app/app/Http/Controllers/Api/HomeController.php

echo -e "\n6. Clearing all caches..."
docker exec listerino_app bash -c "cd /app && php artisan config:clear && php artisan cache:clear && php artisan route:clear"

echo -e "\n7. Testing API endpoint..."
curl -s -o /dev/null -w "Home API status: %{http_code}\n" https://listerino.com/api/home

echo -e "\nProduction fixed!"
ENDSSH