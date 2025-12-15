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

// Top 5 Metrics - Most used colors
$top_colors = $wpdb->get_results(
    "SELECT color_tag, COUNT(*) as count 
    FROM $units_table 
    WHERE color_tag IS NOT NULL AND color_tag != '' 
    GROUP BY color_tag 
    ORDER BY count DESC 
    LIMIT 5"
);

// Top 5 Metrics - Most used lock brands
$top_lock_brands = $wpdb->get_results(
    "SELECT lock_brand, COUNT(*) as count 
    FROM $units_table 
    WHERE lock_brand IS NOT NULL AND lock_brand != '' 
    GROUP BY lock_brand 
    ORDER BY count DESC 
    LIMIT 5"
);

// Top 5 Metrics - Venue types
$top_venues = $wpdb->get_results(
    "SELECT venue_type, COUNT(*) as count 
    FROM $units_table 
    WHERE venue_type IS NOT NULL AND venue_type != '' 
    GROUP BY venue_type 
    ORDER BY count DESC 
    LIMIT 5"
);

// Season breakdown
$seasonal_units = $wpdb->get_var( "SELECT COUNT(*) FROM $units_table WHERE season = 'seasonal'" );
$yearround_units = $wpdb->get_var( "SELECT COUNT(*) FROM $units_table WHERE season = 'year-round'" );

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

<!-- Top 5 Metrics -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'Top 5 Analytics', 'loungenie-portal' ); ?></h2>
    </div>
    <div class="lgp-card-body">
        <div class="lgp-top-metrics-grid">
            <!-- Top Colors -->
            <div class="lgp-top-metric">
                <h3 class="lgp-metric-title"><?php esc_html_e( 'Top Colors', 'loungenie-portal' ); ?></h3>
                <?php if ( ! empty( $top_colors ) ) : ?>
                    <ul class="lgp-metric-list">
                        <?php 
                        $color_map = array(
                            'yellow' => '#FFD700',
                            'red' => '#DC143C',
                            'classic blue' => '#4169E1',
                            'ice blue' => '#87CEEB',
                        );
                        foreach ( $top_colors as $color ) : 
                            $color_lower = strtolower( $color->color_tag );
                            $color_hex = isset( $color_map[$color_lower] ) ? $color_map[$color_lower] : '#999';
                        ?>
                            <li class="lgp-metric-item">
                                <span class="lgp-metric-label">
                                    <span class="lgp-color-indicator" style="background-color: <?php echo esc_attr( $color_hex ); ?>"></span>
                                    <?php echo esc_html( $color->color_tag ); ?>
                                </span>
                                <span class="lgp-metric-value"><?php echo esc_html( $color->count ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="lgp-empty-state"><?php esc_html_e( 'No data available', 'loungenie-portal' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Top Lock Brands -->
            <div class="lgp-top-metric">
                <h3 class="lgp-metric-title"><?php esc_html_e( 'Top Lock Brands', 'loungenie-portal' ); ?></h3>
                <?php if ( ! empty( $top_lock_brands ) ) : ?>
                    <ul class="lgp-metric-list">
                        <?php foreach ( $top_lock_brands as $brand ) : ?>
                            <li class="lgp-metric-item">
                                <span class="lgp-metric-label"><?php echo esc_html( $brand->lock_brand ); ?></span>
                                <span class="lgp-metric-value"><?php echo esc_html( $brand->count ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="lgp-empty-state"><?php esc_html_e( 'No data available', 'loungenie-portal' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Top Venues -->
            <div class="lgp-top-metric">
                <h3 class="lgp-metric-title"><?php esc_html_e( 'Top Venues', 'loungenie-portal' ); ?></h3>
                <?php if ( ! empty( $top_venues ) ) : ?>
                    <ul class="lgp-metric-list">
                        <?php foreach ( $top_venues as $venue ) : ?>
                            <li class="lgp-metric-item">
                                <span class="lgp-metric-label"><?php echo esc_html( $venue->venue_type ); ?></span>
                                <span class="lgp-metric-value"><?php echo esc_html( $venue->count ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="lgp-empty-state"><?php esc_html_e( 'No data available', 'loungenie-portal' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Season Breakdown -->
            <div class="lgp-top-metric">
                <h3 class="lgp-metric-title"><?php esc_html_e( 'Season Breakdown', 'loungenie-portal' ); ?></h3>
                <ul class="lgp-metric-list">
                    <li class="lgp-metric-item">
                        <span class="lgp-metric-label"><?php esc_html_e( 'Seasonal', 'loungenie-portal' ); ?></span>
                        <span class="lgp-metric-value"><?php echo esc_html( $seasonal_units ?: '0' ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?></span>
                    </li>
                    <li class="lgp-metric-item">
                        <span class="lgp-metric-label"><?php esc_html_e( 'Year-Round', 'loungenie-portal' ); ?></span>
                        <span class="lgp-metric-value"><?php echo esc_html( $yearround_units ?: '0' ); ?> <?php esc_html_e( 'units', 'loungenie-portal' ); ?></span>
                    </li>
                </ul>
            </div>
        </div>
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
