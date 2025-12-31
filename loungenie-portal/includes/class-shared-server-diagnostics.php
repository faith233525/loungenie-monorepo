<?php
/**
 * LounGenie Portal Shared Server Diagnostic Tool
 *
 * Analyzes server environment and provides recommendations
 * Access via: yourdomain.com/wp-admin/admin.php?page=lgp_diagnostics
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

namespace LounGenie\Portal\Admin;

class SharedServerDiagnostics
{

    public function __construct()
    {
        add_action('admin_menu', array( $this, 'add_diagnostics_menu' ));
        add_action('admin_init', array( $this, 'handle_download' ));
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_assets' ));
    }

    /**
     * Add diagnostics menu
     */
    public function add_diagnostics_menu()
    {
        add_submenu_page(
            'options-general.php',
            'LounGenie Diagnostics',
            'LounGenie Diagnostics',
            'manage_options',
            'lgp_diagnostics',
            array( $this, 'display_diagnostics' )
        );
    }

    /**
     * Handle report download
     */
    public function handle_download()
    {
        if (! empty($_POST['lgp_download_report']) && current_user_can('manage_options') ) {
            check_admin_referer('lgp_diagnostics_nonce');
            $this->download_report();
        }
    }

    /**
     * Enqueue admin assets for diagnostics page
     */
    public function enqueue_assets( $hook )
    {
        if (! isset($_GET['page']) || 'lgp_diagnostics' !== sanitize_text_field(wp_unslash($_GET['page'])) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        wp_enqueue_style(
            'lgp-admin-diagnostics',
            LGP_ASSETS_URL . 'css/admin-diagnostics.css',
            array(),
            LGP_VERSION
        );
    }

    /**
     * Display diagnostics page
     */
    public function display_diagnostics()
    {
        if (! current_user_can('manage_options') ) {
            wp_die('Access denied');
        }

        ?>
        <div class="wrap">
            <h1>🔍 LounGenie Portal - Shared Server Diagnostics</h1>
            <p>Analyzing your server environment and plugin compatibility...</p>
            <hr>
            
        <?php $this->display_server_info(); ?>
        <?php $this->display_wordpress_info(); ?>
        <?php $this->display_plugin_info(); ?>
        <?php $this->display_database_info(); ?>
        <?php $this->display_recommendations(); ?>
            
            <form method="post" class="lgp-admin-form-actions">
        <?php wp_nonce_field('lgp_diagnostics_nonce'); ?>
                <button type="submit" name="lgp_download_report" class="button button-primary">
                    📥 Download Diagnostic Report
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Display server information
     */
    private function display_server_info()
    {
        echo '<div class="lgp-admin-panel">';
        echo '<h2>🖥️ Server Environment</h2>';
        echo '<table class="widefat">';

        $server_info = array(
        'Operating System'   => php_uname(),
        'PHP Version'        => phpversion(),
        'Web Server'         => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'Server IP'          => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'Max Execution Time' => ini_get('max_execution_time') . 's',
        'Memory Limit'       => ini_get('memory_limit'),
        'Upload Limit'       => ini_get('upload_max_filesize'),
        'POST Limit'         => ini_get('post_max_size'),
        'Current Memory'     => size_format(memory_get_usage(true)),
        'Peak Memory'        => size_format(memory_get_peak_usage(true)),
        );

        foreach ( $server_info as $key => $value ) {
            echo "<tr><td><strong>$key</strong></td><td><code>$value</code></td></tr>";
        }

        echo '</table>';
        echo '</div>';
    }

    /**
     * Display WordPress information
     */
    private function display_wordpress_info()
    {
        global $wp_version;

        echo '<div class="lgp-admin-panel">';
        echo '<h2>📦 WordPress Installation</h2>';
        echo '<table class="widefat">';

        $wp_info = array(
        'WordPress Version' => $wp_version,
        'Site URL'          => site_url(),
        'Home URL'          => home_url(),
        'WordPress Path'    => ABSPATH,
        'Content Path'      => WP_CONTENT_DIR,
        'Plugins Path'      => WP_PLUGIN_DIR,
        'Active Theme'      => wp_get_theme()->get('Name'),
        'Active Plugins'    => count(get_option('active_plugins', array())),
        'Database Host'     => DB_HOST,
        'Database Name'     => DB_NAME,
        'Multisite Enabled' => is_multisite() ? 'Yes' : 'No',
        );

        foreach ( $wp_info as $key => $value ) {
            echo "<tr><td><strong>$key</strong></td><td><code>$value</code></td></tr>";
        }

        echo '</table>';
        echo '</div>';
    }

    /**
     * Display plugin information
     */
    private function display_plugin_info()
    {
        echo '<div class="lgp-admin-panel">';
        echo '<h2>🧩 LounGenie Portal Information</h2>';

        $plugin_file = WP_PLUGIN_DIR . '/loungenie-portal/loungenie-portal.php';
        if (file_exists($plugin_file) ) {
            $plugin_data = get_plugin_data($plugin_file);
            echo '<table class="widefat">';

            $plugin_info = array(
            'Name'              => $plugin_data['Name'] ?? 'Unknown',
            'Version'           => $plugin_data['Version'] ?? 'Unknown',
            'Author'            => $plugin_data['Author'] ?? 'Unknown',
            'Status'            => is_plugin_active('loungenie-portal/loungenie-portal.php') ? 'Active' : 'Inactive',
            'PHP Required'      => '7.4.0+',
            'Namespace Support' => class_exists('\\LounGenie\\Portal\\LGP_Loader') ? '✓ Yes' : '✗ No',
            );

            foreach ( $plugin_info as $key => $value ) {
                echo "<tr><td><strong>$key</strong></td><td><code>$value</code></td></tr>";
            }

            echo '</table>';
        } else {
            echo '<p class="lgp-admin-status lgp-admin-status--error">❌ Plugin not found</p>';
        }

        echo '</div>';
    }

    /**
     * Display database information
     */
    private function display_database_info()
    {
        global $wpdb;

        echo '<div class="lgp-admin-panel">';
        echo '<h2>🗄️ Database Status</h2>';
        echo '<table class="widefat">';

        $db_version = $wpdb->get_var('SELECT VERSION()');
        $db_tables  = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");

        $db_info = array(
        'MySQL Version'   => $db_version,
        'Database Name'   => DB_NAME,
        'Database User'   => DB_USER,
        'Table Count'     => $db_tables,
        'Connection Type' => $wpdb->dbh ? 'Connected' : 'Disconnected',
        'Query Count'     => count($wpdb->queries),
        );

        foreach ( $db_info as $key => $value ) {
            echo "<tr><td><strong>$key</strong></td><td><code>$value</code></td></tr>";
        }

        echo '</table>';
        echo '</div>';
    }

    /**
     * Display recommendations
     */
    private function display_recommendations()
    {
        echo '<div class="lgp-admin-panel lgp-admin-panel--warning">';
        echo '<h2>⚡ Recommendations for Shared Server</h2>';

        $recommendations = array();

        // Check PHP version
        if (version_compare(phpversion(), '8.0.0', '<') ) {
            $recommendations[] = '⚠️ Consider upgrading PHP to 8.0+ for better performance';
        }

        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 64 * 1024 * 1024 ) {
            $recommendations[] = '⚠️ Memory limit below 64MB recommended - contact hosting provider';
        }

        // Check execution time
        $exec_time = (int) ini_get('max_execution_time');
        if ($exec_time < 30 && $exec_time > 0 ) {
            $recommendations[] = '⚠️ Execution time < 30s may cause timeouts - request increase from host';
        }

        // Check for caching plugins
        $active_plugins   = get_option('active_plugins', array());
        $has_cache_plugin = false;
        foreach ( $active_plugins as $plugin ) {
            if (strpos($plugin, 'cache') !== false || strpos($plugin, 'super-cache') !== false ) {
                $has_cache_plugin = true;
            }
        }
        if (! $has_cache_plugin ) {
            $recommendations[] = '💡 Install WP Super Cache or W3 Total Cache for better performance';
        }

        // Check for CDN
        $recommendations[] = '💡 Consider using Cloudflare or another CDN for static assets';

        // Check SSL
        if (! is_ssl() ) {
            $recommendations[] = '💡 Enable SSL/HTTPS for better security and performance';
        }

        // Check database size
        $db_size = $wpdb->get_var("SELECT sum(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
        if ($db_size > 100 * 1024 * 1024 ) {
            $recommendations[] = '🗑️ Database is large (' . size_format($db_size) . ') - consider cleanup/optimization';
        }

        if (empty($recommendations) ) {
            echo '<p>✅ All checks passed! Your server is well-optimized for LounGenie Portal.</p>';
        } else {
            echo '<ul>';
            foreach ( $recommendations as $rec ) {
                echo "<li>$rec</li>";
            }
            echo '</ul>';
        }

        echo '</div>';
    }

    /**
     * Download diagnostic report
     */
    private function download_report()
    {
        $report  = "LounGenie Portal - Shared Server Diagnostic Report\n";
        $report .= 'Generated: ' . current_time('mysql') . "\n";
        $report .= '=' . str_repeat('=', 60) . "\n\n";

        $report .= "SERVER ENVIRONMENT\n";
        $report .= str_repeat('-', 60) . "\n";
        $report .= 'PHP Version: ' . phpversion() . "\n";
        $report .= 'Web Server: ' . ( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ) . "\n";
        $report .= 'Memory Limit: ' . ini_get('memory_limit') . "\n";
        $report .= 'Max Execution: ' . ini_get('max_execution_time') . "s\n";
        $report .= 'Upload Limit: ' . ini_get('upload_max_filesize') . "\n\n";

        $report .= "WordPress INFO\n";
        $report .= str_repeat('-', 60) . "\n";
        $report .= 'WordPress Version: ' . $GLOBALS['wp_version'] . "\n";
        $report .= 'Active Plugins: ' . count(get_option('active_plugins', array())) . "\n";
        $report .= 'Theme: ' . wp_get_theme()->get('Name') . "\n";
        $report .= 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n\n";

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="lgp-diagnostics-' . date('Y-m-d-His') . '.txt"');
        echo $report;
        exit;
    }
}

// Initialize if in WordPress admin
if (is_admin() ) {
    new SharedServerDiagnostics();
}
