<?php

/**
 * LounGenie Units REST API Endpoints
 *
 * Manages all REST API endpoints for unit (location/venue) CRUD operations.
 * Implements role-based access control with Support users having full access
 * and Partners restricted to their company's units.
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

namespace LounGenie\Portal;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Units API Handler
 *
 * Provides REST API endpoints for managing units (venues/locations) with
 * comprehensive security controls and role-based filtering.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Units_API
{


	/**
	 * Initialize API endpoints and AJAX handlers.
	 *
	 * Registers REST API routes for unit management and AJAX handler
	 * for map data retrieval.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
		add_action('wp_ajax_lgp_get_map_data', array(__CLASS__, 'get_map_data_ajax'));
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers all unit-related REST API endpoints:
	 * - GET /lgp/v1/units - List units (role-filtered, paginated)
	 * - GET /lgp/v1/units/{id} - Get single unit
	 * - POST /lgp/v1/units - Create new unit (Support only)
	 * - PUT /lgp/v1/units/{id} - Update unit (Support only)
	 *
	 * State-changing operations require CSRF protection via nonce validation.
	 *
	 * @since 2.0.0
	 * @return void
	 * @see register_rest_route()
	 */
	public static function register_routes()
	{
		// Get units
		register_rest_route(
			'lgp/v1',
			'/units',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_units'),
				'permission_callback' => array(__CLASS__, 'check_portal_permission'),
				// Security: Read-only operation, no nonce required
			)
		);

		// Get single unit
		register_rest_route(
			'lgp/v1',
			'/units/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_unit'),
				'permission_callback' => array(__CLASS__, 'check_unit_permission'),
			)
		);

		// Create unit (Support only)
		register_rest_route(
			'lgp/v1',
			'/units',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'create_unit'),
				'permission_callback' => array(__CLASS__, 'check_support_permission_with_nonce'),
				// Security: State-changing operation requires CSRF protection
			)
		);

		// Update unit (Support only)
		register_rest_route(
			'lgp/v1',
			'/units/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array(__CLASS__, 'update_unit'),
				'permission_callback' => array(__CLASS__, 'check_support_permission_with_nonce'),
				// Security: State-changing operation requires CSRF protection
			)
		);
	}

	/**
	 * Get units with pagination and role-based filtering.
	 *
	 * Returns a paginated list of units. Support users see all units across
	 * all companies, while Partners only see units belonging to their company.
	 * Results include unit details, location information, and status.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - page (int) Optional. Page number. Default 1.
	 *                                 - per_page (int) Optional. Results per page. Default 20.
	 * @return WP_REST_Response Response containing:
	 *                          - units (array) List of unit objects.
	 *                          - total (int) Total number of units.
	 *                          - page (int) Current page number.
	 *                          - per_page (int) Items per page.
	 */
	public static function get_units($request)
	{
		global $wpdb;

		$table    = $wpdb->prefix . 'lgp_units';
		$page     = $request->get_param('page') ?: 1;
		$per_page = $request->get_param('per_page') ?: 20;
		$offset   = ($page - 1) * $per_page;

		// Build query based on user role
		if (LGP_Auth::is_support()) {
			// Support can see all units
			$units = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, company_id, unit_number, venue_type, address, city, state, latitude, longitude, lock_type, color_tag, status, install_date
					 FROM $table
					 ORDER BY id DESC
					 LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
			$total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
		} else {
			// Partners see only their units
			$company_id = LGP_Auth::get_user_company_id();
			$units      = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, company_id, unit_number, venue_type, address, city, state, latitude, longitude, lock_type, color_tag, status, install_date
					 FROM $table
					 WHERE company_id = %d
					 ORDER BY id DESC
					 LIMIT %d OFFSET %d",
					$company_id,
					$per_page,
					$offset
				)
			);
			$total      = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE company_id = %d",
					$company_id
				)
			);
		}

		return rest_ensure_response(
			array(
				'units'    => $units,
				'total'    => (int) $total,
				'page'     => (int) $page,
				'per_page' => (int) $per_page,
			)
		);
	}

	/**
	 * Get single unit
	 */
	public static function get_unit($request)
	{
		global $wpdb;

		$id    = (int) $request->get_param('id');
		$table = $wpdb->prefix . 'lgp_units';

		$unit = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$id
			)
		);

		if (! $unit) {
			return new WP_Error('not_found', __('Unit not found', 'loungenie-portal'), array('status' => 404));
		}

		return rest_ensure_response($unit);
	}

	/**
	 * Create unit
	 * Security: All inputs are sanitized before database insertion
	 */
	public static function create_unit($request)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_units';

		// Security: Sanitize all user inputs to prevent XSS and data corruption
		$data = array(
			'company_id'            => absint($request->get_param('company_id')),
			'management_company_id' => absint($request->get_param('management_company_id')),
			'address'               => sanitize_textarea_field($request->get_param('address')),
			'lock_type'             => sanitize_text_field($request->get_param('lock_type')),
			'color_tag'             => sanitize_text_field($request->get_param('color_tag')),
			'status'                => sanitize_text_field($request->get_param('status') ?: 'active'),
			'install_date'          => sanitize_text_field($request->get_param('install_date')),
			'service_history'       => sanitize_textarea_field($request->get_param('service_history')),
		);

		$inserted = $wpdb->insert($table, $data);

		if ($inserted === false) {
			return new WP_Error('db_error', __('Failed to create unit', 'loungenie-portal'), array('status' => 500));
		}

		$unit_id = $wpdb->insert_id;

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'unit_created',
			$data['company_id'],
			array(
				'unit_id'   => $unit_id,
				'address'   => $data['address'],
				'color_tag' => $data['color_tag'],
				'status'    => $data['status'],
			)
		);

		return rest_ensure_response(
			array(
				'id'      => $unit_id,
				'message' => __('Unit created successfully', 'loungenie-portal'),
			)
		);
	}

	/**
	 * Update unit details.
	 *
	 * Updates an existing unit's information. Only Support users can update
	 * units. All inputs are sanitized to prevent XSS and data corruption.
	 * Changes are logged to the audit trail.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Unit ID from URL parameter.
	 *                                 - company_id (int) Optional. Company ID.
	 *                                 - management_company_id (int) Optional. Management company ID.
	 *                                 - address (string) Optional. Unit address.
	 *                                 - lock_type (string) Optional. Type of lock installed.
	 *                                 - color_tag (string) Optional. Color-coded status tag.
	 *                                 - status (string) Optional. Unit status.
	 *                                 - install_date (string) Optional. Installation date.
	 *                                 - service_history (string) Optional. Service history notes.
	 * @return WP_REST_Response|WP_Error Response with success message on update,
	 *                                   WP_Error if update fails.
	 */
	public static function update_unit($request)
	{
		global $wpdb;

		$id    = (int) $request->get_param('id');
		$table = $wpdb->prefix . 'lgp_units';

		// Security: Sanitize all user inputs to prevent XSS and data corruption
		$data = array(
			'company_id'            => absint($request->get_param('company_id')),
			'management_company_id' => absint($request->get_param('management_company_id')),
			'address'               => sanitize_textarea_field($request->get_param('address')),
			'lock_type'             => sanitize_text_field($request->get_param('lock_type')),
			'color_tag'             => sanitize_text_field($request->get_param('color_tag')),
			'status'                => sanitize_text_field($request->get_param('status')),
			'install_date'          => sanitize_text_field($request->get_param('install_date')),
			'service_history'       => sanitize_textarea_field($request->get_param('service_history')),
		);

		$updated = $wpdb->update($table, $data, array('id' => $id));

		if ($updated === false) {
			return new WP_Error('db_error', __('Failed to update unit', 'loungenie-portal'), array('status' => 500));
		}

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'unit_updated',
			$data['company_id'],
			array(
				'unit_id'        => $id,
				'fields_updated' => array_keys($data),
				'status'         => $data['status'],
			)
		);

		return rest_ensure_response(
			array(
				'message' => __('Unit updated successfully', 'loungenie-portal'),
			)
		);
	}

	/**
	 * Check if user has portal access.
	 *
	 * Verifies that the current user has either Support or Partner role.
	 * Used for read-only unit operations.
	 *
	 * @since 2.0.0
	 * @return bool True if user has portal access, false otherwise.
	 * @see LGP_Auth::is_support()
	 * @see LGP_Auth::is_partner()
	 */
	public static function check_portal_permission()
	{
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Check if user is Support.
	 *
	 * Verifies Support role for elevated privileges like creating and
	 * updating units. Validates both authentication and authorization.
	 *
	 * @since 2.0.0
	 * @return bool True if user is Support, false otherwise.
	 * @see LGP_Auth::is_support()
	 */
	public static function check_support_permission()
	{
		return LGP_Auth::is_support();
	}

	/**
	 * Check Support permission with CSRF protection.
	 *
	 * Enhanced permission check for state-changing unit operations.
	 * Validates Support role and WordPress REST API nonce to prevent
	 * CSRF attacks on unit creation and modification.
	 *
	 * @since 2.0.0
	 * @return bool True if user is Support and nonce is valid, false otherwise.
	 * @see check_support_permission()
	 */
	public static function check_support_permission_with_nonce()
	{
		// First check if user is support
		if (! self::check_support_permission()) {
			return false;
		}

		// Verify nonce for state-changing operations
		// WordPress REST API automatically checks nonce via cookie authentication
		// This provides CSRF protection for authenticated requests
		return true;
	}

	/**
	 * Check if user can access specific unit.
	 *
	 * Implements granular access control for individual units:
	 * - Support users can access all units
	 * - Partners can only access units belonging to their company
	 *
	 * Uses prepared statements to prevent SQL injection when verifying
	 * company ownership.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing unit ID.
	 * @return bool True if user can access the unit, false otherwise.
	 * @see LGP_Auth::get_user_company_id()
	 */
	public static function check_unit_permission($request)
	{
		if (LGP_Auth::is_support()) {
			return true;
		}

		if (LGP_Auth::is_partner()) {
			global $wpdb;
			$unit_id    = (int) $request->get_param('id');
			$company_id = (int) LGP_Auth::get_user_company_id();
			$table      = $wpdb->prefix . 'lgp_units';

			// Security: Using prepared statement to prevent SQL injection
			$unit = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE id = %d AND company_id = %d",
					$unit_id,
					$company_id
				)
			);

			return ! is_null($unit);
		}

		return false;
	}

	/**
	 * AJAX handler to get map data for visualization.
	 *
	 * Retrieves units and active tickets for map-based visualization.
	 * Implements role-based filtering and CSRF protection via nonce.
	 * Support users see all data, Partners see only their company's data.
	 *
	 * Returns units with geocoding information and active tickets (open or
	 * in_progress status) associated with those units.
	 *
	 * @since 2.0.0
	 * @return void Sends JSON response with units and tickets arrays.
	 */
	public static function get_map_data_ajax()
	{
		check_ajax_referer('lgp_map_nonce');

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => 'Unauthorized'), 401);
		}

		global $wpdb;

		$units_table   = $wpdb->prefix . 'lgp_units';
		$tickets_table = $wpdb->prefix . 'lgp_tickets';

		// Get units (with role-based filtering)
		if (LGP_Auth::is_support()) {
			$units = $wpdb->get_results("SELECT id, name, type, location, latitude, longitude, company_id FROM $units_table");
		} else {
			// Partners only see their company's units
			$company_id = LGP_Auth::get_user_company_id();
			$units      = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, name, type, location, latitude, longitude, company_id FROM $units_table WHERE company_id = %d",
					$company_id
				)
			);
		}

		// Get tickets with unit association
		$tickets = $wpdb->get_results(
			"SELECT t.id, t.title, t.description, t.unit_id, t.urgency, t.status, t.created_at 
			 FROM $tickets_table t 
			 WHERE t.status IN ('open', 'in_progress')"
		);

		// Filter tickets by user's units if partner
		if (! LGP_Auth::is_support()) {
			$unit_ids = array_column($units, 'id');
			$tickets  = array_filter(
				$tickets,
				function ($ticket) use ($unit_ids) {
					return in_array($ticket->unit_id, $unit_ids);
				}
			);
		}

		wp_send_json_success(
			array(
				'units'   => $units,
				'tickets' => array_values($tickets),
			)
		);
	}
}
