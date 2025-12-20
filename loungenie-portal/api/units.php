<?php
/**
 * LounGenie Units REST API Endpoints
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Units_API {

	/**
	 * Initialize API endpoints
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_ajax_lgp_get_map_data', array( __CLASS__, 'get_map_data_ajax' ) );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		// Get units
		register_rest_route(
			'lgp/v1',
			'/units',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_units' ),
				'permission_callback' => array( __CLASS__, 'check_portal_permission' ),
			)
		);

		// Get single unit
		register_rest_route(
			'lgp/v1',
			'/units/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_unit' ),
				'permission_callback' => array( __CLASS__, 'check_unit_permission' ),
			)
		);

		// Create unit (Support only)
		register_rest_route(
			'lgp/v1',
			'/units',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_unit' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);

		// Update unit (Support only)
		register_rest_route(
			'lgp/v1',
			'/units/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'update_unit' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);
	}

	/**
	 * Get units
	 */
	public static function get_units( $request ) {
		global $wpdb;

		$table    = $wpdb->prefix . 'lgp_units';
		$page     = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$offset   = ( $page - 1 ) * $per_page;

		// Build query based on user role
		if ( LGP_Auth::is_support() ) {
			// Support can see all units
			$units = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		} else {
			// Partners see only their units
			$company_id = LGP_Auth::get_user_company_id();
			$units      = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE company_id = %d ORDER BY id DESC LIMIT %d OFFSET %d",
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
	public static function get_unit( $request ) {
		global $wpdb;

		$id    = $request->get_param( 'id' );
		$table = $wpdb->prefix . 'lgp_units';

		$unit = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$id
			)
		);

		if ( ! $unit ) {
			return new WP_Error( 'not_found', __( 'Unit not found', 'loungenie-portal' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $unit );
	}

	/**
	 * Create unit
	 */
	public static function create_unit( $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_units';

		$data = array(
			'company_id'            => absint( $request->get_param( 'company_id' ) ),
			'management_company_id' => absint( $request->get_param( 'management_company_id' ) ),
			'address'               => sanitize_textarea_field( $request->get_param( 'address' ) ),
			'lock_type'             => sanitize_text_field( $request->get_param( 'lock_type' ) ),
			'color_tag'             => sanitize_text_field( $request->get_param( 'color_tag' ) ),
			'status'                => sanitize_text_field( $request->get_param( 'status' ) ?: 'active' ),
			'install_date'          => sanitize_text_field( $request->get_param( 'install_date' ) ),
			'service_history'       => sanitize_textarea_field( $request->get_param( 'service_history' ) ),
		);

		$inserted = $wpdb->insert( $table, $data );

		if ( $inserted === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create unit', 'loungenie-portal' ), array( 'status' => 500 ) );
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
				'message' => __( 'Unit created successfully', 'loungenie-portal' ),
			)
		);
	}

	/**
	 * Update unit
	 */
	public static function update_unit( $request ) {
		global $wpdb;

		$id    = $request->get_param( 'id' );
		$table = $wpdb->prefix . 'lgp_units';

		$data = array(
			'company_id'            => absint( $request->get_param( 'company_id' ) ),
			'management_company_id' => absint( $request->get_param( 'management_company_id' ) ),
			'address'               => sanitize_textarea_field( $request->get_param( 'address' ) ),
			'lock_type'             => sanitize_text_field( $request->get_param( 'lock_type' ) ),
			'color_tag'             => sanitize_text_field( $request->get_param( 'color_tag' ) ),
			'status'                => sanitize_text_field( $request->get_param( 'status' ) ),
			'install_date'          => sanitize_text_field( $request->get_param( 'install_date' ) ),
			'service_history'       => sanitize_textarea_field( $request->get_param( 'service_history' ) ),
		);

		$updated = $wpdb->update( $table, $data, array( 'id' => $id ) );

		if ( $updated === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update unit', 'loungenie-portal' ), array( 'status' => 500 ) );
		}

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'unit_updated',
			$data['company_id'],
			array(
				'unit_id'        => $id,
				'fields_updated' => array_keys( $data ),
				'status'         => $data['status'],
			)
		);

		return rest_ensure_response(
			array(
				'message' => __( 'Unit updated successfully', 'loungenie-portal' ),
			)
		);
	}

	/**
	 * Check if user has portal access
	 */
	public static function check_portal_permission() {
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Check if user is Support
	 */
	public static function check_support_permission() {
		return LGP_Auth::is_support();
	}

	/**
	 * Check if user can access unit
	 */
	public static function check_unit_permission( $request ) {
		if ( LGP_Auth::is_support() ) {
			return true;
		}

		if ( LGP_Auth::is_partner() ) {
			global $wpdb;
			$unit_id    = $request->get_param( 'id' );
			$company_id = LGP_Auth::get_user_company_id();
			$table      = $wpdb->prefix . 'lgp_units';

			$unit = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE id = %d AND company_id = %d",
					$unit_id,
					$company_id
				)
			);

			return ! is_null( $unit );
		}

		return false;
	}

	/**
	 * AJAX handler to get map data (units + tickets)
	 */
	public static function get_map_data_ajax() {
		check_ajax_referer( 'lgp_map_nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 401 );
		}

		global $wpdb;

		$units_table   = $wpdb->prefix . 'lgp_units';
		$tickets_table = $wpdb->prefix . 'lgp_tickets';

		// Get units (with role-based filtering)
		if ( LGP_Auth::is_support() ) {
			$units = $wpdb->get_results( "SELECT id, name, type, location, latitude, longitude, company_id FROM $units_table" );
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
		if ( ! LGP_Auth::is_support() ) {
			$unit_ids = array_column( $units, 'id' );
			$tickets  = array_filter(
				$tickets,
				function( $ticket ) use ( $unit_ids ) {
					return in_array( $ticket->unit_id, $unit_ids );
				}
			);
		}

		wp_send_json_success(
			array(
				'units'   => $units,
				'tickets' => array_values( $tickets ),
			)
		);
	}
}
