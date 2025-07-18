#!/bin/bash

echo "=== Finding Laravel Location in Container ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking what's inside the app container..."
docker exec listerino_app ls -la /

echo "2. Looking for Laravel app directories..."
docker exec listerino_app find / -name "artisan" -type f 2>/dev/null | head -5

echo "3. Checking common locations..."
docker exec listerino_app ls -la /app 2>/dev/null || echo "/app not found"
docker exec listerino_app ls -la /var/www/html 2>/dev/null || echo "/var/www/html not found"
docker exec listerino_app ls -la /application 2>/dev/null || echo "/application not found"

echo "4. Checking docker-compose.yml for volume mappings..."
cat docker-compose.yml | grep -A5 "app:"

echo "5. Checking if app is running on host..."
ls -la /var/www/listerino/.env
head -20 /var/www/listerino/.env

ENDSSH