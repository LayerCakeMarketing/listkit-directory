#!/bin/bash

echo "=== EMERGENCY FIX FOR 502 ERROR ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking container status..."
docker ps -a | grep listerino

echo -e "\n2. Checking app container logs..."
docker logs --tail 50 listerino_app

echo -e "\n3. Restarting app container..."
docker restart listerino_app

echo -e "\n4. Waiting for container to start..."
sleep 10

echo -e "\n5. Checking if container is running..."
docker ps | grep listerino_app

echo -e "\n6. Testing nginx to PHP-FPM connection..."
docker exec listerino_app ps aux | grep php-fpm

echo -e "\n7. Checking .env file..."
docker exec listerino_app head -20 /app/.env

echo -e "\n8. Testing basic PHP..."
docker exec listerino_app php -v

echo -e "\n9. Emergency rollback - disable SPA mode..."
docker exec listerino_app sed -i 's/SPA_MODE=true/SPA_MODE=false/' /app/.env
docker exec listerino_app php artisan config:clear

echo -e "\n10. Final restart..."
docker restart listerino_app

sleep 5

echo -e "\n11. Testing endpoints..."
curl -s -o /dev/null -w "Login page: %{http_code}\n" https://listerino.com/login
curl -s -o /dev/null -w "API endpoint: %{http_code}\n" https://listerino.com/api/login-settings

echo -e "\n=== Emergency fix complete! ==="
ENDSSH