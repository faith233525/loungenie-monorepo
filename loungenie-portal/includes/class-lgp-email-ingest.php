<?php
/**
 * Email Ingest Handler for Shared Mailbox
 *
 * Handles fetching emails from shared mailbox and creating tickets.
 *
 * @package loungenie-portal
 */

class LGP_Email_Ingest {

	/**
	 * Graph Client instance
	 *
	 * @var LGP_Graph_Client
	 */
	private $graph;

	/**
	 * Logger instance
	 *
	 * @var LGP_Logger
	 */
	private $logger;

	/**
	 * Shared mailbox email
	 *
	 * @var string
	 */
	private $mailbox;

	/**
	 * Initialize email ingest handler
	 */
	public function __construct() {
		$this->graph   = new LGP_Graph_Client();
		$this->logger  = new LGP_Logger( 'email-ingest' );
		$this->mailbox = get_option( 'lgp_shared_mailbox' );
	}

	/**
	 * Sync emails from shared mailbox
	 *
	 * Uses delta sync pattern for efficiency.
	 *
	 * @return array Sync stats
	 */
	public function sync_messages() {
		$stats = array(
			'total'      => 0,
			'created'    => 0,
			'updated'    => 0,
			'skipped'    => 0,
			'errors'     => 0,
			'error_list' => array(),
		);

		try {
			// Get delta token
			$delta_token = get_transient( 'lgp_email_delta_token' );

			// Fetch messages (normalized shape from LGP_Graph_Client)
			$response = $this->graph->get_messages( $delta_token );

			if ( ! $response || empty( $response['messages'] ) ) {
				$this->logger->info( 'No new messages' );
				return $stats;
			}

			// Process each message
			foreach ( $response['messages'] as $message ) {
				++$stats['total'];

				try {
					$result = $this->process_message( $message );
					if ( 'created' === $result ) {
						++$stats['created'];
					} elseif ( 'updated' === $result ) {
						++$stats['updated'];
					} elseif ( 'skipped' === $result ) {
						++$stats['skipped'];
					}
				} catch ( Exception $e ) {
					++$stats['errors'];
					$stats['error_list'][] = $e->getMessage();
					$this->logger->error(
						'Failed to process message',
						array(
							'message_id' => $message['id'] ?? 'unknown',
							'error'      => $e->getMessage(),
						)
					);
				}
			}

			// Update delta token if present
			if ( ! empty( $response['delta_token'] ) ) {
				set_transient( 'lgp_email_delta_token', $response['delta_token'], 24 * HOUR_IN_SECONDS );
			}

			$this->logger->info( 'Sync complete', $stats );

		} catch ( Exception $e ) {
			$this->logger->error( 'Sync failed', array( 'error' => $e->getMessage() ) );
			++$stats['errors'];
			$stats['error_list'][] = $e->getMessage();
		}

		return $stats;
	}

	/**
	 * Process individual message
	 *
	 * @param array $message Message from Graph API
	 * @return string 'created', 'updated', or 'skipped'
	 * @throws Exception
	 */
	private function process_message( $message ) {
		// Check if already processed
		$ticket_id = $this->get_ticket_for_message( $message['id'] );

		if ( $ticket_id ) {
			// Message already has a ticket - possibly a reply
			$this->handle_reply( $ticket_id, $message );
			return 'updated';
		}

		// Check if this is a reply to an existing conversation
		if ( ! empty( $message['parentMessageId'] ) || ! empty( $message['conversationId'] ) ) {
			$existing_ticket = $this->find_ticket_by_conversation(
				$message['conversationId'] ?? null,
				$message['parentMessageId'] ?? null
			);

			if ( $existing_ticket ) {
				$this->handle_reply( $existing_ticket, $message );
				return 'updated';
			}
		}

		// New ticket from email
		$ticket_id = $this->create_ticket_from_email( $message );

		// Mark message as processed
		update_post_meta( $ticket_id, '_email_message_id', $message['id'] );
		update_post_meta( $ticket_id, '_email_conversation_id', $message['conversationId'] ?? '' );
		update_post_meta( $ticket_id, '_email_internet_message_id', $message['internetMessageId'] ?? '' );

		// Mark as read in mailbox
		try {
			$this->graph->mark_as_read( $message['id'] );
		} catch ( Exception $e ) {
			$this->logger->warning( 'Could not mark message as read', array( 'error' => $e->getMessage() ) );
		}

		return 'created';
	}

	/**
	 * Create ticket from email message
	 *
	 * @param array $message Message from Graph API
	 * @return int Ticket post ID
	 * @throws Exception
	 */
	private function create_ticket_from_email( $message ) {
		// Extract sender info (support normalized or raw Graph shape)
		$sender_email = '';
		$sender_name  = '';
		if ( isset( $message['from'] ) && is_string( $message['from'] ) ) {
			$sender_email = $message['from'];
			$sender_name  = $message['from_name'] ?? $sender_email;
		} else {
			$from         = $message['from']['emailAddress'] ?? array();
			$sender_email = $from['address'] ?? '';
			$sender_name  = $from['name'] ?? $sender_email;
		}

		if ( empty( $sender_email ) ) {
			throw new Exception( 'No sender address in message' );
		}

		// Find or create contact
		$contact_id = $this->get_or_create_contact( $sender_email, $sender_name );

		// Find associated company from contact
		$company_id = $this->get_contact_company( $contact_id );

		// Prepare ticket data
		$body_content = '';
		if ( ! empty( $message['body'] ) && is_string( $message['body'] ) ) {
			$body_content = $message['body'];
		} elseif ( isset( $message['body']['content'] ) ) {
			$body_content = $message['body']['content'];
		} else {
			$body_content = $message['bodyPreview'] ?? '(No content)';
		}

		$ticket_data = array(
			'post_type'    => 'ticket',
			'post_status'  => 'open',
			'post_title'   => $message['subject'] ?? '(No Subject)',
			'post_content' => $body_content,
			'post_author'  => 1,
		);

		// Create ticket
		$ticket_id = wp_insert_post( $ticket_data );

		if ( ! $ticket_id ) {
			throw new Exception( 'Failed to create ticket post' );
		}

		// Set ticket metadata
		update_post_meta( $ticket_id, '_contact_id', $contact_id );
		update_post_meta( $ticket_id, '_company_id', $company_id );
		update_post_meta( $ticket_id, '_email_source', true );
		update_post_meta( $ticket_id, '_sender_email', $sender_email );
		update_post_meta( $ticket_id, '_received_date', $message['receivedDateTime'] ?? current_time( 'mysql' ) );

		// Handle attachments
		if ( ! empty( $message['hasAttachments'] ) ) {
			$this->process_attachments( $ticket_id, $message['id'] );
		}

		$this->logger->info(
			'Ticket created from email',
			array(
				'ticket_id'       => $ticket_id,
				'sender'          => $sender_email,
				'message_id'      => $message['id'],
				'has_attachments' => ! empty( $message['hasAttachments'] ),
			)
		);

		return $ticket_id;
	}

	/**
	 * Handle email reply to existing ticket
	 *
	 * @param int   $ticket_id Ticket ID
	 * @param array $message Message from Graph API
	 * @return int Reply post ID
	 */
	private function handle_reply( $ticket_id, $message ) {
		// Check if reply already exists
		$existing_reply = get_comments(
			array(
				'post_id'    => $ticket_id,
				'meta_key'   => '_email_message_id',
				'meta_value' => $message['id'],
				'count'      => true,
			)
		);

		if ( $existing_reply > 0 ) {
			return null; // Already processed
		}

		// Extract sender info
		$from         = $message['from']['emailAddress'] ?? array();
		$sender_email = $from['address'] ?? 'unknown@example.com';

		// Create reply comment
		$reply_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $ticket_id,
				'comment_content'      => $message['body']['content'] ?? $message['bodyPreview'] ?? '',
				'comment_author'       => $from['name'] ?? $sender_email,
				'comment_author_email' => $sender_email,
				'comment_type'         => 'ticket_reply',
				'comment_meta'         => array(
					'_email_source'        => true,
					'_email_message_id'    => $message['id'],
					'_received_date'       => $message['receivedDateTime'] ?? current_time( 'mysql' ),
					'_conversation_id'     => $message['conversationId'] ?? '',
					'_internet_message_id' => $message['internetMessageId'] ?? '',
				),
			)
		);

		if ( ! $reply_id ) {
			throw new Exception( 'Failed to create reply comment' );
		}

		// Handle attachments for reply
		if ( ! empty( $message['hasAttachments'] ) ) {
			$this->process_reply_attachments( $reply_id, $message['id'] );
		}

		// Update ticket timestamp
		update_post_meta( $ticket_id, '_last_reply_date', $message['receivedDateTime'] ?? current_time( 'mysql' ) );

		// Change status if needed
		if ( 'closed' === get_post_status( $ticket_id ) ) {
			wp_update_post(
				array(
					'ID'          => $ticket_id,
					'post_status' => 'open',
				)
			);
		}

		$this->logger->info(
			'Reply added to ticket',
			array(
				'ticket_id'  => $ticket_id,
				'reply_id'   => $reply_id,
				'from'       => $sender_email,
				'message_id' => $message['id'],
			)
		);

		return $reply_id;
	}

	/**
	 * Process attachments from email message
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $message_id Graph message ID
	 * @throws Exception
	 */
	private function process_attachments( $ticket_id, $message_id ) {
		try {
			$attachments = $this->graph->get_attachments_with_content( $message_id );

			foreach ( $attachments as $attachment ) {
				// Skip if not a file attachment
				if ( '#microsoft.graph.fileAttachment' !== $attachment['@odata.type'] ) {
					continue;
				}

				// Download and attach to ticket
				$this->attach_file_to_ticket( $ticket_id, $attachment );
			}
		} catch ( Exception $e ) {
			$this->logger->warning(
				'Failed to process attachments',
				array(
					'ticket_id' => $ticket_id,
					'error'     => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Attach file to ticket
	 *
	 * @param int   $ticket_id Ticket ID
	 * @param array $attachment Attachment from Graph API
	 */
	private function attach_file_to_ticket( $ticket_id, $attachment ) {
		// Decode content
		$content = base64_decode( $attachment['contentBytes'], true );

		if ( ! $content ) {
			throw new Exception( 'Failed to decode attachment content' );
		}

		// Create upload directory
		$upload_dir = wp_upload_dir();
		$ticket_dir = $upload_dir['basedir'] . '/tickets/' . $ticket_id;

		if ( ! is_dir( $ticket_dir ) ) {
			wp_mkdir_p( $ticket_dir );
		}

		// Save file
		$file_path     = $ticket_dir . '/' . sanitize_file_name( $attachment['name'] );
		$bytes_written = file_put_contents( $file_path, $content );

		if ( ! $bytes_written ) {
			throw new Exception( 'Failed to save attachment file' );
		}

		// Create attachment post
		$attachment_post_id = wp_insert_attachment(
			array(
				'post_title'     => $attachment['name'],
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $ticket_id,
				'post_mime_type' => $attachment['contentType'] ?? 'application/octet-stream',
				'guid'           => wp_upload_dir()['baseurl'] . '/tickets/' . $ticket_id . '/' . sanitize_file_name( $attachment['name'] ),
			)
		);

		// Generate metadata
		wp_update_attachment_metadata( $attachment_post_id, wp_generate_attachment_metadata( $attachment_post_id, $file_path ) );

		$this->logger->debug(
			'Attachment saved',
			array(
				'ticket_id'     => $ticket_id,
				'attachment_id' => $attachment_post_id,
				'filename'      => $attachment['name'],
				'size'          => $bytes_written,
			)
		);
	}

	/**
	 * Process reply attachments
	 *
	 * @param int    $reply_id Comment ID
	 * @param string $message_id Graph message ID
	 */
	private function process_reply_attachments( $reply_id, $message_id ) {
		try {
			$attachments = $this->graph->get_attachments_with_content( $message_id );

			foreach ( $attachments as $attachment ) {
				if ( '#microsoft.graph.fileAttachment' !== $attachment['@odata.type'] ) {
					continue;
				}

				// Save attachment with reference to comment
				$content = base64_decode( $attachment['contentBytes'], true );

				if ( ! $content ) {
					continue;
				}

				// Create upload directory
				$upload_dir = wp_upload_dir();
				$reply_dir  = $upload_dir['basedir'] . '/ticket-replies/' . $reply_id;

				if ( ! is_dir( $reply_dir ) ) {
					wp_mkdir_p( $reply_dir );
				}

				$file_path = $reply_dir . '/' . sanitize_file_name( $attachment['name'] );
				file_put_contents( $file_path, $content );

				// Store reference in comment meta
				add_comment_meta(
					$reply_id,
					'_attachment_file',
					array(
						'name'     => $attachment['name'],
						'path'     => $file_path,
						'size'     => strlen( $content ),
						'mime'     => $attachment['contentType'] ?? 'application/octet-stream',
						'saved_at' => current_time( 'mysql' ),
					)
				);
			}
		} catch ( Exception $e ) {
			$this->logger->warning(
				'Failed to process reply attachments',
				array(
					'reply_id' => $reply_id,
					'error'    => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Get or create contact from email
	 *
	 * @param string $email Email address
	 * @param string $name Contact name
	 * @return int Contact ID
	 */
	private function get_or_create_contact( $email, $name ) {
		// Search for existing contact with this email
		$posts = get_posts(
			array(
				'post_type'  => 'contact',
				'meta_key'   => '_contact_email',
				'meta_value' => $email,
				'fields'     => 'ids',
			)
		);

		if ( ! empty( $posts ) ) {
			return (int) $posts[0];
		}

		// Create new contact
		$contact_id = wp_insert_post(
			array(
				'post_type'   => 'contact',
				'post_status' => 'publish',
				'post_title'  => $name,
			)
		);

		update_post_meta( $contact_id, '_contact_email', $email );
		update_post_meta( $contact_id, '_email_source', true );

		$this->logger->info(
			'Contact created from email',
			array(
				'contact_id' => $contact_id,
				'email'      => $email,
				'name'       => $name,
			)
		);

		return $contact_id;
	}

	/**
	 * Get company for contact
	 *
	 * @param int $contact_id Contact ID
	 * @return int Company ID or 0
	 */
	private function get_contact_company( $contact_id ) {
		return (int) get_post_meta( $contact_id, '_company_id', true );
	}

	/**
	 * Get ticket for a message ID
	 *
	 * @param string $message_id Graph message ID
	 * @return int Ticket ID or 0
	 */
	private function get_ticket_for_message( $message_id ) {
		$posts = get_posts(
			array(
				'post_type'  => 'ticket',
				'meta_key'   => '_email_message_id',
				'meta_value' => $message_id,
				'fields'     => 'ids',
			)
		);

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}

	/**
	 * Find existing ticket by conversation ID
	 *
	 * @param string $conversation_id Conversation ID
	 * @param string $parent_message_id Parent message ID
	 * @return int Ticket ID or 0
	 */
	private function find_ticket_by_conversation( $conversation_id = null, $parent_message_id = null ) {
		if ( ! empty( $conversation_id ) ) {
			$posts = get_posts(
				array(
					'post_type'  => 'ticket',
					'meta_key'   => '_email_conversation_id',
					'meta_value' => $conversation_id,
					'fields'     => 'ids',
				)
			);

			if ( ! empty( $posts ) ) {
				return (int) $posts[0];
			}
		}

		if ( ! empty( $parent_message_id ) ) {
			$posts = get_posts(
				array(
					'post_type'  => 'ticket',
					'meta_key'   => '_email_internet_message_id',
					'meta_value' => $parent_message_id,
					'fields'     => 'ids',
				)
			);

			if ( ! empty( $posts ) ) {
				return (int) $posts[0];
			}
		}

		return 0;
	}
}
