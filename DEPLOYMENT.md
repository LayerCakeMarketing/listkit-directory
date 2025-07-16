# Listerino Deployment Guide

This guide documents the most efficient deployment process for pushing local development changes to the production server on Digital Ocean using Docker, GitHub, and automated workflows.

## Prerequisites

### Local Requirements
- Git
- Docker & Docker Compose
- Node.js 18+ and npm
- PHP 8.3+ and Composer
- SSH access to production server (137.184.113.161)

### Production Server
- Digital Ocean Droplet (137.184.113.161)
- Docker & Docker Compose installed
- Nginx (reverse proxy)
- SSL certificates (Let's Encrypt)
- PostgreSQL with PostGIS in Docker

### GitHub Repository
- Repository: https://github.com/lcreative777/listerino
- Main branch for production deployments

## Quick Start - Most Efficient Deployment

### Method 1: Automated GitHub Actions (Recommended)

This is the most efficient method - just push to GitHub and let automation handle the rest:

```bash
# 1. Make your changes locally
git add .
git commit -m "feat: your feature description"

# 2. Push to GitHub main branch
git push origin main

# 3. GitHub Actions automatically:
#    - Runs tests
#    - Builds frontend
#    - Deploys to production
#    - Sends notification on completion

# Monitor deployment at:
# https://github.com/lcreative777/listerino/actions
```

### Method 2: Quick Deploy Script

For rapid deployments with more control:

```bash
# Quick deploy (skips tests and prompts)
./deploy.sh quick

# OR full deploy with options
./deploy.sh deploy
```

## Architecture Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│ Local Dev       │────▶│ GitHub          │────▶│ Production      │
│ - Laravel API   │     │ - Main branch   │     │ - Docker        │
│ - Vue.js SPA    │     │ - Actions CI/CD │     │ - Digital Ocean │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Production Stack with Docker
- **Frontend**: Vue.js SPA served by Nginx
- **Backend**: Laravel API in Docker container (webdevops/php-nginx:8.3)
- **Database**: PostgreSQL with PostGIS in Docker (postgis/postgis:15-3.4)
- **Cache**: Redis in Docker (redis:alpine)
- **Web Server**: Nginx (reverse proxy on host)

## GitHub Actions Setup

1. **Add repository secrets** in GitHub (Settings → Secrets → Actions):
   - `SERVER_HOST`: 137.184.113.161
   - `SERVER_USER`: root (or your deployment user)
   - `SERVER_SSH_KEY`: Your private SSH key
   - `SLACK_WEBHOOK`: (optional) for deployment notifications

2. **Add deploy key to repository**:
   - Go to Settings → Deploy keys
   - Add the public key from the server (`/root/.ssh/listerino_deploy.pub`)
   - Enable write access

## Detailed Deployment Steps

### Step 1: Local Development

```bash
# 1. Start local development
cd /Users/ericslarson/directory-app
composer dev  # Starts all services

# 2. Make your changes
# - Backend: PHP files update automatically
# - Frontend: Hot reload with npm run dev

# 3. Test your changes
./vendor/bin/phpunit
cd frontend && npm test
```

### Step 2: Build and Commit

```bash
# 1. Build frontend for production
cd frontend
npm run build
cd ..

# 2. Commit changes
git add .
git commit -m "feat: descriptive message"

# 3. Push to GitHub
git push origin main
```

### Step 3: Production Deployment

The deployment happens automatically via GitHub Actions, but here's what it does:

```bash
# On production server:
cd /var/www/listerino

# 1. Pull latest code
git pull origin main

# 2. Build frontend
cd frontend
npm ci
npm run build
cd ..

# 3. Update backend in Docker
docker exec listerino_app composer install --no-dev --optimize-autoloader

# 4. Run migrations
docker exec listerino_app php artisan migrate --force

# 5. Clear and rebuild caches
docker exec listerino_app php artisan cache:clear
docker exec listerino_app php artisan config:cache
docker exec listerino_app php artisan route:cache
docker exec listerino_app php artisan view:cache

# 6. Copy frontend to container
docker cp frontend/dist/. listerino_app:/var/www/html/frontend/dist/

# 7. Restart if needed
docker-compose restart
```

## Docker Commands Reference

### Container Management
```bash
# View running containers
docker-compose ps

# View logs
docker-compose logs -f app
docker-compose logs -f db

# Access containers
docker exec -it listerino_app bash
docker exec -it listerino_db psql -U listerino

# Restart services
docker-compose restart
docker-compose restart app
```

### Database Operations
```bash
# Backup database
docker exec listerino_db pg_dump -U listerino listerino > backup_$(date +%Y%m%d).sql

# Restore database
docker exec -i listerino_db psql -U listerino listerino < backup.sql

# Run migrations
docker exec listerino_app php artisan migrate --force
```

## Environment Configuration

### Production .env (inside container)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://listerino.com

DB_CONNECTION=pgsql
DB_HOST=db  # Docker service name
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=secure_password

REDIS_HOST=redis  # Docker service name
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Cloudflare settings
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_api_token
```

## SSL Certificate

The setup script automatically configures Let's Encrypt SSL. To renew:

```bash
certbot renew
```

## Monitoring

### Application Monitoring
```bash
# Laravel logs (inside container)
docker exec listerino_app tail -f storage/logs/laravel.log

# Container logs
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f redis

# Nginx logs (on host)
tail -f /var/log/nginx/listerino.error.log
tail -f /var/log/nginx/listerino.access.log

# Container stats
docker stats
```

### Health Checks
```bash
# API health check
curl https://listerino.com/api/health

# Database connection
docker exec listerino_app php artisan tinker
>>> DB::connection()->getPdo();

# Redis connection
docker exec listerino_app php artisan tinker
>>> Redis::ping();
```

## Maintenance Mode

```bash
# Enable maintenance mode
docker exec listerino_app php artisan down --message="Upgrading Database" --retry=60

# Allow specific IPs during maintenance
docker exec listerino_app php artisan down --allow=127.0.0.1 --allow=192.168.1.0/24

# Disable maintenance mode
docker exec listerino_app php artisan up
```

## Backup Strategy

### Automated Backups (Cron)
```bash
# Add to crontab: crontab -e

# Database backup daily at 2 AM
0 2 * * * docker exec listerino_db pg_dump -U listerino listerino | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz

# Keep only last 7 days of backups
0 3 * * * find /backups -name "db_*.sql.gz" -mtime +7 -delete

# Files backup weekly
0 3 * * 0 tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /var/www/listerino/storage/app/public
```

### Manual Backup
```bash
# Full backup script
#!/bin/bash
BACKUP_DIR="/backups/manual"
mkdir -p $BACKUP_DIR

# Database
docker exec listerino_db pg_dump -U listerino listerino > $BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql

# Files
tar -czf $BACKUP_DIR/files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/listerino/storage/app/public

# Application code
tar -czf $BACKUP_DIR/code_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/listerino --exclude=node_modules --exclude=vendor
```

## Troubleshooting

### Common Issues

1. **Container won't start**
```bash
# Check logs
docker-compose logs app

# Rebuild container
docker-compose build --no-cache app
docker-compose up -d
```

2. **Permission issues**
```bash
# Fix storage permissions
docker exec listerino_app chmod -R 777 storage bootstrap/cache
```

3. **Memory issues**
```bash
# Check memory usage
docker stats

# Increase PHP memory limit
docker exec listerino_app bash -c "echo 'memory_limit = 512M' >> /opt/docker/etc/php/php.ini"
docker-compose restart app
```

4. **Frontend not updating**
```bash
# Force rebuild and copy
cd frontend
npm run build
docker cp dist/. listerino_app:/var/www/html/frontend/dist/
```

### Rollback Procedure

```bash
# 1. Quick rollback via Git
ssh root@137.184.113.161
cd /var/www/listerino
git log --oneline -5
git reset --hard <previous-commit-hash>

# 2. Rebuild containers
docker-compose down
docker-compose up -d

# 3. Restore database if needed
docker exec -i listerino_db psql -U listerino listerino < /backups/db_20240115.sql
```

## Performance Optimization

### Docker Optimization
```bash
# Prune unused images
docker system prune -a

# Use BuildKit for faster builds
export DOCKER_BUILDKIT=1

# Multi-stage builds in Dockerfile
```

### Laravel Optimization
```bash
# Production optimizations
docker exec listerino_app php artisan config:cache
docker exec listerino_app php artisan route:cache
docker exec listerino_app php artisan view:cache
docker exec listerino_app composer install --optimize-autoloader --no-dev
```

### Frontend Optimization
```bash
# Build with production flag
cd frontend
npm run build -- --mode production

# Enable gzip in Nginx
# Already configured in /etc/nginx/sites-available/listerino.com
```

## Security Best Practices

1. **Never commit sensitive data**
   - Use .env files
   - Add .env to .gitignore
   - Use GitHub secrets

2. **Regular updates**
```bash
# Update Docker images
docker-compose pull
docker-compose up -d

# Update dependencies
docker exec listerino_app composer update --no-dev
cd frontend && npm update
```

3. **Monitor logs for suspicious activity**
```bash
# Check for failed login attempts
docker exec listerino_app grep "Failed login" storage/logs/laravel.log

# Check Nginx access logs
grep "POST /api/login" /var/log/nginx/listerino.access.log
```

## Support & Resources

- **GitHub Issues**: https://github.com/lcreative777/listerino/issues
- **Laravel Docs**: https://laravel.com/docs
- **Vue.js Docs**: https://vuejs.org/guide/
- **Docker Docs**: https://docs.docker.com/

---

Last updated: January 2024
Version: 2.0 (Docker-based deployment)