<?php

/**
 * LG9 Inline CSS mu-plugin
 *
 * Outputs the LG9 shell CSS inline in the page <head> so staging shows styles
 * even if plugin files aren't loaded by aggregated CSS. Requires this file
 * to be uploaded to the staging site's wp-content/mu-plugins/ directory.
 */

add_action('wp_head', function () {
    // Only run on front-end
    if (is_admin()) return;

    $css_path = WP_CONTENT_DIR . '/plugins/lg-block-patterns/assets/css/style.css';
    if (file_exists($css_path)) {
        $css = file_get_contents($css_path);
        if ($css !== false) {
            echo "<!-- lg9-shell-inline (mu-plugin) -->\n";
            echo "<style id=\"lg9-shell-inline\">\n" . $css . "\n</style>\n";
        }
    }
}, 5);
