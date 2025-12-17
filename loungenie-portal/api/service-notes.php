<?php
/**
 * Service Notes REST API
 * Endpoints for managing technician service notes
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function() {
		register_rest_route(
			'lgp/v1',
			'/service-notes',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => 'lgp_handle_service_notes',
				'permission_callback' => function() {
					return LGP_Auth::is_support();
				},
			)
		);
	}
);

/**
 * Handle service notes requests
 */
function lgp_handle_service_notes( WP_REST_Request $request ) {
	global $wpdb;

	if ( 'GET' === $request->get_method() ) {
		return lgp_get_service_notes( $request );
	}

	if ( 'POST' === $request->get_method() ) {
		return lgp_create_service_note( $request );
	}

	return new WP_Error( 'invalid_method', 'Method not allowed', array( 'status' => 405 ) );
}

/**
 * Get service notes for a company
 */
function lgp_get_service_notes( WP_REST_Request $request ) {
	global $wpdb;

	$company_id = $request->get_param( 'company_id' );

	if ( ! $company_id ) {
		return new WP_Error( 'missing_company', 'Company ID required', array( 'status' => 400 ) );
	}

	// Verify access
	if ( ! LGP_Auth::is_support() ) {
		return new WP_Error( 'unauthorized', 'Access denied', array( 'status' => 403 ) );
	}

	$table = $wpdb->prefix . 'lgp_service_notes';
	$notes = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE company_id = %d ORDER BY service_date DESC LIMIT 100",
			$company_id
		)
	);

	return rest_ensure_response( $notes );
}

/**
 * Create a new service note
 */
function lgp_create_service_note( WP_REST_Request $request ) {
	global $wpdb;

	$company_id      = $request->get_json_params()['company_id'] ?? null;
	$unit_id         = $request->get_json_params()['unit_id'] ?? null;
	$service_type    = $request->get_json_params()['service_type'] ?? null;
	$technician_name = $request->get_json_params()['technician_name'] ?? null;
	$notes           = $request->get_json_params()['notes'] ?? null;
	$travel_time     = (int) ( $request->get_json_params()['travel_time'] ?? 0 );
	$service_date    = $request->get_json_params()['service_date'] ?? null;

	// Validate required fields
	if ( ! $company_id || ! $service_type || ! $technician_name || ! $notes || ! $service_date ) {
		return new WP_Error(
			'missing_fields',
			'Missing required fields: company_id, service_type, technician_name, notes, service_date',
			array( 'status' => 400 )
		);
	}

	// Verify access
	if ( ! LGP_Auth::is_support() ) {
		return new WP_Error( 'unauthorized', 'Access denied', array( 'status' => 403 ) );
	}

	$user  = wp_get_current_user();
	$table = $wpdb->prefix . 'lgp_service_notes';

	$data = array(
		'company_id'      => (int) $company_id,
		'unit_id'         => $unit_id ? (int) $unit_id : null,
		'user_id'         => $user->ID,
		'service_type'    => sanitize_text_field( $service_type ),
		'technician_name' => sanitize_text_field( $technician_name ),
		'notes'           => wp_kses_post( $notes ),
		'travel_time'     => $travel_time,
		'service_date'    => sanitize_text_field( $service_date ),
		'created_at'      => current_time( 'mysql', true ),
	);

	$result = $wpdb->insert( $table, $data );

	if ( ! $result ) {
		return new WP_Error( 'db_error', 'Failed to save service note', array( 'status' => 500 ) );
	}

	// Log the action
	LGP_Logger::log_event(
		$user->ID,
		'service_note_created',
		$company_id,
		array(
			'service_note_id' => $wpdb->insert_id,
			'service_type'    => $service_type,
			'technician'      => $technician_name,
			'unit_id'         => $unit_id,
		)
	);

	return rest_ensure_response(
		array(
			'id'      => $wpdb->insert_id,
			'success' => true,
			'message' => 'Service note created successfully',
		)
	);
}
