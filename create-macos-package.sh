#!/bin/bash

# Script to create a complete macOS development package for Listerino/ListKit

echo "Creating macOS Development Package for Listerino/ListKit..."

# Create package directory
PACKAGE_DIR="listerino-macos-dev-package"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="${PACKAGE_DIR}_${TIMESTAMP}"

# Clean up any existing package directory
rm -rf $PACKAGE_DIR
rm -f ${PACKAGE_DIR}*.tar.gz

# Create fresh package directory
mkdir -p $PACKAGE_DIR

# Copy essential files for macOS setup
echo "Copying setup files..."
cp -r . $PACKAGE_DIR/ 2>/dev/null || true

# Create a README for the package
cat > $PACKAGE_DIR/README_PACKAGE.md << 'EOF'
# Listerino/ListKit - macOS Development Package

This package contains everything you need to set up Listerino/ListKit on macOS.

## 📦 Package Contents

- Complete Laravel/Vue.js application code
- PostgreSQL database setup scripts
- Automated macOS setup script
- Environment configuration templates
- Documentation and guides

## 🚀 Quick Start

1. Extract this package to your development directory
2. Navigate to the extracted directory
3. Run the setup script:
   ```bash
   ./setup-macos.sh
   ```

4. Start the development environment:
   ```bash
   composer dev
   ```

## 📚 Documentation

- `QUICK_START_MACOS.md` - Quick start guide
- `MACOS_DEVELOPMENT_SETUP.md` - Comprehensive setup guide
- `CLAUDE.md` - Project documentation
- `SERVICES_AND_API_DOCUMENTATION.md` - API documentation

## 🔧 Manual Setup

If you prefer manual setup, follow the instructions in:
`MACOS_DEVELOPMENT_SETUP.md`

## 📝 Important Files

- `setup-macos.sh` - Automated setup script
- `setup-database.sql` - PostgreSQL database creation
- `.env.macos.example` - Environment configuration template
- `composer.json` - PHP dependencies
- `frontend/package.json` - Node.js dependencies

## 🌐 Access Points

After setup, the application will be available at:
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000

## 💾 System Requirements

- macOS 12.0 (Monterey) or later
- 8GB RAM minimum (16GB recommended)
- 10GB free disk space
- Internet connection for package downloads

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section in `MACOS_DEVELOPMENT_SETUP.md`
2. Review the logs in `storage/logs/laravel.log`
3. Consult the project documentation in `CLAUDE.md`

---
Package created: $(date)
EOF

# Create archive
echo "Creating compressed archive..."
tar -czf "${PACKAGE_NAME}.tar.gz" $PACKAGE_DIR/

# Calculate package size
PACKAGE_SIZE=$(du -sh "${PACKAGE_NAME}.tar.gz" | cut -f1)

# Clean up temporary directory
rm -rf $PACKAGE_DIR

echo ""
echo "✅ Package created successfully!"
echo ""
echo "📦 Package Details:"
echo "   Name: ${PACKAGE_NAME}.tar.gz"
echo "   Size: $PACKAGE_SIZE"
echo ""
echo "📋 To use this package on a new Mac:"
echo "   1. Copy ${PACKAGE_NAME}.tar.gz to the new machine"
echo "   2. Extract: tar -xzf ${PACKAGE_NAME}.tar.gz"
echo "   3. Navigate: cd $PACKAGE_DIR"
echo "   4. Run setup: ./setup-macos.sh"
echo "   5. Start dev: composer dev"
echo ""
echo "The package includes:"
echo "   ✓ Complete source code"
echo "   ✓ PostgreSQL setup scripts"
echo "   ✓ Automated installation script"
echo "   ✓ Environment templates"
echo "   ✓ Comprehensive documentation"
echo ""