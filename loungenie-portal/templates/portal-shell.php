<?php
/**
 * Portal Shell Template
 * Main layout structure with header, sidebar, and content area
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$is_support = LGP_Auth::is_support();
$is_partner = LGP_Auth::is_partner();
$section = get_query_var( 'lgp_section', 'dashboard' );

// Determine which dashboard to load
$dashboard_template = $is_support ? 'dashboard-support.php' : 'dashboard-partner.php';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> - Portal</title>
    <?php wp_head(); ?>
</head>
<body class="lgp-portal-body">

<div class="lgp-portal">
    
    <!-- Header -->
    <header class="lgp-header">
        <div class="lgp-logo">
            LounGenie Portal
        </div>
        
        <div class="lgp-header-actions">
            <div class="lgp-notification-icon">
                <span>🔔</span>
                <span class="lgp-notification-badge">3</span>
            </div>
            
            <div class="lgp-user-menu">
                <span><?php echo esc_html( $current_user->display_name ); ?></span>
                <span>(<?php echo $is_support ? esc_html__( 'Support', 'loungenie-portal' ) : esc_html__( 'Partner', 'loungenie-portal' ); ?>)</span>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="lgp-btn lgp-btn-secondary">
                    <?php esc_html_e( 'Logout', 'loungenie-portal' ); ?>
                </a>
            </div>
            
            <button id="lgp-sidebar-toggle" class="lgp-btn lgp-btn-secondary" style="display: none;">
                ☰
            </button>
        </div>
    </header>
    
    <!-- Sidebar Navigation -->
    <aside class="lgp-sidebar">
        <nav>
            <ul class="lgp-nav">
                <?php if ( $is_support ) : ?>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal' ) ); ?>" class="lgp-nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📊</span>
                            <?php esc_html_e( 'Dashboard', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/companies' ) ); ?>" class="lgp-nav-link <?php echo $section === 'companies' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">🏢</span>
                            <?php esc_html_e( 'Companies', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>" class="lgp-nav-link <?php echo $section === 'units' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📦</span>
                            <?php esc_html_e( 'LounGenie Units', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/tickets' ) ); ?>" class="lgp-nav-link <?php echo $section === 'tickets' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">🎫</span>
                            <?php esc_html_e( 'Tickets', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/map' ) ); ?>" class="lgp-nav-link <?php echo $section === 'map' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">🗺️</span>
                            <?php esc_html_e( 'Map View', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/gateways' ) ); ?>" class="lgp-nav-link <?php echo $section === 'gateways' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📡</span>
                            <?php esc_html_e( 'Gateways', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal' ) ); ?>" class="lgp-nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📊</span>
                            <?php esc_html_e( 'Dashboard', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>" class="lgp-nav-link <?php echo $section === 'units' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📦</span>
                            <?php esc_html_e( 'My Units', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/requests' ) ); ?>" class="lgp-nav-link <?php echo $section === 'requests' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📝</span>
                            <?php esc_html_e( 'Service Requests', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                    <li class="lgp-nav-item">
                        <a href="<?php echo esc_url( home_url( '/portal/history' ) ); ?>" class="lgp-nav-link <?php echo $section === 'history' ? 'active' : ''; ?>">
                            <span class="lgp-nav-icon">📋</span>
                            <?php esc_html_e( 'Request History', 'loungenie-portal' ); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content Area -->
    <main class="lgp-main">
        <?php
        // Load the appropriate template based on section
        if ( $section === 'map' && $is_support ) {
            $template_file = LGP_PLUGIN_DIR . 'templates/map-view.php';
        } elseif ( $section === 'gateways' && $is_support ) {
            $template_file = LGP_PLUGIN_DIR . 'templates/gateway-view.php';
        } elseif ( $section === 'units' ) {
            $template_file = LGP_PLUGIN_DIR . 'templates/units-view.php';
        } elseif ( $section === 'dashboard' || empty( $section ) ) {
            $template_file = LGP_PLUGIN_DIR . 'templates/' . $dashboard_template;
        } else {
            $template_file = null;
        }
        
        if ( $template_file && file_exists( $template_file ) ) {
            include $template_file;
        } else {
            echo '<div class="lgp-card">';
            echo '<h1>' . esc_html__( 'Welcome to LounGenie Portal', 'loungenie-portal' ) . '</h1>';
            echo '<p>' . esc_html__( 'This section is under development.', 'loungenie-portal' ) . '</p>';
            echo '</div>';
        }
        ?>
    </main>
    
</div>

<?php wp_footer(); ?>

<style>
@media (max-width: 1024px) {
    #lgp-sidebar-toggle {
        display: block !important;
    }
}
</style>

</body>
</html>
