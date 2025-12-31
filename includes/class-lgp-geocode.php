<?php

/**
 * Lightweight geocoding helper using free Nominatim (OpenStreetMap)
 * Caches coordinates on the company record to avoid repeat lookups.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Geocode {

	private const ENDPOINT     = 'https://nominatim.openstreetmap.org/search';
	private const CACHE_PREFIX = 'lgp_geocode_';

	/**
	 * Get markers for all companies (support-only callers should gate access).
	 *
	 * @return array[]
	 */
	public static function get_company_markers() {
		global $wpdb;
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$rows            = $wpdb->get_results( "SELECT id, name, address, state, venue_type FROM $companies_table" );
		$markers         = array();
		if ( empty( $rows ) ) {
			return $markers;
		}

		foreach ( $rows as $row ) {
			$loc = self::get_cached_location( $row->id );

			if ( ! $loc ) {
				$loc = self::geocode_company_row( $row );
			}

			if ( $loc ) {
				$markers[] = array(
					'id'   => (int) $row->id,
					'name' => $row->name,
					'type' => $row->venue_type ?? '',
					'lat'  => $loc['lat'],
					'lng'  => $loc['lng'],
				);
			}
		}
		return $markers;
	}

	/**
	 * Support-only wrapper to fetch markers.
	 *
	 * @return array
	 */
	public static function get_company_markers_for_map() {
		// Allow when user can manage options (support) or LGP_Auth says support
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return self::get_company_markers();
		}

		if ( class_exists( 'LGP_Auth' ) && LGP_Auth::is_support() ) {
			return self::get_company_markers();
		}

		return array();
	}

	/**
	 * Geocode a single company row; caches result back to DB when found.
	 *
	 * @param object $row
	 * @return array|null
	 */
	private static function geocode_company_row( $row ) {
		$parts = array_filter(
			array(
				$row->address ?? '',
				$row->state ?? '',
			)
		);
		$loc   = self::lookup( implode( ', ', $parts ) );

		if ( $loc ) {
			self::set_cached_location( $row->id, $loc );
		}
		return $loc;
	}

	/**
	 * Perform a Nominatim lookup (single result).
	 *
	 * @param string $query
	 * @return array|null
	 */
	private static function lookup( $query ) {
		if ( empty( $query ) ) {
			return null;
		}
		$url = add_query_arg(
			array(
				'q'              => $query,
				'format'         => 'json',
				'limit'          => 1,
				'addressdetails' => 0,
			),
			self::ENDPOINT
		);

		$resp = wp_remote_get(
			$url,
			array(
				'headers' => array( 'User-Agent' => 'LounGeniePortal/1.0 (support@poolsafeinc.com)' ),
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $resp ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( empty( $body[0]['lat'] ) || empty( $body[0]['lon'] ) ) {
			return null;
		}
		return array(
			'lat' => (float) $body[0]['lat'],
			'lng' => (float) $body[0]['lon'],
		);
	}

	/**
	 * Retrieve cached coordinates from wp_options.
	 */
	private static function get_cached_location( $company_id ) {
		$data = get_option( self::CACHE_PREFIX . (int) $company_id );
		if ( empty( $data['lat'] ) || empty( $data['lng'] ) ) {
			return null;
		}
		return array(
			'lat' => (float) $data['lat'],
			'lng' => (float) $data['lng'],
		);
	}

	/**
	 * Cache coordinates in wp_options.
	 */
	private static function set_cached_location( $company_id, $loc ) {
		update_option(
			self::CACHE_PREFIX . (int) $company_id,
			array(
				'lat'       => (float) $loc['lat'],
				'lng'       => (float) $loc['lng'],
				'cached_at' => current_time( 'mysql', true ),
			),
			false
		);
	}
}
