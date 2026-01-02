<?php

/**
 * Audit Log REST API
 * Endpoints for viewing audit logs
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register audit log REST endpoint.
 */
function lgp_register_audit_log_rest_route() {
	register_rest_route(
		'lgp/v1',
		'/audit-log',
		array(
			'methods'             => 'GET',
			'callback'            => 'lgp_get_audit_log',
			'permission_callback' => function () {
				return LGP_Auth::is_support();
			},
		)
	);
}

add_action( 'rest_api_init', 'lgp_register_audit_log_rest_route' );

/**
 * Get audit log for a company.
 */
function lgp_get_audit_log( WP_REST_Request $request ) {
	global $wpdb;

	$company_id = $request->get_param( 'company_id' );
	$action     = $request->get_param( 'action' );
	$date       = $request->get_param( 'date' );
	$per_page   = (int) $request->get_param( 'per_page' ) ?: 100;

	if ( ! $company_id ) {
		return new WP_Error( 'missing_company', 'Company ID required', array( 'status' => 400 ) );
	}

	// Verify access - support only.
	if ( ! LGP_Auth::is_support() ) {
		return new WP_Error( 'unauthorized', 'Access denied', array( 'status' => 403 ) );
	}

	$table = $wpdb->prefix . 'lgp_audit_log';

	// Build query.
	$where  = array( 'company_id = %d' );
	$params = array( $company_id );

	if ( $action ) {
		$where[]  = 'action LIKE %s';
		$params[] = '%' . $wpdb->esc_like( $action ) . '%';
	}

	if ( $date ) {
		$where[]  = 'DATE(created_at) = %s';
		$params[] = sanitize_text_field( $date );
	}

	$where_clause = implode( ' AND ', $where );

	$query = $wpdb->prepare(
		"SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d",
		array_merge( $params, array( $per_page ) )
	);

	$logs = $wpdb->get_results( $query );

	// Enrich with user information
	foreach ( $logs as &$log ) {
		if ( $log->user_id ) {
			$user = get_user_by( 'id', $log->user_id );
			if ( $user ) {
				$log->user_login = $user->user_login;
				$log->user_email = $user->user_email;
			}
		}
	}

	return rest_ensure_response( $logs );
}
