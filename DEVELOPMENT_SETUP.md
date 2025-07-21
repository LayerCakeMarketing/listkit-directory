# Development Environment Setup

## Overview
This guide covers setting up and running the Listerino application in your local development environment.

## Prerequisites
- macOS, Linux, or Windows with WSL2
- PostgreSQL 15+ (local or Docker)
- PHP 8.3+
- Composer 2.x
- Node.js 18+ and npm
- Git

## Initial Setup

### 1. Clone Repository
```bash
git clone [repository-url]
cd directory-app
```

### 2. Environment Configuration
```bash
cp .env.example .env
```

Update `.env` with your local settings:
```env
APP_NAME="Directory App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Local PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=illum_local
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Local development settings
SESSION_DRIVER=database
CACHE_STORE=array
QUEUE_CONNECTION=database
```

### 3. Install Dependencies
```bash
# PHP dependencies
composer install

# Node dependencies
cd frontend
npm install
cd ..
```

### 4. Database Setup
```bash
# Create database
createdb illum_local

# Run migrations
php artisan migrate

# Seed sample data (optional)
php artisan db:seed
```

### 5. Application Setup
```bash
# Generate application key
php artisan key:generate

# Create storage link
php artisan storage:link
```

## Running Development Servers

### Option 1: All-in-One Command (Recommended)
```bash
composer dev
```
This starts:
- Laravel development server (port 8000)
- Vite dev server for frontend (port 5173)
- Queue worker
- Log viewer (Pail)

### Option 2: Individual Services
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Frontend dev server
cd frontend
npm run dev

# Terminal 3: Queue worker (if using queues)
php artisan queue:listen

# Terminal 4: Log viewer (optional)
php artisan pail
```

## Development Workflow

### Frontend Development
- Frontend files: `frontend/src/`
- Hot reload enabled with Vite
- Access at: http://localhost:8000 (proxied through Laravel)

### Backend Development
- API routes: `routes/api.php`
- Controllers: `app/Http/Controllers/`
- Models: `app/Models/`
- Automatic reload on PHP file changes

### Database Changes
```bash
# Create new migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback if needed
php artisan migrate:rollback
```

## Testing

### Run Tests
```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/ExampleTest.php

# With coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

## Common Tasks

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Reset Database
```bash
php artisan migrate:fresh --seed
```

### Generate IDE Helper Files
```bash
php artisan ide-helper:generate
php artisan ide-helper:models
```

## Troubleshooting

### Port Already in Use
```bash
# Kill process on port 8000
lsof -ti:8000 | xargs kill -9

# Use different port
php artisan serve --port=8001
```

### NPM Issues
```bash
# Clear cache and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm install
```

### Database Connection Issues
- Verify PostgreSQL is running: `pg_ctl status`
- Check credentials in `.env`
- Ensure database exists: `psql -l`

### Permission Issues
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

## VS Code Setup

### Recommended Extensions
- Laravel Extension Pack
- Vue - Official
- Tailwind CSS IntelliSense
- PHP Intelephense
- ESLint
- Prettier

### Settings (.vscode/settings.json)
```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "[php]": {
    "editor.defaultFormatter": "open-southeners.laravel-pint"
  },
  "tailwindCSS.includeLanguages": {
    "vue": "html"
  }
}
```

## Docker Development (Optional)

If you prefer Docker for local development:

```bash
# Start services
docker-compose -f docker-compose.dev.yml up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Run migrations
docker-compose exec app php artisan migrate
```

## Next Steps
- Review `CLAUDE.md` for coding guidelines
- Check `routes/api.php` for available endpoints
- Explore `frontend/src/` for Vue components
- See `PRODUCTION_SETUP.md` for deployment info