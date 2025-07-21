#!/bin/bash

echo "=== Initializing Git on Server ==="

ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino

echo "1. Current git status:"
git status --short | wc -l
echo "Files with changes: $(git status --short | wc -l)"

echo -e "\n2. Stashing local changes..."
git stash push -m "Local changes before GitHub sync"

echo -e "\n3. Setting up main branch..."
git checkout main 2>/dev/null || git checkout -b main

echo -e "\n4. Setting upstream..."
git branch --set-upstream-to=origin/main main

echo -e "\n5. Pulling from GitHub..."
git pull origin main --no-edit

echo -e "\n6. Verifying README update..."
grep "Last Deployment" README.md || echo "README not updated yet"

echo -e "\n7. Current commit:"
git log --oneline -1

echo "=== Git setup complete! ==="
ENDSSH