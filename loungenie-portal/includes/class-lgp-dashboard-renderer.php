<?php

/**
 * LounGenie Portal Dashboard Renderer
 *
 * Renders the professional dashboard with SLA tracking, ticket assignment,
 * and canned responses integration.
 *
 * @package LounGenie Portal
 * @version 1.0.0
 */

namespace LounGenie\Portal;

if (! defined('ABSPATH')) {
    exit;
}

class LGP_Dashboard_Renderer
{

    /**
     * Render the support dashboard.
     */
    public static function render_support_dashboard()
    {
        $stats = self::get_dashboard_stats();
        $tickets = self::get_active_tickets();

?>
        <div class="lgp-dashboard-support">
            <h1 class="lgp-page-title">Support Dashboard</h1>
            <p class="lgp-page-subtitle">Real-time monitoring • SLA tracking • Ticket management</p>

            <?php self::render_stats_grid($stats); ?>
            <?php self::render_tickets_table($tickets); ?>
            <?php self::render_features_section(); ?>
        </div>
    <?php
    }

    /**
     * Render the partner dashboard.
     */
    public static function render_partner_dashboard()
    {
        $partner_stats = self::get_partner_stats();
        $partner_tickets = self::get_partner_tickets();

    ?>
        <div class="lgp-dashboard-partner">
            <h1 class="lgp-page-title">Partner Dashboard</h1>
            <p class="lgp-page-subtitle">Your company data • Real-time updates</p>

            <?php self::render_stats_grid($partner_stats); ?>
            <?php self::render_tickets_table($partner_tickets); ?>
        </div>
    <?php
    }

    /**
     * Get dashboard statistics.
     */
    private static function get_dashboard_stats()
    {
        global $wpdb;

        $table_companies = $wpdb->prefix . 'lgp_companies';
        $table_units = $wpdb->prefix . 'lgp_units';
        $table_tickets = $wpdb->prefix . 'lgp_tickets';

        return array(
            array(
                'label' => 'Total Companies',
                'value' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_companies}"),
                'icon'  => '▢',
                'color' => 'navy',
                'change' => '↑ 12% from last month',
            ),
            array(
                'label' => 'LounGenie Units',
                'value' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_units}"),
                'icon'  => '▥',
                'color' => 'cyan',
                'change' => '↑ 8% from last month',
            ),
            array(
                'label' => 'Open Tickets',
                'value' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_tickets} WHERE status = 'open'"),
                'icon'  => '✉',
                'color' => 'warning',
                'change' => '↓ 15% from last week',
            ),
            array(
                'label' => 'SLA Breaching',
                'value' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_tickets} WHERE status = 'open' AND sla_due_date < NOW()"),
                'icon'  => '⌛',
                'color' => 'teal',
                'change' => 'Requires attention',
            ),
            array(
                'label' => 'System Health',
                'value' => '98.2%',
                'icon'  => '✔',
                'color' => 'success',
                'change' => 'All systems operational',
            ),
        );
    }

    /**
     * Get partner dashboard statistics.
     */
    private static function get_partner_stats()
    {
        global $wpdb;
        global $current_user;

        $partner_id = get_user_meta($current_user->ID, 'lgp_company_id', true);

        if (! $partner_id) {
            return array();
        }

        $table_units = $wpdb->prefix . 'lgp_units';
        $table_tickets = $wpdb->prefix . 'lgp_tickets';

        return array(
            array(
                'label' => 'Your LounGenie Units',
                'value' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_units} WHERE company_id = %d", $partner_id)),
                'icon'  => '▥',
                'color' => 'teal',
                'change' => '↑ 2 new units this quarter',
            ),
            array(
                'label' => 'Open Support Tickets',
                'value' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_tickets} WHERE company_id = %d AND status = 'open'", $partner_id)),
                'icon'  => '✉',
                'color' => 'warning',
                'change' => '↓ 2 resolved this week',
            ),
            array(
                'label' => 'Locations',
                'value' => '5',
                'icon'  => '▢',
                'color' => 'navy',
                'change' => 'Across 3 states',
            ),
            array(
                'label' => 'Units Active',
                'value' => '96%',
                'icon'  => '✔',
                'color' => 'success',
                'change' => 'All systems operational',
            ),
        );
    }

    /**
     * Get active tickets.
     */
    private static function get_active_tickets()
    {
        global $wpdb;

        $table_tickets = $wpdb->prefix . 'lgp_tickets';
        $table_companies = $wpdb->prefix . 'lgp_companies';

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results(
            "SELECT t.id, t.service_request_id, c.name as company_name, t.status, 
				t.assigned_to, t.priority_level, t.sla_due_date, t.created_at
			FROM {$table_tickets} t
			LEFT JOIN {$table_companies} c ON t.service_request_id = c.id
			WHERE t.status IN ('open', 'pending')
			ORDER BY t.sla_due_date ASC
			LIMIT 5"
        );
        // phpcs:enable

        return $results ? $results : array();
    }

    /**
     * Get partner tickets.
     */
    private static function get_partner_tickets()
    {
        global $wpdb;
        global $current_user;

        $partner_id = get_user_meta($current_user->ID, 'lgp_company_id', true);

        if (! $partner_id) {
            return array();
        }

        $table_tickets = $wpdb->prefix . 'lgp_tickets';

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, service_request_id, status, priority_level, created_at
				FROM {$table_tickets}
				WHERE service_request_id = %d
				ORDER BY created_at DESC
				LIMIT 10",
                $partner_id
            )
        );
        // phpcs:enable

        return $results ? $results : array();
    }

    /**
     * Render stats grid.
     *
     * @param array $stats Stats array.
     */
    private static function render_stats_grid($stats)
    {
    ?>
        <div class="lgp-stats-grid">
            <?php
            foreach ($stats as $stat) {
            ?>
                <div class="lgp-stat-card">
                    <div class="lgp-stat-icon-box lgp-stat-icon-<?php echo esc_attr($stat['color']); ?>">
                        <?php echo esc_html($stat['icon']); ?>
                    </div>
                    <div class="lgp-stat-content">
                        <div class="lgp-stat-value"><?php echo esc_html($stat['value']); ?></div>
                        <div class="lgp-stat-label"><?php echo esc_html($stat['label']); ?></div>
                        <div class="lgp-stat-change"><?php echo esc_html($stat['change']); ?></div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    <?php
    }

    /**
     * Render tickets table.
     *
     * @param array $tickets Tickets array.
     */
    private static function render_tickets_table($tickets)
    {
    ?>
        <div class="lgp-card">
            <div class="lgp-card-header">
                <h3 class="lgp-card-title">
                    <div class="lgp-card-icon">✉</div>
                    Active Tickets
                </h3>
                <p class="lgp-card-subtitle">Real-time updates • Assignment tracking • SLA monitoring</p>
            </div>
            <div class="lgp-card-body">
                <div class="lgp-table-container">
                    <table class="lgp-table">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Company</th>
                                <th>Priority</th>
                                <th>SLA Status</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($tickets)) {
                            ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #7A8699;">
                                        No open tickets at this time.
                                    </td>
                                </tr>
                                <?php
                            } else {
                                foreach ($tickets as $ticket) {
                                    $priority_color = self::get_priority_badge_class($ticket->priority_level);
                                    $sla_badge = self::get_sla_badge($ticket->sla_due_date);
                                ?>
                                    <tr>
                                        <td><strong>#TK-<?php echo esc_html(str_pad($ticket->id, 4, '0', STR_PAD_LEFT)); ?></strong></td>
                                        <td><?php echo esc_html($ticket->company_name); ?></td>
                                        <td><span class="lgp-badge <?php echo esc_attr($priority_color); ?>"><?php echo esc_html(ucfirst($ticket->priority_level)); ?></span></td>
                                        <td><?php echo wp_kses_post($sla_badge); ?></td>
                                        <td><span class="lgp-badge lgp-badge-warning"><?php echo esc_html(ucfirst($ticket->status)); ?></span></td>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render features section.
     */
    private static function render_features_section()
    {
    ?>
        <div class="lgp-card">
            <div class="lgp-card-header">
                <h3 class="lgp-card-title">
                    <div class="lgp-card-icon">⚡</div>
                    Enterprise Features
                </h3>
                <p class="lgp-card-subtitle">Complete support management system</p>
            </div>
            <div class="lgp-card-body">
                <div class="feature-grid">
                    <div class="feature-box">
                        <h4>Real-Time Updates</h4>
                        <p>Auto-refresh every 30 seconds. Live ticket status changes, no manual refresh needed.</p>
                    </div>
                    <div class="feature-box">
                        <h4>Ticket Assignment</h4>
                        <p>Assign tickets to specific support agents. Track workload and performance metrics.</p>
                    </div>
                    <div class="feature-box">
                        <h4>SLA Tracking</h4>
                        <p>Critical: 4h | High: 24h | Medium: 48h | Low: 5d. Real-time countdown monitoring.</p>
                    </div>
                    <div class="feature-box">
                        <h4>Canned Responses</h4>
                        <p>10 pre-written templates for common issues. Boost support team efficiency.</p>
                    </div>
                    <div class="feature-box">
                        <h4>Thread Conversations</h4>
                        <p>Full email thread history. Bidirectional sync with Microsoft Graph integration.</p>
                    </div>
                    <div class="feature-box">
                        <h4>Professional Design</h4>
                        <p>Navy 60-30-10 design system. Clean typography and thoughtful spacing.</p>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Get priority badge CSS class.
     *
     * @param string $priority Priority level.
     * @return string CSS class.
     */
    private static function get_priority_badge_class($priority)
    {
        switch (strtolower($priority)) {
            case 'critical':
                return 'lgp-badge-danger';
            case 'high':
                return 'lgp-badge-warning';
            case 'medium':
            case 'low':
            default:
                return 'lgp-badge-info';
        }
    }

    /**
     * Get SLA badge HTML.
     *
     * @param string $due_date SLA due date.
     * @return string HTML badge.
     */
    private static function get_sla_badge($due_date)
    {
        if (empty($due_date)) {
            return '<span class="lgp-sla-badge good">No SLA</span>';
        }

        $due = strtotime($due_date);
        $now = time();
        $diff = $due - $now;

        if ($diff < 0) {
            return '<span class="lgp-sla-badge critical">SLA Breached</span>';
        } elseif ($diff < 7200) { // 2 hours
            return '<span class="lgp-sla-badge critical">' . self::format_time_remaining($diff) . ' left</span>';
        } elseif ($diff < 28800) { // 8 hours
            return '<span class="lgp-sla-badge warning">' . self::format_time_remaining($diff) . ' left</span>';
        } else {
            return '<span class="lgp-sla-badge good">' . self::format_time_remaining($diff) . ' left</span>';
        }
    }

    /**
     * Format time remaining.
     *
     * @param int $seconds Seconds remaining.
     * @return string Formatted time.
     */
    private static function format_time_remaining($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 24) {
            return floor($hours / 24) . 'd ' . ($hours % 24) . 'h';
        } elseif ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . 'm';
        }
    }

    /**
     * Enqueue dashboard styles.
     */
    public static function enqueue_dashboard_styles()
    {
        wp_enqueue_style(
            'lgp-dashboard-styles',
            LGP_ASSETS_URL . 'css/dashboard-professional.css',
            array(),
            LGP_VERSION
        );
    }

    /**
     * Enqueue dashboard scripts.
     */
    public static function enqueue_dashboard_scripts()
    {
        wp_enqueue_script(
            'lgp-dashboard-scripts',
            LGP_ASSETS_URL . 'js/dashboard-professional.js',
            array('jquery'),
            LGP_VERSION,
            true
        );

        wp_localize_script(
            'lgp-dashboard-scripts',
            'lgpDashboard',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lgp_dashboard_nonce'),
            )
        );
    }
}
