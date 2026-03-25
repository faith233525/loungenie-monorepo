#!/bin/bash
# Install WordPress test environment for running unit tests

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_ROOT_DIR=${WP_ROOT_DIR-/tmp/wordpress}
WP_VERSION=${WP_VERSION-6.4}
DB_NAME=${1-wordpress_test}
DB_USER=${2-root}
DB_PASSWORD=${3-root}
DB_HOST=${4-127.0.0.1}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Installing WordPress test environment...${NC}"
echo "WP_TESTS_DIR: $WP_TESTS_DIR"
echo "WP_ROOT_DIR: $WP_ROOT_DIR"
echo "WP_VERSION: $WP_VERSION"
echo "Database: $DB_NAME@$DB_HOST"

# Create directories
mkdir -p "$WP_TESTS_DIR"
mkdir -p "$WP_ROOT_DIR"

# Download WordPress test library
if [ ! -f "$WP_TESTS_DIR/includes/bootstrap.php" ]; then
    echo -e "${YELLOW}Downloading WordPress test library...${NC}"
    cd /tmp
    curl https://develop.svn.wordpress.org/tags/$WP_VERSION/tests/phpunit/includes/ -r 0-999999 -o wordpress-tests-lib.tar.gz 2>/dev/null
    
    if [ ! -f wordpress-tests-lib.tar.gz ]; then
        echo -e "${RED}Failed to download test library${NC}"
        exit 1
    fi
    
    tar -xzf wordpress-tests-lib.tar.gz -C "$WP_TESTS_DIR" --strip-components=5
    rm wordpress-tests-lib.tar.gz
fi

# Download WordPress
if [ ! -f "$WP_ROOT_DIR/wp-load.php" ]; then
    echo -e "${YELLOW}Downloading WordPress $WP_VERSION...${NC}"
    cd /tmp
    curl https://wordpress.org/wordpress-$WP_VERSION.tar.gz -o wordpress.tar.gz
    
    if [ ! -f wordpress.tar.gz ]; then
        echo -e "${RED}Failed to download WordPress${NC}"
        exit 1
    fi
    
    tar -xzf wordpress.tar.gz
    cp -r wordpress/* "$WP_ROOT_DIR/"
    rm -rf wordpress wordpress.tar.gz
fi

# Create wp-tests-config.php
echo -e "${YELLOW}Creating wp-tests-config.php...${NC}"
cat > "$WP_TESTS_DIR/wp-tests-config.php" << EOF
<?php
error_reporting(E_ALL);

define('ABSPATH', '$WP_ROOT_DIR/');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASSWORD', '$DB_PASSWORD');
define('DB_HOST', '$DB_HOST');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

define('WP_PHP_BINARY', 'php');

if (file_exists('$WP_ROOT_DIR/wp-content/plugins/loungenie-portal/')) {
    define('WP_PLUGIN_DIR', '$WP_ROOT_DIR/wp-content/plugins');
}

\$table_prefix = 'wp_';

define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

require_once ABSPATH . 'wp-settings.php';
EOF

# Create database
echo -e "${YELLOW}Creating test database...${NC}"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create database${NC}"
    echo -e "${YELLOW}Make sure MySQL is running and credentials are correct${NC}"
    exit 1
fi

# Verify installation
if [ -f "$WP_TESTS_DIR/includes/bootstrap.php" ] && [ -f "$WP_ROOT_DIR/wp-load.php" ]; then
    echo -e "${GREEN}✓ WordPress test environment installed successfully${NC}"
    echo ""
    echo -e "${GREEN}Setup complete!${NC}"
    echo ""
    echo "To run tests, use:"
    echo "  composer test"
    echo ""
    echo "Set environment variables:"
    echo "  export WP_TESTS_DIR=$WP_TESTS_DIR"
    echo "  export WP_ROOT_DIR=$WP_ROOT_DIR"
    echo ""
else
    echo -e "${RED}✗ Installation failed${NC}"
    exit 1
fi
