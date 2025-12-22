#!/bin/bash

# LounGenie Portal - Complete Plugin Installation Script
# Run this on your WordPress server to auto-install the complete plugin

set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║    LounGenie Portal - Complete Plugin Installer    ║"
echo "║           Ready for Production Deployment          ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check WordPress installation
if [ ! -f "wp-config.php" ]; then
    echo -e "${RED}❌ Error: wp-config.php not found${NC}"
    echo "Please run this script from your WordPress root directory"
    exit 1
fi

echo -e "${BLUE}✓ WordPress installation found${NC}"

# Create plugins directory if it doesn't exist
if [ ! -d "wp-content/plugins" ]; then
    mkdir -p wp-content/plugins
    echo -e "${BLUE}✓ Created plugins directory${NC}"
fi

# Check if plugin ZIP exists
if [ ! -f "loungenie-portal-complete.zip" ]; then
    echo -e "${RED}❌ Error: loungenie-portal-complete.zip not found${NC}"
    echo "Please ensure the ZIP file is in the WordPress root directory"
    exit 1
fi

echo -e "${BLUE}✓ Plugin ZIP found: loungenie-portal-complete.zip${NC}"

# Backup existing plugin if it exists
if [ -d "wp-content/plugins/loungenie-portal" ]; then
    echo -e "${BLUE}⚠ Existing plugin found, creating backup...${NC}"
    BACKUP_DIR="wp-content/plugins/loungenie-portal.backup.$(date +%Y%m%d_%H%M%S)"
    mv "wp-content/plugins/loungenie-portal" "$BACKUP_DIR"
    echo -e "${GREEN}✓ Backup created: $BACKUP_DIR${NC}"
fi

# Extract plugin
echo -e "${BLUE}📦 Extracting plugin...${NC}"
unzip -q loungenie-portal-complete.zip -d wp-content/plugins/
echo -e "${GREEN}✓ Plugin extracted${NC}"

# Set correct permissions
echo -e "${BLUE}🔐 Setting file permissions...${NC}"
chmod -R 755 wp-content/plugins/loungenie-portal/
find wp-content/plugins/loungenie-portal -type f -exec chmod 644 {} \;
echo -e "${GREEN}✓ Permissions set${NC}"

# Try to activate with WP-CLI if available
if command -v wp &> /dev/null; then
    echo -e "${BLUE}⚙ Activating plugin with WP-CLI...${NC}"
    wp plugin activate loungenie-portal
    echo -e "${GREEN}✓ Plugin activated${NC}"
    
    # Create default support user
    echo -e "${BLUE}👤 Creating default support user...${NC}"
    
    # Check if user already exists
    if ! wp user get support_admin &> /dev/null; then
        wp user create support_admin support@loungenie.local \
            --user_pass=ChangeMe123! \
            --role=editor \
            --first_name="Support" \
            --last_name="Admin"
        
        # Add custom role
        wp user add-role support_admin lgp_support
        echo -e "${GREEN}✓ Support user created (login: support_admin / pass: ChangeMe123!)${NC}"
    fi
else
    echo -e "${BLUE}ℹ WP-CLI not available${NC}"
    echo "Please activate the plugin manually:"
    echo "  1. Login to WordPress admin"
    echo "  2. Go to Plugins"
    echo "  3. Find 'LounGenie Portal'"
    echo "  4. Click 'Activate'"
fi

# Summary
echo ""
echo "╔════════════════════════════════════════════════════╗"
echo "║          ✅ Installation Complete!                ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Your LounGenie Portal plugin is ready!${NC}"
echo ""
echo "📋 Next Steps:"
echo "  1. Login to WordPress Admin"
echo "  2. Go to Plugins → Installed Plugins"
echo "  3. Find 'LounGenie Portal' and click Activate"
echo "  4. Create test users (Support & Partner roles)"
echo "  5. Access /portal in your WordPress site"
echo ""
echo "📚 Documentation:"
echo "  • README.md - Feature overview"
echo "  • SETUP_GUIDE.md - Complete setup"
echo "  • DEPLOYMENT_CHECKLIST.md - Pre-deployment tasks"
echo ""
echo "🔑 Default Support User (if created):"
echo "  • Username: support_admin"
echo "  • Password: ChangeMe123!"
echo "  • Role: LounGenie Support Team"
echo ""
echo -e "${BLUE}Happy deploying! 🚀${NC}"
echo ""
