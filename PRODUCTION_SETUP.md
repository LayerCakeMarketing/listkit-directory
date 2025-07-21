# Production Setup Documentation

## Overview
Listerino.com is deployed on DigitalOcean (137.184.113.161) using a hybrid architecture:
- **Docker Containers**: PostgreSQL, Redis, Laravel App, and Frontend Dev
- **Native**: Nginx (reverse proxy)
- **SSL**: Let's Encrypt with automatic renewal

## Infrastructure

### Docker Services
All services are managed via Docker Compose at `/var/www/listerino/docker-compose.yml`:

1. **listerino_app** (webdevops/php-nginx:8.3)
   - Laravel application
   - Port: 8001 (internal)
   - Working directory: /app

2. **listerino_db** (postgis/postgis:15-3.4)
   - PostgreSQL with PostGIS
   - Port: 5432
   - Credentials: listerino/securepassword123
   - Database: listerino

3. **listerino_redis** (redis:7-alpine)
   - Session storage and caching
   - Port: 6379
   - No authentication

4. **listerino_frontend** (Node development)
   - Port: 5174 (development only)

### Nginx Configuration
- Config: `/etc/nginx/sites-available/listerino.com`
- Serves static files from `/var/www/listerino/frontend/dist`
- Proxies API/auth requests to Docker app on port 8001
- SSL termination with Let's Encrypt

### Directory Structure
```
/var/www/listerino/
├── app/                 # Laravel application code
├── frontend/           # Vue.js frontend
│   └── dist/          # Production build
├── storage/           # Laravel storage
├── docker-compose.yml # Docker configuration
└── .env              # Production environment
```

## Environment Configuration

### Key Production Settings
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://listerino.com
SPA_MODE=true

# Database (Docker internal network)
DB_HOST=listerino_db
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=securepassword123

# Redis (Docker internal network)
REDIS_HOST=listerino_redis
SESSION_DRIVER=redis
CACHE_STORE=redis

# Session/Security
SESSION_DOMAIN=.listerino.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com
```

## Deployment Process

### Prerequisites
- SSH access to root@137.184.113.161
- Local PostgreSQL database
- Node.js and npm installed locally

### Steps

1. **Build Frontend Locally**
   ```bash
   cd frontend
   npm run build
   cd ..
   ```

2. **Export Local Database**
   ```bash
   pg_dump -h localhost -U [username] -d [database] \
     --no-owner --no-privileges --clean --if-exists \
     > database_export.sql
   ```

3. **Deploy Code**
   ```bash
   # Create deployment archive
   tar -czf deploy.tar.gz \
     --exclude='node_modules' \
     --exclude='vendor' \
     --exclude='.git' \
     --exclude='storage/logs/*' \
     --exclude='.env' \
     .

   # Transfer files
   scp deploy.tar.gz root@137.184.113.161:/tmp/
   scp database_export.sql root@137.184.113.161:/tmp/
   ```

4. **On Production Server**
   ```bash
   cd /var/www/listerino
   tar -xzf /tmp/deploy.tar.gz
   
   # Import database
   docker exec -i listerino_db psql -U listerino listerino < /tmp/database_export.sql
   
   # Update Docker app
   docker exec -w /app listerino_app composer install --no-dev
   docker exec -w /app listerino_app php artisan migrate --force
   docker exec -w /app listerino_app php artisan config:cache
   
   # Restart services
   docker-compose restart
   ```

## Maintenance

### View Logs
```bash
# Laravel logs
docker exec listerino_app tail -f /app/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Docker logs
docker logs -f listerino_app
```

### Clear Caches
```bash
docker exec -w /app listerino_app php artisan cache:clear
docker exec -w /app listerino_app php artisan config:cache
docker exec -w /app listerino_app php artisan route:cache
docker exec -w /app listerino_app php artisan view:cache
```

### Database Backup
```bash
docker exec listerino_db pg_dump -U listerino listerino > backup_$(date +%Y%m%d).sql
```

### SSL Certificate Renewal
```bash
certbot renew --nginx
```

## Troubleshooting

### Common Issues

1. **500 Errors on API**
   - Check Docker container is running: `docker ps`
   - Clear config cache: `docker exec -w /app listerino_app php artisan config:clear`
   - Check logs: `docker exec listerino_app tail -f /app/storage/logs/laravel.log`

2. **Database Connection Issues**
   - Verify Docker network: `docker exec listerino_app ping listerino_db`
   - Check credentials in .env match Docker environment
   - Ensure DB_HOST=listerino_db (not localhost)

3. **Session/CSRF Issues**
   - Verify Redis is running: `docker exec listerino_redis redis-cli ping`
   - Check SESSION_DOMAIN includes subdomain (.listerino.com)
   - Clear session data: `docker exec listerino_redis redis-cli FLUSHDB`

### Service Management
```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart listerino_app

# View service status
docker-compose ps

# Rebuild and restart
docker-compose up -d --build
```

## Security Notes
- Never commit .env files to git
- Regularly update Docker images
- Keep SSL certificates current
- Monitor disk space for logs
- Backup database regularly