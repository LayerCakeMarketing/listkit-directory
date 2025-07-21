# Deployment Guide

This guide covers all deployment scenarios for pushing updates to production.

## Table of Contents
1. [Quick Deployment (Code Only)](#quick-deployment-code-only)
2. [Deployment with Database Changes](#deployment-with-database-changes)
3. [Emergency Rollback](#emergency-rollback)
4. [Deployment Checklist](#deployment-checklist)
5. [Common Issues](#common-issues)

## Prerequisites
- Git configured with `listerino` remote
- GitHub Actions properly configured
- SSH access to production server (for manual deployments)

## Quick Deployment (Code Only)

### For Simple Code Changes (Frontend/Backend)

1. **Make your changes locally**
   ```bash
   # Edit files as needed
   # Test thoroughly in local development
   ```

2. **Commit and push to GitHub**
   ```bash
   git add .
   git commit -m "feat: your descriptive message"
   git push listerino main
   ```

3. **GitHub Actions automatically**:
   - Builds frontend
   - Deploys to production
   - Clears caches
   - Restarts services

4. **Monitor deployment**:
   - Check https://github.com/lcreative777/listerino/actions
   - Verify at https://listerino.com

### Manual Deployment (if needed)
```bash
# Build frontend locally
cd frontend
npm run build
cd ..

# Deploy using script
./deploy-simple.sh
```

## Deployment with Database Changes

### When You Have Migrations

1. **Create migration locally**
   ```bash
   php artisan make:migration add_feature_to_table
   # Edit the migration file
   ```

2. **Test migration locally**
   ```bash
   php artisan migrate
   # Test the changes
   php artisan migrate:rollback  # Test rollback
   php artisan migrate           # Re-apply
   ```

3. **Commit all changes**
   ```bash
   git add .
   git commit -m "feat: add feature with migration"
   ```

4. **Deploy with migration**
   ```bash
   # Option 1: Push and let GitHub Actions handle it
   git push listerino main
   # Migrations run automatically

   # Option 2: Manual deployment with control
   ssh root@137.184.113.161
   cd /var/www/listerino
   git pull origin main
   
   # Backup database first!
   docker exec listerino_db pg_dump -U listerino listerino > /backups/before-migration-$(date +%Y%m%d-%H%M%S).sql
   
   # Run migration
   docker exec -w /app listerino_app php artisan migrate --force
   ```

### When You Have Database Structure Changes

1. **Always backup first**
   ```bash
   ssh root@137.184.113.161
   docker exec listerino_db pg_dump -U listerino listerino > /backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql
   ```

2. **For complex changes, use maintenance mode**
   ```bash
   # Enable maintenance mode
   docker exec -w /app listerino_app php artisan down --message="Upgrading Database" --retry=60

   # Deploy and migrate
   git pull origin main
   docker exec -w /app listerino_app php artisan migrate --force

   # Disable maintenance mode
   docker exec -w /app listerino_app php artisan up
   ```

3. **For adding indexes or heavy operations**
   ```bash
   # Run migrations with longer timeout
   docker exec -w /app listerino_app php artisan migrate --force --timeout=0
   ```

## Emergency Rollback

### Code Rollback

1. **Quick revert via Git**
   ```bash
   ssh root@137.184.113.161
   cd /var/www/listerino
   
   # View recent commits
   git log --oneline -10
   
   # Revert to previous commit
   git reset --hard <previous-commit-hash>
   
   # Update Docker containers
   docker-compose restart
   ```

2. **Clear all caches**
   ```bash
   docker exec -w /app listerino_app php artisan optimize:clear
   ```

### Database Rollback

1. **If migration has rollback**
   ```bash
   docker exec -w /app listerino_app php artisan migrate:rollback --step=1
   ```

2. **From backup**
   ```bash
   # Stop the app
   docker exec -w /app listerino_app php artisan down
   
   # Restore database
   docker exec -i listerino_db psql -U listerino listerino < /backups/your-backup.sql
   
   # Start the app
   docker exec -w /app listerino_app php artisan up
   ```

## Deployment Checklist

### Before Deployment
- [ ] All tests pass locally
- [ ] Frontend builds without errors
- [ ] Migrations tested with rollback
- [ ] No sensitive data in commits
- [ ] Proper commit message

### During Deployment
- [ ] Monitor GitHub Actions
- [ ] Check for errors in deployment logs
- [ ] Database backed up (for migrations)

### After Deployment
- [ ] Site loads correctly
- [ ] New features work as expected
- [ ] No errors in Laravel logs
- [ ] API endpoints respond correctly
- [ ] Check performance metrics

## Common Issues

### 1. Frontend Not Updating
```bash
# Force clear browser cache or check in incognito
# On server:
docker exec -w /app listerino_app php artisan view:clear
docker cp frontend/dist/. listerino_app:/app/frontend/dist/
```

### 2. Route Not Found (404)
```bash
# Clear route cache
docker exec -w /app listerino_app php artisan route:clear
docker exec -w /app listerino_app php artisan route:cache
```

### 3. Migration Failed
```bash
# Check the error
docker exec -w /app listerino_app php artisan migrate:status

# Fix and retry
docker exec -w /app listerino_app php artisan migrate --force
```

### 4. GitHub Actions Failed
```bash
# Check the logs in Actions tab
# Common fixes:
ssh root@137.184.113.161
cd /var/www/listerino
git pull origin main  # Ensure server can pull
```

### 5. 502 Bad Gateway Error (Containers Not Running)
```bash
# Check which containers are running
docker ps

# If listerino_app is not running, start it
docker start <container-id>

# Or use production docker-compose
cd /var/www/listerino
docker-compose -f docker-compose.production.yml up -d

# If you get ContainerConfig errors
# 1. Try starting individual containers
docker start listerino_app
docker start listerino_db
docker start listerino_redis

# 2. Or rebuild from production config
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml up -d

# 3. Ensure production config is default
ln -sf docker-compose.production.yml docker-compose.yml
```

### 6. Database Connection Issues
```bash
# Verify database is running
docker ps | grep listerino_db

# Test database connection
docker exec listerino_db psql -U listerino -c "SELECT 1"

# Check environment variables
docker exec listerino_app env | grep DB_

# Ensure using service names (not localhost)
DB_HOST=listerino_db  # Correct
DB_HOST=localhost     # Wrong in Docker
```

## Deployment Commands Reference

### Local Commands
```bash
# Build frontend
npm run build --prefix frontend

# Test everything
php artisan test
npm test --prefix frontend

# Create migration
php artisan make:migration description_of_change

# Create model with migration
php artisan make:model ModelName -m
```

### Production Commands (via SSH)
```bash
# View logs
docker exec listerino_app tail -f /app/storage/logs/laravel.log

# Clear everything
docker exec -w /app listerino_app php artisan optimize:clear

# Run tinker
docker exec -it -w /app listerino_app php artisan tinker

# Database backup
docker exec listerino_db pg_dump -U listerino listerino > backup.sql

# Check Docker status
docker-compose ps
docker stats
```

### GitHub Actions Commands
```bash
# Trigger manual deployment
# Go to Actions > Deploy to Production > Run workflow

# Check deployment status
# Go to Actions tab to see running/completed workflows
```

## Best Practices

1. **Always test locally first**
2. **Use descriptive commit messages**
3. **Deploy during low-traffic times for major changes**
4. **Keep migrations small and focused**
5. **Document any manual steps needed**
6. **Monitor after deployment**

## Support

If deployment fails:
1. Check GitHub Actions logs
2. SSH to server and check Laravel logs
3. Verify Docker containers are running
4. Check database connectivity
5. Review this guide's troubleshooting section