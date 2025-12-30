#!/bin/bash
# Generate code coverage report

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}LounGenie Portal - Code Coverage Report Generator${NC}"
echo ""

# Check if phpunit is installed
if ! command -v vendor/bin/phpunit &> /dev/null; then
    echo -e "${RED}✗ PHPUnit not found. Run 'composer install' first.${NC}"
    exit 1
fi

# Create coverage directory
mkdir -p coverage

echo -e "${YELLOW}Generating coverage reports...${NC}"
echo ""

# Run tests with coverage
vendor/bin/phpunit \
    --coverage-html=coverage/html \
    --coverage-clover=coverage/clover.xml \
    --coverage-text \
    --coverage-crap4j=coverage/crap4j.xml \
    2>&1 | tee coverage/test-report.txt

echo ""
echo -e "${GREEN}✓ Coverage reports generated${NC}"
echo ""
echo "Report locations:"
echo -e "  ${BLUE}HTML Report:${NC}   coverage/html/index.html"
echo -e "  ${BLUE}Clover XML:${NC}    coverage/clover.xml"
echo -e "  ${BLUE}Crap4J:${NC}        coverage/crap4j.xml"
echo -e "  ${BLUE}Text Report:${NC}   coverage/test-report.txt"
echo ""

# Extract and display coverage summary
echo -e "${YELLOW}Coverage Summary:${NC}"
grep "Code Coverage Report" coverage/test-report.txt -A 20 || true

# Calculate coverage percentage
if [ -f coverage/clover.xml ]; then
    echo ""
    echo -e "${YELLOW}Extracting coverage metrics...${NC}"
    
    # Parse XML for coverage stats
    coverage=$(grep -oP '(?<=<package )[^>]+' coverage/clover.xml | head -1 | grep -oP '(?<=methods=")[^"]*')
    
    echo -e "${GREEN}Coverage metrics extracted${NC}"
fi

echo ""
echo -e "${BLUE}Tips:${NC}"
echo "  • Open coverage/html/index.html in browser for detailed report"
echo "  • Green = well tested code"
echo "  • Red = untested code"
echo "  • Orange = partially tested code"
echo ""
