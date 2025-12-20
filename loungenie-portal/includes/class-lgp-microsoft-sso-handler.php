<?php
/**
 * Microsoft SSO Integration Handler
 *
 * Handles Microsoft Outlook / Microsoft 365 Single Sign-On (SSO)
 * Integrates with Microsoft Graph API for secure authentication
 *
 * Requirements:
 * - Microsoft Azure App Registration
 * - Client ID and Client Secret
 * - Redirect URI configured in Azure portal
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

namespace LounGenie\Portal;

class Microsoft_SSO {

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Configuration settings
	 */
	private $client_id;
	private $client_secret;
	private $tenant_id;
	private $redirect_uri;

	/**
	 * Microsoft Graph API endpoints
	 */
	const AUTH_URL  = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize';
	const TOKEN_URL = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';
	const GRAPH_URL = 'https://graph.microsoft.com/v1.0/me';

	/**
	 * OAuth scopes
	 */
	const SCOPES = array( 'openid', 'profile', 'email' );

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Load configuration
	 */
	private function __construct() {
		$this->load_config();
	}

	/**
	 * Load configuration from database or constants
	 */
	private function load_config() {
		// Allow configuration via PHP constants (in wp-config.php)
		if ( defined( 'LGP_MICROSOFT_CLIENT_ID' ) && defined( 'LGP_MICROSOFT_CLIENT_SECRET' ) ) {
			$this->client_id     = LGP_MICROSOFT_CLIENT_ID;
			$this->client_secret = LGP_MICROSOFT_CLIENT_SECRET;
			$this->tenant_id     = defined( 'LGP_MICROSOFT_TENANT_ID' ) ? LGP_MICROSOFT_TENANT_ID : 'common';
		} else {
			// Load from plugin options (for admin configuration)
			$options             = get_option( 'lgp_microsoft_sso_config', array() );
			$this->client_id     = $options['client_id'] ?? '';
			$this->client_secret = $options['client_secret'] ?? '';
			$this->tenant_id     = $options['tenant_id'] ?? 'common';
		}

		// Set redirect URI
		$this->redirect_uri = add_query_arg( 'action', 'lgp_sso_callback', wp_login_url() );
	}

	/**
	 * Check if SSO is configured
	 */
	public function is_configured() {
		return ! empty( $this->client_id ) && ! empty( $this->client_secret );
	}

	/**
	 * Initiate authentication flow
	 */
	public function authenticate() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'sso_not_configured', __( 'Microsoft SSO is not configured', 'loungenie-portal' ) );
		}

		// Generate state for CSRF protection
		$state = wp_generate_uuid4();
		set_transient( 'lgp_sso_state_' . $state, true, HOUR_IN_SECONDS );

		// Build authorization URL
		$auth_url = str_replace( '{tenant}', $this->tenant_id, self::AUTH_URL );

		$redirect = add_query_arg(
			array(
				'client_id'     => $this->client_id,
				'response_type' => 'code',
				'redirect_uri'  => $this->redirect_uri,
				'response_mode' => 'query',
				'scope'         => implode( ' ', self::SCOPES ),
				'state'         => $state,
				'prompt'        => 'select_account', // Allow user to select account
			),
			$auth_url
		);

		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Handle OAuth callback
	 */
	public static function handle_callback() {
		$instance = self::get_instance();

		// Verify state parameter (CSRF protection)
		$state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';
		if ( empty( $state ) || ! get_transient( 'lgp_sso_state_' . $state ) ) {
			return new \WP_Error( 'invalid_state', __( 'Invalid state parameter', 'loungenie-portal' ) );
		}
		delete_transient( 'lgp_sso_state_' . $state );

		// Check for errors from Microsoft
		if ( isset( $_GET['error'] ) ) {
			$error             = sanitize_text_field( $_GET['error'] );
			$error_description = isset( $_GET['error_description'] ) ? sanitize_text_field( $_GET['error_description'] ) : '';

			do_action( 'lgp_sso_error', $error, $error_description );

			return new \WP_Error( $error, $error_description );
		}

		// Get authorization code
		$code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		if ( empty( $code ) ) {
			return new \WP_Error( 'no_auth_code', __( 'No authorization code received', 'loungenie-portal' ) );
		}

		// Exchange code for access token
		$token_response = $instance->get_access_token( $code );
		if ( is_wp_error( $token_response ) ) {
			return $token_response;
		}

		// Get user info from Microsoft Graph
		$user_data = $instance->get_user_data( $token_response['access_token'] );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		// Process login
		Login_Handler::handle_sso_callback( $user_data );
	}

	/**
	 * Exchange authorization code for access token
	 */
	private function get_access_token( $code ) {
		$token_url = str_replace( '{tenant}', $this->tenant_id, self::TOKEN_URL );

		$response = wp_remote_post(
			$token_url,
			array(
				'timeout'   => 30,
				'sslverify' => true,
				'body'      => array(
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'code'          => $code,
					'redirect_uri'  => $this->redirect_uri,
					'grant_type'    => 'authorization_code',
					'scope'         => implode( ' ', self::SCOPES ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			return new \WP_Error( 'token_exchange_failed', __( 'Failed to exchange authorization code for token', 'loungenie-portal' ) );
		}

		if ( empty( $data['access_token'] ) ) {
			return new \WP_Error( 'no_access_token', __( 'No access token in response', 'loungenie-portal' ) );
		}

		return $data;
	}

	/**
	 * Get user information from Microsoft Graph
	 */
	private function get_user_data( $access_token ) {
		$response = wp_remote_get(
			self::GRAPH_URL,
			array(
				'timeout'   => 30,
				'sslverify' => true,
				'headers'   => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			return new \WP_Error( 'graph_request_failed', __( 'Failed to retrieve user data from Microsoft Graph', 'loungenie-portal' ) );
		}

		// Extract user data
		$user_data = array(
			'id'              => $data['id'] ?? '',
			'email'           => $data['userPrincipalName'] ?? $data['mail'] ?? '',
			'name'            => $data['displayName'] ?? '',
			'first_name'      => $data['givenName'] ?? '',
			'last_name'       => $data['surname'] ?? '',
			'office_location' => $data['officeLocation'] ?? '',
			'phone'           => $data['mobilePhone'] ?? '',
		);

		return $user_data;
	}

	/**
	 * Save configuration to database
	 */
	public static function save_config( $client_id, $client_secret, $tenant_id = 'common' ) {
		$config = array(
			'client_id'     => sanitize_text_field( $client_id ),
			'client_secret' => sanitize_text_field( $client_secret ),
			'tenant_id'     => sanitize_text_field( $tenant_id ),
		);

		update_option( 'lgp_microsoft_sso_config', $config );

		do_action( 'lgp_sso_config_updated', $config );
	}

	/**
	 * Get configuration
	 */
	public static function get_config() {
		// Check for constants first
		if ( defined( 'LGP_MICROSOFT_CLIENT_ID' ) ) {
			return array(
				'source'     => 'constants',
				'configured' => defined( 'LGP_MICROSOFT_CLIENT_SECRET' ),
				'client_id'  => defined( 'LGP_MICROSOFT_CLIENT_ID' ) ? LGP_MICROSOFT_CLIENT_ID : '',
				'tenant_id'  => defined( 'LGP_MICROSOFT_TENANT_ID' ) ? LGP_MICROSOFT_TENANT_ID : 'common',
			);
		}

		// Fall back to database options
		$config = get_option( 'lgp_microsoft_sso_config', array() );
		return array(
			'source'     => 'database',
			'configured' => ! empty( $config['client_id'] ) && ! empty( $config['client_secret'] ),
			'client_id'  => $config['client_id'] ?? '',
			'tenant_id'  => $config['tenant_id'] ?? 'common',
		);
	}

	/**
	 * Clear configuration
	 */
	public static function clear_config() {
		delete_option( 'lgp_microsoft_sso_config' );
		do_action( 'lgp_sso_config_cleared' );
	}

	/**
	 * Get documentation for setup
	 */
	public static function get_setup_documentation() {
		return array(
			'title' => __( 'Microsoft SSO Setup Instructions', 'loungenie-portal' ),
			'steps' => array(
				array(
					'title'        => __( 'Register Application in Azure AD', 'loungenie-portal' ),
					'instructions' => array(
						__( '1. Go to Azure Portal: https://portal.azure.com', 'loungenie-portal' ),
						__( '2. Navigate to "Azure Active Directory" → "App registrations"', 'loungenie-portal' ),
						__( '3. Click "New registration"', 'loungenie-portal' ),
						__( '4. Enter application name: "LounGenie Portal"', 'loungenie-portal' ),
						__( '5. Select "Accounts in this organizational directory only"', 'loungenie-portal' ),
						__( '6. Click "Register"', 'loungenie-portal' ),
					),
				),
				array(
					'title'        => __( 'Configure Redirect URI', 'loungenie-portal' ),
					'instructions' => array(
						__( '1. In the registered app, go to "Authentication"', 'loungenie-portal' ),
						__( '2. Under "Redirect URIs", click "Add URI"', 'loungenie-portal' ),
						sprintf( __( '3. Enter the Redirect URI: %s', 'loungenie-portal' ), site_url( 'wp-login.php?action=lgp_sso_callback' ) ),
						__( '4. Check the checkboxes for "Access tokens" and "ID tokens"', 'loungenie-portal' ),
						__( '5. Click "Save"', 'loungenie-portal' ),
					),
				),
				array(
					'title'        => __( 'Create Application Secret', 'loungenie-portal' ),
					'instructions' => array(
						__( '1. Go to "Certificates & secrets"', 'loungenie-portal' ),
						__( '2. Under "Client secrets", click "New client secret"', 'loungenie-portal' ),
						__( '3. Enter description: "LounGenie Portal Secret"', 'loungenie-portal' ),
						__( '4. Select expiration (24 months recommended)', 'loungenie-portal' ),
						__( '5. Click "Add"', 'loungenie-portal' ),
						__( '6. Copy the secret value immediately (cannot be retrieved later)', 'loungenie-portal' ),
					),
				),
				array(
					'title'        => __( 'Configure WordPress', 'loungenie-portal' ),
					'instructions' => array(
						__( 'Add to wp-config.php:', 'loungenie-portal' ),
						__( 'define("LGP_MICROSOFT_CLIENT_ID", "your-client-id");', 'loungenie-portal' ),
						__( 'define("LGP_MICROSOFT_CLIENT_SECRET", "your-client-secret");', 'loungenie-portal' ),
						__( 'define("LGP_MICROSOFT_TENANT_ID", "your-tenant-id");', 'loungenie-portal' ),
						__( 'Or configure in WordPress Admin → Settings → LounGenie Portal → SSO', 'loungenie-portal' ),
					),
				),
				array(
					'title'        => __( 'Set API Permissions', 'loungenie-portal' ),
					'instructions' => array(
						__( '1. In Azure app, go to "API permissions"', 'loungenie-portal' ),
						__( '2. Click "Add a permission"', 'loungenie-portal' ),
						__( '3. Select "Microsoft Graph" → "Delegated permissions"', 'loungenie-portal' ),
						__( '4. Search for and add: "openid", "profile", "email"', 'loungenie-portal' ),
						__( '5. Click "Grant admin consent"', 'loungenie-portal' ),
					),
				),
			),
		);
	}
}

// Handle SSO callback
add_action(
	'init',
	function() {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'lgp_sso_callback' ) {
			Microsoft_SSO::handle_callback();
		}
	}
);
