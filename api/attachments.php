<?php

/**
 * Ticket Attachments REST API Endpoints
 * Handles secure file uploads and downloads for tickets
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

class LGP_Attachments_API
{



	const MAX_FILE_SIZE = 10485760; // 10MB
	const ALLOWED_TYPES = array('image/jpeg', 'image/png', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	const UPLOAD_DIR    = 'lgp-attachments';

	/**
	 * Initialize API endpoints
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register REST API routes
	 */
	public static function register_routes()
	{
		// Upload attachment to ticket
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<ticket_id>\d+)/attachments',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'upload_attachment'),
				'permission_callback' => array(__CLASS__, 'check_ticket_access'),
			)
		);

		// Get ticket attachments
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<ticket_id>\d+)/attachments',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_attachments'),
				'permission_callback' => array(__CLASS__, 'check_ticket_access'),
			)
		);

		// Delete attachment
		register_rest_route(
			'lgp/v1',
			'/attachments/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array(__CLASS__, 'delete_attachment'),
				'permission_callback' => array(__CLASS__, 'check_attachment_permission'),
			)
		);

		// Download attachment
		register_rest_route(
			'lgp/v1',
			'/attachments/(?P<id>\d+)/download',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'download_attachment'),
				'permission_callback' => array(__CLASS__, 'check_attachment_permission'),
			)
		);
	}

	/**
	 * Check upload rate limit (max 10 per hour per user)
	 */
	private static function check_upload_rate_limit($user_id)
	{
		$cache_key = 'lgp_upload_count_' . (int) $user_id;
		$count     = (int) get_transient($cache_key);

		if ($count >= 10) {
			return false;
		}

		// Increment count (transient auto-expires after 1 hour)
		set_transient($cache_key, $count + 1, HOUR_IN_SECONDS);
		return true;
	}

	/**
	 * Check if user has access to ticket
	 */
	public static function check_ticket_access($request)
	{
		$ticket_id = $request->get_param('ticket_id');
		global $wpdb;

		if (! $ticket_id) {
			return false;
		}

		$current_user_id = get_current_user_id();
		if (! $current_user_id) {
			return false;
		}

		// Support can access all tickets
		if (LGP_Auth::is_support()) {
			return true;
		}

		// Partners can only access their company's tickets
		$tickets_table  = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';
		$company_id     = LGP_Auth::get_user_company_id();

		$ticket_company = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT sr.company_id FROM $tickets_table t 
            LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
            WHERE t.id = %d",
				$ticket_id
			)
		);

		// Compare company IDs as integers to avoid type mismatch
		return (int) $ticket_company === (int) $company_id;
	}

	/**
	 * Check if user can access attachment
	 */
	public static function check_attachment_permission($request)
	{
		$id = (int) $request->get_param('id');
		global $wpdb;

		if (! $id) {
			return false;
		}

		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$ticket_id         = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ticket_id FROM $attachments_table WHERE id = %d",
				$id
			)
		);

		if (! $ticket_id) {
			return false;
		}

		// Check if user can access the ticket
		$dummy_request = new WP_REST_Request('GET');
		$dummy_request->set_param('ticket_id', $ticket_id);
		return self::check_ticket_access($dummy_request);
	}

	/**
	 * Upload attachment to ticket
	 */
	public static function upload_attachment($request)
	{
		// Verify nonce
		$nonce = $request->get_header('X-WP-Nonce');
		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error('invalid_nonce', __('Nonce verification failed', 'loungenie-portal'), array('status' => 403));
		}

		$ticket_id       = (int) $request->get_param('ticket_id');
		$current_user_id = get_current_user_id();

		// Rate limiting: max 10 attachments per hour per user.
		if (! self::check_upload_rate_limit($current_user_id)) {
			return new WP_Error('rate_limit_exceeded', 'Too many uploads. Maximum 10 per hour.', array('status' => 429));
		}

		// Validate ticket exists
		global $wpdb;
		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$ticket        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $tickets_table WHERE id = %d",
				$ticket_id
			)
		);

		if (! $ticket) {
			return new WP_Error('ticket_not_found', 'Ticket not found', array('status' => 404));
		}

		// Get uploaded files
		$files = $request->get_file_params();
		if (empty($files)) {
			return new WP_Error('no_files', 'No files uploaded', array('status' => 400));
		}

		// Enforce max files per request (5 files).
		if (count($files) > LGP_File_Validator::MAX_FILES_PER_UPLOAD) {
			return new WP_Error('too_many_files', sprintf('Maximum %d files per upload', LGP_File_Validator::MAX_FILES_PER_UPLOAD), array('status' => 400));
		}

		$uploaded = array();

		foreach ($files as $file_key => $file) {
			// Validate file with LGP_File_Validator (handles size, MIME, filename).
			if (! class_exists('LGP_File_Validator')) {
				require_once LGP_PLUGIN_DIR . 'includes/class-lgp-file-validator.php';
			}

			$validation = LGP_File_Validator::validate($file);
			if (! $validation['valid']) {
				$uploaded[] = array(
					'success' => false,
					'message' => implode('; ', $validation['errors']),
				);
				continue;
			}

			// Handle file upload
			$result = self::handle_file_upload($file, $ticket_id, $current_user_id);
			if (is_wp_error($result)) {
				$uploaded[] = array(
					'success' => false,
					'message' => $result->get_error_message(),
				);
			} else {
				$uploaded[] = array(
					'success'       => true,
					'attachment_id' => $result['id'],
					'message'       => 'File uploaded successfully',
				);

				// Log attachment upload
				if (class_exists('LGP_Logger')) {
					LGP_Logger::log_event(
						$current_user_id,
						'attachment_uploaded',
						null,
						array(
							'ticket_id' => $ticket_id,
							'file_name' => $result['file_name'],
							'file_type' => $result['file_type'],
							'file_size' => $result['file_size'],
						)
					);
				}
			}
		}

		return rest_ensure_response($uploaded);
	}

	/**
	 * Handle file upload to protected directory
	 */
	private static function handle_file_upload($file, $ticket_id, $user_id)
	{
		// Create secure upload directory
		$upload_base = wp_upload_dir();
		$upload_path = $upload_base['basedir'] . '/' . self::UPLOAD_DIR . '/' . $ticket_id;

		if (! file_exists($upload_path)) {
			wp_mkdir_p($upload_path);
		}

		// Create .htaccess to prevent direct access
		$htaccess_file = $upload_path . '/.htaccess';
		if (! file_exists($htaccess_file)) {
			$htaccess_content = "deny from all\n";
			file_put_contents($htaccess_file, $htaccess_content);
		}

		// Generate unique filename
		$original_name = sanitize_file_name($file['name']);
		$ext           = pathinfo($original_name, PATHINFO_EXTENSION);
		$unique_name   = md5(uniqid($original_name, true)) . '.' . $ext;
		$file_path     = $upload_path . '/' . $unique_name;

		// Move uploaded file
		if (! move_uploaded_file($file['tmp_name'], $file_path)) {
			return new WP_Error('upload_failed', 'Failed to upload file');
		}

		// Store attachment record in database
		global $wpdb;
		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$relative_path     = self::UPLOAD_DIR . '/' . $ticket_id . '/' . $unique_name;

		$wpdb->insert(
			$attachments_table,
			array(
				'ticket_id'   => $ticket_id,
				'file_name'   => $original_name,
				'file_type'   => $file['type'],
				'file_size'   => $file['size'],
				'file_path'   => $relative_path,
				'uploaded_by' => $user_id,
				'created_at'  => current_time('mysql', true),
			),
			array('%d', '%s', '%s', '%d', '%s', '%d', '%s')
		);

		return array(
			'id'        => $wpdb->insert_id,
			'file_name' => $original_name,
			'file_type' => $file['type'],
			'file_size' => $file['size'],
			'file_path' => $relative_path,
		);
	}

	/**
	 * Get ticket attachments
	 */
	public static function get_attachments($request)
	{
		$ticket_id = (int) $request->get_param('ticket_id');
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$attachments       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $attachments_table WHERE ticket_id = %d ORDER BY created_at DESC",
				$ticket_id
			)
		);

		$result = array();
		foreach ($attachments as $attachment) {
			$result[] = array(
				'id'          => (int) $attachment->id,
				'ticket_id'   => (int) $attachment->ticket_id,
				'file_name'   => $attachment->file_name,
				'file_type'   => $attachment->file_type,
				'file_size'   => (int) $attachment->file_size,
				'uploaded_by' => (int) $attachment->uploaded_by,
				'created_at'  => $attachment->created_at,
			);
		}

		return rest_ensure_response($result);
	}

	/**
	 * Delete attachment
	 */
	public static function delete_attachment($request)
	{
		// Verify nonce
		$nonce = $request->get_header('X-WP-Nonce');
		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error('invalid_nonce', __('Nonce verification failed', 'loungenie-portal'), array('status' => 403));
		}

		$id              = (int) $request->get_param('id');
		$current_user_id = get_current_user_id();
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$attachment        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $attachments_table WHERE id = %d",
				$id
			)
		);

		if (! $attachment) {
			return new WP_Error('attachment_not_found', 'Attachment not found', array('status' => 404));
		}

		// Only support and uploader can delete
		$is_support  = LGP_Auth::is_support();
		$is_uploader = $attachment->uploaded_by == $current_user_id;

		if (! $is_support && ! $is_uploader) {
			return new WP_Error('forbidden', 'You cannot delete this attachment', array('status' => 403));
		}

		// Delete physical file
		$upload_base = wp_upload_dir();
		$file_path   = $upload_base['basedir'] . '/' . $attachment->file_path;
		if (file_exists($file_path)) {
			unlink($file_path);
		}

		// Delete database record
		$wpdb->delete(
			$attachments_table,
			array('id' => $id),
			array('%d')
		);

		// Log deletion
		if (class_exists('LGP_Logger')) {
			LGP_Logger::log_event(
				$current_user_id,
				'attachment_deleted',
				null,
				array(
					'attachment_id' => $id,
					'ticket_id'     => $attachment->ticket_id,
					'file_name'     => $attachment->file_name,
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Attachment deleted',
			)
		);
	}

	/**
	 * Download attachment
	 */
	public static function download_attachment($request)
	{
		$id = (int) $request->get_param('id');
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$attachment        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $attachments_table WHERE id = %d",
				$id
			)
		);

		if (! $attachment) {
			return new WP_Error('attachment_not_found', 'Attachment not found', array('status' => 404));
		}

		$upload_base = wp_upload_dir();
		$file_path   = $upload_base['basedir'] . '/' . $attachment->file_path;

		if (! file_exists($file_path)) {
			return new WP_Error('file_not_found', 'File not found on server', array('status' => 404));
		}

		// Log download
		$current_user_id = get_current_user_id();
		if (class_exists('LGP_Logger')) {
			LGP_Logger::log_event(
				$current_user_id,
				'attachment_downloaded',
				null,
				array(
					'attachment_id' => $id,
					'ticket_id'     => $attachment->ticket_id,
					'file_name'     => $attachment->file_name,
				)
			);
		}

		// Return file for download with safe headers
		$mime      = in_array($attachment->file_type, self::ALLOWED_TYPES, true) ? $attachment->file_type : 'application/octet-stream';
		$safe_name = sanitize_file_name($attachment->file_name);
		header('Content-Type: ' . $mime);
		header('Content-Disposition: attachment; filename="' . $safe_name . '"');
		header('X-Content-Type-Options: nosniff');
		header('Content-Length: ' . (int) $attachment->file_size);
		readfile($file_path);
		exit;
	}
}

// Initialize
LGP_Attachments_API::init();
