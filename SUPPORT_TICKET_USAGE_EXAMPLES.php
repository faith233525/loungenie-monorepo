<?php
/**
 * Support Ticket Form - Usage Examples
 * 
 * This file demonstrates how to use and integrate the support ticket form
 * in various scenarios within the LounGenie Portal.
 * 
 * @package LounGenie Portal
 */

// ============================================================================
// EXAMPLE 1: Display Form on Support Page
// ============================================================================

// In page-support.php or support.php template
function render_support_page() {
    ?>
    <div class="support-page-container">
        <h1><?php _e( 'Support Center', 'loungenie-portal' ); ?></h1>
        <p><?php _e( 'We\'re here to help. Submit a ticket to get in touch with our support team.', 'loungenie-portal' ); ?></p>
        
        <?php
        // Load the form
        include plugin_dir_path( __FILE__ ) . 'templates/support-ticket-form.php';
        ?>
    </div>
    <?php
}

// ============================================================================
// EXAMPLE 2: Register Assets in Main Plugin File
// ============================================================================

function register_support_ticket_assets() {
    // Register form JavaScript
    wp_register_script(
        'lgp-support-ticket-form',
        plugins_url( 'assets/js/support-ticket-form.js', __FILE__ ),
        array( 'jquery' ), // dependency
        '1.0.0',
        true // in footer
    );

    // Register form CSS
    wp_register_style(
        'lgp-support-ticket-form',
        plugins_url( 'assets/css/support-ticket-form.css', __FILE__ ),
        array(),
        '1.0.0',
        'all'
    );

    // Enqueue on support page
    if ( is_page( 'support' ) || is_page( 'contact-us' ) ) {
        wp_enqueue_script( 'lgp-support-ticket-form' );
        wp_enqueue_style( 'lgp-support-ticket-form' );
    }
}
add_action( 'wp_enqueue_scripts', 'register_support_ticket_assets' );

// ============================================================================
// EXAMPLE 3: Create Shortcode for Easy Embedding
// ============================================================================

function lgp_support_ticket_shortcode( $atts ) {
    ob_start();
    include plugin_dir_path( __FILE__ ) . 'templates/support-ticket-form.php';
    return ob_get_clean();
}
add_shortcode( 'lgp_support_ticket', 'lgp_support_ticket_shortcode' );

// Usage in page editor:
// [lgp_support_ticket]

// ============================================================================
// EXAMPLE 4: Initialize Handler in Plugin Bootstrap
// ============================================================================

// In main plugin file (wp-poolsafe-portal.php)
function loungenie_portal_init() {
    // Load support ticket handler
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-support-ticket-handler.php';
    
    // Initialize AJAX handlers
    LGP_Support_Ticket_Handler::init();
}
add_action( 'plugins_loaded', 'loungenie_portal_init' );

// ============================================================================
// EXAMPLE 5: Create Activation Hook for Upload Directory
// ============================================================================

function loungenie_portal_activation() {
    // Create upload directory for ticket files
    $upload_dir = wp_upload_dir();
    $ticket_dir = $upload_dir['basedir'] . '/lgp-tickets';
    
    if ( ! is_dir( $ticket_dir ) ) {
        wp_mkdir_p( $ticket_dir );
        
        // Add .htaccess for security
        $htaccess_content = 'deny from all';
        file_put_contents( $ticket_dir . '/.htaccess', $htaccess_content );
    }
}
register_activation_hook( __FILE__, 'loungenie_portal_activation' );

// ============================================================================
// EXAMPLE 6: Retrieve Submitted Tickets
// ============================================================================

function get_company_tickets( $company_id, $args = array() ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lgp_tickets';
    
    // Check if table exists
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
        // Fallback: retrieve as posts
        return get_company_tickets_as_posts( $company_id, $args );
    }
    
    // Build query
    $defaults = array(
        'per_page' => 10,
        'page'     => 1,
        'status'   => '', // empty for all
        'order'    => 'DESC',
        'orderby'  => 'created_at',
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    $query = "SELECT * FROM $table_name WHERE company_id = %d";
    $params = array( $company_id );
    
    // Filter by status if provided
    if ( ! empty( $args['status'] ) ) {
        $query .= " AND status = %s";
        $params[] = $args['status'];
    }
    
    // Order
    $query .= " ORDER BY {$args['orderby']} {$args['order']}";
    
    // Pagination
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    $query .= " LIMIT %d, %d";
    $params[] = $offset;
    $params[] = $args['per_page'];
    
    // Execute query
    $query = $wpdb->prepare( $query, $params );
    return $wpdb->get_results( $query );
}

// Usage:
// $tickets = get_company_tickets( 1, array(
//     'status'   => 'open',
//     'per_page' => 20,
//     'page'     => 1,
// ) );

// ============================================================================
// EXAMPLE 7: Get Ticket Details
// ============================================================================

function get_ticket_details( $ticket_id ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lgp_tickets';
    
    $ticket = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $ticket_id )
    );
    
    if ( ! $ticket ) {
        return null;
    }
    
    // Get attached files
    $files = get_post_meta( $ticket_id, '_attached_files', true );
    $ticket->files = is_array( $files ) ? $files : array();
    
    // Get affected units
    $units = get_post_meta( $ticket_id, '_affected_unit_ids', true );
    $ticket->unit_ids = is_array( $units ) ? $units : array();
    
    return $ticket;
}

// Usage:
// $ticket = get_ticket_details( 123 );
// echo $ticket->subject;

// ============================================================================
// EXAMPLE 8: Customize Validation Rules
// ============================================================================

/**
 * Filter ticket validation
 * Add custom validation before submission
 */
function custom_validate_ticket( $validation_result, $submitted_data ) {
    // Add custom validation logic
    if ( ! empty( $submitted_data['subject'] ) ) {
        // Check for inappropriate content
        if ( stripos( $submitted_data['subject'], 'spam' ) !== false ) {
            return array(
                'valid'   => false,
                'message' => __( 'Your subject appears to contain spam. Please revise.', 'loungenie-portal' )
            );
        }
    }
    
    return $validation_result;
}
// add_filter( 'lgp_ticket_validation', 'custom_validate_ticket', 10, 2 );

// ============================================================================
// EXAMPLE 9: Custom Email Notifications
// ============================================================================

/**
 * Filter ticket notification email
 * Customize email content and recipients
 */
function custom_ticket_notification_email( $to, $subject, $message, $ticket_data ) {
    // Add support manager CC
    $support_manager = get_option( 'lgp_support_manager_email' );
    
    if ( $support_manager && is_email( $support_manager ) ) {
        // WordPress doesn't support CC directly, so we'd need to send additional email
        wp_mail(
            $support_manager,
            '[CC] ' . $subject,
            $message
        );
    }
    
    return array( $to, $subject, $message );
}

// ============================================================================
// EXAMPLE 10: Add Ticket to User Dashboard
// ============================================================================

function display_user_ticket_history() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $company_id = LGP_Auth::get_user_company_id();
    
    $tickets = get_company_tickets( $company_id, array(
        'per_page' => 10,
        'page'     => 1,
    ) );
    
    if ( empty( $tickets ) ) {
        echo '<p>' . __( 'No support tickets found.', 'loungenie-portal' ) . '</p>';
        return;
    }
    
    ?>
    <div class="user-ticket-history">
        <h3><?php _e( 'Your Support Tickets', 'loungenie-portal' ); ?></h3>
        
        <table class="tickets-table">
            <thead>
                <tr>
                    <th><?php _e( 'Reference', 'loungenie-portal' ); ?></th>
                    <th><?php _e( 'Subject', 'loungenie-portal' ); ?></th>
                    <th><?php _e( 'Category', 'loungenie-portal' ); ?></th>
                    <th><?php _e( 'Status', 'loungenie-portal' ); ?></th>
                    <th><?php _e( 'Created', 'loungenie-portal' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $tickets as $ticket ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( 'ticket_id', $ticket->id ) ); ?>">
                                <?php echo esc_html( $ticket->ticket_reference ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( $ticket->subject ); ?></td>
                        <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $ticket->category ) ) ); ?></td>
                        <td>
                            <span class="status status-<?php echo esc_attr( $ticket->status ); ?>">
                                <?php echo esc_html( ucfirst( $ticket->status ) ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================================
// EXAMPLE 11: Admin Notification with Ticket Details
// ============================================================================

function send_detailed_admin_notification( $ticket_data, $ticket_id ) {
    $admin_email = get_option( 'admin_email' );
    
    // Build detailed email
    $subject = sprintf(
        __( '[Support Ticket] %s - %s', 'loungenie-portal' ),
        $ticket_data['ticket_reference'],
        $ticket_data['subject']
    );
    
    $message = sprintf(
        '<h2>%s</h2>
        <p><strong>%s:</strong> %s</p>
        <p><strong>%s:</strong> %s</p>
        <p><strong>%s:</strong> %s</p>
        <p><strong>%s:</strong> %s</p>
        <h3>%s</h3>
        <p>%s</p>
        <p><a href="%s">%s</a></p>',
        __( 'New Support Ticket', 'loungenie-portal' ),
        __( 'Ticket Reference', 'loungenie-portal' ),
        $ticket_data['ticket_reference'],
        __( 'From', 'loungenie-portal' ),
        $ticket_data['first_name'] . ' ' . $ticket_data['last_name'],
        __( 'Category', 'loungenie-portal' ),
        ucfirst( str_replace( '_', ' ', $ticket_data['category'] ) ),
        __( 'Urgency', 'loungenie-portal' ),
        ucfirst( $ticket_data['urgency'] ),
        __( 'Issue Description', 'loungenie-portal' ),
        nl2br( esc_html( $ticket_data['description'] ) ),
        admin_url( 'admin.php?page=lgp-tickets&ticket_id=' . $ticket_id ),
        __( 'View Ticket Details', 'loungenie-portal' )
    );
    
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    
    wp_mail( $admin_email, $subject, $message, $headers );
}

// ============================================================================
// EXAMPLE 12: Add Ticket Status Updates
// ============================================================================

function update_ticket_status( $ticket_id, $new_status ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lgp_tickets';
    
    $updated = $wpdb->update(
        $table_name,
        array(
            'status'     => $new_status,
            'updated_at' => current_time( 'mysql' ),
        ),
        array( 'id' => $ticket_id ),
        array( '%s', '%s' ),
        array( '%d' )
    );
    
    if ( $updated ) {
        // Get ticket for notification
        $ticket = get_ticket_details( $ticket_id );
        
        // Send status update email to requester
        wp_mail(
            $ticket->requester_email,
            sprintf(
                __( 'Ticket %s Status Update', 'loungenie-portal' ),
                $ticket->ticket_reference
            ),
            sprintf(
                __( 'Your ticket has been updated to: %s', 'loungenie-portal' ),
                ucfirst( $new_status )
            )
        );
    }
    
    return $updated;
}

// ============================================================================
// EXAMPLE 13: Export Tickets to CSV
// ============================================================================

function export_tickets_to_csv( $company_id ) {
    $tickets = get_company_tickets( $company_id, array( 'per_page' => 9999 ) );
    
    if ( empty( $tickets ) ) {
        return;
    }
    
    // Set headers for CSV download
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="tickets.csv"' );
    
    $output = fopen( 'php://output', 'w' );
    
    // CSV header
    fputcsv( $output, array(
        'ID',
        'Ticket Reference',
        'Requester',
        'Email',
        'Category',
        'Urgency',
        'Subject',
        'Status',
        'Created',
    ) );
    
    // CSV rows
    foreach ( $tickets as $ticket ) {
        fputcsv( $output, array(
            $ticket->id,
            $ticket->ticket_reference,
            $ticket->requester_name,
            $ticket->requester_email,
            $ticket->category,
            $ticket->urgency,
            $ticket->subject,
            $ticket->status,
            $ticket->created_at,
        ) );
    }
    
    fclose( $output );
    exit;
}

// ============================================================================
// EXAMPLE 14: Add Ticket Search Functionality
// ============================================================================

function search_tickets( $search_term, $company_id = null ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lgp_tickets';
    
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $params = array();
    
    // Search in subject and description
    $query .= " AND (subject LIKE %s OR description LIKE %s OR ticket_reference LIKE %s)";
    $search = '%' . $wpdb->esc_like( $search_term ) . '%';
    $params = array( $search, $search, $search );
    
    // Filter by company if specified
    if ( $company_id ) {
        $query .= " AND company_id = %d";
        $params[] = $company_id;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    return $wpdb->get_results(
        $wpdb->prepare( $query, $params )
    );
}

// Usage:
// $results = search_tickets( 'filter problem', 1 );

// ============================================================================
// EXAMPLE 15: Validate Required Custom Classes
// ============================================================================

function verify_dependencies() {
    $required_classes = array(
        'LGP_Auth',
        'LGP_Database',
    );
    
    foreach ( $required_classes as $class_name ) {
        if ( ! class_exists( $class_name ) ) {
            wp_die(
                sprintf(
                    __( 'Required class %s not found. Please ensure all plugin dependencies are properly loaded.', 'loungenie-portal' ),
                    $class_name
                )
            );
        }
    }
}

// ============================================================================
// END OF EXAMPLES
// ============================================================================

/**
 * This file demonstrates common usage patterns for the support ticket form.
 * Adapt these examples to your specific needs and requirements.
 */
