# GitHub to Digital Ocean Deployment Setup

## 1. Generate SSH Key for GitHub Actions

On your local machine:
```bash
# Generate a new SSH key specifically for GitHub deployments
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy_key

# Copy the public key to your server
ssh-copy-id -i ~/.ssh/github_deploy_key.pub root@137.184.113.161
```

## 2. Add GitHub Secrets

Go to your GitHub repository:
1. Navigate to Settings → Secrets and variables → Actions
2. Add these secrets:

- **SERVER_HOST**: `137.184.113.161`
- **SERVER_USER**: `root`
- **SERVER_SSH_KEY**: (paste the contents of `~/.ssh/github_deploy_key` - the PRIVATE key)

To get the private key:
```bash
cat ~/.ssh/github_deploy_key
```

## 3. Fix the Deployment Script

The current workflow has issues. Create a simplified version:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Deploy to server
      uses: appleboy/ssh-action@v1.0.0
      with:
        host: ${{ secrets.SERVER_HOST }}
        username: ${{ secrets.SERVER_USER }}
        key: ${{ secrets.SERVER_SSH_KEY }}
        port: 22
        script: |
          cd /var/www/listerino
          
          # Pull latest code
          git pull origin main
          
          # Copy files to Docker container
          docker cp app/. listerino_app:/app/app/
          docker cp routes/. listerino_app:/app/routes/
          docker cp config/. listerino_app:/app/config/
          docker cp database/migrations/. listerino_app:/app/database/migrations/
          
          # Build frontend
          cd frontend
          npm install
          npm run build
          cd ..
          
          # Update dependencies in container
          docker exec listerino_app composer install --no-dev --optimize-autoloader
          
          # Run migrations
          docker exec listerino_app php artisan migrate --force
          
          # Clear caches
          docker exec listerino_app php artisan config:clear
          docker exec listerino_app php artisan cache:clear
          docker exec listerino_app php artisan route:clear
          
          # Restart container
          docker restart listerino_app
```

## 4. Manual Deployment Script (Alternative)

If GitHub Actions doesn't work, use this manual script:

```bash
#!/bin/bash
# deploy.sh

# Pull from GitHub
git pull origin main

# Deploy to server
rsync -avz --exclude 'node_modules' --exclude '.git' --exclude 'vendor' \
  ./ root@137.184.113.161:/var/www/listerino/

# Run commands on server
ssh root@137.184.113.161 << 'EOF'
cd /var/www/listerino

# Copy to container
docker cp app/. listerino_app:/app/app/
docker cp routes/. listerino_app:/app/routes/

# Build frontend
cd frontend
npm install
npm run build

# Clear caches
docker exec listerino_app php artisan config:clear
docker exec listerino_app php artisan cache:clear

# Restart
docker restart listerino_app
EOF
```

## 5. Test the Deployment

1. Make a small change (like updating README.md)
2. Commit and push:
   ```bash
   git add .
   git commit -m "Test deployment"
   git push origin main
   ```
3. Check GitHub Actions tab to see if it runs

## 6. Debugging

If it fails, check:
- GitHub Actions logs
- SSH key permissions (must be 600)
- Server firewall allows GitHub IPs
- Git repository on server is set up correctly

## Current Issues to Fix

1. The deployment script references wrong paths (`/var/www/html/frontend/dist/`)
2. Frontend files need to go to the right location
3. The health check endpoint might not exist

## Quick Fix for Now

Run this on your server to set up git properly:
```bash
cd /var/www/listerino
git remote -v  # Check current remotes
git remote set-url origin https://github.com/yourusername/yourrepo.git
# OR use SSH:
git remote set-url origin git@github.com:yourusername/yourrepo.git
```