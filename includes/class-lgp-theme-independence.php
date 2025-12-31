<?php
/**
 * Theme Independence & Isolation
 *
 * Ensures the plugin operates completely independent of the WordPress theme.
 * Handles login redirects, admin bar hiding, and portal rendering.
 *
 * @package LounGenie Portal
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Theme_Independence {

	/**
	 * Initialize theme independence
	 */
	public static function init() {
		// Remove theme dependencies
		add_action( 'template_redirect', array( __CLASS__, 'intercept_portal_requests' ), 1 );
		
		// Login redirects for portal roles
		add_filter( 'login_redirect', array( __CLASS__, 'portal_login_redirect' ), 10, 3 );
		
		// Hide admin bar for portal roles
		add_filter( 'show_admin_bar', array( __CLASS__, 'hide_admin_bar_for_portal_roles' ), 20 );
		
		// Remove admin bar completely for portal roles
		add_action( 'init', array( __CLASS__, 'disable_admin_bar_for_portal_roles' ) );
		
		// Block wp-admin access for portal roles
		add_action( 'admin_init', array( __CLASS__, 'block_wp_admin_access' ) );
		
		// Enqueue plugin-controlled assets only
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_portal_assets' ), 999 );
	}

	/**
	 * Intercept portal page requests and render with plugin template
	 */
	public static function intercept_portal_requests() {
		global $wp;
		
		// Check if this is a portal request
		$portal_slugs = array( 'portal', 'partner-portal', 'support-portal' );
		
		foreach ( $portal_slugs as $slug ) {
			if ( isset( $wp->query_vars['pagename'] ) && $wp->query_vars['pagename'] === $slug ) {
				self::render_portal_shell();
				exit;
			}
		}
	}

	/**
	 * Render portal with plugin's own shell (no theme)
	 */
	public static function render_portal_shell() {
		// Verify user access
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url( '/portal' ) ) );
			exit;
		}

		$user = wp_get_current_user();
		
		// Check if user has portal access
		$portal_roles = array( 'lg_partner', 'lg_support', 'administrator' );
		$has_access = false;
		
		foreach ( $portal_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				$has_access = true;
				break;
			}
		}

		if ( ! $has_access ) {
			wp_die( esc_html__( 'You do not have permission to access the portal.', 'loungenie-portal' ) );
		}

		// Load portal shell template (theme-independent)
		$template_path = LGP_PLUGIN_DIR . 'templates/portal-shell.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			wp_die( esc_html__( 'Portal template not found.', 'loungenie-portal' ) );
		}
	}

	/**
	 * Redirect portal roles directly to /portal on login
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $request URL the user is coming from.
	 * @param WP_User|WP_Error $user User object or error.
	 * @return string Redirect URL.
	 */
	public static function portal_login_redirect( $redirect_to, $request, $user ) {
		// Check if user object is valid
		if ( ! isset( $user->ID ) || ! isset( $user->roles ) ) {
			return $redirect_to;
		}

		// Portal roles that should bypass wp-admin
		$portal_roles = array( 'lg_partner', 'lg_support' );
		
		// Check if user has a portal role
		foreach ( $portal_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return home_url( '/portal' );
			}
		}

		return $redirect_to;
	}

	/**
	 * Hide admin bar for portal roles
	 *
	 * @param bool $show_admin_bar Whether to show admin bar.
	 * @return bool
	 */
	public static function hide_admin_bar_for_portal_roles( $show_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return $show_admin_bar;
		}

		$user = wp_get_current_user();
		$portal_roles = array( 'lg_partner', 'lg_support' );
		
		foreach ( $portal_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return false;
			}
		}

		return $show_admin_bar;
	}

	/**
	 * Disable admin bar completely for portal roles
	 */
	public static function disable_admin_bar_for_portal_roles() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();
		$portal_roles = array( 'lg_partner', 'lg_support' );
		
		foreach ( $portal_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				add_filter( 'show_admin_bar', '__return_false' );
				remove_action( 'wp_head', '_admin_bar_bump_cb' );
				break;
			}
		}
	}

	/**
	 * Block wp-admin access for portal roles
	 */
	public static function block_wp_admin_access() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Allow AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$user = wp_get_current_user();
		$portal_roles = array( 'lg_partner', 'lg_support' );
		
		foreach ( $portal_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				wp_safe_redirect( home_url( '/portal' ) );
				exit;
			}
		}
	}

	/**
	 * Enqueue plugin-controlled assets (override theme)
	 */
	public static function enqueue_portal_assets() {
		// Only on portal pages
		if ( ! self::is_portal_page() ) {
			return;
		}

		// Remove theme styles on portal pages
		global $wp_styles;
		if ( isset( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				// Remove theme stylesheets but keep essential WordPress styles
				if ( strpos( $handle, 'theme' ) !== false || strpos( $handle, get_template() ) !== false ) {
					wp_dequeue_style( $handle );
				}
			}
		}

		// Enqueue plugin's core design tokens
		wp_enqueue_style(
			'lgp-core-tokens',
			LGP_ASSETS_URL . 'css/lgp-core-tokens.css',
			array(),
			LGP_VERSION
		);

		// Enqueue portal styles
		wp_enqueue_style(
			'lgp-portal-shell',
			LGP_ASSETS_URL . 'css/portal-shell.css',
			array( 'lgp-core-tokens' ),
			LGP_VERSION
		);
	}

	/**
	 * Check if current page is a portal page
	 *
	 * @return bool
	 */
	private static function is_portal_page() {
		global $wp;
		
		$portal_slugs = array( 'portal', 'partner-portal', 'support-portal' );
		
		if ( isset( $wp->query_vars['pagename'] ) ) {
			return in_array( $wp->query_vars['pagename'], $portal_slugs, true );
		}
		
		return false;
	}
}

// Initialize
LGP_Theme_Independence::init();
