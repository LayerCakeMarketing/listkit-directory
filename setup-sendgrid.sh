#!/bin/bash

# SendGrid Setup Script for Listerino
# This script helps configure SendGrid email settings

echo "========================================="
echo "SendGrid Configuration for Listerino"
echo "========================================="
echo ""

# Check if we're setting up local or production
read -p "Configure for (local/production)? " ENVIRONMENT

if [ "$ENVIRONMENT" = "local" ]; then
    ENV_FILE=".env"
    echo "Configuring local environment..."
else
    ENV_FILE="production.env"
    echo "Configuring production environment..."
    echo "Note: You'll need to manually update the server after this."
fi

# Get SendGrid API key
echo ""
echo "Please get your SendGrid API key from:"
echo "https://app.sendgrid.com/settings/api_keys"
echo ""
read -p "Enter your SendGrid API key: " SENDGRID_API_KEY

# Create backup of current env
if [ -f "$ENV_FILE" ]; then
    cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    echo "Backup created: $ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Update or create env file with SendGrid settings
if [ "$ENVIRONMENT" = "local" ]; then
    # Update local .env
    sed -i '' "s/MAIL_PASSWORD=.*/MAIL_PASSWORD=$SENDGRID_API_KEY/" .env
    
    # Clear config cache
    php artisan config:clear
    php artisan config:cache
    
    echo ""
    echo "✅ Local environment configured!"
    echo ""
    echo "Test with:"
    echo "php artisan tinker"
    echo ">>> Mail::raw('Test', fn(\$m) => \$m->to('your-email@example.com')->subject('Test'));"
    
else
    # Create production env snippet
    cat > sendgrid-production.env << EOF
# SendGrid Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=$SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=donotreply@listerino.com
MAIL_FROM_NAME="Listerino"
EOF
    
    echo ""
    echo "✅ Production configuration created in: sendgrid-production.env"
    echo ""
    echo "To apply to production:"
    echo "1. SSH to server: ssh root@137.184.113.161"
    echo "2. Edit .env: nano /var/www/listerino/.env"
    echo "3. Update the MAIL_* variables with the values in sendgrid-production.env"
    echo "4. Run these commands:"
    echo "   docker exec -w /app listerino_app_manual php artisan config:cache"
    echo "   docker exec -w /app listerino_app_manual php artisan optimize"
fi

echo ""
echo "========================================="
echo "SendGrid Setup Complete!"
echo "========================================="
echo ""
echo "Important reminders:"
echo "- Username is always 'apikey' (literal word)"
echo "- Password is your SendGrid API key"
echo "- From address: donotreply@listerino.com"
echo "- No actual email account needed at listerino.com"
echo ""
echo "Monitor emails at: https://app.sendgrid.com/activity"