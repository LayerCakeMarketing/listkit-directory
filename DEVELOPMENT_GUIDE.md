# Development Guide

This guide provides detailed instructions for starting the development servers and troubleshooting common issues.

## Quick Start

### Recommended Method (All Services)
```bash
composer dev
```
This starts all services concurrently:
- Laravel API server (port 8000)
- Vite frontend server (port 5173)
- Queue worker
- Log viewer

### Individual Services (If Needed)
```bash
# Start Laravel API server
php artisan serve

# Start Vite frontend (in a separate terminal)
cd frontend && npm run dev

# Start queue worker (in a separate terminal)
php artisan queue:listen

# Start log viewer (in a separate terminal)
php artisan pail
```

## Accessing the Application

- **Frontend (Vue SPA)**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation (if enabled)

## Common Issues & Solutions

### Issue 1: CORS Errors in Browser Console
**Symptoms:**
- "Cross-Origin Request Blocked" errors
- "CORS request did not succeed"
- API calls failing with network errors

**Cause:** Laravel server not running or crashed

**Solution:**
1. Check if Laravel server is running:
   ```bash
   curl -I http://localhost:8000/api/stats
   ```
   
2. If not responding, restart Laravel server:
   ```bash
   # Kill any existing Laravel server
   ps aux | grep "php artisan serve" | grep -v grep | awk '{print $2}' | xargs kill -9 2>/dev/null
   
   # Start Laravel server in background
   nohup php artisan serve > /dev/null 2>&1 &
   
   # Or use composer dev to start all services
   composer dev
   ```

3. Verify CORS is working:
   ```bash
   curl -I http://localhost:8000/api/stats -H "Origin: http://localhost:5173"
   # Should see: Access-Control-Allow-Origin: http://localhost:5173
   ```

### Issue 2: "composer dev" Stops Unexpectedly
**Solution:**
- Run services individually in separate terminal windows
- Check for port conflicts:
  ```bash
  lsof -i :8000  # Check Laravel port
  lsof -i :5173  # Check Vite port
  ```

### Issue 3: Database Connection Errors
**Solution:**
1. Verify PostgreSQL is running:
   ```bash
   pg_isready
   ```
   
2. Check database credentials in `.env`:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=illum_local
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. Test connection:
   ```bash
   php artisan db:show
   ```

### Issue 4: Frontend Not Updating
**Solution:**
1. Clear Vite cache:
   ```bash
   cd frontend
   rm -rf node_modules/.vite
   npm run dev
   ```

2. Hard refresh browser: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows/Linux)

### Issue 5: Session/Authentication Issues
**Symptoms:**
- Can't log in
- Session not persisting
- 419 CSRF token mismatch

**Solution:**
1. Clear all caches:
   ```bash
   php artisan optimize:clear
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. Regenerate session tables:
   ```bash
   php artisan session:table
   php artisan migrate
   ```

3. Check `.env` settings:
   ```
   SESSION_DRIVER=database
   SESSION_DOMAIN=localhost
   SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
   ```

## Environment Setup Checklist

### Prerequisites
- [ ] PHP 8.3+ installed
- [ ] Node.js 18+ installed
- [ ] PostgreSQL 15+ installed and running
- [ ] Composer installed globally
- [ ] Git configured

### Initial Setup
1. Clone repository
2. Copy `.env.example` to `.env`
3. Configure database credentials in `.env`
4. Install dependencies:
   ```bash
   composer install
   cd frontend && npm install && cd ..
   ```
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Run migrations:
   ```bash
   php artisan migrate --seed
   ```

## Development Workflow

### Before Starting Development
1. Pull latest changes: `git pull origin main`
2. Update dependencies if needed:
   ```bash
   composer install
   cd frontend && npm install && cd ..
   ```
3. Run migrations: `php artisan migrate`
4. Clear caches: `php artisan optimize:clear`

### During Development
1. Keep `composer dev` running
2. Monitor log viewer for errors
3. Use Laravel Debugbar (if installed) for query debugging
4. Check browser console for frontend errors

### After Making Changes
1. Run tests: `php artisan test`
2. Format code: `./vendor/bin/pint`
3. Build frontend for testing: `cd frontend && npm run build`

## Useful Commands

### Database
```bash
# Create new migration
php artisan make:migration create_table_name

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seed
php artisan migrate:fresh --seed

# Database console
php artisan db
```

### Debugging
```bash
# Interactive console
php artisan tinker

# List routes
php artisan route:list

# Show specific route
php artisan route:list --name=api.

# Clear all caches
php artisan optimize:clear
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=ExampleTest

# Run with coverage
php artisan test --coverage
```

## Port Configuration

Default ports used by the application:
- 8000: Laravel API server
- 5173: Vite development server
- 5432: PostgreSQL database
- 6379: Redis (if used)

To change ports, update:
- Laravel: `php artisan serve --port=8001`
- Vite: Edit `frontend/vite.config.js`

## Troubleshooting Tools

### Check Running Services
```bash
# Check all related processes
ps aux | grep -E "php|node|vite|postgres" | grep -v grep

# Check port usage
lsof -i :8000,5173,5432

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Network Debugging
```bash
# Test API endpoint
curl http://localhost:8000/api/stats

# Test with authentication
curl http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  --cookie "your_session_cookie"
```

## VSCode Extensions (Recommended)

- PHP Intelephense
- Vue - Official
- Tailwind CSS IntelliSense
- Laravel Blade Snippets
- ESLint
- Prettier

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Laravel Sanctum SPA Authentication](https://laravel.com/docs/11.x/sanctum#spa-authentication)

---

Last Updated: July 29, 2025