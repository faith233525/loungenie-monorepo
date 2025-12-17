<?php
/**
 * Gateway View Template (Support-Only)
 * Lists all gateways grouped by partner with management actions
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! LGP_Auth::is_support() ) {
	wp_die( 'Unauthorized access.' );
}

$gateways = LGP_Gateway::get_all();
$grouped  = array();

foreach ( $gateways as $gateway ) {
	$company = $gateway->company_name ?? 'Unknown Company';
	if ( ! isset( $grouped[ $company ] ) ) {
		$grouped[ $company ] = array();
	}
	$grouped[ $company ][] = $gateway;
}

ksort( $grouped );
?>

<div class="lgp-dashboard-header">
	<h1><?php esc_html_e( 'Gateway Management', 'loungenie-portal' ); ?></h1>
	<p><?php esc_html_e( 'Support-only view of all LounGenie gateways', 'loungenie-portal' ); ?></p>
</div>

<div class="lgp-card">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Filters & Search', 'loungenie-portal' ); ?></h2>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-gateway-filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
			<div>
				<label for="gateway-search"><?php esc_html_e( 'Search', 'loungenie-portal' ); ?></label>
				<input type="text" id="gateway-search" placeholder="<?php esc_attr_e( 'Channel, address, or company', 'loungenie-portal' ); ?>" />
			</div>
			<div>
				<label for="gateway-call-button-filter"><?php esc_html_e( 'Call Button', 'loungenie-portal' ); ?></label>
				<select id="gateway-call-button-filter">
					<option value=""><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
					<option value="1"><?php esc_html_e( 'Enabled', 'loungenie-portal' ); ?></option>
					<option value="0"><?php esc_html_e( 'Disabled', 'loungenie-portal' ); ?></option>
				</select>
			</div>
		</div>
	</div>
</div>

<?php if ( empty( $grouped ) ) : ?>
	<div class="lgp-card">
		<div class="lgp-card-body">
			<p><?php esc_html_e( 'No gateways found.', 'loungenie-portal' ); ?></p>
		</div>
	</div>
<?php else : ?>
	<?php foreach ( $grouped as $company_name => $company_gateways ) : ?>
		<div class="lgp-card lgp-gateway-group" data-company="<?php echo esc_attr( $company_name ); ?>">
			<div class="lgp-card-header">
				<h2 class="lgp-card-title"><?php echo esc_html( $company_name ); ?></h2>
				<span class="lgp-gateway-count"><?php echo count( $company_gateways ); ?> <?php esc_html_e( 'gateways', 'loungenie-portal' ); ?></span>
			</div>
			<div class="lgp-card-body">
				<table class="lgp-table lgp-gateway-table">
					<thead>
						<tr>
							<th data-sort="channel"><?php esc_html_e( 'Channel', 'loungenie-portal' ); ?></th>
							<th data-sort="address"><?php esc_html_e( 'Address', 'loungenie-portal' ); ?></th>
							<th data-sort="capacity"><?php esc_html_e( 'Unit Capacity', 'loungenie-portal' ); ?></th>
							<th data-sort="call_button"><?php esc_html_e( 'Call Button', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Equipment', 'loungenie-portal' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'loungenie-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $company_gateways as $gw ) :
							$call_button_class = $gw->call_button ? 'lgp-call-button-enabled' : '';
							?>
							<tr class="<?php echo esc_attr( $call_button_class ); ?>" data-gateway-id="<?php echo esc_attr( $gw->id ); ?>">
								<td><?php echo esc_html( $gw->channel_number ); ?></td>
								<td><?php echo esc_html( $gw->gateway_address ); ?></td>
								<td><?php echo esc_html( $gw->unit_capacity ); ?></td>
								<td>
									<?php if ( $gw->call_button ) : ?>
										<span class="lgp-badge lgp-badge-success"><?php esc_html_e( 'Yes', 'loungenie-portal' ); ?></span>
									<?php else : ?>
										<span class="lgp-badge lgp-badge-secondary"><?php esc_html_e( 'No', 'loungenie-portal' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $gw->included_equipment ?: '—' ); ?></td>
								<td class="lgp-gateway-actions">
									<button class="lgp-btn lgp-btn-sm lgp-btn-view-units" data-gateway-id="<?php echo esc_attr( $gw->id ); ?>">
										<?php esc_html_e( 'View Units', 'loungenie-portal' ); ?>
									</button>
									<button class="lgp-btn lgp-btn-sm lgp-btn-audit-logs" data-gateway-id="<?php echo esc_attr( $gw->id ); ?>">
										<?php esc_html_e( 'Audit Logs', 'loungenie-portal' ); ?>
									</button>
									<button class="lgp-btn lgp-btn-sm lgp-btn-primary lgp-btn-test-signal" data-gateway-id="<?php echo esc_attr( $gw->id ); ?>">
										<?php esc_html_e( 'Test Signal', 'loungenie-portal' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<!-- Modal for View Units -->
<div id="lgp-units-modal" class="lgp-modal" style="display: none;">
	<div class="lgp-modal-content">
		<span class="lgp-modal-close">&times;</span>
		<h2><?php esc_html_e( 'Connected Units', 'loungenie-portal' ); ?></h2>
		<div id="lgp-units-modal-body"></div>
	</div>
</div>

<!-- Modal for Audit Logs -->
<div id="lgp-audit-logs-modal" class="lgp-modal" style="display: none;">
	<div class="lgp-modal-content">
		<span class="lgp-modal-close">&times;</span>
		<h2><?php esc_html_e( 'Gateway Audit Logs', 'loungenie-portal' ); ?></h2>
		<div id="lgp-audit-logs-modal-body"></div>
	</div>
</div>
