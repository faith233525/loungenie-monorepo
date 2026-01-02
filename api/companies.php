<?php

use LounGenie\Portal\LGP_Auth;

/**
 * Companies REST API Endpoints
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Companies_API {



	/**
	 * Initialize API endpoints
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		// Get all companies (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_companies' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);

		// Get single company
		register_rest_route(
			'lgp/v1',
			'/companies/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_company' ),
				'permission_callback' => array( __CLASS__, 'check_company_permission' ),
			)
		);

		// Create company (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_company' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);

		// Update company (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'update_company' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);
	}

	/**
	 * Get all companies
	 */
	public static function get_companies( $request ) {
		global $wpdb;

		$table    = $wpdb->prefix . 'lgp_companies';
		$page     = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$offset   = ( $page - 1 ) * $per_page;

		// Fetch companies with pagination.
		$companies = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY name ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		// Get total count.
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		return rest_ensure_response(
			array(
				'companies' => $companies,
				'total'     => (int) $total,
				'page'      => (int) $page,
				'per_page'  => (int) $per_page,
			)
		);
	}

	/**
	 * Get single company
	 */
	public static function get_company( $request ) {
		global $wpdb;

		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'lgp_companies';

		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$id
			)
		);

		if ( ! $company ) {
			return new WP_Error( 'not_found', __( 'Company not found', 'loungenie-portal' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $company );
	}

	/**
	 * Create company
	 */
	public static function create_company( $request ) {
		global $wpdb;

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$table = $wpdb->prefix . 'lgp_companies';

		$data = array(
			'name'                  => sanitize_text_field( $request->get_param( 'name' ) ),
			'address'               => sanitize_textarea_field( $request->get_param( 'address' ) ),
			'state'                 => sanitize_text_field( $request->get_param( 'state' ) ),
			'contact_name'          => sanitize_text_field( $request->get_param( 'contact_name' ) ),
			'contact_email'         => sanitize_email( $request->get_param( 'contact_email' ) ),
			'contact_phone'         => sanitize_text_field( $request->get_param( 'contact_phone' ) ),
			'management_company_id' => absint( $request->get_param( 'management_company_id' ) ),
		);

		$inserted = $wpdb->insert( $table, $data );

		if ( $inserted === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create company', 'loungenie-portal' ), array( 'status' => 500 ) );
		}

		$company_id = $wpdb->insert_id;

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'company_created',
			$company_id,
			array(
				'company_name' => $data['name'],
				'state'        => $data['state'],
			)
		);

		// Fire action for integrations
		do_action( 'lgp_company_created', $company_id );

		return rest_ensure_response(
			array(
				'id'      => $company_id,
				'message' => __( 'Company created successfully', 'loungenie-portal' ),
			)
		);
	}

	/**
	 * Update company
	 */
	public static function update_company( $request ) {
		global $wpdb;

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'lgp_companies';

		$data = array(
			'name'                  => sanitize_text_field( $request->get_param( 'name' ) ),
			'address'               => sanitize_textarea_field( $request->get_param( 'address' ) ),
			'state'                 => sanitize_text_field( $request->get_param( 'state' ) ),
			'contact_name'          => sanitize_text_field( $request->get_param( 'contact_name' ) ),
			'contact_email'         => sanitize_email( $request->get_param( 'contact_email' ) ),
			'contact_phone'         => sanitize_text_field( $request->get_param( 'contact_phone' ) ),
			'management_company_id' => absint( $request->get_param( 'management_company_id' ) ),
		);

		$updated = $wpdb->update( $table, $data, array( 'id' => $id ) );

		if ( $updated === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update company', 'loungenie-portal' ), array( 'status' => 500 ) );
		}

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'company_updated',
			$id,
			array(
				'company_name'   => $data['name'],
				'fields_updated' => array_keys( $data ),
			)
		);

		return rest_ensure_response(
			array(
				'message' => __( 'Company updated successfully', 'loungenie-portal' ),
			)
		);
	}

	/**
	 * Check if user is Support
	 */
	public static function check_support_permission() {
		return LGP_Auth::is_support();
	}

	/**
	 * Check if user can access company
	 * Support can access all, Partners can access their own
	 */
	public static function check_company_permission( $request ) {
		if ( LGP_Auth::is_support() ) {
			return true;
		}

		if ( LGP_Auth::is_partner() ) {
			$company_id   = LGP_Auth::get_user_company_id();
			$requested_id = (int) $request->get_param( 'id' );
			return (int) $company_id === $requested_id;
		}

		return false;
	}
}
