# GitHub Deployment Monitoring Guide

## 1. Check GitHub Actions

Go to: https://github.com/lcreative777/listerino/actions

You should see:
- A new workflow run for "Deploy to Production" 
- It should be triggered by your push to main branch
- Status: 🟡 In progress (or ✅ Completed)

## 2. Monitor the Deployment

Click on the workflow run to see:
- Real-time logs of the deployment
- Each step as it executes:
  1. SSH connection to server
  2. Git pull from repository
  3. Copying files to Docker containers
  4. Building frontend
  5. Running migrations
  6. Clearing caches
  7. Restarting containers

## 3. Common Issues and Fixes

### If the workflow fails:

**SSH Key Error**:
- Check that SERVER_SSH_KEY secret is properly formatted
- Make sure it includes the full private key with headers:
  ```
  -----BEGIN RSA PRIVATE KEY-----
  [key content]
  -----END RSA PRIVATE KEY-----
  ```

**Permission Denied**:
- The SSH key might not be authorized on the server
- Run: `ssh-copy-id -i ~/.ssh/id_rsa.pub root@137.184.113.161`

**Git Pull Failed**:
- The server repository might be out of sync
- SSH to server and run: `cd /var/www/listerino && git fetch origin && git reset --hard origin/main`

## 4. Verify Deployment Success

After deployment completes:

1. **Check the README on server**:
   ```bash
   ssh root@137.184.113.161 "cat /var/www/listerino/README.md | head -10"
   ```

2. **Check if HomeController was updated**:
   ```bash
   ssh root@137.184.113.161 "docker exec listerino_app grep -A5 'visible()' /app/app/Http/Controllers/Api/HomeController.php"
   ```

3. **Test the website**:
   - Visit https://listerino.com
   - Log in and check if the home feed works
   - No more "status column" errors

## 5. Manual Deployment (if GitHub Actions fails)

```bash
# SSH to server
ssh root@137.184.113.161

# Pull latest changes
cd /var/www/listerino
git pull origin main

# Deploy to containers
docker cp app/. listerino_app:/app/app/
docker exec listerino_app php artisan config:clear
docker restart listerino_app
```

## 6. Check Deployment Logs

On the server:
```bash
# Check container logs
docker logs --tail 50 listerino_app

# Check Laravel logs
docker exec listerino_app tail -50 /app/storage/logs/laravel.log
```