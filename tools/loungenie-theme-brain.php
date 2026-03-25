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

add_action('init', function () {
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
        'callback' => function () {
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

// 5. Ensure Primary Menu order and items (run once)
add_action('init', function () {
    if (get_option('loungenie_menu_ordered')) {
        return;
    }

    // Desired menu sequence
    $desired = array(
        array('title' => 'Home', 'url' => home_url('/')),
        array('title' => 'Features', 'find_title' => 'LounGenie Features'),
        array('title' => 'Gallery', 'find_slug' => 'cabana-installation-photos'),
        array('title' => 'About', 'find_title' => 'About LounGenie'),
        array('title' => 'Contact', 'find_title' => 'Contact LounGenie'),
        array('title' => 'Investors', 'page_id' => 5668),
    );

    // Find menu: prefer 'Main Menu', otherwise first menu
    $menu_term = get_term_by('name', 'Main Menu', 'nav_menu');
    if (! $menu_term) {
        $menus = wp_get_nav_menus();
        if (empty($menus)) {
            return; // no menus to operate on
        }
        $menu_term = $menus[0];
    }

    $menu_id = $menu_term->term_id;
    $position = 0;

    foreach ($desired as $item) {
        $position++;
        $args = array();

        if (! empty($item['page_id'])) {
            $page = get_post($item['page_id']);
            if ($page) {
                $args = array(
                    'menu-item-object-id' => $page->ID,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-title' => $item['title'],
                    'menu-item-status' => 'publish',
                    'menu-item-position' => $position,
                );
            }
        } elseif (! empty($item['find_title'])) {
            $found = get_page_by_title($item['find_title']);
            if ($found) {
                $args = array(
                    'menu-item-object-id' => $found->ID,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-title' => $item['title'],
                    'menu-item-status' => 'publish',
                    'menu-item-position' => $position,
                );
            }
        } elseif (! empty($item['find_slug'])) {
            $pages = get_posts(array('post_type' => 'page', 'name' => $item['find_slug'], 'posts_per_page' => 1));
            if (! empty($pages)) {
                $p = $pages[0];
                $args = array(
                    'menu-item-object-id' => $p->ID,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-title' => $item['title'],
                    'menu-item-status' => 'publish',
                    'menu-item-position' => $position,
                );
            }
        } elseif (! empty($item['url'])) {
            $args = array(
                'menu-item-title' => $item['title'],
                'menu-item-url' => $item['url'],
                'menu-item-status' => 'publish',
                'menu-item-position' => $position,
            );
        }

        if (! empty($args)) {
            // Try to find existing menu item to update by URL or object id
            $existing = wp_get_nav_menu_items($menu_id);
            $found_id = 0;
            if ($existing) {
                foreach ($existing as $ex) {
                    if (! empty($args['menu-item-object-id']) && intval($ex->object_id) === intval($args['menu-item-object-id'])) {
                        $found_id = $ex->ID;
                        break;
                    }
                    if (! empty($args['menu-item-url']) && untrailingslashit($ex->url) === untrailingslashit($args['menu-item-url'])) {
                        $found_id = $ex->ID;
                        break;
                    }
                }
            }

            wp_update_nav_menu_item($menu_id, $found_id, $args);
        }
    }

    // Mark as ordered to avoid repeating
    update_option('loungenie_menu_ordered', time());
});

// 6. Add submenu items under Investors (Board, Financials, Press) — run once
add_action('init', function () {
    if (get_option('loungenie_menu_investor_subs')) {
        return;
    }

    // Find menu (prefer 'Main Menu')
    $menu_term = get_term_by('name', 'Main Menu', 'nav_menu');
    if (! $menu_term) {
        $menus = wp_get_nav_menus();
        if (empty($menus)) {
            return; // no menus available
        }
        $menu_term = $menus[0];
    }

    $menu_id = $menu_term->term_id;
    $items = wp_get_nav_menu_items($menu_id);
    if (! $items) {
        return;
    }

    // Locate parent 'Investors' menu item (prefer the page with ID 5668)
    $parent_item_id = 0;
    foreach ($items as $it) {
        if (isset($it->object_id) && intval($it->object_id) === 5668) {
            $parent_item_id = $it->ID;
            break;
        }
        if (strtolower(trim($it->title)) === 'investors') {
            $parent_item_id = $it->ID;
            break;
        }
    }

    if (! $parent_item_id) {
        return; // cannot locate parent to attach children
    }

    $subs = array(
        array('title' => 'Board', 'find_title' => 'Board Members'),
        array('title' => 'Financials', 'url' => home_url('/investors/financials')),
        array('title' => 'Press', 'find_title' => 'Press'),
    );

    $position = 0;
    foreach ($subs as $s) {
        $position++;
        $args = array();

        if (! empty($s['find_title'])) {
            $found = get_page_by_title($s['find_title']);
            if ($found) {
                $args = array(
                    'menu-item-object-id' => $found->ID,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-title' => $s['title'],
                    'menu-item-status' => 'publish',
                    'menu-item-parent-id' => $parent_item_id,
                    'menu-item-position' => $position,
                );
            } else {
                // fallback to a simple URL under /investors/
                $args = array(
                    'menu-item-title' => $s['title'],
                    'menu-item-url' => home_url('/investors/' . strtolower($s['title'])),
                    'menu-item-status' => 'publish',
                    'menu-item-parent-id' => $parent_item_id,
                    'menu-item-position' => $position,
                );
            }
        } elseif (! empty($s['url'])) {
            $args = array(
                'menu-item-title' => $s['title'],
                'menu-item-url' => $s['url'],
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_item_id,
                'menu-item-position' => $position,
            );
        }

        if (! empty($args)) {
            wp_update_nav_menu_item($menu_id, 0, $args);
        }
    }

    update_option('loungenie_menu_investor_subs', time());
});
