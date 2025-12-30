<?php

/**
 * Dashboard Metrics API
 *
 * Returns aggregated metrics and statistics for dashboard displays.
 * Implements role-based filtering to show Support users all data while
 * Partners see only their company's metrics. Used by dashboard widgets
 * for real-time status updates.
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

namespace LounGenie\Portal;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Dashboard API Handler
 *
 * Provides aggregated metrics with role-based filtering for dashboard
 * visualization. Includes ticket counts, unit statistics, and resolution metrics.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Dashboard_API
{

	/**
	 * Initialize API endpoints.
	 *
	 * Registers dashboard metrics endpoint for retrieving aggregated
	 * statistics and KPIs.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers dashboard metrics endpoint:
	 * - GET /lgp/v1/dashboard - Get aggregated metrics and statistics
	 *
	 * Metrics are automatically filtered based on user role (Support vs Partner).
	 *
	 * @since 2.0.0
	 * @return void
	 * @see register_rest_route()
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
	 * Check if user has portal access.
	 *
	 * Verifies user is logged in and has either Support or Partner role.
	 * Used as permission callback for dashboard metrics endpoint.
	 *
	 * @since 2.0.0
	 * @return bool True if user has portal access, false otherwise.
	 * @see LGP_Auth::is_support()
	 * @see LGP_Auth::is_partner()
	 */
	public function check_portal_access()
	{
		if (! is_user_logged_in()) {
			return false;
		}
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Get dashboard metrics with role-based filtering.
	 *
	 * Returns aggregated metrics including:
	 * - Total units count
	 * - Active tickets count (open, in progress)
	 * - Tickets resolved today
	 * - Average resolution time
	 * - Response time statistics
	 *
	 * Support users see metrics across all companies. Partners see only
	 * their company's metrics. All queries use prepared statements for
	 * security and proper company-level data isolation.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return WP_REST_Response|WP_Error Response with metrics object,
	 *                                   WP_Error if authentication fails or invalid company.
	 */
	public function get_metrics($request)
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

		// Role-based access control
		$is_support = LGP_Auth::is_support();
		$is_partner = LGP_Auth::is_partner();

		if (! $is_support && ! $is_partner) {
			return new WP_Error(
				'forbidden',
				'Insufficient permissions to access dashboard',
				array('status' => 403)
			);
		}

		// Get company context for partners
		$company_id = LGP_Auth::get_user_company_id();

		if (! $is_support && empty($company_id)) {
			return new WP_Error(
				'invalid_company',
				'No company associated with user account',
				array('status' => 400)
			);
		}

		// PERFORMANCE OPTIMIZATION: Implement transient caching for dashboard metrics
		// Cache key is role-specific to ensure proper data isolation
		$cache_key = $is_support ? 'lgp_dashboard_metrics_support' : 'lgp_dashboard_metrics_company_' . $company_id;
		$metrics   = get_transient($cache_key);

		if (false !== $metrics) {
			// Return cached metrics
			return rest_ensure_response($metrics);
		}

		// Database tables
		$units_table    = $wpdb->prefix . 'lgp_units';
		$tickets_table  = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';

		// Apply role-based filtering at database level
		if ($is_support) {
			// Support sees all companies
			$where_units   = '1=1';
			$where_company = '1=1';
		} else {
			// Partner sees only their company
			$where_units   = $wpdb->prepare('company_id = %d', $company_id);
			$where_company = $wpdb->prepare('sr.company_id = %d', $company_id);
		}

		// Units total (use prepared statements when scoping by company)
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

		// Ticket counts are derived by joining service requests -> tickets for company scope
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

		// Average resolution time (hours)
		if ($is_support) {
			$resolution_data = $wpdb->get_var(
				"SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
				 FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE t.status IN ('resolved','closed') AND t.updated_at IS NOT NULL"
			);
		} else {
			$resolution_data = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
					 FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND t.status IN ('resolved','closed') AND t.updated_at IS NOT NULL",
					$company_id
				)
			);
		}
		$avg_resolution = $resolution_data !== null ? round((float) $resolution_data, 1) : null;

		// Log access for audit trail
		LGP_Logger::log_event(
			get_current_user_id(),
			'dashboard_access',
			$is_support ? null : $company_id,
			array(
				'role'             => $is_support ? 'support' : 'partner',
				'metrics_accessed' => true,
			)
		);

		$metrics = array(
			'total_units'        => $total_units,
			'active_tickets'     => $active_tickets,
			'resolved_today'     => $resolved_today,
			'average_resolution' => $avg_resolution,
			'role'               => $is_support ? 'support' : 'partner',
			'company_id'         => $is_support ? null : $company_id,
		);

		// PERFORMANCE: Cache metrics for 5 minutes
		set_transient($cache_key, $metrics, 5 * MINUTE_IN_SECONDS);

		return rest_ensure_response($metrics);
	}
}

LGP_Dashboard_API::init();
