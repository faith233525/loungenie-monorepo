<?php

/**
 * Admin Dashboard Widget
 * Displays Portal Health and Quick Stats in WordPress Dashboard
 *
 * @package LounGenie_Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Admin Dashboard Widget Class
 */
class LGP_Admin_Dashboard_Widget
{

	/**
	 * Initialize dashboard widget
	 */
	public static function init()
	{
		add_action('wp_dashboard_setup', array(__CLASS__, 'register_widget'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_widget_styles'));
	}

	/**
	 * Register dashboard widget
	 */
	public static function register_widget()
	{
		// Only show to users who can manage portal
		if (! current_user_can('manage_options')) {
			return;
		}

		wp_add_dashboard_widget(
			'lgp_portal_health_widget',
			__('LounGenie Portal Health', 'loungenie-portal'),
			array(__CLASS__, 'render_widget')
		);
	}

	/**
	 * Enqueue widget styles
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_widget_styles($hook)
	{
		if ('index.php' !== $hook) {
			return;
		}

		wp_add_inline_style(
			'dashboard',
			'
			#lgp_portal_health_widget .inside { padding: 0; margin: 0; }
			.lgp-widget-container { padding: 12px; }
			.lgp-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
			.lgp-stat-card { background: #f0f0f1; padding: 16px; border-radius: 4px; text-align: center; }
			.lgp-stat-value { font-size: 32px; font-weight: 600; color: #2271b1; display: block; margin-bottom: 4px; }
			.lgp-stat-label { font-size: 13px; color: #646970; }
			.lgp-health-indicators { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
			.lgp-indicator { display: flex; align-items: center; gap: 6px; font-size: 13px; padding: 6px 12px; background: #f0f0f1; border-radius: 4px; }
			.lgp-indicator-dot { width: 8px; height: 8px; border-radius: 50%; }
			.lgp-indicator-dot.green { background: #00a32a; }
			.lgp-indicator-dot.yellow { background: #dba617; }
			.lgp-indicator-dot.red { background: #d63638; }
			.lgp-widget-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #dcdcde; }
			.lgp-widget-actions .button { flex: 1; justify-content: center; }
			.lgp-performance-bar { height: 8px; background: #f0f0f1; border-radius: 4px; overflow: hidden; margin: 8px 0; }
			.lgp-performance-fill { height: 100%; background: linear-gradient(90deg, #00a32a 0%, #dba617 70%, #d63638 100%); transition: width 0.3s ease; }
			.lgp-widget-footer { font-size: 12px; color: #646970; padding: 8px 12px; background: #f6f7f7; border-top: 1px solid #dcdcde; text-align: center; }
			'
		);
	}

	/**
	 * Render dashboard widget
	 */
	public static function render_widget()
	{
		global $wpdb;

		// Get portal statistics
		$stats = self::get_portal_stats();

		// Get health indicators
		$health = self::get_health_indicators();

?>
		<div class="lgp-widget-container">
			<!-- Quick Stats -->
			<div class="lgp-stats-grid">
				<div class="lgp-stat-card">
					<span class="lgp-stat-value"><?php echo esc_html(number_format($stats['total_tickets'])); ?></span>
					<span class="lgp-stat-label"><?php esc_html_e('Total Tickets', 'loungenie-portal'); ?></span>
				</div>
				<div class="lgp-stat-card">
					<span class="lgp-stat-value"><?php echo esc_html(number_format($stats['open_tickets'])); ?></span>
					<span class="lgp-stat-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></span>
				</div>
				<div class="lgp-stat-card">
					<span class="lgp-stat-value"><?php echo esc_html(number_format($stats['total_units'])); ?></span>
					<span class="lgp-stat-label"><?php esc_html_e('Total Units', 'loungenie-portal'); ?></span>
				</div>
				<div class="lgp-stat-card">
					<span class="lgp-stat-value"><?php echo esc_html(number_format($stats['total_companies'])); ?></span>
					<span class="lgp-stat-label"><?php esc_html_e('Companies', 'loungenie-portal'); ?></span>
				</div>
			</div>

			<!-- Health Indicators -->
			<div class="lgp-health-indicators">
				<?php foreach ($health as $indicator) : ?>
					<div class="lgp-indicator">
						<span class="lgp-indicator-dot <?php echo esc_attr($indicator['status']); ?>"></span>
						<span><?php echo esc_html($indicator['label']); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Performance Indicator -->
			<div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
					<span style="font-size: 13px; color: #646970;">
						<?php esc_html_e('System Performance', 'loungenie-portal'); ?>
					</span>
					<span style="font-size: 12px; color: #646970;">
						<?php echo esc_html($stats['performance_score']); ?>%
					</span>
				</div>
				<div class="lgp-performance-bar">
					<div class="lgp-performance-fill" style="width: <?php echo esc_attr($stats['performance_score']); ?>%;"></div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="lgp-widget-actions">
				<a href="<?php echo esc_url(admin_url('admin.php?page=lgp-dashboard')); ?>" class="button button-primary">
					<?php esc_html_e('View Portal', 'loungenie-portal'); ?>
				</a>
				<a href="<?php echo esc_url(admin_url('tools.php?page=lgp-system-health')); ?>" class="button">
					<?php esc_html_e('System Health', 'loungenie-portal'); ?>
				</a>
				<a href="<?php echo esc_url(admin_url('options-general.php?page=lgp-settings')); ?>" class="button">
					<?php esc_html_e('Settings', 'loungenie-portal'); ?>
				</a>
			</div>
		</div>

		<div class="lgp-widget-footer">
			<?php
			/* translators: %s: Version number */
			printf(esc_html__('LounGenie Portal v%s', 'loungenie-portal'), esc_html(LGP_VERSION));
			?>
			|
			<a href="<?php echo esc_url(home_url('/portal/')); ?>" target="_blank">
				<?php esc_html_e('Visit Portal', 'loungenie-portal'); ?>
			</a>
		</div>
<?php
	}

	/**
	 * Get portal statistics
	 *
	 * @return array Statistics array.
	 */
	private static function get_portal_stats()
	{
		global $wpdb;

		// Get cached stats (5 minute cache)
		$cache_key = 'lgp_dashboard_widget_stats';
		$stats     = get_transient($cache_key);

		if (false !== $stats) {
			return $stats;
		}

		$tickets_table   = $wpdb->prefix . 'lgp_tickets';
		$units_table     = $wpdb->prefix . 'lgp_units';
		$companies_table = $wpdb->prefix . 'lgp_companies';

		$stats = array(
			'total_tickets'     => 0,
			'open_tickets'      => 0,
			'total_units'       => 0,
			'total_companies'   => 0,
			'performance_score' => 100,
		);

		// Get ticket counts
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tickets_table)) === $tickets_table) {
			$stats['total_tickets'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tickets_table}");
			$stats['open_tickets']  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tickets_table} WHERE status IN (%s, %s)",
					'open',
					'pending'
				)
			);
		}

		// Get unit count
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $units_table)) === $units_table) {
			$stats['total_units'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$units_table}");
		}

		// Get company count
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $companies_table)) === $companies_table) {
			$stats['total_companies'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$companies_table}");
		}

		// Calculate performance score (simple heuristic)
		$memory_limit       = ini_get('memory_limit');
		$memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
		$memory_usage       = memory_get_usage(true);
		$memory_percentage  = ($memory_usage / $memory_limit_bytes) * 100;

		$stats['performance_score'] = max(0, min(100, 100 - ($memory_percentage / 2)));

		// Cache for 5 minutes
		set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);

		return $stats;
	}

	/**
	 * Get health indicators
	 *
	 * @return array Health indicators array.
	 */
	private static function get_health_indicators()
	{
		$indicators = array();

		// Database connection
		global $wpdb;
		$db_status              = $wpdb->check_connection(false) ? 'green' : 'red';
		$indicators['database'] = array(
			'label'  => __('Database', 'loungenie-portal'),
			'status' => $db_status,
		);

		// Memory status
		$memory_limit         = ini_get('memory_limit');
		$memory_limit_mb      = (int) $memory_limit;
		$memory_status        = $memory_limit_mb >= 128 ? 'green' : ($memory_limit_mb >= 64 ? 'yellow' : 'red');
		$indicators['memory'] = array(
			'label'  => sprintf(__('Memory: %s', 'loungenie-portal'), $memory_limit),
			'status' => $memory_status,
		);

		// Cache status
		$cache_working       = wp_cache_get('test') !== false || wp_cache_set('test', 'test', '', 60);
		$indicators['cache'] = array(
			'label'  => __('Cache', 'loungenie-portal'),
			'status' => $cache_working ? 'green' : 'yellow',
		);

		// PHP version
		$php_version       = PHP_VERSION;
		$php_status        = version_compare($php_version, '8.0', '>=') ? 'green' : (version_compare($php_version, '7.4', '>=') ? 'yellow' : 'red');
		$indicators['php'] = array(
			'label'  => sprintf(__('PHP %s', 'loungenie-portal'), PHP_VERSION),
			'status' => $php_status,
		);

		return $indicators;
	}
}

// Initialize
LGP_Admin_Dashboard_Widget::init();
