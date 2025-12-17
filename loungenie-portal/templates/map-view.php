<?php
/**
 * Map View Template
 * Display partner locations on a map (Support only)
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check support role
if ( ! LGP_Auth::is_support() ) {
	wp_die(
		esc_html__( 'Access Denied: This feature is only available to support users.', 'loungenie-portal' ),
		esc_html__( 'Access Denied', 'loungenie-portal' ),
		array( 'response' => 403 )
	);
}

global $wpdb;

// Fetch all companies with addresses
$companies_table = $wpdb->prefix . 'lgp_companies';
$units_table     = $wpdb->prefix . 'lgp_units';

$companies = $wpdb->get_results(
	"SELECT c.*, COUNT(u.id) as unit_count 
    FROM $companies_table c 
    LEFT JOIN $units_table u ON c.id = u.company_id 
    WHERE c.address IS NOT NULL AND c.address != '' 
    GROUP BY c.id 
    ORDER BY c.name ASC"
);

?>

<div class="lgp-dashboard-header">
	<h1><?php esc_html_e( 'Partner Map View', 'loungenie-portal' ); ?></h1>
	<p><?php esc_html_e( 'View all partner locations and filter by status, unit count, or region', 'loungenie-portal' ); ?></p>
</div>

<!-- Filters -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Filters', 'loungenie-portal' ); ?></h2>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-filters">
			<div class="lgp-filter-group">
				<label for="status-filter" class="lgp-label"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></label>
				<select id="status-filter" class="lgp-select">
					<option value=""><?php esc_html_e( 'All Statuses', 'loungenie-portal' ); ?></option>
					<option value="active"><?php esc_html_e( 'Active', 'loungenie-portal' ); ?></option>
					<option value="install"><?php esc_html_e( 'Installation', 'loungenie-portal' ); ?></option>
					<option value="service"><?php esc_html_e( 'Service', 'loungenie-portal' ); ?></option>
				</select>
			</div>
			
			<div class="lgp-filter-group">
				<label for="region-filter" class="lgp-label"><?php esc_html_e( 'Region/State', 'loungenie-portal' ); ?></label>
				<select id="region-filter" class="lgp-select">
					<option value=""><?php esc_html_e( 'All Regions', 'loungenie-portal' ); ?></option>
					<?php
					$states = $wpdb->get_col( "SELECT DISTINCT state FROM $companies_table WHERE state IS NOT NULL ORDER BY state" );
					foreach ( $states as $state ) {
						echo '<option value="' . esc_attr( $state ) . '">' . esc_html( $state ) . '</option>';
					}
					?>
				</select>
			</div>
			
			<div class="lgp-filter-group">
				<label for="unit-count-filter" class="lgp-label"><?php esc_html_e( 'Minimum Units', 'loungenie-portal' ); ?></label>
				<input type="number" id="unit-count-filter" class="lgp-input" min="0" placeholder="0">
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
		<div id="lgp-map-container" style="height: 500px; background-color: var(--background); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; border: 2px dashed var(--soft);">
			<div style="text-align: center; padding: var(--space-xl);">
				<p style="font-size: var(--font-size-lg); color: var(--neutral); margin-bottom: var(--space-md);">
					🗺️ <?php esc_html_e( 'Map Integration Placeholder', 'loungenie-portal' ); ?>
				</p>
				<p style="color: var(--neutral);">
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
