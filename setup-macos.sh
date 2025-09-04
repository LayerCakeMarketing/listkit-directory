#!/bin/bash

# Listerino/ListKit - macOS Development Environment Setup Script
# This script automates the setup process for macOS developers

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Header
echo ""
echo "================================================"
echo "  Listerino/ListKit - macOS Development Setup  "
echo "================================================"
echo ""

# Check if running on macOS
if [[ "$OSTYPE" != "darwin"* ]]; then
    print_error "This script is designed for macOS only."
    exit 1
fi

# Step 1: Check and install Homebrew
print_status "Checking for Homebrew..."
if ! command -v brew &> /dev/null; then
    print_warning "Homebrew not found. Installing..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
else
    print_success "Homebrew is installed"
fi

# Step 2: Install required packages
print_status "Installing required packages via Homebrew..."

# Check and install PHP 8.3
if ! brew list php@8.3 &> /dev/null; then
    print_status "Installing PHP 8.3..."
    brew install php@8.3
    brew link php@8.3 --force
else
    print_success "PHP 8.3 is already installed"
fi

# Check and install PostgreSQL 15
if ! brew list postgresql@15 &> /dev/null; then
    print_status "Installing PostgreSQL 15..."
    brew install postgresql@15
    brew services start postgresql@15
else
    print_success "PostgreSQL 15 is already installed"
    brew services start postgresql@15 2>/dev/null || true
fi

# Check and install Node.js
if ! brew list node@18 &> /dev/null; then
    print_status "Installing Node.js 18..."
    brew install node@18
    brew link node@18 --force
else
    print_success "Node.js 18 is already installed"
fi

# Check and install Composer
if ! command -v composer &> /dev/null; then
    print_status "Installing Composer..."
    brew install composer
else
    print_success "Composer is already installed"
fi

# Optional: Install Redis
read -p "Do you want to install Redis? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if ! brew list redis &> /dev/null; then
        print_status "Installing Redis..."
        brew install redis
        brew services start redis
    else
        print_success "Redis is already installed"
        brew services start redis 2>/dev/null || true
    fi
    USE_REDIS=true
else
    USE_REDIS=false
fi

# Step 3: Verify installations
print_status "Verifying installations..."
php -v | head -n 1
node -v
npm -v
composer -V | head -n 1
psql --version

# Step 4: Setup PostgreSQL Database
print_status "Setting up PostgreSQL database..."

# Wait for PostgreSQL to be ready
sleep 3

# Check if database already exists
if psql -U postgres -lqt | cut -d \| -f 1 | grep -qw listerino_local; then
    print_warning "Database 'listerino_local' already exists."
    read -p "Do you want to drop and recreate it? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        psql -U postgres -c "DROP DATABASE IF EXISTS listerino_local;"
        psql -U postgres -c "DROP USER IF EXISTS listerino;"
        psql -U postgres < setup-database.sql
        print_success "Database recreated successfully"
    else
        print_status "Keeping existing database"
    fi
else
    psql -U postgres < setup-database.sql
    print_success "Database created successfully"
fi

# Step 5: Setup Laravel Application
print_status "Setting up Laravel application..."

# Install PHP dependencies
print_status "Installing PHP dependencies..."
composer install

# Setup environment file
if [ ! -f .env ]; then
    print_status "Creating .env file..."
    cp .env.macos.example .env
    
    # Generate application key
    php artisan key:generate
    
    # Update Redis configuration if installed
    if [ "$USE_REDIS" = true ]; then
        sed -i '' 's/# REDIS_HOST/REDIS_HOST/g' .env
        sed -i '' 's/# REDIS_PASSWORD/REDIS_PASSWORD/g' .env
        sed -i '' 's/# REDIS_PORT/REDIS_PORT/g' .env
        sed -i '' 's/CACHE_STORE=array/CACHE_STORE=redis/g' .env
        sed -i '' 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/g' .env
    fi
else
    print_warning ".env file already exists. Skipping environment setup."
fi

# Step 6: Setup Frontend
print_status "Setting up frontend..."

# Create frontend .env if it doesn't exist
if [ ! -f frontend/.env ]; then
    cat > frontend/.env << 'EOF'
VITE_APP_NAME="Listerino"
VITE_API_URL="http://localhost:8000"
VITE_APP_URL="http://localhost:5173"
VITE_CLOUDFLARE_ACCOUNT_HASH="nCX0WluV4kb4MYRWgWWi4A"
EOF
    print_success "Frontend .env created"
else
    print_warning "Frontend .env already exists"
fi

# Install frontend dependencies
print_status "Installing frontend dependencies..."
cd frontend
npm install
cd ..

# Step 7: Run migrations
print_status "Running database migrations..."

# Create session table
php artisan session:table 2>/dev/null || true

# Run migrations
php artisan migrate --force

# Optional: Seed database
read -p "Do you want to seed the database with test data? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed
    print_success "Database seeded with test data"
fi

# Step 8: Set permissions
print_status "Setting correct permissions..."
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/logs

# Step 9: Create helper scripts
print_status "Creating helper scripts..."

# Create start script
cat > start-dev.sh << 'EOF'
#!/bin/bash
echo "Starting Listerino development environment..."
composer dev
EOF
chmod +x start-dev.sh

# Create stop script
cat > stop-dev.sh << 'EOF'
#!/bin/bash
echo "Stopping development services..."
pkill -f "php artisan serve"
pkill -f "npm run dev"
pkill -f "php artisan queue"
echo "Services stopped."
EOF
chmod +x stop-dev.sh

# Create reset script
cat > reset-db.sh << 'EOF'
#!/bin/bash
echo "Resetting database..."
php artisan migrate:fresh --seed
echo "Database reset complete."
EOF
chmod +x reset-db.sh

# Final status
echo ""
echo "================================================"
echo "        Setup Complete! 🎉                     "
echo "================================================"
echo ""
print_success "All dependencies installed and configured!"
echo ""
echo "Next steps:"
echo "1. Review and update .env file with your settings"
echo "2. Add any API keys (Cloudflare, Mapbox, Stripe, etc.)"
echo "3. Start the development environment:"
echo ""
echo "   ${GREEN}composer dev${NC}"
echo ""
echo "   Or use the helper script:"
echo "   ${GREEN}./start-dev.sh${NC}"
echo ""
echo "The application will be available at:"
echo "  Frontend: ${BLUE}http://localhost:5173${NC}"
echo "  Backend:  ${BLUE}http://localhost:8000${NC}"
echo ""
echo "Helper scripts created:"
echo "  ./start-dev.sh  - Start all development services"
echo "  ./stop-dev.sh   - Stop all development services"
echo "  ./reset-db.sh   - Reset database with fresh migrations"
echo ""
echo "For more information, see MACOS_DEVELOPMENT_SETUP.md"
echo ""