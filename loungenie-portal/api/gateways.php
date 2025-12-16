<?php
/**
 * Gateways REST API
 * Support-only gateway management endpoints
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Gateways_API {
    
    /**
     * Initialize REST API routes
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }
    
    /**
     * Register gateway REST routes
     */
    public static function register_routes() {
        register_rest_route( 'lgp/v1', '/gateways', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_gateways' ),
                'permission_callback' => array( __CLASS__, 'support_only_permission' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'create_gateway' ),
                'permission_callback' => array( __CLASS__, 'support_only_permission' ),
            ),
        ) );
        
        register_rest_route( 'lgp/v1', '/gateways/(?P<id>\d+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_gateway' ),
                'permission_callback' => array( __CLASS__, 'support_only_permission' ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array( __CLASS__, 'update_gateway' ),
                'permission_callback' => array( __CLASS__, 'support_only_permission' ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array( __CLASS__, 'delete_gateway' ),
                'permission_callback' => array( __CLASS__, 'support_only_permission' ),
            ),
        ) );
        
        register_rest_route( 'lgp/v1', '/gateways/(?P<id>\d+)/test-signal', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'test_signal' ),
            'permission_callback' => array( __CLASS__, 'support_only_permission' ),
        ) );
        
        register_rest_route( 'lgp/v1', '/gateways/(?P<id>\d+)/units', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_gateway_units' ),
            'permission_callback' => array( __CLASS__, 'support_only_permission' ),
        ) );
    }
    
    /**
     * Support-only permission check
     */
    public static function support_only_permission() {
        return LGP_Auth::is_support();
    }
    
    /**
     * GET /lgp/v1/gateways
     */
    public static function get_gateways( $request ) {
        $filters = array(
            'company_id' => $request->get_param( 'company_id' ),
            'call_button' => $request->get_param( 'call_button' ),
            'search' => $request->get_param( 'search' ),
        );
        
        $gateways = LGP_Gateway::get_all( $filters );
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => $gateways,
        ) );
    }
    
    /**
     * GET /lgp/v1/gateways/:id
     */
    public static function get_gateway( $request ) {
        $id = (int) $request['id'];
        $gateway = LGP_Gateway::get( $id );
        
        if ( ! $gateway ) {
            return new WP_Error( 'not_found', 'Gateway not found', array( 'status' => 404 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => $gateway,
        ) );
    }
    
    /**
     * POST /lgp/v1/gateways
     */
    public static function create_gateway( $request ) {
        $data = array(
            'company_id'         => absint( $request->get_param( 'company_id' ) ),
            'channel_number'     => sanitize_text_field( $request->get_param( 'channel_number' ) ),
            'gateway_address'    => sanitize_text_field( $request->get_param( 'gateway_address' ) ),
            'unit_capacity'      => absint( $request->get_param( 'unit_capacity' ) ),
            'call_button'        => absint( $request->get_param( 'call_button' ) ),
            'included_equipment' => sanitize_textarea_field( $request->get_param( 'included_equipment' ) ),
            'admin_password'     => sanitize_text_field( $request->get_param( 'admin_password' ) ),
        );
        
        $gateway_id = LGP_Gateway::create( $data );
        
        if ( ! $gateway_id ) {
            return new WP_Error( 'create_failed', 'Failed to create gateway', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => array( 'id' => $gateway_id ),
            'message' => 'Gateway created successfully',
        ) );
    }
    
    /**
     * PUT /lgp/v1/gateways/:id
     */
    public static function update_gateway( $request ) {
        $id = (int) $request['id'];
        
        $data = array(
            'company_id'         => absint( $request->get_param( 'company_id' ) ),
            'channel_number'     => sanitize_text_field( $request->get_param( 'channel_number' ) ),
            'gateway_address'    => sanitize_text_field( $request->get_param( 'gateway_address' ) ),
            'unit_capacity'      => absint( $request->get_param( 'unit_capacity' ) ),
            'call_button'        => absint( $request->get_param( 'call_button' ) ),
            'included_equipment' => sanitize_textarea_field( $request->get_param( 'included_equipment' ) ),
            'admin_password'     => sanitize_text_field( $request->get_param( 'admin_password' ) ),
        );
        
        $result = LGP_Gateway::update( $id, $data );
        
        if ( ! $result ) {
            return new WP_Error( 'update_failed', 'Failed to update gateway', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Gateway updated successfully',
        ) );
    }
    
    /**
     * DELETE /lgp/v1/gateways/:id
     */
    public static function delete_gateway( $request ) {
        $id = (int) $request['id'];
        
        $result = LGP_Gateway::delete( $id );
        
        if ( ! $result ) {
            return new WP_Error( 'delete_failed', 'Failed to delete gateway', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Gateway deleted successfully',
        ) );
    }
    
    /**
     * POST /lgp/v1/gateways/:id/test-signal
     */
    public static function test_signal( $request ) {
        $id = (int) $request['id'];
        
        $result = LGP_Gateway::test_signal( $id );
        
        return rest_ensure_response( $result );
    }
    
    /**
     * GET /lgp/v1/gateways/:id/units
     */
    public static function get_gateway_units( $request ) {
        $id = (int) $request['id'];
        
        $units = LGP_Gateway::get_connected_units( $id );
        
        return rest_ensure_response( array(
            'success' => true,
            'data' => $units,
        ) );
    }
}
