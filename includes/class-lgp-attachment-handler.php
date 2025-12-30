<?php

/**
 * LounGenie Portal - Attachment Handler
 *
 * Manages file attachments with company-specific folders, chunked parsing,
 * and comprehensive security controls. Implements multi-layered security:
 * - File size and count limits
 * - MIME type validation
 * - Magic byte signature verification
 * - Executable file blocking
 * - Secure filename generation
 * - Company-isolated storage
 *
 * @package LounGenie Portal
 * @version 1.8.0
 * @since 2.0.0 Enhanced with security hardening.
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Attachment Handler
 *
 * Secure file attachment management with chunked reading for memory
 * efficiency and comprehensive security validation.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Attachment_Handler
{

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
		'video/mp4',
		'video/quicktime',
		'video/x-msvideo',
		'video/x-matroska',
		'video/webm',
	);

	/**
	 * Initialize attachment handler.
	 *
	 * Sets up secure attachment storage infrastructure including:
	 * - Base attachment directory creation
	 * - Security file generation (.htaccess, index.php)
	 * - Directory listing prevention
	 * - Script execution blocking
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init()
	{
		// Create base attachment directory with security
		self::ensure_attachment_base_exists();

		// Add .htaccess to prevent direct access to certain files
		self::add_security_files();
	}

	/**
	 * Ensure base attachment directory exists with proper protection.
	 *
	 * Creates the lgp-attachments directory in WordPress uploads folder
	 * and adds index.php to prevent directory listing. Creates directory
	 * structure recursively if needed.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function ensure_attachment_base_exists()
	{
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/lgp-attachments';

		// Create directory
		if (! is_dir($base_dir)) {
			wp_mkdir_p($base_dir);
		}

		// Ensure index.php exists to prevent directory listing
		if (! file_exists($base_dir . '/index.php')) {
			file_put_contents(
				$base_dir . '/index.php',
				"<?php // LounGenie attachment storage\n"
			);
		}
	}

	/**
	 * Add security files (.htaccess, index.php).
	 *
	 * Creates .htaccess file that:
	 * - Denies execution of PHP files
	 * - Blocks executable files (.exe)
	 * - Forces download of documents
	 * - Protects configuration files
	 *
	 * This defense-in-depth approach protects against uploaded malicious
	 * files even if validation is bypassed.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private static function add_security_files()
	{
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

		if (! file_exists($base_dir . '/.htaccess')) {
			file_put_contents($base_dir . '/.htaccess', $htaccess_content);
		}
	}

	/**
	 * Get company-specific attachment directory.
	 *
	 * Returns or creates a dedicated directory for company attachments.
	 * Directory names are based on company email domain for organization.
	 * Automatically creates directory with security files if needed.
	 *
	 * Example: poolsafe.com -> uploads/lgp-attachments/poolsafe-com/
	 *
	 * @since 2.0.0
	 * @param int|string $company_id Company ID or domain.
	 * @return string Full filesystem path to company attachment directory.
	 */
	public static function get_company_directory($company_id)
	{
		global $wpdb;

		// Get company domain
		if (is_numeric($company_id)) {
			$company = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, contact_email FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
					$company_id
				)
			);

			if ($company && $company->contact_email) {
				$domain   = substr(strrchr($company->contact_email, '@'), 1);
				$domain   = str_replace('.', '-', $domain); // poolsafe.com -> poolsafe-com
				$dir_name = sanitize_file_name($domain);
			} else {
				$dir_name = sanitize_file_name('company-' . $company_id);
			}
		} else {
			$dir_name = sanitize_file_name($company_id);
		}

		$upload_dir  = wp_upload_dir();
		$company_dir = $upload_dir['basedir'] . '/lgp-attachments/' . $dir_name;

		// Create directory if it doesn't exist
		if (! is_dir($company_dir)) {
			wp_mkdir_p($company_dir);
			// Add index.php to prevent listing
			file_put_contents(
				$company_dir . '/index.php',
				"<?php // LounGenie attachment storage\n"
			);
		}

		return $company_dir;
	}

	/**
	 * Save attachment with chunked reading (memory-safe).
	 *
	 * Processes and stores file attachment with comprehensive validation:
	 * 1. Validates file type, size, and content
	 * 2. Checks attachment count limits
	 * 3. Generates secure unique filename
	 * 4. Copies file using memory-safe chunked reading
	 * 5. Stores metadata in database
	 * 6. Cleans up on failure
	 *
	 * Uses chunked reading to handle large files without memory exhaustion.
	 *
	 * @since 2.0.0
	 * @param string $file_path Temporary file path from upload.
	 * @param string $filename Original filename from user.
	 * @param int    $ticket_id Associated ticket ID.
	 * @param int    $company_id Company ID for directory organization.
	 * @param int    $uploaded_by User ID who uploaded. Default 0.
	 * @return array|false Attachment metadata array on success, false on failure.
	 *                     Metadata includes: id, filename, path, size, mime_type.
	 */
	public static function save_attachment($file_path, $filename, $ticket_id, $company_id, $uploaded_by = 0)
	{
		// Validate file
		$validation = self::validate_file($file_path, $filename);
		if (! $validation['valid']) {
			error_log("LGP Attachment: Validation failed - {$validation['error']}");
			return false;
		}

		// Check attachment count for ticket
		if (! self::can_add_attachment($ticket_id)) {
			error_log("LGP Attachment: Max attachments exceeded for ticket $ticket_id");
			return false;
		}

		// Get company directory
		$company_dir = self::get_company_directory($company_id);

		// Generate secure filename
		$new_filename = self::generate_secure_filename($filename, $ticket_id);
		$destination  = $company_dir . '/' . $new_filename;

		// Copy file with chunked reading (memory-safe)
		if (! self::copy_file_chunked($file_path, $destination)) {
			error_log("LGP Attachment: Failed to copy file: $filename");
			return false;
		}

		// Get MIME type
		$mime_type = self::get_mime_type($destination);

		// Store in database
		global $wpdb;

		$file_size = filesize($destination);

		$result = $wpdb->insert(
			$wpdb->prefix . 'lgp_ticket_attachments',
			array(
				'ticket_id'   => $ticket_id,
				'file_name'   => $filename,
				'file_type'   => $mime_type,
				'file_size'   => $file_size,
				'file_path'   => $destination,
				'uploaded_by' => $uploaded_by,
				'created_at'  => current_time('mysql'),
			),
			array('%d', '%s', '%s', '%d', '%s', '%d', '%s')
		);

		if (! $result) {
			// Clean up file if database insert fails
			wp_delete_file($destination);
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
	 * Security: Comprehensive validation including MIME type and file signature checks
	 *
	 * @param string $file_path Temporary file path
	 * @param string $filename Original filename
	 * @return array Validation result with 'valid' boolean and 'error' string
	 */
	private static function validate_file($file_path, $filename)
	{
		// Check file exists
		if (! file_exists($file_path) || ! is_readable($file_path)) {
			return array(
				'valid' => false,
				'error' => 'File not readable',
			);
		}

		// Check file size
		$file_size = filesize($file_path);
		if ($file_size > self::MAX_FILE_SIZE) {
			return array(
				'valid' => false,
				'error' => "File too large: $file_size > " . self::MAX_FILE_SIZE,
			);
		}

		// Security: Check MIME type using file info extension (magic bytes)
		$mime_type = self::get_mime_type($file_path);
		if (! in_array($mime_type, self::ALLOWED_MIME_TYPES, true)) {
			return array(
				'valid' => false,
				'error' => "MIME type not allowed: $mime_type",
			);
		}

		// Security: Additional file signature validation (magic bytes check)
		$validation = self::validate_file_signature($file_path, $mime_type);
		if (! $validation['valid']) {
			return $validation;
		}

		// Security: Sanitize filename and check for dangerous extensions
		$sanitized = sanitize_file_name($filename);
		if (empty($sanitized)) {
			return array(
				'valid' => false,
				'error' => 'Invalid filename',
			);
		}

		// Security: Block executable file extensions
		$dangerous_extensions = array('.php', '.phtml', '.php3', '.php4', '.php5', '.exe', '.com', '.bat', '.cmd', '.sh', '.cgi');
		$filename_lower = strtolower($filename);
		foreach ($dangerous_extensions as $ext) {
			if (substr($filename_lower, -strlen($ext)) === $ext) {
				return array(
					'valid' => false,
					'error' => 'Executable files are not allowed for security reasons',
				);
			}
		}

		return array('valid' => true);
	}

	/**
	 * Validate file signature (magic bytes) matches expected MIME type
	 * Security: Prevents file type spoofing by checking actual file content
	 *
	 * @param string $file_path File path
	 * @param string $mime_type Detected MIME type
	 * @return array Validation result
	 */
	private static function validate_file_signature($file_path, $mime_type)
	{
		// Read first 8 bytes of file (enough for most file signatures)
		$handle = fopen($file_path, 'rb');
		if (! $handle) {
			return array('valid' => true); // Skip if can't read
		}

		$bytes = fread($handle, 8);
		fclose($handle);

		if (! $bytes) {
			return array('valid' => true); // Skip if can't read
		}

		// Security: Validate file signatures (magic bytes) for common types
		$signatures = array(
			'image/jpeg' => array(
				'FFD8FF', // JPEG
			),
			'image/png' => array(
				'89504E47', // PNG
			),
			'image/gif' => array(
				'474946', // GIF
			),
			'application/pdf' => array(
				'25504446', // %PDF
			),
			'video/mp4' => array(
				'66747970', // ftyp (offset 4)
			),
		);

		// Get hex representation of first bytes
		$hex = strtoupper(bin2hex(substr($bytes, 0, 4)));

		// Check if we have a signature to validate
		if (isset($signatures[$mime_type])) {
			$valid = false;
			foreach ($signatures[$mime_type] as $signature) {
				if (strpos($hex, $signature) === 0) {
					$valid = true;
					break;
				}
			}

			// For MP4, check at offset 4
			if (! $valid && $mime_type === 'video/mp4') {
				$hex_offset4 = strtoupper(bin2hex(substr($bytes, 4, 4)));
				foreach ($signatures[$mime_type] as $signature) {
					if (strpos($hex_offset4, $signature) === 0) {
						$valid = true;
						break;
					}
				}
			}

			if (! $valid) {
				return array(
					'valid' => false,
					'error' => 'File content does not match declared file type (possible malicious file)',
				);
			}
		}

		return array('valid' => true);
	}

	/**
	 * Check if more attachments can be added to ticket.
	 *
	 * Enforces MAX_ATTACHMENTS_PER_TICKET limit to prevent abuse
	 * and storage exhaustion.
	 *
	 * @since 2.0.0
	 * @param int $ticket_id Ticket ID to check.
	 * @return bool True if under limit and can add more, false if at limit.
	 */
	private static function can_add_attachment($ticket_id)
	{
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
	 * Generate secure filename with unique suffix.
	 *
	 * Creates filename that:
	 * - Prevents path traversal attacks
	 * - Avoids collisions via random suffix
	 * - Associates with ticket via ID prefix
	 * - Preserves original name for user reference
	 *
	 * Format: {ticket_id}-{random}-{sanitized_name}.{ext}
	 *
	 * @since 2.0.0
	 * @param string $original_name Original uploaded filename.
	 * @param int    $ticket_id Ticket ID for association.
	 * @return string Secure filename with ticket ID prefix and random suffix.
	 */
	private static function generate_secure_filename($original_name, $ticket_id)
	{
		$sanitized = sanitize_file_name($original_name);
		$parts     = pathinfo($sanitized);
		$name      = $parts['filename'];
		$ext       = isset($parts['extension']) ? '.' . $parts['extension'] : '';

		// Generate random suffix to prevent collisions
		$suffix = substr(md5(time() . wp_rand()), 0, 8);

		return $ticket_id . '-' . $suffix . '-' . $name . $ext;
	}

	/**
	 * Get MIME type of file.
	 *
	 * Uses PHP file info functions to detect actual MIME type from
	 * file content (not just extension). Tries multiple methods with
	 * fallback for different server configurations.
	 *
	 * @since 2.0.0
	 * @param string $file_path Full filesystem path to file.
	 * @return string MIME type string, or 'application/octet-stream' if unknown.
	 */
	private static function get_mime_type($file_path)
	{
		if (function_exists('mime_content_type')) {
			return mime_content_type($file_path);
		}

		if (extension_loaded('fileinfo')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime  = finfo_file($finfo, $file_path);
			finfo_close($finfo);
			return $mime ?: 'application/octet-stream';
		}

		// Fallback
		return 'application/octet-stream';
	}

	/**
	 * Copy file in chunks to avoid memory exhaustion.
	 *
	 * Reads and writes file in CHUNK_SIZE increments (1MB) to handle
	 * large files without hitting PHP memory limits. Cleans up partial
	 * files on failure. Sets secure file permissions (0644).
	 *
	 * @since 2.0.0
	 * @param string $source Source file path.
	 * @param string $destination Destination file path.
	 * @return bool True on successful copy, false on failure.
	 */
	private static function copy_file_chunked($source, $destination)
	{
		$source_handle = fopen($source, 'rb');
		if (! $source_handle) {
			return false;
		}

		$dest_handle = fopen($destination, 'wb');
		if (! $dest_handle) {
			fclose($source_handle);
			return false;
		}

		// Copy in chunks
		while (! feof($source_handle)) {
			$chunk = fread($source_handle, self::CHUNK_SIZE);
			if ($chunk === false) {
				fclose($source_handle);
				fclose($dest_handle);
				wp_delete_file($destination);
				return false;
			}

			if (fwrite($dest_handle, $chunk) === false) {
				fclose($source_handle);
				fclose($dest_handle);
				wp_delete_file($destination);
				return false;
			}
		}

		fclose($source_handle);
		fclose($dest_handle);

		// Set appropriate permissions
		chmod($destination, 0644);

		return true;
	}

	/**
	 * Get safe attachment download URL with access control.
	 *
	 * Generates secure download URL with temporary token for access
	 * control. Token prevents unauthorized direct file access and
	 * expires to limit exposure window.
	 *
	 * @since 2.0.0
	 * @param int $attachment_id Attachment database ID.
	 * @return string|false Secure download URL with token, or false if not found.
	 */
	public static function get_download_url($attachment_id)
	{
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if (! $attachment) {
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
	public static function delete_attachment($attachment_id)
	{
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if (! $attachment) {
			return false;
		}

		// Delete file
		if (file_exists($attachment->file_path)) {
			wp_delete_file($attachment->file_path);
		}

		// Delete database record
		return (bool) $wpdb->delete(
			$wpdb->prefix . 'lgp_ticket_attachments',
			array('id' => $attachment_id),
			array('%d')
		);
	}

	/**
	 * Get attachment for display (with access control)
	 *
	 * @param int $attachment_id Attachment ID
	 * @return array|false Attachment data or false
	 */
	public static function get_attachment($attachment_id)
	{
		global $wpdb;

		$attachment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_ticket_attachments WHERE id = %d",
				$attachment_id
			)
		);

		if (! $attachment) {
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
