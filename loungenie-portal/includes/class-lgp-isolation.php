<?php

/**
 * Portal Isolation Layer
 *
 * Ensures the LounGenie Portal renders without theme inheritance and keeps
 * partner/support users out of wp-admin while hiding the admin bar.
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Isolation
{


    /**
     * Bootstrap isolation behaviors
     */
    public static function init()
    {
        // Hide admin bar for portal roles everywhere (front + admin heads)
        add_filter('show_admin_bar', array( __CLASS__, 'maybe_hide_admin_bar' ), 20);

        // Prevent portal roles from accessing wp-admin
        add_action('admin_init', array( __CLASS__, 'redirect_portal_roles_from_admin' ), 0);

        // Strip theme assets when rendering portal/login routes
        add_action('wp_enqueue_scripts', array( __CLASS__, 'strip_non_portal_assets' ), PHP_INT_MAX);
    }

    /**
     * Determine if the current request is for a portal-rendered page
     *
     * @return bool
     */
    private static function is_portal_context()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_uri = untrailingslashit(sanitize_text_field(strtok($request_uri, '?')));

        $portal_paths = array(
        '/portal',
        '/portal/login',
        '/support-login',
        '/partner-login',
        );

        if (in_array($request_uri, $portal_paths, true) ) {
            return true;
        }

        // Query vars set by rewrite rules
        if (get_query_var('lgp_portal') || get_query_var('lgp_portal_login') || get_query_var('lgp_support_login') || get_query_var('lgp_partner_login') ) {
            return true;
        }

        return false;
    }

    /**
     * Hide the admin bar for partner/support roles
     *
     * @param bool $show Current admin bar state
     * @return bool
     */
    public static function maybe_hide_admin_bar( $show )
    {
        if (class_exists('LGP_Auth') && ( LGP_Auth::is_partner() || LGP_Auth::is_support() ) ) {
            return false;
        }
        return $show;
    }

    /**
     * Redirect partner/support users away from wp-admin into the portal
     */
    public static function redirect_portal_roles_from_admin()
    {
        if (! is_user_logged_in() ) {
            return;
        }

        // Allow AJAX/cron
        if (wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }

        // Only act inside admin
        if (! is_admin() ) {
            return;
        }

        // Let site admins through
        if (current_user_can('manage_options') ) {
            return;
        }

        if (class_exists('LGP_Auth') && ( LGP_Auth::is_partner() || LGP_Auth::is_support() ) ) {
            wp_safe_redirect(home_url('/portal'));
            exit;
        }
    }

    /**
     * Remove theme and admin assets from portal-rendered pages
     */
    public static function strip_non_portal_assets()
    {
        if (! self::is_portal_context() ) {
            return;
        }

        global $wp_styles, $wp_scripts;

        // Allowlist of styles/scripts needed by the portal
        $allowed_styles = array(
        'lgp-font-montserrat',
        'font-awesome',
        'lgp-design-tokens',
        'lgp-portal-components',
        'lgp-design-system',
        'lgp-portal',
        'lgp-role-switcher',
        'leaflet',
        'dashicons', // keep minimal core icons if a plugin depends on them
        );

        $allowed_scripts = array(
        'jquery',
        'wp-api',
        'lgp-utils',
        'lgp-portal',
        'lgp-portal-init',
        'lgp-company-profile-enhancements',
        'lgp-company-profile-partner-polish',
        'lgp-company-map',
        'lgp-responsive-sidebar',
        'lgp-portal-demo',
        'lgp-tickets-view',
        'lgp-gateway-view',
        'lgp-knowledge-center-view',
        'lgp-help-guides-view',
        'leaflet',
        );

        // Remove emoji scripts/styles to keep head clean
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');

        if ($wp_styles instanceof WP_Styles ) {
            foreach ( (array) $wp_styles->queue as $handle ) {
                if (! in_array($handle, $allowed_styles, true) ) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }

        if ($wp_scripts instanceof WP_Scripts ) {
            foreach ( (array) $wp_scripts->queue as $handle ) {
                if (! in_array($handle, $allowed_scripts, true) ) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
        }
    }
}
