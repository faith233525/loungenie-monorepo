<?php

/**
 * Company Colors Aggregation Utility
 * Handles calculation and caching of company-level color distribution
 *
 * Core Principle: Units are aggregated at company level by color.
 * NO individual unit IDs are exposed or tracked.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Company_Colors {


	const CACHE_TTL = 3600; // 1 hour cache

	/**
	 * Initialize hooks
	 */
	public static function init() {
		 // Invalidate cache when units change
		add_action( 'lgp_unit_created', array( __CLASS__, 'invalidate_cache' ), 10, 2 );
		add_action( 'lgp_unit_updated', array( __CLASS__, 'invalidate_cache' ), 10, 2 );
		add_action( 'lgp_unit_deleted', array( __CLASS__, 'invalidate_cache' ), 10, 2 );
	}

	/**
	 * Get color distribution for a company
	 *
	 * Returns aggregated color counts, NOT individual unit IDs.
	 *
	 * @param int $company_id Company ID
	 * @return array Color counts ['yellow' => 10, 'orange' => 5, ...]
	 */
	public static function get_company_colors( $company_id ) {
		if ( empty( $company_id ) ) {
			return array();
		}

		// Try cache first
		$cache_key = "company_colors_{$company_id}";
		$colors    = wp_cache_get( $cache_key, 'loungenie_portal' );

		if ( false !== $colors ) {
			return $colors;
		}

		// Calculate from database
		$colors = self::calculate_colors( $company_id );

		// Cache result
		wp_cache_set( $cache_key, $colors, 'loungenie_portal', self::CACHE_TTL );

		return $colors;
	}

	/**
	 * Get total unit count for company
	 *
	 * Returns aggregate count, NOT individual unit details.
	 *
	 * @param int $company_id Company ID
	 * @return int Total units
	 */
	public static function get_company_unit_count( $company_id ) {
		global $wpdb;

		if ( empty( $company_id ) ) {
			return 0;
		}

		$count = wp_cache_get( "company_unit_count_{$company_id}", 'loungenie_portal' );

		if ( false === $count ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lgp_units WHERE company_id = %d",
					$company_id
				)
			);

			wp_cache_set( "company_unit_count_{$company_id}", $count, 'loungenie_portal', self::CACHE_TTL );
		}

		return $count;
	}

	/**
	 * Calculate color distribution from units table
	 *
	 * Aggregates by color_tag, returns counts only.
	 *
	 * @param int $company_id Company ID
	 * @return array Color counts
	 */
	private static function calculate_colors( $company_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COALESCE(color_tag, 'unknown') as color, COUNT(*) as count
				 FROM {$wpdb->prefix}lgp_units
				 WHERE company_id = %d
				 GROUP BY color_tag
				 ORDER BY count DESC",
				$company_id
			)
		);

		$color_counts = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$color                  = isset( $row->color ) ? $row->color : 'unknown';
				$color_counts[ $color ] = (int) $row->count;
			}
		}

		return $color_counts;
	}

	/**
	 * Invalidate cache when units change
	 *
	 * @param int $unit_id Unit ID (not used, for hook compatibility)
	 * @param int $company_id Company ID
	 */
	public static function invalidate_cache( $unit_id, $company_id ) {
		wp_cache_delete( "company_colors_{$company_id}", 'loungenie_portal' );
		wp_cache_delete( "company_unit_count_{$company_id}", 'loungenie_portal' );

		// Optionally: Update company.top_colors field in database
		self::refresh_company_colors( $company_id );
	}

	/**
	 * Refresh company colors in database (top_colors JSON field)
	 *
	 * Updates the denormalized top_colors column for faster dashboard queries.
	 *
	 * @param int $company_id Company ID
	 */
	public static function refresh_company_colors( $company_id ) {
		global $wpdb;

		if ( empty( $company_id ) ) {
			return;
		}

		$colors = self::calculate_colors( $company_id );

		$wpdb->update(
			$wpdb->prefix . 'lgp_companies',
			array( 'top_colors' => wp_json_encode( $colors ) ),
			array( 'id' => $company_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get color hex code for display
	 *
	 * Maps color names to hex codes for UI rendering.
	 *
	 * @param string $color Color name
	 * @return string Hex color code
	 */
	public static function get_color_hex( $color ) {
		$color_map = array(
			'yellow' => '#FFC107',
			'orange' => '#FF9800',
			'red'    => '#F44336',
			'green'  => '#4CAF50',
			'blue'   => '#2196F3',
			'purple' => '#9C27B0',
			'gray'   => '#9E9E9E',
			'grey'   => '#9E9E9E',
			'white'  => '#FFFFFF',
			'black'  => '#000000',
		);

		$color = strtolower( trim( $color ) );

		return isset( $color_map[ $color ] ) ? $color_map[ $color ] : '#757575'; // Default gray
	}

	/**
	 * Batch refresh colors for multiple companies
	 *
	 * Useful for maintenance tasks or after bulk unit imports.
	 *
	 * @param array $company_ids Array of company IDs
	 * @return int Number of companies updated
	 */
	public static function batch_refresh( $company_ids = array() ) {
		global $wpdb;

		if ( empty( $company_ids ) ) {
			// Get all company IDs if none specified
			$company_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}lgp_companies" );
		}

		$updated = 0;
		foreach ( $company_ids as $company_id ) {
			self::refresh_company_colors( $company_id );
			$updated++;
		}

		return $updated;
	}
}

// Helper function for templates
if ( ! function_exists( 'lgp_get_color_hex' ) ) {
	/**
	 * Get color hex code for display
	 *
	 * @param string $color Color name
	 * @return string Hex color code
	 */
	function lgp_get_color_hex( $color ) {
		return LGP_Company_Colors::get_color_hex( $color );
	}
}
