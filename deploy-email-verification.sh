#!/bin/bash

echo "🚀 Deploying email verification features to production..."

# Create deployment package
echo "📦 Creating deployment package..."
tar -czf email-verification-deploy.tar.gz \
    app/Http/Controllers/Auth/SpaAuthController.php \
    app/Models/User.php \
    app/Notifications/CustomResetPasswordNotification.php \
    routes/api.php \
    frontend/dist/ \
    frontend/src/components/profile/EmailVerification.vue \
    frontend/src/views/profile/Edit.vue

echo "📤 Uploading to server..."
scp email-verification-deploy.tar.gz root@137.184.113.161:/var/www/listerino/

echo "🔧 Deploying on server..."
ssh root@137.184.113.161 << 'ENDSSH'
    cd /var/www/listerino
    
    # Backup current files
    echo "Creating backups..."
    cp app/Http/Controllers/Auth/SpaAuthController.php app/Http/Controllers/Auth/SpaAuthController.php.backup
    cp routes/api.php routes/api.php.backup
    
    # Extract new files
    echo "Extracting updates..."
    tar -xzf email-verification-deploy.tar.gz
    
    # Clear caches
    echo "Clearing caches..."
    docker exec -w /app listerino_app_manual php artisan config:clear
    docker exec -w /app listerino_app_manual php artisan cache:clear
    docker exec -w /app listerino_app_manual php artisan config:cache
    
    # Cleanup
    rm email-verification-deploy.tar.gz
    
    echo "✅ Deployment complete!"
ENDSSH

# Cleanup local file
rm email-verification-deploy.tar.gz

echo "✨ Email verification features deployed successfully!"