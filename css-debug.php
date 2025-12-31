<?php

/**
 * CSS Debug & Diagnostic Tool
 * Add this to a custom page template to check if CSS is loading
 * 
 * Usage: Create a WordPress page, assign this template, visit it.
 */

if (! defined('ABSPATH')) {
    exit;
}

// Only admins
if (! current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>LounGenie Portal - CSS Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #f5f5f5;
            padding: 20px;
        }

        .debug-box {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
        }

        .ok {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        code {
            background: #e0e0e0;
            padding: 2px 5px;
        }
    </style>
</head>

<body>
    <h1>LounGenie Portal CSS Debug</h1>

    <div class="debug-box">
        <h2>1. Plugin Constants</h2>
        <p><strong>LGP_VERSION:</strong> <code><?php echo defined('LGP_VERSION') ? LGP_VERSION : 'NOT DEFINED'; ?></code></p>
        <p><strong>LGP_PLUGIN_DIR:</strong> <code><?php echo defined('LGP_PLUGIN_DIR') ? LGP_PLUGIN_DIR : 'NOT DEFINED'; ?></code></p>
        <p><strong>LGP_PLUGIN_URL:</strong> <code><?php echo defined('LGP_PLUGIN_URL') ? LGP_PLUGIN_URL : 'NOT DEFINED'; ?></code></p>
        <p><strong>LGP_ASSETS_URL:</strong> <code><?php echo defined('LGP_ASSETS_URL') ? LGP_ASSETS_URL : 'NOT DEFINED'; ?></code></p>
    </div>

    <div class="debug-box">
        <h2>2. CSS File Existence</h2>
        <?php
        $css_files = array(
            'lgp-reset.css',
            'design-tokens.css',
            'portal-components.css',
            'design-system-refactored.css',
            'portal.css',
        );

        if (defined('LGP_PLUGIN_DIR')) {
            foreach ($css_files as $file) {
                $path = LGP_PLUGIN_DIR . 'assets/css/' . $file;
                $exists = file_exists($path) ? '✓ EXISTS' : '✗ MISSING';
                $class = file_exists($path) ? 'ok' : 'error';
                echo "<p><strong>$file:</strong> <span class=\"$class\">$exists</span></p>";
            }
        } else {
            echo '<p class="error">LGP_PLUGIN_DIR not defined!</p>';
        }
        ?>
    </div>

    <div class="debug-box">
        <h2>3. Enqueued Stylesheets</h2>
        <?php
        // Check if LGP_Assets class exists
        if (class_exists('LGP_Assets')) {
            echo '<p class="ok">✓ LGP_Assets class found</p>';

            // Check if enqueue_portal_assets method exists
            if (method_exists('LGP_Assets', 'enqueue_portal_assets')) {
                echo '<p class="ok">✓ enqueue_portal_assets() method exists</p>';
            } else {
                echo '<p class="error">✗ enqueue_portal_assets() method NOT FOUND</p>';
            }
        } else {
            echo '<p class="error">✗ LGP_Assets class NOT FOUND</p>';
        }

        // List all enqueued styles on front-end
        global $wp_styles;
        if (! is_admin() && $wp_styles) {
            echo '<h3>All Enqueued Styles:</h3>';
            foreach ($wp_styles->queue as $handle) {
                if (strpos($handle, 'lgp') !== false) {
                    echo "<p class=\"ok\">✓ $handle</p>";
                }
            }
        }
        ?>
    </div>

    <div class="debug-box">
        <h2>4. WordPress Info</h2>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>Site URL:</strong> <code><?php echo site_url(); ?></code></p>
        <p><strong>Plugin URL:</strong> <code><?php echo plugin_dir_url(LGP_PLUGIN_FILE); ?></code></p>
    </div>

    <div class="debug-box">
        <h2>5. Sample CSS URL Check</h2>
        <?php
        if (defined('LGP_ASSETS_URL')) {
            $test_url = LGP_ASSETS_URL . 'css/portal.css';
            echo "<p>Expected URL: <code>$test_url</code></p>";
            echo "<p><a href=\"$test_url\" target=\"_blank\">Click to test CSS file</a></p>";

            // Try to fetch the file
            $response = wp_remote_head($test_url);
            if (is_wp_error($response)) {
                echo "<p class=\"error\">✗ Error fetching: " . $response->get_error_message() . "</p>";
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if (200 === $code) {
                    echo "<p class=\"ok\">✓ File accessible (HTTP $code)</p>";
                } else {
                    echo "<p class=\"error\">✗ File not found (HTTP $code)</p>";
                }
            }
        }
        ?>
    </div>

    <div class="debug-box">
        <h2>6. Recommendations</h2>
        <ul>
            <li>If CSS files show "MISSING", ensure the ZIP was extracted completely.</li>
            <li>If URLs show "✗", check that <code>wp-config.php</code> has correct <code>WP_CONTENT_URL</code>.</li>
            <li>If files show "HTTP 404", check <code>.htaccess</code> rules aren't blocking assets.</li>
            <li>Disable caching plugins (WP Rocket, Autoptimize) temporarily to test.</li>
            <li>Check browser DevTools → Network tab for failed CSS requests.</li>
        </ul>
    </div>

</body>

</html>