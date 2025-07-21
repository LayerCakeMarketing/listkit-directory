#!/bin/bash

echo "=== Checking Production SPA Configuration ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Current SPA_MODE setting:"
docker exec listerino_app grep SPA_MODE /app/.env

echo -e "\n2. Checking web routes:"
docker exec listerino_app grep -A5 "SPA mode" /app/routes/web.php || echo "No SPA routes found"

echo -e "\n3. Checking if frontend is properly served:"
docker exec listerino_app ls -la /app/public/index.html 2>/dev/null || echo "No index.html in app container"

echo -e "\n4. Checking frontend container:"
docker exec listerino_frontend ls -la /app/dist/index.html

echo -e "\n5. Testing direct place route:"
curl -s -I https://listerino.com/places/entry/4 | head -10

echo -e "\n6. Checking where nginx serves files from:"
docker exec listerino_app grep -A5 "root" /opt/docker/etc/nginx/vhost.conf | head -10

echo -e "\n7. Testing if Vue router is loaded:"
curl -s https://listerino.com/places | grep -o "vue-router" | head -1 || echo "No vue-router found"

echo -e "\n8. Checking HomeController for places inclusion:"
docker exec listerino_app grep -n "Place::where" /app/app/Http/Controllers/Api/HomeController.php || echo "No places query found"
ENDSSH