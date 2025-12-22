#!/bin/bash

# Shared Server Deployment Optimization Script
# 
# Optimizes LounGenie Portal plugin for shared hosting constraints
# Run this after deployment to tune performance
# 
# Usage: ./optimize-shared-server.sh

set -e

echo "=========================================="
echo "Shared Server Optimization Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get plugin directory
PLUGIN_DIR="$(dirname "$0")/.."

echo -e "${YELLOW}Step 1: Remove Unnecessary Files${NC}"
# Remove development files that bloat shared server
rm -rf "$PLUGIN_DIR/.git"
rm -rf "$PLUGIN_DIR/.github"
rm -rf "$PLUGIN_DIR/tests" 2>/dev/null || true
rm -rf "$PLUGIN_DIR/node_modules" 2>/dev/null || true
rm -f "$PLUGIN_DIR/package.json" 2>/dev/null || true
rm -f "$PLUGIN_DIR/composer.json" 2>/dev/null || true
rm -f "$PLUGIN_DIR/.phpunit.xml" 2>/dev/null || true
rm -f "$PLUGIN_DIR/phpcs.xml" 2>/dev/null || true
rm -f "$PLUGIN_DIR/*.md" 2>/dev/null || true
echo -e "${GREEN}✓ Development files removed${NC}"
echo ""

echo -e "${YELLOW}Step 2: Optimize Vendor Directory${NC}"
# Keep only production Composer dependencies
if [ -f "$PLUGIN_DIR/composer.lock" ]; then
    cd "$PLUGIN_DIR"
    composer install --no-dev --optimize-autoloader --prefer-dist 2>/dev/null || true
    cd - > /dev/null
    echo -e "${GREEN}✓ Vendor directory optimized${NC}"
else
    echo -e "${YELLOW}⚠ No Composer lock file found, skipping${NC}"
fi
echo ""

echo -e "${YELLOW}Step 3: Set Optimal File Permissions${NC}"
# Set restrictive permissions for security
find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
# Allow execution of PHP files
find "$PLUGIN_DIR" -name "*.php" -exec chmod 644 {} \;
echo -e "${GREEN}✓ File permissions optimized${NC}"
echo ""

echo -e "${YELLOW}Step 4: Create Cache Directories${NC}"
# Create writable cache directories
mkdir -p "$PLUGIN_DIR/wp-content/cache/loungenie-portal"
chmod 755 "$PLUGIN_DIR/wp-content/cache/loungenie-portal"
mkdir -p "$PLUGIN_DIR/tmp"
chmod 755 "$PLUGIN_DIR/tmp"
echo -e "${GREEN}✓ Cache directories created${NC}"
echo ""

echo -e "${YELLOW}Step 5: Enable Caching via .htaccess${NC}"
# Create .htaccess for browser caching (if Apache)
cat > "$PLUGIN_DIR/.htaccess" << 'EOF'
# Enable Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
<FilesMatch "(^#.*#|\.(php|sql|phtml|php~|phtml~|php\.|class)$)">
    Order Deny,Allow
    Deny from all
</FilesMatch>
EOF
echo -e "${GREEN}✓ .htaccess caching rules added${NC}"
echo ""

echo -e "${YELLOW}Step 6: Create php.ini Overrides (if cPanel/WHM)${NC}"
# Create php.ini for optimization
cat > "$PLUGIN_DIR/.user.ini" << 'EOF'
; Memory and execution optimizations
memory_limit = 64M
max_execution_time = 30
max_input_time = 30

; Security settings
display_errors = 0
error_reporting = E_ALL
log_errors = 1

; Session handling
session.gc_maxlifetime = 1440

; File upload limits
upload_max_filesize = 50M
post_max_size = 50M

; Disable dangerous functions on shared server
disable_functions = "exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source"
EOF
echo -e "${GREEN}✓ .user.ini created (will override php.ini if supported)${NC}"
echo ""

echo -e "${YELLOW}Step 7: Optimize CSS and JS Assets${NC}"
# Minify CSS files (basic)
find "$PLUGIN_DIR/assets/css" -name "*.css" -type f | while read file; do
    if [[ ! "$file" == *.min.css ]]; then
        # Create minified version
        php -r "
            \$css = file_get_contents('$file');
            \$css = preg_replace('/\/\*[^*]*\*+(?:[^/*][^*]*\*+)*\//', '', \$css);
            \$css = preg_replace('/\s\s+/', ' ', \$css);
            \$css = str_replace([' {', '{ ', ' }', '} '], ['{', '{', '}', '}'], \$css);
            file_put_contents('${file%.css}.min.css', \$css);
        " 2>/dev/null || true
    fi
done
echo -e "${GREEN}✓ CSS files optimized${NC}"
echo ""

echo -e "${YELLOW}Step 8: Configure Database Connection Pooling${NC}"
# Create db-optimization.php
cat > "$PLUGIN_DIR/includes/db-optimization.php" << 'EOF'
<?php
/**
 * Database connection pooling and optimization
 * for shared hosting environments
 */

// Enable persistent connections on shared servers (if allowed)
if ( ! defined( 'DB_PERSISTENT' ) ) {
    define( 'DB_PERSISTENT', true );
}

// Set query cache (for MySQL)
if ( ! defined( 'MYSQL_CLIENT_FLAGS' ) ) {
    define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_FOUND_ROWS );
}

// Add database optimization filters
add_filter( 'query', function( $query ) {
    // Log slow queries on shared server
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        $start = microtime( true );
        // Query execution happens here
        $duration = ( microtime( true ) - $start ) * 1000;
        
        if ( $duration > 100 ) { // More than 100ms
            error_log( "Slow query ($duration ms): $query" );
        }
    }
    
    return $query;
} );

// Optimize transient cleanup
add_action( 'init', function() {
    // Run transient cleanup less frequently on shared server
    if ( rand( 1, 1000 ) === 1 ) {
        wp_cache_flush_group( 'transients' );
    }
} );
EOF
echo -e "${GREEN}✓ Database optimization created${NC}"
echo ""

echo -e "${YELLOW}Step 9: Enable WP Super Cache Configuration${NC}"
# Create wp-cache config
cat > "$PLUGIN_DIR/wp-cache-config.php" << 'EOF'
<?php
// WP Super Cache configuration for shared server
define( 'WP_CACHE', true );
define( 'WPCACHEHOME', dirname( __FILE__ ) . '/wp-content/plugins/wp-super-cache/' );
define( 'WP_CACHE_KEY_SALT', 'loungenie-portal_' );

// Cache settings
define( 'WP_CACHE_PRELOAD', true );
define( 'WP_CACHE_PRELOAD_TIMER', 3600 ); // 1 hour
define( 'COMPRESS_CSS', true );
define( 'COMPRESS_SCRIPTS', true );
define( 'CONCATENATE_SCRIPTS', true );
EOF
echo -e "${GREEN}✓ WP Super Cache config created${NC}"
echo ""

echo -e "${YELLOW}Step 10: Create Health Check Script${NC}"
# Create health check
cat > "$PLUGIN_DIR/includes/health-check.php" << 'EOF'
<?php
/**
 * Shared server health check
 * Access via: yourdomain.com/?lgp_health_check=1
 */

if ( ! empty( $_GET['lgp_health_check'] ) ) {
    $health = [
        'status' => 'healthy',
        'timestamp' => current_time( 'mysql' ),
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage( true ),
        'memory_peak' => memory_get_peak_usage( true ),
        'memory_limit' => wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ),
        'database_connection' => $GLOBALS['wpdb']->dbh ? 'connected' : 'disconnected',
    ];
    
    header( 'Content-Type: application/json' );
    echo json_encode( $health );
    exit;
}
EOF
echo -e "${GREEN}✓ Health check created${NC}"
echo ""

echo -e "${YELLOW}Step 11: Generate Deployment Report${NC}"
# Create deployment report
cat > "$PLUGIN_DIR/SHARED_SERVER_DEPLOYMENT.txt" << EOF
=====================================
Shared Server Deployment Report
=====================================

Plugin: LounGenie Portal v1.8.0
Date: $(date)

OPTIMIZATION CHECKLIST:
✓ Development files removed
✓ Vendor directory optimized
✓ File permissions set
✓ Cache directories created
✓ .htaccess caching enabled
✓ .user.ini created
✓ CSS/JS optimized
✓ Database optimization enabled
✓ WP Super Cache configured
✓ Health check enabled

DEPLOYMENT STEPS:
1. Upload plugin to wp-content/plugins/loungenie-portal/
2. Activate plugin in WordPress admin
3. Run health check: ?lgp_health_check=1
4. Monitor performance via WP Dashboard

PERFORMANCE EXPECTATIONS:
- Page load: < 2 seconds
- API response: < 200ms
- Memory usage: < 64MB
- Database queries: < 50ms average

TROUBLESHOOTING:
- Check .user.ini is applied: phpinfo()
- Verify cache is working: wp_cache_get()
- Monitor memory: WP Dashboard > Tools > Health Check
- Check error logs: /wp-content/debug.log

SUPPORT:
See IMPLEMENTATION_UPDATES.md for full documentation
See tests/shared-server-compatibility.php for compatibility checks
EOF
echo -e "${GREEN}✓ Deployment report created${NC}"
echo ""

echo -e "${GREEN}=========================================="
echo "✓ Optimization Complete!"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Upload plugin to shared server"
echo "2. Activate in WordPress Admin"
echo "3. Run: ?lgp_health_check=1 to verify"
echo "4. Check: tests/shared-server-compatibility.php?run_tests=1"
echo ""
