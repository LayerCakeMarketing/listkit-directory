# Listerino Deployment Quick Reference

## 🚀 Fastest Deployment Method

```bash
# Just push to GitHub - that's it!
git add .
git commit -m "feat: your changes"
git push origin main

# GitHub Actions handles everything automatically
```

## 📋 Pre-flight Checklist

- [ ] Frontend builds locally: `cd frontend && npm run build`
- [ ] Tests pass: `./vendor/bin/phpunit`
- [ ] No sensitive data in commits
- [ ] Environment variables updated if needed

## 🛠️ Deployment Methods

### 1. GitHub Actions (Automatic) - RECOMMENDED
```bash
git push origin main
# Monitor: https://github.com/lcreative777/listerino/actions
```

### 2. Deploy Script (Semi-automatic)
```bash
./deploy.sh quick  # Skip prompts
./deploy.sh deploy # With prompts
```

### 3. Manual SSH Deployment
```bash
ssh root@137.184.113.161
cd /var/www/listerino
git pull origin main

# Frontend
cd frontend && npm ci && npm run build && cd ..

# Backend
docker exec listerino_app composer install --no-dev
docker exec listerino_app php artisan migrate --force
docker exec listerino_app php artisan optimize

# Copy frontend to container
docker cp frontend/dist/. listerino_app:/var/www/html/frontend/dist/

# Restart
docker-compose restart
```

## 🔧 Common Commands

### Docker Operations
```bash
# View logs
docker-compose logs -f app

# Access containers
docker exec -it listerino_app bash
docker exec -it listerino_db psql -U listerino

# Restart
docker-compose restart
```

### Cache Management
```bash
docker exec listerino_app php artisan cache:clear
docker exec listerino_app php artisan config:cache
docker exec listerino_app php artisan route:cache
docker exec listerino_app php artisan view:cache
```

### Database
```bash
# Backup
docker exec listerino_db pg_dump -U listerino listerino > backup.sql

# Migrate
docker exec listerino_app php artisan migrate --force
```

## 🚨 Troubleshooting

### Frontend not updating?
```bash
cd frontend && npm run build
docker cp frontend/dist/. listerino_app:/var/www/html/frontend/dist/
```

### Container issues?
```bash
docker-compose logs app
docker-compose down && docker-compose up -d
```

### Permission errors?
```bash
docker exec listerino_app chmod -R 777 storage bootstrap/cache
```

## 📡 Monitoring

- **Application**: https://listerino.com
- **Health Check**: https://listerino.com/api/health
- **Logs**: `docker exec listerino_app tail -f storage/logs/laravel.log`

## 🔄 Rollback

```bash
# Quick rollback
ssh root@137.184.113.161
cd /var/www/listerino
git log --oneline -5
git reset --hard <previous-commit>
docker-compose restart
```

## 📞 Server Access

- **Server**: 137.184.113.161
- **Project**: /var/www/listerino
- **Containers**: listerino_app, listerino_db, listerino_redis

---
Remember: The easiest deployment is just `git push origin main`!