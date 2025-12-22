<?php

/**
 * CSV Partner Import Handler
 * Enables bulk partner company upload for Admin and Support roles
 * WordPress.org compliant: No shell exec, shared hosting safe
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
    exit;
}

class LGP_CSV_Partner_Import
{

    const MAX_FILE_SIZE     = 2097152; // 2MB in bytes
    const BATCH_SIZE        = 50; // Process 50 rows per batch for shared hosting
    const ALLOWED_MIME_TYPE = 'text/csv';

    /**
     * Required CSV columns (case-insensitive)
     */
    const REQUIRED_COLUMNS = array(
        'company_name',
        'company_email',
        'status',
        'primary_contact_name',
        'primary_contact_title',
        'primary_contact_email',
        'primary_contact_phone',
    );

    /**
     * Optional CSV columns (case-insensitive)
     */
    const OPTIONAL_COLUMNS = array(
        'secondary_contact_name',
        'secondary_contact_title',
        'secondary_contact_email',
        'secondary_contact_phone',
    );

    /**
     * Initialize CSV import system
     */
    public static function init()
    {
        // Admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));

        // Register REST API endpoints
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));

        // Enqueue assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Add admin menu item
     */
    public static function add_admin_menu()
    {
        // Check permission: Admin OR Support role
        if (! current_user_can('manage_options') && ! current_user_can('lgp_support')) {
            return;
        }

        add_submenu_page(
            'loungenie-portal',
            __('CSV Partner Import', 'loungenie-portal'),
            __('CSV Import', 'loungenie-portal'),
            'lgp_manage_companies', // Use existing capability
            'lgp-csv-import',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Register REST API routes
     */
    public static function register_routes()
    {
        // CSV upload endpoint
        register_rest_route(
            'lgp/v1',
            '/csv-import/partners',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'handle_csv_upload'),
                'permission_callback' => array(__CLASS__, 'check_import_permission'),
            )
        );

        // Dry-run preview endpoint
        register_rest_route(
            'lgp/v1',
            '/csv-import/preview',
            array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'preview_csv'),
                'permission_callback' => array(__CLASS__, 'check_import_permission'),
            )
        );
    }

    /**
     * Check if user has permission to import
     *
     * @return bool
     */
    public static function check_import_permission()
    {
        return current_user_can('manage_options') || current_user_can('lgp_manage_companies');
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_assets($hook)
    {
        if ('loungenie-portal_page_lgp-csv-import' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'lgp-csv-import',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/csv-import.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'lgp-csv-import',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/csv-import.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'lgp-csv-import',
            'lgpCsvImport',
            array(
                'ajaxUrl'      => rest_url('lgp/v1/csv-import/'),
                'nonce'        => wp_create_nonce('wp_rest'),
                'maxFileSize'  => self::MAX_FILE_SIZE,
                'isAdmin'      => current_user_can('manage_options'),
                'isSupport'    => current_user_can('lgp_support'),
                'translations' => array(
                    'uploading'       => __('Uploading...', 'loungenie-portal'),
                    'processing'      => __('Processing...', 'loungenie-portal'),
                    'validating'      => __('Validating...', 'loungenie-portal'),
                    'complete'        => __('Complete!', 'loungenie-portal'),
                    'error'           => __('Error', 'loungenie-portal'),
                    'fileTooLarge'    => __('File size exceeds 2MB limit', 'loungenie-portal'),
                    'invalidFileType' => __('Only CSV files are allowed', 'loungenie-portal'),
                ),
            )
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page()
    {
        if (! self::check_import_permission()) {
            wp_die(esc_html__('You do not have permission to access this page.', 'loungenie-portal'));
        }

        $is_admin   = current_user_can('manage_options');
        $is_support = current_user_can('lgp_support');

?>
        <div class="wrap lgp-csv-import-page">
            <h1><?php esc_html_e('CSV Partner Import', 'loungenie-portal'); ?></h1>

            <div class="lgp-csv-import-intro">
                <p><?php esc_html_e('Upload a CSV file to bulk import or update partner companies.', 'loungenie-portal'); ?></p>

                <?php if ($is_support && ! $is_admin) : ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e('As a Support user, you can import partner companies but cannot modify system settings or roles.', 'loungenie-portal'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CSV Format Requirements -->
            <div class="lgp-csv-format-card">
                <h2><?php esc_html_e('CSV Format Requirements', 'loungenie-portal'); ?></h2>

                <h3><?php esc_html_e('Required Columns', 'loungenie-portal'); ?></h3>
                <ul class="lgp-column-list">
                    <li><code>company_name</code> - Company name</li>
                    <li><code>company_email</code> - Company email address</li>
                    <li><code>status</code> - active or inactive</li>
                    <li><code>primary_contact_name</code> - Primary contact full name</li>
                    <li><code>primary_contact_title</code> - Primary contact job title</li>
                    <li><code>primary_contact_email</code> - Primary contact email</li>
                    <li><code>primary_contact_phone</code> - Primary contact phone number</li>
                </ul>

                <h3><?php esc_html_e('Optional Columns', 'loungenie-portal'); ?></h3>
                <ul class="lgp-column-list lgp-optional">
                    <li><code>secondary_contact_name</code> - Secondary contact full name</li>
                    <li><code>secondary_contact_title</code> - Secondary contact job title</li>
                    <li><code>secondary_contact_email</code> - Secondary contact email</li>
                    <li><code>secondary_contact_phone</code> - Secondary contact phone number</li>
                </ul>

                <div class="lgp-csv-notes">
                    <p><strong><?php esc_html_e('Notes:', 'loungenie-portal'); ?></strong></p>
                    <ul>
                        <li><?php esc_html_e('Column names are case-insensitive', 'loungenie-portal'); ?></li>
                        <li><?php esc_html_e('Maximum file size: 2MB', 'loungenie-portal'); ?></li>
                        <li><?php esc_html_e('Secondary contact fields are optional', 'loungenie-portal'); ?></li>
                        <li><?php esc_html_e('Existing companies (matched by email) will be updated', 'loungenie-portal'); ?></li>
                    </ul>
                </div>

                <!-- Sample CSV Download -->
                <div class="lgp-sample-download">
                    <a href="#" id="lgp-download-sample-csv" class="button button-secondary">
                        <?php esc_html_e('Download Sample CSV Template', 'loungenie-portal'); ?>
                    </a>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="lgp-csv-upload-card">
                <h2><?php esc_html_e('Upload CSV File', 'loungenie-portal'); ?></h2>

                <form id="lgp-csv-upload-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('lgp_csv_import', 'lgp_csv_nonce'); ?>

                    <div class="lgp-file-input-wrapper">
                        <input type="file" id="lgp-csv-file" name="csv_file" accept=".csv" required>
                        <label for="lgp-csv-file" class="button button-secondary">
                            <?php esc_html_e('Choose CSV File', 'loungenie-portal'); ?>
                        </label>
                        <span class="lgp-file-name"><?php esc_html_e('No file chosen', 'loungenie-portal'); ?></span>
                    </div>

                    <div class="lgp-import-options">
                        <label>
                            <input type="checkbox" id="lgp-dry-run" name="dry_run" value="1">
                            <?php esc_html_e('Dry Run (Preview only, do not import)', 'loungenie-portal'); ?>
                        </label>
                    </div>

                    <div class="lgp-form-actions">
                        <button type="submit" class="button button-primary" id="lgp-upload-btn">
                            <?php esc_html_e('Upload and Process', 'loungenie-portal'); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>

            <!-- Results Display -->
            <div id="lgp-csv-results" class="lgp-csv-results" style="display: none;">
                <h2><?php esc_html_e('Import Results', 'loungenie-portal'); ?></h2>

                <div class="lgp-results-summary">
                    <div class="lgp-stat">
                        <span class="lgp-stat-label"><?php esc_html_e('Total Rows:', 'loungenie-portal'); ?></span>
                        <span class="lgp-stat-value" id="lgp-total-rows">0</span>
                    </div>
                    <div class="lgp-stat lgp-stat-success">
                        <span class="lgp-stat-label"><?php esc_html_e('Success:', 'loungenie-portal'); ?></span>
                        <span class="lgp-stat-value" id="lgp-success-count">0</span>
                    </div>
                    <div class="lgp-stat lgp-stat-error">
                        <span class="lgp-stat-label"><?php esc_html_e('Errors:', 'loungenie-portal'); ?></span>
                        <span class="lgp-stat-value" id="lgp-error-count">0</span>
                    </div>
                </div>

                <div id="lgp-error-details" class="lgp-error-details" style="display: none;">
                    <h3><?php esc_html_e('Error Details', 'loungenie-portal'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Row', 'loungenie-portal'); ?></th>
                                <th><?php esc_html_e('Company', 'loungenie-portal'); ?></th>
                                <th><?php esc_html_e('Error', 'loungenie-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="lgp-error-list"></tbody>
                    </table>
                </div>

                <div id="lgp-success-details" class="lgp-success-details" style="display: none;">
                    <h3><?php esc_html_e('Successfully Imported', 'loungenie-portal'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Company Name', 'loungenie-portal'); ?></th>
                                <th><?php esc_html_e('Email', 'loungenie-portal'); ?></th>
                                <th><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
                                <th><?php esc_html_e('Primary Contact', 'loungenie-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="lgp-success-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Handle CSV upload via REST API
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function handle_csv_upload($request)
    {
        // Verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response(
                array('error' => __('Invalid security token', 'loungenie-portal')),
                403
            );
        }

        // Get uploaded file
        $files = $request->get_file_params();
        if (empty($files['csv_file'])) {
            return new WP_REST_Response(
                array('error' => __('No file uploaded', 'loungenie-portal')),
                400
            );
        }

        $file = $files['csv_file'];

        // Validate file
        $validation = self::validate_uploaded_file($file);
        if (is_wp_error($validation)) {
            return new WP_REST_Response(
                array('error' => $validation->get_error_message()),
                400
            );
        }

        // Parse CSV
        $csv_data = self::parse_csv_file($file['tmp_name']);
        if (is_wp_error($csv_data)) {
            return new WP_REST_Response(
                array('error' => $csv_data->get_error_message()),
                400
            );
        }

        // Check if dry run
        $dry_run = ! empty($request->get_param('dry_run'));

        // Process rows
        $results = self::process_csv_data($csv_data, $dry_run);

        return new WP_REST_Response($results, 200);
    }

    /**
     * Validate uploaded file
     *
     * @param array $file Uploaded file array
     * @return true|WP_Error
     */
    private static function validate_uploaded_file($file)
    {
        // Check for upload errors
        if (! empty($file['error'])) {
            return new WP_Error('upload_error', __('File upload failed', 'loungenie-portal'));
        }

        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    /* translators: %s: maximum file size */
                    __('File size exceeds %s limit', 'loungenie-portal'),
                    size_format(self::MAX_FILE_SIZE)
                )
            );
        }

        // Check file type
        $file_type = wp_check_filetype($file['name']);
        if ('csv' !== $file_type['ext']) {
            return new WP_Error('invalid_file_type', __('Only CSV files are allowed', 'loungenie-portal'));
        }

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = array('text/csv', 'text/plain', 'application/csv');
        if (! in_array($mime, $allowed_mimes, true)) {
            return new WP_Error('invalid_mime_type', __('Invalid file type', 'loungenie-portal'));
        }

        return true;
    }

    /**
     * Parse CSV file
     *
     * @param string $file_path Path to CSV file
     * @return array|WP_Error Array of rows or WP_Error
     */
    private static function parse_csv_file($file_path)
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
        $handle = fopen($file_path, 'r');
        if (false === $handle) {
            return new WP_Error('file_open_failed', __('Could not open CSV file', 'loungenie-portal'));
        }

        $rows    = array();
        $headers = array();
        $line    = 0;

        // Read header row
        $header_row = fgetcsv($handle);
        if (false === $header_row) {
            fclose($handle);
            return new WP_Error('empty_file', __('CSV file is empty', 'loungenie-portal'));
        }

        // Normalize header names (lowercase, trim)
        $headers = array_map(
            function ($header) {
                return strtolower(trim($header));
            },
            $header_row
        );

        // Validate required columns
        $validation = self::validate_csv_headers($headers);
        if (is_wp_error($validation)) {
            fclose($handle);
            return $validation;
        }

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Combine headers with row data
            $row_data = array();
            foreach ($headers as $index => $header) {
                $row_data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            $row_data['_line_number'] = $line + 1; // +1 for header row
            $rows[]                   = $row_data;
        }

        fclose($handle);

        if (empty($rows)) {
            return new WP_Error('no_data_rows', __('CSV file contains no data rows', 'loungenie-portal'));
        }

        return $rows;
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers Array of header names
     * @return true|WP_Error
     */
    private static function validate_csv_headers($headers)
    {
        $missing = array();

        foreach (self::REQUIRED_COLUMNS as $required) {
            if (! in_array(strtolower($required), $headers, true)) {
                $missing[] = $required;
            }
        }

        if (! empty($missing)) {
            return new WP_Error(
                'missing_columns',
                sprintf(
                    /* translators: %s: comma-separated list of missing columns */
                    __('Missing required columns: %s', 'loungenie-portal'),
                    implode(', ', $missing)
                )
            );
        }

        return true;
    }

    /**
     * Process CSV data rows
     *
     * @param array $rows     CSV rows
     * @param bool  $dry_run  Preview only, don't save
     * @return array Processing results
     */
    private static function process_csv_data($rows, $dry_run = false)
    {
        global $wpdb;

        $results = array(
            'total'    => count($rows),
            'success'  => 0,
            'errors'   => 0,
            'dry_run'  => $dry_run,
            'imported' => array(),
            'failed'   => array(),
        );

        $table = $wpdb->prefix . 'lgp_companies';

        foreach ($rows as $row) {
            $line_number = $row['_line_number'];

            // Validate row
            $validation = self::validate_row($row);
            if (is_wp_error($validation)) {
                $results['errors']++;
                $results['failed'][] = array(
                    'line'    => $line_number,
                    'company' => $row['company_name'],
                    'error'   => $validation->get_error_message(),
                );
                continue;
            }

            // If dry run, just validate and continue
            if ($dry_run) {
                $results['success']++;
                $results['imported'][] = array(
                    'name'            => $row['company_name'],
                    'email'           => $row['company_email'],
                    'status'          => $row['status'],
                    'primary_contact' => $row['primary_contact_name'],
                    'action'          => 'would_import',
                );
                continue;
            }

            // Check if company exists (by email)
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE contact_email = %s LIMIT 1",
                    sanitize_email($row['company_email'])
                )
            );

            // Prepare company data
            $company_data = array(
                'name'          => sanitize_text_field($row['company_name']),
                'contact_email' => sanitize_email($row['company_email']),
                'contact_name'  => sanitize_text_field($row['primary_contact_name']),
                'contact_phone' => sanitize_text_field($row['primary_contact_phone']),
                'updated_at'    => current_time('mysql'),
            );

            // Add status if valid
            $status = strtolower(trim($row['status']));
            if (in_array($status, array('active', 'inactive'), true)) {
                $company_data['primary_contract_status'] = $status;
            }

            if ($existing) {
                // Update existing company
                $updated = $wpdb->update(
                    $table,
                    $company_data,
                    array('id' => $existing->id),
                    array('%s', '%s', '%s', '%s', '%s', '%s'),
                    array('%d')
                );

                if (false === $updated) {
                    $results['errors']++;
                    $results['failed'][] = array(
                        'line'    => $line_number,
                        'company' => $row['company_name'],
                        'error'   => __('Database update failed', 'loungenie-portal'),
                    );
                    continue;
                }

                $results['success']++;
                $results['imported'][] = array(
                    'name'            => $row['company_name'],
                    'email'           => $row['company_email'],
                    'status'          => $status,
                    'primary_contact' => $row['primary_contact_name'],
                    'action'          => 'updated',
                );
            } else {
                // Create new company
                $company_data['created_at'] = current_time('mysql');

                $inserted = $wpdb->insert(
                    $table,
                    $company_data,
                    array('%s', '%s', '%s', '%s', '%s', '%s')
                );

                if (false === $inserted) {
                    $results['errors']++;
                    $results['failed'][] = array(
                        'line'    => $line_number,
                        'company' => $row['company_name'],
                        'error'   => __('Database insert failed', 'loungenie-portal'),
                    );
                    continue;
                }

                $results['success']++;
                $results['imported'][] = array(
                    'name'            => $row['company_name'],
                    'email'           => $row['company_email'],
                    'status'          => $status,
                    'primary_contact' => $row['primary_contact_name'],
                    'action'          => 'created',
                );
            }

            // Log the import action
            if (class_exists('LGP_Logger')) {
                LGP_Logger::log_event(
                    get_current_user_id(),
                    'csv_partner_import',
                    $existing ? $existing->id : $wpdb->insert_id,
                    array(
                        'company_name' => $row['company_name'],
                        'action'       => $existing ? 'updated' : 'created',
                        'dry_run'      => $dry_run,
                    )
                );
            }
        }

        return $results;
    }

    /**
     * Validate single CSV row
     *
     * @param array $row Row data
     * @return true|WP_Error
     */
    private static function validate_row($row)
    {
        // Required fields validation
        if (empty($row['company_name'])) {
            return new WP_Error('missing_company_name', __('Company name is required', 'loungenie-portal'));
        }

        if (empty($row['company_email'])) {
            return new WP_Error('missing_company_email', __('Company email is required', 'loungenie-portal'));
        }

        if (! is_email($row['company_email'])) {
            return new WP_Error('invalid_email', __('Invalid company email format', 'loungenie-portal'));
        }

        if (empty($row['status'])) {
            return new WP_Error('missing_status', __('Status is required', 'loungenie-portal'));
        }

        $status = strtolower(trim($row['status']));
        if (! in_array($status, array('active', 'inactive'), true)) {
            return new WP_Error('invalid_status', __('Status must be "active" or "inactive"', 'loungenie-portal'));
        }

        // Primary contact validation
        if (empty($row['primary_contact_name'])) {
            return new WP_Error('missing_primary_name', __('Primary contact name is required', 'loungenie-portal'));
        }

        if (empty($row['primary_contact_title'])) {
            return new WP_Error('missing_primary_title', __('Primary contact title is required', 'loungenie-portal'));
        }

        if (empty($row['primary_contact_email'])) {
            return new WP_Error('missing_primary_email', __('Primary contact email is required', 'loungenie-portal'));
        }

        if (! is_email($row['primary_contact_email'])) {
            return new WP_Error('invalid_primary_email', __('Invalid primary contact email format', 'loungenie-portal'));
        }

        if (empty($row['primary_contact_phone'])) {
            return new WP_Error('missing_primary_phone', __('Primary contact phone is required', 'loungenie-portal'));
        }

        // Secondary contact validation (optional, but if present must be complete)
        $has_secondary = ! empty($row['secondary_contact_name'])
            || ! empty($row['secondary_contact_email'])
            || ! empty($row['secondary_contact_title'])
            || ! empty($row['secondary_contact_phone']);

        if ($has_secondary) {
            // If admin can override, allow partial secondary contact
            $is_admin = current_user_can('manage_options');

            if (! $is_admin) {
                // Support users: require complete secondary contact if any field is present
                if (empty($row['secondary_contact_email']) || ! is_email($row['secondary_contact_email'])) {
                    return new WP_Error(
                        'incomplete_secondary',
                        __('If secondary contact is provided, all fields must be complete', 'loungenie-portal')
                    );
                }
            }
        }

        return true;
    }

    /**
     * Generate sample CSV template
     *
     * @return string CSV content
     */
    public static function generate_sample_csv()
    {
        $headers = array_merge(self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS);

        $sample_rows = array(
            array(
                'Acme Corporation',
                'contact@acme.com',
                'active',
                'John Smith',
                'Operations Manager',
                'john.smith@acme.com',
                '555-0100',
                'Jane Doe',
                'Assistant Manager',
                'jane.doe@acme.com',
                '555-0101',
            ),
            array(
                'Tech Solutions Inc',
                'info@techsolutions.com',
                'active',
                'Mike Johnson',
                'Director',
                'mike.j@techsolutions.com',
                '555-0200',
                '',
                '',
                '',
                '',
            ),
        );

        ob_start();
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($sample_rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        return ob_get_clean();
    }
}
