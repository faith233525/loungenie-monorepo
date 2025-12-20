<?php
/**
 * Central Plugin Initialization Loader
 * Single point of orchestration for all plugin components
 * Ensures predictable startup order and no race conditions
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Loader {

	/**
	 * Initialize all plugin components in dependency order
	 *
	 * Order matters:
	 * 1. Database (creates tables if needed)
	 * 2. Security (headers, CSP)
	 * 3. Capabilities (role → capability mapping)
	 * 4. Authentication (relies on capabilities)
	 * 5. Router (relies on auth)
	 * 6. Assets (relies on router, auth)
	 * 7. REST API (relies on auth, capabilities)
	 * 8. Integrations (rely on everything above)
	 */
	public static function init() {
		// Phase 1: Foundation
		LGP_Database::init();      // Schema, migrations
		LGP_Security::init();      // CSP headers, CORS
		LGP_Capabilities::init();  // Capability registry

		// Phase 2: Core
		LGP_Auth::init();          // Auth checks (uses capabilities)
		LGP_Router::init();        // Route handling (uses auth)
		LGP_Assets::init();        // Asset enqueue (conditional on route/auth)

		// Phase 3: APIs & Logging
		LGP_Logger::init();        // Audit logging
		LGP_Notifications::init(); // Alert system
		LGP_Company_Colors::init(); // Color aggregation (Phase 2B)
		self::register_rest_apis();

		// Phase 4: Features (optional, independently initialized)
		if ( ! self::use_new_email_pipeline() ) {
			// Legacy POP3/Graph hybrid handler
			LGP_Email_Handler::init();
		}
		LGP_Microsoft_SSO::init();
		LGP_HubSpot::init();
		LGP_Outlook::init();
		LGP_System_Health::init();

		// Admin Tools
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-lgp-role-switcher.php';
		}
	}

	/**
	 * Register all REST API endpoints
	 * Centralized so we can version and extend easily
	 *
	 * Note: service-notes.php and audit-log.php self-register via add_action
	 */
	private static function register_rest_apis() {
		// Core API endpoints
		if ( class_exists( 'LGP_Companies_API' ) ) {
			LGP_Companies_API::init();
		}
		if ( class_exists( 'LGP_Units_API' ) ) {
			LGP_Units_API::init();
		}
		if ( class_exists( 'LGP_Tickets_API' ) ) {
			LGP_Tickets_API::init();
		}
		if ( class_exists( 'LGP_Gateways_API' ) ) {
			LGP_Gateways_API::init();
		}
		if ( class_exists( 'LGP_Help_Guides_API' ) ) {
			LGP_Help_Guides_API::init();
		}
		if ( class_exists( 'LGP_Attachments_API' ) ) {
			LGP_Attachments_API::init();
		}

		// Feature API endpoints - with file existence checks
		if ( file_exists( LGP_PLUGIN_DIR . 'api/dashboard.php' ) ) {
			require_once LGP_PLUGIN_DIR . 'api/dashboard.php';
			if ( class_exists( 'LGP_Dashboard_API' ) ) {
				LGP_Dashboard_API::init();
			}
		}

		if ( file_exists( LGP_PLUGIN_DIR . 'api/map.php' ) ) {
			require_once LGP_PLUGIN_DIR . 'api/map.php';
			if ( class_exists( 'LGP_Map_API' ) ) {
				LGP_Map_API::init();
			}
		}

		// Service Notes and Audit Log self-register when loaded (functional approach)
	}

	/**
	 * Get current schema version
	 */
	public static function get_schema_version() {
		return get_option( 'lgp_schema_version', '1.0.0' );
	}

	/**
	 * Check if migrations are needed
	 */
	public static function needs_migration() {
		$current = self::get_schema_version();
		return version_compare( $current, LGP_VERSION, '<' );
	}

	/**
	 * Log initialization for debugging
	 */
	public static function log_init_event( $component, $status = 'ok', $message = '' ) {
		if ( defined( 'LGP_DEBUG' ) && LGP_DEBUG ) {
			// eslint-disable-next-line no-console
			error_log( "LGP_Loader: [{$component}] {$status}" . ( $message ? " - {$message}" : '' ) );
		}
	}

	/**
	 * Determine if the new Graph-based email pipeline is enabled.
	 * Priority: Constant > Env var > Option.
	 */
	private static function use_new_email_pipeline() {
		// Constant override
		if ( defined( 'LGP_EMAIL_PIPELINE' ) ) {
			return 'new' === LGP_EMAIL_PIPELINE || true === LGP_EMAIL_PIPELINE || 1 === LGP_EMAIL_PIPELINE;
		}

		// Env var
		$env = getenv( 'LGP_EMAIL_PIPELINE' );
		if ( $env ) {
			$env = strtolower( trim( $env ) );
			return in_array( $env, array( 'new', 'true', '1', 'on' ), true );
		}

		// Option flag
		if ( function_exists( 'get_option' ) ) {
			return (bool) get_option( 'lgp_use_new_email_pipeline', false );
		}

		return false;
	}
}
