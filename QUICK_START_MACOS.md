# Listerino/ListKit - macOS Quick Start Guide

## 🚀 Fastest Setup (5 minutes)

### Prerequisites Check
```bash
# Check if you have required tools
php -v          # Need 8.3+
psql --version  # Need 15+
node -v         # Need 18+
composer -V     # Need Composer 2
```

### Option A: Automated Setup (Recommended)
```bash
# Run the automated setup script
./setup-macos.sh
```

This script will:
- ✅ Install all dependencies via Homebrew
- ✅ Set up PostgreSQL database
- ✅ Configure environment files
- ✅ Install PHP and NPM packages
- ✅ Run database migrations
- ✅ Create helper scripts

### Option B: Manual Quick Setup

#### 1. Install Dependencies
```bash
# If you don't have these installed
brew install php@8.3 postgresql@15 node@18 composer
brew services start postgresql@15
```

#### 2. Database Setup
```bash
# Run the database setup script
psql -U postgres < setup-database.sql
```

#### 3. Application Setup
```bash
# Install dependencies
composer install
cd frontend && npm install && cd ..

# Copy environment files
cp .env.macos.example .env
php artisan key:generate

# Create frontend env
echo 'VITE_APP_NAME="Listerino"
VITE_API_URL="http://localhost:8000"
VITE_APP_URL="http://localhost:5173"
VITE_CLOUDFLARE_ACCOUNT_HASH="nCX0WluV4kb4MYRWgWWi4A"' > frontend/.env

# Run migrations
php artisan migrate

# Set permissions
chmod -R 775 storage bootstrap/cache
```

## 🎯 Running the Application

### Start Everything at Once
```bash
composer dev
```

This runs:
- Laravel API: http://localhost:8000
- Vue Frontend: http://localhost:5173
- Queue Worker
- Log Viewer

### Access Points
- **Frontend**: http://localhost:5173
- **API**: http://localhost:8000/api
- **Database**: `psql -h localhost -U listerino -d listerino_local`

## 📝 Test Credentials

After seeding the database:
```
Email: test@example.com
Password: password
```

## 🛠 Useful Commands

```bash
# Database
php artisan migrate:fresh --seed  # Reset database with test data
php artisan tinker                 # Interactive shell

# Development
composer dev                        # Start all services
./vendor/bin/pint                  # Format code
php artisan test                   # Run tests

# Frontend
cd frontend && npm run build       # Build for production
```

## ⚠️ Common Issues

### PostgreSQL Not Running?
```bash
brew services restart postgresql@15
```

### Port 8000 Already in Use?
```bash
lsof -i :8000  # Find what's using it
php artisan serve --port=8080  # Use different port
```

### Permission Errors?
```bash
chmod -R 775 storage bootstrap/cache
```

## 📚 Next Steps

1. Review `.env` file and add any API keys
2. Check `MACOS_DEVELOPMENT_SETUP.md` for detailed documentation
3. Read `CLAUDE.md` for project-specific information
4. Start developing!

## 🔗 Quick Links

- [Full Setup Guide](MACOS_DEVELOPMENT_SETUP.md)
- [Project Documentation](CLAUDE.md)
- [API Documentation](SERVICES_AND_API_DOCUMENTATION.md)
- [Deployment Guide](DEPLOYMENT.md)