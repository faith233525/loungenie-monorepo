#!/bin/bash
#
# WordPress.org Production Release Preparation Script v2
# Removes ALL non-production files and creates clean ZIP
#

set -e

PLUGIN_DIR="loungenie-portal"
TEMP_DIR="loungenie-portal-wporg-staging"
OUTPUT_ZIP="loungenie-portal-wporg-production.zip"

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  WordPress.org Production Release Preparation v2             ║"
echo "║  LounGenie Portal v1.8.1                                     ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Remove old files
rm -f "$OUTPUT_ZIP" 2>/dev/null || true
rm -rf "$TEMP_DIR" 2>/dev/null || true

# Create clean copy
echo "→ Creating clean copy of plugin..."
mkdir -p "$TEMP_DIR"
cp -r "$PLUGIN_DIR" "$TEMP_DIR/"

cd "$TEMP_DIR/$PLUGIN_DIR"

echo "→ Removing non-production files..."

# Remove ALL markdown and documentation
find . -name "*.md" -type f -delete
find . -name "*.txt" -type f ! -name "readme.txt" -delete

# Remove development configs
rm -f composer.* package*.json phpunit.xml phpcs.xml 2>/dev/null || true
rm -f .git* .env* .editorconfig .phpcs.xml* .phpunit.* 2>/dev/null || true
rm -f sample-data.sql test-*.* preview-demo.html VERSION 2>/dev/null || true

# Remove development directories
rm -rf tests vendor node_modules docs scripts wp-admin wp-cli 2>/dev/null || true
rm -rf .git .github .vscode .idea .scan 2>/dev/null || true

# Remove source maps
find . -name "*.map" -type f -delete

# Keep only essential CSS files
cd assets/css
for file in *.css; do
    case "$file" in
        design-tokens.css|portal-components.css|design-system-refactored.css|portal.css|login.css|map-view.css|attachments.css)
            echo "  Keeping: $file"
            ;;
        *)
            echo "  Removing: $file"
            rm -f "$file"
            ;;
    esac
done
cd ../..

# Keep only essential JS files
cd assets/js
for file in *.js; do
    case "$file" in
        portal.js|portal-init.js|lgp-utils.js|map-view.js|help-guides-view.js|tickets-view.js|gateway-view.js|training-view.js|company-profile-enhancements.js|company-profile-partner-polish.js|attachments.js)
            echo "  Keeping: $file"
            ;;
        *)
            echo "  Removing: $file"
            rm -f "$file"
            ;;
    esac
done
cd ../..

# Remove non-essential login templates (keep only portal-shell.php)
cd templates
rm -f custom-login-modern.php custom-login-enhanced.php portal-login.php partner-login.php support-login.php 2>/dev/null || true
rm -f training-view.php 2>/dev/null || true
cd ..

# Remove development-only includes
cd includes
rm -f class-shared-server-diagnostics.php 2>/dev/null || true
rm -f class-lgp-shared-server-optimizer.php 2>/dev/null || true
rm -f class-lgp-role-switcher.php 2>/dev/null || true
rm -f class-lgp-user-creator.php 2>/dev/null || true
cd ..

# Remove any test directories from assets
rm -rf assets/tests assets/demos 2>/dev/null || true

echo "→ Cleaning complete!"
echo ""
echo "→ File count:"
echo "  Total files: $(find . -type f | wc -l)"
echo "  PHP files: $(find . -name "*.php" | wc -l)"
echo "  CSS files: $(find assets/css -name "*.css" 2>/dev/null | wc -l)"
echo "  JS files: $(find assets/js -name "*.js" 2>/dev/null | wc -l)"
echo ""

# List structure
echo "→ Directory structure:"
find . -maxdepth 2 -type d | sort

cd ../..

# Create production ZIP
echo ""
echo "→ Creating production ZIP..."
cd "$TEMP_DIR"
zip -r "../$OUTPUT_ZIP" "$PLUGIN_DIR" -q -x "*.DS_Store" -x "*/.phpunit.result.cache" -x "*/__MACOSX/*"

cd ..
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Production ZIP created: $OUTPUT_ZIP"
ls -lh "$OUTPUT_ZIP"

# Verify ZIP contents
echo ""
echo "→ Verifying ZIP contents..."
NON_PROD=$(unzip -l "$OUTPUT_ZIP" | grep -E "(\.md|composer|package\.json|phpunit|\.git|test-|vendor/|node_modules/)" | wc -l)

if [ "$NON_PROD" -gt 0 ]; then
    echo "⚠️  WARNING: Found $NON_PROD non-production files in ZIP!"
    unzip -l "$OUTPUT_ZIP" | grep -E "(\.md|composer|package\.json|phpunit|\.git|test-|vendor/|node_modules/)"
else
    echo "✅ ZIP is clean - no non-production files detected"
fi

echo ""
echo "Ready for WordPress.org submission!"
