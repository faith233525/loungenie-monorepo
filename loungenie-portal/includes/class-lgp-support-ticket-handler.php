<?php

/**
 * Support Ticket Form Handler
 * Processes form submissions and creates tickets
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Support_Ticket_Handler {


	/**
	 * Initialize hooks
	 */
	public static function init() {
		 add_action( 'wp_ajax_lgp_submit_support_ticket', array( __CLASS__, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_lgp_submit_support_ticket', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Handle form submission
	 */
	public static function handle_submission() {
		try {
			// Verify nonce
			if ( ! isset( $_POST['lgp_ticket_nonce'] ) || ! wp_verify_nonce( $_POST['lgp_ticket_nonce'], 'lgp_submit_support_ticket' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'loungenie-portal' ) ) );
			}

			// Validate required fields
			$validation = self::validate_submission();
			if ( ! $validation['valid'] ) {
				wp_send_json_error( array( 'message' => $validation['message'] ) );
			}

			// Process form data
			$ticket_data = self::process_form_data();

			// Create ticket in database
			$ticket_id = self::create_ticket( $ticket_data );

			if ( ! $ticket_id ) {
				wp_send_json_error( array( 'message' => __( 'Failed to create ticket. Please try again.', 'loungenie-portal' ) ) );
			}

			// Handle file uploads
			if ( ! empty( $_FILES['attachments'] ) ) {
				self::process_attachments( $ticket_id, $ticket_data['company_id'] );
			}

			// Send confirmation email
			self::send_confirmation_email( $ticket_data, $ticket_id );

			// Send notification to support team
			self::notify_support_team( $ticket_data, $ticket_id );

			// Return success
			wp_send_json_success(
				array(
					'ticket_id' => $ticket_data['ticket_reference'],
					'message'   => __( 'Your support ticket has been submitted successfully.', 'loungenie-portal' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Validate form submission
	 */
	private static function validate_submission() {
		 $required_fields = array(
			 'first_name',
			 'last_name',
			 'email',
			 'category',
			 'urgency',
			 'subject',
			 'description',
			 'units_affected',
		 );

		 foreach ( $required_fields as $field ) {
			 if ( empty( $_POST[ $field ] ) ) {
				 return array(
					 'valid'   => false,
					 'message' => sprintf( __( 'Required field missing: %s', 'loungenie-portal' ), $field ),
				 );
			 }
		 }

		 // Validate email
		 $email = sanitize_email( $_POST['email'] );
		 if ( ! is_email( $email ) ) {
			 return array(
				 'valid'   => false,
				 'message' => __( 'Invalid email address.', 'loungenie-portal' ),
			 );
		 }

		 // Validate phone if provided
		 if ( ! empty( $_POST['phone'] ) ) {
			 $phone = sanitize_text_field( $_POST['phone'] );
			 if ( ! preg_match( '/^[\d\s()+-]{10,}$/', str_replace( ' ', '', $phone ) ) ) {
				 return array(
					 'valid'   => false,
					 'message' => __( 'Invalid phone number format.', 'loungenie-portal' ),
				 );
			 }
		 }

		 // Validate consent checkboxes
		 if ( empty( $_POST['consent_contact'] ) || empty( $_POST['consent_privacy'] ) ) {
			 return array(
				 'valid'   => false,
				 'message' => __( 'You must agree to the terms to submit a ticket.', 'loungenie-portal' ),
			 );
		 }

		 return array( 'valid' => true );
	}

	/**
	 * Process and sanitize form data
	 */
	private static function process_form_data() {
		$current_user = wp_get_current_user();
		$company_id   = isset( $_POST['company_id'] ) ? absint( $_POST['company_id'] ) : 0;

		// If user is logged in and not admin, use their company
		if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
			$company_id = LGP_Auth::get_user_company_id();
		}

		// Parse units affected
		$units_affected = sanitize_text_field( $_POST['units_affected'] );

		return array(
			'company_id'       => $company_id,
			'first_name'       => sanitize_text_field( $_POST['first_name'] ),
			'last_name'        => sanitize_text_field( $_POST['last_name'] ),
			'email'            => sanitize_email( $_POST['email'] ),
			'phone'            => isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '',
			'category'         => sanitize_text_field( $_POST['category'] ),
			'urgency'          => sanitize_text_field( $_POST['urgency'] ),
			'subject'          => sanitize_text_field( $_POST['subject'] ),
			'description'      => sanitize_textarea_field( $_POST['description'] ),
			'units_affected'   => $units_affected,
			'ticket_reference' => sanitize_text_field( $_POST['ticket_reference'] ),
			'user_id'          => is_user_logged_in() ? $current_user->ID : 0,
		);
	}

	/**
	 * Parse and normalize ticket form data (used by tests to validate parsing logic)
	 *
	 * @param array $data Raw form data (e.g., $_POST)
	 * @return array Parsed ticket form data
	 */
	public static function parse_ticket_form_data( $data = array() ) {
		$data = $data ?: $_POST;

		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		$company_id   = isset( $data['company_id'] ) ? absint( $data['company_id'] ) : 0;

		// If user is logged in and not admin, use their company
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() && function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) && class_exists( 'LGP_Auth' ) ) {
			$company_id = LGP_Auth::get_user_company_id();
		}

		// Parse units affected; preserve raw input for tests that assert ranges/strings
		$units_affected_raw = isset( $data['units_affected'] ) ? $data['units_affected'] : '';
		$units_affected     = sanitize_text_field( $units_affected_raw );

		return array(
			'company_id'         => $company_id,
			'first_name'         => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
			'last_name'          => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
			'email'              => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
			'phone'              => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'category'           => isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '',
			'urgency'            => isset( $data['urgency'] ) ? sanitize_text_field( $data['urgency'] ) : '',
			'subject'            => isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : '',
			'description'        => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			'units_affected'     => $units_affected,
			'units_affected_raw' => $units_affected_raw,
			'ticket_reference'   => isset( $data['ticket_reference'] ) ? sanitize_text_field( $data['ticket_reference'] ) : '',
			'user_id'            => ( $current_user && isset( $current_user->ID ) ) ? $current_user->ID : 0,
		);
	}

	/**
	 * Create ticket in database
	 */
	private static function create_ticket( $ticket_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'lgp_tickets';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			// Fallback: create ticket using custom post type if available
			return self::create_ticket_as_post( $ticket_data );
		}

		// Insert ticket
		$insert_result = $wpdb->insert(
			$table_name,
			array(
				'company_id'       => $ticket_data['company_id'],
				'user_id'          => $ticket_data['user_id'],
				'requester_name'   => $ticket_data['first_name'] . ' ' . $ticket_data['last_name'],
				'requester_email'  => $ticket_data['email'],
				'requester_phone'  => $ticket_data['phone'],
				'category'         => $ticket_data['category'],
				'urgency'          => $ticket_data['urgency'],
				'subject'          => $ticket_data['subject'],
				'description'      => $ticket_data['description'],
				'units_affected'   => $ticket_data['units_affected'],
				'ticket_reference' => $ticket_data['ticket_reference'],
				'status'           => 'open',
				'created_at'       => current_time( 'mysql' ),
				'updated_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $insert_result ) {
			throw new Exception( __( 'Failed to create ticket in database.', 'loungenie-portal' ) );
		}

		// Store unit IDs as metadata if available
		$ticket_id = $wpdb->insert_id;
		// Phase 2B: unit_ids removed - using aggregation only

		return $ticket_id;
	}

	/**
	 * Fallback: Create ticket as custom post type
	 */
	private static function create_ticket_as_post( $ticket_data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'lgp_ticket',
				'post_status'  => 'publish',
				'post_title'   => $ticket_data['subject'],
				'post_content' => $ticket_data['description'],
				'post_author'  => $ticket_data['user_id'] ?: 1,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( __( 'Failed to create ticket.', 'loungenie-portal' ) );
		}

		// Store ticket metadata
		update_post_meta( $post_id, '_company_id', $ticket_data['company_id'] );
		update_post_meta( $post_id, '_requester_name', $ticket_data['first_name'] . ' ' . $ticket_data['last_name'] );
		update_post_meta( $post_id, '_requester_email', $ticket_data['email'] );
		update_post_meta( $post_id, '_requester_phone', $ticket_data['phone'] );
		update_post_meta( $post_id, '_category', $ticket_data['category'] );
		update_post_meta( $post_id, '_urgency', $ticket_data['urgency'] );
		update_post_meta( $post_id, '_units_affected', $ticket_data['units_affected'] );
		// Phase 2B: unit_ids removed - using aggregation only
		update_post_meta( $post_id, '_ticket_reference', $ticket_data['ticket_reference'] );
		update_post_meta( $post_id, '_status', 'open' );

		return $post_id;
	}

	/**
	 * Process file attachments
	 */
	private static function process_attachments( $ticket_id, $company_id ) {
		// Require WordPress file handling
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload_dir        = wp_upload_dir();
		$ticket_upload_dir = $upload_dir['basedir'] . '/lgp-tickets/' . $ticket_id;

		// Create directory if it doesn't exist
		if ( ! is_dir( $ticket_upload_dir ) ) {
			wp_mkdir_p( $ticket_upload_dir );

			// Add .htaccess for security
			$htaccess_path = $ticket_upload_dir . '/.htaccess';
			if ( ! file_exists( $htaccess_path ) ) {
				file_put_contents( $htaccess_path, 'deny from all' );
			}
		}

		$uploaded_files = array();

		// Process each file
		for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
			if ( empty( $_FILES['attachments']['name'][ $i ] ) ) {
				continue;
			}

			$file = array(
				'name'     => $_FILES['attachments']['name'][ $i ],
				'type'     => $_FILES['attachments']['type'][ $i ],
				'tmp_name' => $_FILES['attachments']['tmp_name'][ $i ],
				'error'    => $_FILES['attachments']['error'][ $i ],
				'size'     => $_FILES['attachments']['size'][ $i ],
			);

			// Validate file
			if ( $file['error'] !== UPLOAD_ERR_OK ) {
				continue;
			}

			// Validate file size (10MB max)
			if ( $file['size'] > 10 * 1024 * 1024 ) {
				continue;
			}

			// Validate MIME type
			$allowed_mimes = array(
				'image/jpeg',
				'image/png',
				'image/gif',
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'text/plain',
				'application/zip',
			);

			if ( ! in_array( $file['type'], $allowed_mimes ) ) {
				continue;
			}

			// Generate unique filename
			$filename = sanitize_file_name( $file['name'] );
			$filename = wp_unique_filename( $ticket_upload_dir, $filename );

			// Move file
			$destination = $ticket_upload_dir . '/' . $filename;
			if ( move_uploaded_file( $file['tmp_name'], $destination ) ) {
				$uploaded_files[] = array(
					'filename'      => $filename,
					'original_name' => $file['name'],
					'size'          => $file['size'],
					'type'          => $file['type'],
					'uploaded_at'   => current_time( 'mysql' ),
				);
			}
		}

		// Save uploaded files metadata
		if ( ! empty( $uploaded_files ) ) {
			update_post_meta( $ticket_id, '_attached_files', $uploaded_files );
		}

		return $uploaded_files;
	}

	/**
	 * Send confirmation email to requester
	 */
	private static function send_confirmation_email( $ticket_data, $ticket_id ) {
		$to      = $ticket_data['email'];
		$subject = sprintf(
			__( 'Support Ticket Confirmation: %s', 'loungenie-portal' ),
			$ticket_data['ticket_reference']
		);

		$message = sprintf(
			__(
				'Dear %1$s,

Thank you for submitting a support ticket. Your ticket has been received and assigned the following reference number:

Ticket Reference: %2$s
Category: %3$s
Urgency: %4$s

We will review your request and respond as soon as possible. You can track the status of your ticket using the reference number above.

Best regards,
LounGenie Support Team'
				Thank you for submitting a support ticket . Your ticket has been received and assigned the following reference number:

				Ticket Reference: % s
				Category: % s
				Urgency: % s

				We will review your request and respond as soon as possible . You can track the status of your ticket using the reference number above .

				Best regards,
				LounGenie Support Team',
                'loungenie - portal'
                ),
			$ticket_data['first_name'],
			$ticket_data['ticket_reference'],
			ucfirst( str_replace( '_', ' ', $ticket_data['category'] ) ),
			ucfirst( $ticket_data['urgency'] )
		);

		$headers = array(
			'Content - Type: text / html; charset = UTF - 8',
			'From: ' . get_bloginfo( 'name' ) . ' < ' . get_option( 'admin_email' ) . ' > ',
		);

		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Notify support team of new ticket
	 */
	private static function notify_support_team( $ticket_data, $ticket_id ) 	{
		$admin_email = get_option( 'admin_email' );
		$to          = $admin_email;

		$subject = sprintf(
			__( '[ new Ticket ]() { % 1$s - % 2$s', 'loungenie - portal' ),
			$ticket_data['ticket_reference'],
			$ticket_data['subject']
		);

		$message = sprintf(
			__(
                'A new support ticket has been() { submitted:
				Ticket Reference: % 1$s
				Requester: % 2$s % 3$s
				Email: % 4$s
				Phone: % 5$s
				Category: % 6$s
				Urgency: % 7$s
				Units Affected: % 8$s

				Subject: % 9$s

				Description:
				% 10$s

				-- -
				Please log in to the portal to respond to this ticket . '
Ticket Reference: %s
Requester: %s %s
Email: %s
Phone: %s
Category: %s
Urgency: %s
Units Affected: %s

Subject: %s

Description:
%s

---
Please log in to the portal to respond to this ticket.',
				'loungenie-portal'
			),
			$ticket_data['ticket_reference'],
			$ticket_data['first_name'],
			$ticket_data['last_name'],
			$ticket_data['email'],
			$ticket_data['phone'],
			ucfirst( str_replace( '_', ' ', $ticket_data['category'] ) ) {,
			ucfirst( $ticket_data['urgency'] ),
			$ticket_data['units_affected'],
			$ticket_data['subject'],
			$ticket_data['description']
		);
			}
		}
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

				wp_mail( $to, $subject, $message, $headers );
	}
}

// Initialize handler
LGP_Support_Ticket_Handler::init();
