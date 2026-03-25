<?php
/**
 * Plugin Name: LounGenie Theme Brain (2026)
 * Description: Programmatic theme orchestration for LounGenie staging - sets Primary Menu, brand palette, transparent header, and exposes a cache-purge REST endpoint for LiteSpeed.
 * Version: 1.0.0
 * Author: Copilot (generated)
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function() {
    // 1. Force "Main Menu" to Primary Navigation if it exists
    $menu = get_term_by('name', 'Main Menu', 'nav_menu');
    if ($menu) {
        $locations = get_theme_mod('nav_menu_locations', array());
        $locations['primary'] = $menu->term_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    // 2. Set LounGenie 2026 Brand Palette (#003366, #0073e6) for Kadence if option exists
    $palette = get_option('kadence_color_palette');
    if (is_array($palette) && isset($palette['palette'])) {
        $palette['palette'][0] = '#003366'; // Deep Navy
        $palette['palette'][1] = '#0073e6'; // Electric Blue
        update_option('kadence_color_palette', $palette);
    }

    // 3. Enable Transparent Header Site-Wide (Kadence theme_mod)
    set_theme_mod('transparent_header_active', true);
});

// 4. Create Automated Purge Endpoint for LiteSpeed
add_action('rest_api_init', function () {
    register_rest_route('loungenie/v1', '/purge', array(
        'methods' => 'POST',
        'callback' => function() {
            if (has_action('litespeed_purge_all')) {
                do_action('litespeed_purge_all');
                return new WP_REST_Response('LounGenie Cache Purged!', 200);
            }
            return new WP_Error('no_litespeed', 'LiteSpeed not active', array('status' => 404));
        },
        // IMPORTANT: For staging only; secure this endpoint in production
        'permission_callback' => '__return_true',
    ));
});
