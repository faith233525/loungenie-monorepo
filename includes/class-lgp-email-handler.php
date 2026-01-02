<?php

/**
 * LounGenie Portal email handler.
 *
 * Converts incoming support emails to tickets.
 * v1.8.0 - Adds Microsoft Graph (app-only) support for inbound/outbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email handler class.
 */
class LGP_Email_Handler {



	private static $option_key       = 'lgp_email_settings';
	private static $graph_option_key = 'lgp_graph_settings';

	/**
	 * Initialize email handler.
	 *
	 * @return void
	 */
	public static function init() {
		// Schedule/clear email processing based on configuration.
		self::ensure_cron_scheduled();

		add_action( 'lgp_process_emails', array( __CLASS__, 'process_emails' ) );
	}

	/**
	 * Ensure the email processing cron is scheduled (or cleared) based on settings.
	 *
	 * @return void
	 */
	public static function ensure_cron_scheduled() {
		$has_graph = self::is_graph_enabled();
		$has_pop3  = self::is_pop3_configured();

		if ( $has_graph || $has_pop3 ) {
			if ( ! wp_next_scheduled( 'lgp_process_emails' ) ) {
				// Shared hosting constraint: use WP-Cron hourly schedule only.
				wp_schedule_event( time(), 'hourly', 'lgp_process_emails' );
			}
		} else {
			// No configuration present; avoid noisy cron runs.
			wp_clear_scheduled_hook( 'lgp_process_emails' );
		}
	}

	/**
	 * Process all pending emails.
	 *
	 * @return void
	 */
	public static function process_emails() {
		// Prefer Graph if configured, otherwise fallback to POP3.
		if ( self::is_graph_enabled() ) {
			self::process_graph_emails();
			return;
		}

		$settings = get_option( self::$option_key, array() );

		if ( ! self::is_pop3_configured() ) {
			// Not configured; skip. Cron is cleared when not configured.
			return;
		}

		try {
			// Connect to POP3.
			$mailbox = '{' . $settings['pop3_server'] . ':110/pop3}INBOX';

			// Suppress warnings.
			$connection = @imap_open( $mailbox, $settings['pop3_username'], $settings['pop3_password'] );

			if ( ! $connection ) {
				error_log( 'LGP: POP3 connection failed: ' . imap_last_error() );
				return;
			}

			// Get all emails.
			$emails = imap_search( $connection, 'ALL' );

			if ( $emails ) {
				// SHARED HOSTING: Limit batch size to prevent timeout
				$max_batch_size    = 10; // Process max 10 emails per run
				$emails_to_process = array_slice( array_reverse( $emails ), 0, $max_batch_size );
				$processed_count   = 0;
				$start_time        = time();

				// Process in reverse order (oldest first)
				foreach ( $emails_to_process as $email_id ) {
					// SHARED HOSTING: Check execution time limit
					if ( ( time() - $start_time ) > 20 ) {
						error_log( 'LGP: Email processing stopped - time limit reached. Processed: ' . $processed_count );
						break;
					}

					self::process_email( $connection, $email_id );
					++$processed_count;
				}

				if ( count( $emails ) > $max_batch_size ) {
					error_log( sprintf( 'LGP: %d emails remaining, will process in next run', count( $emails ) - $processed_count ) );
				}
			}

			// Batch expunge all deleted messages at once to minimize server load.
			imap_expunge( $connection );
			imap_close( $connection );
		} catch ( Exception $e ) {
			error_log( 'LGP Email Handler Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Determine if Graph is configured and enabled.
	 */
	private static function is_graph_enabled() {
		$settings = get_option( self::$graph_option_key, array() );
		return ! empty( $settings['enabled'] )
			&& ! empty( $settings['tenant_id'] )
			&& ! empty( $settings['client_id'] )
			&& ! empty( $settings['client_secret'] )
			&& ! empty( $settings['mailbox'] );
	}

	/**
	 * Check if POP3 settings are configured.
	 */
	private static function is_pop3_configured() {
		$settings = get_option( self::$option_key, array() );
		return ! empty( $settings['pop3_server'] )
			&& ! empty( $settings['pop3_username'] )
			&& ! empty( $settings['pop3_password'] );
	}

	/**
	 * Process inbound email via Microsoft Graph (app-only)
	 * Optimization: Batch processing with timeout protection for shared hosting
	 */
	private static function process_graph_emails() {
		if ( ! class_exists( 'LGP_Graph_Client' ) ) {
			require_once LGP_PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
		}

		$settings    = get_option( self::$graph_option_key, array() );
		$delta_token = $settings['delta_token'] ?? null;

		// Shared hosting timeout protection
		$start_time       = time();
		$max_execution    = 20; // Leave 10-second buffer for 30s timeout
		$max_emails_batch = 10; // REDUCED: Process max 10 emails per run for shared hosting
		$processed_count  = 0;

		// Check if we have enough time to execute
		$max_exec_time = (int) ini_get( 'max_execution_time' );
		if ( $max_exec_time > 0 && $max_exec_time < 30 ) {
			$max_execution = max( 10, $max_exec_time - 10 ); // Adjust to available time
			error_log( 'LGP: Adjusted email processing time limit to ' . $max_execution . 's based on server settings' );
		}

		// Concurrency guard: prevent overlapping cron runs (5 min lock)
		$lock_key = 'lgp_graph_sync_lock';
		if ( get_transient( $lock_key ) ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( get_current_user_id(), 'graph_sync_skipped_locked', 0, array( 'reason' => 'lock_active' ) );
			}
			return;
		}
		set_transient( $lock_key, 1, 5 * MINUTE_IN_SECONDS );

		try {
			$client   = new LGP_Graph_Client( $settings );
			$response = $client->get_messages( $delta_token );

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'graph_sync_started',
					0,
					array(
						'delta_present' => (bool) $delta_token,
						'count'         => isset( $response['messages'] ) ? count( $response['messages'] ) : 0,
					)
				);
			}

			// Stage new delta but only commit after successful processing
			$staged_delta = $response['delta_token'] ?? null;

			if ( ! empty( $response['messages'] ) ) {
				foreach ( $response['messages'] as $message ) {
					// Timeout protection: check execution time
					if ( ( time() - $start_time ) > $max_execution ) {
						error_log( "LGP Email batch timeout: processed {$processed_count} emails, stopping" );
						break;
					}

					// Batch limit protection
					if ( $processed_count >= $max_emails_batch ) {
						error_log( "LGP Email batch limit reached: {$max_emails_batch} emails" );
						break;
					}

					try {
						$attachments = $client->get_attachments( $message['id'] );
						LGP_Email_To_Ticket::ingest_graph_message( $message, $attachments );
						++$processed_count;
					} catch ( Exception $inner ) {
						if ( class_exists( 'LGP_Logger' ) ) {
							LGP_Logger::log_event(
								get_current_user_id(),
								'graph_message_process_error',
								0,
								array(
									'message_id' => $message['id'] ?? '',
									'error'      => $inner->getMessage(),
								)
							);
						} else {
							error_log( 'LGP Graph message process error: ' . $inner->getMessage() );
						}
					}
				}
			}

			if ( ! empty( $staged_delta ) ) {
				$settings['delta_token'] = $staged_delta;
				update_option( self::$graph_option_key, $settings );
			}

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'graph_sync_completed',
					0,
					array(
						'updated_delta' => ! empty( $staged_delta ),
					)
				);
			}
		} catch ( Exception $e ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( get_current_user_id(), 'graph_sync_error', 0, array( 'error' => $e->getMessage() ) );
			} else {
				error_log( 'LGP Graph Email Error: ' . $e->getMessage() );
			}
		} finally {
			// Release lock
			delete_transient( $lock_key );
		}
	}

	/**
	 * Process single email
	 */
	private static function process_email( $connection, $email_id ) {
		try {
			// Get email header
			$header = imap_headerinfo( $connection, $email_id );

			if ( ! $header ) {
				return;
			}

			// Get email body
			$body = self::get_email_body( $connection, $email_id );

			// Extract priority from subject
			$priority = 'medium';
			if ( preg_match( '/\[URGENT\]|\[CRITICAL\]|URGENT|CRITICAL/i', $header->subject ) ) {
				$priority = 'high';
			}
			if ( preg_match( '/\[LOW\]|LOW PRIORITY/i', $header->subject ) ) {
				$priority = 'low';
			}

			// Get sender email
			$from = '';
			if ( ! empty( $header->from[0]->mailbox ) && ! empty( $header->from[0]->host ) ) {
				$from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
			}

			if ( empty( $from ) ) {
				error_log( 'LGP: Email has no valid sender' );
				imap_delete( $connection, $email_id );
				imap_expunge( $connection );
				return;
			}

			// Find company by email domain
			$company = self::find_company_by_email( $from );

			if ( ! $company ) {
				error_log( "LGP: Email from unknown company: $from" );
				imap_delete( $connection, $email_id );
				imap_expunge( $connection );
				return;
			}

			// Get or create user contact
			$user_id = self::get_or_create_contact( $company, $from, $header );

			// Create ticket via service_request + ticket tables (atomic)
			$ticket_id = self::create_ticket_from_email(
				(int) $company->ID,
				$from,
				(string) $header->subject,
				(string) $body,
				isset( $header->message_id ) ? (string) $header->message_id : ''
			);

			if ( ! $ticket_id ) {
				error_log( 'LGP: Failed to create ticket from email' );
				imap_delete( $connection, $email_id );
				imap_expunge( $connection );
				return;
			}

			// Process attachments
			self::process_attachments( $connection, $email_id, $ticket_id );

			// Send confirmation email
			self::send_confirmation_email( $company, $user_id, $ticket_id );

			// Mark email as read and delete
			imap_delete( $connection, $email_id );
		} catch ( Exception $e ) {
			error_log( 'LGP: Email processing error: ' . $e->getMessage() );
		}
	}

	/**
	 * Get email body (handle multipart)
	 */
	private static function get_email_body( $connection, $email_id ) {
		$body      = '';
		$structure = imap_fetchstructure( $connection, $email_id );

		if ( ! $structure->parts ) {
			// Simple email
			$body = imap_fetchbody( $connection, $email_id, '1' );

			// Handle encoding
			if ( $structure->encoding == 3 ) { // BASE64
				$body = base64_decode( $body );
			} elseif ( $structure->encoding == 4 ) { // QUOTED-PRINTABLE
				$body = quoted_printable_decode( $body );
			}
		} else {
			// Multipart email - get text part
			foreach ( $structure->parts as $part_id => $part ) {
				if ( strtolower( $part->subtype ) === 'plain' ) {
					$body = imap_fetchbody( $connection, $email_id, $part_id + 1 );

					if ( $part->encoding == 3 ) {
						$body = base64_decode( $body );
					} elseif ( $part->encoding == 4 ) {
						$body = quoted_printable_decode( $body );
					}

					break;
				}
			}
		}

		// Clean up body
		$body = trim( $body );
		$body = wp_kses_post( $body );

		return $body;
	}

	/**
	 * Find company by email domain
	 */
	private static function find_company_by_email( $email ) {
		global $wpdb;

		// Extract domain
		preg_match( '/@(.+)$/', $email, $matches );
		$domain = $matches[1] ?? '';

		if ( empty( $domain ) ) {
			return null;
		}

		// Try to find company with matching domain in metadata
		$args = array(
			'post_type'      => 'lgp_company',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => 'email_domain',
					'value'   => $domain,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		// Try exact email in contact list
		$args = array(
			'post_type'      => 'lgp_company',
			'posts_per_page' => 1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'primary_contact_email',
					'value'   => $email,
					'compare' => '=',
				),
				array(
					'key'     => 'contacts_email',
					'value'   => $email,
					'compare' => 'LIKE',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		return null;
	}

	/**
	 * Get or create contact from email
	 */
	private static function get_or_create_contact( $company, $email, $header ) {
		// Extract name from header
		$name = $header->from[0]->personal ?? 'Support User';

		// Search for existing contact
		$contacts = get_field( 'contacts', $company->ID ) ?: array();

		foreach ( $contacts as $contact ) {
			if ( $contact['email'] === $email ) {
				return $company->ID; // Use company ID as user reference
			}
		}

		// Add new contact
		$new_contact = array(
			'name'  => $name,
			'email' => $email,
			'phone' => '',
			'role'  => 'support',
		);

		$contacts[] = $new_contact;
		update_field( 'contacts', $contacts, $company->ID );

		return $company->ID;
	}

	/**
	 * Create ticket from email
	 */
	private static function create_ticket_from_email( $company_id, $from_email, $subject, $body, $message_id ) {
		global $wpdb;

		// Idempotency check
		if ( $message_id ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}lgp_tickets WHERE email_reference = %s LIMIT 1",
					$message_id
				)
			);
			if ( $exists ) {
				return (int) $exists;
			}
		}

		// START TRANSACTION
		$wpdb->query( 'START TRANSACTION' );
		try {
			// Service request
			$requests_table = $wpdb->prefix . 'lgp_service_requests';
			$notes          = sprintf(
				"Email From: %s\nSubject: %s\n\n%s",
				sanitize_email( $from_email ),
				sanitize_text_field( $subject ),
				wp_strip_all_tags( $body )
			);

			$sr_ok = $wpdb->insert(
				$requests_table,
				array(
					'company_id'   => (int) $company_id,
					'unit_id'      => null,
					'request_type' => 'email',
					'priority'     => 'medium',
					'status'       => 'pending',
					'notes'        => $notes,
				)
			);
			if ( $sr_ok === false ) {
				throw new Exception( 'service_request_insert_failed' );
			}
			$sr_id = $wpdb->insert_id;

			// Ticket
			$tickets_table = $wpdb->prefix . 'lgp_tickets';
			$thread        = array(
				array(
					'timestamp' => current_time( 'mysql' ),
					'user'      => 'Email: ' . sanitize_email( $from_email ),
					'message'   => wp_trim_words( wp_strip_all_tags( $body ), 200, '…' ),
					'subject'   => sanitize_text_field( $subject ),
				),
			);

			$t_ok = $wpdb->insert(
				$tickets_table,
				array(
					'service_request_id' => $sr_id,
					'status'             => 'open',
					'thread_history'     => wp_json_encode( $thread ),
					'email_reference'    => $message_id,
				)
			);
			if ( $t_ok === false ) {
				throw new Exception( 'ticket_insert_failed' );
			}
			$ticket_id = $wpdb->insert_id;

			$wpdb->query( 'COMMIT' );

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'pop3_ingest_ticket_created',
					$company_id,
					array(
						'ticket_id'  => (int) $ticket_id,
						'message_id' => $message_id,
					)
				);
			}

			return (int) $ticket_id;
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( get_current_user_id(), 'pop3_ingest_error', $company_id, array( 'error' => $e->getMessage() ) );
			}
			return false;
		}
	}

	/**
	 * Process email attachments
	 */
	private static function process_attachments( $connection, $email_id, $ticket_id ) {
		$structure = imap_fetchstructure( $connection, $email_id );

		if ( ! isset( $structure->parts ) ) {
			return;
		}

		foreach ( $structure->parts as $part_id => $part ) {
			if ( $part->ifdisposition && strtolower( $part->disposition ) === 'attachment' ) {
				self::save_attachment( $connection, $email_id, $part_id + 1, $ticket_id, $part );
			}
		}
	}

	/**
	 * Save attachment from email
	 */
	private static function save_attachment( $connection, $email_id, $part_id, $ticket_id, $part ) {
		try {
			// Get file content
			$data = imap_fetchbody( $connection, $email_id, $part_id );

			// Handle encoding
			if ( $part->encoding == 3 ) { // BASE64
				$data = base64_decode( $data );
			} elseif ( $part->encoding == 4 ) { // QUOTED-PRINTABLE
				$data = quoted_printable_decode( $data );
			}

			// Get filename
			$filename = 'attachment';
			if ( $part->dparameters ) {
				foreach ( $part->dparameters as $param ) {
					if ( $param->attribute === 'filename' ) {
						$filename = $param->value;
						break;
					}
				}
			}

			$filename = sanitize_file_name( $filename );

			// Check file size
			if ( strlen( $data ) > 10 * 1024 * 1024 ) {
				error_log( "LGP: Attachment too large: $filename" );
				return;
			}

			// Create uploads directory
			$upload_dir = wp_upload_dir();
			$lgp_dir    = $upload_dir['basedir'] . '/lgp-attachments/';

			if ( ! is_dir( $lgp_dir ) ) {
				wp_mkdir_p( $lgp_dir );
			}

			// Save file
			$new_filename = $ticket_id . '-' . time() . '-' . $filename;
			$filepath     = $lgp_dir . $new_filename;

			if ( file_put_contents( $filepath, $data ) === false ) {
				error_log( "LGP: Failed to save attachment: $filename" );
				return;
			}

			// Get MIME type
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = finfo_file( $finfo, $filepath );
			finfo_close( $finfo );

			// Store in database (lgp_ticket_attachments)
			global $wpdb;
			$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';

			// Move file into protected path format and store relative path
			$upload_base = wp_upload_dir();
			$dest_dir    = trailingslashit( $upload_base['basedir'] ) . 'lgp-attachments/' . $ticket_id;
			if ( ! file_exists( $dest_dir ) ) {
				wp_mkdir_p( $dest_dir );
			}
			$ht = $dest_dir . '/.htaccess';
			if ( ! file_exists( $ht ) ) {
				file_put_contents( $ht, "deny from all\n" );
			}

			$ext    = pathinfo( $filename, PATHINFO_EXTENSION );
			$unique = md5( uniqid( $filename, true ) ) . ( $ext ? ".{$ext}" : '' );
			$dest   = $dest_dir . '/' . $unique;
			rename( $filepath, $dest );
			$relative = 'lgp-attachments/' . $ticket_id . '/' . $unique;

			$wpdb->insert(
				$attachments_table,
				array(
					'ticket_id'   => $ticket_id,
					'file_name'   => $filename,
					'file_type'   => $mime ?: 'application/octet-stream',
					'file_size'   => strlen( $data ),
					'file_path'   => $relative,
					'uploaded_by' => 0,
					'created_at'  => current_time( 'mysql', true ),
				),
				array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
			);

			error_log( "LGP: Saved attachment: $filename ($new_filename)" );
		} catch ( Exception $e ) {
			error_log( 'LGP: Attachment save error: ' . $e->getMessage() );
		}
	}

	/**
	 * Send confirmation email
	 */
	private static function send_confirmation_email( $company, $user_id, $ticket_id ) {
		// Get company email
		$primary_email = get_field( 'primary_contact_email', $company->ID );

		if ( ! $primary_email ) {
			return;
		}

		$site_url = site_url();

		$subject = "Ticket Created: #{$ticket_id}";
		$message = sprintf(
			"Your support request has been received.\n\n" .
				"Ticket: #%d\n" .
				"Status: Open\n\n" .
				"You can view this ticket at:\n" .
				"%s\n\n" .
				"We will respond as soon as possible.\n\n" .
				"Best regards,\n" .
				'LounGenie Support Team',
			$ticket_id,
			$site_url . '/portal?tickets=1&ticket=' . $ticket_id
		);

		$graph_settings = get_option( self::$graph_option_key, array() );
		$use_graph      = ! empty( $graph_settings['outbound_via_graph'] ) && self::is_graph_enabled();

		if ( $use_graph ) {
			try {
				if ( ! class_exists( 'LGP_Graph_Client' ) ) {
					require_once LGP_PLUGIN_DIR . 'includes/class-lgp-graph-client.php';
				}
				$client = new LGP_Graph_Client( $graph_settings );
				$client->send_mail( $primary_email, $subject, nl2br( esc_html( $message ) ) );
				return;
			} catch ( Exception $e ) {
				error_log( 'LGP Graph sendMail fallback to wp_mail: ' . $e->getMessage() );
			}
		}

		wp_mail( $primary_email, $subject, $message );
	}

	/**
	 * Get portal page ID
	 */
	private static function get_portal_page_id() {
		$pages = get_posts(
			array(
				'post_type'  => 'page',
				'meta_query' => array(
					array(
						'key'   => '_wp_page_template',
						'value' => 'portal',
					),
				),
			)
		);

		return ! empty( $pages ) ? $pages[0]->ID : 0;
	}

	/**
	 * Get email settings
	 */
	public static function get_settings() {
		return get_option(
			self::$option_key,
			array(
				'pop3_server'   => '',
				'pop3_username' => '',
				'pop3_password' => '',
			)
		);
	}

	/**
	 * Update email settings
	 */
	public static function update_settings( $settings ) {
		return update_option( self::$option_key, $settings );
	}
}
