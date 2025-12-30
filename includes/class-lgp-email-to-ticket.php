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
	 * Ingest a Graph message + attachments and create a ticket.
	 *
	 * @param array $message Parsed Graph message
	 * @param array $attachments Graph attachments array
	 */
	public static function ingest_graph_message( $message, $attachments = array() ) {
		$parsed = array(
			'from'    => $message['from'] ?? '',
			'to'      => '',
			'subject' => $message['subject'] ?? '',
			'body'    => $message['body'] ?? $message['bodyPreview'] ?? '',
		);

		$company_id = self::extract_company_id( $parsed['from'], $parsed['to'] );
		if ( ! $company_id ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( get_current_user_id(), 'graph_ingest_skipped_no_company', 0, array( 'from' => $parsed['from'] ) );
			}
			return false;
		}

		global $wpdb;

		// Idempotency: skip if this email already created a ticket
		$internet_id = $message['internetMessageId'] ?? '';
		if ( ! empty( $internet_id ) ) {
			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}lgp_tickets WHERE email_reference = %s LIMIT 1",
					$internet_id
				)
			);
			if ( $existing_id ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event(
						get_current_user_id(),
						'graph_ingest_duplicate_skipped',
						0,
						array(
							'internetMessageId' => $internet_id,
							'ticket_id'         => (int) $existing_id,
						)
					);
				}
				return (int) $existing_id;
			}

			$cache = get_option( 'lgp_graph_processed_ids', array() );
			if ( in_array( $internet_id, (array) $cache, true ) ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event( get_current_user_id(), 'graph_ingest_cache_duplicate_skipped', 0, array( 'internetMessageId' => $internet_id ) );
				}
				return false;
			}
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1) Create a service request from the email
			$requests_table = $wpdb->prefix . 'lgp_service_requests';
			$priority       = self::detect_priority( $parsed['subject'], $parsed['body'] );
			$notes          = sprintf(
				"Email From: %s\nSubject: %s\n\n%s",
				$parsed['from'],
				$parsed['subject'],
				wp_strip_all_tags( $parsed['body'] )
			);

			$inserted = $wpdb->insert(
				$requests_table,
				array(
					'company_id'   => $company_id,
					'unit_id'      => null,
					'request_type' => 'email',
					'priority'     => $priority,
					'status'       => 'pending',
					'notes'        => $notes,
				)
			);

			if ( $inserted === false ) {
				throw new Exception( 'Failed to insert service request' );
			}

			$service_request_id = $wpdb->insert_id;

			// 2) Create the ticket with initial thread entry
			$tickets_table = $wpdb->prefix . 'lgp_tickets';
			$thread_entry  = array(
				'timestamp' => current_time( 'mysql' ),
				'user'      => 'Email: ' . $parsed['from'],
				'message'   => wp_trim_words( wp_strip_all_tags( $parsed['body'] ), 200, '…' ),
				'subject'   => $parsed['subject'],
			);

			$inserted_ticket = $wpdb->insert(
				$tickets_table,
				array(
					'service_request_id' => $service_request_id,
					'status'             => 'open',
					'thread_history'     => wp_json_encode( array( $thread_entry ) ),
					'email_reference'    => $internet_id,
				)
			);

			if ( $inserted_ticket === false ) {
				throw new Exception( 'Failed to insert ticket' );
			}

			$ticket_id = $wpdb->insert_id;

			// 3) Persist attachments
			if ( ! empty( $attachments ) ) {
				self::save_graph_attachments( $attachments, $ticket_id );
			}

			// Commit
			$wpdb->query( 'COMMIT' );

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'graph_ingest_ticket_created',
					0,
					array(
						'ticket_id'         => (int) $ticket_id,
						'internetMessageId' => $internet_id,
					)
				);
			}
			if ( ! empty( $internet_id ) ) {
				$cache   = get_option( 'lgp_graph_processed_ids', array() );
				$cache   = is_array( $cache ) ? $cache : array();
				$cache[] = $internet_id;
				if ( count( $cache ) > 500 ) {
					$cache = array_slice( $cache, -500 ); }
				update_option( 'lgp_graph_processed_ids', $cache, false );
			}

			return $ticket_id;

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( get_current_user_id(), 'graph_ingest_error', 0, array( 'error' => $e->getMessage() ) );
			}
			return false;
		}
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
			'support@poolsafeinc.com',
			'tickets@poolsafeinc.com',
			'help@poolsafeinc.com',
		);

		return in_array( strtolower( $email ), array_map( 'strtolower', $support_emails ), true );
	}

	/**
	 * Persist Graph attachments to ticket attachments table
	 * Supports fileAttachment (contentBytes)
	 */
	private static function save_graph_attachments( $attachments, $ticket_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'lgp_ticket_attachments';

		foreach ( $attachments as $att ) {
			if ( empty( $att['@odata.type'] ) || strpos( $att['@odata.type'], 'fileAttachment' ) === false ) {
				continue;
			}
			if ( empty( $att['contentBytes'] ) || empty( $att['name'] ) ) {
				continue;
			}

			// Decode file and write to temp for validation
			$file_contents = base64_decode( $att['contentBytes'] );
			if ( $file_contents === false ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event( get_current_user_id(), 'graph_attachment_decode_failed', 0, array( 'name' => $att['name'] ) );
				}
				continue;
			}

			// Size check (10MB)
			if ( strlen( $file_contents ) > LGP_File_Validator::MAX_FILE_SIZE ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event(
						get_current_user_id(),
						'graph_attachment_too_large',
						0,
						array(
							'name' => $att['name'],
							'size' => strlen( $file_contents ),
						)
					);
				}
				continue;
			}

			// Determine MIME by creating a temporary file
			$tmp = wp_tempnam( $att['name'] );
			if ( ! $tmp ) {
				continue;
			}
			file_put_contents( $tmp, $file_contents );
			$mime = mime_content_type( $tmp );
			@unlink( $tmp );

			// Validate MIME against allowed list
			if ( ! isset( LGP_File_Validator::ALLOWED_MIMES[ $mime ] ) ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event(
						get_current_user_id(),
						'graph_attachment_mime_blocked',
						0,
						array(
							'name' => $att['name'],
							'mime' => (string) $mime,
						)
					);
				}
				continue;
			}

			// Save file into protected directory like API does: uploads/lgp-attachments/{ticket_id}/
			$upload_base = wp_upload_dir();
			$dir         = trailingslashit( $upload_base['basedir'] ) . 'lgp-attachments/' . $ticket_id;
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir ); }
			$ht = $dir . '/.htaccess';
			if ( ! file_exists( $ht ) ) {
				file_put_contents( $ht, "deny from all\n" ); }

			$original  = sanitize_file_name( $att['name'] );
			$ext       = pathinfo( $original, PATHINFO_EXTENSION );
			$unique    = md5( uniqid( $original, true ) ) . ( $ext ? ".{$ext}" : '' );
			$file_path = $dir . '/' . $unique;
			$relative  = 'lgp-attachments/' . $ticket_id . '/' . $unique;

			if ( file_put_contents( $file_path, $file_contents ) === false ) {
				if ( class_exists( 'LGP_Logger' ) ) {
					LGP_Logger::log_event(
						get_current_user_id(),
						'graph_attachment_upload_failed',
						0,
						array(
							'name'  => $att['name'],
							'error' => 'write_failed',
						)
					);
				}
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'ticket_id'   => $ticket_id,
					'file_name'   => $original,
					'file_type'   => (string) $mime,
					'file_size'   => strlen( $file_contents ),
					'file_path'   => $relative,
					'uploaded_by' => 0,
					'created_at'  => current_time( 'mysql', true ),
				),
				array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
			);

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'graph_attachment_saved',
					0,
					array(
						'ticket_id' => (int) $ticket_id,
						'name'      => $original,
					)
				);
			}
		}
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
				'company_id'  => $company_id,
				'title'       => $parsed['subject'],
				'description' => $parsed['body'],
				'status'      => 'open',
				'priority'    => self::detect_priority( $parsed['subject'], $parsed['body'] ),
				'created_by'  => self::get_user_from_email( $parsed['from'] ),
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
				'source'      => 'email',
				'from_email'  => $parsed['from'],
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
	 * @param mixed  $headers Email headers
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
	 * @param int    $ticket_id Ticket ID
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
