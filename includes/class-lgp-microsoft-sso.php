<?php
/**
 * LounGenie Portal Microsoft 365 SSO
 *
 * OAuth 2.0 integration with Azure AD for support users
 * Based on PoolSafe Portal v3.3.0 architecture
 *
 * @package LounGenie Portal
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Microsoft_SSO {

	/**
	 * Option names for settings
	 */
	const OPTION_CLIENT_ID     = 'lgp_m365_client_id';
	const OPTION_CLIENT_SECRET = 'lgp_m365_client_secret';
	const OPTION_TENANT_ID     = 'lgp_m365_tenant_id';
	const OPTION_ACCESS_TOKEN  = 'lgp_m365_access_token';
	const OPTION_REFRESH_TOKEN = 'lgp_m365_refresh_token';
	const OPTION_TOKEN_EXPIRES = 'lgp_m365_token_expires';

	/**
	 * Microsoft OAuth endpoints
	 */
	const AUTH_URL  = 'https://login.microsoftonline.com';
	const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

	/**
	 * Initialize SSO
	 */
	public static function init() {
		// Add settings page
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Handle OAuth callback via clean URL
		add_action( 'parse_request', array( __CLASS__, 'handle_oauth_callback' ) );

		// Add SSO button to login page
		add_action( 'login_form', array( __CLASS__, 'add_sso_button' ) );

		// AJAX endpoint for SSO initiation
		add_action( 'wp_ajax_nopriv_lgp_sso_login', array( __CLASS__, 'ajax_initiate_sso' ) );
		add_action( 'wp_ajax_lgp_sso_login', array( __CLASS__, 'ajax_initiate_sso' ) );
	}

	/**
	 * Add settings page
	 */
	public static function add_settings_page() {
		add_options_page(
			__( 'Microsoft 365 SSO', 'loungenie-portal' ),
			__( 'M365 SSO', 'loungenie-portal' ),
			'manage_options',
			'lgp-m365-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'lgp_m365_settings', self::OPTION_CLIENT_ID );
		register_setting( 'lgp_m365_settings', self::OPTION_CLIENT_SECRET );
		register_setting( 'lgp_m365_settings', self::OPTION_TENANT_ID );

		// Portal branding settings
		register_setting(
			'lgp_m365_settings',
			'lgp_custom_logo_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'loungenie-portal' ) );
		}

		$client_id     = get_option( self::OPTION_CLIENT_ID, '' );
		$client_secret = get_option( self::OPTION_CLIENT_SECRET, '' );
		$tenant_id     = get_option( self::OPTION_TENANT_ID, '' );
		$is_configured = ! empty( $client_id ) && ! empty( $client_secret ) && ! empty( $tenant_id );
		$logo_url      = get_option( 'lgp_custom_logo_url', '' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LounGenie Portal Settings', 'loungenie-portal' ); ?></h1>
			
			<div class="notice notice-info">
				<p><strong><?php esc_html_e( 'Azure AD App Registration', 'loungenie-portal' ); ?></strong></p>
				<ol>
					<li><?php esc_html_e( 'Go to Azure Portal → App Registrations', 'loungenie-portal' ); ?></li>
					<li><?php esc_html_e( 'Create new app: "LounGenie Portal SSO"', 'loungenie-portal' ); ?></li>
					<li><?php esc_html_e( 'Set redirect URI:', 'loungenie-portal' ); ?> <code><?php echo esc_url( home_url( '/m365-sso-callback' ) ); ?></code></li>
					<li><?php esc_html_e( 'Add API permissions: User.Read, email, profile, openid', 'loungenie-portal' ); ?></li>
					<li><?php esc_html_e( 'Create client secret and copy the value', 'loungenie-portal' ); ?></li>
				</ol>
			</div>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'lgp_m365_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_CLIENT_ID ); ?>">
								<?php esc_html_e( 'Client ID', 'loungenie-portal' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
									name="<?php echo esc_attr( self::OPTION_CLIENT_ID ); ?>" 
									id="<?php echo esc_attr( self::OPTION_CLIENT_ID ); ?>" 
									value="<?php echo esc_attr( $client_id ); ?>" 
									class="regular-text" />
							<p class="description"><?php esc_html_e( 'Application (client) ID from Azure AD', 'loungenie-portal' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_CLIENT_SECRET ); ?>">
								<?php esc_html_e( 'Client Secret', 'loungenie-portal' ); ?>
							</label>
						</th>
						<td>
							<input type="password" 
									name="<?php echo esc_attr( self::OPTION_CLIENT_SECRET ); ?>" 
									id="<?php echo esc_attr( self::OPTION_CLIENT_SECRET ); ?>" 
									value="<?php echo esc_attr( $client_secret ); ?>" 
									class="regular-text" />
							<p class="description"><?php esc_html_e( 'Client secret value (not the ID)', 'loungenie-portal' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_TENANT_ID ); ?>">
								<?php esc_html_e( 'Tenant ID', 'loungenie-portal' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
									name="<?php echo esc_attr( self::OPTION_TENANT_ID ); ?>" 
									id="<?php echo esc_attr( self::OPTION_TENANT_ID ); ?>" 
									value="<?php echo esc_attr( $tenant_id ); ?>" 
									class="regular-text" />
							<p class="description"><?php esc_html_e( 'Directory (tenant) ID from Azure AD', 'loungenie-portal' ); ?></p>
						</td>
					</tr>
				</table>
				
				<h2 style="margin-top: 40px;"><?php esc_html_e( 'Portal Branding', 'loungenie-portal' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="lgp_custom_logo_url">
								<?php esc_html_e( 'Logo URL', 'loungenie-portal' ); ?>
							</label>
						</th>
						<td>
							<input type="url" 
									name="lgp_custom_logo_url" 
									id="lgp_custom_logo_url" 
									value="<?php echo esc_attr( $logo_url ); ?>" 
									class="regular-text" 
									placeholder="https://yourdomain.com/logo.png" />
							<p class="description">
								<?php esc_html_e( 'Upload your logo to WordPress Media Library, then paste the URL here. Recommended size: 280x80px (PNG with transparent background).', 'loungenie-portal' ); ?>
								<br>
								<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" target="_blank"><?php esc_html_e( 'Go to Media Library', 'loungenie-portal' ); ?></a>
							</p>
							<?php if ( $logo_url ) : ?>
								<div style="margin-top: 12px; padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
									<strong><?php esc_html_e( 'Preview:', 'loungenie-portal' ); ?></strong><br>
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Preview" style="max-width: 280px; height: auto; margin-top: 8px; display: block;" />
								</div>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			
			<?php if ( $is_configured ) : ?>
				<hr>
				<h2><?php esc_html_e( 'Test SSO', 'loungenie-portal' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( self::get_authorization_url() ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Test Sign in with Microsoft', 'loungenie-portal' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get authorization URL
	 *
	 * @return string Authorization URL
	 */
	public static function get_authorization_url() {
		$client_id    = get_option( self::OPTION_CLIENT_ID );
		$tenant_id    = get_option( self::OPTION_TENANT_ID );
		$redirect_uri = '/m365-sso-callback';
		if ( function_exists( 'home_url' ) ) {
			try {
				$redirect_uri = home_url( '/m365-sso-callback' );
			} catch ( \Throwable $e ) {
				// In tests without WordPress context, fall back to relative path
			}
		}

		$params = array(
			'client_id'     => $client_id,
			'response_type' => 'code',
			'redirect_uri'  => $redirect_uri,
			'response_mode' => 'query',
			'scope'         => 'openid profile email User.Read',
			'state'         => wp_create_nonce( 'lgp_m365_oauth' ),
		);

		return self::AUTH_URL . '/' . $tenant_id . '/oauth2/v2.0/authorize?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback via parse_request
	 *
	 * @param WP $wp WordPress environment object
	 */
	public static function handle_oauth_callback( $wp = null ) {
		$request_path = 'm365-sso-callback';
		if ( $wp && isset( $wp->request ) ) {
			$request_path = trim( (string) $wp->request, '/' );
		}

		if ( $request_path !== 'm365-sso-callback' ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		// Verify state nonce
		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( $_GET['state'], 'lgp_m365_oauth' ) ) {
			wp_die( __( 'Invalid state parameter', 'loungenie-portal' ) );
		}

		$code = sanitize_text_field( $_GET['code'] );

		// Exchange code for token
		$token_response = self::exchange_code_for_token( $code );

		if ( is_wp_error( $token_response ) ) {
			wp_die( $token_response->get_error_message() );
		}

		// Store tokens
		update_option( self::OPTION_ACCESS_TOKEN, $token_response['access_token'] );
		update_option( self::OPTION_REFRESH_TOKEN, $token_response['refresh_token'] ?? '' );
		update_option( self::OPTION_TOKEN_EXPIRES, time() + $token_response['expires_in'] );

		// Get user info
		$user_info = self::get_user_info( $token_response['access_token'] );

		if ( is_wp_error( $user_info ) ) {
			wp_die( $user_info->get_error_message() );
		}

		// Create or login user
		$wp_user = self::get_or_create_wp_user( $user_info );

		if ( is_wp_error( $wp_user ) ) {
			wp_die( $wp_user->get_error_message() );
		}

		// Log in user
		wp_set_auth_cookie( $wp_user->ID, true );
		wp_set_current_user( $wp_user->ID );

		// Redirect to portal
		$portal_url = '/portal';
		if ( function_exists( 'home_url' ) ) {
			try {
				$portal_url = home_url( '/portal' );
			} catch ( \Throwable $e ) {
				// fall back to relative path in non-WP test context
			}
		}
		wp_safe_redirect( $portal_url );
		exit;
	}

	/**
	 * Exchange authorization code for access token
	 *
	 * @param string $code Authorization code
	 * @return array|WP_Error Token response or error
	 */
	private static function exchange_code_for_token( $code ) {
		$client_id     = get_option( self::OPTION_CLIENT_ID );
		$client_secret = get_option( self::OPTION_CLIENT_SECRET );
		$tenant_id     = get_option( self::OPTION_TENANT_ID );
		$redirect_uri  = '/m365-sso-callback';
		if ( function_exists( 'home_url' ) ) {
			try {
				$redirect_uri = home_url( '/m365-sso-callback' );
			} catch ( \Throwable $e ) {
				// use relative callback during tests without WordPress
			}
		}

		$token_url = self::AUTH_URL . '/' . $tenant_id . '/oauth2/v2.0/token';

		$response = wp_remote_post(
			$token_url,
			array(
				'body' => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'code'          => $code,
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
					'scope'         => 'openid profile email User.Read',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'oauth_error', $body['error_description'] ?? $body['error'] );
		}

		return $body;
	}

	/**
	 * Get user info from Microsoft Graph
	 *
	 * @param string $access_token Access token
	 * @return array|WP_Error User info or error
	 */
	private static function get_user_info( $access_token ) {
		$response = wp_remote_get(
			self::GRAPH_URL . '/me',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'graph_error', $body['error']['message'] ?? 'Unknown error' );
		}

		return $body;
	}

	/**
	 * Get or create WordPress user from Microsoft user info
	 *
	 * @param array $user_info User info from Microsoft Graph
	 * @return WP_User|WP_Error WordPress user or error
	 */
	private static function get_or_create_wp_user( $user_info ) {
		$email = $user_info['mail'] ?? $user_info['userPrincipalName'] ?? '';

		if ( empty( $email ) ) {
			return new WP_Error( 'no_email', __( 'No email address found in Microsoft account', 'loungenie-portal' ) );
		}

		// Check if user exists
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			// Ensure user has support role
			if ( ! in_array( 'lgp_support', $user->roles ) ) {
				$user->add_role( 'lgp_support' );
			}
			return $user;
		}

		// Create new user
		$username     = sanitize_user( $user_info['userPrincipalName'] ?? $email );
		$display_name = $user_info['displayName'] ?? $username;

		$user_id = wp_create_user( $username, wp_generate_password( 32, true, true ), $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Update user meta
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $display_name,
				'first_name'   => $user_info['givenName'] ?? '',
				'last_name'    => $user_info['surname'] ?? '',
				'role'         => 'lgp_support',
			)
		);

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Add SSO button to login form
	 */
	public static function add_sso_button() {
		$client_id = get_option( self::OPTION_CLIENT_ID );

		if ( empty( $client_id ) ) {
			return;
		}

		$auth_url = self::get_authorization_url();

		?>
		<style>
			.lgp-sso-button {
				display: block;
				width: 100%;
				padding: 12px;
				margin: 20px 0;
				background: #0078d4;
				color: #fff;
				text-align: center;
				text-decoration: none;
				border-radius: 4px;
				font-weight: 500;
				transition: background 0.2s;
			}
			.lgp-sso-button:hover {
				background: #106ebe;
				color: #fff;
			}
			.lgp-sso-divider {
				text-align: center;
				margin: 20px 0;
				color: #666;
			}
		</style>
		<a href="<?php echo esc_url( $auth_url ); ?>" class="lgp-sso-button">
			<?php esc_html_e( 'Sign in with Microsoft 365', 'loungenie-portal' ); ?>
		</a>
		<div class="lgp-sso-divider"><?php esc_html_e( '— OR —', 'loungenie-portal' ); ?></div>
		<?php
	}

	/**
	 * AJAX initiate SSO
	 */
	public static function ajax_initiate_sso() {
		wp_send_json_success(
			array(
				'redirect_url' => self::get_authorization_url(),
			)
		);
	}

	/**
	 * Refresh access token
	 *
	 * @return bool True on success
	 */
	public static function refresh_access_token() {
		$refresh_token = get_option( self::OPTION_REFRESH_TOKEN );

		if ( empty( $refresh_token ) ) {
			return false;
		}

		$client_id     = get_option( self::OPTION_CLIENT_ID );
		$client_secret = get_option( self::OPTION_CLIENT_SECRET );
		$tenant_id     = get_option( self::OPTION_TENANT_ID );

		$token_url = self::AUTH_URL . '/' . $tenant_id . '/oauth2/v2.0/token';

		$response = wp_remote_post(
			$token_url,
			array(
				'body' => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
					'scope'         => 'openid profile email User.Read',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return false;
		}

		// Store new tokens
		update_option( self::OPTION_ACCESS_TOKEN, $body['access_token'] );
		if ( isset( $body['refresh_token'] ) ) {
			update_option( self::OPTION_REFRESH_TOKEN, $body['refresh_token'] );
		}
		update_option( self::OPTION_TOKEN_EXPIRES, time() + $body['expires_in'] );

		return true;
	}
}
