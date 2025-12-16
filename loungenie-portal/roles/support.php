<?php
/**
 * Support Role Definition
 * 
 * Support Role Capabilities:
 * - View all companies and management companies
 * - View all LounGenie units
 * - Track installs, service, maintenance, updates
 * - View all tickets
 * - View partner locations on a map
 * - Full dashboard access
 * - Can filter, search, sort all data
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Support_Role {
    
    /**
     * Register Support role
     */
    public static function register() {
        // Remove role if it exists (to update capabilities)
        remove_role( 'lgp_support' );
        
        // Add role with capabilities
        add_role(
            'lgp_support',
            __( 'LounGenie Support', 'loungenie-portal' ),
            array(
                // WordPress core capabilities (minimal)
                'read' => true,
                
                // Custom portal capabilities
                'lgp_access_portal' => true,
                'lgp_view_all_companies' => true,
                'lgp_view_all_units' => true,
                'lgp_view_all_tickets' => true,
                'lgp_manage_tickets' => true,
                'lgp_view_map' => true,
                'lgp_track_installs' => true,
                'lgp_track_service' => true,
                'lgp_track_maintenance' => true,
                'lgp_filter_data' => true,
                'lgp_search_data' => true,
                'lgp_sort_data' => true,
            )
        );
    }
    
    /**
     * Remove Support role
     */
    public static function remove() {
        remove_role( 'lgp_support' );
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
