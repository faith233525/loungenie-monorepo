<?php
/**
 * Support Dashboard Template
 * Shows system-wide statistics and alerts for support users
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// Fetch statistics
$companies_table = $wpdb->prefix . 'lgp_companies';
$units_table = $wpdb->prefix . 'lgp_units';
$tickets_table = $wpdb->prefix . 'lgp_tickets';
$service_requests_table = $wpdb->prefix . 'lgp_service_requests';

$total_companies = $wpdb->get_var( "SELECT COUNT(*) FROM $companies_table" );
$total_units = $wpdb->get_var( "SELECT COUNT(*) FROM $units_table" );
$active_installs = $wpdb->get_var( "SELECT COUNT(*) FROM $service_requests_table WHERE request_type = 'install' AND status = 'active'" );
$open_tickets = $wpdb->get_var( "SELECT COUNT(*) FROM $tickets_table WHERE status = 'open'" );

// Recent tickets
$recent_tickets = $wpdb->get_results(
    "SELECT t.*, sr.request_type, c.name as company_name 
    FROM $tickets_table t 
    LEFT JOIN $service_requests_table sr ON t.service_request_id = sr.id 
    LEFT JOIN $companies_table c ON sr.company_id = c.id 
    ORDER BY t.created_at DESC 
    LIMIT 10"
);

?>

<div class="lgp-dashboard-header">
    <h1><?php esc_html_e( 'Support Dashboard', 'loungenie-portal' ); ?></h1>
    <p><?php esc_html_e( 'Overview of all companies, units, and support tickets', 'loungenie-portal' ); ?></p>
</div>

<!-- Statistics Grid -->
<div class="lgp-stats-grid">
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Total Companies', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $total_companies ?: '0' ); ?></div>
    </div>
    
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Total Units', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $total_units ?: '0' ); ?></div>
    </div>
    
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Active Installs', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $active_installs ?: '0' ); ?></div>
    </div>
    
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Open Tickets', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $open_tickets ?: '0' ); ?></div>
    </div>
</div>

<!-- Recent Tickets -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'Recent Tickets', 'loungenie-portal' ); ?></h2>
    </div>
    <div class="lgp-card-body">
        <?php if ( ! empty( $recent_tickets ) ) : ?>
            <div class="lgp-table-container">
                <table class="lgp-table" id="tickets-table">
                    <thead>
                        <tr>
                            <th class="sortable"><?php esc_html_e( 'Ticket ID', 'loungenie-portal' ); ?></th>
                            <th class="sortable"><?php esc_html_e( 'Company', 'loungenie-portal' ); ?></th>
                            <th class="sortable"><?php esc_html_e( 'Request Type', 'loungenie-portal' ); ?></th>
                            <th class="sortable"><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
                            <th class="sortable"><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'loungenie-portal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent_tickets as $ticket ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $ticket->id ); ?></td>
                                <td><?php echo esc_html( $ticket->company_name ?? __( 'N/A', 'loungenie-portal' ) ); ?></td>
                                <td><?php echo esc_html( ucfirst( $ticket->request_type ?? 'general' ) ); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'info';
                                    if ( $ticket->status === 'open' ) {
                                        $status_class = 'warning';
                                    } elseif ( $ticket->status === 'closed' ) {
                                        $status_class = 'success';
                                    }
                                    ?>
                                    <span class="lgp-badge lgp-badge-<?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( ucfirst( $ticket->status ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
                                <td>
                                    <a href="#" class="lgp-btn lgp-btn-primary"><?php esc_html_e( 'View', 'loungenie-portal' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No tickets found.', 'loungenie-portal' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- System Alerts -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'System Alerts', 'loungenie-portal' ); ?></h2>
    </div>
    <div class="lgp-card-body">
        <div class="lgp-alert lgp-alert-info">
            <p><?php esc_html_e( 'All systems operational', 'loungenie-portal' ); ?></p>
        </div>
    </div>
</div>
