# Manual Fix Steps for Production

## 1. SSH into the server
```bash
ssh root@listerino.com
```

## 2. Fix the database connection in Docker
```bash
cd /var/www/listerino

# Check if containers are running
docker ps

# If not running, start them
docker-compose up -d

# Fix the .env file inside the container
docker exec -it laravel-app bash

# Inside the container, edit the .env file
cd /var/www
vi .env

# Change DB_HOST from 'db' to 'laravel-db' (the actual container name)
# Save and exit

# Clear caches
php artisan cache:clear
php artisan config:clear

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Exit the container
exit
```

## 3. Deploy the fixed HomeController
```bash
# From your local machine, copy the fixed controller
scp /Users/ericslarson/directory-app/app/Http/Controllers/Api/HomeController.php root@listerino.com:/tmp/

# On the server
docker cp /tmp/HomeController.php laravel-app:/var/www/app/Http/Controllers/Api/HomeController.php

# Clear routes cache
docker exec laravel-app php artisan route:clear
docker exec laravel-app php artisan config:clear
```

## 4. Fix missing scopes in UserList model
```bash
# The error is likely because the UserList model doesn't have a 'searchable' scope
# Create a temporary fix by editing the model directly in the container

docker exec -it laravel-app bash
cd /var/www
vi app/Models/UserList.php

# Add this scope if it doesn't exist:
public function scopeSearchable($query)
{
    return $query->where('visibility', 'public');
}

# Save and exit
exit
```

## 5. Restart services
```bash
docker restart laravel-app laravel-nginx
```

## 6. Test the endpoints
```bash
# Test from the server
curl -H "Cookie: your-session-cookie" https://listerino.com/api/home
```

## Alternative Quick Fix

If the above doesn't work, here's a quick SQL fix for the database host:

```bash
# Edit docker-compose.yml to use network aliases
vi docker-compose.yml

# Under the db service, add:
networks:
  laravel:
    aliases:
      - db
      - laravel-db

# Then recreate the containers
docker-compose down
docker-compose up -d
```

## Expected Results
- The /api/home endpoint should return JSON data
- No more 500 errors
- Feed should load properly on the home page