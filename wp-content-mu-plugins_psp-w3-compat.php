<?php
/**
 * PoolSafe Portal - W3 Total Cache Compatibility Fix
 * 
 * This mu-plugin disables certain W3 Total Cache minification features
 * that can cause JavaScript syntax errors with inline scripts.
 * 
 * Place this file at: /wp-content/mu-plugins/psp-w3-compat.php
 */

// Disable W3 Total Cache minification for inline scripts on portal page
add_action('wp_enqueue_scripts', function() {
    // Only on portal page
    if (! is_admin() && (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'pool-safe-portal') !== false)) {
        // Disable W3 Total Cache
        if (defined('W3TC')) {
            // Prevent minification
            add_filter('w3tc_minify_enabled', '__return_false');
            add_filter('w3tc_minify_inline', '__return_false');
        }
    }
}, 1);

// Alternative: Wrap Portal Config in try-catch for safety
add_action('wp_footer', function() {
    // Only on portal page
    if (! is_admin() && (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'pool-safe-portal') !== false)) {
        ?>
        <script>
        // Safety wrapper for PORTAL_CONFIG initialization
        if (typeof window.PORTAL_CONFIG === 'undefined') {
            console.warn('[PSP] PORTAL_CONFIG not initialized');
            window.PORTAL_CONFIG = {
                apiUrl: '/wp-json/psp/v1/',
                nonce: '<?php echo wp_create_nonce("wp_rest"); ?>',
                user: null,
                adminUrl: '<?php echo admin_url(); ?>'
            };
        }
        console.log('[PSP] Portal configuration loaded');
        </script>
        <?php
    }
}, 99);
