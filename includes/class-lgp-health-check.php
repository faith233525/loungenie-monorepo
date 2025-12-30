<?php

/**
 * Health Check System
 * Provides comprehensive system health monitoring and status endpoints
 *
 * @package LounGenie Portal
 * @version 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Health_Check {

	/**
	 * Initialize health check system
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'wp_ajax_lgp_health_check', array( __CLASS__, 'ajax_health_check' ) );
	}

	/**
	 * Register REST API endpoints
	 */
	public static function register_endpoints() {
		register_rest_route(
			'lgp/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_health_status' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'lgp/v1',
			'/health/detailed',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_detailed_health' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permission' ),
			)
		);
	}

	/**
	 * Get overall health status
	 *
	 * @return WP_REST_Response
	 */
	public static function get_health_status() {
		$status = 'healthy';
		$checks = array(
			'database'     => self::check_database(),
			'cache'        => self::check_cache(),
			'disk_space'   => self::check_disk_space(),
			'php_version'  => self::check_php_version(),
			'wp_version'   => self::check_wp_version(),
			'file_uploads' => self::check_file_uploads(),
		);

		// Determine overall status based on checks
		foreach ( $checks as $check ) {
			if ( $check['status'] === 'error' ) {
				$status = 'unhealthy';
				break;
			} elseif ( $check['status'] === 'warning' && $status !== 'unhealthy' ) {
				$status = 'degraded';
			}
		}

		return rest_ensure_response(
			array(
				'status'    => $status,
				'version'   => LGP_VERSION,
				'timestamp' => current_time( 'mysql' ),
				'uptime'    => self::get_server_uptime(),
				'checks'    => $checks,
				'metrics'   => array(
					'memory_usage'    => memory_get_usage( true ),
					'peak_memory'     => memory_get_peak_usage( true ),
					'active_users'    => self::count_active_users(),
					'pending_tickets' => self::count_pending_tickets(),
					'cron_overdue'    => self::check_cron_schedule(),
				),
			)
		);
	}

	/**
	 * Get detailed health status (admin only)
	 *
	 * @return WP_REST_Response
	 */
	public static function get_detailed_health() {
		$health = self::get_health_status();
		$data   = $health->get_data();

		// Add external API checks
		$data['external_apis'] = array(
			'hubspot'         => self::check_hubspot_connection(),
			'microsoft_graph' => self::check_graph_connection(),
			'microsoft_sso'   => self::check_sso_configuration(),
		);

		// Add database info
		$data['database_info'] = self::get_database_info();

		// Add caching info
		$data['cache_info'] = self::get_cache_info();

		// Add PHP extensions
		$data['php_extensions'] = self::check_required_extensions();

		return rest_ensure_response( $data );
	}

	/**
	 * Check database connection and table existence
	 *
	 * @return array
	 */
	private static function check_database() {
		global $wpdb;

		$status  = 'healthy';
		$message = '';
		$details = array();

		// Test connection
		if ( ! $wpdb->check_connection() ) {
			$status  = 'error';
			$message = 'Database connection failed';
			return compact( 'status', 'message', 'details' );
		}

		// Check required tables
		$tables = array(
			$wpdb->prefix . 'lgp_companies',
			$wpdb->prefix . 'lgp_units',
			$wpdb->prefix . 'lgp_tickets',
			$wpdb->prefix . 'lgp_service_requests',
		);

		foreach ( $tables as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				$status    = 'error';
				$details[] = "Missing table: $table";
			}
		}

		// Get database size
		$db_size = $wpdb->get_var(
			'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
			FROM information_schema.TABLES
			WHERE table_schema = DATABASE()'
		);

		$message = $status === 'healthy' ? 'Database healthy' : 'Database errors detected';

		return array(
			'status'     => $status,
			'message'    => $message,
			'db_size_mb' => $db_size,
			'details'    => $details,
		);
	}

	/**
	 * Check cache system availability
	 *
	 * @return array
	 */
	private static function check_cache() {
		$status        = 'healthy';
		$message       = '';
		$cache_backend = 'transients';
		$response_time = 0;

		// Test cache write/read
		$test_key   = 'lgp_health_check_' . time();
		$test_value = 'test_' . wp_rand();

		$start = microtime( true );
		wp_cache_set( $test_key, $test_value, 'loungenie_portal', 60 );
		$write_time = microtime( true ) - $start;

		$start     = microtime( true );
		$result    = wp_cache_get( $test_key, 'loungenie_portal' );
		$read_time = microtime( true ) - $start;

		$response_time = round( ( $write_time + $read_time ) * 1000, 2 ); // ms

		if ( $result !== $test_value ) {
			$status  = 'warning';
			$message = 'Cache write/read test failed';
		} else {
			// Detect cache backend
			if ( function_exists( 'redis_server_version' ) ) {
				$cache_backend = 'redis';
			} elseif ( function_exists( 'memcache_connect' ) ) {
				$cache_backend = 'memcache';
			} elseif ( extension_loaded( 'apcu' ) ) {
				$cache_backend = 'apcu';
			}
			$message = 'Cache working (' . $cache_backend . ')';
		}

		wp_cache_delete( $test_key, 'loungenie_portal' );

		return array(
			'status'           => $status,
			'message'          => $message,
			'backend'          => $cache_backend,
			'response_time_ms' => $response_time,
		);
	}

	/**
	 * Check disk space availability
	 *
	 * @return array
	 */
	private static function check_disk_space() {
		$status  = 'healthy';
		$message = '';

		$free_space    = disk_free_space( ABSPATH );
		$free_space_gb = round( $free_space / 1024 / 1024 / 1024, 2 );

		if ( $free_space < 100 * 1024 * 1024 ) { // Less than 100MB
			$status = 'error';
		} elseif ( $free_space < 500 * 1024 * 1024 ) { // Less than 500MB
			$status = 'warning';
		}

		$message = $free_space_gb . 'GB available';

		return array(
			'status'        => $status,
			'message'       => $message,
			'free_space_gb' => $free_space_gb,
		);
	}

	/**
	 * Check PHP version
	 *
	 * @return array
	 */
	private static function check_php_version() {
		$status  = 'healthy';
		$message = '';

		$current_version  = PHP_VERSION;
		$required_version = '7.4';

		if ( version_compare( $current_version, $required_version, '<' ) ) {
			$status  = 'error';
			$message = "PHP $current_version installed, $required_version required";
		} else {
			$message = "PHP $current_version installed";
		}

		return array(
			'status'   => $status,
			'message'  => $message,
			'version'  => $current_version,
			'required' => $required_version,
		);
	}

	/**
	 * Check WordPress version
	 *
	 * @return array
	 */
	private static function check_wp_version() {
		global $wp_version;

		$status           = 'healthy';
		$required_version = '5.8';

		if ( version_compare( $wp_version, $required_version, '<' ) ) {
			$status = 'error';
		}

		return array(
			'status'   => $status,
			'message'  => "WordPress $wp_version installed",
			'version'  => $wp_version,
			'required' => $required_version,
		);
	}

	/**
	 * Check file upload directory
	 *
	 * @return array
	 */
	private static function check_file_uploads() {
		$upload_dir = wp_upload_dir();
		$status     = 'healthy';
		$message    = '';
		$details    = array();

		if ( ! is_writable( $upload_dir['basedir'] ) ) {
			$status    = 'error';
			$details[] = 'Upload directory not writable: ' . $upload_dir['basedir'];
		}

		$lgp_upload_dir = $upload_dir['basedir'] . '/lgp-attachments';
		if ( is_dir( $lgp_upload_dir ) && ! is_writable( $lgp_upload_dir ) ) {
			$status    = 'warning';
			$details[] = 'LGP upload directory not writable';
		}

		$message = $status === 'healthy' ? 'File uploads working' : 'File upload issues detected';

		return array(
			'status'  => $status,
			'message' => $message,
			'details' => $details,
			'path'    => $upload_dir['basedir'],
		);
	}

	/**
	 * Check required PHP extensions
	 *
	 * @return array
	 */
	private static function check_required_extensions() {
		$required = array(
			'curl',
			'json',
			'mbstring',
			'mysql',
			'mysqli',
			'pdo',
			'spl',
			'zip',
		);

		$installed = array();
		$missing   = array();

		foreach ( $required as $ext ) {
			if ( extension_loaded( $ext ) ) {
				$installed[] = $ext;
			} else {
				$missing[] = $ext;
			}
		}

		return array(
			'installed' => $installed,
			'missing'   => $missing,
			'count'     => count( $installed ) . '/' . count( $required ),
		);
	}

	/**
	 * Check HubSpot connection
	 *
	 * @return array
	 */
	private static function check_hubspot_connection() {
		$settings = get_option( 'lgp_hubspot_settings', array() );
		$status   = 'unconfigured';
		$message  = 'Not configured';

		if ( ! empty( $settings['api_key'] ) ) {
			// Test connection
			$response = wp_remote_get(
				'https://api.hubapi.com/crm/v3/objects/contacts?limit=1',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $settings['api_key'],
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				$status  = 'error';
				$message = 'Connection failed: ' . $response->get_error_message();
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				if ( 200 === $code ) {
					$status  = 'healthy';
					$message = 'Connected';
				} else {
					$status  = 'error';
					$message = "API returned: $code";
				}
			}
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Check Microsoft Graph connection
	 *
	 * @return array
	 */
	private static function check_graph_connection() {
		$settings = get_option( 'lgp_graph_settings', array() );
		$status   = 'unconfigured';
		$message  = 'Not configured';

		if ( ! empty( $settings['client_id'] ) && ! empty( $settings['client_secret'] ) ) {
			$status  = 'healthy';
			$message = 'Configured';
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Check Microsoft SSO configuration
	 *
	 * @return array
	 */
	private static function check_sso_configuration() {
		$settings = get_option( 'lgp_m365_sso_settings', array() );
		$status   = 'unconfigured';
		$message  = 'Not configured';

		if ( ! empty( $settings['client_id'] ) && ! empty( $settings['client_secret'] ) ) {
			$status  = 'healthy';
			$message = 'Configured';
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Get database info
	 *
	 * @return array
	 */
	private static function get_database_info() {
		global $wpdb;

		return array(
			'name'    => DB_NAME,
			'host'    => DB_HOST,
			'version' => $wpdb->db_version(),
			'charset' => DB_CHARSET,
		);
	}

	/**
	 * Get cache info
	 *
	 * @return array
	 */
	private static function get_cache_info() {
		$info = array(
			'wp_cache_enabled' => defined( 'WP_CACHE' ) ? WP_CACHE : false,
			'transients_count' => self::count_transients(),
		);

		if ( function_exists( 'redis_server_version' ) ) {
			$info['redis_version'] = redis_server_version();
		}

		return $info;
	}

	/**
	 * Count active transients
	 *
	 * @return int
	 */
	private static function count_transients() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_lgp_%'"
		);
	}

	/**
	 * Get server uptime
	 *
	 * @return string
	 */
	private static function get_server_uptime() {
		$uptime = exec( 'uptime -p 2>&1' );
		return $uptime ?: 'Unknown';
	}

	/**
	 * Count active users
	 *
	 * @return int
	 */
	private static function count_active_users() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_login) FROM {$wpdb->users}"
		);
	}

	/**
	 * Count pending tickets
	 *
	 * @return int
	 */
	private static function count_pending_tickets() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}lgp_tickets
			WHERE status = 'open'"
		);
	}

	/**
	 * Check if cron schedule is overdue
	 *
	 * @return bool
	 */
	private static function check_cron_schedule() {
		$crons   = _get_cron_array();
		$overdue = 0;

		if ( is_array( $crons ) ) {
			foreach ( $crons as $timestamp => $cron ) {
				if ( $timestamp < time() ) {
					++$overdue;
				}
			}
		}

		return $overdue > 0;
	}

	/**
	 * Check admin permission
	 *
	 * @return bool
	 */
	public static function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Add health check admin page
	 */
	public static function add_admin_page() {
		add_submenu_page(
			'loungenie-portal',
			'System Health',
			'System Health',
			'manage_options',
			'lgp-health-check',
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

		$health = self::get_detailed_health();
		$data   = $health->get_data();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LounGenie Portal - System Health', 'loungenie-portal' ); ?></h1>

			<?php
			$status_class = $data['status'] === 'healthy' ? 'success' : ( $data['status'] === 'degraded' ? 'warning' : 'error' );
			?>
			<div class="notice notice-<?php echo esc_attr( $status_class ); ?>">
				<p>
					<strong><?php esc_html_e( 'Status:', 'loungenie-portal' ); ?></strong>
					<?php echo esc_html( strtoupper( $data['status'] ) ); ?>
					(<?php echo esc_html( $data['timestamp'] ); ?>)
				</p>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'System Checks', 'loungenie-portal' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<?php foreach ( $data['checks'] as $name => $check ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $name ) ) ); ?></th>
								<td>
									<?php
									$badge_class = 'error' === $check['status'] ? 'error' : ( 'warning' === $check['status'] ? 'warning' : 'success' );
									?>
									<span class="badge badge-<?php echo esc_attr( $badge_class ); ?>">
										<?php echo esc_html( strtoupper( $check['status'] ) ); ?>
									</span>
									<?php echo esc_html( $check['message'] ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Metrics', 'loungenie-portal' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Memory Usage', 'loungenie-portal' ); ?></th>
							<td><?php echo esc_html( size_format( $data['metrics']['memory_usage'] ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Peak Memory', 'loungenie-portal' ); ?></th>
							<td><?php echo esc_html( size_format( $data['metrics']['peak_memory'] ) ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Users', 'loungenie-portal' ); ?></th>
							<td><?php echo esc_html( $data['metrics']['active_users'] ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Pending Tickets', 'loungenie-portal' ); ?></th>
							<td><?php echo esc_html( $data['metrics']['pending_tickets'] ); ?></td>
						</tr>
					</table>
				</div>
			</div>

			<style>
				.badge {
					display: inline-block;
					padding: 4px 8px;
					border-radius: 3px;
					color: white;
					font-size: 12px;
					font-weight: bold;
					margin-right: 10px;
				}
				.badge-success {
					background-color: #28a745;
				}
				.badge-warning {
					background-color: #ffc107;
					color: #333;
				}
				.badge-error {
					background-color: #dc3545;
				}
				.postbox {
					margin-top: 20px;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * AJAX handler for real-time health check
	 */
	public static function ajax_health_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access denied', 403 );
		}

		$health = self::get_health_status();
		wp_send_json_success( $health->get_data() );
	}
}

// Initialize on WordPress init
add_action( 'init', array( 'LGP_Health_Check', 'init' ) );
