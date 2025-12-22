<?php
/**
 * Map View Template
 *
 * Displays poolside locations on a map with service tickets
 * Color-coded by urgency, with side panel for management
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure user has access
if ( ! is_user_logged_in() ) {
	wp_die( 'Access denied' );
}

// Do not call theme header/footer to avoid theme dependency and deprecation warnings.

// Compute base plugin URL defensively for test environments
$lgp_base_url = defined( 'LGP_PLUGIN_URL' )
	? LGP_PLUGIN_URL
	: ( function_exists( 'plugins_url' ) ? trailingslashit( plugins_url( '', dirname( __FILE__ ) ) ) : '/' );

// Load CSS (guard for non-WP test environments)
if ( function_exists( 'wp_enqueue_style' ) ) {
	wp_enqueue_style( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css' );
	wp_enqueue_style( 'lgp-map-view', $lgp_base_url . 'assets/css/map-view.css' );
}

// Load JS
if ( function_exists( 'wp_enqueue_script' ) ) {
	wp_enqueue_script( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true );
	wp_enqueue_script( 'leaflet-markercluster', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/leaflet.markercluster.min.js', array( 'leaflet' ), '1.4.1', true );
	wp_enqueue_script( 'lgp-map-view', $lgp_base_url . 'assets/js/map-view.js', array( 'leaflet' ), '1.0', true );
}

// Localize script data
if ( function_exists( 'wp_localize_script' ) ) {
	wp_localize_script(
		'lgp-map-view',
		'lgpMapData',
		array(
			'ajaxUrl' => function_exists( 'admin_url' ) ? admin_url( 'admin-ajax.php' ) : '/wp-admin/admin-ajax.php',
			'nonce'   => function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'lgp_map_nonce' ) : '',
			'mapType' => isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'all',
			'urgency' => isset( $_GET['urgency'] ) ? sanitize_text_field( $_GET['urgency'] ) : '',
			'status'  => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '',
		)
	);
}
?>

<div class="lgp-page-wrapper lgp-map-wrapper">
	<!-- Header -->
	<header class="lgp-page-header">
		<div class="lgp-container">
			<h1><?php esc_html_e( 'Service Map', 'loungenie-portal' ); ?></h1>
			<span class="screen-reader-text">Partner Map View</span>
			<p class="lgp-page-subtitle"><?php esc_html_e( 'Manage units and service tickets by location', 'loungenie-portal' ); ?></p>
		</div>
	</header>

	<div class="lgp-container lgp-map-content">
		<!-- Map Container -->
		<div class="lgp-map-container">
			<div id="map" class="lgp-map"></div>
		</div>

		<!-- Side Panel -->
		<aside class="lgp-map-sidebar">
			<!-- Filter Section -->
			<div class="lgp-map-filters">
				<h3><?php esc_html_e( 'Filters', 'loungenie-portal' ); ?></h3>

				<!-- Filter: Urgency -->
				<div class="lgp-filter-group">
					<label for="urgency-filter"><?php esc_html_e( 'Urgency', 'loungenie-portal' ); ?></label>
					<select id="urgency-filter" class="lgp-filter-select">
						<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'loungenie-portal' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'loungenie-portal' ); ?></option>
						<option value="medium"><?php esc_html_e( 'Medium', 'loungenie-portal' ); ?></option>
						<option value="low"><?php esc_html_e( 'Low', 'loungenie-portal' ); ?></option>
					</select>
				</div>

				<!-- Filter: Status -->
				<div class="lgp-filter-group">
					<label for="status-filter"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></label>
					<select id="status-filter" class="lgp-filter-select">
						<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
						<option value="open"><?php esc_html_e( 'Open', 'loungenie-portal' ); ?></option>
						<option value="in_progress"><?php esc_html_e( 'In Progress', 'loungenie-portal' ); ?></option>
						<option value="resolved"><?php esc_html_e( 'Resolved', 'loungenie-portal' ); ?></option>
						<option value="closed"><?php esc_html_e( 'Closed', 'loungenie-portal' ); ?></option>
					</select>
				</div>

				<!-- Filter: Type -->
				<div class="lgp-filter-group">
					<label for="type-filter"><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></label>
					<select id="type-filter" class="lgp-filter-select">
						<option value="all"><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
						<option value="maintenance"><?php esc_html_e( 'Maintenance', 'loungenie-portal' ); ?></option>
						<option value="repair"><?php esc_html_e( 'Repair', 'loungenie-portal' ); ?></option>
						<option value="inspection"><?php esc_html_e( 'Inspection', 'loungenie-portal' ); ?></option>
						<option value="cleaning"><?php esc_html_e( 'Cleaning', 'loungenie-portal' ); ?></option>
					</select>
				</div>

				<!-- Sort Options -->
				<div class="lgp-filter-group">
					<label for="sort-select"><?php esc_html_e( 'Sort By', 'loungenie-portal' ); ?></label>
					<select id="sort-select" class="lgp-filter-select">
						<option value="urgency-desc"><?php esc_html_e( 'Urgency (High to Low)', 'loungenie-portal' ); ?></option>
						<option value="date-desc"><?php esc_html_e( 'Date (Newest)', 'loungenie-portal' ); ?></option>
						<option value="date-asc"><?php esc_html_e( 'Date (Oldest)', 'loungenie-portal' ); ?></option>
						<option value="location"><?php esc_html_e( 'Location (A-Z)', 'loungenie-portal' ); ?></option>
					</select>
				</div>

				<button id="reset-filters" class="btn btn-secondary" style="width: 100%;">
					<?php esc_html_e( 'Reset Filters', 'loungenie-portal' ); ?>
				</button>
			</div>

			<!-- Units/Tickets List -->
			<div class="lgp-map-list">
				<h3><?php esc_html_e( 'Units & Tickets', 'loungenie-portal' ); ?></h3>
				<div id="units-list" class="lgp-units-list">
					<div class="lgp-loading">
						<?php esc_html_e( 'Loading...', 'loungenie-portal' ); ?>
					</div>
				</div>
			</div>

			<!-- Legend -->
			<div class="lgp-map-legend">
				<h4><?php esc_html_e( 'Legend', 'loungenie-portal' ); ?></h4>
				<div class="lgp-legend-item">
					<div class="lgp-legend-color" style="background-color: var(--color-critical);"></div>
					<span><?php esc_html_e( 'Critical', 'loungenie-portal' ); ?></span>
				</div>
				<div class="lgp-legend-item">
					<div class="lgp-legend-color" style="background-color: var(--color-high);"></div>
					<span><?php esc_html_e( 'High', 'loungenie-portal' ); ?></span>
				</div>
				<div class="lgp-legend-item">
					<div class="lgp-legend-color" style="background-color: var(--color-medium);"></div>
					<span><?php esc_html_e( 'Medium', 'loungenie-portal' ); ?></span>
				</div>
				<div class="lgp-legend-item">
					<div class="lgp-legend-color" style="background-color: var(--color-low);"></div>
					<span><?php esc_html_e( 'Low', 'loungenie-portal' ); ?></span>
				</div>
			</div>
		</aside>
	</div>

	<!-- Unit/Ticket Detail Modal -->
	<div id="detail-modal" class="lgp-modal" style="display: none;">
		<div class="lgp-modal-content">
			<button class="lgp-modal-close">&times;</button>
			<div id="modal-body">
				<!-- Content loaded via AJAX -->
			</div>
		</div>
	</div>
</div>
</div>

<!-- Map Placeholder -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Map View', 'loungenie-portal' ); ?></h2>
	</div>
	<div class="lgp-card-body">
		<div id="lgp-map-container" class="lgp-h-500 lgp-rounded-md lgp-flex-center lgp-border-2 lgp-border-dashed lgp-border-soft lgp-map-container">
			<div class="lgp-text-center lgp-p-xl">
				<p class="lgp-text-lg lgp-text-muted mb-md">
					🗺️ <?php esc_html_e( 'Map Integration Placeholder', 'loungenie-portal' ); ?>
				</p>
				<p class="lgp-text-muted">
					<?php esc_html_e( 'Integrate with Google Maps, OpenStreetMap, or Mapbox to display partner locations', 'loungenie-portal' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>

<!-- Partner Locations List -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Partner Locations', 'loungenie-portal' ); ?></h2>
	</div>
	<div class="lgp-card-body">
		<?php if ( ! empty( $companies ) ) : ?>
			<div class="lgp-table-container">
				<table class="lgp-table" id="locations-table">
					<thead>
						<tr>
							<th class="sortable"><?php esc_html_e( 'Company Name', 'loungenie-portal' ); ?></th>
							<th class="sortable"><?php esc_html_e( 'Address', 'loungenie-portal' ); ?></th>
							<th class="sortable"><?php esc_html_e( 'State', 'loungenie-portal' ); ?></th>
							<th class="sortable"><?php esc_html_e( 'Unit Count', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Contact', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $companies as $company ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $company->name ); ?></strong></td>
								<td><?php echo esc_html( $company->address ); ?></td>
								<td><?php echo esc_html( $company->state ?? __( 'N/A', 'loungenie-portal' ) ); ?></td>
								<td>
									<span class="lgp-badge lgp-badge-info">
										<?php echo esc_html( $company->unit_count ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?>
									</span>
								</td>
								<td>
									<?php if ( $company->contact_name ) : ?>
										<?php echo esc_html( $company->contact_name ); ?><br>
										<?php if ( $company->contact_email ) : ?>
											<a href="mailto:<?php echo esc_attr( $company->contact_email ); ?>">
												<?php echo esc_html( $company->contact_email ); ?>
											</a>
										<?php endif; ?>
									<?php else : ?>
										<?php esc_html_e( 'N/A', 'loungenie-portal' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No partner locations found with addresses.', 'loungenie-portal' ); ?></p>
		<?php endif; ?>
	</div>
</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
	const statusFilter = document.getElementById('status-filter');
	const regionFilter = document.getElementById('region-filter');
	const unitCountFilter = document.getElementById('unit-count-filter');
	
	function applyFilters() {
		const table = document.getElementById('locations-table');
		if (!table) return;
		
		const rows = table.querySelectorAll('tbody tr');
		const selectedRegion = regionFilter.value.toLowerCase();
		const minUnits = parseInt(unitCountFilter.value) || 0;
		
		rows.forEach(row => {
			const state = row.children[2].textContent.toLowerCase();
			const unitCount = parseInt(row.children[3].textContent);
			
			let show = true;
			
			if (selectedRegion && !state.includes(selectedRegion)) {
				show = false;
			}
			
			if (unitCount < minUnits) {
				show = false;
			}
			
			row.style.display = show ? '' : 'none';
		});
	}
	
	if (statusFilter) statusFilter.addEventListener('change', applyFilters);
	if (regionFilter) regionFilter.addEventListener('change', applyFilters);
	if (unitCountFilter) unitCountFilter.addEventListener('input', applyFilters);
});
</script>
