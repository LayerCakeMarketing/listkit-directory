#!/bin/bash

# Listerino Deployment Script
# This script handles deployment from local development to production

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PRODUCTION_HOST="137.184.113.161"
PRODUCTION_USER="root"
PRODUCTION_DIR="/var/www/listerino"
GITHUB_REPO="https://github.com/lcreative777/listerino.git"
BRANCH="main"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if required tools are installed
check_requirements() {
    print_status "Checking requirements..."
    
    command -v git >/dev/null 2>&1 || { print_error "git is required but not installed."; exit 1; }
    command -v docker >/dev/null 2>&1 || { print_error "docker is required but not installed."; exit 1; }
    command -v ssh >/dev/null 2>&1 || { print_error "ssh is required but not installed."; exit 1; }
    
    print_status "All requirements met!"
}

# Build frontend assets
build_frontend() {
    print_status "Building frontend assets..."
    
    cd frontend
    npm install
    npm run build
    cd ..
    
    print_status "Frontend build complete!"
}

# Run tests
run_tests() {
    print_status "Running tests..."
    
    # Run PHP tests
    ./vendor/bin/phpunit || print_warning "Some tests failed, continuing..."
    
    # Run frontend tests if they exist
    if [ -f "frontend/package.json" ] && grep -q "test" "frontend/package.json"; then
        cd frontend
        npm test || print_warning "Some frontend tests failed, continuing..."
        cd ..
    fi
    
    print_status "Tests complete!"
}

# Commit and push to GitHub
push_to_github() {
    print_status "Pushing to GitHub..."
    
    # Check if there are changes
    if [[ -n $(git status -s) ]]; then
        print_status "Changes detected, committing..."
        git add .
        git commit -m "Deployment: $(date +'%Y-%m-%d %H:%M:%S')"
    else
        print_status "No changes to commit"
    fi
    
    # Push to GitHub
    git push origin $BRANCH
    
    print_status "Pushed to GitHub!"
}

# Deploy to production
deploy_to_production() {
    print_status "Deploying to production..."
    
    # SSH to production and pull latest changes
    ssh $PRODUCTION_USER@$PRODUCTION_HOST << 'ENDSSH'
        set -e
        cd /var/www/listerino
        
        echo "Pulling latest changes..."
        git pull origin main
        
        echo "Installing backend dependencies..."
        docker exec listerino_app composer install --no-dev --optimize-autoloader
        
        echo "Running migrations..."
        docker exec listerino_app php artisan migrate --force
        
        echo "Clearing caches..."
        docker exec listerino_app php artisan cache:clear
        docker exec listerino_app php artisan config:cache
        docker exec listerino_app php artisan route:cache
        docker exec listerino_app php artisan view:cache
        
        echo "Copying frontend build..."
        docker cp frontend/dist/. listerino_app:/var/www/html/public/build/
        
        echo "Restarting services..."
        docker-compose restart
        
        echo "Deployment complete!"
ENDSSH
    
    print_status "Production deployment complete!"
}

# Main deployment function
deploy() {
    print_status "Starting deployment process..."
    
    # 1. Check requirements
    check_requirements
    
    # 2. Build frontend
    build_frontend
    
    # 3. Run tests (optional)
    read -p "Run tests before deployment? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        run_tests
    fi
    
    # 4. Push to GitHub
    push_to_github
    
    # 5. Deploy to production
    read -p "Deploy to production? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        deploy_to_production
    else
        print_warning "Skipping production deployment"
    fi
    
    print_status "Deployment process complete!"
}

# Quick deploy function (skips tests and prompts)
quick_deploy() {
    print_status "Starting quick deployment..."
    
    check_requirements
    build_frontend
    push_to_github
    deploy_to_production
    
    print_status "Quick deployment complete!"
}

# Show usage
usage() {
    echo "Usage: $0 [option]"
    echo "Options:"
    echo "  deploy       - Full deployment with prompts"
    echo "  quick        - Quick deployment (no tests or prompts)"
    echo "  build        - Build frontend only"
    echo "  test         - Run tests only"
    echo "  help         - Show this help message"
}

# Parse command line arguments
case "$1" in
    deploy)
        deploy
        ;;
    quick)
        quick_deploy
        ;;
    build)
        build_frontend
        ;;
    test)
        run_tests
        ;;
    help)
        usage
        ;;
    *)
        usage
        exit 1
        ;;
esac