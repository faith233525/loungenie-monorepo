<?php
/**
 * LounGenie Portal - Email to Ticket Converter
 * Automatically converts incoming emails to support tickets
 *
 * @package LounGenie Portal
 * @version 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Email_To_Ticket {

	/**
	 * Initialize email handler
	 */
	public static function init() {
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_email_failure' ) );
		add_filter( 'wp_mail', array( __CLASS__, 'intercept_support_email' ) );
		add_action( 'lgp_email_received', array( __CLASS__, 'process_received_email' ) );
	}

	/**
	 * Intercept support emails and convert to tickets
	 *
	 * @param array $mail_data Email data
	 * @return array Modified mail data
	 */
	public static function intercept_support_email( $mail_data ) {
		// Check if email is from support channels
		$to_email = is_array( $mail_data['to'] ) ? $mail_data['to'][0] : $mail_data['to'];

		if ( self::is_support_email( $to_email ) ) {
			// Store for processing
			self::store_incoming_email(
				array(
					'to'      => $to_email,
					'subject' => $mail_data['subject'],
					'message' => $mail_data['message'],
					'headers' => $mail_data['headers'],
				)
			);
		}

		return $mail_data;
	}

	/**
	 * Check if email is sent to support channel
	 *
	 * @param string $email Email address
	 * @return bool True if support email
	 */
	private static function is_support_email( $email ) {
		$support_emails = array(
			'support@loungenie.com',
			'tickets@loungenie.com',
			'help@poolsafe.com',
		);

		return in_array( strtolower( $email ), array_map( 'strtolower', $support_emails ), true );
	}

	/**
	 * Store incoming email for processing
	 *
	 * @param array $email_data Email data
	 */
	private static function store_incoming_email( $email_data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_incoming_emails';

		$wpdb->insert(
			$table,
			array(
				'to'         => $email_data['to'],
				'subject'    => $email_data['subject'],
				'message'    => $email_data['message'],
				'headers'    => maybe_serialize( $email_data['headers'] ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Schedule async processing
		do_action( 'lgp_email_received', $email_data );
	}

	/**
	 * Process received email and create ticket
	 *
	 * @param array $email_data Email data
	 * @return int|false Ticket ID or false on failure
	 */
	public static function process_received_email( $email_data ) {
		// Parse email
		$parsed = self::parse_email( $email_data );

		if ( ! $parsed ) {
			return false;
		}

		// Extract company from email domain or sender
		$company_id = self::extract_company_id( $parsed['from'], $parsed['to'] );

		if ( ! $company_id ) {
			return false;
		}

		// Create ticket
		global $wpdb;

		$ticket_id = $wpdb->insert(
			$wpdb->prefix . 'lgp_tickets',
			array(
				'company_id'   => $company_id,
				'title'        => $parsed['subject'],
				'description'  => $parsed['body'],
				'status'       => 'open',
				'priority'     => self::detect_priority( $parsed['subject'], $parsed['body'] ),
				'created_by'   => self::get_user_from_email( $parsed['from'] ),
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
				'source'       => 'email',
				'from_email'   => $parsed['from'],
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ticket_id ) {
			return false;
		}

		// Add email-to-ticket indicator
		update_post_meta( $ticket_id, '_email_source', true );
		update_post_meta( $ticket_id, '_source_email', $parsed['from'] );

		// Process attachments if any
		if ( ! empty( $parsed['attachments'] ) ) {
			LGP_Attachments::link_attachments_to_ticket( $ticket_id, $parsed['attachments'] );
		}

		return $ticket_id;
	}

	/**
	 * Parse email content
	 *
	 * @param array $email_data Raw email data
	 * @return array|false Parsed email data or false
	 */
	private static function parse_email( $email_data ) {
		// Basic parsing - in production, use proper email parser library
		$from = self::extract_email_from_headers( $email_data['headers'], 'From' );

		if ( ! $from ) {
			return false;
		}

		return array(
			'from'        => $from,
			'to'          => $email_data['to'],
			'subject'     => sanitize_text_field( $email_data['subject'] ),
			'body'        => wp_kses_post( $email_data['message'] ),
			'attachments' => array(), // TODO: Parse attachments from email
		);
	}

	/**
	 * Extract email address from headers
	 *
	 * @param mixed $headers Email headers
	 * @param string $header_name Header name to extract
	 * @return string|false Email or false
	 */
	private static function extract_email_from_headers( $headers, $header_name ) {
		if ( is_string( $headers ) ) {
			$header_lines = explode( "\n", $headers );
			foreach ( $header_lines as $line ) {
				if ( strpos( $line, $header_name ) === 0 ) {
					preg_match( '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $line, $matches );
					return $matches[1] ?? false;
				}
			}
		}

		return false;
	}

	/**
	 * Extract company ID from email sender or recipient
		 *
	 * @param string $from_email From email
	 * @param string $to_email To email
	 * @return int|false Company ID or false
	 */
	private static function extract_company_id( $from_email, $to_email ) {
		global $wpdb;

		// Try to find company by email domain match
		$email_domain = substr( strrchr( $from_email, '@' ), 1 );

		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}lgp_companies WHERE email LIKE %s LIMIT 1",
				'%' . $email_domain . '%'
			)
		);

		if ( $company ) {
			return $company->id;
		}

		// Fallback to first company for demo
		$first_company = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}lgp_companies LIMIT 1" );

		return $first_company ?: false;
	}

	/**
	 * Detect ticket priority from email content
	 *
	 * @param string $subject Email subject
	 * @param string $body Email body
	 * @return string Priority level (low, medium, high, critical)
	 */
	private static function detect_priority( $subject, $body ) {
		$content = strtolower( $subject . ' ' . $body );

		// Critical keywords
		if ( preg_match( '/(urgent|critical|emergency|down|offline|broken)/i', $content ) ) {
			return 'critical';
		}

		// High priority keywords
		if ( preg_match( '/(high|asap|important|soon|immediately)/i', $content ) ) {
			return 'high';
		}

		// Low priority keywords
		if ( preg_match( '/(question|info|fyi|low|later)/i', $content ) ) {
			return 'low';
		}

		return 'medium';
	}

	/**
	 * Get or create user from email
	 *
	 * @param string $email Email address
	 * @return int|false User ID or false
	 */
	private static function get_user_from_email( $email ) {
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			return $user->ID;
		}

		// Create new user from email
		$parts    = explode( '@', $email );
		$username = sanitize_user( $parts[0] );

		if ( ! username_exists( $username ) ) {
			return wp_create_user(
				$username,
				wp_generate_password(),
				$email
			);
		}

		return false;
	}

	/**
	 * Log email processing failure
	 *
	 * @param WP_Error $error Error object
	 */
	public static function log_email_failure( $error ) {
		error_log( 'LounGenie Email to Ticket: ' . $error->get_error_message() );
	}

	/**
	 * Create ticket response email
	 *
	 * @param int $ticket_id Ticket ID
	 * @param string $recipient_email Recipient email
	 */
	public static function send_ticket_created_notification( $ticket_id, $recipient_email ) {
		global $wpdb;

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_tickets WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket ) {
			return;
		}

		$subject = sprintf(
			'[#%d] Ticket Created: %s',
			$ticket->id,
			$ticket->title
		);

		$message = sprintf(
			"Thank you for contacting LounGenie Support.\n\nYour ticket #%d has been created and assigned to our support team.\n\nSubject: %s\n\nStatus: %s\n\nWe'll get back to you shortly.",
			$ticket->id,
			$ticket->title,
			ucfirst( $ticket->status )
		);

		wp_mail( $recipient_email, $subject, $message );
	}
}

// Initialize
LGP_Email_To_Ticket::init();
