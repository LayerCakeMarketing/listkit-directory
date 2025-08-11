# Deployment Troubleshooting Guide

## GitHub Actions Failure - Common Causes

### 1. Frontend Build Failure
The most likely cause is the frontend build failing due to the icon import issue we just fixed.

**Solution**: The fix has been pushed, but you may need to:
- Clear GitHub Actions cache
- Re-run the workflow from the Actions tab

### 2. SSH Key Issues
If the deployment can't connect to the server:

**Check GitHub Secrets**:
- Go to: https://github.com/lcreative777/listerino/settings/secrets/actions
- Verify these secrets exist:
  - `SERVER_HOST` (should be: 137.184.113.161)
  - `SERVER_USER` (should be: root)
  - `SERVER_SSH_KEY` (private key content)

### 3. Docker Container Names
The workflow looks for containers with "app" in the name, but your container is named `listerino_app`.

**Quick Fix**: The script tries to find the container dynamically, but if it fails, you can update line 67 in the workflow.

## Manual Deployment Steps

### Option 1: Re-run GitHub Actions
1. Go to: https://github.com/lcreative777/listerino/actions
2. Click on the failed workflow
3. Click "Re-run all jobs"

### Option 2: Manual Deployment via SSH

1. **Transfer the built frontend to server**:
```bash
# From your local machine
cd /Users/ericslarson/directory-app
scp -r frontend/dist root@137.184.113.161:/var/www/listerino/frontend/
```

2. **SSH to server and run deployment**:
```bash
ssh root@137.184.113.161
cd /var/www/listerino

# Pull latest code
git fetch origin main
git reset --hard origin/main

# Copy frontend dist to Docker container
docker cp frontend/dist/. listerino_app:/app/frontend/dist/

# Run migrations
docker exec -w /app listerino_app php artisan migrate --force

# Enable PostGIS
docker exec -u postgres listerino_db psql -d listerino -c 'CREATE EXTENSION IF NOT EXISTS postgis;'

# Clear caches
docker exec -w /app listerino_app php artisan cache:clear
docker exec -w /app listerino_app php artisan config:clear
docker exec -w /app listerino_app php artisan route:clear
docker exec -w /app listerino_app php artisan view:clear
docker exec -w /app listerino_app php artisan optimize

# Set permissions
docker exec listerino_app chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Check status
docker ps
docker exec -w /app listerino_app php artisan about
```

### Option 3: Use the Manual Deploy Script

1. **Copy script to server**:
```bash
scp manual-deploy.sh root@137.184.113.161:/var/www/listerino/
```

2. **Run it**:
```bash
ssh root@137.184.113.161
cd /var/www/listerino
chmod +x manual-deploy.sh
./manual-deploy.sh
```

## Verify Deployment Success

After deployment, check:

1. **Website loads**: https://listerino.com
2. **API health**: https://listerino.com/api/health
3. **New features work**:
   - List editing with sections: `/mylists/{id}/edit`
   - Map view: `/places/map`
   - Admin lists: `/admin/lists`

## Environment Variables to Check

Make sure these are in production `.env`:
```
VITE_MAPBOX_ACCESS_TOKEN=your_mapbox_token
VITE_APP_URL=https://listerino.com
APP_URL=https://listerino.com
FORCE_HTTPS=true
```

## If Issues Persist

1. **Check container logs**:
```bash
docker logs --tail 100 listerino_app
docker logs --tail 100 listerino_db
```

2. **Check Laravel logs**:
```bash
docker exec listerino_app tail -100 /app/storage/logs/laravel.log
```

3. **Restart containers if needed**:
```bash
cd /var/www/listerino
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml up -d
```