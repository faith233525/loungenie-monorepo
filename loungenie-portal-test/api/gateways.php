<?php

use LounGenie\Portal\LGP_Auth;

/**
 * Gateways REST API
 *
 * Support-only gateway management endpoints for managing gateway devices,
 * testing connectivity, and associating units with gateways. Gateway
 * configuration is restricted to Support staff for security and integrity.
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Gateways API Handler
 *
 * Provides REST API endpoints for gateway device management including
 * CRUD operations, signal testing, and unit associations.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Gateways_API
{

	/**
	 * Initialize REST API routes.
	 *
	 * Registers all gateway management endpoints. All endpoints require
	 * Support role for device security and configuration integrity.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register gateway REST routes.
	 *
	 * Registers all gateway management endpoints:
	 * - GET /lgp/v1/gateways - List gateways with filtering
	 * - POST /lgp/v1/gateways - Create new gateway
	 * - GET /lgp/v1/gateways/{id} - Get single gateway
	 * - PUT /lgp/v1/gateways/{id} - Update gateway
	 * - DELETE /lgp/v1/gateways/{id} - Delete gateway
	 * - POST /lgp/v1/gateways/{id}/test-signal - Test gateway connectivity
	 * - GET /lgp/v1/gateways/{id}/units - Get units associated with gateway
	 *
	 * @since 2.0.0
	 * @return void
	 * @see register_rest_route()
	 */
	public static function register_routes()
	{
		register_rest_route(
			'lgp/v1',
			'/gateways',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array(__CLASS__, 'get_gateways'),
					'permission_callback' => array(__CLASS__, 'support_only_permission'),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array(__CLASS__, 'create_gateway'),
					'permission_callback' => array(__CLASS__, 'support_only_permission'),
				),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/gateways/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array(__CLASS__, 'get_gateway'),
					'permission_callback' => array(__CLASS__, 'support_only_permission'),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array(__CLASS__, 'update_gateway'),
					'permission_callback' => array(__CLASS__, 'support_only_permission'),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array(__CLASS__, 'delete_gateway'),
					'permission_callback' => array(__CLASS__, 'support_only_permission'),
				),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/gateways/(?P<id>\d+)/test-signal',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'test_signal'),
				'permission_callback' => array(__CLASS__, 'support_only_permission'),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/gateways/(?P<id>\d+)/units',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_gateway_units'),
				'permission_callback' => array(__CLASS__, 'support_only_permission'),
			)
		);
	}

	/**
	 * Support-only permission check.
	 *
	 * Verifies user has Support role for gateway management operations.
	 * Gateway configuration is restricted to Support staff for security.
	 *
	 * @since 2.0.0
	 * @return bool True if user is Support, false otherwise.
	 * @see LGP_Auth::is_support()
	 */
	public static function support_only_permission()
	{
		return LGP_Auth::is_support();
	}

	/**
	 * Get gateways with optional filtering.
	 *
	 * Returns list of gateway devices with support for filtering by
	 * company, call button status, or search term.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - company_id (int) Optional. Filter by company.
	 *                                 - call_button (bool) Optional. Filter by call button presence.
	 *                                 - search (string) Optional. Search term for gateway address.
	 * @return WP_REST_Response Response with gateways array.
	 */
	public static function get_gateways($request)
	{
		$filters = array(
			'company_id'  => $request->get_param('company_id'),
			'call_button' => $request->get_param('call_button'),
			'search'      => $request->get_param('search'),
		);

		$gateways = LGP_Gateway::get_all($filters);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $gateways,
			)
		);
	}

	/**
	 * Get single gateway by ID.
	 *
	 * Retrieves complete gateway record including configuration, company
	 * association, and equipment details.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Gateway ID from URL parameter.
	 * @return WP_REST_Response|WP_Error Gateway object on success,
	 *                                   WP_Error if gateway not found.
	 */
	public static function get_gateway($request)
	{
		$id      = (int) $request['id'];
		$gateway = LGP_Gateway::get($id);

		if (! $gateway) {
			return new WP_Error('not_found', 'Gateway not found', array('status' => 404));
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $gateway,
			)
		);
	}

	/**
	 * Create new gateway device.
	 *
	 * Creates gateway record with configuration details including channel
	 * number, address, capacity, and security credentials. All inputs are
	 * sanitized for security.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - company_id (int) Required. Company ID.
	 *                                 - channel_number (string) Required. Gateway channel.
	 *                                 - gateway_address (string) Required. Device address.
	 *                                 - unit_capacity (int) Optional. Maximum units supported.
	 *                                 - call_button (bool) Optional. Call button present.
	 *                                 - included_equipment (string) Optional. Equipment details.
	 *                                 - admin_password (string) Optional. Admin access password.
	 * @return WP_REST_Response|WP_Error Response with new gateway ID on success,
	 *                                   WP_Error if creation fails.
	 */
	public static function create_gateway($request)
	{
		$data = array(
			'company_id'         => absint($request->get_param('company_id')),
			'channel_number'     => sanitize_text_field($request->get_param('channel_number')),
			'gateway_address'    => sanitize_text_field($request->get_param('gateway_address')),
			'unit_capacity'      => absint($request->get_param('unit_capacity')),
			'call_button'        => absint($request->get_param('call_button')),
			'included_equipment' => sanitize_textarea_field($request->get_param('included_equipment')),
			'admin_password'     => sanitize_text_field($request->get_param('admin_password')),
		);

		$gateway_id = LGP_Gateway::create($data);

		if (! $gateway_id) {
			return new WP_Error('create_failed', 'Failed to create gateway', array('status' => 500));
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array('id' => $gateway_id),
				'message' => 'Gateway created successfully',
			)
		);
	}

	/**
	 * PUT /lgp/v1/gateways/:id
	 */
	public static function update_gateway($request)
	{
		$id = (int) $request['id'];

		$data = array(
			'company_id'         => absint($request->get_param('company_id')),
			'channel_number'     => sanitize_text_field($request->get_param('channel_number')),
			'gateway_address'    => sanitize_text_field($request->get_param('gateway_address')),
			'unit_capacity'      => absint($request->get_param('unit_capacity')),
			'call_button'        => absint($request->get_param('call_button')),
			'included_equipment' => sanitize_textarea_field($request->get_param('included_equipment')),
			'admin_password'     => sanitize_text_field($request->get_param('admin_password')),
		);

		$result = LGP_Gateway::update($id, $data);

		if (! $result) {
			return new WP_Error('update_failed', 'Failed to update gateway', array('status' => 500));
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Gateway updated successfully',
			)
		);
	}

	/**
	 * DELETE /lgp/v1/gateways/:id
	 */
	public static function delete_gateway($request)
	{
		$id = (int) $request['id'];

		$result = LGP_Gateway::delete($id);

		if (! $result) {
			return new WP_Error('delete_failed', 'Failed to delete gateway', array('status' => 500));
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Gateway deleted successfully',
			)
		);
	}

	/**
	 * POST /lgp/v1/gateways/:id/test-signal
	 */
	public static function test_signal($request)
	{
		$id = (int) $request['id'];

		$result = LGP_Gateway::test_signal($id);

		return rest_ensure_response($result);
	}

	/**
	 * GET /lgp/v1/gateways/:id/units
	 */
	public static function get_gateway_units($request)
	{
		$id = (int) $request['id'];

		$units = LGP_Gateway::get_connected_units($id);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $units,
			)
		);
	}
}
