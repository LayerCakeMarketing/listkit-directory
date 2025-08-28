# Deployment Guide for Listerino

## Production Infrastructure

- **Server**: DigitalOcean VPS (137.184.113.161)
- **Domain**: https://listerino.com
- **Architecture**: Docker containers managed via docker-compose
- **SSL**: Let's Encrypt with auto-renewal via Nginx

## Docker Services

All services run in Docker containers:

1. **listerino_app_manual** - Laravel application (webdevops/php-nginx:8.3)
2. **9106cb440e2f_listerino_db** - PostgreSQL 15 with PostGIS  
3. **ddeeef0833e6_listerino_redis** - Redis for sessions/cache

## Quick Deployment Commands

### Frontend-Only Deployment (Most Common)

When you've only made frontend changes:

```bash
# 1. Build frontend locally
cd frontend
npm run build
cd ..

# 2. Deploy to production
tar -czf frontend-deploy.tar.gz frontend/dist
scp frontend-deploy.tar.gz root@137.184.113.161:/tmp/
ssh root@137.184.113.161 "cd /var/www/listerino && \
  tar -xzf /tmp/frontend-deploy.tar.gz && \
  docker cp frontend/dist/. listerino_app_manual:/app/frontend/dist/ && \
  docker exec listerino_app_manual chown -R www-data:www-data /app/frontend/dist && \
  docker exec -w /app listerino_app_manual php artisan view:clear && \
  docker exec -w /app listerino_app_manual php artisan optimize"
```

### Full Deployment (Code + Database)

When you need to deploy backend changes and optionally update the database:

```bash
# 1. Build frontend
cd frontend
npm run build
cd ..

# 2. Export database (optional - only if you want to overwrite production data)
pg_dump -h localhost -U your_user -d your_db \
  --no-owner --no-privileges --clean --if-exists > db_export.sql

# 3. Create deployment archive
tar -czf deploy.tar.gz \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.git' \
  --exclude='storage/logs/*' \
  --exclude='.env' \
  --exclude='*.md' \
  --exclude='tests' \
  .

# 4. Transfer files
scp deploy.tar.gz root@137.184.113.161:/tmp/
scp db_export.sql root@137.184.113.161:/tmp/  # Optional

# 5. Deploy on server
ssh root@137.184.113.161
cd /var/www/listerino
tar -xzf /tmp/deploy.tar.gz

# Optional: Import database
docker exec -i 9106cb440e2f_listerino_db psql -U listerino -d listerino < /tmp/db_export.sql

# Update Laravel
docker exec -w /app listerino_app_manual composer install --no-dev --optimize-autoloader
docker exec -w /app listerino_app_manual php artisan migrate --force
docker exec -w /app listerino_app_manual php artisan config:cache
docker exec -w /app listerino_app_manual php artisan route:cache
docker exec -w /app listerino_app_manual php artisan view:cache
docker exec -w /app listerino_app_manual php artisan optimize

# Set permissions
docker exec listerino_app_manual chown -R www-data:www-data /app/storage /app/bootstrap/cache
```

## GitHub Actions Deployment (Automated)

Push to main branch triggers automatic deployment:

```bash
git add .
git commit -m "feat: your feature"
git push origin main
```

Monitor at: https://github.com/lcreative777/listerino/actions

## Common Maintenance Tasks

### View Logs

```bash
# Laravel application logs
docker exec listerino_app_manual tail -f /app/storage/logs/laravel.log

# Container logs
docker logs --tail 100 listerino_app_manual
docker logs --tail 100 9106cb440e2f_listerino_db
```

### Clear Caches

```bash
docker exec -w /app listerino_app_manual php artisan cache:clear
docker exec -w /app listerino_app_manual php artisan config:clear
docker exec -w /app listerino_app_manual php artisan route:clear
docker exec -w /app listerino_app_manual php artisan view:clear
docker exec -w /app listerino_app_manual php artisan optimize:clear
```

### Restart Services

```bash
# Restart individual container
docker restart listerino_app_manual

# Restart all services  
cd /var/www/listerino
docker-compose -f docker-compose.production.yml restart
```

### Database Operations

```bash
# Backup database
docker exec 9106cb440e2f_listerino_db pg_dump -U listerino listerino > backup_$(date +%Y%m%d).sql

# Access database console
docker exec -it 9106cb440e2f_listerino_db psql -U listerino -d listerino

# Run specific migration
docker exec -w /app listerino_app_manual php artisan migrate --path=database/migrations/specific_migration.php
```

## Troubleshooting

### Container Not Running (502 Error)

```bash
# Check container status
docker ps -a

# Start container if stopped
docker start listerino_app_manual

# If container won't start, check logs
docker logs listerino_app_manual
```

### Permission Issues

```bash
docker exec listerino_app_manual chown -R www-data:www-data /app/storage
docker exec listerino_app_manual chmod -R 775 /app/storage
docker exec listerino_app_manual chmod -R 775 /app/bootstrap/cache
```

### Database Connection Issues

```bash
# Verify database is running
docker ps | grep listerino_db

# Test connection from app container
docker exec listerino_app_manual ping 9106cb440e2f_listerino_db

# Check database credentials in container
docker exec listerino_app_manual cat /app/.env | grep DB_
```

### Session/Auth Issues

```bash
# Clear Redis cache
docker exec ddeeef0833e6_listerino_redis redis-cli FLUSHDB

# Verify Redis is running
docker exec ddeeef0833e6_listerino_redis redis-cli ping
```

## Environment Variables

Key production settings in `/var/www/listerino/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://listerino.com

DB_HOST=9106cb440e2f_listerino_db
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=password123

REDIS_HOST=ddeeef0833e6_listerino_redis
SESSION_DRIVER=redis
CACHE_STORE=redis

SESSION_DOMAIN=.listerino.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com
```

## Important Notes

1. **Always build frontend locally** before deployment (npm run build)
2. **Container names are fixed** - use exact names shown above
3. **Database password**: password123 (production)
4. **Never commit .env files** to repository
5. **Test locally first** before deploying to production

## Quick Reference

```bash
# SSH to server
ssh root@137.184.113.161

# Navigate to app directory
cd /var/www/listerino

# View running containers
docker ps

# Access app container
docker exec -it listerino_app_manual bash

# Access database
docker exec -it 9106cb440e2f_listerino_db psql -U listerino -d listerino

# Tail Laravel logs
docker exec listerino_app_manual tail -f /app/storage/logs/laravel.log
```

---
Last Updated: August 2025