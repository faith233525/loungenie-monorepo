<?php
/**
 * LounGenie Portal - Enterprise SaaS Partner Management System
 *
 * @package   LounGenie Portal
 * @version   1.0.0
 * @author    LounGenie Team
 * @license   GPL-2.0-or-later
 *
 * Plugin Name: LounGenie Portal
 * Plugin URI: https://loungenie.com/portal
 * Description: Commercial enterprise SaaS portal for LounGenie partner and support management
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: LounGenie Team
 * Author URI: https://loungenie.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loungenie-portal
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// PLUGIN CONSTANTS
// ============================================================================

define( 'LGP_VERSION', '1.0.0' );
define( 'LGP_PLUGIN_FILE', __FILE__ );
define( 'LGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LGP_ASSETS_URL', LGP_PLUGIN_URL . 'assets/' );
define( 'LGP_TEXT_DOMAIN', 'loungenie-portal' );

// ============================================================================
// PREFLIGHT CHECKS
// ============================================================================

/**
 * Check PHP and WordPress version compatibility
 */
function lgp_check_compatibility() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="notice notice-error"><p><strong>LounGenie Portal:</strong> PHP 7.4 or higher is required.</p></div>';
			}
		);
		return false;
	}

	if ( version_compare( $wp_version, '5.8', '<' ) ) {
		add_action(
			'admin_notices',
			function() {
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
// ACTIVATION & DEACTIVATION
// ============================================================================

/**
 * Plugin activation
 * - Create database tables
 * - Register custom roles
 * - Set default capabilities
 */
function lgp_activate() {
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-database.php';
	require_once LGP_PLUGIN_DIR . 'roles/support.php';
	require_once LGP_PLUGIN_DIR . 'roles/partner.php';

	// Create database tables
	LGP_Database::create_tables();

	// Register custom roles
	LGP_Support_Role::register();
	LGP_Partner_Role::register();

	// Flush rewrite rules for custom routes
	flush_rewrite_rules();
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

	// Remove custom roles
	LGP_Support_Role::remove();
	LGP_Partner_Role::remove();

	// Flush rewrite rules
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'lgp_deactivate' );

// ============================================================================
// INITIALIZE PLUGIN
// ============================================================================

/**
 * Initialize all plugin components
 */
function lgp_init() {
	// Load core classes
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-database.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-router.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-auth.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-assets.php';

	// Load enterprise features (backported from PoolSafe Portal v3.3.0)
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-cache.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-security.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-microsoft-sso.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-logger.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-notifications.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-geocode.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-gateway.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-training-video.php';

	// Load integration classes
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-hubspot.php';
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-outlook.php';

	// Load monitoring/admin classes
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-system-health.php';

	// Load API endpoints
	require_once LGP_PLUGIN_DIR . 'api/companies.php';
	require_once LGP_PLUGIN_DIR . 'api/units.php';
	require_once LGP_PLUGIN_DIR . 'api/tickets.php';
	require_once LGP_PLUGIN_DIR . 'api/gateways.php';
	require_once LGP_PLUGIN_DIR . 'api/training-videos.php';
	require_once LGP_PLUGIN_DIR . 'api/attachments.php';
	require_once LGP_PLUGIN_DIR . 'api/service-notes.php';
	require_once LGP_PLUGIN_DIR . 'api/audit-log.php';

	// Load role definitions
	require_once LGP_PLUGIN_DIR . 'roles/support.php';
	require_once LGP_PLUGIN_DIR . 'roles/partner.php';

	// Initialize router for /portal route
	LGP_Router::init();

	// Initialize authentication system
	LGP_Auth::init();

	// Initialize asset management
	LGP_Assets::init();

	// Initialize enterprise features
	// Note: Security headers initialized via plugins_loaded hook in class
	// Cache and SSO initialized via their own hooks

	// Initialize integrations
	LGP_HubSpot::init();
	LGP_Outlook::init();

	// Initialize monitoring/admin
	LGP_System_Health::init();

	// Initialize REST API endpoints
	LGP_Companies_API::init();
	LGP_Units_API::init();
	LGP_Tickets_API::init();
	LGP_Gateways_API::init();
}

add_action( 'plugins_loaded', 'lgp_init' );

// ============================================================================
// CUSTOM REWRITE RULES
// ============================================================================

/**
 * Add custom rewrite rules for /portal route
 */
function lgp_add_rewrite_rules() {
	add_rewrite_rule( '^portal/?$', 'index.php?lgp_portal=1', 'top' );
	add_rewrite_rule( '^portal/(.+)/?$', 'index.php?lgp_portal=1&lgp_section=$matches[1]', 'top' );
}

add_action( 'init', 'lgp_add_rewrite_rules' );

/**
 * Redirect root domain to /portal
 * - Applies to any request hitting the site root ('/') on the frontend
 * - Skips admin, login, REST, callback, sitemap/robots, and existing portal paths
 */
function lgp_redirect_root_to_portal() {
	if ( is_admin() ) {
		return; // never redirect wp-admin
	}

	// Current request path (no query string)
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? strtok( $_SERVER['REQUEST_URI'], '?' ) : '/';
	$request_uri = trailingslashit( $request_uri );

	// Exclusions: don't interfere with these paths
	$excluded_prefixes = array(
		'/portal/',
		'/wp-login.php/',
		'/wp-json/',
		'/psp-azure-callback/',
		'/xmlrpc.php/',
		'/feed/',
		'/sitemap', // includes variations
	);

	foreach ( $excluded_prefixes as $prefix ) {
		if ( 0 === strpos( $request_uri, $prefix ) ) {
			return;
		}
	}

	// Also skip robots and favicon
	if ( '/robots.txt/' === $request_uri || '/favicon.ico/' === $request_uri ) {
		return;
	}

	// If this is the root/front request, always redirect to /portal (regardless of login state)
	if ( '/' === $request_uri || is_front_page() || is_home() ) {
		// Avoid redirect loop if home_url already ends with /portal
		$target = home_url( '/portal' );
		$current = home_url( $request_uri );
		if ( trailingslashit( $current ) !== trailingslashit( $target ) ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'lgp_redirect_root_to_portal', 0 );

/**
 * Add query vars for portal routing
 */
function lgp_query_vars( $vars ) {
	$vars[] = 'lgp_portal';
	$vars[] = 'lgp_section';
	return $vars;
}

add_filter( 'query_vars', 'lgp_query_vars' );
