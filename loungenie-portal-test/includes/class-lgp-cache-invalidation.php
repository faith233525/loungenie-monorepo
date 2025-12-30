<?php

/**
 * Performance Cache Invalidation Hooks
 * 
 * Automatically clears cached data when entities are created, updated, or deleted.
 * This ensures users always see fresh data after making changes while maintaining
 * performance benefits of caching for read-heavy operations.
 *
 * @package LounGenie Portal
 * @since 2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Cache Invalidation Handler
 * 
 * Provides hooks to clear caches when data changes occur.
 * Integrates with LGP_Database::clear_portal_caches() method.
 */
class LGP_Cache_Invalidation
{

    /**
     * Initialize cache invalidation hooks
     *
     * @return void
     */
    public static function init()
    {
        // Ticket operations
        add_action('lgp_ticket_created', array(__CLASS__, 'on_ticket_change'), 10, 2);
        add_action('lgp_ticket_updated', array(__CLASS__, 'on_ticket_change'), 10, 2);
        add_action('lgp_ticket_deleted', array(__CLASS__, 'on_ticket_change'), 10, 2);
        add_action('lgp_ticket_status_changed', array(__CLASS__, 'on_ticket_change'), 10, 2);

        // Unit operations
        add_action('lgp_unit_created', array(__CLASS__, 'on_unit_change'), 10, 2);
        add_action('lgp_unit_updated', array(__CLASS__, 'on_unit_change'), 10, 2);
        add_action('lgp_unit_deleted', array(__CLASS__, 'on_unit_change'), 10, 2);

        // Company operations
        add_action('lgp_company_updated', array(__CLASS__, 'on_company_change'), 10, 1);
        add_action('lgp_company_created', array(__CLASS__, 'on_company_change'), 10, 1);

        // Service request operations
        add_action('lgp_service_request_created', array(__CLASS__, 'on_service_request_change'), 10, 2);
        add_action('lgp_service_request_updated', array(__CLASS__, 'on_service_request_change'), 10, 2);

        // Manual cache clear action
        add_action('admin_post_lgp_clear_all_caches', array(__CLASS__, 'clear_all_caches_admin'));
    }

    /**
     * Handle ticket changes
     *
     * @param int $ticket_id Ticket ID
     * @param int $company_id Company ID
     * @return void
     */
    public static function on_ticket_change($ticket_id, $company_id)
    {
        if (class_exists('LGP_Database')) {
            LGP_Database::clear_portal_caches($company_id);

            // Also clear support-wide caches
            delete_transient('lgp_dashboard_support_stats');
            delete_transient('lgp_dashboard_recent_tickets');
        }
    }

    /**
     * Handle unit changes
     *
     * @param int $unit_id Unit ID
     * @param int $company_id Company ID
     * @return void
     */
    public static function on_unit_change($unit_id, $company_id)
    {
        if (class_exists('LGP_Database')) {
            LGP_Database::clear_portal_caches($company_id);

            // Clear support-wide aggregation caches
            delete_transient('lgp_dashboard_support_stats');
            delete_transient('lgp_dashboard_top_metrics');
        }
    }

    /**
     * Handle company changes
     *
     * @param int $company_id Company ID
     * @return void
     */
    public static function on_company_change($company_id)
    {
        if (class_exists('LGP_Database')) {
            LGP_Database::clear_portal_caches($company_id);

            // Clear company markers cache (for map)
            delete_transient('lgp_company_markers_all');

            // Queue for geocoding if address changed
            if (class_exists('LGP_Geocode')) {
                LGP_Geocode::queue_geocode($company_id);
            }
        }
    }

    /**
     * Handle service request changes
     *
     * @param int $request_id Request ID
     * @param int $company_id Company ID
     * @return void
     */
    public static function on_service_request_change($request_id, $company_id)
    {
        if (class_exists('LGP_Database')) {
            LGP_Database::clear_portal_caches($company_id);
        }
    }

    /**
     * Admin action to manually clear all caches
     *
     * @return void
     */
    public static function clear_all_caches_admin()
    {
        // Security check
        if (! current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'loungenie-portal'));
        }

        check_admin_referer('lgp_clear_caches');

        if (class_exists('LGP_Database')) {
            LGP_Database::clear_portal_caches();
        }

        // Clear all portal transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_lgp_%' 
             OR option_name LIKE '_transient_timeout_lgp_%'"
        );

        wp_redirect(add_query_arg(
            array('page' => 'lgp-settings', 'cache_cleared' => '1'),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Add admin menu item to clear caches
     *
     * @return void
     */
    public static function add_admin_menu()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'lgp-settings',
            __('Clear Performance Caches', 'loungenie-portal'),
            __('Clear Caches', 'loungenie-portal'),
            'manage_options',
            'lgp-clear-caches',
            array(__CLASS__, 'render_clear_caches_page')
        );
    }

    /**
     * Render cache management page
     *
     * @return void
     */
    public static function render_clear_caches_page()
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Performance Cache Management', 'loungenie-portal'); ?></h1>

            <?php if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') : ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('All portal caches have been cleared successfully.', 'loungenie-portal'); ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php esc_html_e('Current Cache Status', 'loungenie-portal'); ?></h2>

                <?php
                $caches = array(
                    'lgp_dashboard_support_stats' => __('Dashboard Statistics', 'loungenie-portal'),
                    'lgp_dashboard_recent_tickets' => __('Recent Tickets', 'loungenie-portal'),
                    'lgp_dashboard_top_metrics' => __('Top Metrics', 'loungenie-portal'),
                    'lgp_dashboard_metrics_support' => __('API Metrics (Support)', 'loungenie-portal'),
                    'lgp_units_list_all' => __('Units List', 'loungenie-portal'),
                    'lgp_company_markers_all' => __('Company Map Markers', 'loungenie-portal'),
                );

                echo '<table class="widefat">';
                echo '<thead><tr><th>Cache Key</th><th>Status</th><th>Size</th></tr></thead>';
                echo '<tbody>';

                foreach ($caches as $key => $label) {
                    $value = get_transient($key);
                    $status = $value !== false ? '✅ Active' : '❌ Empty';
                    $size = $value !== false ? size_format(strlen(serialize($value))) : '-';

                    echo '<tr>';
                    echo '<td><strong>' . esc_html($label) . '</strong><br><code>' . esc_html($key) . '</code></td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td>' . esc_html($size) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                ?>

                <h3><?php esc_html_e('Geocoding Queue', 'loungenie-portal'); ?></h3>
                <?php
                $queue = get_option('lgp_geocode_queue', array());
                echo '<p>';
                printf(
                    esc_html__('Pending companies: %d', 'loungenie-portal'),
                    count($queue)
                );
                echo '</p>';
                ?>

                <h3><?php esc_html_e('Performance Metrics', 'loungenie-portal'); ?></h3>
                <?php
                global $wpdb;
                echo '<p>';
                printf(
                    esc_html__('Database queries this request: %d', 'loungenie-portal'),
                    $wpdb->num_queries
                );
                echo '<br>';
                printf(
                    esc_html__('Memory usage: %s MB', 'loungenie-portal'),
                    number_format(memory_get_usage(true) / 1024 / 1024, 2)
                );
                echo '</p>';
                ?>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Clear All Caches', 'loungenie-portal'); ?></h2>
                <p><?php esc_html_e('Use this when you need to force-refresh all cached data. Caches will rebuild automatically on next access.', 'loungenie-portal'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('lgp_clear_caches'); ?>
                    <input type="hidden" name="action" value="lgp_clear_all_caches">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Clear All Portal Caches', 'loungenie-portal'); ?>
                    </button>
                </form>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Cache Configuration', 'loungenie-portal'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Cache Type', 'loungenie-portal'); ?></th>
                            <th><?php esc_html_e('TTL (Time To Live)', 'loungenie-portal'); ?></th>
                            <th><?php esc_html_e('Purpose', 'loungenie-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Dashboard Statistics</td>
                            <td>5 minutes</td>
                            <td>Aggregated counts and metrics</td>
                        </tr>
                        <tr>
                            <td>Recent Tickets</td>
                            <td>2 minutes</td>
                            <td>Latest 10 tickets</td>
                        </tr>
                        <tr>
                            <td>Top Metrics</td>
                            <td>10 minutes</td>
                            <td>Color/venue/lock aggregations</td>
                        </tr>
                        <tr>
                            <td>API Metrics</td>
                            <td>5 minutes</td>
                            <td>Dashboard API responses</td>
                        </tr>
                        <tr>
                            <td>Units List</td>
                            <td>5 minutes</td>
                            <td>Full unit listings</td>
                        </tr>
                        <tr>
                            <td>Ticket Lists</td>
                            <td>2 minutes</td>
                            <td>Paginated ticket views</td>
                        </tr>
                        <tr>
                            <td>Company Markers</td>
                            <td>30 minutes</td>
                            <td>Map geocoding data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
<?php
    }
}

// Initialize cache invalidation system
LGP_Cache_Invalidation::init();
add_action('admin_menu', array('LGP_Cache_Invalidation', 'add_admin_menu'), 99);
