<?php
/**
 * Assets Management Class
 * Handles CSS and JS enqueuing for the portal
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Assets {
    
    /**
     * Initialize assets management
     */
    public static function init() {
        // Assets are enqueued by router when needed
    }
    
    /**
     * Enqueue portal assets (CSS and JS)
     * Called by router when loading portal
     */
    public static function enqueue_portal_assets() {
        // Enqueue portal CSS
        wp_enqueue_style(
            'lgp-portal',
            LGP_ASSETS_URL . 'css/portal.css',
            array(),
            LGP_VERSION,
            'all'
        );
        
        // Enqueue portal JS
        wp_enqueue_script(
            'lgp-portal',
            LGP_ASSETS_URL . 'js/portal.js',
            array(),
            LGP_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script(
            'lgp-portal',
            'lgpData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'restUrl' => rest_url( 'lgp/v1/' ),
                'nonce' => wp_create_nonce( 'lgp_portal_nonce' ),
                'isSupport' => LGP_Auth::is_support(),
                'isPartner' => LGP_Auth::is_partner(),
            )
        );
    }
}
