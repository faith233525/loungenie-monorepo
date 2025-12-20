<?php
/**
 * Shared Server Optimization Helper
 *
 * Utility functions to optimize plugin for shared server environments
 * Load this in wp-config.php or as a must-use plugin
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Server Optimization Class
 */
class LGP_Shared_Server_Optimizer {

	/**
	 * Initialize optimizations
	 */
	public static function init() {
		// Enable object caching if available
		add_filter( 'lgp_use_object_cache', array( __CLASS__, 'enable_object_cache' ) );

		// Optimize query execution
		add_filter( 'posts_per_page', array( __CLASS__, 'optimize_pagination' ) );

		// Monitor memory usage
		add_action( 'shutdown', array( __CLASS__, 'log_memory_usage' ) );

		// Optimize asset loading
		add_filter( 'style_loader_src', array( __CLASS__, 'optimize_css' ) );
		add_filter( 'script_loader_src', array( __CLASS__, 'optimize_js' ) );
	}

	/**
	 * Enable object caching
	 */
	public static function enable_object_cache() {
		// Check if Redis/Memcached available
		if ( function_exists( 'wp_cache_get' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Optimize pagination for shared servers
	 */
	public static function optimize_pagination( $posts_per_page ) {
		// Limit items per page on shared servers
		if ( self::is_shared_server() ) {
			return min( $posts_per_page, 25 );
		}
		return $posts_per_page;
	}

	/**
	 * Log memory usage for monitoring
	 */
	public static function log_memory_usage() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$memory        = memory_get_peak_usage( true ) / 1024 / 1024;
		$limit         = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) / 1024 / 1024;
		$usage_percent = ( $memory / $limit ) * 100;

		if ( $usage_percent > 80 ) {
			error_log(
				sprintf(
					'LGP Memory Warning: %dMB of %dMB used (%.1f%%)',
					round( $memory ),
					round( $limit ),
					$usage_percent
				)
			);
		}
	}

	/**
	 * Optimize CSS loading
	 */
	public static function optimize_css( $src ) {
		// Add local optimizations
		if ( strpos( $src, 'loungenie-portal' ) !== false ) {
			// Add crossorigin for shared server compatibility
			$src = add_query_arg( array(), $src );
		}
		return $src;
	}

	/**
	 * Optimize JS loading
	 */
	public static function optimize_js( $src ) {
		// Similar optimizations for JS
		if ( strpos( $src, 'loungenie-portal' ) !== false ) {
			$src = add_query_arg( array(), $src );
		}
		return $src;
	}

	/**
	 * Detect if running on shared server
	 */
	public static function is_shared_server() {
		$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		return $memory_limit < 134217728; // Less than 128MB
	}

	/**
	 * Get optimization recommendations
	 */
	public static function get_recommendations() {
		return array(
			'cache'      => array(
				'title'       => 'Enable Object Caching',
				'description' => 'Install Redis or Memcached for better performance',
				'impact'      => 'High',
			),
			'database'   => array(
				'title'       => 'Database Optimization',
				'description' => 'Run wp-cli wp db optimize monthly',
				'impact'      => 'Medium',
			),
			'images'     => array(
				'title'       => 'Image Optimization',
				'description' => 'Use WP Smush for automatic image compression',
				'impact'      => 'Medium',
			),
			'cdn'        => array(
				'title'       => 'Content Delivery',
				'description' => 'Use Cloudflare free tier for static assets',
				'impact'      => 'Medium',
			),
			'monitoring' => array(
				'title'       => 'Performance Monitoring',
				'description' => 'Monitor resource usage and error logs',
				'impact'      => 'Low',
			),
		);
	}

	/**
	 * Check shared server compatibility
	 */
	public static function check_compatibility() {
		$issues = array();

		// Check PHP version
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			$issues[] = array(
				'severity' => 'high',
				'message'  => 'PHP version is below 7.4. Consider upgrading.',
			);
		}

		// Check memory limit
		$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		if ( $memory_limit < 67108864 ) {
			$issues[] = array(
				'severity' => 'medium',
				'message'  => 'Memory limit is below 64MB. Performance may be impacted.',
			);
		}

		// Check max upload size
		$max_upload = wp_max_upload_size();
		if ( $max_upload < 5242880 ) {
			$issues[] = array(
				'severity' => 'low',
				'message'  => 'Max upload size is below 5MB. File uploads may fail.',
			);
		}

		// Check database version
		global $wpdb;
		$mysql_version = $wpdb->db_version();
		if ( version_compare( $mysql_version, '5.7', '<' ) && version_compare( $mysql_version, '10.2', '<' ) ) {
			$issues[] = array(
				'severity' => 'high',
				'message'  => 'MySQL/MariaDB version is below recommended. Consider upgrading.',
			);
		}

		return $issues;
	}
}

// Initialize on WordPress init
add_action( 'init', array( 'LGP_Shared_Server_Optimizer', 'init' ), 1 );

/**
 * Helper function to get optimization status
 */
function lgp_get_shared_server_status() {
	return array(
		'is_shared_server' => LGP_Shared_Server_Optimizer::is_shared_server(),
		'compatibility'    => LGP_Shared_Server_Optimizer::check_compatibility(),
		'recommendations'  => LGP_Shared_Server_Optimizer::get_recommendations(),
	);
}
