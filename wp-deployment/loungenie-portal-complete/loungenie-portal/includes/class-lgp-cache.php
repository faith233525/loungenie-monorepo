<?php
/**
 * LounGenie Portal Cache Management
 *
 * Multi-layer caching system supporting:
 * - WordPress Transients (default)
 * - Redis (if available)
 * - Memcached (if available)
 * - APCu (if available)
 *
 * @package LounGenie Portal
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Cache {

	/**
	 * Cache group for namespacing
	 */
	const CACHE_GROUP = 'loungenie_portal';

	/**
	 * Default TTL in seconds (5 minutes)
	 */
	const DEFAULT_TTL = 300;

	/**
	 * Get cached value or set it if it doesn't exist
	 *
	 * @param string   $key      Cache key
	 * @param callable $callback Function to generate value if cache miss
	 * @param int      $ttl      Time to live in seconds
	 * @return mixed Cached or generated value
	 */
	public static function get_or_set( $key, $callback, $ttl = self::DEFAULT_TTL ) {
		$value = self::get( $key );

		if ( false === $value ) {
			$value = call_user_func( $callback );
			self::set( $key, $value, $ttl );
		}

		return $value;
	}

	/**
	 * Get value from cache
	 *
	 * @param string $key Cache key
	 * @return mixed|false Cached value or false if not found
	 */
	public static function get( $key ) {
		$cache_key = self::build_key( $key );

		// Try object cache first (Redis, Memcached, etc.)
		if ( function_exists( 'wp_cache_get' ) ) {
			$value = wp_cache_get( $cache_key, self::CACHE_GROUP );
			if ( false !== $value ) {
				return $value;
			}
		}

		// Fall back to transients
		return get_transient( $cache_key );
	}

	/**
	 * Set value in cache
	 *
	 * @param string $key   Cache key
	 * @param mixed  $value Value to cache
	 * @param int    $ttl   Time to live in seconds
	 * @return bool True on success, false on failure
	 */
	public static function set( $key, $value, $ttl = self::DEFAULT_TTL ) {
		$cache_key = self::build_key( $key );

		// Set in object cache if available
		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $cache_key, $value, self::CACHE_GROUP, $ttl );
		}

		// Always set in transients as fallback
		return set_transient( $cache_key, $value, $ttl );
	}

	/**
	 * Delete value from cache
	 *
	 * @param string $key Cache key
	 * @return bool True on success, false on failure
	 */
	public static function delete( $key ) {
		$cache_key = self::build_key( $key );

		// Delete from object cache
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
		}

		// Delete from transients
		return delete_transient( $cache_key );
	}

	/**
	 * Delete all cache keys matching a pattern
	 *
	 * @param string $pattern Pattern to match (supports * wildcard)
	 * @return int Number of keys deleted
	 */
	public static function delete_pattern( $pattern ) {
		global $wpdb;

		$count       = 0;
		$pattern_key = self::build_key( str_replace( '*', '%', $pattern ) );

		// Delete from transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . $pattern_key
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_' . $pattern_key
			)
		);

		// Note: Object cache pattern deletion depends on backend
		// Redis and Memcached handle this differently
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
		}

		return $count;
	}

	/**
	 * Flush entire cache group
	 *
	 * @return bool True on success
	 */
	public static function flush() {
		global $wpdb;

		// Flush object cache group
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
		}

		// Delete all transients with our prefix
		$prefix = self::build_key( '' );
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $prefix . '%',
				'_transient_timeout_' . $prefix . '%'
			)
		);

		return true;
	}

	/**
	 * Build cache key with prefix
	 *
	 * @param string $key Original key
	 * @return string Prefixed key
	 */
	private static function build_key( $key ) {
		return 'lgp_' . $key;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats
	 */
	public static function get_stats() {
		return array(
			'object_cache_enabled' => function_exists( 'wp_cache_get' ),
			'redis_enabled'        => class_exists( 'Redis' ),
			'memcached_enabled'    => class_exists( 'Memcached' ),
			'apcu_enabled'         => function_exists( 'apcu_fetch' ),
			'cache_group'          => self::CACHE_GROUP,
		);
	}

	/**
	 * Warm up cache with commonly accessed data
	 */
	public static function warm_up() {
		// Cache dashboard stats for all users
		if ( current_user_can( 'lgp_access_portal' ) ) {
			$user_id = get_current_user_id();

			// Warm dashboard stats
			self::get_or_set(
				'dashboard_stats_' . $user_id,
				function() {
					global $wpdb;
					return array(
						'total_companies' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_companies" ),
						'total_units'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_units" ),
						'open_tickets'    => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lgp_tickets WHERE status = 'open'" ),
					);
				},
				300
			);

			// Warm top metrics
			self::get_or_set(
				'top_colors',
				function() {
					global $wpdb;
					return $wpdb->get_results(
						"SELECT color, COUNT(*) as count FROM {$wpdb->prefix}lgp_units WHERE color != '' GROUP BY color ORDER BY count DESC LIMIT 5",
						ARRAY_A
					);
				},
				600
			);

			self::get_or_set(
				'top_venues',
				function() {
					global $wpdb;
					return $wpdb->get_results(
						"SELECT venue_type, COUNT(*) as count FROM {$wpdb->prefix}lgp_units WHERE venue_type != '' GROUP BY venue_type ORDER BY count DESC LIMIT 5",
						ARRAY_A
					);
				},
				600
			);

			self::get_or_set(
				'top_lock_brands',
				function() {
					global $wpdb;
					return $wpdb->get_results(
						"SELECT lock_brand, COUNT(*) as count FROM {$wpdb->prefix}lgp_units WHERE lock_brand != '' GROUP BY lock_brand ORDER BY count DESC LIMIT 5",
						ARRAY_A
					);
				},
				600
			);
		}
	}

	/**
	 * Invalidate cache on data changes
	 *
	 * @param string $entity Entity type (companies, units, tickets)
	 */
	public static function invalidate_entity( $entity ) {
		switch ( $entity ) {
			case 'companies':
				self::delete_pattern( 'dashboard_stats_*' );
				self::delete_pattern( 'company_*' );
				break;

			case 'units':
				self::delete_pattern( 'dashboard_stats_*' );
				self::delete_pattern( 'top_*' );
				self::delete_pattern( 'unit_*' );
				break;

			case 'tickets':
				self::delete_pattern( 'dashboard_stats_*' );
				self::delete_pattern( 'ticket_*' );
				break;
		}
	}
}
