# Listerino/ListKit - macOS Development Setup Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Quick Start](#quick-start)
3. [Detailed Installation Steps](#detailed-installation-steps)
4. [PostgreSQL Setup](#postgresql-setup)
5. [Application Setup](#application-setup)
6. [Running the Application](#running-the-application)
7. [Troubleshooting](#troubleshooting)
8. [Development Commands](#development-commands)

## System Requirements

- **macOS**: 12.0 (Monterey) or later
- **Homebrew**: Package manager for macOS
- **PHP**: 8.3 or later
- **Node.js**: 18 or later
- **PostgreSQL**: 15 or later
- **Composer**: PHP dependency manager
- **Git**: Version control
- **Redis**: Optional for caching (can use array driver for development)

## Quick Start

For experienced developers, run the setup script:
```bash
./setup-macos.sh
```

This will install all dependencies and configure the application automatically.

## Detailed Installation Steps

### 1. Install Homebrew (if not already installed)
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### 2. Install Required Software
```bash
# Install PHP 8.3
brew install php@8.3
brew link php@8.3

# Install PostgreSQL 15
brew install postgresql@15
brew services start postgresql@15

# Install Node.js and npm
brew install node@18

# Install Composer
brew install composer

# Install Redis (optional)
brew install redis
brew services start redis

# Install additional tools
brew install git wget
```

### 3. Verify Installations
```bash
php -v        # Should show PHP 8.3.x
psql --version # Should show psql (PostgreSQL) 15.x
node -v       # Should show v18.x.x
npm -v        # Should show 9.x.x or later
composer -V   # Should show Composer version
```

## PostgreSQL Setup

### 1. Create Database User and Database
```bash
# Connect to PostgreSQL as default user
psql postgres

# In the PostgreSQL prompt, run:
CREATE USER listerino WITH PASSWORD 'localdev123';
CREATE DATABASE listerino_local OWNER listerino;
GRANT ALL PRIVILEGES ON DATABASE listerino_local TO listerino;

# Enable required extensions
\c listerino_local
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

# Exit PostgreSQL
\q
```

### 2. Configure PostgreSQL for Local Development
```bash
# Find your PostgreSQL configuration directory
brew services info postgresql@15

# Edit postgresql.conf (typically at /opt/homebrew/var/postgresql@15/postgresql.conf)
# Ensure these settings:
# listen_addresses = 'localhost'
# port = 5432
```

### 3. Test Database Connection
```bash
psql -h localhost -U listerino -d listerino_local
# Enter password: localdev123
# You should see: listerino_local=>
\q
```

## Application Setup

### 1. Clone the Repository
```bash
# Navigate to your development directory
cd ~/Development  # or wherever you keep your projects

# Clone the repository (if not already cloned)
git clone https://github.com/lcreative777/listerino.git directory-app
cd directory-app
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Frontend Dependencies
```bash
cd frontend
npm install
cd ..
```

### 4. Configure Environment
```bash
# Copy the environment template
cp .env.example .env

# Edit .env file with your favorite editor
nano .env  # or vim, code, etc.
```

Update the following values in `.env`:
```env
# Application Settings
APP_NAME=Listerino
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:5173
SPA_MODE=true

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=listerino_local
DB_USERNAME=listerino
DB_PASSWORD=localdev123

# Session Configuration (for local development)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false

# Cache and Queue (for local development)
CACHE_STORE=array
QUEUE_CONNECTION=database

# Redis (optional - only if you installed Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000,127.0.0.1:5173,127.0.0.1:8000

# Cloudflare Images (optional - for image uploads)
CLOUDFLARE_ACCOUNT_ID=your_account_id_here
CLOUDFLARE_IMAGES_TOKEN=your_token_here
CLOUDFLARE_ACCOUNT_HASH=your_hash_here

# Email (for local development - logs to file)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@listerino.local
MAIL_FROM_NAME="${APP_NAME}"

# Mapbox (optional - for maps)
MAPBOX_PUBLIC_TOKEN=your_public_token_here
MAPBOX_SECRET_TOKEN=your_secret_token_here

# Stripe (optional - for payments)
STRIPE_KEY=your_stripe_key_here
STRIPE_SECRET=your_stripe_secret_here
```

### 5. Configure Frontend Environment
```bash
# Create frontend environment file
cat > frontend/.env << 'EOF'
VITE_APP_NAME="Listerino"
VITE_API_URL="http://localhost:8000"
VITE_APP_URL="http://localhost:5173"
VITE_CLOUDFLARE_ACCOUNT_HASH="nCX0WluV4kb4MYRWgWWi4A"
VITE_MAPBOX_TOKEN="your_mapbox_token_here"
EOF
```

### 6. Generate Application Key
```bash
php artisan key:generate
```

### 7. Run Database Migrations
```bash
# Create session table
php artisan session:table

# Run all migrations
php artisan migrate

# Seed the database with test data (optional)
php artisan db:seed
```

## Running the Application

### Option 1: All-in-One Command (Recommended)
```bash
composer dev
```
This runs all services concurrently:
- Laravel server (http://localhost:8000)
- Vite dev server (http://localhost:5173)
- Queue worker
- Log viewer

### Option 2: Run Services Individually
```bash
# Terminal 1: Laravel Backend
php artisan serve

# Terminal 2: Frontend Dev Server
cd frontend && npm run dev

# Terminal 3: Queue Worker (optional)
php artisan queue:listen

# Terminal 4: Log Viewer (optional)
php artisan pail
```

### Access the Application
- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **Laravel Logs**: Check `storage/logs/laravel.log`

## Troubleshooting

### Common Issues and Solutions

#### 1. PostgreSQL Connection Refused
```bash
# Check if PostgreSQL is running
brew services list

# Start PostgreSQL if not running
brew services start postgresql@15

# Verify connection
psql -h localhost -U listerino -d listerino_local
```

#### 2. PHP Extensions Missing
```bash
# Install required PHP extensions
brew install php@8.3-intl php@8.3-gd php@8.3-zip

# Verify extensions
php -m | grep -E "(pdo|pgsql|mbstring|openssl|tokenizer|xml|ctype|json)"
```

#### 3. Permission Issues
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/logs
```

#### 4. Port Already in Use
```bash
# Check what's using port 8000
lsof -i :8000

# Kill the process or use a different port
php artisan serve --port=8080
```

#### 5. Frontend Build Issues
```bash
# Clear npm cache and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

#### 6. Session Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Development Commands

### Database Management
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seed
php artisan migrate:fresh --seed

# Create a new migration
php artisan make:migration create_example_table

# Run seeders
php artisan db:seed
```

### Code Quality
```bash
# Format PHP code
./vendor/bin/pint

# Run tests
php artisan test

# Run specific test
php artisan test --filter=ExampleTest
```

### Cache Management
```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration (not recommended for development)
php artisan config:cache
php artisan route:cache
```

### Frontend Commands
```bash
cd frontend

# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Run linting
npm run lint
```

### Artisan Commands
```bash
# Create a controller
php artisan make:controller ExampleController

# Create a model with migration
php artisan make:model Example -m

# Create a form request
php artisan make:request ExampleRequest

# View all available routes
php artisan route:list

# Interactive PHP shell
php artisan tinker
```

## Development Workflow

### 1. Daily Startup
```bash
cd ~/Development/directory-app
git pull origin main
composer install
cd frontend && npm install && cd ..
php artisan migrate
composer dev
```

### 2. Before Committing
```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test

# Build frontend to verify
cd frontend && npm run build && cd ..
```

### 3. Creating a Feature Branch
```bash
git checkout main
git pull origin main
git checkout -b feature/your-feature-name
```

## Project Structure

```
directory-app/
├── app/                    # Laravel application code
│   ├── Http/
│   │   ├── Controllers/    # API and web controllers
│   │   └── Middleware/     # Request middleware
│   ├── Models/            # Eloquent models
│   └── Services/          # Business logic services
├── bootstrap/             # Application bootstrap files
├── config/               # Configuration files
├── database/
│   ├── migrations/       # Database migrations
│   ├── seeders/         # Database seeders
│   └── factories/       # Model factories
├── frontend/            # Vue.js frontend application
│   ├── src/
│   │   ├── components/  # Vue components
│   │   ├── views/      # Page components
│   │   ├── router/     # Vue Router configuration
│   │   └── assets/     # CSS, images, etc.
│   └── dist/           # Built frontend files
├── public/             # Public files (index.php, assets)
├── resources/          # Laravel resources (views, lang)
├── routes/            # Route definitions
│   ├── api.php        # API routes
│   └── web.php        # Web routes
├── storage/           # Logs, cache, uploads
├── tests/            # PHPUnit tests
├── .env              # Environment configuration
├── composer.json     # PHP dependencies
└── package.json      # Node dependencies
```

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs/11.x
- **Vue.js Documentation**: https://vuejs.org/guide/
- **PostgreSQL Documentation**: https://www.postgresql.org/docs/15/
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Project Repository**: https://github.com/lcreative777/listerino

## Support

If you encounter issues not covered in this guide:
1. Check the `storage/logs/laravel.log` file for error details
2. Review the `CLAUDE.md` file for project-specific information
3. Contact the development team

---

**Last Updated**: September 2025
**Version**: 1.0.0