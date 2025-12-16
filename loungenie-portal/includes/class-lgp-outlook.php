<?php
/**
 * Outlook / Microsoft Graph Integration Class
 * Handles email integration for ticket replies and notifications
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Outlook {
    
    /**
     * Microsoft Graph API base URL
     */
    const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0';
    
    /**
     * Microsoft OAuth endpoints
     */
    const OAUTH_AUTHORIZE_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    const OAUTH_TOKEN_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    
    /**
     * Initialize Outlook integration
     */
    public static function init() {
        // Hook into ticket replies
        add_action( 'lgp_ticket_reply_added', array( __CLASS__, 'send_notification_email' ), 10, 3 );
        
        // Add settings page
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        
        // Handle OAuth callback
        add_action( 'admin_init', array( __CLASS__, 'handle_oauth_callback' ) );
        
        // Add reply button to portal
        add_action( 'wp_ajax_lgp_send_outlook_reply', array( __CLASS__, 'ajax_send_reply' ) );
    }
    
    /**
     * Check if Outlook integration is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        $client_id = get_option( 'lgp_outlook_client_id' );
        $client_secret = get_option( 'lgp_outlook_client_secret' );
        
        return ! empty( $client_id ) && ! empty( $client_secret );
    }
    
    /**
     * Get access token (refresh if needed)
     *
     * @return string|false
     */
    private static function get_access_token() {
        $access_token = get_option( 'lgp_outlook_access_token' );
        $expires_at = get_option( 'lgp_outlook_token_expires' );
        
        // Check if token is expired
        if ( $access_token && $expires_at && time() < $expires_at ) {
            return $access_token;
        }
        
        // Try to refresh token
        $refresh_token = get_option( 'lgp_outlook_refresh_token' );
        if ( $refresh_token ) {
            return self::refresh_access_token( $refresh_token );
        }
        
        return false;
    }
    
    /**
     * Refresh access token
     *
     * @param string $refresh_token Refresh token
     * @return string|false
     */
    private static function refresh_access_token( $refresh_token ) {
        $client_id = get_option( 'lgp_outlook_client_id' );
        $client_secret = get_option( 'lgp_outlook_client_secret' );
        
        $response = wp_remote_post( self::OAUTH_TOKEN_URL, array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
                'scope' => 'Mail.Send Mail.ReadWrite offline_access',
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            self::log_error( 'Token refresh failed: ' . $response->get_error_message() );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['access_token'] ) ) {
            update_option( 'lgp_outlook_access_token', $body['access_token'] );
            update_option( 'lgp_outlook_token_expires', time() + $body['expires_in'] );
            
            if ( isset( $body['refresh_token'] ) ) {
                update_option( 'lgp_outlook_refresh_token', $body['refresh_token'] );
            }
            
            return $body['access_token'];
        }
        
        return false;
    }
    
    /**
     * Send email via Microsoft Graph API
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $cc CC recipients (optional)
     * @return bool|WP_Error
     */
    public static function send_email( $to, $subject, $body, $cc = array() ) {
        if ( ! self::is_enabled() ) {
            return new WP_Error( 'outlook_disabled', __( 'Outlook integration is not enabled', 'loungenie-portal' ) );
        }
        
        $access_token = self::get_access_token();
        
        if ( ! $access_token ) {
            return new WP_Error( 'no_access_token', __( 'Not authenticated with Microsoft', 'loungenie-portal' ) );
        }
        
        $message = array(
            'message' => array(
                'subject' => $subject,
                'body' => array(
                    'contentType' => 'HTML',
                    'content' => $body,
                ),
                'toRecipients' => array(
                    array(
                        'emailAddress' => array(
                            'address' => $to,
                        ),
                    ),
                ),
            ),
            'saveToSentItems' => 'true',
        );
        
        // Add CC recipients if provided
        if ( ! empty( $cc ) ) {
            $message['message']['ccRecipients'] = array();
            foreach ( $cc as $cc_email ) {
                $message['message']['ccRecipients'][] = array(
                    'emailAddress' => array(
                        'address' => $cc_email,
                    ),
                );
            }
        }
        
        $response = wp_remote_post( self::GRAPH_API_URL . '/me/sendMail', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $message ),
            'timeout' => 30,
        ) );
        
        if ( is_wp_error( $response ) ) {
            self::log_error( 'Email send failed: ' . $response->get_error_message() );
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        
        if ( $status_code !== 202 ) {
            $body = wp_remote_retrieve_body( $response );
            $error = json_decode( $body, true );
            $error_message = isset( $error['error']['message'] ) ? $error['error']['message'] : 'Unknown error';
            
            self::log_error( sprintf( 'Graph API Error (%d): %s', $status_code, $error_message ) );
            return new WP_Error( 'graph_api_error', $error_message );
        }
        
        return true;
    }
    
    /**
     * Send notification email when ticket is updated
     *
     * @param int $ticket_id Ticket ID
     * @param string $message Reply message
     * @param array $ticket_data Ticket data
     */
    public static function send_notification_email( $ticket_id, $message, $ticket_data = array() ) {
        global $wpdb;
        
        if ( empty( $ticket_data ) ) {
            $tickets_table = $wpdb->prefix . 'lgp_tickets';
            $requests_table = $wpdb->prefix . 'lgp_service_requests';
            $companies_table = $wpdb->prefix . 'lgp_companies';
            
            $ticket_data = $wpdb->get_row( $wpdb->prepare(
                "SELECT t.*, sr.request_type, c.name as company_name, c.contact_email 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                LEFT JOIN $companies_table c ON sr.company_id = c.id 
                WHERE t.id = %d",
                $ticket_id
            ) );
        }
        
        if ( ! $ticket_data || empty( $ticket_data->contact_email ) ) {
            return;
        }
        
        $subject = sprintf(
            __( 'LounGenie Ticket Update - #%d', 'loungenie-portal' ),
            $ticket_id
        );
        
        $body = sprintf(
            '<html><body>
            <h2>Ticket Update</h2>
            <p><strong>Ticket ID:</strong> #%d</p>
            <p><strong>Company:</strong> %s</p>
            <p><strong>Request Type:</strong> %s</p>
            <hr>
            <h3>Latest Update:</h3>
            <p>%s</p>
            <hr>
            <p>View full ticket details in the <a href="%s">LounGenie Portal</a></p>
            </body></html>',
            $ticket_id,
            esc_html( $ticket_data->company_name ),
            esc_html( ucfirst( $ticket_data->request_type ) ),
            nl2br( esc_html( $message ) ),
            home_url( '/portal' )
        );
        
        self::send_email( $ticket_data->contact_email, $subject, $body );
    }
    
    /**
     * Handle AJAX request to send Outlook reply
     */
    public static function ajax_send_reply() {
        check_ajax_referer( 'lgp_portal_nonce', 'nonce' );
        
        if ( ! LGP_Auth::is_support() ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'loungenie-portal' ) ) );
        }
        
        $ticket_id = absint( $_POST['ticket_id'] ?? 0 );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        
        if ( ! $ticket_id || ! $message ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data', 'loungenie-portal' ) ) );
        }
        
        // Get ticket data
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'lgp_tickets';
        $requests_table = $wpdb->prefix . 'lgp_service_requests';
        $companies_table = $wpdb->prefix . 'lgp_companies';
        
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.*, sr.request_type, c.name as company_name, c.contact_email 
            FROM $tickets_table t 
            LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
            LEFT JOIN $companies_table c ON sr.company_id = c.id 
            WHERE t.id = %d",
            $ticket_id
        ) );
        
        if ( ! $ticket ) {
            wp_send_json_error( array( 'message' => __( 'Ticket not found', 'loungenie-portal' ) ) );
        }
        
        // Send email
        $result = self::send_notification_email( $ticket_id, $message, $ticket );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        
        // Add reply to ticket thread
        $thread = json_decode( $ticket->thread_history, true ) ?: array();
        $thread[] = array(
            'timestamp' => current_time( 'mysql' ),
            'user' => wp_get_current_user()->display_name,
            'message' => $message,
            'via' => 'outlook',
        );
        
        $wpdb->update(
            $tickets_table,
            array( 'thread_history' => wp_json_encode( $thread ) ),
            array( 'id' => $ticket_id )
        );
        
        wp_send_json_success( array( 'message' => __( 'Reply sent successfully', 'loungenie-portal' ) ) );
    }
    
    /**
     * Get OAuth authorization URL
     *
     * @return string
     */
    public static function get_auth_url() {
        $client_id = get_option( 'lgp_outlook_client_id' );
        $redirect_uri = admin_url( 'options-general.php?page=lgp-outlook-settings&oauth_callback=1' );
        
        $params = array(
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => $redirect_uri,
            'scope' => 'Mail.Send Mail.ReadWrite offline_access',
            'response_mode' => 'query',
        );
        
        return self::OAUTH_AUTHORIZE_URL . '?' . http_build_query( $params );
    }
    
    /**
     * Handle OAuth callback
     */
    public static function handle_oauth_callback() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'lgp-outlook-settings' ) {
            return;
        }
        
        if ( ! isset( $_GET['oauth_callback'] ) ) {
            return;
        }
        
        if ( isset( $_GET['code'] ) ) {
            $code = sanitize_text_field( $_GET['code'] );
            self::exchange_code_for_token( $code );
            
            // Redirect to clean URL
            wp_redirect( admin_url( 'options-general.php?page=lgp-outlook-settings&auth=success' ) );
            exit;
        }
    }
    
    /**
     * Exchange authorization code for access token
     *
     * @param string $code Authorization code
     * @return bool
     */
    private static function exchange_code_for_token( $code ) {
        $client_id = get_option( 'lgp_outlook_client_id' );
        $client_secret = get_option( 'lgp_outlook_client_secret' );
        $redirect_uri = admin_url( 'options-general.php?page=lgp-outlook-settings&oauth_callback=1' );
        
        $response = wp_remote_post( self::OAUTH_TOKEN_URL, array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
                'scope' => 'Mail.Send Mail.ReadWrite offline_access',
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            self::log_error( 'Token exchange failed: ' . $response->get_error_message() );
            return false;
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['access_token'] ) ) {
            update_option( 'lgp_outlook_access_token', $body['access_token'] );
            update_option( 'lgp_outlook_token_expires', time() + $body['expires_in'] );
            update_option( 'lgp_outlook_refresh_token', $body['refresh_token'] );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Log error
     *
     * @param string $message Error message
     */
    private static function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LounGenie Outlook] ' . $message );
        }
        
        $errors = get_option( 'lgp_outlook_errors', array() );
        $errors[] = array(
            'message' => $message,
            'timestamp' => current_time( 'mysql' ),
        );
        
        if ( count( $errors ) > 50 ) {
            $errors = array_slice( $errors, -50 );
        }
        
        update_option( 'lgp_outlook_errors', $errors );
    }
    
    /**
     * Add settings page
     */
    public static function add_settings_page() {
        add_options_page(
            __( 'LounGenie Outlook Settings', 'loungenie-portal' ),
            __( 'Outlook Integration', 'loungenie-portal' ),
            'manage_options',
            'lgp-outlook-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting( 'lgp_outlook_settings', 'lgp_outlook_client_id' );
        register_setting( 'lgp_outlook_settings', 'lgp_outlook_client_secret' );
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $is_authenticated = ! empty( get_option( 'lgp_outlook_access_token' ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Outlook Integration Settings', 'loungenie-portal' ); ?></h1>
            
            <?php if ( isset( $_GET['auth'] ) && $_GET['auth'] === 'success' ) : ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e( 'Successfully authenticated with Microsoft!', 'loungenie-portal' ); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'lgp_outlook_settings' );
                do_settings_sections( 'lgp_outlook_settings' );
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lgp_outlook_client_id"><?php esc_html_e( 'Azure AD Client ID', 'loungenie-portal' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="lgp_outlook_client_id" 
                                   name="lgp_outlook_client_id" 
                                   value="<?php echo esc_attr( get_option( 'lgp_outlook_client_id' ) ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Get this from Azure Portal → App Registrations', 'loungenie-portal' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lgp_outlook_client_secret"><?php esc_html_e( 'Azure AD Client Secret', 'loungenie-portal' ); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="lgp_outlook_client_secret" 
                                   name="lgp_outlook_client_secret" 
                                   value="<?php echo esc_attr( get_option( 'lgp_outlook_client_secret' ) ); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Create a client secret in Azure Portal', 'loungenie-portal' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e( 'Authentication', 'loungenie-portal' ); ?></h2>
            <p>
                <strong><?php esc_html_e( 'Status:', 'loungenie-portal' ); ?></strong>
                <?php if ( $is_authenticated ) : ?>
                    <span style="color: green;">✓ <?php esc_html_e( 'Authenticated', 'loungenie-portal' ); ?></span>
                <?php else : ?>
                    <span style="color: red;">✗ <?php esc_html_e( 'Not authenticated', 'loungenie-portal' ); ?></span>
                <?php endif; ?>
            </p>
            
            <?php if ( self::is_enabled() && ! $is_authenticated ) : ?>
                <p>
                    <a href="<?php echo esc_url( self::get_auth_url() ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Authenticate with Microsoft', 'loungenie-portal' ); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <h3><?php esc_html_e( 'Setup Instructions', 'loungenie-portal' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Go to Azure Portal → App Registrations', 'loungenie-portal' ); ?></li>
                <li><?php esc_html_e( 'Create a new app registration', 'loungenie-portal' ); ?></li>
                <li><?php esc_html_e( 'Set redirect URI to:', 'loungenie-portal' ); ?> 
                    <code><?php echo esc_html( admin_url( 'options-general.php?page=lgp-outlook-settings&oauth_callback=1' ) ); ?></code>
                </li>
                <li><?php esc_html_e( 'Add API permissions: Mail.Send, Mail.ReadWrite', 'loungenie-portal' ); ?></li>
                <li><?php esc_html_e( 'Create a client secret', 'loungenie-portal' ); ?></li>
                <li><?php esc_html_e( 'Enter Client ID and Client Secret above and save', 'loungenie-portal' ); ?></li>
                <li><?php esc_html_e( 'Click "Authenticate with Microsoft" to authorize', 'loungenie-portal' ); ?></li>
            </ol>
        </div>
        <?php
    }
}
