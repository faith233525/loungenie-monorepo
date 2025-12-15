<?php
/**
 * Tickets REST API Endpoints
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Tickets_API {
    
    /**
     * Initialize API endpoints
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Get tickets
        register_rest_route( 'lgp/v1', '/tickets', array(
            'methods' => 'GET',
            'callback' => array( __CLASS__, 'get_tickets' ),
            'permission_callback' => array( __CLASS__, 'check_portal_permission' ),
        ) );
        
        // Get single ticket
        register_rest_route( 'lgp/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( __CLASS__, 'get_ticket' ),
            'permission_callback' => array( __CLASS__, 'check_ticket_permission' ),
        ) );
        
        // Create ticket/service request (Partners)
        register_rest_route( 'lgp/v1', '/tickets', array(
            'methods' => 'POST',
            'callback' => array( __CLASS__, 'create_ticket' ),
            'permission_callback' => array( __CLASS__, 'check_partner_permission' ),
        ) );
        
        // Update ticket status (Support only)
        register_rest_route( 'lgp/v1', '/tickets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array( __CLASS__, 'update_ticket' ),
            'permission_callback' => array( __CLASS__, 'check_support_permission' ),
        ) );
        
        // Add reply to ticket thread
        register_rest_route( 'lgp/v1', '/tickets/(?P<id>\d+)/reply', array(
            'methods' => 'POST',
            'callback' => array( __CLASS__, 'add_reply' ),
            'permission_callback' => array( __CLASS__, 'check_portal_permission' ),
        ) );
    }
    
    /**
     * Get tickets
     */
    public static function get_tickets( $request ) {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'lgp_tickets';
        $requests_table = $wpdb->prefix . 'lgp_service_requests';
        $page = $request->get_param( 'page' ) ?: 1;
        $per_page = $request->get_param( 'per_page' ) ?: 20;
        $offset = ( $page - 1 ) * $per_page;
        
        if ( LGP_Auth::is_support() ) {
            // Support can see all tickets
            $tickets = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, sr.request_type, sr.priority, sr.company_id 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                ORDER BY t.created_at DESC 
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ) );
            $total = $wpdb->get_var( "SELECT COUNT(*) FROM $tickets_table" );
        } else {
            // Partners see only their tickets
            $company_id = LGP_Auth::get_user_company_id();
            $tickets = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, sr.request_type, sr.priority, sr.company_id 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE sr.company_id = %d 
                ORDER BY t.created_at DESC 
                LIMIT %d OFFSET %d",
                $company_id,
                $per_page,
                $offset
            ) );
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE sr.company_id = %d",
                $company_id
            ) );
        }
        
        return rest_ensure_response( array(
            'tickets' => $tickets,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page,
        ) );
    }
    
    /**
     * Get single ticket
     */
    public static function get_ticket( $request ) {
        global $wpdb;
        
        $id = $request->get_param( 'id' );
        $tickets_table = $wpdb->prefix . 'lgp_tickets';
        $requests_table = $wpdb->prefix . 'lgp_service_requests';
        
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.*, sr.request_type, sr.priority, sr.company_id, sr.notes 
            FROM $tickets_table t 
            LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
            WHERE t.id = %d",
            $id
        ) );
        
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
        
        $company_id = LGP_Auth::get_user_company_id();
        
        // Create service request first
        $requests_table = $wpdb->prefix . 'lgp_service_requests';
        $request_data = array(
            'company_id' => $company_id,
            'unit_id' => absint( $request->get_param( 'unit_id' ) ),
            'request_type' => sanitize_text_field( $request->get_param( 'request_type' ) ),
            'priority' => sanitize_text_field( $request->get_param( 'priority' ) ?: 'normal' ),
            'status' => 'pending',
            'notes' => sanitize_textarea_field( $request->get_param( 'notes' ) ),
        );
        
        $inserted = $wpdb->insert( $requests_table, $request_data );
        
        if ( $inserted === false ) {
            return new WP_Error( 'db_error', __( 'Failed to create service request', 'loungenie-portal' ), array( 'status' => 500 ) );
        }
        
        $service_request_id = $wpdb->insert_id;
        
        // Create ticket
        $tickets_table = $wpdb->prefix . 'lgp_tickets';
        $ticket_data = array(
            'service_request_id' => $service_request_id,
            'status' => 'open',
            'thread_history' => wp_json_encode( array(
                array(
                    'timestamp' => current_time( 'mysql' ),
                    'user' => wp_get_current_user()->display_name,
                    'message' => $request_data['notes'],
                )
            ) ),
        );
        
        $wpdb->insert( $tickets_table, $ticket_data );
        
        return rest_ensure_response( array(
            'ticket_id' => $wpdb->insert_id,
            'service_request_id' => $service_request_id,
            'message' => __( 'Service request submitted successfully', 'loungenie-portal' ),
        ) );
    }
    
    /**
     * Update ticket
     */
    public static function update_ticket( $request ) {
        global $wpdb;
        
        $id = $request->get_param( 'id' );
        $table = $wpdb->prefix . 'lgp_tickets';
        
        $data = array(
            'status' => sanitize_text_field( $request->get_param( 'status' ) ),
        );
        
        $updated = $wpdb->update( $table, $data, array( 'id' => $id ) );
        
        if ( $updated === false ) {
            return new WP_Error( 'db_error', __( 'Failed to update ticket', 'loungenie-portal' ), array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'message' => __( 'Ticket updated successfully', 'loungenie-portal' ),
        ) );
    }
    
    /**
     * Add reply to ticket
     */
    public static function add_reply( $request ) {
        global $wpdb;
        
        $id = $request->get_param( 'id' );
        $table = $wpdb->prefix . 'lgp_tickets';
        
        // Get current thread history
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ) );
        
        if ( ! $ticket ) {
            return new WP_Error( 'not_found', __( 'Ticket not found', 'loungenie-portal' ), array( 'status' => 404 ) );
        }
        
        $thread = json_decode( $ticket->thread_history, true ) ?: array();
        
        // Add new reply
        $thread[] = array(
            'timestamp' => current_time( 'mysql' ),
            'user' => wp_get_current_user()->display_name,
            'message' => sanitize_textarea_field( $request->get_param( 'message' ) ),
        );
        
        // Update ticket
        $wpdb->update(
            $table,
            array( 'thread_history' => wp_json_encode( $thread ) ),
            array( 'id' => $id )
        );
        
        return rest_ensure_response( array(
            'message' => __( 'Reply added successfully', 'loungenie-portal' ),
        ) );
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
            $ticket_id = $request->get_param( 'id' );
            $company_id = LGP_Auth::get_user_company_id();
            
            $tickets_table = $wpdb->prefix . 'lgp_tickets';
            $requests_table = $wpdb->prefix . 'lgp_service_requests';
            
            $ticket = $wpdb->get_row( $wpdb->prepare(
                "SELECT t.* 
                FROM $tickets_table t 
                LEFT JOIN $requests_table sr ON t.service_request_id = sr.id 
                WHERE t.id = %d AND sr.company_id = %d",
                $ticket_id,
                $company_id
            ) );
            
            return ! is_null( $ticket );
        }
        
        return false;
    }
}
