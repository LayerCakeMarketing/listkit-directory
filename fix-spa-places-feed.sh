#!/bin/bash

echo "=== Fixing SPA Mode and Places in Feed ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Re-enabling SPA mode..."
docker exec listerino_app sed -i 's/SPA_MODE=false/SPA_MODE=true/' /app/.env

echo -e "\n2. Creating proper nginx SPA fallback..."
docker exec listerino_app bash -c "cat > /opt/docker/etc/nginx/conf.d/spa-fallback.conf << 'EOF'
# Handle API and backend routes
location ~ ^/(api|sanctum|logout|admin|storage)/ {
    try_files \$uri \$uri/ /index.php?\$query_string;
}

# Handle PHP files
location ~ \.php$ {
    try_files \$uri =404;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    fastcgi_param PATH_INFO \$fastcgi_path_info;
}

# SPA fallback - must be last
location / {
    try_files \$uri \$uri/ @spa;
}

location @spa {
    rewrite ^.*$ /index.html last;
}
EOF"

echo -e "\n3. Verifying HomeController has places query..."
docker exec listerino_app grep -A5 "Only get places" /app/app/Http/Controllers/Api/HomeController.php

echo -e "\n4. Clearing all caches..."
docker exec listerino_app bash -c "cd /app && php artisan config:clear && php artisan cache:clear && php artisan route:clear"

echo -e "\n5. Checking if places table data exists..."
docker exec listerino_app bash -c "cd /app && php artisan tinker --execute=\"echo 'Places count: ' . \App\Models\Place::count();\""

echo -e "\n6. Testing nginx configuration..."
docker exec listerino_app nginx -t

echo -e "\n7. Restarting app container..."
docker restart listerino_app

echo -e "\n8. Waiting for services..."
sleep 10

echo -e "\n9. Verifying SPA mode is enabled..."
docker exec listerino_app grep SPA_MODE /app/.env

echo -e "\n10. Testing endpoints..."
echo "Login page:"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://listerino.com/login

echo "Places page:"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://listerino.com/places

echo "API home (requires auth):"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://listerino.com/api/home

echo "Places API:"
curl -s https://listerino.com/api/places/public | python3 -m json.tool | grep -E "title|slug" | head -10

echo -e "\n=== Fix complete! ==="
echo "1. SPA mode is enabled - places links should work"
echo "2. Places should appear in the home feed"
echo "3. Test by visiting https://listerino.com/places and clicking a place"
ENDSSH