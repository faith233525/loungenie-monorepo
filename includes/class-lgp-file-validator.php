<?php
/**
 * File Upload Validator
 * Hard limits and MIME validation for shared hosting safety
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_File_Validator {

	/**
	 * Initialize file validator and schedule cleanup cron
	 */
	public static function init() {
		// Schedule daily cleanup of expired attachments
		if ( ! wp_next_scheduled( 'lgp_cleanup_expired_attachments' ) ) {
			wp_schedule_event( time(), 'daily', 'lgp_cleanup_expired_attachments' );
		}
		add_action( 'lgp_cleanup_expired_attachments', array( __CLASS__, 'cleanup_expired_files' ) );
	}

	const MAX_FILE_SIZE        = 10485760; // 10MB
	const MAX_FILES_PER_UPLOAD = 5;
	const ALLOWED_MIMES        = array(
		'image/jpeg'         => 'jpg|jpeg',
		'image/png'          => 'png',
		'application/pdf'    => 'pdf',
		'text/plain'         => 'txt',
		'application/msword' => 'doc',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
	);
	const UPLOAD_DIR           = 'lgp-attachments';
	const RETENTION_DAYS       = 90;

	/**
	 * Validate a file before upload
	 *
	 * @param array $file $_FILES entry
	 * @return array ['valid' => bool, 'errors' => []]
	 */
	public static function validate( $file ) {
		$errors = array();

		// Check file exists
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$errors[] = __( 'No file uploaded', 'loungenie-portal' );
			return array(
				'valid'  => false,
				'errors' => $errors,
			);
		}

		// Check size
		$size = filesize( $file['tmp_name'] );
		if ( $size > self::MAX_FILE_SIZE ) {
			$errors[] = sprintf(
				__( 'File exceeds maximum size of %s MB', 'loungenie-portal' ),
				self::MAX_FILE_SIZE / 1048576
			);
		}

		// Check MIME type
		$mime = mime_content_type( $file['tmp_name'] );
		if ( ! isset( self::ALLOWED_MIMES[ $mime ] ) ) {
			$errors[] = sprintf(
				__( 'File type "%s" not allowed', 'loungenie-portal' ),
				esc_html( $mime )
			);
		}

		// Check filename (prevent directory traversal)
		$filename = basename( $file['name'] );
		if ( strpos( $filename, '..' ) !== false || strpos( $filename, '/' ) !== false ) {
			$errors[] = __( 'Invalid filename', 'loungenie-portal' );
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
			'mime'   => $mime,
			'size'   => $size,
		);
	}

	/**
	 * Generate safe filename with randomization
	 *
	 * @param string $original_name Original filename
	 * @param string $mime_type     MIME type
	 * @return string
	 */
	public static function generate_safe_filename( $original_name, $mime_type ) {
		// Get extension from MIME type
		$extension = 'bin';
		foreach ( self::ALLOWED_MIMES as $mime => $exts ) {
			if ( $mime === $mime_type ) {
				$extension = explode( '|', $exts )[0];
				break;
			}
		}

		// Generate random filename
		$random    = bin2hex( random_bytes( 16 ) );
		$sanitized = sanitize_file_name( $original_name );
		$sanitized = preg_replace( '/[^a-zA-Z0-9_-]/', '', $sanitized );

		// Combine: random + sanitized + ext
		return "{$random}_{$sanitized}.{$extension}";
	}

	/**
	 * Get upload directory path
	 *
	 * @return string
	 */
	public static function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		$lgp_dir    = $upload_dir['basedir'] . '/' . self::UPLOAD_DIR;

		// Create if needed
		if ( ! is_dir( $lgp_dir ) ) {
			wp_mkdir_p( $lgp_dir );
		}

		return $lgp_dir;
	}

	/**
	 * Check file retention and delete expired files
	 * Prevents disk exhaustion on shared hosting
	 */
	public static function cleanup_expired_files() {
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_attachments';
		$cutoff_date       = gmdate( 'Y-m-d H:i:s', strtotime( '-' . self::RETENTION_DAYS . ' days' ) );

		// Find expired attachments
		$expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, file_path FROM {$attachments_table} 
                WHERE created_at < %s AND is_deleted = 0",
				$cutoff_date
			)
		);

		foreach ( (array) $expired as $attachment ) {
			// Soft delete
			$wpdb->update(
				$attachments_table,
				array( 'is_deleted' => 1 ),
				array( 'id' => $attachment->id )
			);

			// Delete file if it exists
			if ( file_exists( $attachment->file_path ) ) {
				@unlink( $attachment->file_path );
			}

			LGP_Logger::log_event(
				0,
				'attachment_expired',
				0,
				array( 'attachment_id' => $attachment->id )
			);
		}
	}

	/**
	 * Get file stats for admin monitoring
	 *
	 * @return array
	 */
	public static function get_stats() {
		$upload_dir = self::get_upload_dir();
		$total_size = 0;
		$file_count = 0;

		foreach ( glob( $upload_dir . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				$total_size += filesize( $file );
				++$file_count;
			}
		}

		return array(
			'total_size_mb' => round( $total_size / 1048576, 2 ),
			'file_count'    => $file_count,
			'max_size_mb'   => round( self::MAX_FILE_SIZE / 1048576, 2 ),
		);
	}
}
