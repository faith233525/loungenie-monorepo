#!/bin/bash

# LounGenie Portal - Plugin Validation Test

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PLUGIN_DIR"

echo "LounGenie Portal Plugin Validation"
echo "=================================="
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

PASS=0
FAIL=0

# Test PHP syntax for all files
echo "Checking PHP Syntax..."
for file in loungenie-portal.php uninstall.php includes/*.php api/*.php roles/*.php; do
    if php -l "$file" > /dev/null 2>&1; then
        ((PASS++))
    else
        echo -e "${RED}ERROR: Syntax error in $file${NC}"
        ((FAIL++))
    fi
done

echo -e "${GREEN}✓ $PASS files syntax OK${NC}"

# Check for critical classes
echo ""
echo "Checking Classes..."

classes=(
    "includes/class-lgp-loader.php:LGP_Loader"
    "includes/class-lgp-database.php:LGP_Database"
    "includes/class-lgp-auth.php:LGP_Auth"
    "includes/class-lgp-router.php:LGP_Router"
    "api/companies.php:LGP_Companies_API"
    "api/units.php:LGP_Units_API"
    "api/tickets.php:LGP_Tickets_API"
)

for item in "${classes[@]}"; do
    file="${item%:*}"
    class="${item#*:}"
    if grep -q "class $class" "$file"; then
        ((PASS++))
    else
        echo -e "${RED}ERROR: $class not found in $file${NC}"
        ((FAIL++))
    fi
done

echo -e "${GREEN}✓ All critical classes defined${NC}"

# Check for database tables
echo ""
echo "Checking Database Schema..."

if grep -q "\$companies_table" includes/class-lgp-database.php && \
   grep -q "\$units_table" includes/class-lgp-database.php && \
   grep -q "\$service_requests_table" includes/class-lgp-database.php; then
    echo -e "${GREEN}✓ Database schema tables defined${NC}"
    ((PASS+=3))
else
    echo -e "${RED}ERROR: Missing database table definitions${NC}"
    ((FAIL+=3))
fi

# Check for required files
echo ""
echo "Checking File Structure..."

required_files=(
    "loungenie-portal.php"
    "uninstall.php"
    "composer.json"
    "phpcs.xml"
    ".htaccess"
    "includes/class-lgp-loader.php"
    "api/companies.php"
    "roles/support.php"
    "templates/portal-shell.php"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        ((PASS++))
    else
        echo -e "${RED}ERROR: Missing $file${NC}"
        ((FAIL++))
    fi
done

echo -e "${GREEN}✓ All required files present${NC}"

echo ""
echo "=================================="
echo -e "Results: ${GREEN}${PASS} PASS${NC} | ${RED}${FAIL} FAIL${NC}"
echo "=================================="

if [ $FAIL -eq 0 ]; then
    echo -e "\n${GREEN}✓ Plugin validation complete - Ready for deployment${NC}"
    exit 0
else
    echo -e "\n${RED}✗ Fix $FAIL issues before deployment${NC}"
    exit 1
fi
