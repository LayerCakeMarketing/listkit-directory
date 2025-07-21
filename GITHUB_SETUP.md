# GitHub Deployment Setup

## Overview
This guide documents how to set up GitHub Actions for automated deployment to DigitalOcean.

## Prerequisites
- GitHub repository: https://github.com/lcreative777/listerino
- DigitalOcean server: 137.184.113.161
- SSH access to the server

## GitHub Secrets Configuration

### Required Secrets
In your GitHub repository, go to **Settings → Secrets and variables → Actions** and add:

1. **SERVER_HOST**
   - Value: `137.184.113.161`
   - Description: Your DigitalOcean server IP address

2. **SERVER_USER**
   - Value: `root`
   - Description: SSH user for deployment

3. **SERVER_SSH_KEY**
   - Value: Your private SSH key content
   - Description: Private key that has access to the server
   - Format:
     ```
     -----BEGIN RSA PRIVATE KEY-----
     [Your key content here]
     -----END RSA PRIVATE KEY-----
     ```

### Setting Up Environment
1. Go to **Settings → Environments**
2. Create a new environment called `Production Server`
3. Add the same secrets to this environment
4. Optional: Add protection rules (require approval, restrict to main branch)

## Server Setup

### SSH Key Configuration
On your DigitalOcean server, ensure the deployment key is authorized:

```bash
# Check authorized keys
cat ~/.ssh/authorized_keys

# The public key corresponding to SERVER_SSH_KEY should be listed
```

### Git Repository Setup
The server should have the repository cloned:

```bash
cd /var/www/listerino
git remote -v
# Should show: origin git@github.com:lcreative777/listerino.git
```

### GitHub SSH Key
The server needs to authenticate with GitHub:

```bash
# Test GitHub connection
ssh -T git@github.com
# Should see: Hi lcreative777! You've successfully authenticated...
```

If not set up:
```bash
# Generate SSH key for GitHub
ssh-keygen -t ed25519 -C "server@listerino.com" -f ~/.ssh/github_deploy

# Add to SSH config
cat >> ~/.ssh/config << EOF
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/github_deploy
  StrictHostKeyChecking no
EOF

# Add the public key to GitHub repository as a Deploy Key
cat ~/.ssh/github_deploy.pub
# Copy this and add to: Repository Settings → Deploy keys
```

## Deployment Workflows

### Main Deployment (deploy.yml)
Triggered on:
- Push to main branch
- Manual workflow dispatch

Features:
- Builds frontend locally on GitHub Actions
- Deploys via SSH
- Updates Docker containers
- Runs migrations
- Clears and rebuilds caches
- Health check

### Test Connection (test-connection.yml)
Manual trigger only - verifies:
- SSH connection works
- Secrets are properly configured
- Server environment is correct

## Usage

### Automatic Deployment
```bash
# From local development
git add .
git commit -m "feat: your changes"
git push listerino main

# GitHub Actions will automatically deploy
```

### Manual Deployment
1. Go to **Actions** tab in GitHub
2. Select "Deploy to Production" workflow
3. Click "Run workflow"
4. Select branch and run

### Testing Connection
1. Go to **Actions** tab
2. Select "Test Server Connection"
3. Run workflow to verify setup

## Monitoring

### GitHub Actions
- Check the **Actions** tab for deployment status
- Each deployment shows detailed logs
- Failed deployments show error messages

### Server Logs
```bash
# Laravel logs
docker exec listerino_app tail -f /app/storage/logs/laravel.log

# Docker logs
docker logs -f listerino_app

# Nginx logs
tail -f /var/log/nginx/error.log
```

## Troubleshooting

### SSH Connection Failed
- Verify SERVER_HOST is correct (137.184.113.161)
- Check SERVER_USER is correct (root)
- Ensure SERVER_SSH_KEY is properly formatted
- Test SSH manually: `ssh root@137.184.113.161`

### Git Pull Failed
- Check deploy key on GitHub repository
- Verify server can access GitHub: `ssh -T git@github.com`
- Check git remote: `git remote -v`

### Docker Commands Failed
- Ensure Docker is running: `docker ps`
- Check container names match: `docker ps --format "{{.Names}}"`
- Verify docker-compose.yml exists

### Build Failed
- Check Node.js version compatibility
- Verify package-lock.json is committed
- Check for build errors in logs

## Security Notes
- Never commit secrets to the repository
- Use GitHub Environments for additional protection
- Rotate SSH keys periodically
- Monitor GitHub Actions logs for suspicious activity