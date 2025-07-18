#!/bin/bash

echo "=== Investigating and Fixing Nginx ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Listing all nginx config files..."
docker exec listerino_app find /opt/docker/etc/nginx -name "*.conf" -type f

echo -e "\n2. Checking for existing location directives..."
docker exec listerino_app grep -r "location /" /opt/docker/etc/nginx/vhost.common.d/ 2>/dev/null || echo "No location / found in vhost.common.d"

echo -e "\n3. Removing any SPA configs..."
docker exec listerino_app rm -f /opt/docker/etc/nginx/vhost.common.d/10-spa.conf
docker exec listerino_app rm -f /opt/docker/etc/nginx/vhost.common.d/spa.conf

echo -e "\n4. Checking if location / is already defined..."
docker exec listerino_app grep -B2 -A2 "location /" /opt/docker/etc/nginx/vhost.conf

echo -e "\n5. Since SPA mode is enabled, let's ensure index.html exists..."
docker exec listerino_app ls -la /app/public/index.html

echo -e "\n6. Testing nginx without extra configs..."
docker exec listerino_app nginx -t

echo -e "\n7. Restarting to apply clean config..."
docker restart listerino_app

sleep 5

echo -e "\n8. Alternative approach - modify app to handle SPA routing in PHP..."
docker exec listerino_app bash -c "cd /app && cat > routes/web-spa.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

// API and auth routes are in api.php and auth.php

// SPA catch-all route - must be last
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
EOF"

echo -e "\n9. Checking if SPA mode uses web-spa.php..."
docker exec listerino_app grep -A5 "SPA mode" /app/routes/web.php

echo -e "\n10. Final test..."
echo "Container status:"
docker ps | grep listerino_app

echo -e "\nTesting places page:"
curl -s https://listerino.com/places | grep -o "<title>.*</title>" | head -1

echo -e "\n=== Done! ==="
echo "SPA routing should now work through Laravel instead of nginx"
ENDSSH