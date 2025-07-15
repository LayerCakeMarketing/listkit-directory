#!/bin/bash

echo "Copying files to production server..."

# Copy PHP files
scp app/Http/Controllers/Admin/SettingsController.php root@137.184.113.161:/var/www/listerino/app/Http/Controllers/Admin/
scp app/Models/Setting.php root@137.184.113.161:/var/www/listerino/app/Models/
scp app/Http/Controllers/Admin/MediaController.php root@137.184.113.161:/var/www/listerino/app/Http/Controllers/Admin/
scp app/Http\Controllers/Auth/RegisteredUserController.php root@137.184.113.161:/var/www/listerino/app/Http/Controllers/Auth/
scp app/Http/Middleware/HandleInertiaRequests.php root@137.184.113.161:/var/www/listerino/app/Http/Middleware/
scp app/Services/CloudflareImageService.php root@137.184.113.161:/var/www/listerino/app/Services/

# Copy migration
scp database/migrations/2025_07_07_000003_create_settings_table.php root@137.184.113.161:/var/www/listerino/database/migrations/

# Copy Vue files
scp resources/js/Pages/Admin/Settings/Index.vue root@137.184.113.161:/var/www/listerino/resources/js/Pages/Admin/Settings/
scp resources/js/Pages/Auth/RegistrationClosed.vue root@137.184.113.161:/var/www/listerino/resources/js/Pages/Auth/
scp resources/js/config/navigation.js root@137.184.113.161:/var/www/listerino/resources/js/config/

# Copy routes
scp routes/web.php root@137.184.113.161:/var/www/listerino/routes/

echo "Files copied. Now running deployment commands on server..."

ssh root@137.184.113.161 << 'EOF'
cd /var/www/listerino
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
systemctl restart php8.3-fpm
systemctl restart nginx
EOF