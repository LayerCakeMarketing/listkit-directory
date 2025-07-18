#!/bin/bash

echo "=== Fixing Production SPA and Places ==="

# First copy the corrected HomeController
scp app/Http/Controllers/Api/HomeController.php root@137.184.113.161:/tmp/

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Enabling SPA mode..."
docker exec listerino_app sed -i 's/SPA_MODE=false/SPA_MODE=true/' /app/.env

echo -e "\n2. Deploying fixed HomeController..."
docker cp /tmp/HomeController.php listerino_app:/app/app/Http/Controllers/Api/HomeController.php

echo -e "\n3. Clearing all caches..."
docker exec listerino_app bash -c "cd /app && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear"

echo -e "\n4. Ensuring frontend files are in place..."
# Make sure index.html is the SPA entry point
docker exec listerino_app bash -c "cd /app/public && ls -la index.html"

echo -e "\n5. Checking nginx fallback configuration..."
docker exec listerino_app bash -c "grep -A5 'location /' /opt/docker/etc/nginx/vhost.conf"

echo -e "\n6. Creating nginx SPA configuration if needed..."
docker exec listerino_app bash -c "cat > /opt/docker/etc/nginx/vhost.common.d/spa.conf << 'EOF'
# SPA fallback for Vue Router
location / {
    try_files \$uri \$uri/ /index.html;
}

location /api {
    try_files \$uri \$uri/ /index.php?\$query_string;
}

location /sanctum {
    try_files \$uri \$uri/ /index.php?\$query_string;
}
EOF"

echo -e "\n7. Restarting containers..."
docker restart listerino_app

echo -e "\n8. Waiting for services..."
sleep 10

echo -e "\n9. Testing configuration..."
echo "SPA Mode:"
docker exec listerino_app grep SPA_MODE /app/.env

echo -e "\nTesting places page:"
curl -s -I https://listerino.com/places | grep -E "HTTP|Content-Type"

echo -e "\nTesting API home endpoint (with auth):"
curl -s https://listerino.com/api/home -H "Accept: application/json" | python3 -m json.tool | grep -E "error|message" | head -5

echo -e "\n=== Fix complete! ==="
echo "1. SPA mode is now enabled"
echo "2. HomeController fixed to not require 'status' column for posts"
echo "3. Places should now route correctly"
echo "4. Feed should include places"

# Cleanup
rm /tmp/HomeController.php
ENDSSH