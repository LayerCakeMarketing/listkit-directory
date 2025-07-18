#!/bin/bash

echo "=== Modifying Location Root for SPA ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking current location / config..."
docker exec listerino_app cat /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf

echo -e "\n2. Backing up original..."
docker exec listerino_app cp /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf.bak

echo -e "\n3. Modifying for SPA fallback..."
docker exec listerino_app bash -c "cat > /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf << 'EOF'
location / {
    try_files \\\$uri \\\$uri/ /index.html;
}

location ~ ^/(api|sanctum|logout|oauth|broadcasting|storage)/ {
    try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
}

location ~ \.php$ {
    try_files \\\$uri =404;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME \\\$document_root\\\$fastcgi_script_name;
    fastcgi_pass php;
}
EOF"

echo -e "\n4. Testing nginx configuration..."
docker exec listerino_app nginx -t

echo -e "\n5. If config is valid, restart..."
if docker exec listerino_app nginx -t 2>&1 | grep -q "syntax is ok"; then
    docker restart listerino_app
    echo "Container restarted with SPA config"
else
    echo "Config failed, restoring backup..."
    docker exec listerino_app mv /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf.bak /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf
    docker restart listerino_app
fi

echo -e "\n6. Waiting for services..."
sleep 10

echo -e "\n7. Final tests..."
echo "Places page (should return 200):"
curl -s -o /dev/null -w "%{http_code}\n" https://listerino.com/places

echo "Place detail page test (should return 200):"
curl -s -o /dev/null -w "%{http_code}\n" https://listerino.com/places/entry/4

echo "API test:"
curl -s -o /dev/null -w "%{http_code}\n" https://listerino.com/api/places/public

echo -e "\n=== SPA routing configured! ==="
echo "Test it:"
echo "1. Go to https://listerino.com/places"
echo "2. Click on any place - it should navigate without redirecting"
echo "3. Check home feed at https://listerino.com/home (after login)"
ENDSSH