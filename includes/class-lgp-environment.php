<?php

/**
 * Environment Configuration System
 * Support for dev, staging, and production environments
 *
 * @package LounGenie Portal
 * @version 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Environment {

	private static $config          = array();
	private static $initialized     = false;
	private static $is_initializing = false;

	const ENV_DEVELOPMENT = 'development';
	const ENV_STAGING     = 'staging';
	const ENV_PRODUCTION  = 'production';

	/**
	 * Initialize environment configuration
	 * Uses recursion guard to prevent infinite loops
	 */
	public static function init() {
		// Guard 1: Already initialized
		if ( self::$initialized ) {
			return;
		}

		// Guard 2: Already initializing (prevent infinite recursion)
		if ( self::$is_initializing ) {
			return;
		}

		// Set flag BEFORE any calls that might trigger ensure_initialized()
		self::$is_initializing = true;

		try {
			self::load_config();
			self::set_constants();
			self::configure_features();
			self::$initialized = true;
		} finally {
			// Always clear the initializing flag
			self::$is_initializing = false;
		}
	}

	/**
	 * Load environment configuration from multiple sources
	 * Priority: env variables > .env file > WordPress options
	 */
	private static function load_config() {
		// Get environment name
		$env = self::get_env_var( 'LGP_ENV', self::ENV_PRODUCTION );

		self::$config = array(
			'environment' => $env,
			'debug'       => self::determine_debug_mode( $env ),
			'log_level'   => self::determine_log_level( $env ),
			'cache_ttl'   => self::determine_cache_ttl( $env ),
			'rate_limit'  => self::determine_rate_limit( $env ),
			'api_timeout' => self::determine_api_timeout( $env ),
			'features'    => self::get_feature_flags( $env ),
		);

		// Store in WordPress options for persistence (only if changed)
		$existing = get_option( 'lgp_environment_config' );
		if ( $existing !== self::$config ) {
			update_option( 'lgp_environment_config', self::$config, 'no' );
		}
	}

	/**
	 * Get environment variable from multiple sources
	 *
	 * @param string $name Variable name
	 * @param string $default Default value
	 * @return string
	 */
	private static function get_env_var( $name, $default = '' ) {
		// Check PHP environment variable first
		if ( isset( $_ENV[ $name ] ) ) {
			return sanitize_text_field( $_ENV[ $name ] );
		}

		// Check getenv()
		$env_value = getenv( $name );
		if ( false !== $env_value ) {
			return sanitize_text_field( $env_value );
		}

		// Check constant
		if ( defined( $name ) ) {
			return constant( $name );
		}

		// Check .env file
		$env_value = self::read_env_file( $name );
		if ( $env_value ) {
			return sanitize_text_field( $env_value );
		}

		return $default;
	}

	/**
	 * Read .env file from plugin root
	 *
	 * @param string $key Key to read
	 * @return string|null
	 */
	private static function read_env_file( $key ) {
		$env_file = dirname( LGP_PLUGIN_DIR ) . '/.env';

		if ( ! file_exists( $env_file ) ) {
			return null;
		}

		$lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		foreach ( $lines as $line ) {
			// Skip comments
			if ( '#' === substr( trim( $line ), 0, 1 ) ) {
				continue;
			}

			if ( strpos( $line, '=' ) !== false ) {
				list( $k, $v ) = explode( '=', $line, 2 );
				$k             = trim( $k );
				$v             = trim( $v );

				if ( $k === $key ) {
					// Remove quotes if present
					if ( '"' === substr( $v, 0, 1 ) ) {
						$v = substr( $v, 1, -1 );
					}
					return $v;
				}
			}
		}

		return null;
	}

	/**
	 * Determine debug mode based on environment
	 *
	 * @param string $env Environment name
	 * @return bool
	 */
	private static function determine_debug_mode( $env ) {
		// Allow override
		if ( defined( 'LGP_DEBUG' ) ) {
			return (bool) LGP_DEBUG;
		}

		$debug_env = self::get_env_var( 'LGP_DEBUG', false );
		if ( false !== $debug_env ) {
			return in_array( strtolower( $debug_env ), array( 'true', '1', 'on', 'yes' ), true );
		}

		// Default: debug only in development
		return self::ENV_DEVELOPMENT === $env;
	}

	/**
	 * Determine logging level based on environment
	 *
	 * @param string $env Environment name
	 * @return string
	 */
	private static function determine_log_level( $env ) {
		$level_map = array(
			self::ENV_DEVELOPMENT => 'debug',
			self::ENV_STAGING     => 'info',
			self::ENV_PRODUCTION  => 'warning',
		);

		return $level_map[ $env ] ?? 'warning';
	}

	/**
	 * Determine cache TTL based on environment
	 *
	 * @param string $env Environment name
	 * @return int
	 */
	private static function determine_cache_ttl( $env ) {
		$ttl_map = array(
			self::ENV_DEVELOPMENT => 60, // 1 minute
			self::ENV_STAGING     => 300, // 5 minutes
			self::ENV_PRODUCTION  => 3600, // 1 hour
		);

		return $ttl_map[ $env ] ?? 3600;
	}

	/**
	 * Determine rate limit based on environment
	 *
	 * @param string $env Environment name
	 * @return int
	 */
	private static function determine_rate_limit( $env ) {
		$limit_map = array(
			self::ENV_DEVELOPMENT => 100, // Relaxed for testing
			self::ENV_STAGING     => 50,
			self::ENV_PRODUCTION  => 20,
		);

		return $limit_map[ $env ] ?? 20;
	}

	/**
	 * Determine API timeout based on environment
	 *
	 * @param string $env Environment name
	 * @return int
	 */
	private static function determine_api_timeout( $env ) {
		$timeout_map = array(
			self::ENV_DEVELOPMENT => 30, // Longer timeout for testing
			self::ENV_STAGING     => 15,
			self::ENV_PRODUCTION  => 10,
		);

		return $timeout_map[ $env ] ?? 10;
	}

	/**
	 * Get feature flags based on environment
	 *
	 * @param string $env Environment name
	 * @return array
	 */
	private static function get_feature_flags( $env ) {
		$flags = array(
			'health_check_endpoint' => true,
			'security_audit_log'    => true,
			'query_monitor'         => true,
			'api_docs'              => true,
			'experimental_features' => self::ENV_DEVELOPMENT === $env,
			'beta_features'         => in_array( $env, array( self::ENV_DEVELOPMENT, self::ENV_STAGING ), true ),
		);

		// Allow environment override
		foreach ( $flags as $key => $value ) {
			$env_key   = 'LGP_FEATURE_' . strtoupper( $key );
			$env_value = self::get_env_var( $env_key, false );

			if ( false !== $env_value ) {
				$flags[ $key ] = in_array( strtolower( $env_value ), array( 'true', '1', 'on', 'yes' ), true );
			}
		}

		return $flags;
	}

	/**
	 * Set WordPress constants based on environment
	 * NOTE: Uses self::$config directly instead of get_environment() to avoid recursion
	 */
	private static function set_constants() {
		if ( ! defined( 'LGP_DEBUG' ) ) {
			define( 'LGP_DEBUG', self::$config['debug'] );
		}

		if ( ! defined( 'LGP_LOG_LEVEL' ) ) {
			define( 'LGP_LOG_LEVEL', self::$config['log_level'] );
		}

		if ( ! defined( 'LGP_CACHE_TTL' ) ) {
			define( 'LGP_CACHE_TTL', self::$config['cache_ttl'] );
		}

		if ( ! defined( 'LGP_API_TIMEOUT' ) ) {
			define( 'LGP_API_TIMEOUT', self::$config['api_timeout'] );
		}

		// Enable WordPress debugging in development
		// Use self::$config directly, NOT self::get_environment() which would trigger ensure_initialized()
		if ( self::ENV_DEVELOPMENT === self::$config['environment'] ) {
			if ( ! defined( 'WP_DEBUG' ) ) {
				define( 'WP_DEBUG', true );
			}
			if ( ! defined( 'WP_DEBUG_LOG' ) ) {
				define( 'WP_DEBUG_LOG', true );
			}
		}
	}

	/**
	 * Configure features based on environment
	 */
	private static function configure_features() {
		$features = self::$config['features'];

		// Disable expensive features in production if needed
		if ( self::ENV_PRODUCTION === self::get_environment() ) {
			add_filter(
				'query_monitor_enabled',
				function () {
					return false;
				}
			);
		}

		// Always enable security features
		if ( class_exists( 'LGP_Security' ) ) {
			LGP_Security::init();
		}
	}

	/**
	 * Get current environment
	 *
	 * @return string
	 */
	public static function get_environment() {
		self::ensure_initialized();
		return self::$config['environment'];
	}

	/**
	 * Check if debug mode is enabled
	 *
	 * @return bool
	 */
	public static function is_debug() {
		self::ensure_initialized();
		return self::$config['debug'];
	}

	/**
	 * Get logging level
	 *
	 * @return string
	 */
	public static function get_log_level() {
		self::ensure_initialized();
		return self::$config['log_level'];
	}

	/**
	 * Get cache TTL in seconds
	 *
	 * @return int
	 */
	public static function get_cache_ttl() {
		self::ensure_initialized();
		return self::$config['cache_ttl'];
	}

	/**
	 * Get rate limit
	 *
	 * @return int
	 */
	public static function get_rate_limit() {
		self::ensure_initialized();
		return self::$config['rate_limit'];
	}

	/**
	 * Get API timeout
	 *
	 * @return int
	 */
	public static function get_api_timeout() {
		self::ensure_initialized();
		return self::$config['api_timeout'];
	}

	/**
	 * Check if feature is enabled
	 *
	 * @param string $feature Feature name
	 * @return bool
	 */
	public static function is_feature_enabled( $feature ) {
		self::ensure_initialized();
		return self::$config['features'][ $feature ] ?? false;
	}

	/**
	 * Get all configuration
	 *
	 * @return array
	 */
	public static function get_config() {
		self::ensure_initialized();
		return self::$config;
	}

	/**
	 * Ensure configuration is initialized
	 * Safe to call even during init() due to recursion guards
	 */
	private static function ensure_initialized() {
		// Skip if already initialized or currently initializing
		if ( ! self::$initialized && ! self::$is_initializing ) {
			self::init();
		}
	}

	/**
	 * Get .env file template for users
	 *
	 * @return string
	 */
	public static function get_env_template() {
		return <<<'ENV'
# LounGenie Portal Environment Configuration
# Copy this file to the repository root as .env and customize for your environment

# Environment: development, staging, or production
LGP_ENV=production

# Enable debug mode (true/false)
LGP_DEBUG=false

# Feature flags
LGP_FEATURE_HEALTH_CHECK_ENDPOINT=true
LGP_FEATURE_SECURITY_AUDIT_LOG=true
LGP_FEATURE_QUERY_MONITOR=true
LGP_FEATURE_API_DOCS=true
LGP_FEATURE_EXPERIMENTAL_FEATURES=false
LGP_FEATURE_BETA_FEATURES=false

# Email configuration
LGP_EMAIL_PROVIDER=WordPress
# LGP_EMAIL_FROM_NAME="LounGenie Support"
# LGP_EMAIL_FROM_ADDRESS="support@loungenie.com"

# Database backups
# LGP_BACKUP_ENABLED=true
# LGP_BACKUP_SCHEDULE=daily

# Third-party API keys
# LGP_HUBSPOT_API_KEY=your_key_here
# LGP_MICROSOFT_CLIENT_ID=your_client_id
# LGP_MICROSOFT_CLIENT_SECRET=your_client_secret

# Cache configuration
# LGP_CACHE_BACKEND=redis
# LGP_REDIS_HOST=localhost
# LGP_REDIS_PORT=6379
ENV;
	}

	/**
	 * Generate WordPress admin page for environment configuration
	 */
	public static function add_admin_page() {
		add_submenu_page(
			'loungenie-portal',
			'Environment Configuration',
			'Environment Config',
			'manage_options',
			'lgp-environment',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}

		self::ensure_initialized();
		$config          = self::$config;
		$env_file_exists = file_exists( dirname( LGP_PLUGIN_DIR ) . '/.env' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LounGenie Portal - Environment Configuration', 'loungenie-portal' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Current Environment:', 'loungenie-portal' ); ?></strong>
					<code><?php echo esc_html( strtoupper( $config['environment'] ) ); ?></code>
				</p>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Configuration Status', 'loungenie-portal' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( '.env File:', 'loungenie-portal' ); ?></th>
						<td>
							<?php if ( $env_file_exists ) : ?>
								<span style="color: green;">✓ <?php esc_html_e( 'Found', 'loungenie-portal' ); ?></span>
							<?php else : ?>
								<span style="color: orange;">⚠ <?php esc_html_e( 'Not found (using defaults)', 'loungenie-portal' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug Mode:', 'loungenie-portal' ); ?></th>
						<td><?php echo esc_html( $config['debug'] ? 'Enabled' : 'Disabled' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Log Level:', 'loungenie-portal' ); ?></th>
						<td><code><?php echo esc_html( $config['log_level'] ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cache TTL:', 'loungenie-portal' ); ?></th>
						<td><?php echo esc_html( $config['cache_ttl'] ); ?> <?php esc_html_e( 'seconds', 'loungenie-portal' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'API Timeout:', 'loungenie-portal' ); ?></th>
						<td><?php echo esc_html( $config['api_timeout'] ); ?> <?php esc_html_e( 'seconds', 'loungenie-portal' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rate Limit:', 'loungenie-portal' ); ?></th>
						<td><?php echo esc_html( $config['rate_limit'] ); ?> <?php esc_html_e( 'requests/hour', 'loungenie-portal' ); ?></td>
					</tr>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Feature Flags', 'loungenie-portal' ); ?></h2>
				<table class="form-table">
					<?php foreach ( $config['features'] as $feature => $enabled ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $feature ) ) ); ?></th>
							<td>
								<span style="color: <?php echo esc_attr( $enabled ? 'green' : 'gray' ); ?>;">
									<?php echo esc_html( $enabled ? '✓ Enabled' : '✗ Disabled' ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( '.env Template', 'loungenie-portal' ); ?></h2>
				<p><?php esc_html_e( 'Copy the following template and save it as .env in the repository root:', 'loungenie-portal' ); ?></p>
				<textarea readonly style="width: 100%; height: 400px; font-family: monospace; background: #f5f5f5; padding: 10px;">
		<?php echo esc_html( self::get_env_template() ); ?>
				</textarea>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Environment Examples', 'loungenie-portal' ); ?></h2>
				<h3><?php esc_html_e( 'Development', 'loungenie-portal' ); ?></h3>
				<pre><code>LGP_ENV=development
LGP_DEBUG=true
LGP_FEATURE_EXPERIMENTAL_FEATURES=true</code></pre>

				<h3><?php esc_html_e( 'Staging', 'loungenie-portal' ); ?></h3>
				<pre><code>LGP_ENV=staging
LGP_DEBUG=false
LGP_FEATURE_BETA_FEATURES=true</code></pre>

				<h3><?php esc_html_e( 'Production', 'loungenie-portal' ); ?></h3>
				<pre><code>LGP_ENV=production
LGP_DEBUG=false
LGP_FEATURE_EXPERIMENTAL_FEATURES=false</code></pre>
			</div>
		</div>

		<style>
			.card { background: white; padding: 20px; margin: 20px 0; border: 1px solid #e5e5e5; border-radius: 5px; }
			pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; }
			code { font-family: 'Courier New', monospace; }
		</style>
		<?php
	}
}

// Initialize on WordPress init
add_action( 'plugins_loaded', array( 'LGP_Environment', 'init' ), 5 );
