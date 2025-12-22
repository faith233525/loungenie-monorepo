<?php
/**
 * Email Reply Handler
 *
 * Handles sending replies via Graph API to the shared mailbox
 * and detecting replies that came via Outlook.
 *
 * @package loungenie-portal
 */

class LGP_Email_Reply {

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
	 * Initialize reply handler
	 */
	public function __construct() {
		$this->graph   = new LGP_Graph_Client();
		$this->logger  = new LGP_Logger( 'email-reply' );
		$this->mailbox = get_option( 'lgp_shared_mailbox' );
	}

	/**
	 * Send reply to ticket via email
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $reply_content Reply content (HTML)
	 * @param int    $author_id WordPress user ID
	 * @param array  $attachments Optional attachments
	 * @return int Comment ID
	 * @throws Exception
	 */
	public function send_reply( $ticket_id, $reply_content, $author_id = 1, $attachments = array() ) {
		// Get ticket details
		$ticket = get_post( $ticket_id );
		if ( ! $ticket ) {
			throw new Exception( 'Ticket not found' );
		}

		// Get sender email and details
		$sender_email = get_post_meta( $ticket_id, '_sender_email', true );
		if ( ! $sender_email ) {
			throw new Exception( 'No sender email found for ticket' );
		}

		// Get email metadata for reply threading
		$conversation_id     = get_post_meta( $ticket_id, '_email_conversation_id', true );
		$internet_message_id = get_post_meta( $ticket_id, '_email_internet_message_id', true );

		// Build message payload with proper threading
		$message_payload = $this->build_reply_message(
			$sender_email,
			$ticket->post_title,
			$reply_content,
			$conversation_id,
			$internet_message_id,
			$attachments
		);

		// Send via Graph API
		$graph_response = $this->graph->send_mail_message( $this->mailbox, $message_payload );

		if ( ! $graph_response || ! empty( $graph_response['error'] ) ) {
			$error = $graph_response['error']['message'] ?? 'Unknown error';
			throw new Exception( 'Failed to send email: ' . $error );
		}

		// Create WordPress comment record
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $ticket_id,
				'comment_content'      => $reply_content,
				'comment_author'       => get_the_author_meta( 'display_name', $author_id ),
				'comment_author_email' => get_the_author_meta( 'user_email', $author_id ),
				'comment_author_url'   => get_the_author_meta( 'user_url', $author_id ),
				'comment_type'         => 'ticket_reply',
				'user_id'              => $author_id,
			)
		);

		if ( ! $comment_id ) {
			throw new Exception( 'Failed to create comment' );
		}

		// Store email metadata in comment
		update_comment_meta( $comment_id, '_email_sent', true );
		update_comment_meta( $comment_id, '_reply_to_email', $sender_email );
		update_comment_meta( $comment_id, '_sent_via_portal', true );
		update_comment_meta( $comment_id, '_sent_timestamp', current_time( 'mysql' ) );

		// Handle local attachments
		if ( ! empty( $attachments ) ) {
			$this->attach_local_files( $comment_id, $attachments );
		}

		$this->logger->info(
			'Reply sent to ticket',
			array(
				'ticket_id'   => $ticket_id,
				'comment_id'  => $comment_id,
				'to'          => $sender_email,
				'author_id'   => $author_id,
				'attachments' => count( $attachments ),
			)
		);

		return $comment_id;
	}

	/**
	 * Build reply message with proper threading
	 *
	 * @param string $to_email Recipient email
	 * @param string $subject Original subject
	 * @param string $html_body Reply body (HTML)
	 * @param string $conversation_id Conversation ID
	 * @param string $in_reply_to Internet Message ID to reply to
	 * @param array  $attachments File attachments
	 * @return array Message payload for Graph API
	 */
	private function build_reply_message( $to_email, $subject, $html_body, $conversation_id = '', $in_reply_to = '', $attachments = array() ) {
		$graph_attachments = array();

		// Process attachments
		foreach ( $attachments as $att ) {
			if ( empty( $att['path'] ) ) {
				continue;
			}

			// Read file
			$file_content = file_get_contents( $att['path'] );
			if ( ! $file_content ) {
				$this->logger->warning( 'Failed to read attachment file', array( 'path' => $att['path'] ) );
				continue;
			}

			$graph_attachments[] = array(
				'@odata.type'  => '#microsoft.graph.fileAttachment',
				'name'         => $att['name'] ?? basename( $att['path'] ),
				'contentBytes' => base64_encode( $file_content ),
			);
		}

		// Build message headers for threading
		$additional_headers = array();

		if ( ! empty( $in_reply_to ) ) {
			$additional_headers[] = array(
				'name'  => 'In-Reply-To',
				'value' => $in_reply_to,
			);
		}

		if ( ! empty( $conversation_id ) ) {
			$additional_headers[] = array(
				'name'  => 'X-Conversation-ID',
				'value' => $conversation_id,
			);
		}

		$payload = array(
			'message'         => array(
				'subject'                => 'Re: ' . $subject,
				'body'                   => array(
					'contentType' => 'HTML',
					'content'     => $html_body,
				),
				'toRecipients'           => array(
					array(
						'emailAddress' => array(
							'address' => $to_email,
						),
					),
				),
				'from'                   => array(
					'emailAddress' => array(
						'address' => $this->mailbox,
					),
				),
				'internetMessageHeaders' => $additional_headers,
			),
			'saveToSentItems' => true,
		);

		if ( ! empty( $graph_attachments ) ) {
			$payload['message']['attachments'] = $graph_attachments;
		}

		return $payload;
	}

	/**
	 * Attach local files to comment
	 *
	 * @param int   $comment_id Comment ID
	 * @param array $attachments Attachments with 'path' and 'name'
	 */
	private function attach_local_files( $comment_id, $attachments ) {
		foreach ( $attachments as $att ) {
			if ( empty( $att['path'] ) || ! file_exists( $att['path'] ) ) {
				continue;
			}

			add_comment_meta(
				$comment_id,
				'_attachment',
				array(
					'name' => $att['name'] ?? basename( $att['path'] ),
					'path' => $att['path'],
					'size' => filesize( $att['path'] ),
				)
			);
		}
	}

	/**
	 * Detect and record Outlook replies
	 *
	 * This is called periodically to check for replies sent via Outlook
	 * to the shared mailbox directly.
	 *
	 * @return int Number of replies detected
	 */
	public function detect_outlook_replies() {
		$count = 0;

		// Get all open tickets with email source
		$tickets = get_posts(
			array(
				'post_type'      => 'ticket',
				'posts_per_page' => -1,
				'meta_key'       => '_email_source',
				'meta_value'     => true,
				'post_status'    => array( 'open', 'waiting' ),
			)
		);

		foreach ( $tickets as $ticket ) {
			$conversation_id = get_post_meta( $ticket->ID, '_email_conversation_id', true );

			if ( ! $conversation_id ) {
				continue;
			}

			// Check for new messages in conversation
			try {
				$new_count = $this->check_conversation_for_replies( $ticket->ID, $conversation_id );
				$count    += $new_count;
			} catch ( Exception $e ) {
				$this->logger->warning(
					'Failed to check conversation',
					array(
						'ticket_id'       => $ticket->ID,
						'conversation_id' => $conversation_id,
						'error'           => $e->getMessage(),
					)
				);
			}
		}

		return $count;
	}

	/**
	 * Check conversation for new replies via Outlook
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $conversation_id Conversation ID from email
	 * @return int Number of new replies found
	 * @throws Exception
	 */
	private function check_conversation_for_replies( $ticket_id, $conversation_id ) {
		$count = 0;

		// Get conversation messages from Graph API
		$path = '/users/' . rawurlencode( $this->mailbox ) . '/conversations/' . rawurlencode( $conversation_id ) . '/threads';

		try {
			// Note: This requires Conversations.Read permission
			$response = $this->graph->request( 'GET', $path );

			if ( empty( $response['value'] ) ) {
				return 0;
			}

			// Process each thread
			foreach ( $response['value'] as $thread ) {
				// Get messages in thread
				$thread_path     = $path . '/' . rawurlencode( $thread['id'] ) . '/posts';
				$thread_response = $this->graph->request( 'GET', $thread_path );

				if ( empty( $thread_response['value'] ) ) {
					continue;
				}

				// Check for new messages
				foreach ( $thread_response['value'] as $post ) {
					// Skip if already recorded
					$existing = get_comments(
						array(
							'post_id'    => $ticket_id,
							'meta_key'   => '_email_internet_message_id',
							'meta_value' => $post['id'],
							'count'      => true,
						)
					);

					if ( $existing > 0 ) {
						continue;
					}

					// Skip system messages
					if ( $this->is_system_message( $post ) ) {
						continue;
					}

					// Add as comment
					$this->record_outlook_reply( $ticket_id, $post );
					$count++;
				}
			}
		} catch ( Exception $e ) {
			$this->logger->warning( 'Could not fetch conversation threads', array( 'error' => $e->getMessage() ) );
		}

		return $count;
	}

	/**
	 * Record Outlook reply as WordPress comment
	 *
	 * @param int   $ticket_id Ticket ID
	 * @param array $post Email post from Graph API
	 */
	private function record_outlook_reply( $ticket_id, $post ) {
		$from = $post['from']['emailAddress'] ?? array();

		// Create comment
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $ticket_id,
				'comment_content'      => $post['body']['content'] ?? $post['bodyPreview'] ?? '',
				'comment_author'       => $from['name'] ?? 'Unknown',
				'comment_author_email' => $from['address'] ?? 'unknown@example.com',
				'comment_type'         => 'ticket_reply',
			)
		);

		if ( $comment_id ) {
			// Mark as from Outlook
			update_comment_meta( $comment_id, '_email_source', true );
			update_comment_meta( $comment_id, '_sent_via_outlook', true );
			update_comment_meta( $comment_id, '_email_internet_message_id', $post['id'] );
			update_comment_meta( $comment_id, '_received_date', $post['receivedDateTime'] ?? current_time( 'mysql' ) );

			$this->logger->info(
				'Outlook reply recorded',
				array(
					'ticket_id'  => $ticket_id,
					'comment_id' => $comment_id,
					'from'       => $from['address'] ?? 'unknown',
				)
			);
		}
	}

	/**
	 * Check if message is a system message
	 *
	 * @param array $post Email post
	 * @return bool
	 */
	private function is_system_message( $post ) {
		$sender  = $post['from']['emailAddress']['address'] ?? '';
		$content = $post['body']['content'] ?? '';

		// Skip read receipts and delivery notifications
		if ( strpos( $content, 'read receipt' ) !== false || strpos( $content, 'delivery notification' ) !== false ) {
			return true;
		}

		// Skip automated messages
		if ( strpos( $sender, 'noreply' ) !== false || strpos( $sender, 'no-reply' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete reply from ticket
	 *
	 * @param int $comment_id Comment ID
	 * @return bool Success
	 * @throws Exception
	 */
	public function delete_reply( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			throw new Exception( 'Comment not found' );
		}

		// Check if this was sent via email
		$sent_via_email = get_comment_meta( $comment_id, '_email_sent', true );

		if ( $sent_via_email ) {
			// Would need to implement email deletion or marking as deleted
			// For now, just mark as deleted in portal
			update_comment_meta( $comment_id, '_deleted_in_portal', true );

			$this->logger->info(
				'Email reply marked as deleted',
				array(
					'comment_id' => $comment_id,
					'ticket_id'  => $comment->comment_post_ID,
				)
			);
		}

		// Delete comment
		wp_delete_comment( $comment_id, true );

		return true;
	}
}
