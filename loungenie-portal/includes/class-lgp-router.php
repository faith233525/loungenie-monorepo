<?php
/**
 * Router Class
 * Handles /portal route and redirects
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Router {
    
    /**
     * Initialize router
     */
    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'handle_portal_route' ) );
    }
    
    /**
     * Handle /portal route
     */
    public static function handle_portal_route() {
        if ( ! get_query_var( 'lgp_portal' ) ) {
            return;
        }
        
        // Check if user is authenticated
        if ( ! is_user_logged_in() ) {
            // Redirect to WordPress login with return URL
            $return_url = home_url( '/portal' );
            $login_url = wp_login_url( $return_url );
            wp_redirect( $login_url );
            exit;
        }
        
        // Check if user has portal access (Support or Partner role)
        $current_user = wp_get_current_user();
        $allowed_roles = array( 'lgp_support', 'lgp_partner' );
        
        if ( ! array_intersect( $allowed_roles, $current_user->roles ) ) {
            wp_die( 
                esc_html__( 'Access Denied: You do not have permission to access the portal.', 'loungenie-portal' ),
                esc_html__( 'Access Denied', 'loungenie-portal' ),
                array( 'response' => 403 )
            );
        }
        
        // Load portal shell
        self::load_portal();
        exit;
    }
    
    /**
     * Load portal template
     */
    private static function load_portal() {
        // Prevent theme CSS from loading
        remove_all_actions( 'wp_enqueue_scripts' );
        remove_all_actions( 'wp_print_styles' );
        remove_all_actions( 'wp_print_head_scripts' );
        remove_all_actions( 'wp_footer' );
        
        // Re-add only our assets
        add_action( 'wp_enqueue_scripts', array( 'LGP_Assets', 'enqueue_portal_assets' ) );
        
        // Check if specific section is requested
        $section = get_query_var( 'lgp_section' );
        
        // If map section and user is support, load map view directly
        if ( $section === 'map' && LGP_Auth::is_support() ) {
            self::load_map_view();
            return;
        }
        
        // If gateways section and user is support, load gateway view
        if ( $section === 'gateways' && LGP_Auth::is_support() ) {
            self::load_gateway_view();
            return;
        }
        
        // If training section, load training videos view
        if ( $section === 'training' ) {
            self::load_training_view();
            return;
        }
        
        // Otherwise load portal shell (which includes dashboards)
        require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
    }
    
    /**
     * Load map view in portal shell
     */
    private static function load_map_view() {
        require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
    }
    
    /**
     * Load gateway view in portal shell (support-only)
     */
    private static function load_gateway_view() {
        wp_enqueue_script( 'lgp-gateway-view', LGP_ASSETS_URL . 'js/gateway-view.js', array( 'lgp-portal' ), LGP_VERSION, true );
        require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
    }
    
    /**
     * Load training videos view in portal shell
     */
    private static function load_training_view() {
        wp_enqueue_script( 'lgp-training-view', LGP_ASSETS_URL . 'js/training-view.js', array( 'lgp-portal' ), LGP_VERSION, true );
        require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
    }
}
