<?php

use LounGenie\Portal\LGP_Auth;

/**
 * Companies REST API Endpoints
 *
 * Manages partner company CRUD operations including company profiles,
 * contact information, and management company associations. All endpoints
 * are restricted to Support users for data integrity and security.
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Companies API Handler
 *
 * Provides REST API endpoints for managing partner company records with
 * strict Support-only access control.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Companies_API
{


	/**
	 * Initialize API endpoints.
	 *
	 * Registers REST API initialization hook for company management
	 * endpoints. All endpoints require Support role.
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
	 * Registers all company management endpoints:
	 * - GET /lgp/v1/companies - List all companies (paginated)
	 * - GET /lgp/v1/companies/{id} - Get single company details
	 * - POST /lgp/v1/companies - Create new company (Support only)
	 * - PUT /lgp/v1/companies/{id} - Update company (Support only)
	 *
	 * @since 2.0.0
	 * @return void
	 * @see register_rest_route()
	 */
	public static function register_routes()
	{
		// Get all companies (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_companies'),
				'permission_callback' => array(__CLASS__, 'check_support_permission'),
			)
		);

		// Get single company
		register_rest_route(
			'lgp/v1',
			'/companies/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_company'),
				'permission_callback' => array(__CLASS__, 'check_company_permission'),
			)
		);

		// Create company (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'create_company'),
				'permission_callback' => array(__CLASS__, 'check_support_permission'),
			)
		);

		// Update company (Support only)
		register_rest_route(
			'lgp/v1',
			'/companies/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array(__CLASS__, 'update_company'),
				'permission_callback' => array(__CLASS__, 'check_support_permission'),
			)
		);
	}

	/**
	 * Get all companies with pagination.
	 *
	 * Returns paginated list of all partner companies sorted alphabetically.
	 * Restricted to Support users for maintaining data integrity.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - page (int) Optional. Page number. Default 1.
	 *                                 - per_page (int) Optional. Results per page. Default 20.
	 * @return WP_REST_Response Response containing:
	 *                          - companies (array) List of company objects.
	 *                          - total (int) Total number of companies.
	 *                          - page (int) Current page number.
	 *                          - per_page (int) Items per page.
	 */
	public static function get_companies($request)
	{
		global $wpdb;

		$table    = $wpdb->prefix . 'lgp_companies';
		$page     = $request->get_param('page') ?: 1;
		$per_page = $request->get_param('per_page') ?: 20;
		$offset   = ($page - 1) * $per_page;

		$companies = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY name ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

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
	 * Get single company by ID.
	 *
	 * Retrieves complete company record including contact information,
	 * management company associations, and credential status.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Company ID from URL parameter.
	 * @return WP_REST_Response|WP_Error Company object on success,
	 *                                   WP_Error if company not found.
	 */
	public static function get_company($request)
	{
		global $wpdb;

		$id    = (int) $request->get_param('id');
		$table = $wpdb->prefix . 'lgp_companies';

		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$id
			)
		);

		if (! $company) {
			return new WP_Error('not_found', __('Company not found', 'loungenie-portal'), array('status' => 404));
		}

		return rest_ensure_response($company);
	}

	/**
	 * Create new company record.
	 *
	 * Creates a new partner company with contact information and
	 * management company association. All inputs are sanitized to
	 * prevent XSS and data corruption.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - name (string) Required. Company name.
	 *                                 - address (string) Optional. Company address.
	 *                                 - state (string) Optional. State/province.
	 *                                 - contact_name (string) Optional. Primary contact name.
	 *                                 - contact_email (string) Optional. Primary contact email.
	 *                                 - contact_phone (string) Optional. Primary contact phone.
	 *                                 - management_company_id (int) Optional. Management company association.
	 * @return WP_REST_Response|WP_Error Response with new company ID on success,
	 *                                   WP_Error if database insert fails.
	 */
	public static function create_company($request)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_companies';

		$data = array(
			'name'                  => sanitize_text_field($request->get_param('name')),
			'address'               => sanitize_textarea_field($request->get_param('address')),
			'state'                 => sanitize_text_field($request->get_param('state')),
			'contact_name'          => sanitize_text_field($request->get_param('contact_name')),
			'contact_email'         => sanitize_email($request->get_param('contact_email')),
			'contact_phone'         => sanitize_text_field($request->get_param('contact_phone')),
			'management_company_id' => absint($request->get_param('management_company_id')),
		);

		$inserted = $wpdb->insert($table, $data);

		if ($inserted === false) {
			return new WP_Error('db_error', __('Failed to create company', 'loungenie-portal'), array('status' => 500));
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
		do_action('lgp_company_created', $company_id);

		return rest_ensure_response(
			array(
				'id'      => $company_id,
				'message' => __('Company created successfully', 'loungenie-portal'),
			)
		);
	}

	/**
	 * Update company
	 */
	public static function update_company($request)
	{
		global $wpdb;

		$id    = (int) $request->get_param('id');
		$table = $wpdb->prefix . 'lgp_companies';

		$data = array(
			'name'                  => sanitize_text_field($request->get_param('name')),
			'address'               => sanitize_textarea_field($request->get_param('address')),
			'state'                 => sanitize_text_field($request->get_param('state')),
			'contact_name'          => sanitize_text_field($request->get_param('contact_name')),
			'contact_email'         => sanitize_email($request->get_param('contact_email')),
			'contact_phone'         => sanitize_text_field($request->get_param('contact_phone')),
			'management_company_id' => absint($request->get_param('management_company_id')),
		);

		$updated = $wpdb->update($table, $data, array('id' => $id));

		if ($updated === false) {
			return new WP_Error('db_error', __('Failed to update company', 'loungenie-portal'), array('status' => 500));
		}

		// Audit logging
		$user = wp_get_current_user();
		LGP_Logger::log_event(
			$user->ID,
			'company_updated',
			$id,
			array(
				'company_name'   => $data['name'],
				'fields_updated' => array_keys($data),
			)
		);

		return rest_ensure_response(
			array(
				'message' => __('Company updated successfully', 'loungenie-portal'),
			)
		);
	}

	/**
	 * Check if user is Support
	 */
	public static function check_support_permission()
	{
		return LGP_Auth::is_support();
	}

	/**
	 * Check if user can access company
	 * Support can access all, Partners can access their own
	 */
	public static function check_company_permission($request)
	{
		if (LGP_Auth::is_support()) {
			return true;
		}

		if (LGP_Auth::is_partner()) {
			$company_id   = LGP_Auth::get_user_company_id();
			$requested_id = (int) $request->get_param('id');
			return (int) $company_id === $requested_id;
		}

		return false;
	}
}
