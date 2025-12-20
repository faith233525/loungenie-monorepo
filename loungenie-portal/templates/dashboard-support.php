<?php

/**
 * Support Dashboard Template
 * Shows system-wide statistics and alerts for support users
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Get color hex code for LounGenie color tags
 *
 * @param string $color_name Color name
 * @return string Hex color code
 */
if (! function_exists('lgp_get_color_hex')) {
	/**
	 * Map color names to hex values for dashboard display.
	 *
	 * @param string $color_name Color label.
	 * @return string Hex code fallback to soft background.
	 */
	function lgp_get_color_hex($color_name)
	{
		$color_map   = array(
			'yellow'       => '#D8EFF3',
			'red'          => '#DCFCE7',
			'classic blue' => '#CCF8F1',
			'ice blue'     => '#D6F6FC',
		);
		$color_lower = strtolower(trim($color_name));
		return isset($color_map[$color_lower]) ? $color_map[$color_lower] : '#E9F8F9';
	}
}

global $wpdb;

// Fetch statistics (tables are trusted internal names)
$lgp_companies_table        = $wpdb->prefix . 'lgp_companies';
$lgp_units_table            = $wpdb->prefix . 'lgp_units';
$lgp_tickets_table          = $wpdb->prefix . 'lgp_tickets';
$lgp_service_requests_table = $wpdb->prefix . 'lgp_service_requests';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$total_companies = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$lgp_companies_table}");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$total_units     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$lgp_units_table}");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$active_installs = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$lgp_service_requests_table} WHERE request_type = %s AND status = %s", 'install', 'active'));
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$open_tickets    = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$lgp_tickets_table} WHERE status = %s", 'open'));

// Recent tickets
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- table names are trusted internal
$recent_tickets = $wpdb->get_results(
	"SELECT t.*, sr.request_type, c.name as company_name
	FROM {$lgp_tickets_table} t
	LEFT JOIN {$lgp_service_requests_table} sr ON t.service_request_id = sr.id
	LEFT JOIN {$lgp_companies_table} c ON sr.company_id = c.id
	ORDER BY t.created_at DESC
	LIMIT 10"
);

// Top 5 Metrics - Most used colors
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- table names are trusted internal
$top_colors = $wpdb->get_results(
	"SELECT color_tag, COUNT(*) as count
	FROM {$lgp_units_table}
	WHERE color_tag IS NOT NULL AND color_tag != ''
	GROUP BY color_tag
	ORDER BY count DESC
	LIMIT 5"
);

// Top 5 Metrics - Most used lock brands
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- table names are trusted internal
$top_lock_brands = $wpdb->get_results(
	"SELECT lock_brand, COUNT(*) as count
	FROM {$lgp_units_table}
	WHERE lock_brand IS NOT NULL AND lock_brand != ''
	GROUP BY lock_brand
	ORDER BY count DESC
	LIMIT 5"
);

// Top 5 Metrics - Venue types
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- table names are trusted internal
$top_venues = $wpdb->get_results(
	"SELECT venue_type, COUNT(*) as count
	FROM {$lgp_units_table}
	WHERE venue_type IS NOT NULL AND venue_type != ''
	GROUP BY venue_type
	ORDER BY count DESC
	LIMIT 5"
);

// Season breakdown
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$seasonal_units  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$lgp_units_table} WHERE season = %s", 'seasonal'));
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are trusted internal
$yearround_units = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$lgp_units_table} WHERE season = %s", 'year-round'));

?>

<div class="lgp-dashboard-container">

	<div class="lgp-dashboard-header">
		<h1><?php esc_html_e('Support Dashboard', 'loungenie-portal'); ?></h1>
		<p><?php esc_html_e('Overview of all companies, units, and support tickets', 'loungenie-portal'); ?></p>
		<p class="lgp-dashboard-subbrand">MyPOOLSAFE</p>
	</div>

	<!-- Welcome Banner -->
	<div class="lgp-support-welcome" role="note">
		<h3 class="lgp-support-welcome-title"><?php esc_html_e('Welcome to the LounGenie Support Team Portal!', 'loungenie-portal'); ?></h3>
		<p class="lgp-support-welcome-body"><?php esc_html_e('Track partner company updates, service requests, and units all in one place. Let\'s keep everything running smoothly!', 'loungenie-portal'); ?></p>
	</div>

	<!-- Orientation Card -->
	<div class="lgp-orientation-card" aria-label="<?php esc_attr_e('Dashboard Orientation', 'loungenie-portal'); ?>">
		<div class="lgp-orientation-left">
			<div class="lgp-orientation-title">
				<?php esc_html_e('Support Team Operations', 'loungenie-portal'); ?>
			</div>
			<?php if (! empty($support_team_location)) : ?>
				<div class="lgp-orientation-subtitle">
					<?php echo esc_html($support_team_location); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="lgp-orientation-right">
			<div class="lgp-orientation-metric-group">
				<div class="lgp-orientation-metric-item">
					<p class="lgp-orientation-metric">
						<?php echo esc_html(! empty($total_units) ? $total_units : '0'); ?>
					</p>
					<p class="lgp-orientation-metric-label">
						<?php esc_html_e('Total Units', 'loungenie-portal'); ?>
					</p>
				</div>
				<div class="lgp-orientation-metric-item">
					<p class="lgp-orientation-metric-secondary-number">
						<?php echo esc_html($open_tickets ?: '0'); ?>
					</p>
					<p class="lgp-orientation-metric-label">
						<?php esc_html_e('Active Tickets', 'loungenie-portal'); ?>
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Stats Overview -->
	<div class="lgp-card-grid lgp-mt-4">
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html(! empty($total_companies) ? $total_companies : '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Total Companies', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-tower-cell" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html(! empty($total_units) ? $total_units : '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Active Units', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html(! empty($open_tickets) ? $open_tickets : '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-wrench" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html(! empty($active_installs) ? $active_installs : '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Active Installs', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Welcome Card -->
	<div class="lgp-welcome-card lgp-welcome-card-hidden">
		<div class="lgp-welcome-greeting">
			<?php
			$hour = (int) gmdate('H');
			if ($hour < 12) {
				esc_html_e('Good morning!', 'loungenie-portal');
			} elseif ($hour < 18) {
				esc_html_e('Good afternoon!', 'loungenie-portal');
			} else {
				esc_html_e('Good evening!', 'loungenie-portal');
			}
			?>
		</div>
		<div class="lgp-welcome-user">
			<?php
			$current_user = wp_get_current_user();
			// Translators: %s is the user's display name
			printf(esc_html__('Welcome back, %s', 'loungenie-portal'), esc_html($current_user->display_name));
			?>
		</div>

		<div class="lgp-welcome-context">
			<div class="lgp-welcome-context-icon">
				<i class="fa-solid fa-headset lgp-icon-default" aria-hidden="true"></i>
			</div>
			<div class="lgp-welcome-context-text">
				<h3><?php esc_html_e('Support Operations', 'loungenie-portal'); ?></h3>
				<p>
					<?php
					// Translators: %d is the number of companies
					printf(esc_html__('Managing %d companies', 'loungenie-portal'), esc_html($total_companies));
					?>
				</p>
			</div>
		</div>

		<div class="lgp-welcome-actions">
			<a href="<?php echo esc_url(home_url('/portal/tickets')); ?>" class="lgp-btn lgp-btn-primary">
				<i class="fa-solid fa-ticket lgp-icon-action" aria-hidden="true"></i>
				<?php
				if ($open_tickets > 0) {
					// Translators: %d is the number of open tickets
					printf(esc_html__('View Tickets (%d)', 'loungenie-portal'), esc_html($open_tickets));
				} else {
					esc_html_e('View Tickets', 'loungenie-portal');
				}
				?>
			</a>
			<a href="<?php echo esc_url(home_url('/portal/companies')); ?>" class="lgp-btn lgp-btn-secondary">
				<i class="fa-solid fa-building lgp-icon-action" aria-hidden="true"></i>
				<?php esc_html_e('Manage Companies', 'loungenie-portal'); ?>
			</a>
		</div>

		<div class="lgp-welcome-tip">
			<strong><?php esc_html_e('System Status:', 'loungenie-portal'); ?></strong>
			<?php
			if ($open_tickets > 5) {
				esc_html_e(' Moderate ticket volume. Review priority items.', 'loungenie-portal');
			} elseif ($open_tickets > 0) {
				esc_html_e(' Light ticket volume. All systems nominal.', 'loungenie-portal');
			} else {
				esc_html_e(' No open tickets. Excellent!', 'loungenie-portal');
			}
			?>
		</div>
	</div>

	<!-- KPI Statistics Cards -->
	<div class="lgp-kpi-grid">
		<div class="lgp-kpi-card">
			<div class="lgp-kpi-card-icon">
				<i class="fa-solid fa-building lgp-kpi-icon-color"></i>
			</div>
			<p class="lgp-kpi-value"><?php echo esc_html(! empty($total_companies) ? $total_companies : '0'); ?></p>
			<p class="lgp-kpi-label"><?php esc_html_e('Total Companies', 'loungenie-portal'); ?></p>
		</div>
		<div class="lgp-kpi-card">
			<div class="lgp-kpi-card-icon">
				<i class="fa-solid fa-tower-cell lgp-kpi-icon-color"></i>
			</div>
			<p class="lgp-kpi-value"><?php echo esc_html(! empty($total_units) ? $total_units : '0'); ?></p>
			<p class="lgp-kpi-label"><?php esc_html_e('Total Units', 'loungenie-portal'); ?></p>
		</div>
		<div class="lgp-kpi-card">
			<div class="lgp-kpi-card-icon">
				<i class="fa-solid fa-ticket lgp-kpi-icon-color"></i>
			</div>
			<p class="lgp-kpi-value"><?php echo esc_html(! empty($open_tickets) ? $open_tickets : '0'); ?></p>
			<p class="lgp-kpi-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></p>
		</div>
	</div>

	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Company Map', 'loungenie-portal'); ?></h2>
			<p class="lgp-card-subtitle"><?php esc_html_e('Support-only view powered by OpenStreetMap', 'loungenie-portal'); ?></p>
		</div>
		<div class="lgp-card-body">
			<div id="lgp-company-map" class="lgp-h-480"></div>
		</div>
	</div>

	<!-- Statistics Grid (Legacy) -->
	<div class="lgp-stats-grid">
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e('Total Companies', 'loungenie-portal'); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html(! empty($total_companies) ? $total_companies : '0'); ?></div>
		</div>

		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e('Total Units', 'loungenie-portal'); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html(! empty($total_units) ? $total_units : '0'); ?></div>
		</div>

		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e('Active Installs', 'loungenie-portal'); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html(! empty($active_installs) ? $active_installs : '0'); ?></div>
		</div>

		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html(! empty($open_tickets) ? $open_tickets : '0'); ?></div>
		</div>
	</div>

	<!-- Top 5 Metrics -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Top 5 Analytics', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<div class="lgp-top-metrics-grid">
				<!-- Top Colors -->
				<div class="lgp-top-metric">
					<h3 class="lgp-metric-title"><?php esc_html_e('Top Colors', 'loungenie-portal'); ?></h3>
					<?php if (! empty($top_colors)) : ?>
						<ul class="lgp-metric-list">
							<?php
							foreach ($top_colors as $color) :
								$color_hex = lgp_get_color_hex($color->color_tag);
							?>
								<li class="lgp-metric-item">
									<span class="lgp-metric-label">
										<span class="lgp-color-indicator" style="--lgp-color-value: <?php echo esc_attr($color_hex); ?>;"></span>
										<?php echo esc_html($color->color_tag); ?>
									</span>
									<span class="lgp-metric-value"><?php echo esc_html($color->count); ?> <?php esc_html_e('units', 'loungenie-portal'); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="lgp-empty-state"><?php esc_html_e('No data available', 'loungenie-portal'); ?></p>
					<?php endif; ?>
				</div>

				<!-- Top Lock Brands -->
				<div class="lgp-top-metric">
					<h3 class="lgp-metric-title"><?php esc_html_e('Top Lock Brands', 'loungenie-portal'); ?></h3>
					<?php if (! empty($top_lock_brands)) : ?>
						<ul class="lgp-metric-list">
							<?php foreach ($top_lock_brands as $brand) : ?>
								<li class="lgp-metric-item">
									<span class="lgp-metric-label"><?php echo esc_html($brand->lock_brand); ?></span>
									<span class="lgp-metric-value"><?php echo esc_html($brand->count); ?> <?php esc_html_e('units', 'loungenie-portal'); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="lgp-empty-state"><?php esc_html_e('No data available', 'loungenie-portal'); ?></p>
					<?php endif; ?>
				</div>

				<!-- Top Venues -->
				<div class="lgp-top-metric">
					<h3 class="lgp-metric-title"><?php esc_html_e('Top Venues', 'loungenie-portal'); ?></h3>
					<?php if (! empty($top_venues)) : ?>
						<ul class="lgp-metric-list">
							<?php foreach ($top_venues as $venue) : ?>
								<li class="lgp-metric-item">
									<span class="lgp-metric-label"><?php echo esc_html($venue->venue_type); ?></span>
									<span class="lgp-metric-value"><?php echo esc_html($venue->count); ?> <?php esc_html_e('units', 'loungenie-portal'); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="lgp-empty-state"><?php esc_html_e('No data available', 'loungenie-portal'); ?></p>
					<?php endif; ?>
				</div>

				<!-- Season Breakdown -->
				<div class="lgp-top-metric">
					<h3 class="lgp-metric-title"><?php esc_html_e('Season Breakdown', 'loungenie-portal'); ?></h3>
					<ul class="lgp-metric-list">
						<li class="lgp-metric-item">
							<span class="lgp-metric-label"><?php esc_html_e('Seasonal', 'loungenie-portal'); ?></span>
							<span class="lgp-metric-value"><?php echo esc_html(! empty($seasonal_units) ? $seasonal_units : '0'); ?> <?php esc_html_e('units', 'loungenie-portal'); ?></span>
						</li>
						<li class="lgp-metric-item">
							<span class="lgp-metric-label"><?php esc_html_e('Year-Round', 'loungenie-portal'); ?></span>
							<span class="lgp-metric-value"><?php echo esc_html(! empty($yearround_units) ? $yearround_units : '0'); ?> <?php esc_html_e('units', 'loungenie-portal'); ?></span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>


	<!-- Create New Ticket Section -->
	<div class="lgp-card lgp-ticket-creation-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Create Support Ticket', 'loungenie-portal'); ?></h2>
			<p class="lgp-card-subtitle"><?php esc_html_e('Or upload attachments to an existing ticket', 'loungenie-portal'); ?></p>
		</div>
		<div class="lgp-card-body">
			<?php
			$lgp_admin_ajax = function_exists('admin_url') ? admin_url('admin-ajax.php') : '/wp-admin/admin-ajax.php';
			?>
			<form class="lgp-ticket-form" method="POST" action="<?php echo esc_url($lgp_admin_ajax); ?>">
				<input type="hidden" name="action" value="lgp_create_ticket">
				<?php
				if (function_exists('wp_nonce_field')) {
					wp_nonce_field('lgp_create_ticket_nonce');
				} else {
				?>
					<input type="hidden" name="_wpnonce" value="lgp_create_ticket_nonce" />
				<?php } ?>

				<div class="lgp-form-group">
					<label class="lgp-form-label" for="ticket_subject"><?php esc_html_e('Subject', 'loungenie-portal'); ?></label>
					<input type="text" id="ticket_subject" name="subject" class="lgp-form-input" placeholder="<?php esc_attr_e('Brief description of the issue', 'loungenie-portal'); ?>" required>
				</div>

				<div class="lgp-form-group">
					<label class="lgp-form-label" for="ticket_priority"><?php esc_html_e('Priority', 'loungenie-portal'); ?></label>
					<select id="ticket_priority" name="priority" class="lgp-form-select">
						<option value="low"><?php esc_html_e('Low', 'loungenie-portal'); ?></option>
						<option value="medium" selected><?php esc_html_e('Medium', 'loungenie-portal'); ?></option>
						<option value="high"><?php esc_html_e('High', 'loungenie-portal'); ?></option>
						<option value="critical"><?php esc_html_e('Critical', 'loungenie-portal'); ?></option>
					</select>
				</div>

				<div class="lgp-form-group">
					<label class="lgp-form-label" for="ticket_description"><?php esc_html_e('Description', 'loungenie-portal'); ?></label>
					<textarea id="ticket_description" name="description" class="lgp-form-textarea" placeholder="<?php esc_attr_e('Detailed information about the issue', 'loungenie-portal'); ?>" required></textarea>
				</div>

				<div class="lgp-form-group">
					<label class="lgp-form-label"><?php esc_html_e('Attachments', 'loungenie-portal'); ?></label>
					<div class="lgp-attachment-zone">
						<div class="lgp-attachment-zone-icon">📎</div>
						<div class="lgp-attachment-zone-text">
							<?php esc_html_e('Drag and drop files here or click to select', 'loungenie-portal'); ?>
						</div>
						<div class="lgp-attachment-zone-hint">
							<?php esc_html_e('Supported: PDF, DOC, XLS, PPT, TXT, CSV, JPG, PNG, GIF, ZIP (Max 10 MB)', 'loungenie-portal'); ?>
						</div>
						<input type="file" class="lgp-attachment-input" multiple>
					</div>
					<ul class="lgp-attachment-list"></ul>
				</div>

				<div class="lgp-flex-center lgp-form-buttons">
					<button type="submit" class="lgp-btn lgp-btn-primary">
						<?php esc_html_e('Create Ticket', 'loungenie-portal'); ?>
					</button>
					<button type="reset" class="lgp-btn lgp-btn-secondary">
						<?php esc_html_e('Clear', 'loungenie-portal'); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Recent Tickets -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Recent Tickets', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<?php if (! empty($recent_tickets)) : ?>
				<div class="lgp-table-container">
					<table class="lgp-table" id="tickets-table">
						<thead>
							<tr>
								<th class="sortable"><?php esc_html_e('Ticket ID', 'loungenie-portal'); ?></th>
								<th class="sortable"><?php esc_html_e('Company', 'loungenie-portal'); ?></th>
								<th class="sortable"><?php esc_html_e('Request Type', 'loungenie-portal'); ?></th>
								<th class="sortable"><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
								<th class="sortable"><?php esc_html_e('Created', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Actions', 'loungenie-portal'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($recent_tickets as $ticket) : ?>
								<tr>
									<td>#<?php echo esc_html($ticket->id); ?></td>
									<td><?php echo esc_html($ticket->company_name ?? __('N/A', 'loungenie-portal')); ?></td>
									<td><?php echo esc_html(ucfirst($ticket->request_type ?? 'general')); ?></td>
									<td>
										<?php
										$status_class = 'info';
										if ($ticket->status === 'open') {
											$status_class = 'warning';
										} elseif ($ticket->status === 'closed') {
											$status_class = 'success';
										}
										?>
										<span class="lgp-badge lgp-badge-<?php echo esc_attr($status_class); ?>">
											<?php echo esc_html(ucfirst($ticket->status)); ?>
										</span>
									</td>
									<td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket->created_at))); ?></td>
									<td>
										<a href="#" class="lgp-btn lgp-btn-primary"><?php esc_html_e('View', 'loungenie-portal'); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p><?php esc_html_e('No tickets found.', 'loungenie-portal'); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- System Alerts -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('System Alerts', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<div class="lgp-alert lgp-alert-info">
				<p><?php esc_html_e('All systems operational', 'loungenie-portal'); ?></p>
			</div>
		</div>
	</div>
</div><!-- .lgp-dashboard-container -->