<?php
/**
 * LounGenie Portal - Shared Mailbox Integration via Graph API
 * Handles inbound/outbound mail for support@poolsafeinc.com
 *
 * App-only OAuth (client credentials flow):
 * - Fetches messages from shared mailbox via Graph
 * - Converts emails to tickets with threading metadata
 * - Tracks replies from portal and Outlook
 * - Downloads attachments
 * - Sends replies via shared mailbox
 *
 * @package LounGenie Portal
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared mailbox integration via Microsoft Graph API.
 */
class LGP_Shared_Mailbox {

	/**
	 * Initialize shared mailbox integration.
	 *
	 * @return void
	 */
	public static function init() {
		// Periodic sync of shared mailbox (via wp-cron or scheduled action).
		add_action( 'lgp_sync_shared_mailbox', array( __CLASS__, 'sync_inbox' ) );

		// Hook to send replies via shared mailbox.
		add_action( 'lgp_ticket_reply_added', array( __CLASS__, 'send_reply_via_graph' ), 10, 3 );

		// Register settings.
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Get Graph client configured for shared mailbox
	 */
	public static function get_graph_client() {
		$settings = self::get_settings();
		if ( empty( $settings['tenant_id'] ) || empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) ) {
			throw new Exception( 'Shared mailbox Graph settings not configured' );
		}

		if ( ! class_exists( 'LGP_Graph_Client' ) ) {
			require_once __DIR__ . '/class-lgp-graph-client.php';
		}

		return new LGP_Graph_Client( $settings );
	}

	/**
	 * Sync shared mailbox inbox: fetch new messages, create tickets
	 * Uses delta sync if supported
	 */
	public static function sync_inbox() {
		try {
			$client = self::get_graph_client();

			// Get delta token from last sync
			$delta_token = get_option( 'lgp_graph_delta_token' );

			// Fetch messages (delta-aware)
			$response = $client->get_messages( $delta_token );

			if ( empty( $response['value'] ) ) {
				return;
			}

			foreach ( $response['value'] as $message ) {
				// Idempotency check
				$internet_id = $message['internetMessageId'] ?? '';
				if ( ! self::is_message_processed( $internet_id ) ) {
					self::ingest_message( $message, $client );
					self::mark_message_processed( $internet_id );
				}
			}

			// Store new delta token for next sync
			if ( ! empty( $response['@odata.deltaLink'] ) ) {
				update_option( 'lgp_graph_delta_token', $response['@odata.deltaLink'] );
			}

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'shared_mailbox_sync_complete',
					0,
					array( 'message_count' => count( $response['value'] ?? array() ) )
				);
			}
		} catch ( Exception $e ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_error( 'Shared mailbox sync failed: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Ingest a single Graph message and create a ticket
	 *
	 * @param array  $message Graph message object
	 * @param object $client LGP_Graph_Client instance
	 */
	public static function ingest_message( $message, $client ) {
		// Extract sender email
		$from_email = $message['from']['emailAddress']['address'] ?? '';
		if ( empty( $from_email ) ) {
			return;
		}

		// Find company by email domain
		$company_id = self::find_company_by_domain( $from_email );
		if ( ! $company_id ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					get_current_user_id(),
					'shared_mailbox_unknown_domain',
					0,
					array( 'from' => $from_email )
				);
			}
			return;
		}

		global $wpdb;

		// Extract threading metadata
		$internet_id     = $message['internetMessageId'] ?? '';
		$conversation_id = $message['conversationId'] ?? '';
		$parent_ref      = $message['parentMessageId'] ?? '';

		// Check if this is a reply to an existing ticket
		$ticket_id = self::find_ticket_by_conversation( $conversation_id, $company_id );

		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( $ticket_id ) {
				// Add reply to existing ticket
				self::add_reply_to_ticket( $ticket_id, $message, $from_email );
			} else {
				// Create new ticket from incoming email
				$ticket_id = self::create_ticket_from_message( $company_id, $message, $internet_id, $conversation_id );
			}

			// Download attachments from message
			if ( ! empty( $message['hasAttachments'] ) && $ticket_id ) {
				self::download_attachments( $ticket_id, $message['id'], $client );
			}

			$wpdb->query( 'COMMIT' );
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_error( 'Failed to ingest message: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Create new ticket from incoming email message
	 *
	 * @param int    $company_id Company ID
	 * @param array  $message Graph message
	 * @param string $internet_id Unique message ID
	 * @param string $conversation_id Thread conversation ID
	 * @return int Ticket ID
	 */
	private static function create_ticket_from_message( $company_id, $message, $internet_id, $conversation_id ) {
		global $wpdb;

		// Create service request
		$requests_table = $wpdb->prefix . 'lgp_service_requests';
		$priority       = self::detect_priority( $message['subject'] ?? '', $message['bodyPreview'] ?? '' );

		$inserted = $wpdb->insert(
			$requests_table,
			array(
				'company_id'   => $company_id,
				'unit_id'      => null,
				'request_type' => 'email',
				'priority'     => $priority,
				'status'       => 'pending',
				'notes'        => self::extract_notes( $message ),
			)
		);

		if ( ! $inserted ) {
			throw new Exception( 'Failed to insert service request' );
		}

		$request_id = $wpdb->insert_id;

		// Create ticket with threading metadata
		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$wpdb->insert(
			$tickets_table,
			array(
				'service_request_id' => $request_id,
				'status'             => 'open',
				'email_reference'    => $internet_id,
				'thread_history'     => wp_json_encode(
					array(
						'conversation_id' => $conversation_id,
						'messages'        => array(
							array(
								'internet_id'     => $internet_id,
								'from'            => $message['from']['emailAddress']['address'] ?? '',
								'from_name'       => $message['from']['emailAddress']['name'] ?? '',
								'received_time'   => $message['receivedDateTime'] ?? gmdate( 'c' ),
								'replied_by_user' => null, // Inbound only, no portal user
								'replied_via'     => 'outlook', // Or 'portal'
								'type'            => 'inbound',
								'subject'         => $message['subject'] ?? '',
							),
						),
					)
				),
			)
		);

		$ticket_id = $wpdb->insert_id;

		if ( class_exists( 'LGP_Logger' ) ) {
			LGP_Logger::log_event(
				get_current_user_id(),
				'shared_mailbox_ticket_created',
				$ticket_id,
				array(
					'from'            => $message['from']['emailAddress']['address'] ?? '',
					'company_id'      => $company_id,
					'conversation_id' => $conversation_id,
				)
			);
		}

		return (int) $ticket_id;
	}

	/**
	 * Add reply to existing ticket from Outlook
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param array  $message Graph message
	 * @param string $from_email Sender email
	 */
	private static function add_reply_to_ticket( $ticket_id, $message, $from_email ) {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$ticket        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $ticket_id ) );

		if ( ! $ticket ) {
			throw new Exception( 'Ticket not found' );
		}

		$thread_history = json_decode( $ticket->thread_history ?? '{}', true );
		if ( ! is_array( $thread_history ) ) {
			$thread_history = array();
		}

		if ( ! isset( $thread_history['messages'] ) ) {
			$thread_history['messages'] = array();
		}

		// Map sender email to portal support user (optional)
		$portal_user = self::find_portal_user_by_email( $from_email );

		// Add message to thread
		$thread_history['messages'][] = array(
			'internet_id'     => $message['internetMessageId'] ?? '',
			'from'            => $from_email,
			'from_name'       => $message['from']['emailAddress']['name'] ?? '',
			'received_time'   => $message['receivedDateTime'] ?? gmdate( 'c' ),
			'replied_by_user' => $portal_user ? $portal_user['user_id'] : null,
			'replied_by_name' => $portal_user ? $portal_user['display_name'] : $message['from']['emailAddress']['name'] ?? '',
			'replied_via'     => $portal_user ? 'outlook_as_user' : 'outlook',
			'type'            => 'reply',
			'subject'         => $message['subject'] ?? '',
		);

		// Update ticket
		$wpdb->update(
			$tickets_table,
			array( 'thread_history' => wp_json_encode( $thread_history ) ),
			array( 'id' => $ticket_id )
		);

		if ( class_exists( 'LGP_Logger' ) ) {
			LGP_Logger::log_event(
				$portal_user ? $portal_user['user_id'] : 0,
				'shared_mailbox_reply_received',
				$ticket_id,
				array(
					'from' => $from_email,
					'via'  => $portal_user ? 'outlook_as_user' : 'outlook',
				)
			);
		}
	}

	/**
	 * Send reply via shared mailbox
	 * Hook: lgp_ticket_reply_added
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param int    $user_id Support user ID
	 * @param string $reply_text Reply message text
	 */
	public static function send_reply_via_graph( $ticket_id, $user_id, $reply_text ) {
		try {
			global $wpdb;

			// Get ticket and company
			$tickets_table  = $wpdb->prefix . 'lgp_tickets';
			$requests_table = $wpdb->prefix . 'lgp_service_requests';

			$ticket = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT t.*, sr.company_id FROM {$tickets_table} t
					 INNER JOIN {$requests_table} sr ON t.service_request_id = sr.id
					 WHERE t.id = %d",
					$ticket_id
				)
			);

			if ( ! $ticket ) {
				return;
			}

			// Get original sender email from thread history
			$thread        = json_decode( $ticket->thread_history ?? '{}', true );
			$first_message = $thread['messages'][0] ?? null;
			if ( ! $first_message || empty( $first_message['from'] ) ) {
				return;
			}

			$recipient_email = $first_message['from'];
			$user            = get_user_by( 'id', $user_id );
			$user_display    = $user ? ( $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->user_login ) : 'Support Team';

			// Compose email
			$subject = 'Re: ' . ( $first_message['subject'] ?? 'Support Ticket' );
			$body    = self::format_reply_body( $reply_text, $user_display );

			// Send via shared mailbox
			$client   = self::get_graph_client();
			$settings = self::get_settings();

			// Construct message with thread ID preservation
			$message_data = array(
				'message'         => array(
					'subject'      => $subject,
					'body'         => array(
						'contentType' => 'HTML',
						'content'     => $body,
					),
					'toRecipients' => array(
						array(
							'emailAddress' => array(
								'address' => $recipient_email,
							),
						),
					),
				),
				'saveToSentItems' => 'true',
			);

			// Add conversation context if available
			if ( ! empty( $thread['conversation_id'] ) ) {
				$message_data['message']['conversationId'] = $thread['conversation_id'];
			}

			// Optional: Add "on behalf of" header if support user has mailbox
			if ( ! empty( $settings['allow_send_on_behalf'] ) && $user ) {
				$message_data['message']['from'] = array(
					'emailAddress' => array(
						'address' => $settings['mailbox'] ?? '',
						'name'    => $user_display,
					),
				);
			}

			$client->send_mail_message( $settings['mailbox'] ?? '', $message_data );

			// Record in ticket thread
			self::record_portal_reply( $ticket_id, $user_id, $reply_text );

			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event(
					$user_id,
					'shared_mailbox_reply_sent',
					$ticket_id,
					array( 'to' => $recipient_email )
				);
			}
		} catch ( Exception $e ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_error( 'Failed to send reply via Graph: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Record portal reply in ticket thread
	 */
	private static function record_portal_reply( $ticket_id, $user_id, $reply_text ) {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$ticket        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $ticket_id ) );

		if ( ! $ticket ) {
			return;
		}

		$thread_history = json_decode( $ticket->thread_history ?? '{}', true );
		if ( ! is_array( $thread_history ) ) {
			$thread_history = array( 'messages' => array() );
		}

		$user = get_user_by( 'id', $user_id );

		$thread_history['messages'][] = array(
			'internet_id'     => 'portal_' . uniqid(),
			'from'            => $user ? $user->user_email : '',
			'from_name'       => $user ? ( $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->user_login ) : '',
			'received_time'   => gmdate( 'c' ),
			'replied_by_user' => $user_id,
			'replied_by_name' => $user ? $user->display_name : '',
			'replied_via'     => 'portal',
			'type'            => 'reply',
			'subject'         => 'Reply',
		);

		$wpdb->update(
			$tickets_table,
			array( 'thread_history' => wp_json_encode( $thread_history ) ),
			array( 'id' => $ticket_id )
		);
	}

	/**
	 * Download attachments from Graph message
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $message_id Graph message ID
	 * @param object $client LGP_Graph_Client
	 */
	private static function download_attachments( $ticket_id, $message_id, $client ) {
		try {
			$attachments = $client->get_attachments( $message_id );

			if ( empty( $attachments['value'] ) ) {
				return;
			}

			foreach ( $attachments['value'] as $attachment ) {
				$filename = sanitize_file_name( $attachment['name'] ?? 'attachment' );
				$content  = $attachment['contentBytes'] ?? '';

				if ( empty( $content ) ) {
					continue;
				}

				// Decode base64 if needed
				if ( preg_match( '/^[A-Za-z0-9+\/=]+$/', $content ) ) {
					$content = base64_decode( $content, true );
				}

				// Store attachment using existing handler
				if ( class_exists( 'LGP_Attachment_Handler' ) ) {
					LGP_Attachment_Handler::store_attachment( $ticket_id, $filename, $content );
				}
			}
		} catch ( Exception $e ) {
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_error( 'Failed to download attachments: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Find company by email domain
	 */
	private static function find_company_by_domain( $email ) {
		global $wpdb;

		// Extract domain
		$domain = substr( strrchr( $email, '@' ), 1 );
		if ( empty( $domain ) ) {
			return null;
		}

		$companies_table = $wpdb->prefix . 'lgp_companies';

		// Match by email field
		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$companies_table} WHERE email LIKE %s OR secondary_contact_email LIKE %s LIMIT 1",
				'%' . $domain . '%',
				'%' . $domain . '%'
			)
		);

		return $company ? (int) $company->id : null;
	}

	/**
	 * Find ticket by conversation ID
	 */
	private static function find_ticket_by_conversation( $conversation_id, $company_id ) {
		global $wpdb;

		if ( empty( $conversation_id ) ) {
			return null;
		}

		$tickets_table  = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT t.id FROM {$tickets_table} t
				 INNER JOIN {$requests_table} sr ON t.service_request_id = sr.id
				 WHERE sr.company_id = %d
				 AND t.thread_history LIKE %s
				 ORDER BY t.id DESC LIMIT 1",
				$company_id,
				'%' . $conversation_id . '%'
			)
		);

		return $ticket ? (int) $ticket->id : null;
	}

	/**
	 * Find portal user by email
	 */
	private static function find_portal_user_by_email( $email ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return null;
		}

		// Check if user is support role
		if ( ! in_array( 'lgp_support', (array) $user->roles, true ) ) {
			return null;
		}

		return array(
			'user_id'      => $user->ID,
			'display_name' => $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->user_login,
			'email'        => $user->user_email,
		);
	}

	/**
	 * Mark message as processed (idempotency)
	 */
	private static function is_message_processed( $internet_id ) {
		if ( empty( $internet_id ) ) {
			return false;
		}

		global $wpdb;

		$tickets_table = $wpdb->prefix . 'lgp_tickets';

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tickets_table} WHERE email_reference = %s LIMIT 1",
				$internet_id
			)
		);

		return ! empty( $found );
	}

	/**
	 * Mark message as processed
	 */
	private static function mark_message_processed( $internet_id ) {
		if ( empty( $internet_id ) ) {
			return;
		}

		$processed = get_option( 'lgp_processed_messages', array() );
		if ( ! in_array( $internet_id, (array) $processed, true ) ) {
			$processed[] = $internet_id;
			// Keep only last 1000
			$processed = array_slice( $processed, -1000 );
			update_option( 'lgp_processed_messages', $processed );
		}
	}

	/**
	 * Extract notes from message
	 */
	private static function extract_notes( $message ) {
		$from    = $message['from']['emailAddress']['address'] ?? 'Unknown';
		$subject = $message['subject'] ?? 'No Subject';
		$body    = wp_strip_all_tags( $message['body']['content'] ?? $message['bodyPreview'] ?? '' );

		return sprintf(
			"Email From: %s\nSubject: %s\n\n%s",
			$from,
			$subject,
			$body
		);
	}

	/**
	 * Detect priority from email
	 */
	private static function detect_priority( $subject, $body ) {
		$content = strtolower( $subject . ' ' . $body );

		if ( preg_match( '/(urgent|critical|emergency|down|offline|broken)/i', $content ) ) {
			return 'high';
		}

		if ( preg_match( '/(important|asap|soon|immediately)/i', $content ) ) {
			return 'high';
		}

		if ( preg_match( '/(low|question|info|fyi|later)/i', $content ) ) {
			return 'low';
		}

		return 'medium';
	}

	/**
	 * Format reply body with footer
	 */
	private static function format_reply_body( $reply_text, $user_display ) {
		return sprintf(
			'<p>%s</p><hr><p><small>%s from %s via LounGenie Portal</small></p>',
			wp_kses_post( nl2br( $reply_text ) ),
			gmdate( 'Y-m-d H:i:s' ),
			esc_html( $user_display )
		);
	}

	/**
	 * Get settings
	 */
	public static function get_settings() {
		return get_option(
			'lgp_shared_mailbox_settings',
			array(
				'tenant_id'            => '',
				'client_id'            => '',
				'client_secret'        => '',
				'mailbox'              => '',
				'allow_send_on_behalf' => false,
			)
		);
	}

	/**
	 * Add settings page
	 */
	public static function add_settings_page() {
		add_options_page(
			__( 'Shared Mailbox Settings', 'loungenie-portal' ),
			__( 'Shared Mailbox', 'loungenie-portal' ),
			'manage_options',
			'lgp-shared-mailbox',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'lgp_shared_mailbox', 'lgp_shared_mailbox_settings' );
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Shared Mailbox Settings', 'loungenie-portal' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'lgp_shared_mailbox' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="tenant_id"><?php esc_html_e( 'Azure Tenant ID', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="text" name="lgp_shared_mailbox_settings[tenant_id]" id="tenant_id" value="<?php echo esc_attr( $settings['tenant_id'] ?? '' ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="client_id"><?php esc_html_e( 'Client ID', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="text" name="lgp_shared_mailbox_settings[client_id]" id="client_id" value="<?php echo esc_attr( $settings['client_id'] ?? '' ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="client_secret"><?php esc_html_e( 'Client Secret', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="password" name="lgp_shared_mailbox_settings[client_secret]" id="client_secret" value="<?php echo esc_attr( $settings['client_secret'] ?? '' ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mailbox"><?php esc_html_e( 'Shared Mailbox Address', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="email" name="lgp_shared_mailbox_settings[mailbox]" id="mailbox" value="<?php echo esc_attr( $settings['mailbox'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'e.g., support@poolsafeinc.com', 'loungenie-portal' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="allow_send_on_behalf"><?php esc_html_e( 'Allow "Send On Behalf"', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="lgp_shared_mailbox_settings[allow_send_on_behalf]" id="allow_send_on_behalf" value="1" <?php checked( $settings['allow_send_on_behalf'] ?? false ); ?>>
							<p class="description"><?php esc_html_e( 'If enabled, replies will show sender name with shared mailbox. Requires additional Graph permissions.', 'loungenie-portal' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
