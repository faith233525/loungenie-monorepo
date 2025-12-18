<?php
/**
 * Tickets View Template
 * Lists tickets for partners/support with filters and thread detail
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$is_support   = LGP_Auth::is_support();
$company_id   = LGP_Auth::get_user_company_id();
$company_name = method_exists( 'LGP_Auth', 'get_company_name' ) ? LGP_Auth::get_company_name() : '';


$tickets_table   = $wpdb->prefix . 'lgp_tickets';
$requests_table  = $wpdb->prefix . 'lgp_service_requests';
$companies_table = $wpdb->prefix . 'lgp_companies';

// Status counts for quick overview (respect partner scoping)
if ( $is_support ) {
	$counts_raw = $wpdb->get_results( "SELECT status, COUNT(*) AS count FROM {$tickets_table} GROUP BY status" );
} else {
	$counts_raw = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT t.status, COUNT(*) AS count
            FROM {$tickets_table} t
            LEFT JOIN {$requests_table} sr ON t.service_request_id = sr.id
            WHERE sr.company_id = %d
            GROUP BY t.status",
			$company_id
		)
	);
}

$counts = array(
	'open'    => 0,
	'pending' => 0,
	'closed'  => 0,
);
foreach ( (array) $counts_raw as $row ) {
	$counts[ $row->status ] = isset( $counts[ $row->status ] ) ? (int) $row->count : (int) $row->count;
}
$total_tickets = array_sum( $counts );

// Support can filter by company
$companies = array();
if ( $is_support ) {
	$companies = $wpdb->get_results( "SELECT id, name FROM {$companies_table} ORDER BY name ASC" );
}

// Prepare filter option lists
$priorities    = array( 'urgent', 'high', 'normal' );
$request_types = array( 'install', 'maintenance', 'repair', 'update', 'general' );
$statuses      = array( 'open', 'pending', 'closed' );

$rest_base = esc_url_raw( rest_url( 'lgp/v1/' ) );
$rest_nonce = wp_create_nonce( 'wp_rest' );
?>

<div class="lgp-dashboard-header">
	<h1><?php echo esc_html( $is_support ? __( 'Support Tickets', 'loungenie-portal' ) : __( 'My Support Tickets', 'loungenie-portal' ) ); ?></h1>
	<p>
		<?php
		if ( $is_support ) {
			esc_html_e( 'Track, triage, and reply to all partner tickets.', 'loungenie-portal' );
		} else {
			esc_html_e( 'View the status of your requests and reply to the support team.', 'loungenie-portal' );
		}
		?>
	</p>
</div>

<div class="lgp-card">
	<div class="lgp-card-body lgp-stats-grid">
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e( 'Open', 'loungenie-portal' ); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html( $counts['open'] ); ?></div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e( 'Pending', 'loungenie-portal' ); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html( $counts['pending'] ); ?></div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e( 'Closed', 'loungenie-portal' ); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html( $counts['closed'] ); ?></div>
		</div>
		<div class="lgp-stat-card">
			<div class="lgp-stat-label"><?php esc_html_e( 'Total Tickets', 'loungenie-portal' ); ?></div>
			<div class="lgp-stat-value"><?php echo esc_html( $total_tickets ); ?></div>
		</div>
	</div>
</div>

<div class="lgp-card" id="lgp-tickets-view" data-is-support="<?php echo $is_support ? '1' : '0'; ?>" data-rest-url="<?php echo esc_attr( $rest_base ); ?>" data-rest-nonce="<?php echo esc_attr( $rest_nonce ); ?>" data-company-name="<?php echo esc_attr( $company_name ); ?>">
	<div class="lgp-card-header">
		<h2 class="lgp-card-title"><?php esc_html_e( 'Tickets', 'loungenie-portal' ); ?></h2>
		<div class="lgp-flex-center lgp-tickets-actions">
			<a class="lgp-btn lgp-btn-secondary" href="<?php echo esc_url( home_url( '/portal' ) ); ?>">➕ <?php esc_html_e( 'New Request', 'loungenie-portal' ); ?></a>
			<button type="button" class="lgp-btn lgp-btn-secondary" id="lgp-refresh-tickets">
				<?php esc_html_e( 'Refresh', 'loungenie-portal' ); ?>
			</button>
		</div>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-filters lgp-tickets-filters">
			<div class="lgp-form-group">
				<label for="lgp-ticket-search" class="lgp-label"><?php esc_html_e( 'Search', 'loungenie-portal' ); ?></label>
				<input type="text" id="lgp-ticket-search" class="lgp-input" placeholder="<?php esc_attr_e( 'Search by ID, type, or text...', 'loungenie-portal' ); ?>">
			</div>

			<div class="lgp-form-group">
				<label for="lgp-ticket-status-filter" class="lgp-label"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></label>
				<select id="lgp-ticket-status-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All statuses', 'loungenie-portal' ); ?></option>
					<?php foreach ( $statuses as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lgp-form-group">
				<label for="lgp-ticket-priority-filter" class="lgp-label"><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></label>
				<select id="lgp-ticket-priority-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All priorities', 'loungenie-portal' ); ?></option>
					<?php foreach ( $priorities as $priority ) : ?>
						<option value="<?php echo esc_attr( $priority ); ?>"><?php echo esc_html( ucfirst( $priority ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lgp-form-group">
				<label for="lgp-ticket-type-filter" class="lgp-label"><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></label>
				<select id="lgp-ticket-type-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All types', 'loungenie-portal' ); ?></option>
					<?php foreach ( $request_types as $type ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( $is_support ) : ?>
				<div class="lgp-form-group">
					<label for="lgp-ticket-company-filter" class="lgp-label"><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></label>
					<select id="lgp-ticket-company-filter" class="lgp-select">
						<option value="all"><?php esc_html_e( 'All companies', 'loungenie-portal' ); ?></option>
						<?php foreach ( $companies as $company ) : ?>
							<option value="<?php echo esc_attr( $company->id ); ?>"><?php echo esc_html( $company->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
		</div>

		<div class="lgp-table-container" id="lgp-tickets-table-container">
			<div id="lgp-tickets-loading" class="lgp-loading-spinner">
				<div class="lgp-spinner"></div>
				<p><?php esc_html_e( 'Loading tickets...', 'loungenie-portal' ); ?></p>
			</div>

			<div id="lgp-tickets-empty" class="lgp-empty-state lgp-hidden">
				<p><?php esc_html_e( 'No tickets found yet.', 'loungenie-portal' ); ?></p>
			</div>

			<table class="lgp-table lgp-hidden" id="lgp-tickets-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ticket', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
						<?php if ( $is_support ) : ?>
							<th><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
						<?php endif; ?>
						<th><?php esc_html_e( 'Unit', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'loungenie-portal' ); ?></th>
					</tr>
				</thead>
				<tbody id="lgp-tickets-table-body"></tbody>
			</table>
		</div>
	</div>
</div>

<div class="lgp-card lgp-hidden" id="lgp-ticket-detail" data-ticket-id="">
	<div class="lgp-card-header lgp-ticket-detail-header">
		<div>
			<div class="lgp-badge lgp-badge-info" id="lgp-ticket-detail-status">—</div>
			<h3 id="lgp-ticket-detail-title" class="lgp-ticket-detail-title">&nbsp;</h3>
			<p id="lgp-ticket-detail-meta" class="lgp-text-muted lgp-ticket-detail-meta">&nbsp;</p>
		</div>
		<?php if ( $is_support ) : ?>
			<div class="lgp-form-group lgp-ticket-status-form">
				<label for="lgp-ticket-status-select" class="lgp-label"><?php esc_html_e( 'Update Status', 'loungenie-portal' ); ?></label>
				<select id="lgp-ticket-status-select" class="lgp-select">
					<?php foreach ( $statuses as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></option>
					<?php endforeach; ?>
				</select>
			<button type="button" class="lgp-btn lgp-btn-primary lgp-ticket-status-btn" id="lgp-ticket-status-apply">
					<?php esc_html_e( 'Apply', 'loungenie-portal' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
	<div class="lgp-card-body">
		<div class="lgp-grid-auto-250 lgp-ticket-meta lgp-ticket-detail-grid">
			<div>
				<strong><?php esc_html_e( 'Ticket ID', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-id">—</div>
			</div>
			<div>
				<strong><?php esc_html_e( 'Request Type', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-type">—</div>
			</div>
			<div>
				<strong><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-priority">—</div>
			</div>
			<div>
				<strong><?php esc_html_e( 'Unit ID', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-unit">—</div>
			</div>
			<div>
				<strong><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-company">—</div>
			</div>
			<div>
				<strong><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></strong>
				<div id="lgp-ticket-detail-created">—</div>
			</div>
		</div>

		<h4><?php esc_html_e( 'Thread', 'loungenie-portal' ); ?></h4>
		<div id="lgp-ticket-thread" class="lgp-thread lgp-muted-border lgp-ticket-thread"></div>

		<form id="lgp-ticket-reply-form" class="lgp-form" novalidate>
			<div class="lgp-form-group">
				<label for="lgp-ticket-reply-message" class="lgp-label"><?php esc_html_e( 'Reply', 'loungenie-portal' ); ?></label>
				<textarea id="lgp-ticket-reply-message" class="lgp-textarea" rows="4" placeholder="<?php esc_attr_e( 'Type your reply to support...', 'loungenie-portal' ); ?>" required></textarea>
			</div>
			<button type="submit" class="lgp-btn lgp-btn-primary">
				<?php esc_html_e( 'Send Reply', 'loungenie-portal' ); ?>
			</button>
		</form>
	</div>
</div>
