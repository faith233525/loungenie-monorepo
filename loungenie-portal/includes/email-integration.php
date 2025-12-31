<?php
/**
 * Email Integration Hooks and Filters
 *
 * Integrates email ingestion and reply handlers with WordPress.
 *
 * @package loungenie-portal
 */

// Register custom cron schedules for 5 and 10 minutes
add_filter(
    'cron_schedules',
    function ( $schedules ) {
        if (! isset($schedules['5-minute']) ) {
            $schedules['5-minute'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __('Every 5 Minutes', 'loungenie-portal'),
            );
        }

        if (! isset($schedules['10-minute']) ) {
            $schedules['10-minute'] = array(
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display'  => __('Every 10 Minutes', 'loungenie-portal'),
            );
        }

        return $schedules;
    }
);

// Register scheduled sync
add_action(
    'wp_loaded',
    function () {
        if (! wp_next_scheduled('lgp_sync_emails') ) {
            wp_schedule_event(time(), '5-minute', 'lgp_sync_emails');
        }

        if (! wp_next_scheduled('lgp_detect_outlook_replies') ) {
            wp_schedule_event(time() + 120, '10-minute', 'lgp_detect_outlook_replies');
        }

        // Deconflict: ensure legacy schedulers are disabled while new pipeline is active
        if (function_exists('wp_clear_scheduled_hook') ) {
            wp_clear_scheduled_hook('lgp_process_emails'); // legacy handler
            wp_clear_scheduled_hook('lgp_sync_shared_mailbox'); // legacy shared mailbox
        }
    }
);

/**
 * Hook: Sync emails from shared mailbox
 */
add_action(
    'lgp_sync_emails',
    function () {
        try {
            $ingest = new LGP_Email_Ingest();
            $stats  = $ingest->sync_messages();

            // Optional: Send admin notification if there are errors
            if ($stats['errors'] > 0 ) {
                error_log('Email sync errors: ' . wp_json_encode($stats));
            }
        } catch ( Exception $e ) {
            error_log('Email sync failed: ' . $e->getMessage());
        }
    }
);

/**
 * Hook: Detect Outlook replies to tickets
 */
add_action(
    'lgp_detect_outlook_replies',
    function () {
        try {
            $reply_handler = new LGP_Email_Reply();
            $count         = $reply_handler->detect_outlook_replies();

            if ($count > 0 ) {
                error_log("Detected $count Outlook replies");
            }
        } catch ( Exception $e ) {
            error_log('Outlook reply detection failed: ' . $e->getMessage());
        }
    }
);

/**
 * Hook: Send portal reply via email
 *
 * When a user replies to a ticket in the portal, send the reply via email.
 */
add_action(
    'comment_post',
    function ( $comment_id, $comment ) {
        // Only handle ticket replies
        if ('ticket_reply' !== $comment->comment_type ) {
            return;
        }

        // Check if this is an email source ticket
        $ticket_id       = $comment->comment_post_ID;
        $is_email_ticket = get_post_meta($ticket_id, '_email_source', true);

        if (! $is_email_ticket ) {
            return;
        }

        // Check if reply was already sent via email
        $email_sent = get_comment_meta($comment_id, '_email_sent', true);
        if ($email_sent ) {
            return;
        }

        try {
            $reply_handler = new LGP_Email_Reply();
            $author_id     = isset($comment->user_id) ? $comment->user_id : 1;

            // Build attachments from comment meta
            $attachments     = get_comment_meta($comment_id, '_attachment', false);
            $attachment_data = array();

            foreach ( $attachments as $att ) {
                $attachment_data[] = array(
                'path' => $att['path'],
                'name' => $att['name'],
                );
            }

            // Send reply
            $reply_handler->send_reply(
                $ticket_id,
                $comment->comment_content,
                $author_id,
                $attachment_data
            );
        } catch ( Exception $e ) {
            // Log error but don't fail the comment creation
            error_log('Failed to send email reply: ' . $e->getMessage());

            // Store error in comment meta for later review
            add_comment_meta($comment_id, '_email_send_error', $e->getMessage());
        }
    },
    10,
    2
);

/**
 * Filter: Add email info to ticket meta
 */
add_filter(
    'lgp_ticket_meta',
    function ( $meta, $ticket_id ) {
        $email_source = get_post_meta($ticket_id, '_email_source', true);

        if ($email_source ) {
            $meta['email'] = array(
            'sender_email'        => get_post_meta($ticket_id, '_sender_email', true),
            'message_id'          => get_post_meta($ticket_id, '_email_message_id', true),
            'conversation_id'     => get_post_meta($ticket_id, '_email_conversation_id', true),
            'internet_message_id' => get_post_meta($ticket_id, '_email_internet_message_id', true),
            'received_date'       => get_post_meta($ticket_id, '_received_date', true),
            );
        }

        return $meta;
    },
    10,
    2
);

/**
 * Filter: Get email status for ticket
 *
 * Returns status information about email processing for a ticket.
 */
add_filter(
    'lgp_get_ticket_email_status',
    function ( $ticket_id ) {
        $status = array(
        'is_email_ticket'     => (bool) get_post_meta($ticket_id, '_email_source', true),
        'sender_email'        => get_post_meta($ticket_id, '_sender_email', true),
        'replies_via_outlook' => 0,
        'replies_via_portal'  => 0,
        'total_replies'       => 0,
        );

        if (! $status['is_email_ticket'] ) {
            return $status;
        }

        $comments = get_comments(
            array(
            'post_id' => $ticket_id,
            'type'    => 'ticket_reply',
            'status'  => 'approve',
            )
        );

        $status['total_replies'] = count($comments);

        foreach ( $comments as $comment ) {
            if (get_comment_meta($comment->comment_ID, '_sent_via_outlook', true) ) {
                $status['replies_via_outlook']++;
            } elseif (get_comment_meta($comment->comment_ID, '_sent_via_portal', true) ) {
                $status['replies_via_portal']++;
            }
        }

        return $status;
    },
    10,
    1
);

/**
 * Register REST API endpoints for email operations
 */
add_action(
    'rest_api_init',
    function () {
        // Sync emails manually
        register_rest_route(
            'lgp/v1',
            '/email/sync',
            array(
            'methods'             => 'POST',
            'callback'            => function () {
                // Check permissions
                if (! current_user_can('manage_options') ) {
                      return new WP_Error('forbidden', 'You do not have permission', array( 'status' => 403 ));
                }

                try {
                    $ingest = new LGP_Email_Ingest();
                    $stats = $ingest->sync_messages();
                    return rest_ensure_response($stats);
                } catch ( Exception $e ) {
                    return new WP_Error('sync_error', $e->getMessage());
                }
            },
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );

        // Send reply manually
        register_rest_route(
            'lgp/v1',
            '/email/send-reply',
            array(
            'methods'             => 'POST',
            'callback'            => function ( WP_REST_Request $request ) {
                $ticket_id = $request->get_param('ticket_id');
                $content = $request->get_param('content');

                if (! $ticket_id || ! $content ) {
                    return new WP_Error('invalid_params', 'Missing required parameters');
                }

                // Check ticket access
                if (! current_user_can('edit_post', $ticket_id) ) {
                    return new WP_Error('forbidden', 'You do not have permission', array( 'status' => 403 ));
                }

                try {
                    $reply_handler = new LGP_Email_Reply();
                    $comment_id = $reply_handler->send_reply(
                        $ticket_id,
                        $content,
                        get_current_user_id()
                    );

                       return rest_ensure_response(array( 'comment_id' => $comment_id ));
                } catch ( Exception $e ) {
                    return new WP_Error('send_error', $e->getMessage());
                }
            },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

        // Get ticket email status
        register_rest_route(
            'lgp/v1',
            '/email/ticket-status/(?P<ticket_id>\d+)',
            array(
            'methods'             => 'GET',
            'callback'            => function ( WP_REST_Request $request ) {
                $ticket_id = $request->get_param('ticket_id');

                if (! current_user_can('read_post', $ticket_id) ) {
                    return new WP_Error('forbidden', 'You do not have permission', array( 'status' => 403 ));
                }

                $status = apply_filters('lgp_get_ticket_email_status', $ticket_id);
                return rest_ensure_response($status);
            },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );
    }
);

/**
 * Admin notices for email configuration
 */
add_action(
    'admin_notices',
    function () {
        if (! current_user_can('manage_options') ) {
            return;
        }

        $mailbox   = get_option('lgp_shared_mailbox');
        $tenant_id = get_option('lgp_azure_tenant_id');

        if (! $mailbox || ! $tenant_id ) {
            ?>
        <div class="notice notice-warning is-dismissible">
            <p>
            <?php esc_html_e('Pool Safe Portal: Email integration is not configured. Please configure the shared mailbox in Settings.', 'loungenie-portal'); ?>
            </p>
        </div>
            <?php
        }
    }
);
