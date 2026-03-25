#!/bin/bash
#
# WordPress.org Production Release Preparation Script
# Removes non-production files and creates clean ZIP
#

set -e

PLUGIN_DIR="loungenie-portal"
TEMP_DIR="loungenie-portal-wporg-staging"
OUTPUT_ZIP="loungenie-portal-wporg-production.zip"

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  WordPress.org Production Release Preparation                ║"
echo "║  LounGenie Portal v1.8.1                                     ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Remove old staging directory if exists
if [ -d "$TEMP_DIR" ]; then
    echo "→ Removing old staging directory..."
    rm -rf "$TEMP_DIR"
fi

# Create clean copy
echo "→ Creating clean copy of plugin..."
mkdir -p "$TEMP_DIR"
cp -r "$PLUGIN_DIR" "$TEMP_DIR/"

cd "$TEMP_DIR/$PLUGIN_DIR"

echo "→ Removing non-production files..."

# Remove development/documentation files
rm -f *.md 2>/dev/null || true
rm -f composer.* package*.json phpunit.xml phpcs.xml 2>/dev/null || true
rm -f .git* .env* .editorconfig .phpcs.xml* 2>/dev/null || true
rm -f sample-data.sql test-*.* preview-demo.html 2>/dev/null || true
rm -f VERSION 2>/dev/null || true

# Remove development directories
rm -rf tests vendor node_modules docs scripts wp-admin wp-cli 2>/dev/null || true
rm -rf .git .github .vscode .idea 2>/dev/null || true

# Remove asset source files (keep minified only)
find assets/css -name "*.map" -delete 2>/dev/null || true
find assets/js -name "*.map" -delete 2>/dev/null || true

# Keep only essential CSS
cd assets/css
KEEP_CSS="design-tokens.css portal-components.css design-system-refactored.css portal.css login.css map-view.css attachments.css"
for file in *.css; do
    if ! echo "$KEEP_CSS" | grep -w "$file" > /dev/null; then
        echo "  Removing extra CSS: $file"
        rm -f "$file"
    fi
done
cd ../..

# Keep only essential JS
cd assets/js
KEEP_JS="portal.js portal-init.js lgp-utils.js map-view.js knowledge-center-view.js tickets-view.js gateway-view.js training-view.js company-profile-enhancements.js company-profile-partner-polish.js attachments.js"
for file in *.js; do
    if ! echo "$KEEP_JS" | grep -w "$file" > /dev/null; then
        echo "  Removing extra JS: $file"
        rm -f "$file"
    fi
done
cd ../..

# Remove non-essential templates
cd templates
rm -f custom-login-modern.php custom-login-enhanced.php portal-login.php partner-login.php support-login.php 2>/dev/null || true
cd ..

# Remove test/demo files from includes (keep admin helpers needed by loader)
cd includes
rm -f class-shared-server-diagnostics.php class-lgp-shared-server-optimizer.php 2>/dev/null || true
cd ..

echo "→ Cleaning complete!"
echo ""
echo "→ File count:"
echo "  PHP files: $(find . -name "*.php" | wc -l)"
echo "  CSS files: $(find assets/css -name "*.css" | wc -l)"
echo "  JS files: $(find assets/js -name "*.js" | wc -l)"
echo ""

cd ../..

# Create production ZIP
echo "→ Creating production ZIP..."
cd "$TEMP_DIR"
zip -r "../$OUTPUT_ZIP" "$PLUGIN_DIR" -q

cd ..
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Production ZIP created: $OUTPUT_ZIP"
ls -lh "$OUTPUT_ZIP"
echo ""
echo "Ready for WordPress.org submission!"
