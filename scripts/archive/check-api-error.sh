#!/bin/bash

echo "=== Checking API Error on Production ==="

ssh root@listerino.com << 'ENDSSH'
cd /var/www/listerino

echo "1. Checking Laravel logs..."
docker exec laravel-app tail -n 50 /var/www/storage/logs/laravel.log

echo -e "\n2. Checking container logs..."
docker logs --tail 30 laravel-app

echo -e "\n3. Testing API endpoint directly..."
docker exec laravel-app bash -c "cd /var/www && php artisan tinker --execute=\"
    \$user = App\Models\User::where('email', 'eric@layercakemarketing.com')->first();
    if (\$user) {
        echo 'User found: ' . \$user->name . PHP_EOL;
        echo 'User ID: ' . \$user->id . PHP_EOL;
        echo 'Following count: ' . \$user->following()->count() . PHP_EOL;
    } else {
        echo 'User not found';
    }
\""

echo -e "\n4. Checking if required tables exist..."
docker exec laravel-app bash -c "cd /var/www && php artisan tinker --execute=\"
    echo 'Posts table exists: ' . (Schema::hasTable('posts') ? 'Yes' : 'No') . PHP_EOL;
    echo 'Places table exists: ' . (Schema::hasTable('places') ? 'Yes' : 'No') . PHP_EOL;
    echo 'User lists table exists: ' . (Schema::hasTable('user_lists') ? 'Yes' : 'No') . PHP_EOL;
    echo 'List categories table exists: ' . (Schema::hasTable('list_categories') ? 'Yes' : 'No') . PHP_EOL;
    echo 'Tags table exists: ' . (Schema::hasTable('tags') ? 'Yes' : 'No') . PHP_EOL;
\""

echo -e "\n5. Checking database connection..."
docker exec laravel-app bash -c "cd /var/www && php artisan db:show"

ENDSSH