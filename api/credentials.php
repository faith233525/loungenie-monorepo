<?php

/**
 * Partner Credentials REST API
 *
 * Manages partner company credentials including usernames, passwords, and
 * contact information. This is a security-sensitive API restricted to Support
 * staff only. All passwords are hashed using WordPress secure password hashing.
 * 
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Credentials API Handler
 *
 * Provides secure REST API endpoints for managing partner company credentials.
 * Implements strict access control, password hashing, and audit logging.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Credentials_API
{

    /**
     * Initialize API endpoints.
     *
     * Registers REST API initialization hook for credential management
     * endpoints. All endpoints are restricted to Support users only.
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
     * Registers all credential management endpoints:
     * - POST /lgp/v1/company/{id}/credentials - Update company credentials
     * - GET /lgp/v1/company/{id}/credentials - Get company credentials (masked)
     * - GET /lgp/v1/companies/credentials - List all companies with credential status
     * - GET /lgp/v1/generate-password - Generate secure random password
     *
     * All endpoints require Support role. State-changing operations include
     * CSRF protection via nonce validation.
     *
     * @since 2.0.0
     * @return void
     * @see register_rest_route()
     */
    public static function register_routes()
    {
        // Update company credentials
        register_rest_route(
            'lgp/v1',
            '/company/(?P<id>\d+)/credentials',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'update_company_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission_with_nonce'),
                // Security: State-changing operation requires CSRF protection
            )
        );

        // Get company credentials (for Support verification)
        register_rest_route(
            'lgp/v1',
            '/company/(?P<id>\d+)/credentials',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'get_company_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
                // Security: Read-only operation, no nonce required
            )
        );

        // List all companies with credential status
        register_rest_route(
            'lgp/v1',
            '/companies/credentials',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'list_companies_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
            )
        );

        // Generate random password
        register_rest_route(
            'lgp/v1',
            '/generate-password',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'generate_password'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
            )
        );
    }

    /**
     * Check if user is Support.
     *
     * Validates both authentication and authorization for credential access.
     * Only Support users or administrators can manage credentials.
     *
     * @since 2.0.0
     * @return bool True if user is Support or admin, false otherwise.
     */
    public static function check_support_permission()
    {
        if (! is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array('lgp_support', (array) $user->roles, true) || current_user_can('manage_options');
    }

    /**
     * Check Support permission with CSRF protection.
     *
     * Enhanced permission check for credential update operations.
     * Validates Support role and WordPress REST API nonce to prevent
     * CSRF attacks on security-sensitive credential operations.
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
     * Update company credentials.
     *
     * Updates or creates credentials for a partner company including username,
     * password, and contact information. Passwords are securely hashed using
     * WordPress bcrypt-based hashing. Validates uniqueness of usernames and
     * required contact information.
     *
     * Process:
     * 1. Validates required fields (username, password, primary contact)
     * 2. Verifies company exists
     * 3. Checks for duplicate username
     * 4. Hashes password securely
     * 5. Updates company record
     * 6. Logs credential update event
     *
     * @since 2.0.0
     * @param WP_REST_Request $request REST API request object containing:
     *                                 - id (int) Required. Company ID from URL parameter.
     *                                 - partner_username (string) Required. Unique username.
     *                                 - partner_password (string) Required. Password (will be hashed).
     *                                 - primary_contact_name (string) Required. Primary contact name.
     *                                 - primary_contact_email (string) Required. Primary contact email.
     *                                 - primary_contact_phone (string) Optional. Primary contact phone.
     *                                 - secondary_contact_name (string) Optional. Secondary contact name.
     *                                 - secondary_contact_email (string) Optional. Secondary contact email.
     *                                 - secondary_contact_phone (string) Optional. Secondary contact phone.
     * @return WP_REST_Response|WP_Error Response with success message and username,
     *                                   WP_Error if validation fails or company not found.
     */
    public static function update_company_credentials($request)
    {
        global $wpdb;

        // Verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', __('Nonce verification failed', 'loungenie-portal'), array('status' => 403));
        }

        $company_id = (int) $request->get_param('id');
        $username   = sanitize_user($request->get_param('partner_username'));
        // Security: Passwords should be sanitized but not overly stripped
        // sanitize_text_field removes line breaks and some chars, which is appropriate for passwords
        $password   = sanitize_text_field($request->get_param('partner_password'));

        // Validate required fields
        if (empty($username) || empty($password)) {
            return new WP_Error(
                'missing_fields',
                __('Username and password are required', 'loungenie-portal'),
                array('status' => 400)
            );
        }

        // Validate contact information
        $primary_name = sanitize_text_field($request->get_param('primary_contact_name'));
        $primary_email = sanitize_email($request->get_param('primary_contact_email'));

        if (empty($primary_name) || empty($primary_email)) {
            return new WP_Error(
                'missing_contact',
                __('Primary contact name and email are required', 'loungenie-portal'),
                array('status' => 400)
            );
        }

        $table = $wpdb->prefix . 'lgp_companies';

        // Verify company exists
        $company = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM $table WHERE id = %d", $company_id)
        );

        if (! $company) {
            return new WP_Error(
                'company_not_found',
                __('Company not found', 'loungenie-portal'),
                array('status' => 404)
            );
        }

        // Check for duplicate username (excluding current company)
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE partner_username = %s AND id != %d",
                $username,
                $company_id
            )
        );

        if ($existing) {
            return new WP_Error(
                'username_exists',
                __('This username is already in use by another company', 'loungenie-portal'),
                array('status' => 400)
            );
        }

        // Security: Hash password using WordPress secure password hashing (bcrypt-based)
        $hashed_password = wp_hash_password($password);

        // Prepare data
        $data = array(
            'partner_username'        => $username,
            'partner_password'        => $hashed_password,
            'primary_contact_name'    => $primary_name,
            'primary_contact_email'   => $primary_email,
            'primary_contact_phone'   => sanitize_text_field($request->get_param('primary_contact_phone')),
            'secondary_contact_name'  => sanitize_text_field($request->get_param('secondary_contact_name')),
            'secondary_contact_email' => sanitize_email($request->get_param('secondary_contact_email')),
            'secondary_contact_phone' => sanitize_text_field($request->get_param('secondary_contact_phone')),
            'updated_at'              => current_time('mysql'),
        );

        // Update company
        $updated = $wpdb->update(
            $table,
            $data,
            array('id' => $company_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($updated === false) {
            return new WP_Error(
                'update_failed',
                __('Failed to update credentials', 'loungenie-portal'),
                array('status' => 500)
            );
        }

        // Log the action
        do_action('lgp_credentials_updated', $company_id, $username);

        return rest_ensure_response(
            array(
                'success'    => true,
                'message'    => __('Credentials updated successfully', 'loungenie-portal'),
                'company_id' => $company_id,
                'username'   => $username,
            )
        );
    }

    /**
     * Get company credentials (password excluded).
     *
     * Retrieves credential information for a specific company. Passwords are
     * never returned - only username and contact information are included.
     * Used for credential verification and management by Support staff.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request REST API request object containing:
     *                                 - id (int) Required. Company ID from URL parameter.
     * @return WP_REST_Response|WP_Error Company credential object (password excluded),
     *                                   WP_Error if company not found.
     */
    public static function get_company_credentials($request)
    {
        global $wpdb;

        $company_id = (int) $request->get_param('id');
        $table = $wpdb->prefix . 'lgp_companies';

        $company = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
					id, 
					name, 
					partner_username,
					primary_contact_name,
					primary_contact_email,
					primary_contact_phone,
					secondary_contact_name,
					secondary_contact_email,
					secondary_contact_phone
				FROM $table WHERE id = %d",
                $company_id
            )
        );

        if (! $company) {
            return new WP_Error(
                'not_found',
                __('Company not found', 'loungenie-portal'),
                array('status' => 404)
            );
        }

        return rest_ensure_response($company);
    }

    /**
     * List all companies with credential status.
     *
     * Returns paginated list of all companies with their credential
     * configuration status. Useful for Support dashboard to identify
     * which companies need credential setup.
     *
     * Status values:
     * - 'configured': Company has username configured
     * - 'pending': Company lacks credentials
     *
     * @since 2.0.0
     * @param WP_REST_Request $request REST API request object containing:
     *                                 - page (int) Optional. Page number. Default 1.
     *                                 - per_page (int) Optional. Results per page. Default 20.
     * @return WP_REST_Response Response containing:
     *                          - companies (array) List of company objects with credential_status.
     *                          - total (int) Total number of companies.
     *                          - page (int) Current page number.
     *                          - per_page (int) Items per page.
     *                          - configured (int) Count of companies with credentials.
     *                          - pending (int) Count of companies without credentials.
     */
    public static function list_companies_credentials($request)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';
        $page = intval($request->get_param('page')) ?: 1;
        $per_page = intval($request->get_param('per_page')) ?: 20;
        $offset = ($page - 1) * $per_page;

        $companies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
					id,
					name,
					partner_username,
					primary_contact_name,
					primary_contact_email,
					CASE 
						WHEN partner_username IS NOT NULL AND partner_username != '' THEN 'configured'
						ELSE 'pending'
					END as credential_status
				FROM $table
				ORDER BY name ASC
				LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        return rest_ensure_response(
            array(
                'companies'  => $companies,
                'total'      => $total,
                'page'       => $page,
                'per_page'   => $per_page,
                'configured' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE partner_username IS NOT NULL AND partner_username != ''"),
                'pending'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE partner_username IS NULL OR partner_username = ''"),
            )
        );
    }

    /**
     * Generate secure random password.
     *
     * Generates a cryptographically secure random password suitable for
     * partner credentials. Uses WordPress password generation with special
     * characters for enhanced security.
     *
     * @since 2.0.0
     * @return WP_REST_Response Response containing generated 16-character password
     *                          with special characters.
     * @see wp_generate_password()
     */
    public static function generate_password()
    {
        return rest_ensure_response(
            array(
                'password' => wp_generate_password(16, true, true),
            )
        );
    }
}

// Initialize
add_action('plugins_loaded', array('LGP_Credentials_API', 'init'));
