<?php

/**
 * Security Audit Log System
 * Tracks authentication, authorization, and security-related events
 *
 * @package LounGenie Portal
 * @version 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Security_Audit {

	const TABLE_NAME     = 'lgp_security_audit';
	const RETENTION_DAYS = 90;

	/**
	 * Initialize security audit system
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'create_table' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'wp_ajax_lgp_audit_search', array( __CLASS__, 'ajax_search' ) );
		add_action( 'wp_ajax_lgp_audit_export', array( __CLASS__, 'ajax_export' ) );
		add_action( 'wp_ajax_lgp_audit_clear', array( __CLASS__, 'ajax_clear' ) );

		// Hook into authentication and authorization events
		add_action( 'wp_login_failed', array( __CLASS__, 'log_failed_login' ), 10, 1 );
		add_action( 'lgp_unauthorized_access_attempt', array( __CLASS__, 'log_unauthorized_access' ), 10, 3 );
		add_action( 'lgp_rate_limit_exceeded', array( __CLASS__, 'log_rate_limit' ), 10, 3 );
		add_action( 'lgp_dangerous_action', array( __CLASS__, 'log_dangerous_action' ), 10, 3 );
		add_action( 'lgp_user_permission_changed', array( __CLASS__, 'log_permission_change' ), 10, 3 );

		// Run cleanup on daily schedule
		if ( ! wp_next_scheduled( 'lgp_audit_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'lgp_audit_cleanup' );
		}
		add_action( 'lgp_audit_cleanup', array( __CLASS__, 'cleanup_old_records' ) );
	}

	/**
	 * Create audit log table if not exists
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table already exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			return;
		}

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			user_email VARCHAR(255),
			user_ip VARCHAR(45),
			event_type VARCHAR(100) NOT NULL,
			severity VARCHAR(20) NOT NULL DEFAULT 'info',
			action VARCHAR(255) NOT NULL,
			resource_type VARCHAR(100),
			resource_id BIGINT(20) UNSIGNED,
			details JSON,
			status VARCHAR(50),
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			
			KEY idx_timestamp (timestamp),
			KEY idx_user_id (user_id),
			KEY idx_event_type (event_type),
			KEY idx_severity (severity),
			KEY idx_user_ip (user_ip),
			FULLTEXT KEY ft_action (action)
		) $charset_collate;";

		// @phpstan-ignore-next-line dbDelta is WordPress core function
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		if ( ! empty( $wpdb->last_error ) ) {
			error_log( 'LGP Security Audit table creation error: ' . $wpdb->last_error );
		}
	}

	/**
	 * Log event to audit table
	 *
	 * @param string      $event_type Event type (login_failed, unauthorized_access, etc.)
	 * @param string      $severity Severity level (info, warning, error, critical).
	 * @param string      $action Human-readable action description.
	 * @param string|null $resource_type Resource type (user, ticket, company, etc.).
	 * @param int|null    $resource_id Resource ID.
	 * @param array       $details Additional details to store as JSON.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function log_event( $event_type, $severity, $action, $resource_type = null, $resource_id = null, $details = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$user_id    = get_current_user_id();
		$user       = get_userdata( $user_id );
		$user_email = $user ? $user->user_email : '';
		$user_ip    = self::get_client_ip();

		$insert_data = array(
			'user_id'       => $user_id ?: null,
			'user_email'    => $user_email,
			'user_ip'       => $user_ip,
			'event_type'    => sanitize_text_field( $event_type ),
			'severity'      => sanitize_text_field( $severity ),
			'action'        => sanitize_text_field( $action ),
			'resource_type' => $resource_type ? sanitize_text_field( $resource_type ) : null,
			'resource_id'   => $resource_id ? absint( $resource_id ) : null,
			'details'       => ! empty( $details ) ? wp_json_encode( $details ) : null,
			'timestamp'     => current_time( 'mysql' ),
		);

		$insert_formats = array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
		);

		$result = $wpdb->insert( $table_name, $insert_data, $insert_formats );

		if ( false === $result ) {
			error_log( 'LGP Security Audit log error: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Log failed login attempt
	 *
	 * @param string $username Username attempted.
	 */
	public static function log_failed_login( $username ) {
		$ip = self::get_client_ip();

		self::log_event(
			'login_failed',
			'warning',
			"Failed login attempt for user: $username",
			'user',
			null,
			array(
				'username' => $username,
				'ip'       => $ip,
			)
		);

		// Check for brute force attempts
		$count = self::count_failed_attempts( $ip, 300 ); // Last 5 minutes
		if ( $count > 5 ) {
			self::log_event(
				'brute_force_detected',
				'critical',
				"Brute force attempt detected from IP: $ip",
				'ip',
				null,
				array(
					'ip'       => $ip,
					'attempts' => $count,
				)
			);
		}
	}

	/**
	 * Log unauthorized access attempt
	 *
	 * @param int    $user_id User ID.
	 * @param string $action Action attempted.
	 * @param string $reason Reason for denial.
	 */
	public static function log_unauthorized_access( $user_id, $action, $reason ) {
		$user     = get_userdata( $user_id );
		$username = $user ? $user->user_login : "User #$user_id";

		self::log_event(
			'unauthorized_access',
			'error',
			"Unauthorized access: $action - $reason",
			'user',
			$user_id,
			array(
				'username' => $username,
				'action'   => $action,
				'reason'   => $reason,
			)
		);
	}

	/**
	 * Log rate limit exceeded
	 *
	 * @param int    $user_id User ID.
	 * @param string $action Action that was rate limited.
	 * @param int    $limit Limit that was exceeded.
	 */
	public static function log_rate_limit( $user_id, $action, $limit ) {
		self::log_event(
			'rate_limit_exceeded',
			'warning',
			"Rate limit exceeded for: $action (limit: $limit/hour)",
			'user',
			$user_id,
			array(
				'action' => $action,
				'limit'  => $limit,
			)
		);
	}

	/**
	 * Log dangerous action (bulk delete, export, etc)
	 *
	 * @param int    $user_id User ID.
	 * @param string $action Action name.
	 * @param array  $details Action details.
	 */
	public static function log_dangerous_action( $user_id, $action, $details = array() ) {
		self::log_event(
			'dangerous_action',
			'warning',
			"Dangerous action performed: $action",
			'user',
			$user_id,
			array_merge( array( 'action' => $action ), $details )
		);
	}

	/**
	 * Log permission changes
	 *
	 * @param int    $user_id User being modified.
	 * @param string $change Type of change (role_changed, capability_granted, etc).
	 * @param array  $details Change details.
	 */
	public static function log_permission_change( $user_id, $change, $details = array() ) {
		$user = get_userdata( $user_id );

		self::log_event(
			'permission_changed',
			'warning',
			"User permission changed: $change",
			'user',
			$user_id,
			array_merge(
				array(
					'username' => $user ? $user->user_login : "User #$user_id",
					'change'   => $change,
				),
				$details
			)
		);
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// Cloudflare
			$ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Proxy
			$forwarded = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip        = trim( $forwarded[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			// Direct
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'Unknown';
	}

	/**
	 * Count failed attempts from IP in time window
	 *
	 * @param string $ip IP address.
	 * @param int    $seconds Time window in seconds.
	 * @return int Count of failed attempts.
	 */
	private static function count_failed_attempts( $ip, $seconds ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$time_ago   = date( 'Y-m-d H:i:s', time() - $seconds );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name
				WHERE user_ip = %s
				AND event_type = 'login_failed'
				AND timestamp > %s",
				$ip,
				$time_ago
			)
		);
	}

	/**
	 * Cleanup old audit records
	 */
	public static function cleanup_old_records() {
		global $wpdb;

		$table_name  = $wpdb->prefix . self::TABLE_NAME;
		$cutoff_date = date( 'Y-m-d H:i:s', time() - ( self::RETENTION_DAYS * 24 * 60 * 60 ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE timestamp < %s",
				$cutoff_date
			)
		);

		if ( $deleted > 0 ) {
			error_log( "LGP Security Audit: Deleted $deleted old records" );
		}
	}

	/**
	 * Add admin page
	 */
	public static function add_admin_page() {
		add_submenu_page(
			'loungenie-portal',
			'Security Audit Log',
			'Security Log',
			'manage_options',
			'lgp-security-audit',
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

		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Get filters
		$filter_event    = isset( $_GET['filter_event'] ) ? sanitize_text_field( $_GET['filter_event'] ) : '';
		$filter_severity = isset( $_GET['filter_severity'] ) ? sanitize_text_field( $_GET['filter_severity'] ) : '';
		$filter_user     = isset( $_GET['filter_user'] ) ? sanitize_text_field( $_GET['filter_user'] ) : '';
		$search          = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$paged           = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page        = 25;

		// Build query
		$where_clauses = array();
		$where_values  = array();

		if ( ! empty( $filter_event ) ) {
			$where_clauses[] = 'event_type = %s';
			$where_values[]  = $filter_event;
		}

		if ( ! empty( $filter_severity ) ) {
			$where_clauses[] = 'severity = %s';
			$where_values[]  = $filter_severity;
		}

		if ( ! empty( $filter_user ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[]  = absint( $filter_user );
		}

		if ( ! empty( $search ) ) {
			$where_clauses[] = 'action LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_sql = ! empty( $where_clauses ) ? ' WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Count total
		$total       = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" . $where_sql, 0, $where_values );
		$total_pages = ceil( $total / $per_page );
		$offset      = ( $paged - 1 ) * $per_page;

		// Get records
		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name" . $where_sql . '
				ORDER BY timestamp DESC
				LIMIT %d OFFSET %d',
				array_merge( $where_values, array( $per_page, $offset ) )
			)
		);

		// Get distinct values for filters
		$event_types = $wpdb->get_col( "SELECT DISTINCT event_type FROM $table_name ORDER BY event_type" );
		$severities  = $wpdb->get_col( "SELECT DISTINCT severity FROM $table_name ORDER BY severity" );
		$users       = $wpdb->get_results( "SELECT ID, user_login FROM {$wpdb->users} ORDER BY user_login LIMIT 20" );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LounGenie Portal - Security Audit Log', 'loungenie-portal' ); ?></h1>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Filters', 'loungenie-portal' ); ?></h2>
				<form method="GET" action="">
					<input type="hidden" name="page" value="lgp-security-audit" />

					<div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
						<div>
							<label><?php esc_html_e( 'Event Type:', 'loungenie-portal' ); ?></label>
							<select name="filter_event">
								<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
								<?php foreach ( $event_types as $type ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $filter_event, $type ); ?>>
										<?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div>
							<label><?php esc_html_e( 'Severity:', 'loungenie-portal' ); ?></label>
							<select name="filter_severity">
								<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
								<?php foreach ( $severities as $sev ) : ?>
									<option value="<?php echo esc_attr( $sev ); ?>" <?php selected( $filter_severity, $sev ); ?>>
										<?php echo esc_html( ucfirst( $sev ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div>
							<label><?php esc_html_e( 'User:', 'loungenie-portal' ); ?></label>
							<select name="filter_user">
								<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
								<?php foreach ( $users as $user ) : ?>
									<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $filter_user, $user->ID ); ?>>
										<?php echo esc_html( $user->user_login ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div>
							<label><?php esc_html_e( 'Search Action:', 'loungenie-portal' ); ?></label>
							<input type="text" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search..." />
						</div>
					</div>

					<div style="display: flex; gap: 10px;">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'loungenie-portal' ); ?></button>
						<a href="?page=lgp-security-audit" class="button"><?php esc_html_e( 'Clear Filters', 'loungenie-portal' ); ?></a>
						<button type="button" class="button" onclick="lgpAuditExport();"><?php esc_html_e( 'Export CSV', 'loungenie-portal' ); ?></button>
						<button type="button" class="button button-danger" onclick="if(confirm('<?php esc_attr_e( 'Clear all audit logs?', 'loungenie-portal' ); ?>')) lgpAuditClear();">
							<?php esc_html_e( 'Clear All', 'loungenie-portal' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Audit Events', 'loungenie-portal' ); ?> (<?php echo esc_html( $total ); ?>)</h2>
				<table class="wp-list-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Timestamp', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Event Type', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Severity', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'User', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Action', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'IP Address', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $records ) ) : ?>
							<?php foreach ( $records as $record ) : ?>
								<tr>
									<td><?php echo esc_html( $record->timestamp ); ?></td>
									<td>
										<code><?php echo esc_html( $record->event_type ); ?></code>
									</td>
									<td>
										<?php
										$badge_color = match ( $record->severity ) {
											'critical' => '#dc3545',
											'error' => '#e74c3c',
											'warning' => '#f39c12',
											default => '#3498db',
										};
	?>
										<span style="background-color: <?php echo esc_attr( $badge_color ); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">
											<?php echo esc_html( ucfirst( $record->severity ) ); ?>
										</span>
									</td>
									<td>
										<?php
										if ( ! empty( $record->user_id ) ) {
											$user = get_userdata( $record->user_id );
											echo esc_html( $user ? $user->user_login : "ID: {$record->user_id}" );
										} else {
											echo '—';
										}
										?>
									</td>
									<td><?php echo esc_html( $record->action ); ?></td>
									<td><code><?php echo esc_html( $record->user_ip ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" style="text-align: center; padding: 20px;">
									<?php esc_html_e( 'No audit events found.', 'loungenie-portal' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav">
						<?php
						$pagination = paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
								'type'      => 'array',
							)
						);
						if ( $pagination ) {
							echo wp_kses_post( implode( "\n", $pagination ) );
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
			function lgpAuditExport() {
				const params = new URLSearchParams(window.location.search);
				params.delete('page');
				params.append('action', 'lgp_audit_export');

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: params,
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					}
				})
				.then(response => response.blob())
				.then(blob => {
					const url = window.URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.href = url;
					a.download = 'lgp-audit-' + new Date().toISOString().split('T')[0] + '.csv';
					document.body.appendChild(a);
					a.click();
					window.URL.revokeObjectURL(url);
					a.remove();
				});
			}

			function lgpAuditClear() {
				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: new URLSearchParams({ action: 'lgp_audit_clear' }),
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						window.location.reload();
					}
				});
			}
		</script>

		<style>
			.button-danger {
				background-color: #dc3545;
				border-color: #dc3545;
				color: white;
			}
			.button-danger:hover {
				background-color: #c82333;
				border-color: #bd2130;
				color: white;
			}
			select {
				padding: 8px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			input[type="text"] {
				padding: 8px;
				border: 1px solid #ddd;
				border-radius: 4px;
				width: 100%;
			}
			label {
				display: block;
				margin-bottom: 5px;
				font-weight: bold;
			}
		</style>
		<?php
	}

	/**
	 * AJAX handler for search
	 */
	public static function ajax_search() {
		check_ajax_referer( 'lgp_security_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access denied' );
		}

		// Handled by page reload with filter parameters
		wp_send_json_success();
	}

	/**
	 * AJAX handler for export
	 */
	public static function ajax_export() {
		check_ajax_referer( 'wp_nonce', '_wpnonce', false );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access denied' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Get all records (respecting same filters as admin page)
		$records = $wpdb->get_results(
			"SELECT timestamp, user_id, user_email, user_ip, event_type, severity, action, resource_type, resource_id
			FROM $table_name
			ORDER BY timestamp DESC"
		);

		// Generate CSV
		$csv = "Timestamp,User ID,User Email,IP Address,Event Type,Severity,Action,Resource Type,Resource ID\n";

		foreach ( $records as $record ) {
			$csv .= sprintf(
				'"%s","%d","%s","%s","%s","%s","%s","%s","%s"' . "\n",
				$record->timestamp,
				$record->user_id,
				$record->user_email,
				$record->user_ip,
				$record->event_type,
				$record->severity,
				str_replace( '"', '""', $record->action ),
				$record->resource_type,
				$record->resource_id
			);
		}

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="lgp-audit-' . date( 'Y-m-d' ) . '.csv"' );
		echo $csv;
		exit;
	}

	/**
	 * AJAX handler for clear
	 */
	public static function ajax_clear() {
		check_ajax_referer( 'wp_nonce', '_wpnonce', false );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Access denied' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->query( "TRUNCATE TABLE $table_name" );

		wp_send_json_success();
	}
}

// Initialize
add_action( 'init', array( 'LGP_Security_Audit', 'init' ) );
