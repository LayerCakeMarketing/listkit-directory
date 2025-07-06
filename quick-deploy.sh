#!/bin/bash

# Quick deployment script for listerino.com
# This sets up SSH config and initiates deployment

SERVER_IP="137.184.113.161"
SERVER_USER="root"

echo "🚀 Quick Deploy to listerino.com"
echo "================================"

# Check if SSH config exists
if ! grep -q "Host listerino" ~/.ssh/config 2>/dev/null; then
    echo "📝 Adding server to SSH config..."
    cat >> ~/.ssh/config <<EOF

Host listerino
    HostName ${SERVER_IP}
    User ${SERVER_USER}
    IdentityFile ~/.ssh/id_rsa
    StrictHostKeyChecking no
EOF
    echo "✅ SSH config updated"
fi

# Test connection
echo "🔗 Testing connection..."
if ssh -o ConnectTimeout=5 listerino "echo 'Connected successfully'"; then
    echo "✅ Connection successful!"
else
    echo "❌ Connection failed. Please check:"
    echo "   1. Your SSH key is correct"
    echo "   2. The server IP is accessible"
    echo "   3. Your SSH key is added to the server"
    exit 1
fi

# Copy and run server setup
echo "📤 Copying server setup script..."
scp scripts/server-setup.sh listerino:/tmp/

echo "🔧 Running server setup (this will take a few minutes)..."
ssh listerino "bash /tmp/server-setup.sh"

echo ""
echo "✅ Initial setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Update the database password in /var/www/listerino/.env"
echo "2. Copy your Cloudflare credentials to .env"
echo "3. Update your GitHub repository URL in the server"
echo "4. Run: ./deploy-to-listerino.sh"
echo ""
echo "🔑 Don't forget to:"
echo "- Add the deploy key from the server to your GitHub repo"
echo "- Set up GitHub Actions secrets"