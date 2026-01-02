<?php

/**
 * Query performance monitor.
 *
 * Tracks slow queries, cache efficiency, and optimization suggestions.
 *
 * @package LounGenie Portal
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LGP_Query_Monitor class.
 *
 * Monitors query performance and provides optimization insights for debugging.
 */
class LGP_Query_Monitor {

	const SLOW_QUERY_THRESHOLD = 0.1; // 100ms.
	const CACHE_TABLE_NAME     = 'lgp_query_performance';

	/**
	 * Query log array.
	 *
	 * @var array
	 */
	private static $query_log = array();

	/**
	 * Cache hits count.
	 *
	 * @var int
	 */
	private static $cache_hits = 0;

	/**
	 * Cache misses count.
	 *
	 * @var int
	 */
	private static $cache_misses = 0;

	/**
	 * Initialize query monitor.
	 *
	 * @return void
	 */
	public static function init() {
		// Only enable in development/staging or if explicitly enabled.
		if ( ! self::is_enabled() ) {
			return;
		}

		// Create table on activation.
		add_action( 'lgp_plugin_activated', array( __CLASS__, 'create_table' ) );

		// Hook into WordPress to monitor queries.
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			add_action( 'admin_footer', array( __CLASS__, 'analyze_queries' ) );
		}

		// Register REST endpoint for performance data.
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );

		// Register admin page.
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );

		// Schedule cleanup.
		if ( ! wp_next_scheduled( 'lgp_cleanup_query_log' ) ) {
			wp_schedule_event( time(), 'daily', 'lgp_cleanup_query_log' );
		}
		add_action( 'lgp_cleanup_query_log', array( __CLASS__, 'cleanup_old_logs' ) );
	}

	/**
	 * Check if query monitoring is enabled.
	 *
	 * @return bool
	 */
	private static function is_enabled() {
		$env = class_exists( 'LGP_Environment' ) ? LGP_Environment::get_environment() : 'production';

		// Enable in development and staging.
		if ( in_array( $env, array( 'development', 'staging' ), true ) ) {
			return true;
		}

		// Check for explicit enable flag.
		return defined( 'LGP_ENABLE_QUERY_MONITOR' ) && LGP_ENABLE_QUERY_MONITOR;
	}

	/**
	 * Create performance logging table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			query_hash VARCHAR(32) NOT NULL,
			query_text LONGTEXT NOT NULL,
			execution_time FLOAT NOT NULL,
			is_slow BOOLEAN DEFAULT FALSE,
			affected_rows INT DEFAULT 0,
			backtrace LONGTEXT,
			caller_file VARCHAR(255),
			caller_line INT,
			caller_function VARCHAR(255),
			environment VARCHAR(20),
			KEY idx_timestamp (timestamp),
			KEY idx_slow (is_slow),
			KEY idx_hash (query_hash),
			KEY idx_env (environment)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Create summary table
		$summary_table = $wpdb->prefix . 'lgp_query_summary';
		$summary_sql   = "CREATE TABLE IF NOT EXISTS {$summary_table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			query_hash VARCHAR(32) NOT NULL UNIQUE,
			query_pattern VARCHAR(500),
			execution_count INT DEFAULT 1,
			avg_execution_time FLOAT,
			max_execution_time FLOAT,
			min_execution_time FLOAT,
			slow_count INT DEFAULT 0,
			optimization_suggestion LONGTEXT,
			last_analyzed DATETIME,
			KEY idx_hash (query_hash)
		) {$charset};";

		dbDelta( $summary_sql );
	}

	/**
	 * Register REST endpoints
	 */
	public static function register_endpoints() {
		register_rest_route(
			'lgp/v1',
			'/performance',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_performance_data' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/performance/slow-queries',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_slow_queries' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/performance/suggestions',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_suggestions' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			)
		);
	}

	/**
	 * Analyze queries from SAVEQUERIES.
	 *
	 * @return void
	 */
	public static function analyze_queries() {
		global $wpdb;

		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES || empty( $wpdb->queries ) ) {
			return;
		}

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;
		$env        = class_exists( 'LGP_Environment' ) ? LGP_Environment::get_environment() : 'unknown';

		foreach ( $wpdb->queries as $query_data ) {
			$query   = $query_data[0];
			$time    = $query_data[1];
			$is_slow = $time > self::SLOW_QUERY_THRESHOLD;

			// Hash the query (remove values for pattern matching).
			$query_hash = md5( self::normalize_query( $query ) );

			// Get caller information.
			$caller = self::get_caller_info( $query_data );

			// Store query log.
			$wpdb->insert(
				$table_name,
				array(
					'query_hash'      => $query_hash,
					'query_text'      => substr( $query, 0, 1000 ),
					'execution_time'  => $time,
					'is_slow'         => $is_slow ? 1 : 0,
					'caller_file'     => $caller['file'],
					'caller_line'     => $caller['line'],
					'caller_function' => $caller['function'],
					'environment'     => $env,
					'timestamp'       => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%f', '%d', '%s', '%d', '%s', '%s', '%s' )
			);

		// Update summary.
			self::update_query_summary( $query_hash, $query, $time, $is_slow );
		}
	}

	/**
	 * Normalize query for pattern matching (remove VALUES).
	 *
	 * @param string $query The SQL query to normalize.
	 * @return string Normalized query pattern.
		// Remove actual values, keep structure
		$normalized = preg_replace( "/('.*?'|\".*?\"|[0-9]+)/", '?', $query );
		// Remove multiple spaces
		$normalized = preg_replace( '/\s+/', ' ', $normalized );
		return trim( $normalized );
	}

	/**
	 * Get caller information from query stack.
	 *
	 * @param array $query_data Query data from SAVEQUERIES.
	 * @return array Caller information with file, line, function.
		return array(
			'file'     => isset( $query_data[2] ) ? $query_data[2] : '',
			'line'     => isset( $query_data[3] ) ? $query_data[3] : 0,
			'function' => 'query_execution',
		);
	}

	/**
	 * Update query summary table.
	 *
	 * @param string $query_hash Query hash.
	 * @param string $query SQL query text.
	 * @param float  $time Execution time in seconds.
	 * @param bool   $is_slow Whether query exceeds slow threshold.
	 * @return void
		global $wpdb;

		$summary_table = $wpdb->prefix . 'lgp_query_summary';

		// Check if hash exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$summary_table} WHERE query_hash = %s",
				$query_hash
			)
		);

		if ( $exists ) {
			// Update existing.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$summary_table}
					 SET execution_count = execution_count + 1,
						 avg_execution_time = (avg_execution_time * execution_count + %f) / (execution_count + 1),
						 max_execution_time = GREATEST(max_execution_time, %f),
						 min_execution_time = LEAST(min_execution_time, %f),
						 slow_count = slow_count + %d,
						 last_analyzed = NOW()
					 WHERE query_hash = %s",
					$time,
					$time,
					$time,
					$is_slow ? 1 : 0,
					$query_hash
				)
			);
		} else {
			// Insert new.
			$wpdb->insert(
				$summary_table,
				array(
					'query_hash'         => $query_hash,
					'query_pattern'      => substr( self::normalize_query( $query ), 0, 500 ),
					'execution_count'    => 1,
					'avg_execution_time' => $time,
					'max_execution_time' => $time,
					'min_execution_time' => $time,
					'slow_count'         => $is_slow ? 1 : 0,
					'last_analyzed'      => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%d', '%f', '%f', '%f', '%d', '%s' )
			);
		}
	}

	/**
	 * Get overall performance data.
	 *
	 * @return array Performance metrics and statistics.
	 */
	public static function get_performance_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;

		// Get stats from last 24 hours
		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_queries,
				SUM(is_slow) as slow_queries,
				AVG(execution_time) as avg_time,
				MAX(execution_time) as max_time,
				MIN(execution_time) as min_time
			FROM {$table_name}
			WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
		);

		return rest_ensure_response(
			array(
				'total_queries'      => (int) $stats->total_queries,
				'slow_queries'       => (int) $stats->slow_queries,
				'slow_percentage'    => $stats->total_queries > 0 ? round( ( $stats->slow_queries / $stats->total_queries ) * 100, 2 ) : 0,
				'avg_execution_time' => round( (float) $stats->avg_time, 4 ),
				'max_execution_time' => round( (float) $stats->max_time, 4 ),
				'min_execution_time' => round( (float) $stats->min_time, 4 ),
				'slow_threshold'     => self::SLOW_QUERY_THRESHOLD,
			)
		);
	}

	/**
	 * Get slow queries
	 */
	public static function get_slow_queries( $request ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;
		$page       = (int) $request->get_param( 'page' ) ?: 1;
		$limit      = (int) $request->get_param( 'limit' ) ?: 25;
		$offset     = ( $page - 1 ) * $limit;

		$queries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query_hash, query_text, execution_time, caller_file, caller_line, timestamp
				 FROM {$table_name}
				 WHERE is_slow = 1
				 ORDER BY execution_time DESC, timestamp DESC
				 LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE is_slow = 1" );

		return rest_ensure_response(
			array(
				'queries' => $queries,
				'total'   => (int) $total,
				'page'    => $page,
				'pages'   => (int) ceil( $total / $limit ),
			)
		);
	}

	/**
	 * Get optimization suggestions
	 */
	public static function get_suggestions() {
		global $wpdb;

		$summary_table = $wpdb->prefix . 'lgp_query_summary';

		// Get top slow queries
		$slow_queries = $wpdb->get_results(
			"SELECT query_hash, query_pattern, execution_count, avg_execution_time, slow_count
			 FROM {$summary_table}
			 WHERE slow_count > 0
			 ORDER BY avg_execution_time DESC
			 LIMIT 10"
		);

		$suggestions = array();

		foreach ( $slow_queries as $query ) {
			$suggestion = self::analyze_query_pattern( $query );
			if ( $suggestion ) {
				$suggestions[] = $suggestion;
			}
		}

		return rest_ensure_response( $suggestions );
	}

	/**
	 * Analyze query pattern and provide suggestions
	 */
	private static function analyze_query_pattern( $query ) {
		$pattern = strtoupper( $query->query_pattern ?? '' );

		$suggestion = array(
			'query_hash'      => $query->query_hash,
			'execution_count' => (int) $query->execution_count,
			'avg_time'        => round( (float) $query->avg_execution_time, 4 ),
			'suggestions'     => array(),
		);

		// Missing JOIN condition
		if ( preg_match( '/JOIN.*?WHERE/i', $pattern ) === 0 && preg_match( '/JOIN/i', $pattern ) ) {
			$suggestion['suggestions'][] = 'Consider adding WHERE clause for JOIN optimization';
		}

		// SELECT *
		if ( preg_match( '/SELECT \?.*?FROM/i', $pattern ) || preg_match( '/SELECT \*/i', $pattern ) ) {
			$suggestion['suggestions'][] = 'Avoid SELECT *, specify needed columns only';
		}

		// No LIMIT on large queries
		if ( preg_match( '/SELECT/i', $pattern ) && ! preg_match( '/LIMIT/i', $pattern ) && $query->execution_count > 100 ) {
			$suggestion['suggestions'][] = 'Consider adding LIMIT clause for pagination';
		}

		// Multiple OR conditions
		if ( preg_match_all( '/OR/i', $pattern ) > 3 ) {
			$suggestion['suggestions'][] = 'Many OR conditions detected. Consider IN() for better performance';
		}

		// Nested subqueries
		if ( preg_match_all( '/\(/i', $pattern ) > 3 ) {
			$suggestion['suggestions'][] = 'Multiple nested levels detected. Consider JOIN for better performance';
		}

		// NOT IN on large result set
		if ( preg_match( '/NOT IN/i', $pattern ) && $query->execution_count > 50 ) {
			$suggestion['suggestions'][] = 'NOT IN may be slow. Consider LEFT JOIN with NULL check';
		}

		return count( $suggestion['suggestions'] ) > 0 ? $suggestion : null;
	}

	/**
	 * Register admin page
	 */
	public static function register_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'tools.php',
			'Query Performance',
			'Query Performance',
			'manage_options',
			'lgp-query-monitor',
			array( __CLASS__, 'render_dashboard' )
		);
	}

	/**
	 * Render performance dashboard
	 */
	public static function render_dashboard() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;

		// Get stats
		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_queries,
				SUM(is_slow) as slow_queries,
				AVG(execution_time) as avg_time,
				MAX(execution_time) as max_time
			FROM {$table_name}
			WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
		);

		// Get slow queries
		$slow_queries = $wpdb->get_results(
			"SELECT query_text, execution_time, caller_file, caller_line, timestamp
			 FROM {$table_name}
			 WHERE is_slow = 1
			 ORDER BY execution_time DESC
			 LIMIT 20"
		);

		// Get top patterns
		$summary_table = $wpdb->prefix . 'lgp_query_summary';
		$patterns      = $wpdb->get_results(
			"SELECT query_hash, query_pattern, execution_count, avg_execution_time, slow_count
			 FROM {$summary_table}
			 ORDER BY execution_count DESC
			 LIMIT 10"
		);

		?>
		<div class="wrap">
			<h1>Query Performance Monitor</h1>

			<!-- Stats Cards -->
			<div class="lgp-stats-grid">
				<div class="lgp-stat-card">
					<h3>Total Queries (24h)</h3>
					<div class="lgp-stat-value"><?php echo esc_html( $stats->total_queries ); ?></div>
				</div>

				<div class="lgp-stat-card">
					<h3>Slow Queries</h3>
					<div class="lgp-stat-value"><?php echo esc_html( $stats->slow_queries ); ?></div>
					<p class="lgp-stat-meta">
						<?php
						$percentage = $stats->total_queries > 0 ? round( ( $stats->slow_queries / $stats->total_queries ) * 100, 2 ) : 0;
						echo esc_html( $percentage ) . '%';
						?>
					</p>
				</div>

				<div class="lgp-stat-card">
					<h3>Average Execution</h3>
					<div class="lgp-stat-value"><?php echo esc_html( round( $stats->avg_time, 4 ) ); ?>ms</div>
				</div>

				<div class="lgp-stat-card">
					<h3>Max Execution</h3>
					<div class="lgp-stat-value"><?php echo esc_html( round( $stats->max_time, 4 ) ); ?>ms</div>
				</div>
			</div>

			<!-- Slow Queries Table -->
			<div class="lgp-panel" style="margin-top: 2rem;">
				<h2>Slowest Queries (Top 20)</h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Query</th>
							<th>Time (ms)</th>
							<th>File:Line</th>
							<th>Timestamp</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $slow_queries ) ) : ?>
							<?php foreach ( $slow_queries as $query ) : ?>
								<tr>
									<td>
										<code style="font-size: 11px; word-break: break-all;">
											<?php echo esc_html( substr( $query->query_text, 0, 80 ) ); ?>...
										</code>
									</td>
									<td><strong><?php echo esc_html( round( $query->execution_time, 4 ) ); ?></strong></td>
									<td>
										<small>
											<?php echo esc_html( basename( $query->caller_file ) ); ?>:<?php echo esc_html( $query->caller_line ); ?>
										</small>
									</td>
									<td><?php echo esc_html( $query->timestamp ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="4" style="text-align: center;">No slow queries found in the last 24 hours. Great job!</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Top Patterns -->
			<div class="lgp-panel" style="margin-top: 2rem;">
				<h2>Top Query Patterns</h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Pattern</th>
							<th>Count</th>
							<th>Avg Time (ms)</th>
							<th>Slow Count</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $patterns ) ) : ?>
							<?php foreach ( $patterns as $pattern ) : ?>
								<tr>
									<td>
										<code style="font-size: 11px; word-break: break-all;">
											<?php echo esc_html( substr( $pattern->query_pattern, 0, 100 ) ); ?>...
										</code>
									</td>
									<td><?php echo esc_html( $pattern->execution_count ); ?></td>
									<td><?php echo esc_html( round( $pattern->avg_execution_time, 4 ) ); ?></td>
									<td>
										<?php if ( $pattern->slow_count > 0 ) : ?>
											<span style="color: red; font-weight: bold;">
												<?php echo esc_html( $pattern->slow_count ); ?>
											</span>
										<?php else : ?>
											0
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="4" style="text-align: center;">No queries recorded yet.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Info Box -->
			<div class="lgp-panel" style="margin-top: 2rem; background: #f0f7ff; border-left: 4px solid #0073aa;">
				<h3>Performance Monitor Info</h3>
				<p><strong>Threshold:</strong> Queries taking longer than <?php echo esc_html( self::SLOW_QUERY_THRESHOLD * 1000 ); ?>ms are marked as slow.</p>
				<p><strong>Data Retention:</strong> Query logs are automatically cleaned up after 30 days.</p>
				<p><strong>Enabled in:</strong> Development and Staging environments only (unless explicitly enabled).</p>
				<p><strong>Requires:</strong> <code>define( 'SAVEQUERIES', true );</code> in wp-config.php (development only)</p>
			</div>
		</div>

		<style>
			.lgp-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 1rem;
				margin-bottom: 2rem;
			}

			.lgp-stat-card {
				background: white;
				border: 1px solid #e0e0e0;
				border-radius: 4px;
				padding: 1.5rem;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}

			.lgp-stat-card h3 {
				margin: 0 0 0.5rem 0;
				font-size: 14px;
				font-weight: 600;
				color: #666;
			}

			.lgp-stat-value {
				font-size: 28px;
				font-weight: 700;
				color: #0073aa;
			}

			.lgp-stat-meta {
				margin: 0.5rem 0 0 0;
				font-size: 12px;
				color: #999;
			}

			.lgp-panel {
				background: white;
				border: 1px solid #e0e0e0;
				border-radius: 4px;
				padding: 1.5rem;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}

			.lgp-panel h2 {
				margin-top: 0;
				margin-bottom: 1rem;
			}
		</style>
		<?php
	}

	/**
	 * Clean up old logs
	 */
	public static function cleanup_old_logs() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::CACHE_TABLE_NAME;

		// Delete records older than 30 days
		$wpdb->query(
			"DELETE FROM {$table_name}
			 WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);
	}

	/**
	 * Check permission
	 */
	public static function check_permission() {
		return current_user_can( 'manage_options' );
	}
}
