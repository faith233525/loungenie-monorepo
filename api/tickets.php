<?php

/**
 * Tickets REST API Endpoints
 *
 * Handles all REST API endpoints for ticket management including creation,
 * retrieval, updates, and replies. Implements role-based access control
 * with CSRF protection for state-changing operations.
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

namespace LounGenie\Portal;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Tickets API Handler
 *
 * Manages ticket lifecycle through REST API endpoints with comprehensive
 * security controls including authentication, authorization, and CSRF protection.
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Tickets_API
{



	/**
	 * Initialize API endpoints.
	 *
	 * Registers the REST API initialization hook that will register all
	 * ticket-related endpoints when the REST API is initialized.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers all ticket-related REST API endpoints:
	 * - GET /lgp/v1/tickets - List tickets (paginated)
	 * - GET /lgp/v1/tickets/{id} - Get single ticket
	 * - POST /lgp/v1/tickets - Create new ticket (Partners only)
	 * - PUT /lgp/v1/tickets/{id} - Update ticket status (Support only)
	 * - POST /lgp/v1/tickets/{id}/reply - Add reply to ticket thread
	 *
	 * Each endpoint includes appropriate permission callbacks with CSRF
	 * protection for state-changing operations (POST, PUT).
	 *
	 * @since 2.0.0
	 * @return void
	 * @see register_rest_route()
	 */
	public static function register_routes()
	{
		// Get tickets
		register_rest_route(
			'lgp/v1',
			'/tickets',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_tickets'),
				'permission_callback' => array(__CLASS__, 'check_portal_permission'),
				// Security: Read-only operation, no nonce required
			)
		);

		// Get single ticket
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_ticket'),
				'permission_callback' => array(__CLASS__, 'check_ticket_permission'),
			)
		);

		// Create ticket/service request (Partners)
		register_rest_route(
			'lgp/v1',
			'/tickets',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'create_ticket'),
				'permission_callback' => array(__CLASS__, 'check_partner_permission_with_nonce'),
				// Security: State-changing operation requires CSRF protection
			)
		);

		// Update ticket status (Support only)
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array(__CLASS__, 'update_ticket'),
				'permission_callback' => array(__CLASS__, 'check_support_permission_with_nonce'),
				// Security: State-changing operation requires CSRF protection
			)
		);

		// Add reply to ticket thread
		register_rest_route(
			'lgp/v1',
			'/tickets/(?P<id>\d+)/reply',
			array(
				'methods'             => 'POST',
				'callback'            => array(__CLASS__, 'add_reply'),
				'permission_callback' => array(__CLASS__, 'check_portal_permission_with_nonce'),
				// Security: State-changing operation requires CSRF protection
			)
		);
	}

	/**
	 * Get tickets with pagination and role-based filtering.
	 *
	 * Returns a paginated list of tickets. Support users see all tickets,
	 * while Partners only see tickets belonging to their company. Results
	 * include ticket details, associated service request information, and
	 * company names.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - page (int) Optional. Page number. Default 1.
	 *                                 - per_page (int) Optional. Results per page. Default 20.
	 * @return WP_REST_Response Response containing:
	 *                          - tickets (array) List of ticket objects.
	 *                          - total (int) Total number of tickets.
	 *                          - page (int) Current page number.
	 *                          - per_page (int) Items per page.
	 */
	public static function get_tickets($request)
	{
		global $wpdb;

		$tickets_table   = $wpdb->prefix . 'lgp_tickets';
		$requests_table  = $wpdb->prefix . 'lgp_service_requests';
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$page            = $request->get_param('page') ?: 1;
		$per_page        = $request->get_param('per_page') ?: 20;
		$offset          = ($page - 1) * $per_page;

		// PERFORMANCE OPTIMIZATION: Add transient caching for ticket lists
		$is_support = LGP_Auth::is_support();
		$company_id = $is_support ? 'all' : LGP_Auth::get_user_company_id();
		$cache_key  = 'lgp_tickets_list_' . $company_id . '_page_' . $page . '_per_' . $per_page;
		$cached     = get_transient($cache_key);

		if (false !== $cached) {
			return rest_ensure_response($cached);
		}

		$tickets = array();
		$total   = 0;

		if (LGP_Auth::is_support()) {
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
			// Error handling
			if (null === $tickets) {
				error_log(sprintf('LounGenie Portal: Get tickets query failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error));
				$tickets = array();
			}
			$total = $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table");
			if (null === $total) {
				error_log(sprintf('LounGenie Portal: Count tickets query failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error));
				$total = 0;
			}
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
			// Error handling
			if (null === $tickets) {
				error_log(sprintf('LounGenie Portal: Get partner tickets query failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error));
				$tickets = array();
			}
			$total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE sr.company_id = %d",
					$company_id
				)
			);
			// Error handling
			if (null === $total) {
				error_log(sprintf('LounGenie Portal: Count partner tickets query failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error));
				$total = 0;
			}
		}

		$response = array(
			'tickets'  => $tickets,
			'total'    => (int) $total,
			'page'     => (int) $page,
			'per_page' => (int) $per_page,
		);

		// PERFORMANCE: Cache ticket list for 2 minutes
		set_transient($cache_key, $response, 2 * MINUTE_IN_SECONDS);

		return rest_ensure_response($response);
	}

	/**
	 * Get single ticket by ID.
	 *
	 * Retrieves detailed information for a specific ticket including
	 * associated service request details and company information.
	 * Access is controlled by check_ticket_permission().
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Ticket ID from URL parameter.
	 * @return WP_REST_Response|WP_Error Ticket object on success, WP_Error if not found.
	 */
	public static function get_ticket($request)
	{
		global $wpdb;

		$id              = (int) $request->get_param('id');
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

		if (! $ticket) {
			return new WP_Error('not_found', __('Ticket not found', 'loungenie-portal'), array('status' => 404));
		}

		return rest_ensure_response($ticket);
	}

	/**
	 * Create new ticket from service request.
	 *
	 * Creates both a service request and associated ticket in a single atomic
	 * transaction. Partners can create tickets for their own company. The request
	 * creates an initial thread entry with the submitted details.
	 *
	 * Process:
	 * 1. Validates and sanitizes input parameters
	 * 2. Creates service request record
	 * 3. Creates ticket with initial thread entry
	 * 4. Logs audit event
	 * 5. Fires 'lgp_ticket_created' action for integrations
	 *
	 * Uses database transactions to ensure atomicity - both records are created
	 * or neither is created.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - unit_id (int) Required. Unit ID for the request.
	 *                                 - priority (string) Optional. Priority level (normal, high). Default 'normal'.
	 *                                 - request_type (string) Optional. Type of request. Default 'general'.
	 *                                 - contact_name (string) Optional. Contact person name.
	 *                                 - contact_email (string) Optional. Contact email address.
	 *                                 - contact_phone (string) Optional. Contact phone number.
	 *                                 - units_affected (int) Optional. Number of units affected.
	 *                                 - notes (string) Required. Issue description.
	 * @return WP_REST_Response|WP_Error Response with ticket_id and service_request_id on success,
	 *                                   WP_Error on validation or database failure.
	 * @throws Exception When database operations fail, triggers transaction rollback.
	 */
	public static function create_ticket($request)
	{
		global $wpdb;

		$company_id   = LGP_Auth::get_user_company_id();
		$unit_id      = absint($request->get_param('unit_id'));
		$priority     = sanitize_text_field($request->get_param('priority') ?: 'normal');
		$request_type = sanitize_text_field($request->get_param('request_type') ?: 'general');

		$contact_name   = sanitize_text_field($request->get_param('contact_name'));
		$contact_email  = sanitize_email($request->get_param('contact_email'));
		$contact_phone  = sanitize_text_field($request->get_param('contact_phone'));
		$units_affected = absint($request->get_param('units_affected'));
		$notes_raw      = sanitize_textarea_field($request->get_param('notes'));

		// Build a safe note that captures minimal form data
		$notes_parts = array();
		if ($contact_name) {
			$notes_parts[] = 'Contact: ' . $contact_name;
		}
		if ($contact_email) {
			$notes_parts[] = 'Email: ' . $contact_email;
		}
		if ($contact_phone) {
			$notes_parts[] = 'Phone: ' . $contact_phone;
		}
		if ($units_affected) {
			$notes_parts[] = 'Units affected: ' . $units_affected;
		}
		if ($notes_raw) {
			$notes_parts[] = 'Issue: ' . $notes_raw;
		}

		$notes_combined = implode("\n", array_filter($notes_parts));
		if (empty($notes_combined)) {
			return new WP_Error('invalid_message', __('Please provide issue details.', 'loungenie-portal'), array('status' => 400));
		}

		// START TRANSACTION for atomic ticket creation
		$wpdb->query('START TRANSACTION');

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

			$inserted = $wpdb->insert($requests_table, $request_data);

			if ($inserted === false) {
				throw new Exception('Failed to create service request');
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
							'timestamp' => current_time('mysql'),
							'user'      => wp_get_current_user()->display_name,
							'message'   => $request_data['notes'],
						),
					)
				),
			);

			$inserted_ticket = $wpdb->insert($tickets_table, $ticket_data);

			if ($inserted_ticket === false) {
				throw new Exception('Failed to create ticket');
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
			$wpdb->query('COMMIT');

			// Fire action for integrations (after successful commit)
			do_action('lgp_ticket_created', $ticket_id, (object) array_merge((array) $ticket_data, (array) $request_data));

			return rest_ensure_response(
				array(
					'ticket_id'          => $ticket_id,
					'service_request_id' => $service_request_id,
					'message'            => __('Service request submitted successfully', 'loungenie-portal'),
				)
			);
		} catch (Exception $e) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query('ROLLBACK');

			// Helpful debug during tests
			error_log('LGP ticket create error: ' . $e->getMessage());

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
				__('Failed to create service request', 'loungenie-portal'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Update ticket status.
	 *
	 * Updates the status of an existing ticket. Only Support users can update
	 * tickets. Uses database transactions to ensure atomic updates with audit
	 * logging. Records both old and new status for audit trail.
	 *
	 * Process:
	 * 1. Locks ticket row for update (prevents race conditions)
	 * 2. Captures old status for audit trail
	 * 3. Updates status and timestamp
	 * 4. Logs audit event with status change details
	 * 5. Fires 'lgp_ticket_updated' action for integrations
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Ticket ID from URL parameter.
	 *                                 - status (string) Required. New ticket status.
	 * @return WP_REST_Response|WP_Error Response with success message on update,
	 *                                   WP_Error if ticket not found or update fails.
	 * @throws Exception When database operations fail, triggers transaction rollback.
	 */
	public static function update_ticket($request)
	{
		global $wpdb;

		$id         = (int) $request->get_param('id');
		$new_status = sanitize_text_field($request->get_param('status'));
		$table      = $wpdb->prefix . 'lgp_tickets';

		// START TRANSACTION for atomic update
		$wpdb->query('START TRANSACTION');

		try {
			// Get current ticket for audit trail
			$old_ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d FOR UPDATE", $id));

			if (! $old_ticket) {
				throw new Exception('Ticket not found');
			}

			$old_status = $old_ticket->status;

			// Update ticket status and timestamp
			$data = array(
				'status'     => $new_status,
				'updated_at' => current_time('mysql'),
			);

			$updated = $wpdb->update($table, $data, array('id' => $id));

			if ($updated === false) {
				throw new Exception('Database update failed');
			}

			// Get company context for audit logging
			$requests_table  = $wpdb->prefix . 'lgp_service_requests';
			$service_request = $wpdb->get_row($wpdb->prepare("SELECT company_id FROM $requests_table WHERE id = %d", $old_ticket->service_request_id));

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
			$wpdb->query('COMMIT');

			// Fire action for integrations (after successful commit)
			do_action('lgp_ticket_updated', $id, $new_status, $old_status);

			return rest_ensure_response(
				array(
					'message'   => __('Ticket updated successfully', 'loungenie-portal'),
					'ticket_id' => $id,
					'status'    => $new_status,
				)
			);
		} catch (Exception $e) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query('ROLLBACK');

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
				__('Failed to update ticket', 'loungenie-portal'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Add reply to ticket thread.
	 *
	 * Appends a new message to the ticket's thread history. Both Partners and
	 * Support users can add replies. Uses row-level locking to prevent race
	 * conditions when multiple users reply simultaneously.
	 *
	 * Process:
	 * 1. Validates message is not empty
	 * 2. Locks ticket row for update (prevents concurrent modification)
	 * 3. Retrieves and decodes current thread history
	 * 4. Appends new reply with timestamp and user info
	 * 5. Updates ticket with new thread and timestamp
	 * 6. Logs audit event
	 * 7. Fires 'lgp_ticket_reply_added' action for integrations
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing:
	 *                                 - id (int) Required. Ticket ID from URL parameter.
	 *                                 - message (string) Required. Reply message content.
	 * @return WP_REST_Response|WP_Error Response with success message on success,
	 *                                   WP_Error if message empty, ticket not found, or update fails.
	 * @throws Exception When database operations fail, triggers transaction rollback.
	 */
	public static function add_reply($request)
	{
		global $wpdb;

		$id      = (int) $request->get_param('id');
		$message = sanitize_textarea_field($request->get_param('message'));
		$table   = $wpdb->prefix . 'lgp_tickets';

		if (empty($message)) {
			return new WP_Error(
				'invalid_message',
				__('Reply message cannot be empty', 'loungenie-portal'),
				array('status' => 400)
			);
		}

		// START TRANSACTION for atomic reply addition
		$wpdb->query('START TRANSACTION');

		try {
			// Get current thread history with row lock
			$ticket = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE id = %d FOR UPDATE",
					$id
				)
			);

			if (! $ticket) {
				throw new Exception('Ticket not found');
			}

			$thread = json_decode($ticket->thread_history, true) ?: array();

			// Add new reply
			$thread[] = array(
				'timestamp' => current_time('mysql'),
				'user'      => wp_get_current_user()->display_name,
				'message'   => $message,
			);

			// Update ticket with new thread
			$updated = $wpdb->update(
				$table,
				array(
					'thread_history' => wp_json_encode($thread),
					'updated_at'     => current_time('mysql'),
				),
				array('id' => $id)
			);

			if ($updated === false) {
				throw new Exception('Failed to update thread history');
			}

			// COMMIT TRANSACTION
			$wpdb->query('COMMIT');

			// Audit logging with company context
			$requests_table  = $wpdb->prefix . 'lgp_service_requests';
			$service_request = $wpdb->get_row($wpdb->prepare("SELECT company_id FROM $requests_table WHERE id = %d", $ticket->service_request_id));
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
			do_action('lgp_ticket_reply_added', $id, $message, $ticket);

			return rest_ensure_response(
				array(
					'message'   => __('Reply added successfully', 'loungenie-portal'),
					'ticket_id' => $id,
				)
			);
		} catch (Exception $e) {
			// ROLLBACK TRANSACTION on any error
			$wpdb->query('ROLLBACK');

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
				__('Failed to add reply', 'loungenie-portal'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Check if user has portal access.
	 *
	 * Verifies that the current user has either Support or Partner role,
	 * granting them access to portal features. Used for read-only operations
	 * that don't require CSRF protection.
	 *
	 * @since 2.0.0
	 * @return bool True if user has portal access, false otherwise.
	 * @see LGP_Auth::is_support()
	 * @see LGP_Auth::is_partner()
	 */
	public static function check_portal_permission()
	{
		return LGP_Auth::is_support() || LGP_Auth::is_partner();
	}

	/**
	 * Check if user is Support.
	 *
	 * Verifies that the current user has Support role, granting them
	 * elevated privileges for ticket management. Used for operations
	 * restricted to Support staff.
	 *
	 * @since 2.0.0
	 * @return bool True if user is Support, false otherwise.
	 * @see LGP_Auth::is_support()
	 */
	public static function check_support_permission()
	{
		return LGP_Auth::is_support();
	}

	/**
	 * Check if user is Partner.
	 *
	 * Verifies that the current user has Partner role, granting them
	 * access to partner-specific features like creating service requests.
	 *
	 * @since 2.0.0
	 * @return bool True if user is Partner, false otherwise.
	 * @see LGP_Auth::is_partner()
	 */
	public static function check_partner_permission()
	{
		return LGP_Auth::is_partner();
	}

	/**
	 * Check if user can access specific ticket.
	 *
	 * Implements granular access control for individual tickets:
	 * - Support users can access all tickets
	 * - Partners can only access tickets belonging to their company
	 *
	 * Uses prepared statements to prevent SQL injection when verifying
	 * company ownership.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object containing ticket ID.
	 * @return bool True if user can access the ticket, false otherwise.
	 * @see LGP_Auth::get_user_company_id()
	 */
	public static function check_ticket_permission($request)
	{
		if (LGP_Auth::is_support()) {
			return true;
		}

		if (LGP_Auth::is_partner()) {
			global $wpdb;
			$ticket_id  = (int) $request->get_param('id');
			$company_id = LGP_Auth::get_user_company_id();

			$tickets_table  = $wpdb->prefix . 'lgp_tickets';
			$requests_table = $wpdb->prefix . 'lgp_service_requests';

			// Security: Using prepared statement to prevent SQL injection
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

			return ! is_null($ticket);
		}

		return false;
	}

	/**
	 * Check portal permission with CSRF protection.
	 *
	 * Enhanced permission check for state-changing operations (POST, PUT, DELETE).
	 * Validates both user authentication and WordPress REST API nonce to prevent
	 * Cross-Site Request Forgery (CSRF) attacks.
	 *
	 * WordPress REST API automatically validates nonces for cookie-authenticated
	 * requests, providing built-in CSRF protection.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return bool True if user has portal access and nonce is valid, false otherwise.
	 * @see check_portal_permission()
	 */
	public static function check_portal_permission_with_nonce($request)
	{
		// First check if user has portal access
		if (! self::check_portal_permission()) {
			return false;
		}

		// Verify nonce for state-changing operations
		// WordPress REST API automatically checks nonce via cookie authentication
		// This provides CSRF protection for authenticated requests
		return true;
	}

	/**
	 * Check Support permission with CSRF protection.
	 *
	 * Enhanced permission check for Support-only state-changing operations.
	 * Validates both Support role and WordPress REST API nonce to prevent
	 * CSRF attacks on privileged operations.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return bool True if user is Support and nonce is valid, false otherwise.
	 * @see check_support_permission()
	 */
	public static function check_support_permission_with_nonce($request)
	{
		// First check if user is support
		if (! self::check_support_permission()) {
			return false;
		}

		// Verify nonce for state-changing operations
		// WordPress REST API automatically checks nonce via cookie authentication
		// This provides CSRF protection for authenticated requests
		return true;
	}

	/**
	 * Check Partner permission with CSRF protection.
	 *
	 * Enhanced permission check for Partner-only state-changing operations.
	 * Validates both Partner role and WordPress REST API nonce to prevent
	 * CSRF attacks on ticket creation and modifications.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request REST API request object.
	 * @return bool True if user is Partner and nonce is valid, false otherwise.
	 * @see check_partner_permission()
	 */
	public static function check_partner_permission_with_nonce($request)
	{
		// First check if user is partner
		if (! self::check_partner_permission()) {
			return false;
		}

		// Verify nonce for state-changing operations
		// WordPress REST API automatically checks nonce via cookie authentication
		// This provides CSRF protection for authenticated requests
		return true;
	}
}
