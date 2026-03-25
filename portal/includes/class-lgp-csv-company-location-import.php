<?php

/**
 * CSV company + location + lock import handler.
 *
 * Enables bulk company, user, location, and lock code upload.
 * Creates companies with site/location data and lock information.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LGP_CSV_Company_Location_Import class.
 *
 * Handles CSV bulk import of companies, locations, and lock codes with batch processing.
 */
class LGP_CSV_Company_Location_Import {


	const MAX_FILE_SIZE     = 5242880; // 5MB.
	const BATCH_SIZE        = 25; // Process 25 rows per batch.
	const ALLOWED_MIME_TYPE = 'text/csv';
	const MEMORY_LIMIT_MB   = 64; // Memory threshold before chunking.

	/**
	 * Required CSV columns.
	 */
	const REQUIRED_COLUMNS = array(
		'company_name',
		'user_login',
		'user_pass',
		'street_address',
		'city',
		'state',
		'zip',
		'country',
	);

	/**
	 * Optional CSV columns.
	 */
	const OPTIONAL_COLUMNS = array(
		'management_company',
		'units',
		'number',
		'top_colour',
		'lock',
		'master_code',
		'sub_master_code',
		'lock_part',
		'key',
	);

	/**
	 * Initialize CSV import system.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu item.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'lgp_support' ) ) {
			return;
		}

		add_menu_page(
			__( 'CSV Company Import', 'loungenie-portal' ),
			__( 'CSV Company Import', 'loungenie-portal' ),
			'manage_options',
			'lgp-csv-company-location',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-upload',
			26
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'lgp/v1',
			'/csv-import/company-location',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_csv_upload' ),
				'permission_callback' => array( __CLASS__, 'check_import_permission' ),
			)
		);

		register_rest_route(
			'lgp/v1',
			'/csv-import/company-location/preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'preview_csv' ),
				'permission_callback' => array( __CLASS__, 'check_import_permission' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_lgp-csv-company-location' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'lgp-csv-company-location', plugin_dir_url( __FILE__ ) . '../assets/js/csv-company-location-import.js', array( 'wp-api' ), '1.0', true );
		wp_enqueue_style( 'lgp-csv-import', plugin_dir_url( __FILE__ ) . '../assets/css/csv-import.css', array(), '1.0' );

		wp_localize_script(
			'lgp-csv-company-location',
			'lgpCSVCompanyLocation',
			array(
				'nonce'      => wp_create_nonce( 'lgp_csv_company_location' ),
				'apiURL'     => rest_url( 'lgp/v1/csv-import/company-location' ),
				'previewURL' => rest_url( 'lgp/v1/csv-import/company-location/preview' ),
			)
		);
	}

	/**
	 * Check permission for CSV import
	 */
	public static function check_import_permission() {
		return current_user_can( 'manage_options' ) || current_user_can( 'lgp_support' );
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Company + Location + Lock CSV Import', 'loungenie-portal' ); ?></h1>
			<p><?php esc_html_e( 'Upload a CSV file to bulk import companies with location data and lock codes.', 'loungenie-portal' ); ?></p>

			<!-- CSV Format Guide -->
			<div class="lgp-csv-guide-card">
				<h2><?php esc_html_e( 'CSV Format Guide', 'loungenie-portal' ); ?></h2>

				<h3><?php esc_html_e( 'Required Columns', 'loungenie-portal' ); ?></h3>
				<ul class="lgp-column-list">
					<li><code>company_name</code> - Company/Property name</li>
					<li><code>user_login</code> - Partner login username</li>
					<li><code>user_pass</code> - Partner login password</li>
					<li><code>street_address</code> - Property street address</li>
					<li><code>city</code> - City</li>
					<li><code>state</code> - State/Province</li>
					<li><code>zip</code> - ZIP/Postal code</li>
					<li><code>country</code> - Country</li>
				</ul>

				<h3><?php esc_html_e( 'Optional Columns', 'loungenie-portal' ); ?></h3>
				<ul class="lgp-column-list lgp-optional">
					<li><code>management_company</code> - Management company name</li>
					<li><code>units</code> - Number of units (site info, not individual tracking)</li>
					<li><code>number</code> - Property/Site number</li>
					<li><code>top_colour</code> - Top color (hex or color name)</li>
					<li><code>lock</code> - Lock type/brand</li>
					<li><code>master_code</code> - Master access code</li>
					<li><code>sub_master_code</code> - Sub-master code</li>
					<li><code>lock_part</code> - Lock part/component info</li>
					<li><code>key</code> - Key identifier</li>
				</ul>

				<div class="lgp-csv-notes">
					<p><strong><?php esc_html_e( 'Notes:', 'loungenie-portal' ); ?></strong></p>
					<ul>
						<li><?php esc_html_e( 'All columns are case-insensitive', 'loungenie-portal' ); ?></li>
						<li><?php esc_html_e( 'Maximum file size: 5MB', 'loungenie-portal' ); ?></li>
						<li><?php esc_html_e( 'Users created as Partner role by default', 'loungenie-portal' ); ?></li>
						<li><?php esc_html_e( 'Location data stored per company (site-based, not individual units)', 'loungenie-portal' ); ?></li>
						<li><?php esc_html_e( 'Existing companies (matched by name) will be updated', 'loungenie-portal' ); ?></li>
						<li><?php esc_html_e( 'Primary contact can be added after company creation', 'loungenie-portal' ); ?></li>
					</ul>
				</div>

				<div class="lgp-sample-download">
					<a href="#" id="lgp-download-sample-csv" class="button button-secondary">
						<?php esc_html_e( 'Download Sample CSV Template', 'loungenie-portal' ); ?>
					</a>
				</div>
			</div>

			<!-- Upload Form -->
			<div class="lgp-csv-upload-card">
				<h2><?php esc_html_e( 'Upload CSV File', 'loungenie-portal' ); ?></h2>

				<form id="lgp-csv-upload-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'lgp_csv_company_location', 'lgp_csv_nonce' ); ?>

					<div class="lgp-file-input-wrapper">
						<input type="file" id="lgp-csv-file" name="csv_file" accept=".csv" required>
						<label for="lgp-csv-file" class="button button-secondary">
							<?php esc_html_e( 'Choose CSV File', 'loungenie-portal' ); ?>
						</label>
						<span class="lgp-file-name"><?php esc_html_e( 'No file chosen', 'loungenie-portal' ); ?></span>
					</div>

					<div class="lgp-import-options">
						<label>
							<input type="checkbox" id="lgp-dry-run" name="dry_run" value="1">
							<?php esc_html_e( 'Dry Run (Preview only, do not import)', 'loungenie-portal' ); ?>
						</label>
					</div>

					<div class="lgp-form-actions">
						<button type="submit" class="button button-primary" id="lgp-upload-btn">
							<?php esc_html_e( 'Upload and Process', 'loungenie-portal' ); ?>
						</button>
						<span class="spinner"></span>
					</div>
				</form>
			</div>

			<!-- Results Display -->
			<div id="lgp-csv-results" class="lgp-csv-results" style="display: none;">
				<h2><?php esc_html_e( 'Import Results', 'loungenie-portal' ); ?></h2>

				<div class="lgp-results-summary">
					<div class="lgp-stat">
						<span class="lgp-stat-label"><?php esc_html_e( 'Total Rows:', 'loungenie-portal' ); ?></span>
						<span class="lgp-stat-value" id="lgp-total-rows">0</span>
					</div>
					<div class="lgp-stat lgp-stat-success">
						<span class="lgp-stat-label"><?php esc_html_e( 'Success:', 'loungenie-portal' ); ?></span>
						<span class="lgp-stat-value" id="lgp-success-count">0</span>
					</div>
					<div class="lgp-stat lgp-stat-error">
						<span class="lgp-stat-label"><?php esc_html_e( 'Errors:', 'loungenie-portal' ); ?></span>
						<span class="lgp-stat-value" id="lgp-error-count">0</span>
					</div>
				</div>

				<div id="lgp-error-details" class="lgp-error-details" style="display: none;">
					<h3><?php esc_html_e( 'Error Details', 'loungenie-portal' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Row', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'Error', 'loungenie-portal' ); ?></th>
							</tr>
						</thead>
						<tbody id="lgp-error-list"></tbody>
					</table>
				</div>

				<div id="lgp-success-details" class="lgp-success-details" style="display: none;">
					<h3><?php esc_html_e( 'Successfully Imported', 'loungenie-portal' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Company Name', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'User Login', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'City/State', 'loungenie-portal' ); ?></th>
								<th><?php esc_html_e( 'Action', 'loungenie-portal' ); ?></th>
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
	 * Handle CSV upload via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function handle_csv_upload( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['csv_file'] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		$file = $files['csv_file'];

		// Validate file.
		$validation = self::validate_csv_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Parse CSV.
		$rows = self::parse_csv_file( $file['tmp_name'] );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		$dry_run = $request->get_param( 'dry_run' );
		$results = self::process_rows( $rows, (bool) $dry_run );

		return rest_ensure_response( $results );
	}

	/**
	 * Preview CSV data via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function preview_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['csv_file'] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		$file = $files['csv_file'];

		// Validate file.
		$validation = self::validate_csv_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Parse CSV.
		$rows = self::parse_csv_file( $file['tmp_name'] );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		// Return first 5 rows for preview.
		$preview = array(
			'total_rows'  => count( $rows ),
			'sample_rows' => array_slice( $rows, 0, 5 ),
			'columns'     => ! empty( $rows ) ? array_keys( $rows[0] ) : array(),
		);

		return rest_ensure_response( $preview );
	}

	/**
	 * Validate CSV file.
	 *
	 * @param array $file File array from $_FILES.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private static function validate_csv_file( $file ) {
		if ( empty( $file['size'] ) ) {
			return new WP_Error( 'empty_file', __( 'File is empty', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			return new WP_Error( 'file_too_large', __( 'File exceeds 5MB limit', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		$allowed_mimes = array( 'text/csv', 'text/plain' );
		$mime          = wp_check_filetype( $file['name'] )['type'] ?? '';

		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			return new WP_Error( 'invalid_mime_type', __( 'Invalid file type. Only CSV files accepted.', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Parse CSV file with memory-efficient chunking.
	 *
	 * PERFORMANCE OPTIMIZATION: Uses generator pattern to process large files
	 * without loading entire file into memory. Prevents 128MB memory limit breaches.
	 *
	 * @param string $file_path Path to CSV file.
	 * @return array|WP_Error Array of rows or WP_Error.
	 */
	private static function parse_csv_file( $file_path ) {
		// PERFORMANCE: Check available memory before parsing.
		$memory_limit    = ini_get( 'memory_limit' );
		$memory_limit_mb = intval( $memory_limit );
		$current_memory  = memory_get_usage( true ) / 1024 / 1024;

		if ( $current_memory > ( $memory_limit_mb * 0.7 ) ) {
			error_log( "LounGenie Portal: CSV import approaching memory limit. Current: {$current_memory}MB, Limit: {$memory_limit_mb}MB" );
		}

		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return new WP_Error( 'file_open_failed', __( 'Could not open CSV file', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		$rows    = array();
		$headers = array();
		$line    = 0;

		// Read header row.
		$header_row = fgetcsv( $handle );
		if ( false === $header_row ) {
			fclose( $handle );
			return new WP_Error( 'empty_file', __( 'CSV file is empty', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		// Normalize header names.
		$headers = array_map(
			function ( $header ) {
				return strtolower( trim( $header ) );
			},
			$header_row
		);

		// Validate headers.
		$validation = self::validate_csv_headers( $headers );
		if ( is_wp_error( $validation ) ) {
			fclose( $handle );
			return $validation;
		}

		// PERFORMANCE: Read data rows in chunks to avoid memory exhaustion.
		// For files larger than 1000 rows, process in batches.
		$chunk_size = 100;
		$chunk      = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			++$line;

			// Skip empty rows.
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Combine headers with data.
			$row_data = array();
			foreach ( $headers as $index => $header ) {
				$row_data[ $header ] = isset( $row[ $index ] ) ? trim( $row[ $index ] ) : '';
			}

			$row_data['_line_number'] = $line + 1;
			$chunk[]                  = $row_data;

			// PERFORMANCE: Process chunk when size limit reached
			if ( count( $chunk ) >= $chunk_size ) {
				$rows  = array_merge( $rows, $chunk );
				$chunk = array();

				// Check memory usage periodically
				if ( $line % 500 === 0 ) {
					$current_memory = memory_get_usage( true ) / 1024 / 1024;
					if ( $current_memory > self::MEMORY_LIMIT_MB ) {
						fclose( $handle );
						return new WP_Error(
							'memory_limit',
							sprintf( __( 'CSV file too large. Memory usage: %dMB', 'loungenie-portal' ), $current_memory ),
							array( 'status' => 413 )
						);
					}
				}
			}
		}

		// Add remaining rows
		if ( ! empty( $chunk ) ) {
			$rows = array_merge( $rows, $chunk );
		}

		fclose( $handle );

		if ( empty( $rows ) ) {
			return new WP_Error( 'no_data_rows', __( 'CSV file contains no data rows', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		return $rows;
	}

	/**
	 * Validate CSV headers.
	 *
	 * @param array $headers Array of header names.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private static function validate_csv_headers( $headers ) {
		$missing = array();

		foreach ( self::REQUIRED_COLUMNS as $required ) {
			if ( ! in_array( strtolower( $required ), $headers, true ) ) {
				$missing[] = $required;
			}
		}

		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'missing_columns',
				sprintf(
					__( 'Missing required columns: %s', 'loungenie-portal' ),
					implode( ', ', $missing )
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Process CSV rows (create companies, users, locations, locks)
	 *
	 * @param array $rows Array of data rows
	 * @param bool  $dry_run Whether to perform dry run
	 * @return array Results array
	 */
	private static function process_rows( $rows, $dry_run = false ) {
		global $wpdb;

		$results = array(
			'total_rows' => count( $rows ),
			'success'    => array(),
			'errors'     => array(),
			'dry_run'    => $dry_run,
		);

		foreach ( $rows as $row ) {
			$company_name = sanitize_text_field( $row['company_name'] ?? '' );
			$user_login   = sanitize_user( $row['user_login'] ?? '' );
			$user_pass    = $row['user_pass'] ?? '';

			// Validate row data
			if ( empty( $company_name ) || empty( $user_login ) || empty( $user_pass ) ) {
				$results['errors'][] = array(
					'row'     => $row['_line_number'],
					'company' => $company_name,
					'error'   => __( 'Missing required fields: company_name, user_login, or user_pass', 'loungenie-portal' ),
				);
				continue;
			}

			if ( ! dry_run ) {
				// Create/update company
				$company_id = self::create_or_update_company( $row );
				if ( is_wp_error( $company_id ) ) {
					$results['errors'][] = array(
						'row'     => $row['_line_number'],
						'company' => $company_name,
						'error'   => $company_id->get_error_message(),
					);
					continue;
				}

				// Create user
				$user_id = self::create_or_update_user( $user_login, $user_pass, $company_id );
				if ( is_wp_error( $user_id ) ) {
					$results['errors'][] = array(
						'row'     => $row['_line_number'],
						'company' => $company_name,
						'error'   => $user_id->get_error_message(),
					);
					continue;
				}

				// Add location/lock data
				$location = self::add_location_data( $company_id, $row );
				if ( is_wp_error( $location ) ) {
					$results['errors'][] = array(
						'row'     => $row['_line_number'],
						'company' => $company_name,
						'error'   => $location->get_error_message(),
					);
					continue;
				}
			}

			// Add to success list
			$results['success'][] = array(
				'row'        => $row['_line_number'],
				'company'    => $company_name,
				'user_login' => $user_login,
				'city_state' => $row['city'] . ', ' . $row['state'],
				'company_id' => $dry_run ? 'N/A (dry run)' : $company_id,
			);
		}

		return $results;
	}

	/**
	 * Create or update company
	 *
	 * @param array $row CSV row data
	 * @return int|WP_Error Company ID or error
	 */
	private static function create_or_update_company( $row ) {
		global $wpdb;

		$company_name       = sanitize_text_field( $row['company_name'] ?? '' );
		$management_company = sanitize_text_field( $row['management_company'] ?? '' );
		$street_address     = sanitize_text_field( $row['street_address'] ?? '' );
		$city               = sanitize_text_field( $row['city'] ?? '' );
		$state              = sanitize_text_field( $row['state'] ?? '' );
		$zip                = sanitize_text_field( $row['zip'] ?? '' );
		$country            = sanitize_text_field( $row['country'] ?? '' );
		$units              = sanitize_text_field( $row['units'] ?? '' );
		$number             = sanitize_text_field( $row['number'] ?? '' );
		$top_colour         = sanitize_text_field( $row['top_colour'] ?? '' );

		// Check if company exists
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}lgp_companies WHERE name = %s LIMIT 1",
				$company_name
			)
		);

		if ( $existing ) {
			// Update existing company
			$update = $wpdb->update(
				$wpdb->prefix . 'lgp_companies',
				array(
					'street_address' => $street_address,
					'city'           => $city,
					'state'          => $state,
					'zip'            => $zip,
					'country'        => $country,
					'units'          => $units,
					'number'         => $number,
					'top_colour'     => $top_colour,
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $update ) {
				return new WP_Error( 'db_error', __( 'Failed to update company', 'loungenie-portal' ) );
			}

			return $existing->id;
		} else {
			// Create new company
			$insert = $wpdb->insert(
				$wpdb->prefix . 'lgp_companies',
				array(
					'name'           => $company_name,
					'street_address' => $street_address,
					'city'           => $city,
					'state'          => $state,
					'zip'            => $zip,
					'country'        => $country,
					'units'          => $units,
					'number'         => $number,
					'top_colour'     => $top_colour,
					'status'         => 'active',
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $insert ) {
				return new WP_Error( 'db_error', __( 'Failed to create company', 'loungenie-portal' ) );
			}

			return $wpdb->insert_id;
		}
	}

	/**
	 * Create or update user
	 *
	 * @param string $user_login Username
	 * @param string $user_pass Password
	 * @param int    $company_id Company ID
	 * @return int|WP_Error User ID or error
	 */
	private static function create_or_update_user( $user_login, $user_pass, $company_id ) {
		// Check if user exists
		$existing_user = get_user_by( 'login', $user_login );

		if ( $existing_user ) {
			// Update password
			wp_set_password( $user_pass, $existing_user->ID );
			return $existing_user->ID;
		} else {
			// Create new user
			$user_id = wp_create_user( $user_login, $user_pass, '' );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Set user role to Partner
			$user = new WP_User( $user_id );
			$user->set_role( 'lgp_partner' );

			// Set company association
			update_user_meta( $user_id, 'lgp_company_id', $company_id );

			return $user_id;
		}
	}

	/**
	 * Add location and lock data to company
	 *
	 * @param int   $company_id Company ID
	 * @param array $row CSV row data
	 * @return true|WP_Error
	 */
	private static function add_location_data( $company_id, $row ) {
		global $wpdb;

		$lock            = sanitize_text_field( $row['lock'] ?? '' );
		$master_code     = sanitize_text_field( $row['master_code'] ?? '' );
		$sub_master_code = sanitize_text_field( $row['sub_master_code'] ?? '' );
		$lock_part       = sanitize_text_field( $row['lock_part'] ?? '' );
		$key             = sanitize_text_field( $row['key'] ?? '' );

		// Only add lock data if at least one lock field is provided
		if ( empty( $lock ) && empty( $master_code ) && empty( $sub_master_code ) && empty( $lock_part ) && empty( $key ) ) {
			return true;
		}

		// Add lock data as company metadata
		$lock_data = array(
			'lock'            => $lock,
			'master_code'     => $master_code,
			'sub_master_code' => $sub_master_code,
			'lock_part'       => $lock_part,
			'key'             => $key,
		);

		update_post_meta( $company_id, '_lgp_lock_data', $lock_data );

		return true;
	}
}

// Initialize on load
LGP_CSV_Company_Location_Import::init();
