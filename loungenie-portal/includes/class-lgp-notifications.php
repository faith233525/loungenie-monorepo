<?php
/**
 * Minimal notification helper (test-focused)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Notifications {
	/**
	 * Notify support and partner on ticket events.
	 *
	 * @param array  $ticket   Expect keys: ticket_id, company_id, partner_user_id, partner_email, support_email
	 * @param string $event    created|updated|replied|closed
	 * @param string $priority low|medium|high|urgent
	 */
	public static function notify_ticket_event( $ticket, $event, $priority = 'medium' ) {
		$ticket_id   = $ticket['ticket_id'] ?? null;
		$company_id  = $ticket['company_id'] ?? null;
		$support_to  = $ticket['support_email'] ?? 'support@poolsafeinc.com';
		$partner_to  = $ticket['partner_email'] ?? null;
		$partner_uid = $ticket['partner_user_id'] ?? null;

		$subject = sprintf( 'Ticket %s [%s]', $event, $priority );
		$message = sprintf( 'Ticket %s for company %s', $event, $company_id );

		// Support email on all events
		wp_mail( $support_to, $subject, $message );
		if ( class_exists( 'LGP_Logger' ) ) {
			LGP_Logger::log_notification(
				$support_to,
				'email',
				$priority,
				$ticket_id,
				$company_id,
				array(
					'role'  => 'support',
					'event' => $event,
				)
			);
		}

		// Partner notifications only for their own company
		if ( $partner_to && $partner_uid ) {
			wp_mail( $partner_to, $subject, $message );
			if ( function_exists( 'lgp_portal_alert' ) ) {
				lgp_portal_alert( $partner_uid, $message, $priority );
			}
			if ( class_exists( 'LGP_Logger' ) ) {
				LGP_Logger::log_notification(
					$partner_uid,
					'email',
					$priority,
					$ticket_id,
					$company_id,
					array(
						'role'  => 'partner',
						'event' => $event,
					)
				);
				LGP_Logger::log_notification(
					$partner_uid,
					'portal',
					$priority,
					$ticket_id,
					$company_id,
					array(
						'role'  => 'partner',
						'event' => $event,
					)
				);
			}
		}
	}
}

// Hook into ticket lifecycle events to send notifications
add_action( 'lgp_ticket_created', function( $ticket_id, $context ) {
	$company_id = isset( $context->company_id ) ? (int) $context->company_id : 0;
	$partner    = wp_get_current_user();
	$payload    = array(
		'ticket_id'      => $ticket_id,
		'company_id'     => $company_id,
		'partner_user_id'=> $partner ? $partner->ID : 0,
		'partner_email'  => $partner ? $partner->user_email : '',
		'support_email'  => get_option( 'lgp_support_email', 'support@poolsafeinc.com' ),
	);
	LGP_Notifications::notify_ticket_event( $payload, 'created', 'medium' );
}, 10, 2 );

add_action( 'lgp_ticket_updated', function( $ticket_id, $new_status ) {
	$company_id = method_exists( 'LGP_Auth', 'get_user_company_id' ) ? LGP_Auth::get_user_company_id() : 0;
	$partner    = wp_get_current_user();
	$payload    = array(
		'ticket_id'      => (int) $ticket_id,
		'company_id'     => (int) $company_id,
		'partner_user_id'=> $partner ? $partner->ID : 0,
		'partner_email'  => $partner ? $partner->user_email : '',
		'support_email'  => get_option( 'lgp_support_email', 'support@poolsafeinc.com' ),
	);
	LGP_Notifications::notify_ticket_event( $payload, 'updated', 'medium' );
}, 10, 2 );

add_action( 'lgp_ticket_reply_added', function( $ticket_id, $message, $ticket_obj ) {
	$company_id = isset( $ticket_obj->company_id ) ? (int) $ticket_obj->company_id : 0;
	$partner    = wp_get_current_user();
	$payload    = array(
		'ticket_id'      => (int) $ticket_id,
		'company_id'     => (int) $company_id,
		'partner_user_id'=> $partner ? $partner->ID : 0,
		'partner_email'  => $partner ? $partner->user_email : '',
		'support_email'  => get_option( 'lgp_support_email', 'support@poolsafeinc.com' ),
	);
	LGP_Notifications::notify_ticket_event( $payload, 'replied', 'medium' );
}, 10, 3 );
