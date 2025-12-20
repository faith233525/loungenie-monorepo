<?php

/**
 * Map Units API
 * Returns units with geolocation for Leaflet map view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Map_API {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$api = new self();
		register_rest_route(
			'lgp/v1',
			'/map/units',
			array(
				'methods'             => 'GET',
				'callback'            => array( $api, 'get_units' ),
				'permission_callback' => array( $api, 'check_portal_access' ),
			)
		);
	}

	public function check_portal_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Allow both Support and Partner roles; downstream filtering scopes partner data
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	public function get_units( $request ) {
		global $wpdb;

		// Enhanced authentication check
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				'Authentication required',
				array( 'status' => 401 )
			);
		}

		$is_support = LGP_Auth::is_support();
		$is_partner = LGP_Auth::is_partner();

		// Partners must have a company_id
		$company_id = LGP_Auth::get_user_company_id();
		if ( $is_partner && empty( $company_id ) ) {
			return new WP_Error(
				'invalid_company',
				'No company associated with user account',
				array( 'status' => 400 )
			);
		}

		if ( ! $is_support && ! $is_partner ) {
			return new WP_Error(
				'forbidden',
				'Insufficient permissions for map view',
				array( 'status' => 403 )
			);
		}

		$units_table     = $wpdb->prefix . 'lgp_units';
		$companies_table = $wpdb->prefix . 'lgp_companies';

		// Query with role-based filtering using prepared statements when scoping by company
		if ( $is_support ) {
			$results = $wpdb->get_results(
				"SELECT u.company_id, u.unit_number, u.status, u.season, u.latitude, u.longitude,
						c.primary_contract_status
				 FROM {$units_table} u
				 LEFT JOIN {$companies_table} c ON c.id = u.company_id
				 WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL"
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT u.company_id, u.unit_number, u.status, u.season, u.latitude, u.longitude,
							c.primary_contract_status
					 FROM {$units_table} u
					 LEFT JOIN {$companies_table} c ON c.id = u.company_id
					 WHERE u.company_id = %d AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL",
					$company_id
				)
			);
		}

		// Log access for audit trail
		LGP_Logger::log_event(
			get_current_user_id(),
			'map_access',
			$is_support ? null : $company_id,
			array(
				'role'           => $is_support ? 'support' : 'partner',
				'units_returned' => count( $results ),
			)
		);

		return rest_ensure_response(
			array(
				'units' => $results,
				'total' => count( $results ),
				'role'  => $is_support ? 'support' : 'partner',
			)
		);
	}
}

LGP_Map_API::init();
