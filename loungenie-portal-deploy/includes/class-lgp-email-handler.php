<?php
/**
 * LounGenie Portal - Email Handler
 * Converts incoming support emails to tickets
 * v1.7.0
 */

if (!defined('ABSPATH')) exit;

class LGP_Email_Handler {
    
    private static $option_key = 'lgp_email_settings';
    
    /**
     * Initialize email handler
     */
    public static function init() {
        // Schedule email processing
        if (!wp_next_scheduled('lgp_process_emails')) {
            wp_schedule_event(time(), 'five_minutes', 'lgp_process_emails');
        }
        
        add_action('lgp_process_emails', [__CLASS__, 'process_emails']);
    }
    
    /**
     * Process all pending emails
     */
    public static function process_emails() {
        $settings = get_option(self::$option_key, []);
        
        if (empty($settings['pop3_server']) || empty($settings['pop3_username']) || empty($settings['pop3_password'])) {
            error_log('LGP: Email settings not configured');
            return;
        }
        
        try {
            // Connect to POP3
            $mailbox = '{' . $settings['pop3_server'] . ':110/pop3}INBOX';
            
            // Suppress warnings
            $connection = @imap_open($mailbox, $settings['pop3_username'], $settings['pop3_password']);
            
            if (!$connection) {
                error_log('LGP: POP3 connection failed: ' . imap_last_error());
                return;
            }
            
            // Get all emails
            $emails = imap_search($connection, 'ALL');
            
            if ($emails) {
                // Process in reverse order (oldest first)
                foreach (array_reverse($emails) as $email_id) {
                    self::process_email($connection, $email_id);
                }
            }
            
            imap_close($connection);
            
        } catch (Exception $e) {
            error_log('LGP Email Handler Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Process single email
     */
    private static function process_email($connection, $email_id) {
        try {
            // Get email header
            $header = imap_headerinfo($connection, $email_id);
            
            if (!$header) {
                return;
            }
            
            // Get email body
            $body = self::get_email_body($connection, $email_id);
            
            // Extract priority from subject
            $priority = 'medium';
            if (preg_match('/\[URGENT\]|\[CRITICAL\]|URGENT|CRITICAL/i', $header->subject)) {
                $priority = 'high';
            }
            if (preg_match('/\[LOW\]|LOW PRIORITY/i', $header->subject)) {
                $priority = 'low';
            }
            
            // Get sender email
            $from = '';
            if (!empty($header->from[0]->mailbox) && !empty($header->from[0]->host)) {
                $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
            }
            
            if (empty($from)) {
                error_log('LGP: Email has no valid sender');
                imap_delete($connection, $email_id);
                imap_expunge($connection);
                return;
            }
            
            // Find company by email domain
            $company = self::find_company_by_email($from);
            
            if (!$company) {
                error_log("LGP: Email from unknown company: $from");
                imap_delete($connection, $email_id);
                imap_expunge($connection);
                return;
            }
            
            // Get or create user contact
            $user_id = self::get_or_create_contact($company, $from, $header);
            
            // Create ticket
            $ticket_id = self::create_ticket([
                'subject'      => $header->subject,
                'description'  => $body,
                'priority'     => $priority,
                'company_id'   => $company->ID,
                'status'       => 'open',
                'source'       => 'email',
                'source_email' => $from,
                'created_by'   => $user_id,
                'email_msg_id' => $header->message_id ?? '',
            ]);
            
            if (!$ticket_id) {
                error_log('LGP: Failed to create ticket from email');
                imap_delete($connection, $email_id);
                imap_expunge($connection);
                return;
            }
            
            // Process attachments
            self::process_attachments($connection, $email_id, $ticket_id);
            
            // Send confirmation email
            self::send_confirmation_email($company, $user_id, $ticket_id);
            
            // Mark email as read and delete
            imap_delete($connection, $email_id);
            
        } catch (Exception $e) {
            error_log('LGP: Email processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get email body (handle multipart)
     */
    private static function get_email_body($connection, $email_id) {
        $body = '';
        $structure = imap_fetchstructure($connection, $email_id);
        
        if (!$structure->parts) {
            // Simple email
            $body = imap_fetchbody($connection, $email_id, '1');
            
            // Handle encoding
            if ($structure->encoding == 3) { // BASE64
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) { // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
            }
        } else {
            // Multipart email - get text part
            foreach ($structure->parts as $part_id => $part) {
                if (strtolower($part->subtype) === 'plain') {
                    $body = imap_fetchbody($connection, $email_id, $part_id + 1);
                    
                    if ($part->encoding == 3) {
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) {
                        $body = quoted_printable_decode($body);
                    }
                    
                    break;
                }
            }
        }
        
        // Clean up body
        $body = trim($body);
        $body = wp_kses_post($body);
        
        return $body;
    }
    
    /**
     * Find company by email domain
     */
    private static function find_company_by_email($email) {
        global $wpdb;
        
        // Extract domain
        preg_match('/@(.+)$/', $email, $matches);
        $domain = $matches[1] ?? '';
        
        if (empty($domain)) {
            return null;
        }
        
        // Try to find company with matching domain in metadata
        $args = [
            'post_type'      => 'lgp_company',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => 'email_domain',
                    'value'   => $domain,
                    'compare' => '=',
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $query->posts[0];
        }
        
        // Try exact email in contact list
        $args = [
            'post_type'      => 'lgp_company',
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => 'primary_contact_email',
                    'value'   => $email,
                    'compare' => '=',
                ],
                [
                    'key'     => 'contacts_email',
                    'value'   => $email,
                    'compare' => 'LIKE',
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $query->posts[0];
        }
        
        return null;
    }
    
    /**
     * Get or create contact from email
     */
    private static function get_or_create_contact($company, $email, $header) {
        // Extract name from header
        $name = $header->from[0]->personal ?? 'Support User';
        
        // Search for existing contact
        $contacts = get_field('contacts', $company->ID) ?: [];
        
        foreach ($contacts as $contact) {
            if ($contact['email'] === $email) {
                return $company->ID; // Use company ID as user reference
            }
        }
        
        // Add new contact
        $new_contact = [
            'name'  => $name,
            'email' => $email,
            'phone' => '',
            'role'  => 'support'
        ];
        
        $contacts[] = $new_contact;
        update_field('contacts', $contacts, $company->ID);
        
        return $company->ID;
    }
    
    /**
     * Create ticket from email
     */
    private static function create_ticket($data) {
        global $wpdb;
        
        // Prepare data
        $insert_data = [
            'post_title'   => sanitize_text_field($data['subject']),
            'post_content' => wp_kses_post($data['description']),
            'post_type'    => 'lgp_ticket',
            'post_status'  => 'publish',
            'post_author'  => 0,
        ];
        
        // Insert ticket
        $ticket_id = wp_insert_post($insert_data);
        
        if (!$ticket_id) {
            return false;
        }
        
        // Add metadata
        $meta_data = [
            'priority'      => sanitize_text_field($data['priority']),
            'status'        => sanitize_text_field($data['status']),
            'company_id'    => intval($data['company_id']),
            'source'        => 'email',
            'source_email'  => sanitize_email($data['source_email']),
            'email_msg_id'  => sanitize_text_field($data['email_msg_id']),
        ];
        
        foreach ($meta_data as $key => $value) {
            update_post_meta($ticket_id, '_lgp_' . $key, $value);
        }
        
        // Log action
        do_action('lgp_ticket_created', $ticket_id, $data);
        
        error_log("LGP: Created ticket #$ticket_id from email");
        
        return $ticket_id;
    }
    
    /**
     * Process email attachments
     */
    private static function process_attachments($connection, $email_id, $ticket_id) {
        $structure = imap_fetchstructure($connection, $email_id);
        
        if (!isset($structure->parts)) {
            return;
        }
        
        foreach ($structure->parts as $part_id => $part) {
            if ($part->ifdisposition && strtolower($part->disposition) === 'attachment') {
                self::save_attachment($connection, $email_id, $part_id + 1, $ticket_id, $part);
            }
        }
    }
    
    /**
     * Save attachment from email
     */
    private static function save_attachment($connection, $email_id, $part_id, $ticket_id, $part) {
        try {
            // Get file content
            $data = imap_fetchbody($connection, $email_id, $part_id);
            
            // Handle encoding
            if ($part->encoding == 3) { // BASE64
                $data = base64_decode($data);
            } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                $data = quoted_printable_decode($data);
            }
            
            // Get filename
            $filename = 'attachment';
            if ($part->dparameters) {
                foreach ($part->dparameters as $param) {
                    if ($param->attribute === 'filename') {
                        $filename = $param->value;
                        break;
                    }
                }
            }
            
            $filename = sanitize_file_name($filename);
            
            // Check file size
            if (strlen($data) > 10 * 1024 * 1024) {
                error_log("LGP: Attachment too large: $filename");
                return;
            }
            
            // Create uploads directory
            $upload_dir = wp_upload_dir();
            $lgp_dir = $upload_dir['basedir'] . '/lgp-attachments/';
            
            if (!is_dir($lgp_dir)) {
                wp_mkdir_p($lgp_dir);
            }
            
            // Save file
            $new_filename = $ticket_id . '-' . time() . '-' . $filename;
            $filepath = $lgp_dir . $new_filename;
            
            if (file_put_contents($filepath, $data) === false) {
                error_log("LGP: Failed to save attachment: $filename");
                return;
            }
            
            // Get MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filepath);
            finfo_close($finfo);
            
            // Store in database
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'lgp_attachments',
                [
                    'ticket_id'   => $ticket_id,
                    'file_path'   => $filepath,
                    'file_name'   => $filename,
                    'file_size'   => strlen($data),
                    'mime_type'   => $mime ?: 'application/octet-stream',
                    'uploaded_by' => 0,
                ],
                ['%d', '%s', '%s', '%d', '%s', '%d']
            );
            
            error_log("LGP: Saved attachment: $filename ($new_filename)");
            
        } catch (Exception $e) {
            error_log("LGP: Attachment save error: " . $e->getMessage());
        }
    }
    
    /**
     * Send confirmation email
     */
    private static function send_confirmation_email($company, $user_id, $ticket_id) {
        // Get company email
        $primary_email = get_field('primary_contact_email', $company->ID);
        
        if (!$primary_email) {
            return;
        }
        
        $ticket = get_post($ticket_id);
        $site_url = site_url();
        
        $subject = "Ticket Created: #{$ticket_id}";
        $message = sprintf(
            "Your support request has been received.\n\n" .
            "Ticket: #%d\n" .
            "Subject: %s\n" .
            "Status: Open\n\n" .
            "You can view this ticket at:\n" .
            "%s\n\n" .
            "We will respond as soon as possible.\n\n" .
            "Best regards,\n" .
            "LounGenie Support Team",
            $ticket_id,
            $ticket->post_title,
            $site_url . '/?page_id=' . $this->get_portal_page_id() . '&ticket=' . $ticket_id
        );
        
        wp_mail($primary_email, $subject, $message);
    }
    
    /**
     * Get portal page ID
     */
    private static function get_portal_page_id() {
        $pages = get_posts([
            'post_type'   => 'page',
            'meta_query'  => [
                [
                    'key'   => '_wp_page_template',
                    'value' => 'portal',
                ]
            ]
        ]);
        
        return !empty($pages) ? $pages[0]->ID : 0;
    }
    
    /**
     * Get email settings
     */
    public static function get_settings() {
        return get_option(self::$option_key, [
            'pop3_server'   => '',
            'pop3_username' => '',
            'pop3_password' => '',
        ]);
    }
    
    /**
     * Update email settings
     */
    public static function update_settings($settings) {
        return update_option(self::$option_key, $settings);
    }
}

// Initialize
add_action('plugins_loaded', ['LGP_Email_Handler', 'init']);
