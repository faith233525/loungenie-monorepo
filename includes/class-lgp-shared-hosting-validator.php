<?php
/**
 * Shared Hosting Validator & Performance Monitor
 *
 * Validates plugin configuration for shared hosting environments
 * Provides real-time monitoring and recommendations
 *
 * @package LounGenie Portal
 * @version 1.8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Hosting Validator Class
 */
class LGP_Shared_Hosting_Validator {

	/**
	 * Run complete validation suite
	 *
	 * @return array Validation results
	 */
	public static function validate_all() {
		$results = array(
			'php_config'        => self::validate_php_config(),
			'database'          => self::validate_database(),
			'memory_usage'      => self::validate_memory(),
			'query_performance' => self::validate_queries(),
			'caching'           => self::validate_caching(),
			'cron'              => self::validate_cron(),
			'file_uploads'      => self::validate_file_uploads(),
			'rate_limiting'     => self::validate_rate_limiting(),
			'recommendations'   => self::get_recommendations(),
		);

		$results['overall_status'] = self::calculate_overall_status( $results );
		$results['timestamp']      = current_time( 'mysql' );

		return $results;
	}

	/**
	 * Validate PHP configuration for shared hosting
	 *
	 * @return array Validation results
	 */
	private static function validate_php_config() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check PHP version
		$php_version = phpversion();
		if ( version_compare( $php_version, '7.4', '<' ) ) {
			$results['issues'][] = array(
				'severity' => 'critical',
				'message'  => sprintf( 'PHP version %s is below minimum 7.4', $php_version ),
			);
			$results['status']   = 'fail';
		} elseif ( version_compare( $php_version, '8.0', '<' ) ) {
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => sprintf( 'PHP version %s should be upgraded to 8.0+', $php_version ),
			);
			$results['status']   = 'warning';
		}

		// Check max_execution_time
		$max_exec = (int) ini_get( 'max_execution_time' );
		if ( $max_exec > 0 && $max_exec < 30 ) {
			$results['issues'][] = array(
				'severity'    => 'warning',
				'message'     => sprintf( 'max_execution_time is %ds, should be at least 30s', $max_exec ),
				'current'     => $max_exec . 's',
				'recommended' => '30s',
			);
			if ( $results['status'] === 'pass' ) {
				$results['status'] = 'warning';
			}
		}

		// Check memory_limit
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		if ( $memory_limit < 134217728 ) { // 128MB
			$results['issues'][] = array(
				'severity'    => 'warning',
				'message'     => sprintf( 'memory_limit is %dMB, recommended 128MB+', round( $memory_limit / 1024 / 1024 ) ),
				'current'     => size_format( $memory_limit ),
				'recommended' => '128MB',
			);
			if ( $results['status'] === 'pass' ) {
				$results['status'] = 'warning';
			}
		}

		// Check post_max_size
		$post_max = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
		if ( $post_max < 10485760 ) { // 10MB
			$results['issues'][] = array(
				'severity' => 'info',
				'message'  => sprintf( 'post_max_size is %dMB, recommended 10MB+', round( $post_max / 1024 / 1024 ) ),
			);
		}

		$results['details'] = array(
			'php_version'        => $php_version,
			'memory_limit'       => size_format( $memory_limit ),
			'max_execution_time' => $max_exec . 's',
			'post_max_size'      => size_format( $post_max ),
		);

		return $results;
	}

	/**
	 * Validate database configuration
	 *
	 * @return array Validation results
	 */
	private static function validate_database() {
		global $wpdb;

		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check for missing indexes
		$missing_indexes = self::check_missing_indexes();
		if ( ! empty( $missing_indexes ) ) {
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => 'Missing database indexes detected',
				'details'  => $missing_indexes,
			);
			$results['status']   = 'warning';
		}

		// Check database connection
		if ( ! $wpdb->check_connection( false ) ) {
			$results['issues'][] = array(
				'severity' => 'critical',
				'message'  => 'Database connection failed',
			);
			$results['status']   = 'fail';
		}

		// Check table sizes
		$table_sizes  = self::check_table_sizes();
		$large_tables = array_filter(
			$table_sizes,
			function ( $size ) {
				return $size > 104857600; // 100MB
			}
		);

		if ( ! empty( $large_tables ) ) {
			$results['issues'][] = array(
				'severity' => 'info',
				'message'  => 'Large tables detected - consider optimization',
				'tables'   => $large_tables,
			);
		}

		$results['details'] = array(
			'table_sizes' => $table_sizes,
			'total_size'  => array_sum( $table_sizes ),
		);

		return $results;
	}

	/**
	 * Validate memory usage
	 *
	 * @return array Validation results
	 */
	private static function validate_memory() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		$current_usage = memory_get_usage( true );
		$peak_usage    = memory_get_peak_usage( true );
		$memory_limit  = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );

		$usage_percent = ( $peak_usage / $memory_limit ) * 100;

		if ( $usage_percent > 90 ) {
			$results['issues'][] = array(
				'severity' => 'critical',
				'message'  => sprintf( 'Memory usage at %.1f%% of limit', $usage_percent ),
			);
			$results['status']   = 'fail';
		} elseif ( $usage_percent > 75 ) {
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => sprintf( 'Memory usage at %.1f%% of limit', $usage_percent ),
			);
			$results['status']   = 'warning';
		}

		$results['details'] = array(
			'current_usage' => size_format( $current_usage ),
			'peak_usage'    => size_format( $peak_usage ),
			'memory_limit'  => size_format( $memory_limit ),
			'usage_percent' => round( $usage_percent, 1 ) . '%',
		);

		return $results;
	}

	/**
	 * Validate query performance
	 *
	 * @return array Validation results
	 */
	private static function validate_queries() {
		global $wpdb;

		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check slow queries if Query Monitor available
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$slow_queries = array_filter(
				$wpdb->queries,
				function ( $query ) {
					return isset( $query[1] ) && $query[1] > 0.1; // > 100ms
				}
			);

			if ( ! empty( $slow_queries ) ) {
				$results['issues'][] = array(
					'severity' => 'warning',
					'message'  => sprintf( '%d slow queries detected (>100ms)', count( $slow_queries ) ),
				);
				$results['status']   = 'warning';
			}

			$results['details'] = array(
				'total_queries' => count( $wpdb->queries ),
				'slow_queries'  => count( $slow_queries ),
			);
		} else {
			$results['details'] = array(
				'message' => 'Query monitoring not enabled (set SAVEQUERIES to true)',
			);
		}

		return $results;
	}

	/**
	 * Validate caching configuration
	 *
	 * @return array Validation results
	 */
	private static function validate_caching() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check for object caching
		if ( ! wp_using_ext_object_cache() ) {
			$results['issues'][] = array(
				'severity' => 'info',
				'message'  => 'External object cache not detected - consider Redis/Memcached',
			);
		}

		// Check transient usage
		$transient_count = self::count_transients();
		if ( $transient_count > 1000 ) {
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => sprintf( '%d transients stored - consider cleanup', $transient_count ),
			);
			$results['status']   = 'warning';
		}

		$results['details'] = array(
			'object_cache_enabled' => wp_using_ext_object_cache(),
			'transient_count'      => $transient_count,
		);

		return $results;
	}

	/**
	 * Validate WP-Cron configuration
	 *
	 * @return array Validation results
	 */
	private static function validate_cron() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check if cron is disabled
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$results['issues'][] = array(
				'severity' => 'info',
				'message'  => 'WP-Cron is disabled - ensure system cron is configured',
			);
		}

		// Check scheduled events
		$cron_events = _get_cron_array();
		$lgp_events  = array();

		foreach ( $cron_events as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( strpos( $hook, 'lgp_' ) === 0 ) {
					$lgp_events[ $hook ] = $timestamp;
				}
			}
		}

		$results['details'] = array(
			'cron_enabled'     => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'lgp_events'       => count( $lgp_events ),
			'scheduled_events' => $lgp_events,
		);

		return $results;
	}

	/**
	 * Validate file upload configuration
	 *
	 * @return array Validation results
	 */
	private static function validate_file_uploads() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		$upload_max = wp_max_upload_size();

		if ( $upload_max < 10485760 ) { // 10MB
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => sprintf( 'Upload max size is %s, recommended 10MB+', size_format( $upload_max ) ),
			);
			$results['status']   = 'warning';
		}

		// Check upload directory writability
		$upload_dir = wp_upload_dir();
		if ( ! is_writable( $upload_dir['basedir'] ) ) {
			$results['issues'][] = array(
				'severity' => 'critical',
				'message'  => 'Upload directory is not writable',
			);
			$results['status']   = 'fail';
		}

		$results['details'] = array(
			'max_upload_size' => size_format( $upload_max ),
			'upload_dir'      => $upload_dir['basedir'],
			'writable'        => is_writable( $upload_dir['basedir'] ),
		);

		return $results;
	}

	/**
	 * Validate rate limiting configuration
	 *
	 * @return array Validation results
	 */
	private static function validate_rate_limiting() {
		$results = array(
			'status' => 'pass',
			'issues' => array(),
		);

		// Check if rate limiter class exists
		if ( ! class_exists( 'LGP_Rate_Limiter' ) ) {
			$results['issues'][] = array(
				'severity' => 'warning',
				'message'  => 'Rate limiter class not found',
			);
			$results['status']   = 'warning';
		}

		$results['details'] = array(
			'rate_limiter_active' => class_exists( 'LGP_Rate_Limiter' ),
		);

		return $results;
	}

	/**
	 * Check for missing database indexes
	 *
	 * @return array Missing indexes
	 */
	private static function check_missing_indexes() {
		global $wpdb;

		$missing = array();
		$tables  = array(
			$wpdb->prefix . 'lgp_tickets'            => array( 'service_request_id', 'status', 'email_reference' ),
			$wpdb->prefix . 'lgp_service_requests'   => array( 'company_id', 'unit_id', 'status' ),
			$wpdb->prefix . 'lgp_units'              => array( 'company_id', 'status' ),
			$wpdb->prefix . 'lgp_ticket_attachments' => array( 'ticket_id', 'uploaded_by' ),
		);

		foreach ( $tables as $table => $required_indexes ) {
			$existing_indexes = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );
			$existing_columns = wp_list_pluck( $existing_indexes, 'Column_name' );

			foreach ( $required_indexes as $column ) {
				if ( ! in_array( $column, $existing_columns, true ) ) {
					$missing[] = "{$table}.{$column}";
				}
			}
		}

		return $missing;
	}

	/**
	 * Check table sizes
	 *
	 * @return array Table sizes in bytes
	 */
	private static function check_table_sizes() {
		global $wpdb;

		$sizes  = array();
		$tables = array(
			'lgp_companies',
			'lgp_units',
			'lgp_service_requests',
			'lgp_tickets',
			'lgp_ticket_attachments',
			'lgp_audit_log',
		);

		foreach ( $tables as $table ) {
			$full_table = $wpdb->prefix . $table;
			$result     = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT (data_length + index_length) as size 
					FROM information_schema.TABLES 
					WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$full_table
				)
			);

			$sizes[ $table ] = isset( $result->size ) ? (int) $result->size : 0;
		}

		return $sizes;
	}

	/**
	 * Count transients in database
	 *
	 * @return int Transient count
	 */
	private static function count_transients() {
		global $wpdb;

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_%'"
		);

		return (int) $count;
	}

	/**
	 * Calculate overall status
	 *
	 * @param array $results All validation results
	 * @return string Overall status (pass/warning/fail)
	 */
	private static function calculate_overall_status( $results ) {
		$has_fail    = false;
		$has_warning = false;

		foreach ( $results as $key => $result ) {
			if ( $key === 'recommendations' ) {
				continue;
			}

			if ( isset( $result['status'] ) ) {
				if ( $result['status'] === 'fail' ) {
					$has_fail = true;
				} elseif ( $result['status'] === 'warning' ) {
					$has_warning = true;
				}
			}
		}

		if ( $has_fail ) {
			return 'fail';
		} elseif ( $has_warning ) {
			return 'warning';
		}

		return 'pass';
	}

	/**
	 * Get optimization recommendations
	 *
	 * @return array Recommendations
	 */
	private static function get_recommendations() {
		$recommendations = array();

		// Check memory
		$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		if ( $memory_limit < 134217728 ) {
			$recommendations[] = array(
				'priority' => 'high',
				'title'    => 'Increase PHP Memory Limit',
				'message'  => 'Add to wp-config.php: define(\'WP_MEMORY_LIMIT\', \'128M\');',
			);
		}

		// Check object cache
		if ( ! wp_using_ext_object_cache() ) {
			$recommendations[] = array(
				'priority' => 'medium',
				'title'    => 'Enable Object Caching',
				'message'  => 'Install Redis or Memcached for better performance',
			);
		}

		// Check cron
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			$recommendations[] = array(
				'priority' => 'medium',
				'title'    => 'Use System Cron',
				'message'  => 'Disable WP-Cron and configure system cron for better reliability',
			);
		}

		return $recommendations;
	}

	/**
	 * Generate validation report
	 *
	 * @return string HTML report
	 */
	public static function generate_report() {
		$validation = self::validate_all();

		$html  = '<div class="lgp-validation-report">';
		$html .= '<h2>Shared Hosting Validation Report</h2>';
		$html .= '<p>Generated: ' . esc_html( $validation['timestamp'] ) . '</p>';
		$html .= '<p><strong>Overall Status:</strong> <span class="status-' . esc_attr( $validation['overall_status'] ) . '">' . esc_html( strtoupper( $validation['overall_status'] ) ) . '</span></p>';

		foreach ( $validation as $section => $data ) {
			if ( in_array( $section, array( 'overall_status', 'timestamp', 'recommendations' ), true ) ) {
				continue;
			}

			$html .= '<h3>' . esc_html( ucwords( str_replace( '_', ' ', $section ) ) ) . '</h3>';
			$html .= '<p><strong>Status:</strong> ' . esc_html( $data['status'] ) . '</p>';

			if ( ! empty( $data['issues'] ) ) {
				$html .= '<ul>';
				foreach ( $data['issues'] as $issue ) {
					$html .= '<li class="severity-' . esc_attr( $issue['severity'] ) . '">' . esc_html( $issue['message'] ) . '</li>';
				}
				$html .= '</ul>';
			}
		}

		if ( ! empty( $validation['recommendations'] ) ) {
			$html .= '<h3>Recommendations</h3>';
			$html .= '<ul>';
			foreach ( $validation['recommendations'] as $rec ) {
				$html .= '<li class="priority-' . esc_attr( $rec['priority'] ) . '"><strong>' . esc_html( $rec['title'] ) . ':</strong> ' . esc_html( $rec['message'] ) . '</li>';
			}
			$html .= '</ul>';
		}

		$html .= '</div>';

		return $html;
	}
}
