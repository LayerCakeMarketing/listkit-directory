#!/bin/bash

# Remove hot file if it exists
if [ -f "public/hot" ]; then
    rm -f public/hot
    echo "Removed hot file that was causing CORS issues"
fi

# Add a pre-commit hook to prevent hot file from being committed
if [ -d ".git" ]; then
    cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
# Prevent hot file from being committed
if git diff --cached --name-only | grep -q "public/hot"; then
    echo "Error: public/hot file should not be committed"
    echo "Run: git reset HEAD public/hot"
    exit 1
fi
EOF
    chmod +x .git/hooks/pre-commit
    echo "Added git pre-commit hook to prevent hot file commits"
fi

echo "Done! The hot file has been removed and won't cause CORS issues."