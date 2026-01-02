<?php

/**
 * Partner Dashboard Template
 * Shows partner-specific information and request forms
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

global $wpdb;

// Get partner's company ID.
$company_id = LGP_Auth::get_user_company_id();

if (! $company_id) {
	echo '<div class="lgp-card">';
	echo '<p>' . esc_html__('No company associated with your account. Please contact support.', 'loungenie-portal') . '</p>';
	echo '</div>';
	return;
}

// Fetch company information.
$companies_table        = $wpdb->prefix . 'lgp_companies';
$units_table            = $wpdb->prefix . 'lgp_units';
$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
$mgmt_companies_table   = $wpdb->prefix . 'lgp_management_companies';

$company       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $companies_table WHERE id = %d", $company_id));
$unit_count    = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $units_table WHERE company_id = %d", $company_id));
$open_requests = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $service_requests_table WHERE company_id = %d AND status IN ('pending', 'in_progress')",
		$company_id
	)
);

// Get management company if exists.
$management_company = null;
if ($company->management_company_id) {
	$management_company = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $mgmt_companies_table WHERE id = %d",
			$company->management_company_id
		)
	);
}

// Recent activity.
$recent_requests = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM $service_requests_table WHERE company_id = %d ORDER BY created_at DESC LIMIT 5",
		$company_id
	)
);

?>

<div class="lgp-dashboard-container">

	<div class="lgp-dashboard-header">
		<h1><?php esc_html_e('Partner Dashboard', 'loungenie-portal'); ?></h1>
	</div>

	<!-- Orientation Card -->
	<div class="lgp-orientation-card" aria-label="<?php esc_attr_e('Dashboard Orientation', 'loungenie-portal'); ?>">
		<div class="lgp-orientation-left">
			<div class="lgp-orientation-title">
				<?php echo esc_html($company->name); ?>
			</div>
			<?php if ($management_company) : ?>
				<div class="lgp-orientation-subtitle">
					<?php echo esc_html($management_company->name); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="lgp-orientation-right">
			<div class="lgp-orientation-metric-item">
				<p class="lgp-orientation-metric">
					<?php echo esc_html($unit_count ?: '0'); ?>
				</p>
				<p class="lgp-orientation-metric-label">
					<?php esc_html_e('Active Units', 'loungenie-portal'); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- KPI Cards -->
	<div class="lgp-card-grid lgp-mt-4">
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-tower-cell" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html($unit_count ?: '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Active Gateways', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value"><?php echo esc_html($open_requests ?: '0'); ?></div>
					<div class="lgp-stat-card-label"><?php esc_html_e('Open Tickets', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-card-header">
				<div class="lgp-stat-card-icon"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i></div>
				<div>
					<div class="lgp-stat-card-value">99.98%</div>
					<div class="lgp-stat-card-label"><?php esc_html_e('System Uptime', 'loungenie-portal'); ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Primary Actions -->
	<div class="lgp-card lgp-flex lgp-items-center lgp-justify-between">
		<div class="lgp-card-header lgp-border-none lgp-pb-0 lgp-mb-0">
			<h2 class="lgp-card-title"><?php esc_html_e('Quick Actions', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-footer lgp-border-none lgp-pt-0">
			<a href="<?php echo esc_url(home_url('/portal/requests')); ?>" class="button button-primary">
				<i class="fa-solid fa-plus" aria-hidden="true"></i>
				<?php esc_html_e('Create Ticket', 'loungenie-portal'); ?>
			</a>
			<a href="<?php echo esc_url(home_url('/portal/gateways')); ?>" class="button button-secondary">
				<i class="fa-solid fa-router" aria-hidden="true"></i>
				<?php esc_html_e('Add New Gateway', 'loungenie-portal'); ?>
			</a>
		</div>
	</div>

	<!-- Company Information -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Company Information', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<div class="lgp-grid-auto-250">
				<div>
					<p><strong><?php esc_html_e('Company Name:', 'loungenie-portal'); ?></strong> <?php echo esc_html($company->name); ?></p>
					<p><strong><?php esc_html_e('Address:', 'loungenie-portal'); ?></strong> <?php echo esc_html($company->address ?? __('N/A', 'loungenie-portal')); ?></p>
					<p><strong><?php esc_html_e('State:', 'loungenie-portal'); ?></strong> <?php echo esc_html($company->state ?? __('N/A', 'loungenie-portal')); ?></p>
				</div>
				<?php if ($management_company) : ?>
					<div>
						<p><strong><?php esc_html_e('Management Company:', 'loungenie-portal'); ?></strong> <?php echo esc_html($management_company->name); ?></p>
						<p><strong><?php esc_html_e('Contact:', 'loungenie-portal'); ?></strong> <?php echo esc_html($management_company->contact_name ?? __('N/A', 'loungenie-portal')); ?></p>
						<p><strong><?php esc_html_e('Email:', 'loungenie-portal'); ?></strong> <?php echo esc_html($management_company->contact_email ?? __('N/A', 'loungenie-portal')); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Submit Service Request -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Submit Service Request', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<form id="service-request-form" method="post">
				<div class="lgp-form-group">
					<label for="request-type" class="lgp-label"><?php esc_html_e('Request Type', 'loungenie-portal'); ?></label>
					<select id="request-type" name="request_type" class="lgp-select" required>
						<option value=""><?php esc_html_e('Select type...', 'loungenie-portal'); ?></option>
						<option value="install"><?php esc_html_e('Installation', 'loungenie-portal'); ?></option>
						<option value="maintenance"><?php esc_html_e('Maintenance', 'loungenie-portal'); ?></option>
						<option value="repair"><?php esc_html_e('Repair', 'loungenie-portal'); ?></option>
						<option value="update"><?php esc_html_e('Update', 'loungenie-portal'); ?></option>
					</select>
				</div>

				<div class="lgp-form-group">
					<label for="priority" class="lgp-label"><?php esc_html_e('Priority', 'loungenie-portal'); ?></label>
					<select id="priority" name="priority" class="lgp-select" required>
						<option value="normal"><?php esc_html_e('Normal', 'loungenie-portal'); ?></option>
						<option value="high"><?php esc_html_e('High', 'loungenie-portal'); ?></option>
						<option value="urgent"><?php esc_html_e('Urgent', 'loungenie-portal'); ?></option>
					</select>
				</div>

				<div class="lgp-form-group">
					<label for="notes" class="lgp-label"><?php esc_html_e('Notes', 'loungenie-portal'); ?></label>
					<textarea id="notes" name="notes" class="lgp-textarea" placeholder="<?php esc_attr_e('Describe your request...', 'loungenie-portal'); ?>"></textarea>
				</div>

				<?php wp_nonce_field('lgp_submit_service_request', 'lgp_service_request_nonce'); ?>

				<button type="submit" class="button button-primary">
					<?php esc_html_e('Submit Request', 'loungenie-portal'); ?>
				</button>
			</form>
		</div>
	</div>

	<!-- Recent Activity -->
	<div class="lgp-card">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title"><?php esc_html_e('Recent Activity', 'loungenie-portal'); ?></h2>
		</div>
		<div class="lgp-card-body">
			<?php if (! empty($recent_requests)) : ?>
				<div class="lgp-table-container">
					<table class="lgp-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Request ID', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Type', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Priority', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Status', 'loungenie-portal'); ?></th>
								<th><?php esc_html_e('Date', 'loungenie-portal'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($recent_requests as $request) : ?>
								<tr>
									<td>#<?php echo esc_html($request->id); ?></td>
									<td><?php echo esc_html(ucfirst($request->request_type)); ?></td>
									<td><?php echo esc_html(ucfirst($request->priority)); ?></td>
									<td>
										<?php
										$status_class = 'info';
										if ($request->status === 'pending') {
											$status_class = 'warning';
										} elseif ($request->status === 'completed') {
											$status_class = 'success';
										}
										?>
										<span class="lgp-badge lgp-badge-<?php echo esc_attr($status_class); ?>">
											<?php echo esc_html(ucfirst($request->status)); ?>
										</span>
									</td>
									<td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($request->created_at))); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p><?php esc_html_e('No recent activity.', 'loungenie-portal'); ?></p>
			<?php endif; ?>
		</div>
	</div>

</div><!-- .lgp-dashboard-container -->