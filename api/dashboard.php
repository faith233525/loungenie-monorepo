<?php

/**
 * Dashboard metrics API.
 *
 * Returns aggregated metrics for Support/Partner users.
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Dashboard API class.
 */
class LGP_Dashboard_API
{

	/**
	 * Initialize dashboard API.
	 *
	 * @return void
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register API routes.
	 *
	 * @return void
	 */
	public static function register_routes()
	{
		$api = new self();
		register_rest_route(
			'lgp/v1',
			'/dashboard',
			array(
				'methods'             => 'GET',
				'callback'            => array($api, 'get_metrics'),
				'permission_callback' => array($api, 'check_portal_access'),
			)
		);
	}

	/**
	 * Check portal access.
	 *
	 * @return bool
	 */
	public function check_portal_access()
	{
		if (! is_user_logged_in()) {
			return false;
		}
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Get metrics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_metrics($request)
	{
		global $wpdb;

		// Enhanced authentication check.
		if (! is_user_logged_in()) {
			return new WP_Error(
				'unauthorized',
				'Authentication required',
				array('status' => 401)
			);
		}

		// Role-based access control.
		$is_support = LGP_Auth::is_support();
		$is_partner = LGP_Auth::is_partner();

		if (! $is_support && ! $is_partner) {
			return new WP_Error(
				'forbidden',
				'Insufficient permissions to access dashboard',
				array('status' => 403)
			);
		}

		// Get company context for partners.
		$company_id = LGP_Auth::get_user_company_id();

		if (! $is_support && empty($company_id)) {
			return new WP_Error(
				'invalid_company',
				'No company associated with user account',
				array('status' => 400)
			);
		}

		// Optimization: Use transient cache (15 min TTL) for fast dashboard loads.
		$cache_key = 'lgp_dashboard_' . ($is_support ? 'support' : $company_id);
		$cached    = get_transient($cache_key);

		if (false !== $cached) {
			$cached['from_cache'] = true;
			$cached['cache_age']  = round((time() - $cached['generated_at']) / 60) . ' min';
			return rest_ensure_response($cached);
		}

		// Database tables.
		$units_table    = $wpdb->prefix . 'lgp_units';
		$tickets_table  = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';

		// Apply role-based filtering at database level.
		if ($is_support) {
			// Support sees all companies.
			$where_units   = '1=1';
			$where_company = '1=1';
		} else {
			// Partner sees only their company.
			$where_units   = $wpdb->prepare('company_id = %d', $company_id);
			$where_company = $wpdb->prepare('sr.company_id = %d', $company_id);
		}

		// Units total (use prepared statements when scoping by company).
		if ($is_support) {
			$units_result = $wpdb->get_var("SELECT COUNT(*) FROM {$units_table}");
			$total_units  = ! empty($units_result) ? (int) $units_result : 0;
		} else {
			$units_result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$units_table} WHERE company_id = %d",
					$company_id
				)
			);
			$total_units  = ! empty($units_result) ? (int) $units_result : 0;
		}

		// Ticket counts are derived by joining service requests -> tickets for company scope.
		if ($is_support) {
			$tickets_result = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE t.status NOT IN ('resolved','closed')"
			);
			$active_tickets = ! empty($tickets_result) ? (int) $tickets_result : 0;
		} else {
			$tickets_result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND t.status NOT IN ('resolved','closed')",
					$company_id
				)
			);
			$active_tickets = ! empty($tickets_result) ? (int) $tickets_result : 0;
		}

		if ($is_support) {
			$today_result   = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE DATE(t.updated_at) = CURDATE() AND t.status IN ('resolved','closed')"
			);
			$resolved_today = ! empty($today_result) ? (int) $today_result : 0;
		} else {
			$today_result   = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND DATE(t.updated_at) = CURDATE() AND t.status IN ('resolved','closed')",
					$company_id
				)
			);
			$resolved_today = ! empty($today_result) ? (int) $today_result : 0;
		}

		// Average resolution time (hours).
		// SHARED HOSTING: Limit dataset to recent tickets only for memory efficiency.
		if ($is_support) {
			$resolution_data = $wpdb->get_var(
				"SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
				 FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE t.status IN ('resolved','closed') 
				   AND t.updated_at IS NOT NULL
				   AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				 LIMIT 1000"
			);
		} else {
			$resolution_data = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
					 FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d 
					   AND t.status IN ('resolved','closed') 
					   AND t.updated_at IS NOT NULL
					   AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
					 LIMIT 1000",
					$company_id
				)
			);
		}
		$avg_resolution = $resolution_data !== null ? round((float) $resolution_data, 1) : null;

		// Log access for audit trail.
		if (class_exists('LGP_Logger')) {
			LGP_Logger::log_event(
				get_current_user_id(),
				'dashboard_access',
				$is_support ? null : $company_id,
				array(
					'role'             => $is_support ? 'support' : 'partner',
					'metrics_accessed' => true,
				)
			);
		}

		$result = array(
			'total_units'        => $total_units,
			'active_tickets'     => $active_tickets,
			'resolved_today'     => $resolved_today,
			'average_resolution' => $avg_resolution,
			'role'               => $is_support ? 'support' : 'partner',
			'company_id'         => $is_support ? null : $company_id,
			'generated_at'       => time(),
			'from_cache'         => false,
		);

		// SHARED HOSTING: Cache for 15 minutes to reduce database load.
		set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);

		return rest_ensure_response($result);
	}

	/**
	 * Invalidate dashboard cache (called when tickets/units change)
	 *
	 * @param int $company_id Company ID (null for support view)
	 */
	public static function invalidate_cache($company_id = null)
	{
		if ($company_id) {
			delete_transient('lgp_dashboard_' . $company_id);
		} else {
			delete_transient('lgp_dashboard_support');
		}
	}
}

LGP_Dashboard_API::init();

// Invalidate cache when tickets or units change
add_action(
	'lgp_ticket_created',
	function ($ticket_id) {
		global $wpdb;
		$ticket = $wpdb->get_row($wpdb->prepare("SELECT service_request_id FROM {$wpdb->prefix}lgp_tickets WHERE id = %d", $ticket_id));
		if ($ticket) {
			$request = $wpdb->get_row($wpdb->prepare("SELECT company_id FROM {$wpdb->prefix}lgp_service_requests WHERE id = %d", $ticket->service_request_id));
			if ($request) {
				LGP_Dashboard_API::invalidate_cache($request->company_id);
				LGP_Dashboard_API::invalidate_cache(null); // Support view
			}
		}
	}
);
add_action(
	'lgp_ticket_updated',
	function ($ticket_id) {
		global $wpdb;
		$ticket = $wpdb->get_row($wpdb->prepare("SELECT service_request_id FROM {$wpdb->prefix}lgp_tickets WHERE id = %d", $ticket_id));
		if ($ticket) {
			$request = $wpdb->get_row($wpdb->prepare("SELECT company_id FROM {$wpdb->prefix}lgp_service_requests WHERE id = %d", $ticket->service_request_id));
			if ($request) {
				LGP_Dashboard_API::invalidate_cache($request->company_id);
				LGP_Dashboard_API::invalidate_cache(null); // Support view
			}
		}
	}
);
