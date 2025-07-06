#!/bin/bash

# GitHub Repository Setup Helper
# This script helps configure your GitHub repository for deployment

echo "🐙 GitHub Repository Setup"
echo "========================="

# Get repository information
REPO_URL=$(git config --get remote.origin.url)
if [ -z "$REPO_URL" ]; then
    echo "❌ No Git repository found. Please initialize Git first."
    exit 1
fi

echo "📦 Repository: $REPO_URL"
echo ""

# Instructions for GitHub setup
cat <<EOF
📋 Manual steps required in GitHub:

1. **Add Deploy Key** (Settings → Deploy keys):
   - Title: "Listerino Production Server"
   - Key: Copy from server: ssh root@137.184.113.161 'cat /root/.ssh/listerino_deploy.pub'
   - ✅ Allow write access

2. **Add Secrets** (Settings → Secrets → Actions):
   Required:
   - SERVER_HOST: 137.184.113.161
   - SERVER_USER: root
   - SERVER_SSH_KEY: Your private SSH key (cat ~/.ssh/id_rsa)
   
   Optional:
   - SLACK_WEBHOOK: Your Slack webhook URL for notifications

3. **Enable Actions** (Settings → Actions → General):
   - Actions permissions: Allow all actions

4. **Update server repository URL** (if needed):
   ssh root@137.184.113.161 "cd /var/www/listerino && git remote set-url origin git@github.com:LayerCakeMarketing/listkit-directory.git"

5. **Test deployment**:
   - Push to main branch, or
   - Go to Actions → Deploy to Production → Run workflow

EOF

echo ""
echo "🔄 Checking current branch..."
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "⚠️  You're on branch '$CURRENT_BRANCH'. Deployments run from 'main' branch."
    echo "   To switch: git checkout main"
fi

echo ""
echo "✅ After completing these steps, push to main to trigger deployment!"