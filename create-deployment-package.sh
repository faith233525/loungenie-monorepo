#!/bin/bash
#
# WordPress Plugin Deployment Package Creator
# Creates production-ready ZIP for shared hosting deployment
# Excludes development files (tests, vendor, node_modules, etc.)
#

set -e

PLUGIN_DIR="loungenie-portal"
VERSION="1.6.0"
OUTPUT_FILE="${PLUGIN_DIR}-v${VERSION}-deploy.zip"
TEMP_DIR="/tmp/wp-plugin-deploy-$$"

echo "🚀 Creating WordPress Plugin Deployment Package"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Plugin: ${PLUGIN_DIR}"
echo "Version: ${VERSION}"
echo "Output: ${OUTPUT_FILE}"
echo ""

# Create temporary directory
echo "📁 Creating temporary directory..."
mkdir -p "${TEMP_DIR}/${PLUGIN_DIR}"

# Copy only production files
echo "📦 Copying production files..."
rsync -av --progress \
  --exclude='tests/' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='*.log' \
  --exclude='*.md' \
  --exclude='*.xml' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='phpunit.xml' \
  --exclude='phpcs.xml' \
  --exclude='.phpcs.xml.dist' \
  --exclude='*.DS_Store' \
  --exclude='preview-demo.html' \
  --exclude='test-load.php' \
  --exclude='scripts/offline-*.php' \
  --exclude='scripts/Offline*.php' \
  "${PLUGIN_DIR}/" "${TEMP_DIR}/${PLUGIN_DIR}/"

# Create ZIP
echo ""
echo "🗜️  Creating ZIP archive..."
cd "${TEMP_DIR}"
zip -r -q "${OUTPUT_FILE}" "${PLUGIN_DIR}/"

# Move to original directory
echo "📤 Moving package to workspace..."
mv "${OUTPUT_FILE}" "/workspaces/Pool-Safe-Portal/"

# Cleanup
echo "🧹 Cleaning up temporary files..."
rm -rf "${TEMP_DIR}"

# Show package info
cd "/workspaces/Pool-Safe-Portal"
PACKAGE_SIZE=$(du -h "${OUTPUT_FILE}" | cut -f1)

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Deployment Package Created Successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Package: ${OUTPUT_FILE}"
echo "💾 Size: ${PACKAGE_SIZE}"
echo ""
echo "🚀 Ready to Deploy!"
echo ""
echo "Deployment Steps:"
echo "1. Log into your WordPress Admin on shared hosting"
echo "2. Go to Plugins → Add New → Upload Plugin"
echo "3. Choose: ${OUTPUT_FILE}"
echo "4. Click 'Install Now' then 'Activate'"
echo "5. Verify database tables created (wp_lgp_*)"
echo ""
echo "⚠️  IMPORTANT: Backup your database before deploying!"
echo "   Via phpMyAdmin: Export → SQL Format → Save"
echo ""
