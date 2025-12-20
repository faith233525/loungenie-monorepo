<?php
/**
 * System Health Class
 * Monitors OAuth tokens, system requirements, and error logs
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_System_Health {

	/**
	 * Initialize system health monitoring
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_post_lgp_clear_error_log', array( __CLASS__, 'handle_clear_errors' ) );
	}

	/**
	 * Add System Health admin page
	 */
	public static function add_admin_page() {
		add_management_page(
			__( 'LounGenie System Health', 'loungenie-portal' ),
			__( 'LounGenie Health', 'loungenie-portal' ),
			'manage_options',
			'lgp-system-health',
			array( __CLASS__, 'render_health_page' )
		);
	}

	/**
	 * Render System Health page
	 */
	public static function render_health_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$health_data = self::gather_health_data();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LounGenie System Health', 'loungenie-portal' ); ?></h1>
			
			<?php if ( isset( $_GET['cleared'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Error log cleared successfully.', 'loungenie-portal' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Overall Status -->
			<div class="lgp-health-summary">
				<h2><?php esc_html_e( 'Overall Status', 'loungenie-portal' ); ?></h2>
				<div class="lgp-health-badge lgp-health-<?php echo esc_attr( $health_data['overall_status'] ); ?>">
					<?php echo esc_html( ucfirst( $health_data['overall_status'] ) ); ?>
				</div>
			</div>

			<!-- OAuth Token Status -->
			<div class="lgp-health-section">
				<h2><?php esc_html_e( 'Microsoft OAuth Status', 'loungenie-portal' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Outlook Integration', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php if ( $health_data['outlook']['configured'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓ <?php esc_html_e( 'Configured', 'loungenie-portal' ); ?></span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-danger">✗ <?php esc_html_e( 'Not Configured', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<?php if ( $health_data['outlook']['configured'] ) : ?>
						<tr>
							<td><?php esc_html_e( 'Access Token', 'loungenie-portal' ); ?></td>
							<td>
								<?php if ( $health_data['outlook']['has_token'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓ <?php esc_html_e( 'Present', 'loungenie-portal' ); ?></span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">⚠ <?php esc_html_e( 'Missing', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Token Expiration', 'loungenie-portal' ); ?></td>
							<td>
								<?php if ( $health_data['outlook']['expires_at'] ) : ?>
									<?php
									$time_until   = $health_data['outlook']['expires_at'] - time();
									$hours_until  = round( $time_until / 3600 );
									$status_class = $time_until < 3600 ? 'danger' : ( $time_until < 86400 ? 'warning' : 'success' );
									?>
									<span class="lgp-badge lgp-badge-<?php echo esc_attr( $status_class ); ?>">
										<?php
										if ( $time_until < 0 ) {
											esc_html_e( 'Expired', 'loungenie-portal' );
										} elseif ( $hours_until < 24 ) {
											printf( esc_html__( '%d hours', 'loungenie-portal' ), $hours_until );
										} else {
											printf( esc_html__( '%d days', 'loungenie-portal' ), round( $hours_until / 24 ) );
										}
										?>
									</span>
									<br>
									<small><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $health_data['outlook']['expires_at'] ) . ' UTC' ); ?></small>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">N/A</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Redirect Mode', 'loungenie-portal' ); ?></td>
							<td>
								<code><?php echo esc_html( $health_data['outlook']['redirect_mode'] ); ?></code>
								<?php if ( $health_data['outlook']['redirect_mode'] === 'front' ) : ?>
									<span class="lgp-badge lgp-badge-success">✓ <?php esc_html_e( 'Azure-compliant', 'loungenie-portal' ); ?></span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">⚠ <?php esc_html_e( 'May not work with Azure', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Current Redirect URI', 'loungenie-portal' ); ?></td>
							<td><code><?php echo esc_html( $health_data['outlook']['redirect_uri'] ); ?></code></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- System Requirements -->
			<div class="lgp-health-section">
				<h2><?php esc_html_e( 'System Requirements', 'loungenie-portal' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'PHP Version', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php echo esc_html( $health_data['system']['php_version'] ); ?>
								<?php if ( $health_data['system']['php_ok'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓</span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-danger">✗ <?php esc_html_e( 'Requires 7.4+', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WordPress Version', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php echo esc_html( $health_data['system']['wp_version'] ); ?>
								<?php if ( $health_data['system']['wp_ok'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓</span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-danger">✗ <?php esc_html_e( 'Requires 5.8+', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'HTTPS', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php if ( $health_data['system']['https_enabled'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓ <?php esc_html_e( 'Enabled', 'loungenie-portal' ); ?></span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">⚠ <?php esc_html_e( 'Not enabled', 'loungenie-portal' ); ?></span>
									<br><small><?php esc_html_e( 'HTTPS required for Azure OAuth and security headers', 'loungenie-portal' ); ?></small>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'cURL Extension', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php if ( $health_data['system']['curl_enabled'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓ <?php esc_html_e( 'Enabled', 'loungenie-portal' ); ?></span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-danger">✗ <?php esc_html_e( 'Not enabled', 'loungenie-portal' ); ?></span>
									<br><small><?php esc_html_e( 'Required for Microsoft Graph and HubSpot API calls', 'loungenie-portal' ); ?></small>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Memory Limit', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php echo esc_html( $health_data['system']['memory_limit'] ); ?>
								<?php if ( $health_data['system']['memory_ok'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓</span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">⚠ <?php esc_html_e( 'Recommend 256M+', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Max Execution Time', 'loungenie-portal' ); ?></strong></td>
							<td>
								<?php echo esc_html( $health_data['system']['max_execution_time'] ); ?>s
								<?php if ( $health_data['system']['execution_time_ok'] ) : ?>
									<span class="lgp-badge lgp-badge-success">✓</span>
								<?php else : ?>
									<span class="lgp-badge lgp-badge-warning">⚠ <?php esc_html_e( 'Recommend 60s+', 'loungenie-portal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Error Log -->
			<div class="lgp-health-section">
				<h2>
					<?php esc_html_e( 'Recent Errors', 'loungenie-portal' ); ?>
					<span class="lgp-badge"><?php echo esc_html( count( $health_data['errors'] ) ); ?></span>
				</h2>
				
				<?php if ( ! empty( $health_data['errors'] ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 1rem;">
						<input type="hidden" name="action" value="lgp_clear_error_log">
						<?php wp_nonce_field( 'lgp_clear_errors' ); ?>
						<button type="submit" class="button" onclick="return confirm('<?php esc_attr_e( 'Clear all error logs?', 'loungenie-portal' ); ?>');">
							<?php esc_html_e( 'Clear Error Log', 'loungenie-portal' ); ?>
						</button>
					</form>
					
					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width: 180px;"><?php esc_html_e( 'Timestamp', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'Message', 'loungenie-portal' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $health_data['errors'], 0, 50 ) as $error ) : ?>
								<tr>
									<td><code><?php echo esc_html( $error['timestamp'] ); ?></code></td>
									<td><?php echo esc_html( $error['message'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( count( $health_data['errors'] ) > 50 ) : ?>
						<p><em><?php printf( esc_html__( 'Showing 50 of %d errors', 'loungenie-portal' ), count( $health_data['errors'] ) ); ?></em></p>
					<?php endif; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No errors logged.', 'loungenie-portal' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Quick Links -->
			<div class="lgp-health-section">
				<h2><?php esc_html_e( 'Quick Links', 'loungenie-portal' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=lgp-outlook-settings' ) ); ?>" class="button">
						<?php esc_html_e( 'Outlook Integration Settings', 'loungenie-portal' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>" class="button">
						<?php esc_html_e( 'Flush Permalinks', 'loungenie-portal' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/portal' ) ); ?>" class="button" target="_blank">
						<?php esc_html_e( 'View Portal', 'loungenie-portal' ); ?>
					</a>
				</p>
			</div>

			<style>
				.lgp-health-summary {
					background: #fff;
					padding: 20px;
					border: 1px solid #ccd0d4;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					margin-bottom: 20px;
				}
				.lgp-health-badge {
					display: inline-block;
					padding: 10px 20px;
					border-radius: 4px;
					font-weight: 600;
					font-size: 16px;
				}
				.lgp-health-badge.lgp-health-healthy {
					background: #d4edda;
					color: #155724;
				}
				.lgp-health-badge.lgp-health-warning {
					background: #fff3cd;
					color: #856404;
				}
				.lgp-health-badge.lgp-health-critical {
					background: #f8d7da;
					color: #721c24;
				}
				.lgp-health-section {
					background: #fff;
					padding: 20px;
					border: 1px solid #ccd0d4;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					margin-bottom: 20px;
				}
				.lgp-health-section h2 {
					margin-top: 0;
					padding-bottom: 10px;
					border-bottom: 1px solid #ccd0d4;
				}
				.lgp-badge {
					display: inline-block;
					padding: 2px 8px;
					border-radius: 3px;
					font-size: 12px;
					font-weight: 600;
				}
				.lgp-badge-success {
					background: #d4edda;
					color: #155724;
				}
				.lgp-badge-warning {
					background: #fff3cd;
					color: #856404;
				}
				.lgp-badge-danger {
					background: #f8d7da;
					color: #721c24;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Gather system health data
	 *
	 * @return array
	 */
	private static function gather_health_data() {
		global $wp_version;

		// Outlook OAuth status
		$outlook_client_id     = get_option( 'lgp_outlook_client_id' );
		$outlook_client_secret = get_option( 'lgp_outlook_client_secret' );
		$outlook_token         = get_option( 'lgp_outlook_access_token' );
		$outlook_expires       = get_option( 'lgp_outlook_token_expires' );
		$redirect_mode         = get_option( 'lgp_outlook_redirect_mode', 'admin' );

		// Build redirect URI
		$redirect_uri = '';
		if ( class_exists( 'LGP_Outlook' ) ) {
			$reflection = new ReflectionClass( 'LGP_Outlook' );
			$method     = $reflection->getMethod( 'get_redirect_uri' );
			$method->setAccessible( true );
			$redirect_uri = $method->invoke( null );
		}

		$outlook_data = array(
			'configured'    => ! empty( $outlook_client_id ) && ! empty( $outlook_client_secret ),
			'has_token'     => ! empty( $outlook_token ),
			'expires_at'    => $outlook_expires ? (int) $outlook_expires : null,
			'redirect_mode' => $redirect_mode,
			'redirect_uri'  => $redirect_uri,
		);

		// System requirements
		$memory_limit = ini_get( 'memory_limit' );
		$memory_bytes = self::parse_size( $memory_limit );
		$max_exec     = ini_get( 'max_execution_time' );

		$system_data = array(
			'php_version'        => PHP_VERSION,
			'php_ok'             => version_compare( PHP_VERSION, '7.4', '>=' ),
			'wp_version'         => $wp_version,
			'wp_ok'              => version_compare( $wp_version, '5.8', '>=' ),
			'https_enabled'      => is_ssl(),
			'curl_enabled'       => function_exists( 'curl_version' ),
			'memory_limit'       => $memory_limit,
			'memory_ok'          => $memory_bytes >= 256 * 1024 * 1024,
			'max_execution_time' => $max_exec,
			'execution_time_ok'  => $max_exec == 0 || $max_exec >= 60,
		);

		// Error log
		$errors = get_option( 'lgp_outlook_errors', array() );

		// Overall status
		$critical_issues = 0;
		$warnings        = 0;

		if ( ! $system_data['php_ok'] || ! $system_data['wp_ok'] || ! $system_data['curl_enabled'] ) {
			$critical_issues++;
		}
		if ( $outlook_data['configured'] && ! $outlook_data['has_token'] ) {
			$warnings++;
		}
		if ( $outlook_data['expires_at'] && $outlook_data['expires_at'] < time() ) {
			$warnings++;
		}
		if ( ! $system_data['https_enabled'] ) {
			$warnings++;
		}
		if ( ! $system_data['memory_ok'] || ! $system_data['execution_time_ok'] ) {
			$warnings++;
		}

		$overall_status = 'healthy';
		if ( $critical_issues > 0 ) {
			$overall_status = 'critical';
		} elseif ( $warnings > 0 ) {
			$overall_status = 'warning';
		}

		return array(
			'overall_status' => $overall_status,
			'outlook'        => $outlook_data,
			'system'         => $system_data,
			'errors'         => $errors,
		);
	}

	/**
	 * Parse size string to bytes
	 *
	 * @param string $size Size string (e.g., "256M")
	 * @return int
	 */
	private static function parse_size( $size ) {
		$unit  = strtoupper( substr( $size, -1 ) );
		$value = (int) substr( $size, 0, -1 );

		switch ( $unit ) {
			case 'G':
				$value *= 1024;
				// Fall through.
			case 'M':
				$value *= 1024;
				// Fall through.
			case 'K':
				$value *= 1024;
		}

		return $value;
	}

	/**
	 * Handle clear errors action
	 */
	public static function handle_clear_errors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'loungenie-portal' ) );
		}

		check_admin_referer( 'lgp_clear_errors' );

		update_option( 'lgp_outlook_errors', array() );

		wp_redirect( admin_url( 'tools.php?page=lgp-system-health&cleared=1' ) );
		exit;
	}
}
