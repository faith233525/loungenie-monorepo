#!/bin/bash
set -e

echo "=== CREATING PRODUCTION-READY ZIP ==="
echo ""

# Define production folder
PROD_DIR="loungenie-portal-production"
ZIP_NAME="loungenie-portal-v1.8.1-production.zip"

# Clean previous build
rm -rf "$PROD_DIR" "$ZIP_NAME" 2>/dev/null || true

echo "## Step 1: Creating production directory structure"
mkdir -p "$PROD_DIR"

echo "## Step 2: Copying essential files"

# Core plugin files
cp loungenie-portal.php "$PROD_DIR/"
cp uninstall.php "$PROD_DIR/" 2>/dev/null || true
cp VERSION "$PROD_DIR/" 2>/dev/null || true

# Production documentation
echo "Copying production docs..."
for doc in README.md readme.txt CHANGELOG.md SETUP_GUIDE.md FILTERING_GUIDE.md ENTERPRISE_FEATURES.md FEATURES.md OPTIONAL_CONFIGURATION_GUIDE.md; do
    [ -f "$doc" ] && cp "$doc" "$PROD_DIR/" && echo "  ✅ $doc"
done

# Configuration files (composer.json, phpunit.xml for reference)
cp composer.json "$PROD_DIR/" 2>/dev/null || true
cp phpcs.xml "$PROD_DIR/" 2>/dev/null || true

echo ""
echo "## Step 3: Copying runtime folders"

# Copy full folders
for folder in includes api templates assets roles scripts languages; do
    if [ -d "$folder" ]; then
        cp -r "$folder" "$PROD_DIR/"
        FILE_COUNT=$(find "$PROD_DIR/$folder" -type f | wc -l)
        echo "  ✅ $folder/ ($FILE_COUNT files)"
    fi
done

echo ""
echo "## Step 4: Excluding development files"
echo "EXCLUDED from production ZIP:"
echo "  ❌ /tests (development only)"
echo "  ❌ /docs (development docs)"
echo "  ❌ /vendor (Composer dependencies)"
echo "  ❌ node_modules (if any)"
echo "  ❌ .git, .github"
echo "  ❌ *.sh, *.log, *.tmp scripts"

echo ""
echo "## Step 5: Creating ZIP archive"
cd ..
zip -r "loungenie-portal/$ZIP_NAME" "$PROD_DIR" -q
cd loungenie-portal

# Get sizes
PROD_SIZE=$(du -sh "$PROD_DIR" | awk '{print $1}')
ZIP_SIZE=$(ls -lh "$ZIP_NAME" | awk '{print $5}')
FILE_COUNT=$(find "$PROD_DIR" -type f | wc -l)

echo ""
echo "✅ Production ZIP created successfully!"
echo ""
echo "📦 Package Details:"
echo "   Production folder: $PROD_SIZE"
echo "   ZIP archive: $ZIP_SIZE"
echo "   Total files: $FILE_COUNT"
echo "   Location: $ZIP_NAME"
echo ""
echo "📋 Contents:"
ls -1 "$PROD_DIR" | head -20
