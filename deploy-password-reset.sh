#!/bin/bash

echo "🚀 Deploying password reset fix to production..."

# Copy the updated files
echo "📦 Creating deployment package..."
tar -czf password-reset-deploy.tar.gz \
    app/Models/User.php \
    app/Notifications/CustomResetPasswordNotification.php \
    app/Http/Controllers/Auth/SpaAuthController.php \
    frontend/dist/ \
    .env

echo "📤 Uploading to server..."
scp password-reset-deploy.tar.gz root@137.184.113.161:/var/www/listerino/

echo "🔧 Deploying on server..."
ssh root@137.184.113.161 << 'ENDSSH'
    cd /var/www/listerino
    
    # Backup current files
    echo "Creating backup..."
    cp app/Models/User.php app/Models/User.php.backup
    
    # Extract new files
    echo "Extracting updates..."
    tar -xzf password-reset-deploy.tar.gz
    
    # Clear caches
    echo "Clearing caches..."
    docker exec -w /app listerino_app php artisan config:clear
    docker exec -w /app listerino_app php artisan cache:clear
    
    # Cleanup
    rm password-reset-deploy.tar.gz
    
    echo "✅ Deployment complete!"
ENDSSH

# Cleanup local file
rm password-reset-deploy.tar.gz

echo "✨ Password reset fix deployed successfully!"