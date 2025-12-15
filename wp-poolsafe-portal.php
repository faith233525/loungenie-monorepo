<?php
/**
 * PoolSafe Portal - Secure Partner Management System
 *
 * @package   PoolSafe Portal
 * @version   3.3.0
 * @author    faith233525
 * @license   GPL-2.0-or-later
 * @link      https://yourdomain.com/poolsafe-portal
 *
 * Plugin Name: PoolSafe Portal
 * Plugin URI: https://yourdomain.com/poolsafe-portal
 * Description: Secure partner portal with ticketing, SSO, partner management, and analytics
 * Version: 3.3.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: faith233525
 * Author URI: https://yourdomain.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: psp
 * Domain Path: /languages
 *
 * CHANGELOG (v3.3.0):
 * - Unified portal shortcode with company-based authentication
 * - Microsoft 365 SSO for support users
 * - Company accounts with multiple contacts
 * - Simple passwords (no complexity rules, securely hashed)
 * - Admin interface for company management
 * - REST API endpoints for dashboard, tickets, services
 * - Performance optimizations with caching and pagination
 * - Minified assets with version-based cache busting
 * - Theme-integrated responsive CSS
 * - ARIA-compliant UI components
 *
 * CHANGELOG (v3.0.0):
 * - Full WordPress Standards Compliance
 * - Custom database tables with proper $wpdb->prefix
 * - REST API with namespace (poolsafe/v1)
 * - Custom roles without core capabilities
 * - WP-Cron for all scheduled tasks
 * - Proper asset enqueueing with dependencies
 * - i18n/translation support throughout
 * - Security escaping for all output
 * - Activation/deactivation hooks with role creation
 * - Support for caching plugins (LiteSpeed, W3TC, WP Rocket)
 * - Tested on WordPress 5.8+, PHP 7.4+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Emergency error logging for debugging fatal errors
error_log( '[PSP v3.3.0] Plugin file loaded at ' . date( 'Y-m-d H:i:s' ) );

// ============================================================================
// CONFIGURATION CONSTANTS
// ============================================================================

/**
 * Plugin path constants (MUST be defined first)
 * Safe to reference in any component without circular dependency
 */
if ( ! defined( 'PSP_PLUGIN_FILE' ) ) {
    define( 'PSP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'PSP_PLUGIN_DIR' ) ) {
    define( 'PSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'PSP_PLUGIN_URL' ) ) {
    define( 'PSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'PSP_ASSETS_URL' ) ) {
    define( 'PSP_ASSETS_URL', PSP_PLUGIN_URL . 'assets/' );
}

/**
 * Version and compatibility constants
 * Define minimum requirements for PHP and WordPress
 */
define( 'PSP_VERSION', '3.3.0' );
define( 'PSP_DB_VERSION', '3.2.5' );
define( 'PSP_MIN_PHP', '7.4' );
define( 'PSP_MIN_WP', '5.8' );
define( 'PSP_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
define( 'PSP_TEXT_DOMAIN', 'psp' );
define( 'PSP_LOCALE_DIR', PSP_PLUGIN_DIR . 'languages' );

// ============================================================================
// SECURITY HEADERS
// ============================================================================

/**
 * Set HTTP security headers for HTTPS connections
 * Includes HSTS, CSP (Report-Only), X-Frame-Options, and more
 * Only applied to HTTPS connections (production safety)
 */
add_action(
    'send_headers',
    function () {
        if ( ! is_ssl() ) {
            return;
        }

        $enabled = apply_filters( 'psp_security_headers_enabled', true );
        if ( ! $enabled ) {
            return;
        }

        header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload' );
        header( 'X-Content-Type-Options: nosniff' );

        $frame_option = apply_filters( 'psp_frame_options', 'SAMEORIGIN' );
        if ( $frame_option ) {
            header( 'X-Frame-Options: ' . $frame_option );
        }

        $referrer = apply_filters( 'psp_referrer_policy', 'strict-origin-when-cross-origin' );
        if ( $referrer ) {
            header( 'Referrer-Policy: ' . $referrer );
        }

        $permissions = apply_filters( 'psp_permissions_policy', 'geolocation=(), microphone=(), camera=()' );
        if ( $permissions ) {
            header( 'Permissions-Policy: ' . $permissions );
        }

        header( 'X-XSS-Protection: 1; mode=block' );

        $nonce = apply_filters( 'psp_csp_nonce', bin2hex( random_bytes( 16 ) ) );

        $directives = array(
            'default-src' => "'self'",
            'connect-src' => [ "'self'", 'https://login.microsoftonline.com', 'https://graph.microsoft.com', 'https://api.hubapi.com' ],
            'img-src'     => [ "'self'", 'data:', 'https:' ],
            'style-src'   => [ "'self'", "'nonce-{$nonce}'", 'https://cdnjs.cloudflare.com', 'https://fonts.googleapis.com' ],
            'script-src'  => [ "'self'", "'nonce-{$nonce}'", 'https://cdnjs.cloudflare.com', 'https://login.microsoftonline.com' ],
            'font-src'    => [ "'self'", 'data:', 'https://fonts.googleapis.com', 'https://fonts.gstatic.com' ],
            'frame-ancestors' => "'self'",
            'base-uri'    => "'self'",
            'form-action' => [ "'self'", 'https://login.microsoftonline.com' ],
            'worker-src'  => [ "'self'", 'blob:' ],
        );

        /**
         * Filter CSP directives before sending.
         * Return associative array of directive => array|string.
         */
        $directives = apply_filters( 'psp_csp_directives', $directives, $nonce );

        $parts = array();
        foreach ( $directives as $directive => $value ) {
            if ( is_array( $value ) ) {
                $value = implode( ' ', $value );
            }
            if ( $value === '' || $value === null ) {
                continue;
            }
            $parts[] = $directive . ' ' . $value;
        }

        if ( ! empty( $parts ) ) {
            $mode        = apply_filters( 'psp_csp_mode', 'enforce' ); // enforce|report-only
            $header_name = ( $mode === 'report-only' ) ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $csp         = implode( '; ', $parts );

            $report_uri = apply_filters( 'psp_csp_report_uri', '' );
            if ( $report_uri ) {
                $csp .= '; report-uri ' . $report_uri;
            }

            header( $header_name . ': ' . $csp );
        }

        // Expose nonce for enqueueing inline scripts when needed
        if ( ! defined( 'PSP_CSP_NONCE' ) ) {
            define( 'PSP_CSP_NONCE', $nonce );
        }
    }
);

// ============================================================================
// PREFLIGHT CHECKS & COMPATIBILITY VALIDATION
// ============================================================================

/**
 * Preflight check for required plugin files
 *
 * Prevents fatal errors on misconfigured installations where:
 * - Plugin folder is misplaced or nested
 * - Files are partially copied/missing
 * - Installation path is incorrect (e.g., wp-poolsafe-portal-1)
 *
 * @return bool True if all required files exist, false otherwise
 */
if ( ! function_exists( 'psp_preflight' ) ) {
    function psp_preflight() {
        $required_files = array(
            __DIR__ . '/includes/class-psp-plugin.php',
        );

        $missing = array();
        foreach ( $required_files as $file_path ) {
            if ( ! file_exists( $file_path ) ) {
                $missing[] = $file_path;
            }
        }

        if ( ! empty( $missing ) ) {
            $message = "PoolSafe Portal plugin files are missing.\n\n";
            $message .= "Plugin path: " . __DIR__ . "\n";
            $message .= "Missing files: \n- " . implode( "\n- ", $missing ) . "\n\n";
            $message .= "Fix: In WordPress Admin → Plugins, delete any stale copies like 'wp-poolsafe-portal-1' or nested folders, "
                . "then upload the zip so it installs to 'wp-content/plugins/wp-poolsafe-portal/'.";

            // Log error (wp_die not available before WordPress loads)
            error_log( '[PoolSafe Portal] ' . $message );
            return false;
        }

        return true;
    }
}

/**
 * Validate PHP and WordPress version compatibility
 *
 * Prevents fatal errors on installations below:
 * - PHP 7.4 (modern language features, security patches)
 * - WordPress 5.8 (REST API stability, modern hooks)
 *
 * @return bool True if compatible, false otherwise
 */
if ( ! function_exists( 'psp_check_compatibility' ) ) {
    function psp_check_compatibility() {
        global $wp_version;

        if ( version_compare( PHP_VERSION, PSP_MIN_PHP, '<' ) ) {
            $message = sprintf( 'PHP %s+ required (current: %s)', PSP_MIN_PHP, PHP_VERSION );
            error_log( '[PoolSafe Portal] Compatibility: ' . $message );
            return false;
        }

        if ( version_compare( $wp_version, PSP_MIN_WP, '<' ) ) {
            $message = sprintf( 'WordPress %s+ required (current: %s)', PSP_MIN_WP, $wp_version );
            error_log( '[PoolSafe Portal] Compatibility: ' . $message );
            return false;
        }

        return true;
    }
}

/**
 * Execute preflight and compatibility checks
 * Stop plugin execution if either check fails
 */
if ( ! psp_preflight() || ! psp_check_compatibility() ) {
    add_action(
        'admin_notices',
        function () {
            printf(
                '<div class="notice notice-error"><p><strong>PoolSafe Portal Error:</strong> %s</p></div>',
                esc_html( 'Cannot initialize - check error logs for details.' )
            );
        }
    );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[PoolSafe Portal] Initialization stopped - preflight or compatibility check failed.' );
    }

    return; // Stop plugin execution
}

// ============================================================================
// W3 TOTAL CACHE COMPATIBILITY
// ============================================================================

/**
 * Install a must-use plugin to silence W3 Total Cache textdomain notices
 *
 * On WordPress 6.7+, W3TC can trigger _load_textdomain_just_in_time notices
 * before init. This MU plugin loads early and suppresses those notices,
 * keeping logs clean without affecting functionality.
 */
function psp_install_w3tc_notice_silencer() {
    if ( ! defined( 'WP_CONTENT_DIR' ) ) {
        return;
    }

    $mu_dir  = WP_CONTENT_DIR . '/mu-plugins';
    $mu_file = $mu_dir . '/psp-w3tc-notice-silencer.php';

    // Ensure MU plugins directory exists
    if ( ! is_dir( $mu_dir ) ) {
        wp_mkdir_p( $mu_dir );
    }

    $silencer_code = <<<'PHP'
<?php
/**
 * PoolSafe Portal - W3TC Notice Silencer (MU Plugin)
 * 
 * Suppresses _load_textdomain_just_in_time notices for w3-total-cache
 * on WordPress 6.7+ without affecting functionality.
 */
add_filter(
    'doing_it_wrong_trigger_error',
    function ( $trigger, $function, $message ) {
        if (
            $function === '_load_textdomain_just_in_time' &&
            strpos( $message, 'w3-total-cache' ) !== false
        ) {
            return false; // Suppress the notice
        }
        return $trigger;
    },
    1,
    3
);
PHP;

    // Write file if missing or contents differ
    $needs_write = true;
    if ( file_exists( $mu_file ) ) {
        $existing_code = file_get_contents( $mu_file );
        if ( $existing_code === $silencer_code ) {
            $needs_write = false;
        }
    }

    if ( $needs_write ) {
        file_put_contents( $mu_file, $silencer_code );
    }
}

// Install silencer on activation
register_activation_hook( __FILE__, 'psp_install_w3tc_notice_silencer' );

// Ensure silencer exists even if activation hook was skipped
psp_install_w3tc_notice_silencer();

/**
 * Suppress textdomain loading notices on WordPress 6.7+
 * These notices occur when WordPress checks plugin health too early.
 * We suppress notices only for domains that are loaded by WordPress
 * core or other plugins during the initial check, not plugin code.
 */
add_filter(
    'doing_it_wrong_trigger_error',
    function ( $trigger, $function, $message ) {
        // Suppress _load_textdomain_just_in_time notices for known causes
        if ( $function === '_load_textdomain_just_in_time' ) {
            // List of domains that trigger notices during plugin health check
            $allowed_suppress = array(
                'w3-total-cache',    // W3 Total Cache plugin
                'health-check',      // WordPress health check endpoint
                'wp-cerber',         // Security plugins that check early
            );
            
            foreach ( $allowed_suppress as $domain ) {
                if ( strpos( $message, $domain ) !== false ) {
                    return false; // Suppress the notice
                }
            }
        }
        return $trigger;
    },
    1,
    3
);

/**
 * Suppress wp_is_block_theme notices on WordPress 6.8+
 * This function should not be called before theme directory is registered
 * (occurs during plugin health check)
 */
add_filter(
    'doing_it_wrong_trigger_error',
    function ( $trigger, $function, $message ) {
        if ( $function === 'wp_is_block_theme' ) {
            // This notice occurs when called before 'setup_theme' hook
            // Safe to suppress during plugin health checks
            return false;
        }
        return $trigger;
    },
    1,
    3
);

// ============================================================================
// CACHE MANAGEMENT
// ============================================================================

/**
 * Clear all caches on plugin activation
 *
 * Ensures fresh code is served and prevents stale cache issues:
 * - WordPress object cache
 * - W3 Total Cache
 * - WP Fastest Cache
 * - WP Super Cache
 * - Transients
 */
function psp_clear_all_caches() {
    // WordPress object cache
    wp_cache_flush();

    // W3 Total Cache
    if ( function_exists( 'w3tc_flush_all' ) ) {
        w3tc_flush_all();
    }

    // WP Fastest Cache
    if ( class_exists( 'WpFastestCache' ) ) {
        $wpfc = new \WpFastestCache();
        $wpfc->deleteCache( true );
    }

    // WP Super Cache
    if ( function_exists( 'wp_cache_clean_cache' ) ) {
        wp_cache_clean_cache( null );
    }

    // Clear transients
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%'" );

    error_log( '[PoolSafe Portal v2.5.3] All caches cleared on activation' );
}

// Register activation hook to clear caches
register_activation_hook( __FILE__, 'psp_clear_all_caches' );

/**
 * Create activity log table on activation (one-time operation)
 * Avoids repeated dbDelta overhead on every request
 */
register_activation_hook(
    __FILE__,
    function () {
        $activity_logs_file = __DIR__ . '/includes/class-psp-activity-logs.php';
        if ( file_exists( $activity_logs_file ) ) {
            require_once $activity_logs_file;
        }

        if ( class_exists( '\\PSP\\Activity_Logs\\Activity_Logs' ) ) {
            \PSP\Activity_Logs\Activity_Logs::create_table();
        }
    }
);

/**
 * Start session only when needed to avoid breaking page cache/headers.
 * Defaults to starting for logged-in users; can be disabled via the
 * `psp_session_required` filter for environments that forbid PHP sessions.
 */
function psp_session_required() {
    $should_start = is_user_logged_in();
    /**
     * Allow integrators to toggle session usage.
     *
     * @param bool $should_start Whether to start the PHP session.
     */
    return (bool) apply_filters( 'psp_session_required', $should_start );
}

function psp_maybe_start_session() {
    if ( session_status() === PHP_SESSION_ACTIVE ) {
        return;
    }

    if ( headers_sent() ) {
        return;
    }

    if ( ! psp_session_required() ) {
        return;
    }

    // Use cache-friendly limiter when sessions are enabled
    if ( function_exists( 'session_cache_limiter' ) ) {
        @session_cache_limiter( 'private_no_expire' );
    }

    @session_start();
}

// Start sessions late in bootstrap to keep caches effective
add_action( 'init', 'psp_maybe_start_session', 1 );

/**
 * Register custom cron schedule for plugins expecting "3600secs"
 * Provides compatibility with older code using non-standard intervals
 */
add_filter(
    'cron_schedules',
    function ( $schedules ) {
        if ( ! isset( $schedules['3600secs'] ) ) {
            $schedules['3600secs'] = array(
                'interval' => 3600,
                'display'  => 'Once Hourly (3600secs)',
            );
        }
        return $schedules;
    }
);

// ============================================================================
// CORE PLUGIN CLASSES (ESSENTIAL - ALWAYS LOADED)
// ============================================================================

// Admin & Status
require_once __DIR__ . '/includes/class-psp-admin-status.php';

// Authentication & Users
require_once __DIR__ . '/includes/class-psp-auth.php';
require_once __DIR__ . '/includes/class-psp-connection-tester.php';
require_once __DIR__ . '/includes/class-psp-roles.php';
require_once __DIR__ . '/includes/class-psp-company-users.php';
require_once __DIR__ . '/includes/class-psp-user-management.php';

// Portal Features
require_once __DIR__ . '/includes/class-psp-partners.php';
require_once __DIR__ . '/includes/class-psp-tickets.php';
require_once __DIR__ . '/includes/class-psp-ticket-replies.php';
require_once __DIR__ . '/includes/class-psp-ticket-assignment.php';
require_once __DIR__ . '/includes/class-psp-search-filters.php';
require_once __DIR__ . '/includes/class-psp-calendar.php';
require_once __DIR__ . '/includes/class-psp-frontend.php';
require_once __DIR__ . '/includes/class-psp-admin.php';

// Enhanced Features (Logging & Monitoring)
require_once __DIR__ . '/includes/class-psp-email-notifications.php';
require_once __DIR__ . '/includes/class-psp-notifications.php';
require_once __DIR__ . '/includes/class-psp-audit-logger.php';
require_once __DIR__ . '/includes/class-psp-api-logger.php';
require_once __DIR__ . '/includes/class-psp-sla.php';

// Training Videos System
require_once __DIR__ . '/includes/class-psp-training-videos.php';

// Activity Logs System
require_once __DIR__ . '/includes/class-psp-activity-logs.php';

// CSV Export System
require_once __DIR__ . '/includes/class-psp-csv-export.php';

// Notification System
require_once __DIR__ . '/includes/class-psp-notification-system.php';

// Partner Location Map System
require_once __DIR__ . '/includes/class-psp-partner-map.php';

// Performance Enhancements (CSP, Preload, AVIF support, Brotli hints)
require_once __DIR__ . '/includes/class-psp-performance-enhancements.php';

// Health Check Endpoint
require_once __DIR__ . '/includes/class-psp-health-check.php';

// Input Validation & Security
if ( file_exists( __DIR__ . '/includes/class-psp-input-fields.php' ) ) {
    require_once __DIR__ . '/includes/class-psp-input-fields.php';
}
if ( file_exists( __DIR__ . '/includes/class-psp-input-validation.php' ) ) {
    require_once __DIR__ . '/includes/class-psp-input-validation.php';
}

// API, Shortcodes & Performance
require_once __DIR__ . '/includes/models/class-company.php';
require_once __DIR__ . '/includes/api/class-api-response.php';
require_once __DIR__ . '/includes/api/class-api-router.php';

// Views & Frontend Components (Phase 4)
if ( file_exists( __DIR__ . '/views/components/helpers.php' ) ) {
    require_once __DIR__ . '/views/components/helpers.php';
}

require_once __DIR__ . '/includes/class-psp-rest.php';
require_once __DIR__ . '/includes/class-psp-portal-api.php';
require_once __DIR__ . '/includes/class-psp-shortcode-portal.php';
require_once __DIR__ . '/includes/class-psp-shortcodes.php';
require_once __DIR__ . '/includes/class-psp-videos.php';
require_once __DIR__ . '/includes/class-psp-cache-manager.php';
require_once __DIR__ . '/includes/class-psp-query-optimizer.php';

// ============================================================================
// ENQUEUE SCRIPTS & STYLES
// ============================================================================
// Frontend and admin assets are now fully handled in CSP-compliant classes:
// - Frontend: PSP\Frontend::enqueue_assets()
// - Admin:    handled within admin classes without legacy inline assets
// This prevents duplicate enqueues and avoids referencing removed legacy files.

/**
 * Load plugin translations
 * Enables translation of all __() and _e() strings
 */
add_action( 'init', function() {
    load_plugin_textdomain( PSP_TEXT_DOMAIN, false, PSP_LOCALE_DIR );
} );

// ============================================================================
// SETTINGS & CONFIGURATION
// ============================================================================

// Role-Based Access Control
require_once __DIR__ . '/includes/class-psp-rbac-manager.php';

// Settings Management
require_once __DIR__ . '/includes/class-psp-settings-page.php';
require_once __DIR__ . '/includes/class-psp-settings-backup.php';
require_once __DIR__ . '/includes/class-psp-api-failure-alerter.php';

/**
 * Initialize REST API, performance optimizations, and settings on plugins_loaded
 * This hook fires after all plugins are loaded, ensuring dependencies are available
 */
add_action(
    'plugins_loaded',
    function () {
        new PSP_Portal_API();
        new PSP_Videos();
        PSP\Cache_Manager::init();
        PSP\RBAC_Manager::init();
        PSP\Settings_Page::init();
        PSP\Settings_Backup::init();
        PSP\API_Failure_Alerter::init();
    }
);

// ============================================================================
// INTEGRATIONS (OPTIONAL - ENABLE/DISABLE AS NEEDED)
// ============================================================================

// HubSpot CRM Integration
require_once __DIR__ . '/includes/class-psp-hubspot.php';
require_once __DIR__ . '/includes/class-psp-hubspot-settings.php';

// Azure AD Single Sign-On (REQUIRED for support staff Outlook login)
require_once __DIR__ . '/includes/class-psp-azure-auth.php';
require_once __DIR__ . '/includes/class-psp-azure-ad.php';

// Partner Import & Management
require_once __DIR__ . '/includes/bulk-import-partners.php';
require_once __DIR__ . '/includes/class-psp-bulk-import-admin.php';
require_once __DIR__ . '/includes/class-psp-support-dashboard.php';
require_once __DIR__ . '/includes/class-psp-partner-csv-import.php';
// Note: class-psp-csv-partner-import.php removed - duplicate of Partner_CSV_Import (consolidated into class-psp-partner-csv-import.php)

// Email Integration
require_once __DIR__ . '/includes/class-psp-email-to-ticket.php';

// ============================================================================
// PLUGIN INITIALIZATION & ORCHESTRATION
// ============================================================================

// Plugin lifecycle and initialization
require_once __DIR__ . '/includes/class-psp-wordpress-activation.php';
require_once __DIR__ . '/includes/class-psp-rest-api-standards.php';
require_once __DIR__ . '/includes/class-psp-logger.php';
require_once __DIR__ . '/includes/class-psp-query-cache.php';
require_once __DIR__ . '/includes/class-psp-activator.php';
require_once __DIR__ . '/includes/class-psp-db-migrator.php';
require_once __DIR__ . '/includes/class-psp-plugin.php';

/**
 * Load Composer autoloader if available
 * Optional for production (classes are manually required above)
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Load environment variables from .env file if present
 * Uses Composer's dotenv library to avoid fatal errors on hosts without .env
 */
if ( file_exists( __DIR__ . '/.env' ) ) {
    try {
        if ( class_exists( 'Dotenv\Dotenv' ) ) {
            $dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
            $dotenv->load();
        }
    } catch ( Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[PoolSafe Portal] Dotenv load skipped: ' . $e->getMessage() );
        }
    }
}

use PSP\Plugin;
use PSP\Shortcodes;
use PSP\REST;

/**
 * Plugin lifecycle hooks - WordPress Standards Compliance
 * 
 * Activation: Creates tables, roles, capabilities, and schedules cron jobs
 * Deactivation: Unschedules cron jobs and clears temporary cache
 * NOTE: Database and user data are preserved on deactivation for re-activation
 */
register_activation_hook( __FILE__, array( 'PSP_WordPress_Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PSP_WordPress_Activation', 'deactivate' ) );

/**
 * Initialize all plugin components on plugins_loaded
 *
 * This consolidated hook ensures:
 * - All plugins are fully loaded
 * - WordPress is fully initialized
 * - Dependencies are available
 * - Execution order is predictable
 */
add_action(
    'plugins_loaded',
    function () {
        error_log( '[PSP] plugins_loaded hook firing at ' . date( 'Y-m-d H:i:s' ) );
        try {
        // Company authentication system
        require_once __DIR__ . '/includes/class-psp-db-schema.php';
        error_log( '[PSP] class-psp-db-schema.php loaded' );
        require_once __DIR__ . '/includes/class-psp-company-auth.php';
        error_log( '[PSP] class-psp-company-auth.php loaded' );
        require_once __DIR__ . '/includes/class-psp-support-auth.php';
        error_log( '[PSP] class-psp-support-auth.php loaded' );
        require_once __DIR__ . '/includes/class-psp-portal.php';
        error_log( '[PSP] class-psp-portal.php loaded' );
        require_once __DIR__ . '/includes/class-psp-company-admin.php';
        error_log( '[PSP] class-psp-company-admin.php loaded' );
        
        \PSP\Company_Auth::init();
        error_log( '[PSP] Company_Auth::init() called' );
        \PSP\Support_Auth::init();
        error_log( '[PSP] Support_Auth::init() called' );
        // Legacy SPA portal disabled in favor of server-rendered Astra-aware shortcode
        // \PSP\Portal::init();
        // error_log( '[PSP] Portal::init() called' );
        \PSP\Company_Admin::init();
        error_log( '[PSP] Company_Admin::init() called' );

        // Initialize Partner CSV Import
        \PSP\Partner_CSV_Import::init();
        error_log( '[PSP] Partner_CSV_Import::init() called' );

        // Initialize REST API
        REST::init();

        // Initialize legacy shortcodes (if needed)
        Shortcodes::init();

        // Initialize integrations
        PSP_HubSpot::init();
        PSP_API_Logger::init();

        // Initialize input validation if available
        if ( class_exists( 'PSP_Input_Fields' ) ) {
            PSP_Input_Fields::init();
        }

        // Initialize core plugin and register custom post types
        Plugin::init();

        // Run orchestrator to register CPTs and capabilities
        error_log( '[PSP] Creating Plugin orchestrator' );
        $plugin = new Plugin();
        error_log( '[PSP] Plugin instance created, calling run()' );
        $plugin->run();
        error_log( '[PSP] Plugin::run() completed successfully' );
        
        // Clean expired sessions daily
        if (!wp_next_scheduled('psp_clean_sessions')) {
            wp_schedule_event(time(), 'daily', 'psp_clean_sessions');
        }
        error_log( '[PSP] plugins_loaded hook completed successfully' );
        } catch ( Exception $e ) {
            error_log( '[PSP FATAL] Exception in plugins_loaded: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() );
            error_log( '[PSP FATAL] Stack trace: ' . $e->getTraceAsString() );
            throw $e;
        }
    }
);

add_action('psp_clean_sessions', function() {
    \PSP\DB_Schema::clean_expired_sessions();
});
