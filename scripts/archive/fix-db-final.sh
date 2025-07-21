#\!/bin/bash

echo "=== Fixing Production Database Connection ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Current DB_HOST in container..."
docker exec listerino_app grep DB_HOST /app/.env

echo "2. Fixing database host..."
docker exec listerino_app sed -i 's/DB_HOST=.*/DB_HOST=listerino_db/' /app/.env

echo "3. Verifying the change..."
docker exec listerino_app grep DB_HOST /app/.env

echo "4. Clearing Laravel caches..."
docker exec listerino_app bash -c "cd /app && php artisan config:clear && php artisan cache:clear && php artisan route:clear"

echo "5. Testing database connection..."
docker exec listerino_app bash -c "cd /app && php artisan tinker --execute=\"try { DB::connection()->getPdo(); echo 'Database connected\!'; } catch (Exception \\\$e) { echo 'Failed: ' . \\\$e->getMessage(); }\""

echo "6. Restarting app container..."
docker restart listerino_app

echo "Waiting for container to start..."
sleep 5

echo "7. Testing API endpoint..."
curl -s -o /dev/null -w "API Response Code: %{http_code}\n" https://listerino.com/api/home

ENDSSH
