<?php
/**
 * Authentication Class
 * Handles user authentication and session management
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Auth {
    
    /**
     * Initialize authentication system
     */
    public static function init() {
        // Redirect after login to /portal if user has portal role
        add_filter( 'login_redirect', array( __CLASS__, 'redirect_after_login' ), 10, 3 );
    }
    
    /**
     * Redirect users to portal after successful login
     *
     * @param string $redirect_to URL to redirect to
     * @param string $request URL the user is coming from
     * @param WP_User|WP_Error $user User object
     * @return string Redirect URL
     */
    public static function redirect_after_login( $redirect_to, $request, $user ) {
        if ( ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
            return $redirect_to;
        }
        
        // Check if user has portal access
        $portal_roles = array( 'lgp_support', 'lgp_partner' );
        if ( array_intersect( $portal_roles, $user->roles ) ) {
            return home_url( '/portal' );
        }
        
        return $redirect_to;
    }
    
    /**
     * Check if current user has Support role
     *
     * @return bool
     */
    public static function is_support() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        return in_array( 'lgp_support', $current_user->roles );
    }
    
    /**
     * Check if current user has Partner role
     *
     * @return bool
     */
    public static function is_partner() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        return in_array( 'lgp_partner', $current_user->roles );
    }
    
    /**
     * Get current user's company ID (for partners)
     *
     * @return int|null
     */
    public static function get_user_company_id() {
        if ( ! is_user_logged_in() ) {
            return null;
        }
        
        $current_user = wp_get_current_user();
        return get_user_meta( $current_user->ID, 'lgp_company_id', true );
    }
}
