<?php
/**
 * LounGenie Portal - Attachment Handler
 * Manages file attachments with company-specific folders, chunked parsing, and security
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Attachment_Handler {

	const MAX_FILE_SIZE              = 10 * 1024 * 1024; // 10MB per file
	const MAX_ATTACHMENTS_PER_TICKET = 5;
	const CHUNK_SIZE                 = 1024 * 1024; // 1MB chunks for parsing
	const ALLOWED_MIME_TYPES         = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'text/plain',
		'text/csv',
	);

	/**
	 * Initialize attachment handler
	 */
	public static function init() {
		// Create base attachment directory with security
		self::ensure_attachment_base_exists();

		// Add .htaccess to prevent direct access to certain files
		self::add_security_files();
	}

	/**
	 * Ensure base attachment directory exists with proper protection
	 */
	public static function ensure_attachment_base_exists() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/lgp-attachments';

		// Create directory
		if ( ! is_dir( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
		}

		// Ensure index.php exists to prevent directory listing
		if ( ! file_exists( $base_dir . '/index.php' ) ) {
			file_put_contents(
				$base_dir . '/index.php',
				"<?php // LounGenie attachment storage\n"
			);
		}
	}

	/**
	 * Add security files (.htaccess, index.php)
	 */
	private static function add_security_files() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/lgp-attachments';

		// Add .htaccess to prevent script execution
		$htaccess_content = <<<'HTACCESS'
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

<FilesMatch "\.exe$">
    Deny from all
</FilesMatch>

AddType application/octet-stream .pdf .doc .docx .xls .xlsx .txt .csv .jpg .png .gif

# Prevent access to sensitive files
<FilesMatch "^(config|wp-config|\.env)">
    Deny from all
</FilesMatch>
HTACCESS;

		if ( ! file_exists( $base_dir . '/.htaccess' ) ) {
			file_put_contents( $base_dir . '/.htaccess', $htaccess_content );
		}
	}

	/**
	 * Get company-specific attachment directory
	 *
	 * @param int|string $company_id Company ID or domain
	 * @return string Company attachment directory path
	 */
	public static function get_company_directory( $company_id ) {
		global $wpdb;

		// Get company domain
		if ( is_numeric( $company_id ) ) {
			$company = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, contact_email FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
					$company_id
				)
			);

			if ( $company && $company->contact_email ) {
				$domain   = substr( strrchr( $company->contact_email, '@' ), 1 );
				$domain   = str_replace( '.', '-', $domain ); // poolsafe.com -> poolsafe-com
				$dir_name = sanitize_file_name( $domain );
			} else {
				$dir_name = sanitize_file_name( 'company-' . $company_id );
			}
		} else {
			$dir_name = sanitize_file_name( $company_id );
		}

		$upload_dir  = wp_upload_dir();
		$company_dir = $upload_dir['basedir'] . '/lgp-attachments/' . $dir_name;

		// Create directory if it doesn't exist
		if ( ! is_dir( $company_dir ) ) {
			wp_mkdir_p( $company_dir );
			// Add index.php to prevent listing
			file_put_contents(
				$company_dir . '/index.php',
				"<?php // LounGenie attachment storage\n"
			);
		}

		return $company_dir;
	}

	/**
	 * Save attachment with chunked reading (memory-safe)
	 *
	 * @param string $file_path Temporary file path
	 * @param string $filename Original filename
	 * @param int    $ticket_id Ticket ID
	 * @param int    $company_id Company ID
	 * @param int    $uploaded_by User ID who uploaded
	 * @return array|false Attachment metadata or false on failure
	 */
	public static function save_attachment( $file_path, $filename, $ticket_id, $company_id, $uploaded_by = 0 ) {
		// Validate file
		$validation = self::validate_file( $file_path, $filename );
		if ( ! $validation['valid'] ) {
			error_log( "LGP Attachment: Validation failed - {$validation['error']}" );
			return false;
		}

		// Check attachment count for ticket
		if ( ! self::can_add_attachment( $ticket_id ) ) {
			error_log( "LGP Attachment: Max attachments exceeded for ticket $ticket_id" );
			return false;
		}

		// Get company directory
		$company_dir = self::get_company_directory( $company_id );

		// Generate secure filename
		$new_filename = self::generate_secure_filename( $filename, $ticket_id );
		$destination  = $company_dir . '/' . $new_filename;

		// Copy file with chunked reading (memory-safe)
		if ( ! self::copy_file_chunked( $file_path, $destination ) ) {
			error_log( "LGP Attachment: Failed to copy file: $filename" );
			return false;
		}

		// Get MIME type
		$mime_type = self::get_mime_type( $destination );

		// Store in database
		global $wpdb;

		$file_size = filesize( $destination );

		$result = $wpdb->insert(
			$wpdb->prefix . 'lgp_ticket_attachments',
			array(
				'ticket_id'   => $ticket_id,
				'file_name'   => $filename,
				'file_type'   => $mime_type,
				'file_size'   => $file_size,
				'file_path'   => $destination,
				'uploaded_by' => $uploaded_by,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		);

		if ( ! $result ) {
			// Clean up file if database insert fails
			unlink( $destination );
			return false;
		}

		return array(
			'id'        => $wpdb->insert_id,
			'filename'  => $new_filename,
			'path'      => $destination,
			'size'      => $file_size,
			'mime_type' => $mime_type,
		);
	}

	/**
	 * Validate file before saving
	 *
	 * @param string $file_path Temporary file path
	 * @param string $filename Original filename
	 * @return array Validation result with 'valid' boolean and 'error' string
	 */
	private static function validate_file( $file_path, $filename ) {
		// Check file exists
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return array(
				'valid' => false,
				'error' => 'File not readable',
			);
		}

		// Check file size
		$file_size = filesize( $file_path );
		if ( $file_size > self::MAX_FILE_SIZE ) {
			return array(
				'valid' => false,
				'error' => "File too large: $file_size > " . self::MAX_FILE_SIZE,
			);
		}

		// Check MIME type
		$mime_type = self::get_mime_type( $file_path );
		if ( ! in_array( $mime_type, self::ALLOWED_MIME_TYPES, true ) ) {
			return array(
				'valid' => false,
				'error' => "MIME type not allowed: $mime_type",
			);
		}

		// Sanitize filename
		$sanitized = sanitize_file_name( $filename );
		if ( empty( $sanitized ) ) {
			return array(
				'valid' => false,
				'error' => 'Invalid filename',
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Check if we can add more attachments to ticket
	 *
	 * @param int $ticket_id Ticket ID
	 * @return bool True if can add
	 */
	private static function can_add_attachment( $ticket_id ) {
		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}lgp_ticket_attachments WHERE ticket_id = %d",
				$ticket_id
			)
		);

		return $count < self::MAX_ATTACHMENTS_PER_TICKET;
	}

	/**
	 * Generate secure filename with unique suffix
	 *
	 * @param string $original_name Original filename
	 * @param int    $ticket_id Ticket ID
	 * @return string Secure filename
	 */
	private static function generate_secure_filename( $original_name, $ticket_id ) {
		$sanitized = sanitize_file_name( $original_name );
		$parts     = pathinfo( $sanitized );
		$name      = $parts['filename'];
		$ext       = isset( $parts['extension'] ) ? '.' . $parts['extension'] : '';

		// Generate random suffix to prevent collisions
		$suffix = substr( md5( time() . wp_rand() ), 0, 8 );

		return $ticket_id . '-' . $suffix . '-' . $name . $ext;
	}

	/**
	 * Get MIME type of file
	 *
	 * @param string $file_path File path
	 * @return string MIME type or application/octet-stream
	 */
	private static function get_mime_type( $file_path ) {
		if ( function_exists( 'mime_content_type' ) ) {
			return mime_content_type( $file_path );
		}

		if ( extension_loaded( 'fileinfo' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = finfo_file( $finfo, $file_path );
			finfo_close( $finfo );
			return $mime ?: 'application/octet-stream';
		}

		// Fallback
		return 'application/octet-stream';
	}

	/**
	 * Copy file in chunks to avoid memory exhaustion
	 *
	 * @param string $source Source file path
	 * @param string $destination Destination file path
	 * @return bool Success
	 */
	private static function copy_file_chunked( $source, $destination ) {
		$source_handle = fopen( $source, 'rb' );
		if ( ! $source_handle ) {
			return false;
		}

		$dest_handle = fopen( $destination, 'wb' );
		if ( ! $dest_handle ) {
			fclose( $source_handle );
			return false;
		}

		// Copy in chunks
		while ( ! feof( $source_handle ) ) {
			$chunk = fread( $source_handle, self::CHUNK_SIZE );
			if ( $chunk === false ) {
				fclose( $source_handle );
				fclose( $dest_handle );
				unlink( $destination );
				return false;
			}

			if ( fwrite( $dest_handle, $chunk ) === false ) {
				fclose( $source_handle );
				fclose( $dest_handle );
				unlink( $destination );
				return false;
			}
		}

		fclose( $source_handle );
		fclose( $dest_handle );

		// Set appropriate permissions
		chmod( $destination, 0644 );

		return true;
	}

	/**
	 * Get safe attachment download URL (with access control)
	 *
	 * @param int $attachment_id Attachment ID
	 * @return string|false Download URL or false
	 */
	public static function get_download_url( $attachment_id ) {
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if ( ! $attachment ) {
			return false;
		}

		// Generate temporary token for access control
		$token = hash_hmac(
			'sha256',
			$attachment_id . '|' . $attachment->ticket_id,
			wp_salt()
		);

		// Return portal download URL (with access control check)
		return add_query_arg(
			array(
				'lgp_action' => 'download_attachment',
				'id'         => $attachment_id,
				'token'      => $token,
			),
			site_url()
		);
	}

	/**
	 * Delete attachment securely
	 *
	 * @param int $attachment_id Attachment ID
	 * @return bool Success
	 */
	public static function delete_attachment( $attachment_id ) {
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if ( ! $attachment ) {
			return false;
		}

		// Delete file
		if ( file_exists( $attachment->file_path ) ) {
			unlink( $attachment->file_path );
		}

		// Delete database record
		return (bool) $wpdb->delete(
			$wpdb->prefix . 'lgp_ticket_attachments',
			array( 'id' => $attachment_id ),
			array( '%d' )
		);
	}

	/**
	 * Get attachment for display (with access control)
	 *
	 * @param int $attachment_id Attachment ID
	 * @return array|false Attachment data or false
	 */
	public static function get_attachment( $attachment_id ) {
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if ( ! $attachment ) {
			return false;
		}

		// Check access (portal will verify user can access ticket)
		return array(
			'id'          => $attachment->id,
			'filename'    => $attachment->file_name,
			'size'        => $attachment->file_size,
			'type'        => $attachment->file_type,
			'created'     => $attachment->created_at,
			'uploaded_by' => $attachment->uploaded_by,
		);
	}
}

// Initialize on every page load
LGP_Attachment_Handler::init();
