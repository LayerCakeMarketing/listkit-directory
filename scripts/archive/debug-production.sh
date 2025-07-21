#!/bin/bash

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "=== Debugging Production Database ==="

echo "1. Current DB_HOST in .env:"
docker exec listerino_app grep DB_HOST /app/.env

echo -e "\n2. Testing DNS resolution:"
docker exec listerino_app nslookup listerino_db || docker exec listerino_app getent hosts listerino_db

echo -e "\n3. Direct PHP PDO test:"
docker exec listerino_app php -r "
try {
    \$pdo = new PDO('pgsql:host=listerino_db;port=5432;dbname=laravel', 'laravel', 'secret');
    echo 'PDO Connection: SUCCESS\n';
    \$stmt = \$pdo->query('SELECT version()');
    echo 'PostgreSQL version: ' . \$stmt->fetchColumn() . '\n';
} catch (Exception \$e) {
    echo 'PDO Connection FAILED: ' . \$e->getMessage() . '\n';
}
"

echo -e "\n4. Laravel database test:"
docker exec listerino_app bash -c "cd /app && php artisan db:show" || echo "Database show failed"

echo -e "\n5. Testing internal API call:"
docker exec listerino_app curl -s -H "Accept: application/json" http://localhost/api/home | python3 -m json.tool | head -30 || echo "API call failed"

echo -e "\n6. Checking error logs:"
docker exec listerino_app tail -20 /app/storage/logs/laravel.log 2>/dev/null || echo "No error logs"

echo "=== Debug complete ==="
ENDSSH