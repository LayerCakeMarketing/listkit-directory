#!/bin/bash

echo "Fixing local environment issues..."

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Remove bootstrap cache files
rm -f bootstrap/cache/*.php

# Clear compiled files
rm -f storage/framework/views/*.php

# Rebuild caches for local environment
php artisan config:cache
php artisan route:cache

echo "Environment fixed! Please restart your Laravel server."