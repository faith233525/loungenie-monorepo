<?php
/**
 * Activate Loungenie Theme (temporary mu-plugin)
 * This mu-plugin will activate the `loungenie` theme on first load
 * and then attempt to remove itself.
 */

add_action('init', function() {
    if (function_exists('get_option') && function_exists('update_option')) {
        $current_template = get_option('template');
        $current_stylesheet = get_option('stylesheet');
        if ($current_template !== 'loungenie' || $current_stylesheet !== 'loungenie') {
            if (function_exists('switch_theme')) {
                switch_theme('loungenie');
            } else {
                update_option('template', 'loungenie');
                update_option('stylesheet', 'loungenie');
            }
        }
    }
    // Best-effort remove this file so it doesn't run again
    @unlink(__FILE__);
}, 1);
