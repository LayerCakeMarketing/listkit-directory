#!/bin/bash

echo "=== Setting up Git Deployment ==="

# First, let's check your local git remote
echo "Your local git remote:"
git remote -v

echo -e "\n=== Now setting up on server ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

# Remove the empty git repo and clone fresh
echo "1. Backing up current files..."
mv .git .git.backup

echo "2. Initializing new git repo..."
git init
git remote add origin https://github.com/lcreative777/listerino.git

echo "3. Setting up git config..."
git config user.email "deploy@listerino.com"
git config user.name "Deploy Bot"

echo "4. Fetching from GitHub..."
git fetch origin main

echo "5. Resetting to match GitHub..."
git reset --hard origin/main

echo "6. Setting branch..."
git branch --set-upstream-to=origin/main main
git checkout main

echo "7. Current status:"
git status
git log --oneline -5

echo -e "\n=== Git deployment is now set up! ==="
echo "You can now push to GitHub and pull on the server"
ENDSSH