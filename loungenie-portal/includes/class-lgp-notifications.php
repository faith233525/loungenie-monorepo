<?php
/**
 * Minimal notification helper (test-focused)
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Notifications
{
    /**
     * Initialize notifications
     */
    public static function init()
    {
        // Notifications are passive - triggered by other components
        // No hooks needed at init time
    }

    /**
     * Notify support and partner on ticket events.
     *
     * @param array  $ticket   Expect keys: ticket_id, company_id, partner_user_id, partner_email, support_email
     * @param string $event    created|updated|replied|closed
     * @param string $priority low|medium|high|urgent
     */
    public static function notify_ticket_event( $ticket, $event, $priority = 'medium' )
    {
        $ticket_id   = $ticket['ticket_id'] ?? null;
        $company_id  = $ticket['company_id'] ?? null;
        $support_to  = $ticket['support_email'] ?? 'support@poolsafeinc.com';
        $partner_to  = $ticket['partner_email'] ?? null;
        $partner_uid = $ticket['partner_user_id'] ?? null;

        $subject = sprintf('Ticket %s [%s]', $event, $priority);
        $message = sprintf('Ticket %s for company %s', $event, $company_id);

        // Support email on all events
        wp_mail($support_to, $subject, $message);
        if (class_exists('LGP_Logger') ) {
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
        if ($partner_to && $partner_uid ) {
            wp_mail($partner_to, $subject, $message);
            if (function_exists('lgp_portal_alert') ) {
                lgp_portal_alert($partner_uid, $message, $priority);
            }
            if (class_exists('LGP_Logger') ) {
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
