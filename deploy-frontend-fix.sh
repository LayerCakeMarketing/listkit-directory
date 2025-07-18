#!/bin/bash

echo "=== Deploying Frontend Fix to Production ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /tmp
tar -xzf dist-deploy.tar.gz

echo "1. Checking current nginx setup..."
docker exec listerino_app ls -la /app/public/ | head -5

echo -e "\n2. Deploying frontend files..."
# Copy to frontend container
docker cp dist/. listerino_frontend:/app/dist/

# Also ensure the app container has the frontend files
docker exec listerino_app mkdir -p /app/public
docker cp dist/. listerino_app:/app/public/

echo -e "\n3. Checking nginx configuration..."
docker exec listerino_app cat /opt/docker/etc/nginx/vhost.conf | grep -A5 "location /"

echo -e "\n4. Restarting containers..."
docker restart listerino_frontend listerino_app

echo -e "\n5. Waiting for services..."
sleep 10

echo -e "\n6. Testing endpoints..."
echo "Places page:"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://listerino.com/places

echo -e "\nAPI endpoint:"
curl -s https://listerino.com/api/places/public | head -20

echo -e "\n=== Deployment complete! ==="
echo "Test by visiting: https://listerino.com/places"
echo "Click on any place to verify links are working"

# Cleanup
rm -rf dist/ dist-deploy.tar.gz
ENDSSH