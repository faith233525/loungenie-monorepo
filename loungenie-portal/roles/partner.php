<?php
/**
 * Partner Role Definition
 * 
 * Partner Role Capabilities:
 * - View only their company and management company
 * - View their LounGenie unit count
 * - Submit service / install / update requests
 * - Track request status and history
 * - Access a stable form that does not change
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Partner_Role {
    
    /**
     * Register Partner role
     */
    public static function register() {
        // Remove role if it exists (to update capabilities)
        remove_role( 'lgp_partner' );
        
        // Add role with capabilities
        add_role(
            'lgp_partner',
            __( 'LounGenie Partner', 'loungenie-portal' ),
            array(
                // WordPress core capabilities (minimal)
                'read' => true,
                
                // Custom portal capabilities
                'lgp_access_portal' => true,
                'lgp_view_own_company' => true,
                'lgp_view_own_units' => true,
                'lgp_submit_service_request' => true,
                'lgp_submit_install_request' => true,
                'lgp_submit_update_request' => true,
                'lgp_track_own_requests' => true,
                'lgp_view_request_history' => true,
            )
        );
    }
    
    /**
     * Remove Partner role
     */
    public static function remove() {
        remove_role( 'lgp_partner' );
    }
    
    /**
     * Check if user has specific capability
     *
     * @param string $capability
     * @return bool
     */
    public static function has_capability( $capability ) {
        return current_user_can( $capability );
    }
}
