<?php
/**
 * Tickets view template
 *
 * Presents the ticket list, filters, and ticket detail panel for both
 * support (multi-tenant) and partner (scoped) roles.
 *
 * @package LounGenie Portal
 */

namespace LounGenie\Portal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine role context.
$is_support    = LGP_Auth::is_support();
$company_id    = $is_support ? 0 : (int) LGP_Auth::get_user_company_id();
$rest_url      = esc_url_raw( rest_url( 'lgp/v1/' ) );
$rest_nonce    = wp_create_nonce( 'wp_rest' );
$company_name  = '';
$companies     = array();

global $wpdb;

// Partner context: fetch company name safely.
if ( ! $is_support && $company_id > 0 ) {
	$companies_table = $wpdb->prefix . 'lgp_companies';
	$company_row     = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$companies_table} WHERE id = %d", $company_id ) );
	if ( null === $company_row ) {
		error_log( sprintf( 'LGP: company lookup failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error ) );
	} else {
		$company_name = (string) $company_row->name;
	}
}

// Support context: fetch companies for filter dropdown.
if ( $is_support ) {
	$companies_table = $wpdb->prefix . 'lgp_companies';
	$companies       = $wpdb->get_results( "SELECT id, name FROM {$companies_table} ORDER BY name ASC" );
	if ( null === $companies ) {
		error_log( sprintf( 'LGP: company list query failed in %s:%d - %s', __FILE__, __LINE__, $wpdb->last_error ) );
		$companies = array();
	}
}

// Allow filters to be overridden via hooks defined in main plugin.
$ticket_statuses   = function_exists( 'lgp_get_ticket_statuses' ) ? (array) lgp_get_ticket_statuses() : array( 'open', 'pending', 'closed' );
$ticket_priorities = function_exists( 'lgp_get_ticket_priorities' ) ? (array) lgp_get_ticket_priorities() : array( 'urgent', 'high', 'normal' );
$ticket_types      = function_exists( 'lgp_get_request_types' ) ? (array) lgp_get_request_types() : array( 'install', 'maintenance', 'repair', 'update', 'general' );

?>

<div id="lgp-tickets-view"
     class="lgp-layout"
     data-rest-url="<?php echo esc_url( $rest_url ); ?>"
     data-rest-nonce="<?php echo esc_attr( $rest_nonce ); ?>"
     data-is-support="<?php echo $is_support ? '1' : '0'; ?>"
     data-company-name="<?php echo esc_attr( $company_name ); ?>">

	<header class="lgp-page-header">
		<h1 class="lgp-page-title"><?php esc_html_e( 'Tickets', 'loungenie-portal' ); ?></h1>
		<p class="lgp-text-muted">
			<?php echo $is_support
				? esc_html__( 'View and manage all partner tickets.', 'loungenie-portal' )
				: esc_html__( 'View and manage your company tickets.', 'loungenie-portal' ); ?>
		</p>
	</header>

	<section class="lgp-card lgp-card-spacious" aria-labelledby="lgp-ticket-filters-heading">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title" id="lgp-ticket-filters-heading"><?php esc_html_e( 'Filters', 'loungenie-portal' ); ?></h2>
			<button id="lgp-refresh-tickets" class="lgp-btn lgp-btn-secondary lgp-btn-sm" type="button"><?php esc_html_e( 'Refresh', 'loungenie-portal' ); ?></button>
		</div>
		<div class="lgp-card-body lgp-filters-grid">
			<label class="lgp-field">
				<span class="lgp-label"><?php esc_html_e( 'Search', 'loungenie-portal' ); ?></span>
				<input type="search" id="lgp-ticket-search" class="lgp-input" placeholder="<?php esc_attr_e( 'Search tickets…', 'loungenie-portal' ); ?>" autocomplete="off" spellcheck="true">
			</label>

			<label class="lgp-field">
				<span class="lgp-label"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></span>
				<select id="lgp-ticket-status-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
					<?php foreach ( $ticket_statuses as $status ) : ?>
						<option value="<?php echo esc_attr( strtolower( $status ) ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="lgp-field">
				<span class="lgp-label"><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></span>
				<select id="lgp-ticket-priority-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
					<?php foreach ( $ticket_priorities as $priority ) : ?>
						<option value="<?php echo esc_attr( strtolower( $priority ) ); ?>"><?php echo esc_html( ucfirst( $priority ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="lgp-field">
				<span class="lgp-label"><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></span>
				<select id="lgp-ticket-type-filter" class="lgp-select">
					<option value="all"><?php esc_html_e( 'All', 'loungenie-portal' ); ?></option>
					<?php foreach ( $ticket_types as $type ) : ?>
						<option value="<?php echo esc_attr( strtolower( $type ) ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<?php if ( $is_support ) : ?>
				<label class="lgp-field">
					<span class="lgp-label"><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></span>
					<select id="lgp-ticket-company-filter" class="lgp-select">
						<option value="all"><?php esc_html_e( 'All companies', 'loungenie-portal' ); ?></option>
						<?php foreach ( $companies as $company ) : ?>
							<option value="<?php echo esc_attr( $company->id ); ?>"><?php echo esc_html( $company->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
		</div>
	</section>

	<section class="lgp-card lgp-card-spacious" aria-labelledby="lgp-ticket-table-heading">
		<div class="lgp-card-header">
			<h2 class="lgp-card-title" id="lgp-ticket-table-heading"><?php esc_html_e( 'Tickets', 'loungenie-portal' ); ?></h2>
		</div>
		<div class="lgp-card-body">
			<div id="lgp-tickets-loading" class="lgp-loading" aria-live="polite"><?php esc_html_e( 'Loading tickets…', 'loungenie-portal' ); ?></div>
			<div id="lgp-tickets-empty" class="lgp-empty lgp-hidden" aria-live="polite">
				<p class="lgp-text-muted"><?php esc_html_e( 'No tickets found.', 'loungenie-portal' ); ?></p>
			</div>
			<table id="lgp-tickets-table" class="lgp-table lgp-hidden">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
						<?php if ( $is_support ) : ?>
							<th><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
						<?php endif; ?>
						<th><?php esc_html_e( 'Unit', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'loungenie-portal' ); ?></th>
						<th><?php esc_html_e( 'Action', 'loungenie-portal' ); ?></th>
					</tr>
				</thead>
				<tbody id="lgp-tickets-table-body"></tbody>
			</table>
		</div>
	</section>

	<section id="lgp-ticket-detail" class="lgp-card lgp-card-spacious lgp-hidden" aria-labelledby="lgp-ticket-detail-title">
		<div class="lgp-card-header">
			<div>
				<p class="lgp-overline" id="lgp-ticket-detail-id"></p>
				<h2 class="lgp-card-title" id="lgp-ticket-detail-title"></h2>
				<p class="lgp-text-muted" id="lgp-ticket-detail-meta"></p>
			</div>
			<div id="lgp-ticket-detail-status"></div>
		</div>
		<div class="lgp-card-body lgp-ticket-detail-grid">
			<div class="lgp-detail-meta">
				<dl class="lgp-definition-list">
					<dt><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></dt>
					<dd id="lgp-ticket-detail-type"></dd>
					<dt><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></dt>
					<dd id="lgp-ticket-detail-priority"></dd>
					<dt><?php esc_html_e( 'Unit', 'loungenie-portal' ); ?></dt>
					<dd id="lgp-ticket-detail-unit"></dd>
					<dt><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></dt>
					<dd id="lgp-ticket-detail-company"></dd>
					<dt><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></dt>
					<dd id="lgp-ticket-detail-created"></dd>
				</dl>
				<?php if ( $is_support ) : ?>
					<div class="lgp-field" style="margin-top:16px;">
						<label for="lgp-ticket-status-select" class="lgp-label"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></label>
						<select id="lgp-ticket-status-select" class="lgp-select">
							<?php foreach ( $ticket_statuses as $status ) : ?>
								<option value="<?php echo esc_attr( strtolower( $status ) ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<button id="lgp-ticket-status-apply" class="lgp-btn lgp-btn-primary lgp-btn-sm" type="button" style="margin-top:8px;">
							<?php esc_html_e( 'Update Status', 'loungenie-portal' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<div class="lgp-detail-thread">
				<h3 class="lgp-section-title"><?php esc_html_e( 'Thread', 'loungenie-portal' ); ?></h3>
				<div id="lgp-ticket-thread" class="lgp-thread" aria-live="polite"></div>

				<form id="lgp-ticket-reply-form" class="lgp-form" novalidate>
					<label class="lgp-field">
						<span class="lgp-label"><?php esc_html_e( 'Add Reply', 'loungenie-portal' ); ?></span>
						<textarea id="lgp-ticket-reply-message" class="lgp-textarea" rows="4" required spellcheck="true" aria-required="true"></textarea>
					</label>
					<button type="submit" class="lgp-btn lgp-btn-primary"><?php esc_html_e( 'Send Reply', 'loungenie-portal' ); ?></button>
				</form>
			</div>
		</div>
	</section>
</div>
