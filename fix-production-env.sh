#!/bin/bash

# Fix production environment issues

echo "Fixing production environment..."

# First, let's copy the correct .env file to the server
echo "Copying Docker environment file..."
scp .env.docker root@listerino.com:/tmp/.env.docker

# SSH into the server and fix the environment
ssh root@listerino.com << 'ENDSSH'
cd /var/www/listerino

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Copy the Docker-specific .env
cp /tmp/.env.docker .env

# Update production-specific values
sed -i 's/APP_NAME="Directory App"/APP_NAME="Listerino"/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's|APP_URL=http://localhost:8000|APP_URL=https://listerino.com|' .env
sed -i 's/SESSION_DOMAIN=null/SESSION_DOMAIN=.listerino.com/' .env
sed -i 's/SANCTUM_STATEFUL_DOMAINS=.*/SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com/' .env
sed -i 's/CORS_ALLOWED_ORIGINS=.*/CORS_ALLOWED_ORIGINS=https://listerino.com,https://www.listerino.com/' .env
sed -i 's/SPA_MODE=false/SPA_MODE=true/' .env

# Ensure the correct encryption key is set
if ! grep -q "APP_KEY=base64:1YA8nhjDSrRM7PqEjFitF/K704J3ZLUn5FsbhcPnODk=" .env; then
    echo "Updating APP_KEY..."
    sed -i 's/APP_KEY=.*/APP_KEY=base64:1YA8nhjDSrRM7PqEjFitF\/K704J3ZLUn5FsbhcPnODk=/' .env
fi

# Now copy the .env into the Docker container
docker cp .env laravel-app:/var/www/.env

# Clear all caches in the container
docker exec laravel-app bash -c "cd /var/www && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear"

# Restart the app container
docker restart laravel-app

# Check if container is running
sleep 5
docker ps | grep laravel-app

# Test the login page
echo "Testing login page..."
curl -I https://listerino.com/login

# Clean up
rm /tmp/.env.docker

echo "Production environment fixed!"
ENDSSH

echo "Done!"