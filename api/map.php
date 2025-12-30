<?php

/**
 * Map Units API
 * Returns units with geolocation for Leaflet map view
 */

namespace LounGenie\Portal;

if (! defined('ABSPATH')) {
	exit;
}

class LGP_Map_API
{


	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	public static function register_routes()
	{
		$api = new self();
		register_rest_route(
			'lgp/v1',
			'/map/units',
			array(
				'methods'             => 'GET',
				'callback'            => array($api, 'get_units'),
				'permission_callback' => array($api, 'check_portal_access'),
			)
		);
	}

	public function check_portal_access()
	{
		if (! is_user_logged_in()) {
			return false;
		}

		// Allow both Support and Partner roles; downstream filtering scopes partner data
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	public function get_units($request)
	{
		global $wpdb;

		// Enhanced authentication check
		if (! is_user_logged_in()) {
			return new WP_Error(
				'unauthorized',
				'Authentication required',
				array('status' => 401)
			);
		}

		$is_support = LGP_Auth::is_support();
		$is_partner = LGP_Auth::is_partner();

		// Partners must have a company_id
		$company_id = LGP_Auth::get_user_company_id();
		if ($is_partner && empty($company_id)) {
			return new WP_Error(
				'invalid_company',
				'No company associated with user account',
				array('status' => 400)
			);
		}

		if (! $is_support && ! $is_partner) {
			return new WP_Error(
				'forbidden',
				'Insufficient permissions for map view',
				array('status' => 403)
			);
		}

		$units_table     = $wpdb->prefix . 'lgp_units';
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$tickets_table   = $wpdb->prefix . 'lgp_tickets';

		// Query with role-based filtering using prepared statements when scoping by company
		// Return fields expected by map-view.js: id, name, type, location, latitude, longitude
		// Using actual schema fields: unit_number, venue_type, address, lock_type
		if ($is_support) {
			$units = $wpdb->get_results(
				"SELECT 
					u.id, 
					CONCAT('Unit ', COALESCE(u.unit_number, u.id)) AS name,
					COALESCE(u.venue_type, u.lock_type, 'Unknown') AS type,
					CONCAT_WS(', ', c.name, u.address) AS location,
					u.latitude, 
					u.longitude,
					u.status,
					u.season
				 FROM {$units_table} u
				 LEFT JOIN {$companies_table} c ON c.id = u.company_id
				 WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL"
			);
			$units = ! empty($units) ? $units : array();

			// Get all tickets for these units via service_requests
			$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
			$tickets                = $wpdb->get_results(
				"SELECT 
					t.id,
					sr.unit_id,
					CONCAT('Ticket #', t.id) as title,
					sr.notes as description,
					t.status,
					sr.priority as urgency,
					t.created_at
				 FROM {$tickets_table} t
				 LEFT JOIN {$service_requests_table} sr ON t.service_request_id = sr.id
				 WHERE sr.unit_id IS NOT NULL 
				 AND t.status NOT IN ('closed', 'resolved')"
			);
			$tickets                = ! empty($tickets) ? $tickets : array();
		} else {
			$units = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						u.id,
						CONCAT('Unit ', COALESCE(u.unit_number, u.id)) AS name,
						COALESCE(u.venue_type, u.lock_type, 'Unknown') AS type,
						CONCAT_WS(', ', c.name, u.address) AS location,
						u.latitude, 
						u.longitude,
						u.status,
						u.season
					 FROM {$units_table} u
					 LEFT JOIN {$companies_table} c ON c.id = u.company_id
					 WHERE u.company_id = %d AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL",
					$company_id
				)
			);
			$units = ! empty($units) ? $units : array();

			// Get tickets for partner's units only via service_requests
			$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
			$tickets                = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						t.id,
						sr.unit_id,
						CONCAT('Ticket #', t.id) as title,
						sr.notes as description,
						t.status,
						sr.priority as urgency,
						t.created_at
					 FROM {$tickets_table} t
					 LEFT JOIN {$service_requests_table} sr ON t.service_request_id = sr.id
					 INNER JOIN {$units_table} u ON sr.unit_id = u.id
					 WHERE u.company_id = %d 
					 AND t.status NOT IN ('closed', 'resolved')",
					$company_id
				)
			);
			$tickets                = ! empty($tickets) ? $tickets : array();
		}

		// Log access for audit trail
		LGP_Logger::log_event(
			get_current_user_id(),
			'map_access',
			$is_support ? null : $company_id,
			array(
				'role'             => $is_support ? 'support' : 'partner',
				'units_returned'   => count($units),
				'tickets_returned' => count($tickets),
			)
		);

		return rest_ensure_response(
			array(
				'units'   => $units,
				'tickets' => $tickets,
				'total'   => count($units),
				'role'    => $is_support ? 'support' : 'partner',
			)
		);
	}
}

LGP_Map_API::init();
