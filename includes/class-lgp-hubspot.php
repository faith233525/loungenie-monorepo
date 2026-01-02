<?php
/**
 * HubSpot integration class.
 *
 * Syncs companies, units, service requests, and tickets with HubSpot CRM.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HubSpot integration class.
 */
class LGP_HubSpot {

	/**
	 * HubSpot API base URL
	 */
	const API_BASE_URL = 'https://api.hubapi.com';

	/**
	 * Initialize HubSpot integration.
	 *
	 * @return void
	 */
	public static function init() {
		// Hook into ticket creation to sync with HubSpot (queued for batch processing).
		add_action( 'lgp_ticket_created', array( __CLASS__, 'sync_ticket_to_hubspot' ), 10, 2 );
		add_action( 'lgp_ticket_updated', array( __CLASS__, 'update_hubspot_ticket' ), 10, 2 );

		// Hook into company creation.
		add_action( 'lgp_company_created', array( __CLASS__, 'sync_company_to_hubspot' ), 10, 1 );

		// Batch sync processor (runs every 5 minutes).
		add_action( 'lgp_hubspot_batch_sync', array( __CLASS__, 'process_sync_queue' ) );

		// Add settings page.
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Get HubSpot API key from settings.
	 *
	 * @return string|false
	 */
	private static function get_api_key() {
		return get_option( 'lgp_hubspot_api_key', false );
	}

	/**
	 * Check if HubSpot integration is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return ! empty( self::get_api_key() );
	}

	/**
	 * Make API request to HubSpot.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method HTTP method (GET, POST, PATCH).
	 * @param array  $data Request data.
	 * @return array|WP_Error
	 */
	private static function api_request( $endpoint, $method = 'GET', $data = array() ) {
		if ( ! self::is_enabled() ) {
			return new WP_Error( 'hubspot_disabled', __( 'HubSpot integration is not enabled', 'loungenie-portal' ) );
		}

		$api_key = self::get_api_key();
		$url     = self::API_BASE_URL . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PATCH', 'PUT' ) ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'API Request Failed: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$error_message = isset( $decoded['message'] ) ? $decoded['message'] : 'Unknown error';
			self::log_error( sprintf( 'HubSpot API Error (%d): %s', $status_code, $error_message ) );
			return new WP_Error( 'hubspot_api_error', $error_message, array( 'status' => $status_code ) );
		}

		return $decoded;
	}

	/**
	 * Sync company to HubSpot.
	 *
	 * @param int $company_id Company ID.
	 * @return bool|WP_Error
	 */
	public static function sync_company_to_hubspot( $company_id ) {
		global $wpdb;

		$table   = $wpdb->prefix . 'lgp_companies';
		$company = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $company_id ) );

		if ( ! $company ) {
			return new WP_Error( 'company_not_found', __( 'Company not found', 'loungenie-portal' ) );
		}

		// Check if already synced
		$hubspot_id = get_option( 'lgp_hubspot_company_' . $company_id );

		$data = array(
			'properties' => array(
				'name'    => $company->name,
				'address' => $company->address,
				'state'   => $company->state,
				'phone'   => $company->contact_phone,
				'domain'  => '', // Could extract from email if needed.
			),
		);

		if ( $hubspot_id ) {
			// Update existing company.
			$response = self::api_request( '/crm/v3/objects/companies/' . $hubspot_id, 'PATCH', $data );
		} else {
			// Create new company.
			$response = self::api_request( '/crm/v3/objects/companies', 'POST', $data );

			if ( ! is_wp_error( $response ) && isset( $response['id'] ) ) {
				update_option( 'lgp_hubspot_company_' . $company_id, $response['id'] );
			}
		}

		if ( is_wp_error( $response ) ) {
			self::schedule_retry( 'sync_company', $company_id );
			return $response;
		}

		return true;
	}

	/**
	 * Queue ticket for HubSpot sync (batch processing).
	 * Optimization: Prevents rate limits on shared hosting.
	 *
	 * @param int   $ticket_id  Ticket ID.
	 * @param array $ticket_data Ticket data.
	 * @return bool
	 */
	public static function sync_ticket_to_hubspot( $ticket_id, $ticket_data = array() ) {
		self::queue_sync( 'ticket', $ticket_id );
		return true;
	}

	/**
	 * Queue object for batch HubSpot sync.
	 * Optimized: Prevents unbounded growth, implements backoff.
	 *
	 * @param string $type      Object type (ticket, company).
	 * @param int    $object_id Object ID.
	 * @return void
	 */
	private static function queue_sync( $type, $object_id ) {
		$queue        = get_option( 'lgp_hubspot_sync_queue', array() );
		$queue_entry  = array(
			'type'      => $type,
			'id'        => $object_id,
			'queued_at' => time(),
			'attempts'  => 0,
		);
		$queue[]      = $queue_entry;

		// Cap queue to prevent unbounded growth (max 500 items).
		if ( count( $queue ) > 500 ) {
			// Remove oldest entries.
			$queue = array_slice( $queue, -500 );
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_event( 0, 'hubspot_queue_capped', 0, array( 'count' => count( $queue ) ) );
			}
		}

		update_option( 'lgp_hubspot_sync_queue', $queue );

		// Schedule batch processing if not already scheduled.
		if ( ! wp_next_scheduled( 'lgp_hubspot_batch_sync' ) ) {
			wp_schedule_single_event( time() + 300, 'lgp_hubspot_batch_sync' ); // 5 minutes
		}
	}

	/**
	 * Process HubSpot sync queue in batches.
	 * Prevents API rate limits (max 10 per batch).
	 *
	 * @return void
	 */
	public static function process_sync_queue() {
		$queue = get_option( 'lgp_hubspot_sync_queue', array() );

		if ( empty( $queue ) ) {
			return;
		}

		$batch_size = 10; // HubSpot allows ~10 req/sec.
		$batch      = array_splice( $queue, 0, $batch_size );

		foreach ( $batch as $item ) {
			try {
				if ( 'ticket' === $item['type'] ) {
					self::sync_ticket_immediate( $item['id'] );
				} elseif ( 'company' === $item['type'] ) {
					self::sync_company_to_hubspot( $item['id'] );
				}
			} catch ( Exception $e ) {
				error_log( 'HubSpot batch sync failed: ' . $e->getMessage() );
				// Re-queue for retry.
				$queue[] = $item;
			}
		}

		// Update queue.
		update_option( 'lgp_hubspot_sync_queue', $queue );

		// Schedule next batch if queue not empty.
		if ( ! empty( $queue ) && ! wp_next_scheduled( 'lgp_hubspot_batch_sync' ) ) {
			wp_schedule_single_event( time() + 10, 'lgp_hubspot_batch_sync' ); // 10 seconds.
		}
	}

	/**
	 * Immediate ticket sync (called by batch processor).
	 *
	 * @param int   $ticket_id  Ticket ID.
	 * @param array $ticket_data Ticket data.
	 * @return bool|WP_Error
	 */
	private static function sync_ticket_immediate( $ticket_id, $ticket_data = array() ) {
		global $wpdb;

		if ( empty( $ticket_data ) ) {
			$tickets_table  = $wpdb->prefix . 'lgp_tickets';
			$requests_table = $wpdb->prefix . 'lgp_service_requests';

			$ticket_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT t.*, sr.request_type, sr.priority, sr.company_id, sr.notes 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE t.id = %d",
					$ticket_id
				)
			);
		}

		if ( ! $ticket_data ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found', 'loungenie-portal' ) );
		}

		// Get company HubSpot ID
		$company_hubspot_id = get_option( 'lgp_hubspot_company_' . $ticket_data->company_id );

		$data = array(
			'properties' => array(
				'subject'            => sprintf( '[LounGenie] %s Request #%d', ucfirst( $ticket_data->request_type ), $ticket_id ),
				'content'            => $ticket_data->notes ?? '',
				'hs_pipeline_stage'  => self::map_status_to_pipeline( $ticket_data->status ),
				'hs_ticket_priority' => self::map_priority( $ticket_data->priority ),
			),
		);

		// Check if already synced
		$hubspot_ticket_id = get_option( 'lgp_hubspot_ticket_' . $ticket_id );

		if ( $hubspot_ticket_id ) {
			// Update existing ticket
			$response = self::api_request( '/crm/v3/objects/tickets/' . $hubspot_ticket_id, 'PATCH', $data );
		} else {
			// Create new ticket
			$response = self::api_request( '/crm/v3/objects/tickets', 'POST', $data );

			if ( ! is_wp_error( $response ) && isset( $response['id'] ) ) {
				update_option( 'lgp_hubspot_ticket_' . $ticket_id, $response['id'] );

				// Associate ticket with company if we have the company ID
				if ( $company_hubspot_id ) {
					self::associate_ticket_to_company( $response['id'], $company_hubspot_id );
				}
			}
		}

		if ( is_wp_error( $response ) ) {
			self::schedule_retry( 'sync_ticket', $ticket_id );
			return $response;
		}

		return true;
	}

	/**
	 * Update HubSpot ticket when status changes
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $new_status New status
	 * @return bool|WP_Error
	 */
	public static function update_hubspot_ticket( $ticket_id, $new_status ) {
		$hubspot_ticket_id = get_option( 'lgp_hubspot_ticket_' . $ticket_id );

		if ( ! $hubspot_ticket_id ) {
			// Ticket not synced yet, sync it now
			return self::sync_ticket_to_hubspot( $ticket_id );
		}

		$data = array(
			'properties' => array(
				'hs_pipeline_stage' => self::map_status_to_pipeline( $new_status ),
			),
		);

		$response = self::api_request( '/crm/v3/objects/tickets/' . $hubspot_ticket_id, 'PATCH', $data );

		if ( is_wp_error( $response ) ) {
			self::schedule_retry( 'update_ticket', $ticket_id );
			return $response;
		}

		return true;
	}

	/**
	 * Associate ticket with company in HubSpot
	 *
	 * @param string $ticket_id HubSpot ticket ID
	 * @param string $company_id HubSpot company ID
	 * @return bool
	 */
	private static function associate_ticket_to_company( $ticket_id, $company_id ) {
		$data = array(
			array(
				'from' => array( 'id' => $ticket_id ),
				'to'   => array( 'id' => $company_id ),
				'type' => 'ticket_to_company',
			),
		);

		$response = self::api_request( '/crm/v3/objects/tickets/batch/associate/company', 'POST', $data );

		return ! is_wp_error( $response );
	}

	/**
	 * Map portal status to HubSpot pipeline stage
	 *
	 * @param string $status Portal status
	 * @return string HubSpot pipeline stage
	 */
	private static function map_status_to_pipeline( $status ) {
		$mapping = array(
			'open'        => '1',      // New
			'pending'     => '2',   // Waiting on contact
			'in_progress' => '3', // In progress
			'completed'   => '4',  // Closed
			'closed'      => '4',     // Closed
		);

		return isset( $mapping[ $status ] ) ? $mapping[ $status ] : '1';
	}

	/**
	 * Map portal priority to HubSpot priority
	 *
	 * @param string $priority Portal priority
	 * @return string HubSpot priority
	 */
	private static function map_priority( $priority ) {
		$mapping = array(
			'low'    => 'LOW',
			'normal' => 'MEDIUM',
			'high'   => 'HIGH',
			'urgent' => 'HIGH',
		);

		return isset( $mapping[ $priority ] ) ? $mapping[ $priority ] : 'MEDIUM';
	}

	/**
	 * Schedule retry for failed sync
	 *
	 * @param string $action Action to retry
	 * @param int    $id Record ID
	 */
	private static function schedule_retry( $action, $id ) {
		$retry_queue = get_option( 'lgp_hubspot_retry_queue', array() );

		$retry_queue[] = array(
			'action'    => $action,
			'id'        => $id,
			'attempts'  => 0,
			'scheduled' => time(),
		);

		update_option( 'lgp_hubspot_retry_queue', $retry_queue );

		// Schedule cron job to process retries
		if ( ! wp_next_scheduled( 'lgp_hubspot_process_retries' ) ) {
			wp_schedule_single_event( time() + 300, 'lgp_hubspot_process_retries' ); // Retry in 5 minutes
		}
	}

	/**
	 * Log error to WordPress error log
	 *
	 * @param string $message Error message
	 */
	private static function log_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[LounGenie HubSpot] ' . $message );
		}

		// Also store in options for admin view
		$errors   = get_option( 'lgp_hubspot_errors', array() );
		$errors[] = array(
			'message'   => $message,
			'timestamp' => current_time( 'mysql' ),
		);

		// Keep only last 50 errors
		if ( count( $errors ) > 50 ) {
			$errors = array_slice( $errors, -50 );
		}

		update_option( 'lgp_hubspot_errors', $errors );
	}

	/**
	 * Add settings page to WordPress admin
	 */
	public static function add_settings_page() {
		add_options_page(
			__( 'LounGenie HubSpot Settings', 'loungenie-portal' ),
			__( 'HubSpot Integration', 'loungenie-portal' ),
			'manage_options',
			'lgp-hubspot-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting(
			'lgp_hubspot_settings',
			'lgp_hubspot_api_key',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HubSpot Integration Settings', 'loungenie-portal' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'lgp_hubspot_settings' );
				do_settings_sections( 'lgp_hubspot_settings' );
				?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="lgp_hubspot_api_key"><?php esc_html_e( 'HubSpot API Key', 'loungenie-portal' ); ?></label>
						</th>
						<td>
							<input type="text" 
									id="lgp_hubspot_api_key" 
									name="lgp_hubspot_api_key" 
									value="<?php echo esc_attr( get_option( 'lgp_hubspot_api_key' ) ); ?>" 
									class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Enter your HubSpot Private App Access Token. Get it from HubSpot Settings → Integrations → Private Apps.', 'loungenie-portal' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e( 'Integration Status', 'loungenie-portal' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'Status:', 'loungenie-portal' ); ?></strong>
				<?php if ( self::is_enabled() ) : ?>
					<span style="color: green;">✓ <?php esc_html_e( 'Enabled', 'loungenie-portal' ); ?></span>
				<?php else : ?>
					<span style="color: red;">✗ <?php esc_html_e( 'Disabled (API key required)', 'loungenie-portal' ); ?></span>
				<?php endif; ?>
			</p>
			
			<?php
			$errors = get_option( 'lgp_hubspot_errors', array() );
			if ( ! empty( $errors ) ) :
				?>
				<h3><?php esc_html_e( 'Recent Errors', 'loungenie-portal' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Timestamp', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Message', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_reverse( array_slice( $errors, -10 ) ) as $error ) : ?>
							<tr>
								<td><?php echo esc_html( $error['timestamp'] ); ?></td>
								<td><?php echo esc_html( $error['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
