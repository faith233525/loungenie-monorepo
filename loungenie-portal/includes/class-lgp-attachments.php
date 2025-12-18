<?php
/**
 * LounGenie Portal - Attachments Handler
 * Manages file uploads for tickets and email attachments
 *
 * @package LounGenie Portal
 * @version 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Attachments {

	/**
	 * Initialize attachment handler
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_lgp_upload_attachment', array( __CLASS__, 'handle_attachment_upload' ) );
		add_action( 'wp_ajax_nopriv_lgp_upload_attachment', array( __CLASS__, 'handle_attachment_upload' ) );
	}

	/**
	 * Enqueue JavaScript for attachment handling
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script(
			'lgp-attachments',
			LGP_PLUGIN_URL . 'assets/js/attachments.js',
			array( 'jquery' ),
			LGP_VERSION,
			true
		);

		wp_localize_script(
			'lgp-attachments',
			'lgpAttachments',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'lgp_attachment_nonce' ),
				'maxFileSize' => self::get_max_file_size(),
				'allowedTypes' => self::get_allowed_file_types(),
			)
		);
	}

	/**
	 * Handle file upload via AJAX
	 */
	public static function handle_attachment_upload() {
		check_ajax_referer( 'lgp_attachment_nonce' );

		if ( ! isset( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file provided', 'loungenie-portal' ) ) );
		}

		$file = $_FILES['file'];

		// Validate file
		$validation = self::validate_file( $file );
		if ( ! $validation['valid'] ) {
			wp_send_json_error( array( 'message' => $validation['error'] ) );
		}

		// Handle file upload
		$upload_result = self::upload_file( $file );
		if ( ! $upload_result['success'] ) {
			wp_send_json_error( array( 'message' => $upload_result['error'] ) );
		}

		wp_send_json_success(
			array(
				'file_id'   => $upload_result['file_id'],
				'file_name' => $file['name'],
				'file_size' => $file['size'],
				'file_url'  => $upload_result['url'],
			)
		);
	}

	/**
	 * Validate uploaded file
	 *
	 * @param array $file File array from $_FILES
	 * @return array Validation result
	 */
	private static function validate_file( $file ) {
		$allowed_types = self::get_allowed_file_types();
		$max_file_size = self::get_max_file_size();

		// Check file size
		if ( $file['size'] > $max_file_size ) {
			return array(
				'valid' => false,
				'error' => sprintf(
					__( 'File size exceeds maximum of %s MB', 'loungenie-portal' ),
					$max_file_size / ( 1024 * 1024 )
				),
			);
		}

		// Check file type
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_ext, $allowed_types, true ) ) {
			return array(
				'valid' => false,
				'error' => sprintf(
					__( 'File type not allowed. Allowed types: %s', 'loungenie-portal' ),
					implode( ', ', $allowed_types )
				),
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Upload file to server
	 *
	 * @param array $file File array from $_FILES
	 * @return array Upload result
	 */
	private static function upload_file( $file ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/wp-handle-upload.php';

		$upload_overrides = array( 'test_form' => false );
		$movefile         = wp_handle_upload( $file, $upload_overrides );

		if ( isset( $movefile['error'] ) ) {
			return array(
				'success' => false,
				'error'   => $movefile['error'],
			);
		}

		// Save attachment to database
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $file['name'],
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_mime_type' => $movefile['type'],
				'guid'           => $movefile['url'],
			),
			$movefile['file']
		);

		if ( is_wp_error( $attachment_id ) ) {
			return array(
				'success' => false,
				'error'   => $attachment_id->get_error_message(),
			);
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $movefile['file'] );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return array(
			'success'  => true,
			'file_id'  => $attachment_id,
			'url'      => $movefile['url'],
			'file_name' => $file['name'],
		);
	}

	/**
	 * Get maximum file size
	 *
	 * @return int Maximum file size in bytes
	 */
	private static function get_max_file_size() {
		// 10 MB default
		return 10 * 1024 * 1024;
	}

	/**
	 * Get allowed file types
	 *
	 * @return array List of allowed file extensions
	 */
	private static function get_allowed_file_types() {
		return array(
			'pdf',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'txt',
			'csv',
			'jpg',
			'jpeg',
			'png',
			'gif',
			'zip',
		);
	}

	/**
	 * Get attachment info
	 *
	 * @param int $attachment_id Attachment ID
	 * @return array Attachment details
	 */
	public static function get_attachment_info( $attachment_id ) {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment ) {
			return false;
		}

		return array(
			'id'    => $attachment_id,
			'name'  => $attachment->post_title,
			'url'   => wp_get_attachment_url( $attachment_id ),
			'size'  => filesize( get_attached_file( $attachment_id ) ),
			'type'  => $attachment->post_mime_type,
			'date'  => $attachment->post_date,
		);
	}

	/**
	 * Link attachments to ticket
	 *
	 * @param int   $ticket_id Ticket ID
	 * @param array $attachment_ids Array of attachment IDs
	 * @return bool Success
	 */
	public static function link_attachments_to_ticket( $ticket_id, $attachment_ids ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_ticket_attachments';

		foreach ( $attachment_ids as $attachment_id ) {
			$wpdb->insert(
				$table,
				array(
					'ticket_id'     => $ticket_id,
					'attachment_id' => $attachment_id,
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
		}

		return true;
	}

	/**
	 * Get ticket attachments
	 *
	 * @param int $ticket_id Ticket ID
	 * @return array List of attachments
	 */
	public static function get_ticket_attachments( $ticket_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_ticket_attachments';

		$attachment_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT attachment_id FROM $table WHERE ticket_id = %d ORDER BY created_at DESC",
				$ticket_id
			)
		);

		$attachments = array();
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_info = self::get_attachment_info( $attachment_id );
			if ( $attachment_info ) {
				$attachments[] = $attachment_info;
			}
		}

		return $attachments;
	}
}

// Initialize
LGP_Attachments::init();
