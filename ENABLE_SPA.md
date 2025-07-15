# How to Enable Full SPA Mode

The SPA infrastructure is fully set up, but Laravel is still using Inertia routes. To fully switch to SPA mode:

## Option 1: Manual Route File Swap (Recommended for Testing)

1. **Backup current routes:**
   ```bash
   cp routes/web.php routes/web-inertia.php
   ```

2. **Replace web.php with SPA routes:**
   ```bash
   cp routes/web-spa.php routes/web.php
   ```

3. **Clear caches and restart:**
   ```bash
   php artisan optimize:clear
   php artisan serve
   ```

4. **To revert back to Inertia:**
   ```bash
   cp routes/web-inertia.php routes/web.php
   php artisan optimize:clear
   ```

## Option 2: Environment-Based Loading (Production Ready)

Since bootstrap/app.php can't access config/env during bootstrap, modify it directly:

1. Edit `bootstrap/app.php`:
   ```php
   ->withRouting(
       web: __DIR__.'/../routes/web-spa.php',  // For SPA
       // web: __DIR__.'/../routes/web.php',   // For Inertia
       api: __DIR__.'/../routes/api.php',
       commands: __DIR__.'/../routes/console.php',
       health: '/up',
   )
   ```

2. Clear caches and restart

## Testing the SPA

Once enabled, visit http://localhost:8000 and you should see:
- The Vue Router app loads
- API calls are made to fetch data
- Routes like `/location/california` are handled by Vue Router
- Only 1 Laravel route exists (the catch-all)

## Current Architecture Benefits

- **Before**: 65+ Laravel routes, slow resolution, memory bloat
- **After**: 1 catch-all route, Vue Router handles everything
- **API**: Full REST API at `/api/*` endpoints
- **Scalability**: Supports millions of dynamic URLs