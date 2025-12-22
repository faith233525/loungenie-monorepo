<?php
/**
 * LounGenie Portal - Email Notifications (Production-Ready v1.8.0)
 * Template-based notifications with event routing
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Email_Notifications {

	/**
	 * Notification template mapping
	 */
	private static $templates = array(
		'ticket_created'    => 'Ticket Created: %s',
		'ticket_updated'    => 'Ticket Updated: %s',
		'ticket_replied'    => 'New Reply on Ticket: %s',
		'ticket_resolved'   => 'Ticket Resolved: %s',
		'ticket_closed'     => 'Ticket Closed: %s',
		'ticket_reassigned' => 'Ticket Reassigned: %s',
	);

	/**
	 * Initialize notifications
	 */
	public static function init() {
		// Hook for ticket events
		add_action( 'lgp_ticket_created', array( __CLASS__, 'on_ticket_created' ), 10, 3 );
		add_action( 'lgp_ticket_updated', array( __CLASS__, 'on_ticket_updated' ), 10, 3 );
		add_action( 'lgp_ticket_replied', array( __CLASS__, 'on_ticket_replied' ), 10, 3 );
		add_action( 'lgp_ticket_status_changed', array( __CLASS__, 'on_ticket_status_changed' ), 10, 4 );
	}

	/**
	 * Notify when ticket is created
	 *
	 * @param int   $ticket_id Ticket ID
	 * @param int   $company_id Company ID
	 * @param int   $user_id User ID who created
	 * @param array $email_data Email data
	 */
	public static function notify_ticket_created( $ticket_id, $company_id, $user_id, $email_data = array() ) {
		global $wpdb;

		// Get ticket and request data
		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_tickets WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket ) {
			return;
		}

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_service_requests WHERE id = %d",
				$ticket->service_request_id
			)
		);

		// Get company data
		$company = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
				$company_id
			)
		);

		// Build notification payload
		$notification = array(
			'ticket_id'     => $ticket_id,
			'ticket_number' => sprintf( '#%d', $ticket_id ),
			'company_id'    => $company_id,
			'company_name'  => $company ? $company->name : 'Unknown Company',
			'priority'      => $request ? $request->priority : 'medium',
			'status'        => $request ? $request->status : 'open',
			'created_by'    => $user_id,
			'created_at'    => $ticket->created_at,
			'content'       => isset( $email_data['body'] ) ? substr( $email_data['body'], 0, 500 ) : '',
			'email'         => isset( $email_data['from'] ) ? $email_data['from'] : '',
		);

		// Send to Support Team (always)
		self::send_to_support_team( $notification, 'ticket_created' );

		// Send to Partner Company (if their ticket)
		self::send_to_partner_company( $notification, 'ticket_created' );

		// Log notification
		self::log_notification( 'created', $ticket_id, $company_id, $user_id );
	}

	/**
	 * Notify when ticket is updated
	 *
	 * @param int $ticket_id Ticket ID
	 * @param int $company_id Company ID
	 * @param int $updated_by User ID who updated
	 */
	public static function on_ticket_updated( $ticket_id, $company_id, $updated_by ) {
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

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lgp_service_requests WHERE id = %d",
				$ticket->service_request_id
			)
		);

		$user = get_user_by( 'id', $updated_by );

		$notification = array(
			'ticket_id'  => $ticket_id,
			'company_id' => $company_id,
			'priority'   => $request ? $request->priority : 'medium',
			'updated_by' => $user ? $user->display_name : 'Support Team',
			'updated_at' => $ticket->updated_at,
		);

		self::send_to_support_team( $notification, 'ticket_updated' );
		self::send_to_partner_company( $notification, 'ticket_updated' );
		self::log_notification( 'updated', $ticket_id, $company_id, $updated_by );
	}

	/**
	 * Notify when ticket has new reply
	 *
	 * @param int $ticket_id Ticket ID
	 * @param int $company_id Company ID
	 * @param int $replied_by User ID who replied
	 */
	public static function on_ticket_replied( $ticket_id, $company_id, $replied_by ) {
		$user = get_user_by( 'id', $replied_by );

		$notification = array(
			'ticket_id'  => $ticket_id,
			'company_id' => $company_id,
			'replied_by' => $user ? $user->display_name : 'Support Team',
		);

		self::send_to_support_team( $notification, 'ticket_replied' );
		self::send_to_partner_company( $notification, 'ticket_replied' );
		self::log_notification( 'replied', $ticket_id, $company_id, $replied_by );
	}

	/**
	 * Notify when ticket status changes
	 *
	 * @param int    $ticket_id Ticket ID
	 * @param string $old_status Old status
	 * @param string $new_status New status
	 * @param int    $changed_by User ID who changed status
	 */
	public static function on_ticket_status_changed( $ticket_id, $old_status, $new_status, $changed_by ) {
		$event = 'ticket_' . $new_status; // e.g., 'ticket_resolved', 'ticket_closed'

		$notification = array(
			'ticket_id'  => $ticket_id,
			'old_status' => $old_status,
			'new_status' => $new_status,
			'changed_by' => $changed_by,
		);

		self::send_to_support_team( $notification, $event );
		self::send_to_partner_company( $notification, $event );
		self::log_notification( $new_status, $ticket_id, 0, $changed_by );
	}

	/**
	 * Send notification to Support Team
	 *
	 * @param array  $notification Notification data
	 * @param string $event Event type
	 */
	private static function send_to_support_team( $notification, $event ) {
		// Get all support team members
		$support_users = get_users(
			array(
				'role'   => 'lgp_support',
				'fields' => array( 'ID', 'user_email', 'display_name' ),
			)
		);

		if ( empty( $support_users ) ) {
			// Fallback to admin email
			$support_users = array(
				(object) array(
					'user_email'   => get_option( 'admin_email' ),
					'display_name' => 'Administrator',
				),
			);
		}

		foreach ( $support_users as $user ) {
			self::send_notification_email(
				$user->user_email,
				$user->display_name,
				$notification,
				$event,
				'support'
			);
		}
	}

	/**
	 * Send notification to Partner Company
	 *
	 * @param array  $notification Notification data
	 * @param string $event Event type
	 */
	private static function send_to_partner_company( $notification, $event ) {
		global $wpdb;

		$company_id = $notification['company_id'];

		// Get all partner users for this company
		$partner_users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID, u.user_email, u.display_name 
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
				WHERE um.meta_key = '_lgp_company_id' AND um.meta_value = %d",
				$company_id
			)
		);

		// Also check for users with lgp_partner role
		$partner_role_users = get_users(
			array(
				'role'   => 'lgp_partner',
				'fields' => array( 'ID', 'user_email', 'display_name' ),
			)
		);

		// Merge and deduplicate
		$users_by_id = array();
		foreach ( $partner_users as $user ) {
			$users_by_id[ $user->ID ] = $user;
		}
		foreach ( $partner_role_users as $user ) {
			$company = get_user_meta( $user->ID, '_lgp_company_id', true );
			if ( $company == $company_id ) {
				$users_by_id[ $user->ID ] = $user;
			}
		}

		if ( empty( $users_by_id ) ) {
			return;
		}

		foreach ( $users_by_id as $user ) {
			self::send_notification_email(
				$user->user_email,
				$user->display_name,
				$notification,
				$event,
				'partner'
			);
		}
	}

	/**
	 * Send actual notification email
	 *
	 * @param string $to_email Recipient email
	 * @param string $recipient_name Recipient name
	 * @param array  $notification Notification data
	 * @param string $event Event type
	 * @param string $recipient_type support|partner
	 */
	private static function send_notification_email( $to_email, $recipient_name, $notification, $event, $recipient_type ) {
		// Get email template
		$template = self::get_email_template( $event, $recipient_type );

		if ( ! $template ) {
			return;
		}

		// Prepare email
		$subject = self::prepare_subject( $event, $notification );
		$message = self::prepare_message( $template, $notification, $recipient_type, $recipient_name );
		$headers = self::get_email_headers();

		// Send email
		$result = wp_mail( $to_email, $subject, $message, $headers );

		if ( ! $result ) {
			error_log( "LGP: Failed to send notification email to $to_email for event $event" );
		}
	}

	/**
	 * Get email template for event
	 *
	 * @param string $event Event type
	 * @param string $recipient_type support|partner
	 * @return string|false Template or false
	 */
	private static function get_email_template( $event, $recipient_type ) {
		$template_key = "lgp_email_template_$event" . ( $recipient_type === 'partner' ? '_partner' : '' );
		$template     = get_option( $template_key );

		if ( $template ) {
			return $template;
		}

		// Return default template
		return self::get_default_template( $event );
	}

	/**
	 * Get default email template
	 *
	 * @param string $event Event type
	 * @return string Template
	 */
	private static function get_default_template( $event ) {
		$templates = array(
			'ticket_created'  => "A new support ticket has been created.\n\nTicket #%ticket_id%\nCompany: %company_name%\nPriority: %priority%\nStatus: %status%\nCreated: %created_at%\n\nContent:\n%content%",
			'ticket_updated'  => "Ticket #%ticket_id% has been updated.\n\nUpdated by: %updated_by%\nPriority: %priority%\nTime: %updated_at%",
			'ticket_replied'  => "There's a new reply on ticket #%ticket_id%.\n\nReply from: %replied_by%",
			'ticket_resolved' => "Ticket #%ticket_id% has been marked as resolved.\n\nCompany: %company_name%",
			'ticket_closed'   => "Ticket #%ticket_id% has been closed.\n\nCompany: %company_name%",
		);

		return isset( $templates[ $event ] ) ? $templates[ $event ] : '';
	}

	/**
	 * Prepare email subject
	 *
	 * @param string $event Event type
	 * @param array  $notification Notification data
	 * @return string Subject
	 */
	private static function prepare_subject( $event, $notification ) {
		$template = isset( self::$templates[ $event ] ) ? self::$templates[ $event ] : 'Ticket Notification';

		$subject = sprintf(
			$template,
			$notification['ticket_number'] ?? $notification['ticket_id'] ?? ''
		);

		return apply_filters( 'lgp_notification_subject', $subject, $event, $notification );
	}

	/**
	 * Prepare email message
	 *
	 * @param string $template Email template
	 * @param array  $notification Notification data
	 * @param string $recipient_type support|partner
	 * @param string $recipient_name Recipient name
	 * @return string Message
	 */
	private static function prepare_message( $template, $notification, $recipient_type, $recipient_name ) {
		$replacements = array(
			'%recipient_name%' => $recipient_name,
			'%ticket_id%'      => $notification['ticket_id'] ?? '',
			'%ticket_number%'  => $notification['ticket_number'] ?? '',
			'%company_name%'   => $notification['company_name'] ?? '',
			'%priority%'       => ucfirst( $notification['priority'] ?? 'normal' ),
			'%status%'         => ucfirst( $notification['status'] ?? 'open' ),
			'%content%'        => $notification['content'] ?? '',
			'%email%'          => $notification['email'] ?? '',
			'%created_at%'     => $notification['created_at'] ?? '',
			'%updated_at%'     => $notification['updated_at'] ?? '',
			'%updated_by%'     => $notification['updated_by'] ?? '',
			'%replied_by%'     => $notification['replied_by'] ?? '',
			'%site_name%'      => get_option( 'blogname' ),
			'%portal_url%'     => $recipient_type === 'partner' ? home_url( '/partner-login' ) : home_url( '/support-login' ),
		);

		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		// Add footer
		$footer = sprintf(
			"\n\nBest regards,\n%s Support Team\n%s",
			get_option( 'blogname' ),
			home_url()
		);

		$message .= $footer;

		return apply_filters( 'lgp_notification_message', $message, $template, $notification );
	}

	/**
	 * Get email headers
	 *
	 * @return array|string Email headers
	 */
	private static function get_email_headers() {
		$from      = apply_filters( 'lgp_email_from_address', get_option( 'admin_email' ) );
		$from_name = apply_filters( 'lgp_email_from_name', get_option( 'blogname' ) . ' Support' );

		return array(
			'From: ' . $from_name . ' <' . $from . '>',
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $from,
		);
	}

	/**
	 * Log notification for audit
	 *
	 * @param string $event Event type
	 * @param int    $ticket_id Ticket ID
	 * @param int    $company_id Company ID
	 * @param int    $user_id User ID
	 */
	private static function log_notification( $event, $ticket_id, $company_id, $user_id ) {
		if ( ! class_exists( 'LGP_Logger' ) ) {
			return;
		}

		LGP_Logger::log_event(
			$user_id,
			"notification_sent_$event",
			array(
				'ticket_id'  => $ticket_id,
				'company_id' => $company_id,
			)
		);
	}
}

// Initialize on every page load
LGP_Email_Notifications::init();
