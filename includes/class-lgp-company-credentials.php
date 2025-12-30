<?php

/**
 * Company Credentials Manager
 * Allows Support team to create/update partner usernames and passwords
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
    exit;
}

class LGP_Company_Credentials
{

    /**
     * Initialize credentials management
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }

    /**
     * Add admin menu item for credentials management
     */
    public static function add_admin_menu()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'options-general.php',
            __('Partner Credentials', 'loungenie-portal'),
            __('Partner Credentials', 'loungenie-portal'),
            'manage_options',
            'lgp-partner-credentials',
            array(__CLASS__, 'render_credentials_page')
        );
    }

    /**
     * Register REST API routes for credential management
     */
    public static function register_rest_routes()
    {
        // Update company credentials
        register_rest_route(
            'lgp/v1',
            '/company/(?P<id>\d+)/credentials',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'update_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
            )
        );

        // Get company credentials (Support only, for verification)
        register_rest_route(
            'lgp/v1',
            '/company/(?P<id>\d+)/credentials',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'get_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
            )
        );

        // Get all companies with credentials status
        register_rest_route(
            'lgp/v1',
            '/companies/credentials',
            array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'get_companies_with_credentials'),
                'permission_callback' => array(__CLASS__, 'check_support_permission'),
            )
        );
    }

    /**
     * Check if user has Support permission
     */
    public static function check_support_permission()
    {
        if (! is_user_logged_in()) {
            return false;
        }

        $current_user = wp_get_current_user();
        return in_array('lgp_support', (array) $current_user->roles, true) || current_user_can('manage_options');
    }

    /**
     * Update company credentials
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_credentials($request)
    {
        global $wpdb;

        $company_id = (int) $request->get_param('id');
        $username   = sanitize_user($request->get_param('partner_username'));
        $password   = $request->get_param('partner_password');
        $primary    = array(
            'name'  => sanitize_text_field($request->get_param('primary_contact_name')),
            'email' => sanitize_email($request->get_param('primary_contact_email')),
            'phone' => sanitize_text_field($request->get_param('primary_contact_phone')),
        );
        $secondary  = array(
            'name'  => sanitize_text_field($request->get_param('secondary_contact_name')),
            'email' => sanitize_email($request->get_param('secondary_contact_email')),
            'phone' => sanitize_text_field($request->get_param('secondary_contact_phone')),
        );

        // Validate inputs
        if (empty($username)) {
            return new WP_Error('missing_username', __('Partner username is required', 'loungenie-portal'), array('status' => 400));
        }

        if (empty($password)) {
            return new WP_Error('missing_password', __('Partner password is required', 'loungenie-portal'), array('status' => 400));
        }

        if (empty($primary['name']) || empty($primary['email'])) {
            return new WP_Error('missing_primary', __('Primary contact name and email are required', 'loungenie-portal'), array('status' => 400));
        }

        // Check if company exists
        $table = $wpdb->prefix . 'lgp_companies';
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE id = %d",
                $company_id
            )
        );

        if (! $exists) {
            return new WP_Error('not_found', __('Company not found', 'loungenie-portal'), array('status' => 404));
        }

        // Check if username is already taken by another company
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE partner_username = %s AND id != %d",
                $username,
                $company_id
            )
        );

        if ($existing) {
            return new WP_Error('username_taken', __('This username is already assigned to another company', 'loungenie-portal'), array('status' => 400));
        }

        // Hash password with WordPress hashing
        $hashed_password = wp_hash_password($password);

        // Update company with credentials
        $updated = $wpdb->update(
            $table,
            array(
                'partner_username'        => $username,
                'partner_password'        => $hashed_password,
                'primary_contact_name'    => $primary['name'],
                'primary_contact_email'   => $primary['email'],
                'primary_contact_phone'   => $primary['phone'],
                'secondary_contact_name'  => $secondary['name'],
                'secondary_contact_email' => $secondary['email'],
                'secondary_contact_phone' => $secondary['phone'],
                'updated_at'              => current_time('mysql'),
            ),
            array('id' => $company_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($updated === false) {
            return new WP_Error('db_error', __('Failed to update credentials', 'loungenie-portal'), array('status' => 500));
        }

        // Log credential update
        do_action('lgp_company_credentials_updated', $company_id, $username);

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __('Credentials updated successfully', 'loungenie-portal'),
                'company_id' => $company_id,
                'username' => $username,
            )
        );
    }

    /**
     * Get company credentials (masked password for verification)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_credentials($request)
    {
        global $wpdb;

        $company_id = (int) $request->get_param('id');
        $table = $wpdb->prefix . 'lgp_companies';

        $company = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, partner_username, primary_contact_name, primary_contact_email, primary_contact_phone, secondary_contact_name, secondary_contact_email, secondary_contact_phone FROM $table WHERE id = %d",
                $company_id
            )
        );

        if (! $company) {
            return new WP_Error('not_found', __('Company not found', 'loungenie-portal'), array('status' => 404));
        }

        return rest_ensure_response($company);
    }

    /**
     * Get all companies with credentials status
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function get_companies_with_credentials($request)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $offset = ($page - 1) * $per_page;

        $companies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, partner_username, primary_contact_name, primary_contact_email, 
				        CASE WHEN partner_username IS NOT NULL AND partner_username != '' THEN 'configured' ELSE 'pending' END as credential_status
				 FROM $table 
				 ORDER BY name ASC 
				 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        return rest_ensure_response(
            array(
                'companies' => $companies,
                'total' => (int) $total,
                'page' => (int) $page,
                'per_page' => (int) $per_page,
            )
        );
    }

    /**
     * Verify partner credentials (used by partner login)
     *
     * @param string $username
     * @param string $password
     * @return int|false Company ID if valid, false otherwise
     */
    public static function verify_partner_credentials($username, $password)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';
        $company = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, partner_password FROM $table WHERE partner_username = %s LIMIT 1",
                $username
            )
        );

        if (! $company) {
            return false;
        }

        // Verify password hash
        if (wp_check_password($password, $company->partner_password)) {
            return (int) $company->id;
        }

        return false;
    }

    /**
     * Render admin page for managing credentials
     */
    public static function render_credentials_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'loungenie-portal'));
        }
?>
        <div class="wrap">
            <h1><?php esc_html_e('Partner Company Credentials', 'loungenie-portal'); ?></h1>
            <p><?php esc_html_e('Configure partner login credentials and contact information for each company.', 'loungenie-portal'); ?></p>

            <div id="lgp-credentials-app"></div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const app = document.getElementById('lgp-credentials-app');
                    app.innerHTML = '<p><?php esc_html_e('Loading...', 'loungenie-portal'); ?></p>';

                    fetch('/wp-json/lgp/v1/companies/credentials')
                        .then(response => response.json())
                        .then(data => {
                            let html = '<table class="widefat striped"><thead><tr>';
                            html += '<th><?php esc_attr_e('Company Name', 'loungenie-portal'); ?></th>';
                            html += '<th><?php esc_attr_e('Username', 'loungenie-portal'); ?></th>';
                            html += '<th><?php esc_attr_e('Primary Contact', 'loungenie-portal'); ?></th>';
                            html += '<th><?php esc_attr_e('Status', 'loungenie-portal'); ?></th>';
                            html += '<th><?php esc_attr_e('Action', 'loungenie-portal'); ?></th>';
                            html += '</tr></thead><tbody>';

                            data.companies.forEach(company => {
                                const status = company.credential_status === 'configured' ?
                                    '<span style="color: green;">✓ Configured</span>' :
                                    '<span style="color: orange;">⚠ Pending</span>';
                                html += '<tr>';
                                html += '<td>' + company.name + '</td>';
                                html += '<td>' + (company.partner_username || '—') + '</td>';
                                html += '<td>' + (company.primary_contact_name || '—') + '</td>';
                                html += '<td>' + status + '</td>';
                                html += '<td><button onclick="editCredentials(' + company.id + ')" class="button"><?php esc_attr_e('Edit', 'loungenie-portal'); ?></button></td>';
                                html += '</tr>';
                            });

                            html += '</tbody></table>';
                            app.innerHTML = html;
                        });
                });

                function editCredentials(companyId) {
                    const form = prompt('Enter new partner username:', '');
                    if (!form) return;

                    const password = prompt('Enter partner password:', '');
                    if (!password) return;

                    const primaryName = prompt('Enter primary contact name:', '');
                    if (!primaryName) return;

                    const primaryEmail = prompt('Enter primary contact email:', '');
                    if (!primaryEmail) return;

                    fetch('/wp-json/lgp/v1/company/' + companyId + '/credentials', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>'
                            },
                            body: JSON.stringify({
                                partner_username: form,
                                partner_password: password,
                                primary_contact_name: primaryName,
                                primary_contact_email: primaryEmail,
                                primary_contact_phone: prompt('Enter primary contact phone:', ''),
                                secondary_contact_name: prompt('Enter secondary contact name (optional):', ''),
                                secondary_contact_email: prompt('Enter secondary contact email (optional):', ''),
                                secondary_contact_phone: prompt('Enter secondary contact phone (optional):', '')
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('<?php esc_attr_e('Credentials updated successfully!', 'loungenie-portal'); ?>');
                                location.reload();
                            } else {
                                alert(data.message || '<?php esc_attr_e('Error updating credentials', 'loungenie-portal'); ?>');
                            }
                        });
                }
            </script>
        </div>
<?php
    }

    /**
     * Register settings (placeholder for future enhancements)
     */
    public static function register_settings()
    {
        // Settings can be registered here if needed
    }
}

// Initialize on WordPress load
add_action('plugins_loaded', array('LGP_Company_Credentials', 'init'));
