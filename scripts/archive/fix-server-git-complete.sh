#!/bin/bash

echo "=== Fixing Git Repository on Server ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Backing up current files..."
cp .env /tmp/.env.backup
cp -r frontend/node_modules /tmp/node_modules_backup 2>/dev/null || true

echo "2. Removing broken git repository..."
rm -rf .git

echo "3. Initializing fresh git repository..."
git init
git remote add origin https://github.com/lcreative777/listerino.git

echo "4. Fetching from GitHub..."
git fetch origin main

echo "5. Checking out main branch..."
git checkout -b main origin/main -f

echo "6. Restoring .env file..."
cp /tmp/.env.backup .env

echo "7. Current status:"
git log --oneline -5
echo ""
grep "Last Deployment" README.md || echo "README check"

echo "=== Git repository fixed! ==="
ENDSSH