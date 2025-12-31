<?php

/**
 * Admin Page for Partner Credentials Management
 * Allows Support team to configure partner login credentials and contacts
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
    exit;
}

class LGP_Admin_Credentials_UI
{

    /**
     * Initialize admin UI
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_ajax_lgp_get_companies', array(__CLASS__, 'ajax_get_companies'));
        add_action('wp_ajax_lgp_save_credentials', array(__CLASS__, 'ajax_save_credentials'));
    }

    /**
     * Add admin menu page
     */
    public static function add_menu_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            __('Partner Credentials', 'loungenie-portal'),
            __('Partner Credentials', 'loungenie-portal'),
            'manage_options',
            'lgp-credentials-manager',
            array(__CLASS__, 'render_page'),
            'dashicons-admin-users',
            25
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook_suffix)
    {
        if ('toplevel_page_lgp-credentials-manager' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'lgp-credentials-admin',
            LGP_ASSETS_URL . 'css/admin-credentials.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'lgp-credentials-admin',
            LGP_ASSETS_URL . 'js/admin-credentials.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'lgp-credentials-admin',
            'lgpCredentials',
            array(
                'nonce' => wp_create_nonce('lgp_credentials_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
            )
        );
    }

    /**
     * Render admin page
     */
    public static function render_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'loungenie-portal'));
        }
?>
        <div class="wrap">
            <h1><?php esc_html_e('Partner Company Credentials Management', 'loungenie-portal'); ?></h1>
            <p><?php esc_html_e('Configure login credentials and contact information for each partner company. Each company gets one unique username and password.', 'loungenie-portal'); ?></p>

            <div id="lgp-credentials-container" class="lgp-credentials-container">
                <div class="lgp-loading">
                    <p><?php esc_html_e('Loading companies...', 'loungenie-portal'); ?></p>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * AJAX handler to get all companies
     */
    public static function ajax_get_companies()
    {
        check_ajax_referer('lgp_credentials_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'loungenie-portal'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lgp_companies';

        $companies = $wpdb->get_results(
            "SELECT 
				id, 
				name, 
				partner_username, 
				primary_contact_name, 
				primary_contact_email,
				primary_contact_phone,
				secondary_contact_name,
				secondary_contact_email,
				secondary_contact_phone,
				CASE WHEN partner_username IS NOT NULL AND partner_username != '' THEN 'configured' ELSE 'pending' END as status
			FROM $table 
			ORDER BY name ASC"
        );

        wp_send_json_success($companies);
    }

    /**
     * AJAX handler to save credentials
     */
    public static function ajax_save_credentials()
    {
        check_ajax_referer('lgp_credentials_nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'loungenie-portal'));
        }

        global $wpdb;

        $company_id = intval($_POST['company_id'] ?? 0);
        $username = sanitize_user($_POST['partner_username'] ?? '');
        $password = $_POST['partner_password'] ?? '';
        $primary_name = sanitize_text_field($_POST['primary_contact_name'] ?? '');
        $primary_email = sanitize_email($_POST['primary_contact_email'] ?? '');
        $primary_phone = sanitize_text_field($_POST['primary_contact_phone'] ?? '');
        $secondary_name = sanitize_text_field($_POST['secondary_contact_name'] ?? '');
        $secondary_email = sanitize_email($_POST['secondary_contact_email'] ?? '');
        $secondary_phone = sanitize_text_field($_POST['secondary_contact_phone'] ?? '');

        // Validation
        if (! $company_id || ! $username || ! $password || ! $primary_name || ! $primary_email) {
            wp_send_json_error(__('All required fields must be filled', 'loungenie-portal'));
        }

        $table = $wpdb->prefix . 'lgp_companies';

        // Check if company exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE id = %d", $company_id)
        );

        if (! $exists) {
            wp_send_json_error(__('Company not found', 'loungenie-portal'));
        }

        // Check if username is already taken by another company
        $username_taken = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE partner_username = %s AND id != %d",
                $username,
                $company_id
            )
        );

        if ($username_taken) {
            wp_send_json_error(__('This username is already assigned to another company', 'loungenie-portal'));
        }

        // Hash password
        $hashed_password = wp_hash_password($password);

        // Update company
        $updated = $wpdb->update(
            $table,
            array(
                'partner_username' => $username,
                'partner_password' => $hashed_password,
                'primary_contact_name' => $primary_name,
                'primary_contact_email' => $primary_email,
                'primary_contact_phone' => $primary_phone,
                'secondary_contact_name' => $secondary_name,
                'secondary_contact_email' => $secondary_email,
                'secondary_contact_phone' => $secondary_phone,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $company_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($updated === false) {
            wp_send_json_error(__('Failed to update credentials', 'loungenie-portal'));
        }

        // Log action
        do_action('lgp_partner_credentials_updated', $company_id, $username);

        wp_send_json_success(array(
            'message' => __('Credentials updated successfully!', 'loungenie-portal'),
            'company_id' => $company_id,
        ));
    }
}

// Initialize
add_action('plugins_loaded', array('LGP_Admin_Credentials_UI', 'init'));
