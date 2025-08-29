#!/bin/bash

# Direct deployment script for email verification updates (no migrations)
set -e

echo "Starting direct deployment to production server (email verification only)..."

# Server details
SERVER="root@137.184.113.161"
REMOTE_DIR="/var/www/listerino"

# Create a deployment archive with only the changed files
echo "Creating deployment archive..."
tar -czf email-verification-deploy.tar.gz \
    app/Notifications/CustomEmailVerificationNotification.php \
    app/Notifications/CustomResetPasswordNotification.php \
    app/Models/User.php \
    app/Http/Controllers/Auth/SpaAuthController.php \
    routes/api.php \
    frontend/dist

echo "Transferring files to production server..."
scp email-verification-deploy.tar.gz $SERVER:/tmp/

echo "Deploying on production server..."
ssh $SERVER << 'EOF'
set -e
cd /var/www/listerino

# Backup current files
echo "Creating backup..."
cp -r app/Notifications app/Notifications.bak 2>/dev/null || mkdir -p app/Notifications
cp -r frontend/dist frontend/dist.bak 2>/dev/null || true

# Extract new files
echo "Extracting new files..."
tar -xzf /tmp/email-verification-deploy.tar.gz

# Clear caches only
echo "Clearing caches..."
docker exec -w /app listerino_app_manual php artisan config:clear
docker exec -w /app listerino_app_manual php artisan route:clear
docker exec -w /app listerino_app_manual php artisan cache:clear
docker exec -w /app listerino_app_manual php artisan view:clear

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data app/Notifications
chown -R www-data:www-data app/Models
chown -R www-data:www-data app/Http/Controllers
chown -R www-data:www-data routes
chown -R www-data:www-data frontend/dist

# Clean up
rm /tmp/email-verification-deploy.tar.gz

echo "Deployment complete!"
EOF

# Clean up local archive
rm email-verification-deploy.tar.gz

echo "Direct deployment completed successfully!"