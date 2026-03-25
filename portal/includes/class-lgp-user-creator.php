<?php
/**
 * LounGenie Portal user auto-creation.
 *
 * Automatically creates WP users from incoming emails with proper role assignment.
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User creator class.
 */
class LGP_User_Creator {

	/**
	 * Get or create WP user from email with company mapping.
	 *
	 * @param string $email Email address.
	 * @param int    $company_id Company ID.
	 * @param string $display_name Optional display name.
	 * @return int|WP_Error User ID or WP_Error.
	 */
	public static function get_or_create_user( $email, $company_id, $display_name = '' ) {
		// Check if user exists.
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			return $user->ID;
		}

		// Create new user.
		return self::create_user_from_email( $email, $company_id, $display_name );
	}

	/**
	 * Create new WP user from email.
	 *
	 * @param string $email Email address.
	 * @param int    $company_id Company ID.
	 * @param string $display_name Optional display name.
	 * @return int|WP_Error User ID or WP_Error.
	 */
	private static function create_user_from_email( $email, $company_id, $display_name = '' ) {
		// Validate email.
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Invalid email address' );
		}

		// Generate username from email.
		$username = self::generate_username( $email );
		if ( is_wp_error( $username ) ) {
			return $username;
		}

		// Generate secure random password.
		$password = wp_generate_password( 16, true, true );

		// Use provided display name or extract from email.
		if ( empty( $display_name ) ) {
			$display_name = self::extract_display_name( $email );
		}

		// Create user.
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			error_log( "LGP User Creator: Failed to create user - {$user_id->get_error_message()}" );
			return $user_id;
		}

		// Update user metadata.
		wp_update_user(
			array(
				'ID'         => $user_id,
				'first_name' => $display_name,
				'user_url'   => '', // Clear default.
			)
		);

		// Add company ID to user meta for role-based access
		update_user_meta( $user_id, '_lgp_company_id', $company_id );

		// Assign Partner Company role
		$user = new WP_User( $user_id );
		$user->add_role( 'lgp_partner' );

		// Log user creation for audit
		if ( class_exists( 'LGP_Logger' ) ) {
			LGP_Logger::log_event(
				$user_id,
				'user_created_from_email',
				array(
					'email'      => $email,
					'company_id' => $company_id,
					'source'     => 'email_to_ticket',
				)
			);
		}

		// Send password reset email instead of plain password
		self::send_welcome_email( $user_id, $email, $company_id );

		error_log( "LGP User Creator: Created user $username (ID: $user_id) for company $company_id" );

		return $user_id;
	}

	/**
	 * Generate unique username from email
	 *
	 * @param string $email Email address
	 * @return string|WP_Error Username or error
	 */
	private static function generate_username( $email ) {
		// Extract username part from email
		$parts         = explode( '@', $email );
		$base_username = sanitize_user( $parts[0], true );

		if ( empty( $base_username ) ) {
			return new WP_Error( 'invalid_email_format', 'Cannot extract valid username from email' );
		}

		// Check if username exists
		if ( ! username_exists( $base_username ) ) {
			return $base_username;
		}

		// Username exists, add domain suffix
		$domain   = str_replace( '.', '', $parts[1] );
		$username = $base_username . '-' . substr( $domain, 0, 8 );

		if ( ! username_exists( $username ) ) {
			return $username;
		}

		// Still exists, use email hash
		$username = $base_username . '-' . substr( md5( $email ), 0, 8 );

		if ( ! username_exists( $username ) ) {
			return $username;
		}

		// Last resort: use random suffix
		return $base_username . '-' . wp_rand( 100, 999 );
	}

	/**
	 * Extract display name from email
	 *
	 * @param string $email Email address
	 * @return string Display name
	 */
	private static function extract_display_name( $email ) {
		// Extract name part (before @)
		$parts     = explode( '@', $email );
		$name_part = $parts[0];

		// Convert jane.doe to Jane Doe
		$name = str_replace( array( '.', '-', '_' ), ' ', $name_part );
		$name = ucwords( strtolower( $name ) );

		return $name;
	}

	/**
	 * Send welcome email with password reset link
	 *
	 * @param int    $user_id User ID
	 * @param string $email Email address
	 * @param int    $company_id Company ID
	 */
	private static function send_welcome_email( $user_id, $email, $company_id ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		// Get company name
		global $wpdb;
		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT name FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
				$company_id
			)
		);

		$company_name = $company ? $company->name : 'LounGenie Portal';

		// Generate password reset link
		$reset_key = get_password_reset_key( $user );

		if ( is_wp_error( $reset_key ) ) {
			error_log( "LGP User Creator: Failed to generate reset key - {$reset_key->get_error_message()}" );
			return;
		}

		$reset_url = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $user->user_login ), 'login' );

		// Portal login URL
		$portal_url = home_url( '/partner-login' );

		$subject = sprintf( 'Welcome to %s Partner Portal', $company_name );

		$message = sprintf(
			"Welcome to the %s Partner Portal!\n\n" .
			"An account has been created for you at: %s\n\n" .
			"Email: %s\n" .
			"Username: %s\n\n" .
			"To set your password, please click the link below:\n" .
			"%s\n\n" .
			"This link expires in 24 hours.\n\n" .
			"After setting your password, you can log in at:\n" .
			"%s\n\n" .
			"If you have any questions, please contact our support team.\n\n" .
			"Best regards,\n" .
			'%s Support Team',
			$company_name,
			site_url(),
			$email,
			$user->user_login,
			$reset_url,
			$portal_url,
			$company_name
		);

		$headers = array(
			'From: ' . get_option( 'blogname' ) . ' <' . get_option( 'admin_email' ) . '>',
			'Content-Type: text/plain; charset=UTF-8',
		);

		wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Link user to company
	 *
	 * @param int $user_id User ID
	 * @param int $company_id Company ID
	 * @return bool Success
	 */
	public static function link_user_to_company( $user_id, $company_id ) {
		return (bool) update_user_meta( $user_id, '_lgp_company_id', $company_id );
	}

	/**
	 * Get user's company ID
	 *
	 * @param int $user_id User ID
	 * @return int|false Company ID or false
	 */
	public static function get_user_company_id( $user_id ) {
		return get_user_meta( $user_id, '_lgp_company_id', true );
	}

	/**
	 * Get company ID from email domain
	 *
	 * @param string $email Email address
	 * @return int|false Company ID or false
	 */
	public static function find_company_by_email_domain( $email ) {
		global $wpdb;

		// Extract domain
		$parts = explode( '@', $email );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$domain = strtolower( $parts[1] );

		// Search for company with matching contact email domain
		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}lgp_companies WHERE contact_email LIKE %s LIMIT 1",
				'%@' . $domain
			)
		);

		if ( $company ) {
			return $company->id;
		}

		// Try searching in stored company domains (if metadata exists)
		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}lgp_companies 
				WHERE id IN (
					SELECT company_id FROM {$wpdb->prefix}postmeta 
					WHERE meta_key = '_lgp_email_domain' AND meta_value = %s
				)
				LIMIT 1",
				$domain
			)
		);

		return $company ? $company->id : false;
	}

	/**
	 * Bulk import users from company email domain
	 *
	 * @param int   $company_id Company ID
	 * @param array $emails Array of email addresses
	 * @return array Results with created/failed counts
	 */
	public static function import_users_for_company( $company_id, $emails ) {
		$results = array(
			'created' => 0,
			'exists'  => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		foreach ( $emails as $email ) {
			$user_id = self::get_or_create_user( $email, $company_id );

			if ( is_wp_error( $user_id ) ) {
				++$results['failed'];
				$results['errors'][] = array(
					'email' => $email,
					'error' => $user_id->get_error_message(),
				);
			} else {
				// Check if newly created or existing
				$user            = get_user_by( 'id', $user_id );
				$user_registered = strtotime( $user->user_registered );

				if ( time() - $user_registered < 60 ) {
					++$results['created'];
				} else {
					++$results['exists'];
				}
			}
		}

		return $results;
	}
}
