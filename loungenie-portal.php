<?php

/**
 * LounGenie Portal - Enterprise SaaS Partner Management System
 *
 * @package   LounGenie Portal
 * @version   1.8.1
 * @author    LounGenie Team
 * @license   GPL-2.0-or-later
 *
 * Plugin Name: LounGenie Portal
 * Plugin URI: https://loungenie.com/portal
 * Description: Commercial enterprise SaaS portal for LounGenie partner and support management
 * Version: 1.8.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: LounGenie Team
 * Author URI: https://loungenie.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loungenie-portal
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// PLUGIN CONSTANTS.
// ============================================================================

define( 'LGP_VERSION', '1.8.1' );
define( 'LGP_PLUGIN_FILE', __FILE__ );

// Use PHP functions instead of WordPress functions to avoid timing issues during activation.
if ( ! defined( 'LGP_PLUGIN_DIR' ) ) {
	// @phpstan-ignore-next-line trailingslashit is WordPress core function
	define( 'LGP_PLUGIN_DIR', trailingslashit( __DIR__ ) );
}
if ( ! defined( 'LGP_PLUGIN_URL' ) ) {
	// @phpstan-ignore-next-line plugins_url and trailingslashit are WordPress core functions
	define( 'LGP_PLUGIN_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
}
if ( ! defined( 'LGP_ASSETS_URL' ) ) {
	define( 'LGP_ASSETS_URL', LGP_PLUGIN_URL . 'assets/' );
}
define( 'LGP_TEXT_DOMAIN', 'loungenie-portal' );

// ============================================================================
// PREFLIGHT CHECKS.
// ============================================================================

/**
 * Check PHP and WordPress version compatibility.
 */
function lgp_check_compatibility() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>LounGenie Portal:</strong> PHP 7.4 or higher is required.</p></div>';
			}
		);
		return false;
	}

	if ( version_compare( $wp_version, '5.8', '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>LounGenie Portal:</strong> WordPress 5.8 or higher is required.</p></div>';
			}
		);
		return false;
	}

	return true;
}

if ( ! lgp_check_compatibility() ) {
	return;
}

// ============================================================================
// ACTIVATION & DEACTIVATION.
// ============================================================================

/**
 * Plugin activation.
 * - Create database tables.
 * - Register custom roles.
 * - Set default capabilities.
 */
function lgp_activate() {
	// Ensure no output leaks during activation to avoid "unexpected output" notices.
	$activation_ob_level = ob_get_level();
	ob_start();

	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-database.php';
	// Capabilities are required by role registration during activation.
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-capabilities.php';
	require_once LGP_PLUGIN_DIR . 'roles/support.php';
	require_once LGP_PLUGIN_DIR . 'roles/partner.php';

	// Create database tables and register roles.
	LGP_Database::create_tables();
	LGP_Capabilities::register_capabilities();
	LGP_Support_Role::register();
	LGP_Partner_Role::register();

	// Flush rewrite rules. for custom routes.
	flush_rewrite_rules();

	// Swallow any buffered output to keep activation clean. Log for diagnostics if present.
	$activation_output = ob_get_clean();
	while ( ob_get_level() > $activation_ob_level ) {
		ob_end_clean();
	}

	if ( ! empty( $activation_output ) ) {
		error_log( 'LGP activation output suppressed: ' . substr( $activation_output, 0, 500 ) );
	}
}

register_activation_hook( __FILE__, 'lgp_activate' );

/**
 * Plugin deactivation
 * - Remove custom roles
 * - Flush rewrite rules
 */
function lgp_deactivate() {
	require_once LGP_PLUGIN_DIR . 'roles/support.php';
	require_once LGP_PLUGIN_DIR . 'roles/partner.php';

	// Remove custom roles.
	LGP_Support_Role::remove();
	LGP_Partner_Role::remove();

	// Flush rewrite rules.
	// @phpstan-ignore-next-line flush_rewrite_rules is WordPress core function
	flush_rewrite_rules();
}

// @phpstan-ignore-next-line register_activation_hook is WordPress core function
register_deactivation_hook( __FILE__, 'lgp_deactivate' );

// ============================================================================
// INTERNATIONALIZATION
// ============================================================================

/**
 * Load plugin text domain for translations
 */
function lgp_load_textdomain() {
	// @phpstan-ignore-next-line load_plugin_textdomain and plugin_basename are WordPress core functions
	load_plugin_textdomain(
		'loungenie-portal',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'lgp_load_textdomain' );

// ============================================================================
// INITIALIZE PLUGIN
// ============================================================================

/**
 * Initialize all plugin components
 */
function lgp_init() {
	// Load all required classes first
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-loader.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-database.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-router.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-auth.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-assets.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-cache.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-security.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-microsoft-sso.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-microsoft-sso-handler.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-login-handler.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-logger.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-notifications.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-geocode.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-gateway.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-knowledge-guide.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-training-video.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-hubspot.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-outlook.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-system-health.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-attachments.php';
	// Legacy email handler will be conditionally initialized via loader based on feature flag
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-handler.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-capabilities.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-rest-errors.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-file-validator.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-rate-limiter.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-shared-hosting-rules.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-to-ticket.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-company-colors.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-migrations.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-csv-partner-import.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-theme-independence.php';

	// Conditionally load new Graph-based email pipeline
	$use_new_email = false;
	if ( defined( 'LGP_EMAIL_PIPELINE' ) ) {
		$use_new_email = ( 'new' === LGP_EMAIL_PIPELINE || true === LGP_EMAIL_PIPELINE || 1 === LGP_EMAIL_PIPELINE );
	} elseif ( getenv( 'LGP_EMAIL_PIPELINE' ) ) {
		$env           = strtolower( trim( getenv( 'LGP_EMAIL_PIPELINE' ) ) );
		$use_new_email = in_array( $env, array( 'new', 'true', '1', 'on' ), true );
	} else {
		$use_new_email = (bool) get_option( 'lgp_use_new_email_pipeline', false );
	}

	if ( $use_new_email ) {
		// New pipeline components
		require_once LGP_PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
		require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-ingest.php';
		require_once LGP_PLUGIN_DIR . 'includes/class-lgp-email-reply.php';
		require_once LGP_PLUGIN_DIR . 'includes/email-integration.php';
	}

	// Load API endpoints
	require_once LGP_PLUGIN_DIR . 'api/companies.php';
	require_once LGP_PLUGIN_DIR . 'api/units.php';
	require_once LGP_PLUGIN_DIR . 'api/tickets.php';
	require_once LGP_PLUGIN_DIR . 'api/gateways.php';
	require_once LGP_PLUGIN_DIR . 'api/knowledge-center.php';
	require_once LGP_PLUGIN_DIR . 'api/attachments.php';
	require_once LGP_PLUGIN_DIR . 'api/service-notes.php';
	require_once LGP_PLUGIN_DIR . 'api/audit-log.php';
	require_once LGP_PLUGIN_DIR . 'api/dashboard.php';
	require_once LGP_PLUGIN_DIR . 'api/map.php';

	// Load role definitions
	require_once LGP_PLUGIN_DIR . 'roles/support.php';
	require_once LGP_PLUGIN_DIR . 'roles/partner.php';

	// Initialize all components via centralized loader
	LGP_Loader::init();
	// Initialize migrations (versioned schema upgrades)
	LGP_Migrations::init();
}

// Initialize after the theme directory is registered to avoid early core calls
// that may trigger wp_is_block_theme() notices in WordPress >= 6.8.
// after_setup_theme runs after setup_theme and before init, which is safe for our loaders.
add_action( 'after_setup_theme', 'lgp_init', 0 );

// ============================================================================
// CUSTOM REWRITE RULES
// ============================================================================

/**
 * Add custom rewrite rules for /portal route
 */
function lgp_add_rewrite_rules() {
	// @phpstan-ignore-next-line add_rewrite_rule is WordPress core function
	add_rewrite_rule( '^portal/?$', 'index.php?lgp_portal=1', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^portal/(.+)/?$', 'index.php?lgp_portal=1&lgp_section=$matches[1]', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^portal/login/?$', 'index.php?lgp_portal_login=1', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^support-login/?$', 'index.php?lgp_support_login=1', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^partner-login/?$', 'index.php?lgp_partner_login=1', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^psp-azure-callback/?$', 'index.php?lgp_azure_callback=1', 'top' );
	// @phpstan-ignore-next-line
	add_rewrite_rule( '^m365-sso-callback/?$', 'index.php?lgp_m365_callback=1', 'top' );
}

add_action( 'init', 'lgp_add_rewrite_rules' );

/**
 * Redirect root domain to /portal
 * - Applies to any request hitting the site root ('/') on the frontend
 * - Skips admin, login, REST, callback, sitemap/robots, and existing portal paths
 */
function lgp_redirect_root_to_portal() {
	// Never redirect admin, AJAX, or cron requests..
	// @phpstan-ignore-next-line is_admin, wp_doing_ajax, wp_doing_cron are WordPress core functions
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	// Explicit check: don't redirect WordPress admin URLs..
	// @phpstan-ignore-next-line sanitize_text_field and wp_unslash are WordPress core functions
	$raw_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$raw_uri         = strtok( $raw_request_uri, '?' );
	if ( 0 === strpos( $raw_uri, '/wp-admin' ) || 0 === strpos( $raw_uri, '/wp-login.php' ) ) {
		return; // Don't interfere with WordPress admin.
	}

	// Current request path (no query string)..
	// @phpstan-ignore-next-line sanitize_text_field and wp_unslash are WordPress core functions
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( strtok( $_SERVER['REQUEST_URI'], '?' ) ) ) : '/';
	// @phpstan-ignore-next-line trailingslashit is WordPress core function
	$request_uri = trailingslashit( $request_uri );

	// Exclusions: don't interfere with these paths..
	$excluded_prefixes = array(
		'/portal/',
		'/wp-admin/',
		'/wp-login.php/',
		'/wp-json/',
		'/psp-azure-callback/',
		'/m365-sso-callback/',
		'/xmlrpc.php/',
		'/feed/',
		'/sitemap', // includes variations
	);

	foreach ( $excluded_prefixes as $prefix ) {
		if ( 0 === strpos( $request_uri, $prefix ) ) {
			return;
		}
	}

	// Also skip robots and favicon..
	if ( '/robots.txt/' === $request_uri || '/favicon.ico/' === $request_uri ) {
		return;
	}

	// If this is the root/front request, always redirect to /portal (regardless of login state)..
	// @phpstan-ignore-next-line is_front_page and is_home are WordPress core functions
	if ( '/' === $request_uri || is_front_page() || is_home() ) {
		// Avoid redirect loop if home_url already ends with /portal.
		// @phpstan-ignore-next-line home_url is WordPress core function
		$target = home_url( '/portal' );
		// @phpstan-ignore-next-line home_url is WordPress core function
		$current = home_url( $request_uri );
		// @phpstan-ignore-next-line trailingslashit is WordPress core function
		if ( trailingslashit( $current ) !== trailingslashit( $target ) ) {
			// @phpstan-ignore-next-line wp_safe_redirect is WordPress core function
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'lgp_redirect_root_to_portal', 0 );

/**
 * Add query vars for portal routing
 *
 * @param array $vars Query vars.
 * @return array
 */
function lgp_query_vars( $vars ) {
	$vars[] = 'lgp_portal';
	$vars[] = 'lgp_section';
	$vars[] = 'lgp_portal_login';
	$vars[] = 'lgp_support_login';
	$vars[] = 'lgp_partner_login';
	$vars[] = 'lgp_azure_callback';
	$vars[] = 'lgp_m365_callback';
	return $vars;
}

// @phpstan-ignore-next-line add_filter is WordPress core function
add_filter( 'query_vars', 'lgp_query_vars' );
