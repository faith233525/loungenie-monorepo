<?php

/**
 * Tickets REST API endpoints.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tickets API class.
 */
class LGP_Tickets_API {




	/**
	 * Initialize API endpoints.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		// Get tickets.
		register_rest_route(
			'lgp/v1',
			'/tickets',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_tickets' ),
				'permission_callback' => array( __CLASS__, 'check_portal_permission' ),
			)
		);

		// Get single ticket.
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_ticket' ),
				'permission_callback' => array( __CLASS__, 'check_ticket_permission' ),
			)
		);

		// Create ticket/service request (Partners).
		register_rest_route(
			'lgp/v1',
			'/tickets',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_ticket' ),
				'permission_callback' => array( __CLASS__, 'check_partner_permission' ),
			)
		);

		// Update ticket status (Support only).
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'update_ticket' ),
				'permission_callback' => array( __CLASS__, 'check_support_permission' ),
			)
		);

		// Add reply to ticket thread.
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)/reply',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'add_reply' ),
				'permission_callback' => array( __CLASS__, 'check_portal_permission' ),
			)
		);
	}

	/**
	 * Get tickets
	 */
	public static function get_tickets( $request ) {
		global $wpdb;

		$tickets_table   = $wpdb->prefix . 'lgp_tickets';
		$requests_table  = $wpdb->prefix . 'lgp_service_requests';
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$page            = $request->get_param( 'page' ) ?: 1;
		$per_page        = $request->get_param( 'per_page' ) ?: 20;
		$offset          = ( $page - 1 ) * $per_page;

		if ( LGP_Auth::is_support() ) {
			// Support can see all tickets
			$tickets = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.*, sr.request_type, sr.priority, sr.company_id, c.name AS company_name 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                LEFT JOIN $companies_table c ON sr.company_id = c.id 
                ORDER BY t.created_at DESC 
                LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
			$total   = $wpdb->get_var( "SELECT COUNT(*) FROM $tickets_table" );
		} else {
			// Partners see only their tickets
			$company_id = LGP_Auth::get_user_company_id();
			$tickets    = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.*, sr.request_type, sr.priority, sr.company_id, c.name AS company_name 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                LEFT JOIN $companies_table c ON sr.company_id = c.id 
                WHERE sr.company_id = %d 
                ORDER BY t.created_at DESC 
                LIMIT %d OFFSET %d",
					$company_id,
					$per_page,
					$offset
				)
			);
			$total      = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE sr.company_id = %d",
					$company_id
				)
			);
		}

		return rest_ensure_response(
			array(
				'tickets'  => $tickets,
				'total'    => (int) $total,
				'page'     => (int) $page,
				'per_page' => (int) $per_page,
			)
		);
	}

	/**
	 * Get single ticket
	 */
	public static function get_ticket( $request ) {
		global $wpdb;

		$id              = (int) $request->get_param( 'id' );
		$tickets_table   = $wpdb->prefix . 'lgp_tickets';
		$requests_table  = $wpdb->prefix . 'lgp_service_requests';
		$companies_table = $wpdb->prefix . 'lgp_companies';

		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT t.*, sr.request_type, sr.priority, sr.company_id, sr.notes, c.name AS company_name 
            FROM $tickets_table t 
            LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
            LEFT JOIN $companies_table c ON sr.company_id = c.id 
            WHERE t.id = %d",
				$id
			)
		);

		if ( ! $ticket ) {
			return new WP_Error( 'not_found', __( 'Ticket not found', 'loungenie-portal' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $ticket );
	}

	/**
	 * Create ticket (from service request)
	 */
	public static function create_ticket( $request ) {
		global $wpdb;

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		// Rate limiting: max 5 tickets per hour per user.
		$user_id  = get_current_user_id();
		$cache_key = 'lgp_ticket_count_' . (int) $user_id;
		$count    = (int) get_transient( $cache_key );

		if ( $count >= 5 ) {
			return new WP_Error( 'rate_limit_exceeded', 'Too many tickets. Maximum 5 per hour.', array( 'status' => 429 ) );
		}

		// Increment count
		set_transient( $cache_key, $count + 1, HOUR_IN_SECONDS );

		$company_id   = LGP_Auth::get_user_company_id();
		$unit_id      = absint( $request->get_param( 'unit_id' ) );
		$priority     = sanitize_text_field( $request->get_param( 'priority' ) ?: 'normal' );
		$request_type = sanitize_text_field( $request->get_param( 'request_type' ) ?: 'general' );

		$contact_name   = sanitize_text_field( $request->get_param( 'contact_name' ) );
		$contact_email  = sanitize_email( $request->get_param( 'contact_email' ) );
		$contact_phone  = sanitize_text_field( $request->get_param( 'contact_phone' ) );
		$units_affected = absint( $request->get_param( 'units_affected' ) );
		$notes_raw      = sanitize_textarea_field( $request->get_param( 'notes' ) );

		// Build a safe note that captures minimal form data
		$notes_parts = array();
		if ( $contact_name ) {
			$notes_parts[] = 'Contact: ' . $contact_name;
		}
		if ( $contact_email ) {
			$notes_parts[] = 'Email: ' . $contact_email;
		}
		if ( $contact_phone ) {
			$notes_parts[] = 'Phone: ' . $contact_phone;
		}
		if ( $units_affected ) {
			$notes_parts[] = 'Units affected: ' . $units_affected;
		}
		if ( $notes_raw ) {
			$notes_parts[] = 'Issue: ' . $notes_raw;
		}

		$notes_combined = implode( "\n", array_filter( $notes_parts ) );
		if ( empty( $notes_combined ) ) {
			return new WP_Error( 'invalid_message', __( 'Please provide issue details.', 'loungenie-portal' ), array( 'status' => 400 ) );
		}

		// START TRANSACTION for atomic ticket creation
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Create service request first
			$requests_table = $wpdb->prefix . 'lgp_service_requests';
			$request_data   = array(
				'company_id'   => $company_id,
				'unit_id'      => $unit_id,
				'request_type' => $request_type,
				'priority'     => $priority,
				'status'       => 'pending',
				'notes'        => $notes_combined,
			);

			$inserted = $wpdb->insert( $requests_table, $request_data );

			if ( $inserted === false ) {
				throw new Exception( 'Failed to create service request' );
			}

			$service_request_id = $wpdb->insert_id;

			// Create ticket
			$tickets_table = $wpdb->prefix . 'lgp_tickets';
			$ticket_data   = array(
				'service_request_id' => $service_request_id,
				'status'             => 'open',
				'thread_history'     => wp_json_encode(
					array(
						array(
							'timestamp' => current_time( 'mysql' ),
							'user'      => wp_get_current_user()->display_name,
							'message'   => $request_data['notes'],
						),
					)
				),
			);

			$inserted_ticket = $wpdb->insert( $tickets_table, $ticket_data );

			if ( $inserted_ticket === false ) {
				throw new Exception( 'Failed to create ticket' );
			}

			$ticket_id = $wpdb->insert_id;

			// Audit logging
			$user = wp_get_current_user();
			LGP_Logger::log_event(
				$user->ID,
				'ticket_created',
				$company_id,
				array(
					'ticket_id'          => $ticket_id,
					'service_request_id' => $service_request_id,
					'request_type'       => $request_data['request_type'],
					'priority'           => $request_data['priority'],
					'unit_id'            => $request_data['unit_id'],
				)
			);

			// COMMIT TRANSACTION
			$wpdb->query( 'COMMIT' );

			// Fire action for integrations (after successful commit)
			do_action( 'lgp_ticket_created', $ticket_id, (object) array_merge( (array) $ticket_data, (array) $request_data ) );

			return rest_ensure_response(
				array(
					'ticket_id'          => $ticket_id,
					'service_request_id' => $service_request_id,
					'message'            => __( 'Service request submitted successfully', 'loungenie-portal' ),
				)
			);
		} catch ( Exception $e ) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query( 'ROLLBACK' );

			// Helpful debug during tests
			error_log( 'LGP ticket create error: ' . $e->getMessage() );

			// Use existing logger methods (no log_error in LGP_Logger)
			LGP_Logger::log(
				'ticket',
				'creation_failed',
				array(
					'error'      => $e->getMessage(),
					'company_id' => $company_id,
					'user_id'    => get_current_user_id(),
				),
				get_current_user_id(),
				$company_id
			);

			return new WP_Error(
				'db_error',
				__( 'Failed to create service request', 'loungenie-portal' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Update ticket
	 */
	public static function update_ticket( $request ) {
		global $wpdb;

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$id         = (int) $request->get_param( 'id' );
		$new_status = sanitize_text_field( $request->get_param( 'status' ) );
		$table      = $wpdb->prefix . 'lgp_tickets';

		// START TRANSACTION for atomic update
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Get current ticket for audit trail
			$old_ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d FOR UPDATE", $id ) );

			if ( ! $old_ticket ) {
				throw new Exception( 'Ticket not found' );
			}

			$old_status = $old_ticket->status;

			// Update ticket status and timestamp
			$data = array(
				'status'     => $new_status,
				'updated_at' => current_time( 'mysql' ),
			);

			$updated = $wpdb->update( $table, $data, array( 'id' => $id ) );

			if ( $updated === false ) {
				throw new Exception( 'Database update failed' );
			}

			// Get company context for audit logging
			$requests_table  = $wpdb->prefix . 'lgp_service_requests';
			$service_request = $wpdb->get_row( $wpdb->prepare( "SELECT company_id FROM $requests_table WHERE id = %d", $old_ticket->service_request_id ) );

			// Audit logging (fallback to null company context if not found)
			$user            = wp_get_current_user();
			$company_context = $service_request ? $service_request->company_id : null;
			LGP_Logger::log_event(
				$user->ID,
				'ticket_updated',
				$company_context,
				array(
					'ticket_id'  => $id,
					'old_status' => $old_status,
					'new_status' => $new_status,
					'updated_by' => $user->user_login,
				)
			);

			// COMMIT TRANSACTION
			$wpdb->query( 'COMMIT' );

			// Fire action for integrations (after successful commit)
			do_action( 'lgp_ticket_updated', $id, $new_status, $old_status );

			return rest_ensure_response(
				array(
					'message'   => __( 'Ticket updated successfully', 'loungenie-portal' ),
					'ticket_id' => $id,
					'status'    => $new_status,
				)
			);
		} catch ( Exception $e ) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query( 'ROLLBACK' );

			LGP_Logger::log(
				'ticket',
				'update_failed',
				array(
					'error'     => $e->getMessage(),
					'ticket_id' => $id,
					'user_id'   => get_current_user_id(),
				),
				get_current_user_id()
			);

			return new WP_Error(
				'db_error',
				__( 'Failed to update ticket', 'loungenie-portal' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Add reply to ticket
	 */
	public static function add_reply( $request ) {
		global $wpdb;

		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$id      = (int) $request->get_param( 'id' );
		$message = sanitize_textarea_field( $request->get_param( 'message' ) );
		$table   = $wpdb->prefix . 'lgp_tickets';

		if ( empty( $message ) ) {
			return new WP_Error(
				'invalid_message',
				__( 'Reply message cannot be empty', 'loungenie-portal' ),
				array( 'status' => 400 )
			);
		}

		// START TRANSACTION for atomic reply addition
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Get current thread history with row lock
			$ticket = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE id = %d FOR UPDATE",
					$id
				)
			);

			if ( ! $ticket ) {
				throw new Exception( 'Ticket not found' );
			}

			$thread = json_decode( $ticket->thread_history, true ) ?: array();

			// Add new reply
			$thread[] = array(
				'timestamp' => current_time( 'mysql' ),
				'user'      => wp_get_current_user()->display_name,
				'message'   => $message,
			);

			// Update ticket with new thread
			$updated = $wpdb->update(
				$table,
				array(
					'thread_history' => wp_json_encode( $thread ),
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $id )
			);

			if ( $updated === false ) {
				throw new Exception( 'Failed to update thread history' );
			}

			// COMMIT TRANSACTION
			$wpdb->query( 'COMMIT' );

			// Audit logging with company context
			$requests_table  = $wpdb->prefix . 'lgp_service_requests';
			$service_request = $wpdb->get_row( $wpdb->prepare( "SELECT company_id FROM $requests_table WHERE id = %d", $ticket->service_request_id ) );
			$company_context = $service_request ? $service_request->company_id : null;
			$user            = wp_get_current_user();
			LGP_Logger::log_event(
				$user->ID,
				'ticket_reply_added',
				$company_context,
				array(
					'ticket_id' => $id,
					'reply_by'  => $user->user_login,
				)
			);

			// Fire action for integrations (after successful commit)
			do_action( 'lgp_ticket_reply_added', $id, $message, $ticket );

			return rest_ensure_response(
				array(
					'message'   => __( 'Reply added successfully', 'loungenie-portal' ),
					'ticket_id' => $id,
				)
			);
		} catch ( Exception $e ) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query( 'ROLLBACK' );

			LGP_Logger::log(
				'ticket',
				'reply_failed',
				array(
					'error'     => $e->getMessage(),
					'ticket_id' => $id,
					'user_id'   => get_current_user_id(),
				),
				get_current_user_id()
			);

			return new WP_Error(
				'db_error',
				__( 'Failed to add reply', 'loungenie-portal' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Check if user has portal access
	 */
	public static function check_portal_permission() {
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Check if user is Support
	 */
	public static function check_support_permission() {
		return LGP_Auth::is_support();
	}

	/**
	 * Check if user is Partner
	 */
	public static function check_partner_permission() {
		return LGP_Auth::is_partner();
	}

	/**
	 * Check if user can access ticket
	 */
	public static function check_ticket_permission( $request ) {
		if ( LGP_Auth::is_support() ) {
			return true;
		}

		if ( LGP_Auth::is_partner() ) {
			global $wpdb;
			$ticket_id  = (int) $request->get_param( 'id' );
			$company_id = LGP_Auth::get_user_company_id();

			$tickets_table  = $wpdb->prefix . 'lgp_tickets';
			$requests_table = $wpdb->prefix . 'lgp_service_requests';

			$ticket = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT t.* 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE t.id = %d AND sr.company_id = %d",
					$ticket_id,
					$company_id
				)
			);

			return ! is_null( $ticket );
		}

		return false;
	}
}
