<?php

/**
 * Units View Template
 * Displays all units with comprehensive filtering options
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

$units_table     = $wpdb->prefix . 'lgp_units';
$companies_table = $wpdb->prefix . 'lgp_companies';

// Guard: ensure units table exists; attempt schema creation if missing.
$units_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $units_table));
if (strtolower((string) $units_table_exists) !== strtolower($units_table)) {
	if (class_exists('LGP_Database')) {
		LGP_Database::create_tables();
		$units_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $units_table));
	}
}

// Check user role for data filtering
$is_support = LGP_Auth::is_support();
$company_id = LGP_Auth::get_user_company_id();

// Build base query
$where_clauses = array('1=1');

if (! $is_support && $company_id) {
	$where_clauses[] = $wpdb->prepare('u.company_id = %d', $company_id);
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch units only if table exists; otherwise show empty list
if (strtolower((string) $units_table_exists) === strtolower($units_table)) {
	$units = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.*, c.name as company_name 
			FROM {$units_table} u 
			LEFT JOIN {$companies_table} c ON u.company_id = c.id 
			WHERE {$where_sql}
			ORDER BY u.created_at DESC 
			LIMIT %d",
			100
		)
	);
} else {
	$units = array();
}

?>

<div class="lgp-dashboard-header">
	<h1><?php esc_html_e('LounGenie Units', 'loungenie-portal'); ?></h1>
	<p><?php esc_html_e('View and filter all LounGenie units', 'loungenie-portal'); ?></p>
</div>

<!-- Advanced Filters -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e('Filters', 'loungenie-portal'); ?></h2>
		<button type="button" class="lgp-btn lgp-btn-secondary" id="lgp-clear-filters">
			<?php esc_html_e('Clear All Filters', 'loungenie-portal'); ?>
		</button>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-filters lgp-advanced-filters">
			<!-- Color Filter -->
			<div class="lgp-filter-group">
				<label for="filter-color" class="lgp-label"><?php esc_html_e('Color', 'loungenie-portal'); ?></label>
				<select id="filter-color" class="lgp-select lgp-table-filter" data-filter="color">
					<option value=""><?php esc_html_e('All Colors', 'loungenie-portal'); ?></option>
					<option value="Yellow"><?php esc_html_e('Yellow', 'loungenie-portal'); ?></option>
					<option value="Red"><?php esc_html_e('Red', 'loungenie-portal'); ?></option>
					<option value="Classic Blue"><?php esc_html_e('Classic Blue', 'loungenie-portal'); ?></option>
					<option value="Ice Blue"><?php esc_html_e('Ice Blue', 'loungenie-portal'); ?></option>
				</select>
			</div>

			<!-- Season Filter -->
			<div class="lgp-filter-group">
				<label for="filter-season" class="lgp-label"><?php esc_html_e('Season', 'loungenie-portal'); ?></label>
				<select id="filter-season" class="lgp-select lgp-table-filter" data-filter="season">
					<option value=""><?php esc_html_e('All Seasons', 'loungenie-portal'); ?></option>
					<option value="seasonal"><?php esc_html_e('Seasonal', 'loungenie-portal'); ?></option>
					<option value="year-round"><?php esc_html_e('Year-Round', 'loungenie-portal'); ?></option>
				</select>
			</div>

			<!-- Venue Filter -->
			<div class="lgp-filter-group">
				<label for="filter-venue" class="lgp-label"><?php esc_html_e('Venue Type', 'loungenie-portal'); ?></label>
				<select id="filter-venue" class="lgp-select lgp-table-filter" data-filter="venue">
					<option value=""><?php esc_html_e('All Venues', 'loungenie-portal'); ?></option>
					<option value="Hotel"><?php esc_html_e('Hotel', 'loungenie-portal'); ?></option>
					<option value="Resort"><?php esc_html_e('Resort', 'loungenie-portal'); ?></option>
					<option value="Waterpark"><?php esc_html_e('Waterpark', 'loungenie-portal'); ?></option>
					<option value="Surf Park"><?php esc_html_e('Surf Park', 'loungenie-portal'); ?></option>
					<option value="Others"><?php esc_html_e('Others', 'loungenie-portal'); ?></option>
				</select>
			</div>

			<!-- Lock Brand Filter -->
			<div class="lgp-filter-group">
				<label for="filter-lock-brand" class="lgp-label"><?php esc_html_e('Lock Brand', 'loungenie-portal'); ?></label>
				<select id="filter-lock-brand" class="lgp-select lgp-table-filter" data-filter="lock-brand">
					<option value=""><?php esc_html_e('All Brands', 'loungenie-portal'); ?></option>
					<option value="MAKE"><?php esc_html_e('MAKE', 'loungenie-portal'); ?></option>
					<option value="L&F"><?php esc_html_e('L&F', 'loungenie-portal'); ?></option>
				</select>
			</div>

			<!-- Status Filter -->
			<div class="lgp-filter-group">
				<label for="filter-status" class="lgp-label"><?php esc_html_e('Status', 'loungenie-portal'); ?></label>
				<select id="filter-status" class="lgp-select lgp-table-filter" data-filter="status">
					<option value=""><?php esc_html_e('All Statuses', 'loungenie-portal'); ?></option>
					<option value="active"><?php esc_html_e('Active', 'loungenie-portal'); ?></option>
					<option value="install"><?php esc_html_e('Installation', 'loungenie-portal'); ?></option>
					<option value="service"><?php esc_html_e('Service', 'loungenie-portal'); ?></option>
				</select>
			</div>

			<!-- Search -->
			<div class="lgp-search-box">
				<label for="units-search" class="lgp-label"><?php esc_html_e('Search', 'loungenie-portal'); ?></label>
				<input type="text" id="units-search" class="lgp-input lgp-search-input" placeholder="<?php esc_attr_e('Search units...', 'loungenie-portal'); ?>" data-table="units-table">
			</div>
		</div>

		<!-- Active Filters Display -->
		<div id="active-filters" class="lgp-active-filters lgp-hidden">
			<strong><?php esc_html_e('Active Filters:', 'loungenie-portal'); ?></strong>
			<div id="active-filters-list" class="lgp-filter-tags"></div>
		</div>
	</div>
</div>

<!-- Units Table with Export -->
<div class="lgp-card">
	<div class="lgp-card-header flex justify-between items-center">
		<h2 class="lgp-card-title"><?php esc_html_e('Units List', 'loungenie-portal'); ?></h2>
		<button type="button" class="lgp-btn lgp-btn-primary" id="lgp-export-units">
			📥 <?php esc_html_e('Export to CSV', 'loungenie-portal'); ?>
		</button>
	</div>
	<div class="lgp-card-body">
		<!-- Loading Spinner -->
		<div id="lgp-loading-spinner" class="lgp-loading-spinner lgp-hidden">
			<div class="lgp-spinner"></div>
			<p><?php esc_html_e('Loading...', 'loungenie-portal'); ?></p>
		</div>

		<?php if (! empty($units)) : ?>
			<div class="lgp-table-container">
				<table class="lgp-table" id="units-table">
					<thead>
						<tr>
							<th class="sortable"><?php esc_html_e('Unit ID', 'loungenie-portal'); ?></th>
							<th class="sortable"><?php esc_html_e('Company', 'loungenie-portal'); ?></th>
							<th class="sortable" data-sort="color"><?php esc_html_e('Color', 'loungenie-portal'); ?></th>
							<th class="sortable" data-sort="season"><?php esc_html_e('Season', 'loungenie-portal'); ?></th>
							<th class="sortable" data-sort="venue"><?php esc_html_e('Venue', 'loungenie-portal'); ?></th>
							<th class="sortable" data-sort="lock-brand"><?php esc_html_e('Lock Brand', 'loungenie-portal'); ?></th>
							<th class="sortable" data-sort="status"><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Install Date', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Actions', 'loungenie-portal'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($units as $unit) : ?>
							<tr data-color="<?php echo esc_attr($unit->color_tag ?? ''); ?>"
								data-season="<?php echo esc_attr($unit->season ?? ''); ?>"
								data-venue="<?php echo esc_attr($unit->venue_type ?? ''); ?>"
								data-lock-brand="<?php echo esc_attr($unit->lock_brand ?? ''); ?>"
								data-status="<?php echo esc_attr($unit->status); ?>">
								<td>#<?php echo esc_html($unit->id); ?></td>
								<td><?php echo esc_html($unit->company_name); ?></td>
								<td>
									<?php
									if ($unit->color_tag) :
										$color_hex = lgp_get_color_hex($unit->color_tag);
									?>
										<span class="lgp-color-tag">
											<span class="lgp-color-indicator" style="--lgp-color-value: <?php echo esc_attr($color_hex); ?>;"></span>
											<?php echo esc_html($unit->color_tag); ?>
										</span>
									<?php else : ?>
										<span class="lgp-empty-value">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($unit->season) : ?>
										<span class="lgp-badge lgp-badge-<?php echo esc_attr($unit->season === 'seasonal' ? 'warning' : 'info'); ?>">
											<?php echo esc_html(ucfirst(str_replace('-', ' ', $unit->season))); ?>
										</span>
									<?php else : ?>
										<span class="lgp-empty-value">—</span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html($unit->venue_type ?? '—'); ?></td>
								<td><?php echo esc_html($unit->lock_brand ?? '—'); ?></td>
								<td>
									<?php
									$status_class = 'info';
									if ($unit->status === 'active') {
										$status_class = 'success';
									} elseif ($unit->status === 'service') {
										$status_class = 'warning';
									}
									?>
									<span class="lgp-badge lgp-badge-<?php echo esc_attr($status_class); ?>">
										<?php echo esc_html(ucfirst($unit->status)); ?>
									</span>
								</td>
								<td><?php echo esc_html($unit->install_date ? date_i18n(get_option('date_format'), strtotime($unit->install_date)) : '—'); ?></td>
								<td>
									<button class="lgp-btn lgp-btn-primary lgp-btn-sm" data-unit-id="<?php echo esc_attr($unit->id); ?>">
										<?php esc_html_e('View', 'loungenie-portal'); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Results Count -->
			<div class="lgp-results-info">
				<span id="visible-count"><?php echo count($units); ?></span> <?php esc_html_e('of', 'loungenie-portal'); ?>
				<span id="total-count"><?php echo count($units); ?></span> <?php esc_html_e('units', 'loungenie-portal'); ?>
			</div>
		<?php else : ?>
			<div class="lgp-empty-state-card">
				<p class="lgp-empty-state-icon">📦</p>
				<h3 class="lgp-empty-state-title"><?php esc_html_e('No Units Found', 'loungenie-portal'); ?></h3>
				<p class="lgp-empty-state-text"><?php esc_html_e('There are no LounGenie units to display at this time.', 'loungenie-portal'); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>