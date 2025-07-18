#!/bin/bash

echo "=== Fixing Nginx SPA Configuration Properly ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Removing incorrect nginx config..."
docker exec listerino_app rm -f /opt/docker/etc/nginx/conf.d/spa-fallback.conf

echo -e "\n2. Checking default vhost configuration..."
docker exec listerino_app cat /opt/docker/etc/nginx/vhost.conf | grep -A20 "server {"

echo -e "\n3. Creating SPA rewrite in correct location..."
docker exec listerino_app bash -c "mkdir -p /opt/docker/etc/nginx/vhost.common.d && cat > /opt/docker/etc/nginx/vhost.common.d/10-spa.conf << 'EOF'
# SPA fallback for Vue Router
location / {
    try_files \\\$uri \\\$uri/ /index.html;
}

# Ensure API routes go to PHP
location ~ ^/(api|sanctum|logout|storage)/ {
    try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
}
EOF"

echo -e "\n4. Testing nginx configuration..."
docker exec listerino_app nginx -t

echo -e "\n5. If nginx test passed, restart container..."
if docker exec listerino_app nginx -t 2>&1 | grep -q "syntax is ok"; then
    docker restart listerino_app
    echo "Container restarted successfully"
else
    echo "Nginx config has errors, removing spa config..."
    docker exec listerino_app rm -f /opt/docker/etc/nginx/vhost.common.d/10-spa.conf
    docker restart listerino_app
fi

echo -e "\n6. Waiting for services..."
sleep 10

echo -e "\n7. Checking final status..."
docker exec listerino_app ps aux | grep nginx | head -3
docker exec listerino_app grep SPA_MODE /app/.env

echo -e "\n8. Testing places page..."
curl -s -I https://listerino.com/places | grep -E "HTTP|Location"

echo -e "\n9. Testing if places show in feed - manual check needed"
echo "Please log into https://listerino.com and check:"
echo "- Can you click on places in /places page?"
echo "- Do places show in the home feed?"

echo -e "\n=== Configuration complete ==="
ENDSSH