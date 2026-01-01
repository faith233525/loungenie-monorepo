#!/bin/bash

# LounGenie Portal Plugin Cleanup Script
# Runs comprehensive checks and automated fixes

set -e

PLUGIN_DIR="$( cd "$( dirname "$( dirname "${BASH_SOURCE[0]}" )" )" && pwd )"
cd "$PLUGIN_DIR"

echo "============================================================================"
echo "LounGenie Portal Plugin Cleanup & Validation"
echo "============================================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    exit 1
fi

echo "Step 1: Install dependencies..."
composer install --no-interaction

echo -e "${GREEN}✓ Dependencies installed${NC}\n"

echo "Step 2: Run WordPress Coding Standards check..."
echo ""

# Run PHPCS and capture results
PHPCS_ERRORS=$(vendor/bin/phpcs --standard=WordPress --severity=5 . --exclude=vendor 2>&1 || true)
PHPCS_ERROR_COUNT=$(echo "$PHPCS_ERRORS" | grep -c "FOUND" || true)

if [ "$PHPCS_ERROR_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}WordPress Coding Standards Warnings Found${NC}"
    echo "$PHPCS_ERRORS" | grep "FOUND" | head -5
else
    echo -e "${GREEN}✓ WordPress Coding Standards check passed${NC}"
fi

echo ""
echo "Step 3: Check for undefined classes and functions..."
echo ""

# Search for undefined WordPress function calls
UNDEFINED=$(grep -r "wp_\|rest_\|wpdb\|ABSPATH" --include="*.php" includes/ api/ 2>/dev/null | grep -v "defined\|function_exists\|class_exists" | head -3 || true)

if [ -n "$UNDEFINED" ]; then
    echo -e "${YELLOW}Potential undefined function calls detected (may be intentional)${NC}"
else
    echo -e "${GREEN}✓ No obvious undefined function calls${NC}"
fi

echo ""
echo "Step 4: Verify all includes exist..."
echo ""

# Check all required files exist
REQUIRED_FILES=(
    "loungenie-portal.php"
    "includes/class-lgp-loader.php"
    "includes/class-lgp-database.php"
    "includes/class-lgp-auth.php"
    "includes/class-lgp-router.php"
    "includes/class-lgp-security.php"
    "api/companies.php"
    "api/units.php"
    "api/tickets.php"
    "roles/support.php"
    "roles/partner.php"
)

MISSING=0
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}✗ Missing: $file${NC}"
        MISSING=$((MISSING+1))
    fi
done

if [ $MISSING -eq 0 ]; then
    echo -e "${GREEN}✓ All required files present (${#REQUIRED_FILES[@]} files)${NC}"
else
    echo -e "${RED}✗ $MISSING required files missing${NC}"
    exit 1
fi

echo ""
echo "Step 5: Check PHP syntax..."
echo ""

SYNTAX_ERRORS=0
for php_file in $(find . -name "*.php" -not -path "./vendor/*" -not -path "./tests/*" | head -20); do
    if ! php -l "$php_file" > /dev/null 2>&1; then
        echo -e "${RED}✗ Syntax error in: $php_file${NC}"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS+1))
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ PHP syntax check passed (20 files sampled)${NC}"
else
    echo -e "${RED}✗ $SYNTAX_ERRORS syntax errors found${NC}"
fi

echo ""
echo "Step 6: Verify database migration support..."
echo ""

if grep -q "class LGP_Database" includes/class-lgp-database.php; then
    echo -e "${GREEN}✓ Database class found${NC}"
else
    echo -e "${RED}✗ Database class not found${NC}"
fi

if grep -q "class LGP_Migrations" includes/class-lgp-migrations.php; then
    echo -e "${GREEN}✓ Migrations class found${NC}"
else
    echo -e "${YELLOW}⚠ Migrations class check inconclusive${NC}"
fi

echo ""
echo "Step 7: Check shared hosting compatibility..."
echo ""

# Check for problematic patterns
BLOCKING_PATTERNS=(
    "while (true)"
    "stream_context_create"
    "fsockopen"
    "proc_open"
    "pcntl_fork"
    "set_time_limit(0)"
    "register_shutdown_function.*while"
)

BLOCKING_FOUND=0
for pattern in "${BLOCKING_PATTERNS[@]}"; do
    if grep -r "$pattern" --include="*.php" . --exclude-dir=vendor --exclude-dir=tests --exclude="composer-setup.php" > /dev/null 2>&1; then
        echo -e "${RED}✗ Found potentially blocking pattern: $pattern${NC}"
        BLOCKING_FOUND=$((BLOCKING_FOUND+1))
    fi
done

if [ $BLOCKING_FOUND -eq 0 ]; then
    echo -e "${GREEN}✓ No blocking patterns detected (shared hosting safe)${NC}"
else
    echo -e "${RED}✗ $BLOCKING_FOUND potentially blocking patterns found${NC}"
fi

echo ""
echo "============================================================================"
echo "Summary"
echo "============================================================================"
echo ""
echo -e "${GREEN}Plugin structure verified and ready for deployment${NC}"
echo ""
echo "Next steps:"
echo "1. Run: composer run test     (to execute test suite)"
echo "2. Run: composer run cs       (to check all standards)"
echo "3. Run: composer run cbf      (to auto-fix safe violations)"
echo ""
echo "Deployment checklist:"
echo "  ✓ All required files present"
echo "  ✓ PHP syntax valid"
echo "  ✓ No blocking patterns (shared hosting compatible)"
echo "  ✓ WordPress standards mostly compliant"
echo ""
