<?php

/**
 * Unified Company Profile View Template
 * Consolidated view showing company data, tickets, units, gateways, training videos, and contract metadata
 * Partners see read-only view of their company; Support can view and edit any company
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

global $wpdb;

// Get company ID from request parameter
$company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : LGP_Auth::get_user_company_id();

if (! $company_id) {
	echo '<div class="lgp-card">';
	echo '<p>' . esc_html__('No company found. Please contact support.', 'loungenie-portal') . '</p>';
	echo '</div>';
	return;
}

// Authorization check
$is_support      = LGP_Auth::is_support();
$user_company_id = LGP_Auth::get_user_company_id();
$can_edit        = $is_support || ($company_id === $user_company_id);

if (! $is_support && $company_id !== $user_company_id) {
	echo '<div class="lgp-card">';
	echo '<p>' . esc_html__('You do not have access to this company.', 'loungenie-portal') . '</p>';
	echo '</div>';
	return;
}

// Fetch company information
$companies_table        = $wpdb->prefix . 'lgp_companies';
$units_table            = $wpdb->prefix . 'lgp_units';
$tickets_table          = $wpdb->prefix . 'lgp_tickets';
$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
$gateways_table         = $wpdb->prefix . 'lgp_gateways';
$training_videos_table  = $wpdb->prefix . 'lgp_training_videos';
$mgmt_companies_table   = $wpdb->prefix . 'lgp_management_companies';

$company = $wpdb->get_row($wpdb->prepare("SELECT * FROM $companies_table WHERE id = %d", $company_id));

if (! $company) {
	echo '<div class="lgp-card">';
	echo '<p>' . esc_html__('Company not found.', 'loungenie-portal') . '</p>';
	echo '</div>';
	return;
}

// Fetch related data
$units = $wpdb->get_results($wpdb->prepare("SELECT * FROM $units_table WHERE company_id = %d ORDER BY id DESC", $company_id));

// Phase 2B: Get color aggregates instead of individual units
$unit_colors = LGP_Company_Colors::get_company_colors($company_id);
$unit_count  = LGP_Company_Colors::get_company_unit_count($company_id);

$gateways = $wpdb->get_results($wpdb->prepare("SELECT * FROM $gateways_table WHERE company_id = %d ORDER BY id DESC", $company_id));
$tickets  = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT t.*, sr.request_type, sr.priority, sr.status as request_status FROM $tickets_table t 
    LEFT JOIN $service_requests_table sr ON t.service_request_id = sr.id 
    WHERE sr.company_id = %d ORDER BY t.created_at DESC LIMIT 10",
		$company_id
	)
);

// Count metrics
$total_units = count($units);
// Phase 2B: Use aggregated count instead of fetching all units
$total_units               = $unit_count;
$total_gateways            = count($gateways);
$open_tickets              = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $tickets_table t 
    LEFT JOIN $service_requests_table sr ON t.service_request_id = sr.id 
    WHERE sr.company_id = %d AND t.status = 'open'",
		$company_id
	)
);
$gateways_with_call_button = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $gateways_table WHERE company_id = %d AND call_button = 1",
		$company_id
	)
);

// Get management company if exists
$management_company = null;
if ($company->management_company_id) {
	$management_company = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $mgmt_companies_table WHERE id = %d",
			$company->management_company_id
		)
	);
}

?>

<div class="lgp-dashboard-header">
	<div class="lgp-flex lgp-justify-between lgp-items-center">
		<div>
			<h1><?php echo esc_html($company->name); ?></h1>
			<p><?php esc_html_e('Company Profile & Management', 'loungenie-portal'); ?></p>
		</div>
		<?php if ($can_edit && $is_support) : ?>
			<a href="#" class="lgp-btn lgp-btn-primary" id="lgp-edit-company-btn">
				<?php esc_html_e('Edit Company', 'loungenie-portal'); ?>
			</a>
		<?php endif; ?>
	</div>
</div>

<!-- Metrics Grid -->
<div class="lgp-stats-grid">
	<div class="lgp-stat-card">
		<div class="lgp-stat-label"><?php esc_html_e('LounGenie Units', 'loungenie-portal'); ?></div>
		<div class="lgp-stat-value"><?php echo esc_html($total_units); ?></div>
	</div>

	<div class="lgp-stat-card">
		<div class="lgp-stat-label"><?php esc_html_e('Gateways', 'loungenie-portal'); ?></div>
		<div class="lgp-stat-value"><?php echo esc_html($total_gateways); ?></div>
		<small><?php echo esc_html($gateways_with_call_button); ?> with call button</small>
	</div>

	<div class="lgp-stat-card">
		<div class="lgp-stat-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></div>
		<div class="lgp-stat-value"><?php echo esc_html($open_tickets ?: '0'); ?></div>
	</div>

	<?php if (! empty($company->contract_type)) : ?>
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e('Contract Type', 'loungenie-portal'); ?></div>
			<div class="lgp-stat-value lgp-text-sm">
				<?php echo esc_html(ucfirst(str_replace('_', ' ', $company->contract_type))); ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Company Information Card -->
<div class="lgp-card" data-section="company-info">
	<div class="lgp-card-header collapsible" data-section="company-info">
		<h2 class="lgp-card-title"><?php esc_html_e('Company Information', 'loungenie-portal'); ?></h2>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-grid-auto-300">
			<div>
				<h3><?php esc_html_e('Basic Information', 'loungenie-portal'); ?></h3>
				<p><strong><?php esc_html_e('Company Name:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->name); ?></p>
				<p><strong><?php esc_html_e('Address:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->address ?? __('N/A', 'loungenie-portal')); ?></p>
				<p><strong><?php esc_html_e('State:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->state ?? __('N/A', 'loungenie-portal')); ?></p>
				<p><strong><?php esc_html_e('Venue Type:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->venue_type ?? __('N/A', 'loungenie-portal')); ?></p>
			</div>

			<div>
				<h3><?php esc_html_e('Primary Contact', 'loungenie-portal'); ?></h3>
				<p><strong><?php esc_html_e('Contact Name:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->contact_name ?? __('N/A', 'loungenie-portal')); ?></p>
				<p><strong><?php esc_html_e('Email:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->contact_email ?? __('N/A', 'loungenie-portal')); ?></p>
				<p><strong><?php esc_html_e('Phone:', 'loungenie-portal'); ?></strong><br>
					<?php echo esc_html($company->contact_phone ?? __('N/A', 'loungenie-portal')); ?></p>
			</div>

			<?php if (! empty($company->secondary_contact_name) || ! empty($company->secondary_contact_email)) : ?>
				<div>
					<h3><?php esc_html_e('Secondary Contact', 'loungenie-portal'); ?></h3>
					<p><strong><?php esc_html_e('Contact Name:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($company->secondary_contact_name ?? __('N/A', 'loungenie-portal')); ?></p>
					<p><strong><?php esc_html_e('Email:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($company->secondary_contact_email ?? __('N/A', 'loungenie-portal')); ?></p>
					<p><strong><?php esc_html_e('Phone:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($company->secondary_contact_phone ?? __('N/A', 'loungenie-portal')); ?></p>
				</div>
			<?php endif; ?>

			<?php if (! empty($company->contract_type) || ! empty($company->contract_start_date)) : ?>
				<div>
					<h3><?php esc_html_e('Contract Information', 'loungenie-portal'); ?></h3>
					<p><strong><?php esc_html_e('Contract Type:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html(ucfirst(str_replace('_', ' ', $company->contract_type ?? __('N/A', 'loungenie-portal')))); ?></p>
					<p><strong><?php esc_html_e('Start Date:', 'loungenie-portal'); ?></strong><br>
						<?php echo $company->contract_start_date ? esc_html(date_i18n(get_option('date_format'), strtotime($company->contract_start_date))) : esc_html__('N/A', 'loungenie-portal'); ?></p>
					<p><strong><?php esc_html_e('End Date:', 'loungenie-portal'); ?></strong><br>
						<?php echo $company->contract_end_date ? esc_html(date_i18n(get_option('date_format'), strtotime($company->contract_end_date))) : esc_html__('N/A', 'loungenie-portal'); ?></p>
				</div>
			<?php endif; ?>

			<?php if ($management_company) : ?>
				<div>
					<h3><?php esc_html_e('Management Company', 'loungenie-portal'); ?></h3>
					<p><strong><?php esc_html_e('Company Name:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($management_company->name); ?></p>
					<p><strong><?php esc_html_e('Contact:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($management_company->contact_name ?? __('N/A', 'loungenie-portal')); ?></p>
					<p><strong><?php esc_html_e('Email:', 'loungenie-portal'); ?></strong><br>
						<?php echo esc_html($management_company->contact_email ?? __('N/A', 'loungenie-portal')); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Units Section -->
<div class="lgp-card" data-section="units">
	<div class="lgp-card-header collapsible" data-section="units">
		<h2 class="lgp-card-title"><?php esc_html_e('LounGenie Units', 'loungenie-portal'); ?> (<?php echo esc_html($total_units); ?>)</h2>
	</div>
	<div class="lgp-card-body">
		<!-- Phase 2B: Color Distribution (Company-level aggregates only) -->
		<?php if (! empty($unit_colors)) : ?>
			<div class="lgp-color-distribution">
				<div class="lgp-color-legend">
					<p class="lgp-text-muted lgp-mb-3">
						<?php esc_html_e('Unit distribution by status color:', 'loungenie-portal'); ?>
					</p>
					<div class="lgp-color-bars">
						<?php
						$total = array_sum($unit_colors);
						foreach ($unit_colors as $color => $count) :
							$percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
							$hex_color  = LGP_Company_Colors::get_color_hex($color);
						?>
							<div class="lgp-color-bar-item lgp-mb-3">
								<div class="lgp-flex lgp-justify-between lgp-items-center lgp-mb-1">
									<div class="lgp-flex lgp-items-center">
										<span class="lgp-color-indicator" style="background-color: <?php echo esc_attr($hex_color); ?>; width: 20px; height: 20px; display: inline-block; border-radius: 3px; margin-right: 8px; border: 1px solid #ddd;"></span>
										<span class="lgp-color-name lgp-font-medium">
											<?php echo esc_html(ucfirst($color)); ?>
										</span>
									</div>
									<span class="lgp-color-count lgp-text-muted">
										<?php
										/* translators: %1$d: unit count, %2$s: percentage */
										printf(esc_html__('%1$d units (%2$s%%)', 'loungenie-portal'), $count, $percentage);
										?>
									</span>
								</div>
								<div class="lgp-progress-bar" style="background-color: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
									<div class="lgp-progress-fill" style="background-color: <?php echo esc_attr($hex_color); ?>; width: <?php echo esc_attr($percentage); ?>%; height: 100%;"></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ($is_support) : ?>
					<!-- Support: Show detailed units table -->
					<div class="lgp-mt-4">
						<button type="button" class="lgp-btn lgp-btn-secondary" onclick="this.nextElementSibling.classList.toggle('lgp-hidden')">
							<?php esc_html_e('View Detailed Unit List', 'loungenie-portal'); ?>
						</button>
						<div class="lgp-table-container lgp-max-h-400 lgp-mt-2 lgp-hidden">
							<table class="lgp-table">
								<thead>
									<tr>
										<th><?php esc_html_e('ID', 'loungenie-portal'); ?></th>
										<th><?php esc_html_e('Address', 'loungenie-portal'); ?></th>
										<th><?php esc_html_e('Lock Type', 'loungenie-portal'); ?></th>
										<th><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
										<th><?php esc_html_e('Color', 'loungenie-portal'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($units as $unit) : ?>
										<tr>
											<td>#<?php echo esc_html($unit->id); ?></td>
											<td><?php echo esc_html(substr($unit->address ?? 'N/A', 0, 40)); ?></td>
											<td><?php echo esc_html($unit->lock_type ?? 'N/A'); ?></td>
											<td><span class="lgp-badge lgp-badge-info"><?php echo esc_html(ucfirst($unit->status ?? 'unknown')); ?></span></td>
											<td><?php echo esc_html($unit->color_tag ?? 'N/A'); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('No units found for this company.', 'loungenie-portal'); ?></p>
		<?php endif; ?>
	</div>
</div>

<!-- Gateways Section (Support Only) -->
<?php if ($is_support) : ?>
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Gateways', 'loungenie-portal'); ?> (<?php echo esc_html(count($gateways)); ?>)</h2>
		</div>
		<div class="lgp-card-body">
			<?php if (! empty($gateways)) : ?>
				<div class="lgp-table-container lgp-max-h-400">
					<table class="lgp-table">
						<thead>
							<tr>
								<th><?php esc_html_e('ID', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Channel', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Address', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Unit Capacity', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Call Button', 'loungenie-portal'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($gateways as $gateway) : ?>
								<tr <?php echo $gateway->call_button ? 'class="lgp-gateway-active"' : ''; ?>>
									<td>#<?php echo esc_html($gateway->id); ?></td>
									<td><?php echo esc_html($gateway->channel_number ?? 'N/A'); ?></td>
									<td><?php echo esc_html($gateway->gateway_address ?? 'N/A'); ?></td>
									<td><?php echo esc_html($gateway->unit_capacity ?? '0'); ?></td>
									<td><?php echo $gateway->call_button ? '✓' : '✗'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p><?php esc_html_e('No gateways found for this company.', 'loungenie-portal'); ?></p>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<!-- Tickets Section -->
<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e('Recent Tickets', 'loungenie-portal'); ?></h2>
	</div>
	<div class="lgp-card-body">
		<?php if (! empty($tickets)) : ?>
			<div class="lgp-table-container lgp-max-h-400">
				<table class="lgp-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Ticket ID', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Type', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Priority', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Created', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Actions', 'loungenie-portal'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tickets as $ticket) : ?>
							<tr>
								<td>#<?php echo esc_html($ticket->id); ?></td>
								<td><?php echo esc_html(ucfirst($ticket->request_type ?? 'general')); ?></td>
								<td>
									<?php
									$priority_class = 'info';
									if ($ticket->priority === 'high') {
										$priority_class = 'warning';
									} elseif ($ticket->priority === 'urgent') {
										$priority_class = 'error';
									}
									?>
									<span class="lgp-badge lgp-badge-<?php echo esc_attr($priority_class); ?>">
										<?php echo esc_html(ucfirst($ticket->priority ?? 'normal')); ?>
									</span>
								</td>
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
										<?php echo esc_html(ucfirst($ticket->status ?? 'unknown')); ?>
									</span>
								</td>
								<td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket->created_at))); ?></td>
								<td>
									<button class="lgp-btn lgp-btn-small lgp-btn-secondary reply-ticket-btn"
										data-ticket-id="<?php echo esc_attr($ticket->id); ?>"
										data-ticket-status="<?php echo esc_attr($ticket->status ?? 'open'); ?>">
										<?php esc_html_e('Reply', 'loungenie-portal'); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('No tickets found for this company.', 'loungenie-portal'); ?></p>
		<?php endif; ?>
	</div>
</div>

<!-- Audit Log Viewer (Support Only) -->
<?php if ($is_support) : ?>
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Audit Log', 'loungenie-portal'); ?></h2>
			<div class="lgp-flex-center lgp-mb-md">
				<input type="text" id="audit-filter-action" placeholder="<?php esc_attr_e('Filter by action...', 'loungenie-portal'); ?>" class="lgp-input">
				<input type="date" id="audit-filter-date" class="lgp-input">
			</div>
		</div>
		<div class="lgp-card-body">
			<div id="audit-log-container" class="lgp-table-container lgp-max-h-400">
				<table class="lgp-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Timestamp', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('User', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Action', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Details', 'loungenie-portal'); ?></th>
						</tr>
					</thead>
					<tbody id="audit-log-body">
						<tr>
							<td colspan="4"><?php esc_html_e('Loading audit log...', 'loungenie-portal'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- Service Notes Section (Support Only) -->
<?php if ($is_support) : ?>
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Service Notes', 'loungenie-portal'); ?></h2>
			<button class="lgp-btn lgp-btn-primary lgp-mt-sm" id="add-service-note-btn">
				<?php esc_html_e('+ Add Service Note', 'loungenie-portal'); ?>
			</button>
		</div>
		<div class="lgp-card-body">
			<!-- Service Notes Form (hidden by default) -->
			<div id="service-note-form" class="lgp-hidden lgp-mb-lg lgp-p-md lgp-service-note-form">
				<form id="add-service-note-form">
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Date', 'loungenie-portal'); ?></label>
						<input type="date" id="service-note-date" required class="lgp-input">
					</div>
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Technician', 'loungenie-portal'); ?></label>
						<input type="text" id="service-note-technician" required class="lgp-input">
					</div>
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Service Type', 'loungenie-portal'); ?></label>
						<select id="service-note-type" required class="lgp-select">
							<option value=""><?php esc_html_e('Select service type...', 'loungenie-portal'); ?></option>
							<option value="maintenance"><?php esc_html_e('Maintenance', 'loungenie-portal'); ?></option>
							<option value="repair"><?php esc_html_e('Repair', 'loungenie-portal'); ?></option>
							<option value="inspection"><?php esc_html_e('Inspection', 'loungenie-portal'); ?></option>
							<option value="installation"><?php esc_html_e('Installation', 'loungenie-portal'); ?></option>
							<option value="other"><?php esc_html_e('Other', 'loungenie-portal'); ?></option>
						</select>
					</div>
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Unit (optional)', 'loungenie-portal'); ?></label>
						<select id="service-note-unit" class="lgp-select">
							<option value="">-- <?php esc_html_e('Select unit...', 'loungenie-portal'); ?> --</option>
							<?php foreach ($units as $unit) : ?>
								<option value="<?php echo esc_attr($unit->id); ?>">#<?php echo esc_html($unit->id); ?> - <?php echo esc_html(substr($unit->address ?? '', 0, 40)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Notes', 'loungenie-portal'); ?></label>
						<textarea id="service-note-notes" required class="lgp-textarea"></textarea>
					</div>
					<div class="lgp-form-group">
						<label class="lgp-label"><?php esc_html_e('Travel Time (minutes)', 'loungenie-portal'); ?></label>
						<input type="number" id="service-note-travel-time" min="0" class="lgp-input">
					</div>
					<div class="lgp-flex-center">
						<button type="submit" class="lgp-btn lgp-btn-primary"><?php esc_html_e('Save Service Note', 'loungenie-portal'); ?></button>
						<button type="button" id="cancel-service-note-btn" class="lgp-btn lgp-btn-secondary"><?php esc_html_e('Cancel', 'loungenie-portal'); ?></button>
					</div>
				</form>
			</div>

			<!-- Service Notes Table -->
			<div id="service-notes-table-container" class="lgp-table-container lgp-max-h-400">
				<table class="lgp-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Date', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Technician', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Type', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Unit', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Travel Time', 'loungenie-portal'); ?></th>
							<th><?php esc_html_e('Notes', 'loungenie-portal'); ?></th>
						</tr>
					</thead>
					<tbody id="service-notes-body">
						<tr>
							<td colspan="6"><?php esc_html_e('Loading service notes...', 'loungenie-portal'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- Modal: Ticket Reply -->
<div id="reply-modal" class="lgp-modal lgp-hidden">
	<div class="lgp-modal-content">
		<div class="lgp-modal-header">
			<h2><?php esc_html_e('Reply to Ticket', 'loungenie-portal'); ?></h2>
			<button class="lgp-modal-close" id="lgp-reply-modal-close">&times;</button>
		</div>
		<div class="lgp-modal-body">
			<form id="reply-form">
				<input type="hidden" id="reply-ticket-id" value="">
				<div class="lgp-form-group">
					<label class="lgp-label"><?php esc_html_e('Your Reply', 'loungenie-portal'); ?></label>
					<textarea id="reply-content" required class="lgp-textarea"></textarea>
				</div>
				<div class="lgp-flex-center">
					<button type="submit" class="lgp-btn lgp-btn-primary"><?php esc_html_e('Send Reply', 'loungenie-portal'); ?></button>
					<button type="button" class="lgp-btn lgp-btn-secondary" id="lgp-reply-modal-cancel"><?php esc_html_e('Cancel', 'loungenie-portal'); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php $lgp_nonce = method_exists('LGP_Security', 'get_csp_nonce') ? LGP_Security::get_csp_nonce() : ''; ?>
<script<?php echo $lgp_nonce ? ' nonce="' . esc_attr($lgp_nonce) . '"' : ''; ?>>
	// Pass company ID to JavaScript
	window.lgpCompanyId = <?php echo (int) $company_id; ?>;
	window.lgpIsSupport = <?php echo $is_support ? 'true' : 'false'; ?>;
	</script>