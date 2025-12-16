<?php
/**
 * Unified Company Profile View Template
 * Consolidated view showing company data, tickets, units, gateways, training videos, and contract metadata
 * Partners see read-only view of their company; Support can view and edit any company
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// Get company ID from request parameter
$company_id = isset( $_GET['company_id'] ) ? (int) $_GET['company_id'] : LGP_Auth::get_user_company_id();

if ( ! $company_id ) {
    echo '<div class="lgp-card">';
    echo '<p>' . esc_html__( 'No company found. Please contact support.', 'loungenie-portal' ) . '</p>';
    echo '</div>';
    return;
}

// Authorization check
$is_support = LGP_Auth::is_support();
$user_company_id = LGP_Auth::get_user_company_id();
$can_edit = $is_support || ( $company_id === $user_company_id );

if ( ! $is_support && $company_id !== $user_company_id ) {
    echo '<div class="lgp-card">';
    echo '<p>' . esc_html__( 'You do not have access to this company.', 'loungenie-portal' ) . '</p>';
    echo '</div>';
    return;
}

// Fetch company information
$companies_table = $wpdb->prefix . 'lgp_companies';
$units_table = $wpdb->prefix . 'lgp_units';
$tickets_table = $wpdb->prefix . 'lgp_tickets';
$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
$gateways_table = $wpdb->prefix . 'lgp_gateways';
$training_videos_table = $wpdb->prefix . 'lgp_training_videos';
$mgmt_companies_table = $wpdb->prefix . 'lgp_management_companies';

$company = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $companies_table WHERE id = %d", $company_id ) );

if ( ! $company ) {
    echo '<div class="lgp-card">';
    echo '<p>' . esc_html__( 'Company not found.', 'loungenie-portal' ) . '</p>';
    echo '</div>';
    return;
}

// Fetch related data
$units = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $units_table WHERE company_id = %d ORDER BY id DESC", $company_id ) );
$gateways = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $gateways_table WHERE company_id = %d ORDER BY id DESC", $company_id ) );
$tickets = $wpdb->get_results( $wpdb->prepare(
    "SELECT t.*, sr.request_type, sr.priority, sr.status as request_status FROM $tickets_table t 
    LEFT JOIN $service_requests_table sr ON t.service_request_id = sr.id 
    WHERE sr.company_id = %d ORDER BY t.created_at DESC LIMIT 10",
    $company_id
) );

// Count metrics
$total_units = count( $units );
$total_gateways = count( $gateways );
$open_tickets = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM $tickets_table t 
    LEFT JOIN $service_requests_table sr ON t.service_request_id = sr.id 
    WHERE sr.company_id = %d AND t.status = 'open'",
    $company_id
) );
$gateways_with_call_button = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM $gateways_table WHERE company_id = %d AND call_button = 1",
    $company_id
) );

// Get management company if exists
$management_company = null;
if ( $company->management_company_id ) {
    $management_company = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $mgmt_companies_table WHERE id = %d",
        $company->management_company_id
    ) );
}

?>

<div class="lgp-dashboard-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><?php echo esc_html( $company->name ); ?></h1>
            <p><?php esc_html_e( 'Company Profile & Management', 'loungenie-portal' ); ?></p>
        </div>
        <?php if ( $can_edit && $is_support ) : ?>
            <a href="#" class="lgp-btn lgp-btn-primary" onclick="alert('Edit company feature coming soon'); return false;">
                <?php esc_html_e( 'Edit Company', 'loungenie-portal' ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Metrics Grid -->
<div class="lgp-stats-grid">
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'LounGenie Units', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $total_units ); ?></div>
    </div>
    
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Gateways', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $total_gateways ); ?></div>
        <small><?php echo esc_html( $gateways_with_call_button ); ?> with call button</small>
    </div>
    
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Open Tickets', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value"><?php echo esc_html( $open_tickets ?: '0' ); ?></div>
    </div>
    
    <?php if ( ! empty( $company->contract_type ) ) : ?>
    <div class="lgp-stat-card">
        <div class="lgp-stat-label"><?php esc_html_e( 'Contract Type', 'loungenie-portal' ); ?></div>
        <div class="lgp-stat-value" style="font-size: 0.9em;">
            <?php echo esc_html( ucfirst( str_replace( '_', ' ', $company->contract_type ) ) ); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Company Information Card -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'Company Information', 'loungenie-portal' ); ?></h2>
    </div>
    <div class="lgp-card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-lg);">
            <div>
                <h3><?php esc_html_e( 'Basic Information', 'loungenie-portal' ); ?></h3>
                <p><strong><?php esc_html_e( 'Company Name:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->name ); ?></p>
                <p><strong><?php esc_html_e( 'Address:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->address ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'State:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->state ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Venue Type:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->venue_type ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
            </div>
            
            <div>
                <h3><?php esc_html_e( 'Primary Contact', 'loungenie-portal' ); ?></h3>
                <p><strong><?php esc_html_e( 'Contact Name:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->contact_name ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Email:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->contact_email ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Phone:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->contact_phone ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
            </div>
            
            <?php if ( ! empty( $company->secondary_contact_name ) || ! empty( $company->secondary_contact_email ) ) : ?>
            <div>
                <h3><?php esc_html_e( 'Secondary Contact', 'loungenie-portal' ); ?></h3>
                <p><strong><?php esc_html_e( 'Contact Name:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->secondary_contact_name ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Email:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->secondary_contact_email ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Phone:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $company->secondary_contact_phone ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $company->contract_type ) || ! empty( $company->contract_start_date ) ) : ?>
            <div>
                <h3><?php esc_html_e( 'Contract Information', 'loungenie-portal' ); ?></h3>
                <p><strong><?php esc_html_e( 'Contract Type:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( ucfirst( str_replace( '_', ' ', $company->contract_type ?? __( 'N/A', 'loungenie-portal' ) ) ) ); ?></p>
                <p><strong><?php esc_html_e( 'Start Date:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo $company->contract_start_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $company->contract_start_date ) ) ) : esc_html__( 'N/A', 'loungenie-portal' ); ?></p>
                <p><strong><?php esc_html_e( 'End Date:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo $company->contract_end_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $company->contract_end_date ) ) ) : esc_html__( 'N/A', 'loungenie-portal' ); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ( $management_company ) : ?>
            <div>
                <h3><?php esc_html_e( 'Management Company', 'loungenie-portal' ); ?></h3>
                <p><strong><?php esc_html_e( 'Company Name:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $management_company->name ); ?></p>
                <p><strong><?php esc_html_e( 'Contact:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $management_company->contact_name ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
                <p><strong><?php esc_html_e( 'Email:', 'loungenie-portal' ); ?></strong><br>
                   <?php echo esc_html( $management_company->contact_email ?? __( 'N/A', 'loungenie-portal' ) ); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Units Section -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'LounGenie Units', 'loungenie-portal' ); ?> (<?php echo esc_html( count( $units ) ); ?>)</h2>
    </div>
    <div class="lgp-card-body">
        <?php if ( ! empty( $units ) ) : ?>
            <div class="lgp-table-container" style="max-height: 400px; overflow-y: auto;">
                <table class="lgp-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Address', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Lock Type', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Color', 'loungenie-portal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $units as $unit ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $unit->id ); ?></td>
                                <td><?php echo esc_html( substr( $unit->address ?? 'N/A', 0, 40 ) ); ?></td>
                                <td><?php echo esc_html( $unit->lock_type ?? 'N/A' ); ?></td>
                                <td><span class="lgp-badge lgp-badge-info"><?php echo esc_html( ucfirst( $unit->status ?? 'unknown' ) ); ?></span></td>
                                <td><?php echo esc_html( $unit->color_tag ?? 'N/A' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No units found for this company.', 'loungenie-portal' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Gateways Section (Support Only) -->
<?php if ( $is_support ) : ?>
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'Gateways', 'loungenie-portal' ); ?> (<?php echo esc_html( count( $gateways ) ); ?>)</h2>
    </div>
    <div class="lgp-card-body">
        <?php if ( ! empty( $gateways ) ) : ?>
            <div class="lgp-table-container" style="max-height: 400px; overflow-y: auto;">
                <table class="lgp-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Channel', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Address', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Unit Capacity', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Call Button', 'loungenie-portal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $gateways as $gateway ) : ?>
                            <tr <?php echo $gateway->call_button ? 'style="background-color: #ffffcc;"' : ''; ?>>
                                <td>#<?php echo esc_html( $gateway->id ); ?></td>
                                <td><?php echo esc_html( $gateway->channel_number ?? 'N/A' ); ?></td>
                                <td><?php echo esc_html( $gateway->gateway_address ?? 'N/A' ); ?></td>
                                <td><?php echo esc_html( $gateway->unit_capacity ?? '0' ); ?></td>
                                <td><?php echo $gateway->call_button ? '✓' : '✗'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No gateways found for this company.', 'loungenie-portal' ); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tickets Section -->
<div class="lgp-card">
    <div class="lgp-card-header">
        <h2 class="lgp-card-title"><?php esc_html_e( 'Recent Tickets', 'loungenie-portal' ); ?></h2>
    </div>
    <div class="lgp-card-body">
        <?php if ( ! empty( $tickets ) ) : ?>
            <div class="lgp-table-container" style="max-height: 400px; overflow-y: auto;">
                <table class="lgp-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Ticket ID', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Priority', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'loungenie-portal' ); ?></th>
                            <th><?php esc_html_e( 'Created', 'loungenie-portal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tickets as $ticket ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $ticket->id ); ?></td>
                                <td><?php echo esc_html( ucfirst( $ticket->request_type ?? 'general' ) ); ?></td>
                                <td>
                                    <?php
                                    $priority_class = 'info';
                                    if ( $ticket->priority === 'high' ) {
                                        $priority_class = 'warning';
                                    } elseif ( $ticket->priority === 'urgent' ) {
                                        $priority_class = 'error';
                                    }
                                    ?>
                                    <span class="lgp-badge lgp-badge-<?php echo esc_attr( $priority_class ); ?>">
                                        <?php echo esc_html( ucfirst( $ticket->priority ?? 'normal' ) ); ?>
                                    </span>
                                </td>
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
                                        <?php echo esc_html( ucfirst( $ticket->status ?? 'unknown' ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No tickets found for this company.', 'loungenie-portal' ); ?></p>
        <?php endif; ?>
    </div>
</div>

