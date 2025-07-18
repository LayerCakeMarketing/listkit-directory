#!/bin/bash

echo "=== FIXING NGINX CONFIGURATION ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Removing duplicate nginx config..."
docker exec listerino_app rm -f /opt/docker/etc/nginx/vhost.common.d/spa.conf

echo -e "\n2. Checking nginx configuration..."
docker exec listerino_app nginx -t

echo -e "\n3. Restarting app container..."
docker restart listerino_app

echo -e "\n4. Waiting for services..."
sleep 10

echo -e "\n5. Checking if nginx is running..."
docker exec listerino_app ps aux | grep nginx

echo -e "\n6. Testing endpoints..."
curl -s -o /dev/null -w "Login page: %{http_code}\n" https://listerino.com/login
curl -s -o /dev/null -w "API login-settings: %{http_code}\n" https://listerino.com/api/login-settings
curl -s -o /dev/null -w "API home: %{http_code}\n" https://listerino.com/api/home

echo -e "\n7. Checking SPA mode status..."
docker exec listerino_app grep SPA_MODE /app/.env

echo -e "\n=== Nginx fixed! ==="
ENDSSH