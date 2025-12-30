<?php
/**
 * Custom Login Handler Class
 *
 * Handles authentication for both partner (WordPress) and support (SSO) roles
 * Implements security best practices:
 * - Nonce validation on all login actions
 * - Secure error messaging (no user enumeration)
 * - Graceful handling of failed logins
 * - Role-based capability checks
 * - Company-level data isolation
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

namespace LounGenie\Portal;

class Login_Handler {

	/**
	 * Hook prefix for this handler
	 */
	const HOOK_PREFIX = 'lgp_';

	/**
	 * Nonce action names
	 */
	const NONCE_PARTNER = 'lgp_partner_login';
	const NONCE_SSO     = 'lgp_sso_login';
	const NONCE_LOGOUT  = 'lgp_logout';

	/**
	 * Login page slug
	 */
	const LOGIN_PAGE_SLUG = 'lgp-login';

	/**
	 * Initialize the login handler
	 */
	public static function init() {
		// Register custom login page
		add_action( 'init', array( __CLASS__, 'register_login_page' ) );

		// Handle form submissions
		add_action( 'init', array( __CLASS__, 'handle_partner_login' ) );
		add_action( 'init', array( __CLASS__, 'handle_sso_login' ) );

		// Hook into WordPress login redirection
		add_filter( 'login_url', array( __CLASS__, 'custom_login_url' ) );
		add_filter( 'wp_login_url', array( __CLASS__, 'custom_login_url' ) );

		// Redirect to custom login page
		add_action( 'login_init', array( __CLASS__, 'redirect_to_custom_login' ) );

		// Handle logout
		add_action( 'wp_logout', array( __CLASS__, 'handle_logout' ) );

		// Add login page body class
		add_filter( 'body_class', array( __CLASS__, 'add_login_body_class' ) );

		// Add login redirect based on role
		add_filter( 'login_redirect', array( __CLASS__, 'redirect_on_role' ), 10, 3 );
	}

	/**
	 * Register custom login page template
	 */
	public static function register_login_page() {
		// Custom login page via custom URL parameter
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'login' ) {
			self::load_custom_login_template();
			exit;
		}
	}

	/**
	 * Load custom login template
	 */
	public static function load_custom_login_template() {
		$template = LGP_PLUGIN_DIR . 'templates/custom-login.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Handle partner (WordPress) login
	 */
	public static function handle_partner_login() {
		// Check if this is a partner login attempt
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'lgp_partner_login' ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['lgp_login_nonce'] ) ||
			! wp_verify_nonce( $_POST['lgp_login_nonce'], self::NONCE_PARTNER ) ) {
			self::redirect_with_error( 'invalid_nonce', 'partner' );
			return;
		}

		// Sanitize and validate inputs
		$user_login    = isset( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '';
		$user_password = isset( $_POST['user_password'] ) ? $_POST['user_password'] : '';
		$remember      = isset( $_POST['rememberme'] ) ? true : false;
		$redirect_to   = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : admin_url( '' );

		// Validate inputs
		if ( empty( $user_login ) || empty( $user_password ) ) {
			self::redirect_with_error( 'invalid_credentials', 'partner', $redirect_to );
			return;
		}

		// Attempt to authenticate
		$user = wp_authenticate( $user_login, $user_password );

		// Check for authentication errors
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();

			// Security: Don't reveal specific error (no user enumeration)
			self::redirect_with_error( 'invalid_credentials', 'partner', $redirect_to );

			// Log the actual error for debugging
			do_action( 'lgp_login_failed', $error_code, $user_login );
			return;
		}

		// Verify user has partner role
		if ( ! self::user_has_role( $user->ID, 'partner' ) ) {
			self::redirect_with_error( 'invalid_role', 'partner', $redirect_to );
			do_action( 'lgp_unauthorized_login', $user->ID, 'partner' );
			return;
		}

		// Check if user account is active (custom meta flag)
		$is_active = get_user_meta( $user->ID, 'lgp_account_active', true );
		if ( $is_active === '0' || ( empty( $is_active ) && ! current_user_can( 'manage_options' ) ) ) {
			self::redirect_with_error( 'account_disabled', 'partner', $redirect_to );
			do_action( 'lgp_disabled_account_login', $user->ID );
			return;
		}

		// Set session cookie
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $remember );

		// Update last login time
		update_user_meta( $user->ID, 'lgp_last_login', current_time( 'mysql' ) );

		// Log successful login
		do_action( 'lgp_login_success', $user->ID, 'partner' );

		// Redirect based on user role and settings
		$redirect = self::get_role_redirect( $user->ID, $redirect_to );
		wp_safe_remote_post(
			wp_login_url(),
			array(
				'blocking'  => false,
				'sslverify' => false,
			)
		);

		wp_redirect( apply_filters( 'login_redirect', $redirect, $redirect_to, $user ) );
		exit;
	}

	/**
	 * Handle SSO (Microsoft) login
	 */
	public static function handle_sso_login() {
		// Check if this is an SSO login attempt
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'lgp_microsoft_sso' ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['lgp_sso_nonce'] ) ||
			! wp_verify_nonce( $_POST['lgp_sso_nonce'], self::NONCE_SSO ) ) {
			self::redirect_with_error( 'invalid_nonce', 'support' );
			return;
		}

		// Get Microsoft SSO instance
		$microsoft_sso = Microsoft_SSO::get_instance();

		if ( ! $microsoft_sso ) {
			self::redirect_with_error( 'sso_failed', 'support' );
			return;
		}

		// Store redirect URL for use after authentication
		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : admin_url( '' );
		set_transient( 'lgp_sso_redirect_' . wp_get_current_user()->ID, $redirect_to, HOUR_IN_SECONDS );

		// Initiate Microsoft SSO flow
		$microsoft_sso->authenticate();
	}

	/**
	 * Handle SSO callback (processed by Microsoft_SSO class)
	 */
	public static function handle_sso_callback( $user_data ) {
		// Validate user data
		if ( empty( $user_data['email'] ) || empty( $user_data['name'] ) ) {
			self::redirect_with_error( 'sso_failed', 'support' );
			return;
		}

		// Try to get or create user
		$user = self::get_or_create_sso_user( $user_data );

		if ( is_wp_error( $user ) ) {
			self::redirect_with_error( 'user_not_found', 'support' );
			return;
		}

		// Verify user has support role
		if ( ! self::user_has_role( $user->ID, 'support' ) ) {
			self::redirect_with_error( 'invalid_role', 'support' );
			do_action( 'lgp_unauthorized_sso_login', $user->ID );
			return;
		}

		// Check if user account is active
		$is_active = get_user_meta( $user->ID, 'lgp_account_active', true );
		if ( $is_active === '0' ) {
			self::redirect_with_error( 'account_disabled', 'support' );
			return;
		}

		// Set session cookie
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true ); // Remember for 14 days with SSO

		// Update last login and SSO metadata
		update_user_meta( $user->ID, 'lgp_last_login', current_time( 'mysql' ) );
		update_user_meta( $user->ID, 'lgp_last_sso_login', current_time( 'mysql' ) );
		update_user_meta( $user->ID, 'lgp_sso_email', $user_data['email'] );

		// Log successful SSO login
		do_action( 'lgp_login_success', $user->ID, 'support' );

		// Get redirect URL
		$redirect = get_transient( 'lgp_sso_redirect_' . $user->ID );
		if ( ! $redirect ) {
			$redirect = admin_url( '' );
		}
		delete_transient( 'lgp_sso_redirect_' . $user->ID );

		// Apply redirect filter and go
		wp_redirect( apply_filters( 'login_redirect', $redirect, '', $user ) );
		exit;
	}

	/**
	 * Get or create SSO user
	 */
	private static function get_or_create_sso_user( $user_data ) {
		// Try to find user by email
		$user = get_user_by( 'email', $user_data['email'] );

		if ( $user ) {
			return $user;
		}

		// Check if SSO user creation is allowed
		if ( ! apply_filters( 'lgp_allow_sso_user_creation', false ) ) {
			return new \WP_Error( 'user_creation_disabled', __( 'User creation via SSO is disabled', 'loungenie-portal' ) );
		}

		// Create new user
		$username = self::generate_sso_username( $user_data['email'] );
		$user_id  = wp_create_user( $username, wp_generate_password( 32 ), $user_data['email'] );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Set user display name from Microsoft data
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $user_data['name'],
				'first_name'   => $user_data['first_name'] ?? '',
				'last_name'    => $user_data['last_name'] ?? '',
			)
		);

		// Set SSO metadata
		update_user_meta( $user_id, 'lgp_sso_user', true );
		update_user_meta( $user_id, 'lgp_sso_email', $user_data['email'] );
		update_user_meta( $user_id, 'lgp_sso_id', $user_data['id'] );

		// Assign support role
		$user = get_user_by( 'id', $user_id );
		$user->add_role( 'support' );

		do_action( 'lgp_sso_user_created', $user_id, $user_data );

		return $user;
	}

	/**
	 * Generate unique username for SSO user
	 */
	private static function generate_sso_username( $email ) {
		// Use email prefix as base
		$base     = sanitize_user( explode( '@', $email )[0], true );
		$username = $base;
		$counter  = 1;

		// Ensure uniqueness
		while ( username_exists( $username ) ) {
			$username = $base . $counter;
			++$counter;
		}

		return $username;
	}

	/**
	 * Check if user has role
	 */
	private static function user_has_role( $user_id, $role ) {
		$user = get_user_by( 'id', $user_id );
		return $user && $user->has_cap( 'lgp_' . $role );
	}

	/**
	 * Get redirect URL based on user role
	 */
	private static function get_role_redirect( $user_id, $fallback = '' ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return $fallback ?: admin_url( '' );
		}

		// Check if user has partner role
		if ( $user->has_cap( 'lgp_partner' ) ) {
			return apply_filters( 'lgp_partner_redirect', home_url( '/partner-dashboard' ), $user_id );
		}

		// Check if user has support role
		if ( $user->has_cap( 'lgp_support' ) ) {
			return apply_filters( 'lgp_support_redirect', home_url( '/support-dashboard' ), $user_id );
		}

		// Admin or other role
		return $fallback ?: admin_url( '' );
	}

	/**
	 * Redirect with error
	 */
	private static function redirect_with_error( $error, $role = 'select', $redirect_to = '' ) {
		$login_url = self::get_custom_login_url( 'select' );

		if ( $role !== 'select' ) {
			$login_url = add_query_arg( 'login_type', $role, $login_url );
		}

		$login_url = add_query_arg( 'error', $error, $login_url );

		if ( $redirect_to ) {
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url );
		}

		wp_redirect( $login_url );
		exit;
	}

	/**
	 * Get custom login URL
	 */
	public static function get_custom_login_url( $login_type = 'select', $redirect_to = '' ) {
		$url = add_query_arg( 'action', 'login', wp_login_url() );

		if ( $login_type !== 'select' ) {
			$url = add_query_arg( 'login_type', $login_type, $url );
		}

		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $url );
		}

		return $url;
	}

	/**
	 * Custom login URL filter
	 */
	public static function custom_login_url( $url ) {
		return self::get_custom_login_url();
	}

	/**
	 * Redirect to custom login page
	 */
	public static function redirect_to_custom_login() {
		if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'login' ) {
			return;
		}

		self::load_custom_login_template();
	}

	/**
	 * Handle logout
	 */
	public static function handle_logout() {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			do_action( 'lgp_logout', $user_id );
		}

		// Redirect to custom login page
		wp_redirect( self::get_custom_login_url() );
		exit;
	}

	/**
	 * Add login body class
	 */
	public static function add_login_body_class( $classes ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'login' ) {
			$classes[] = 'lgp-login-page';
		}
		return $classes;
	}

	/**
	 * Role-based redirect on login
	 */
	public static function redirect_on_role( $redirect_to, $requested_redirect_to, $user ) {
		// If user is object, get their ID
		if ( is_object( $user ) ) {
			$user_id = $user->ID;
		} else {
			$user_id = $user;
		}

		// Get role-based redirect
		$role_redirect = self::get_role_redirect( $user_id, $redirect_to );

		return apply_filters( 'lgp_login_redirect', $role_redirect, $redirect_to, $user_id );
	}
}

// Initialize on plugins_loaded
add_action( 'plugins_loaded', array( '\LounGenie\Portal\Login_Handler', 'init' ), 5 );
