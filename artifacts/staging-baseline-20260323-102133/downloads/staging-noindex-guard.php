<?php

/**
 * Plugin Name: Staging Noindex Guard
 * Description: Forces noindex directives on the staging environment.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('send_headers', function () {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
});

add_filter('wp_robots', function ($robots) {
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    $robots['nosnippet'] = true;
    return $robots;
}, 999);

add_filter('pre_option_blog_public', function () {
    return '0';
}, 999);

add_filter('rank_math/modules', function ($modules) {
    unset($modules['analytics']);
    return $modules;
}, 999);
