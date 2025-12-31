<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>LounGenie Portal</title>
    
    <!-- Plugin-controlled CSS - NO THEME DEPENDENCIES -->
    <link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/lgp-core-tokens.css' ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/portal-shell.css' ); ?>">
    
    <?php
    /**
     * Only include essential WordPress hooks
     * NO get_header() or theme functions
     */
    do_action( 'lgp_portal_head' );
    ?>
</head>
<body class="lgp-portal">
    
    <?php
    $current_user = wp_get_current_user();
    $is_support = in_array( 'lg_support', $current_user->roles, true );
    $is_partner = in_array( 'lg_partner', $current_user->roles, true );
    $is_admin = in_array( 'administrator', $current_user->roles, true );
    ?>
    
    <!-- Portal Container - Plugin Controlled Layout -->
    <div class="lgp-portal-container">
        
        <!-- Header - Plugin Authority -->
        <header class="lgp-header">
            <div class="lgp-header-logo">
                <span class="lgp-logo-text" style="color: var(--lg-structure);">LounGenie</span>
                <span class="lgp-logo-separator">×</span>
                <span class="lgp-logo-text" style="color: var(--lg-primary);">MyPOOLSAFE</span>
            </div>
            
            <div class="lgp-header-actions">
                <div class="lgp-user-info">
                    <span class="lgp-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                    <span class="lgp-user-role">
                        <?php
                        if ( $is_support ) {
                            echo 'Support';
                        } elseif ( $is_partner ) {
                            echo 'Partner';
                        } elseif ( $is_admin ) {
                            echo 'Administrator';
                        }
                        ?>
                    </span>
                </div>
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/portal' ) ) ); ?>" class="lgp-btn lgp-btn-secondary">
                    Logout
                </a>
            </div>
        </header>
        
        <!-- Sidebar Navigation - Plugin Authority -->
        <aside class="lgp-sidebar" style="background-color: var(--lg-structure);">
            <nav class="lgp-nav">
                <?php if ( $is_support || $is_admin ) : ?>
                    <a href="<?php echo esc_url( home_url( '/portal' ) ); ?>" class="lgp-nav-item active">
                        <span class="lgp-nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/tickets' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🎫</span>
                        <span>Tickets</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/companies' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🏢</span>
                        <span>Companies</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🔧</span>
                        <span>Units</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/map' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🗺️</span>
                        <span>Map View</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/knowledge' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">📚</span>
                        <span>Knowledge Center</span>
                    </a>
                <?php elseif ( $is_partner ) : ?>
                    <a href="<?php echo esc_url( home_url( '/portal' ) ); ?>" class="lgp-nav-item active">
                        <span class="lgp-nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/tickets' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🎫</span>
                        <span>My Tickets</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">🔧</span>
                        <span>My Units</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/knowledge' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">📚</span>
                        <span>Knowledge Center</span>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/portal/profile' ) ); ?>" class="lgp-nav-item">
                        <span class="lgp-nav-icon">👤</span>
                        <span>Company Profile</span>
                    </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Main Content Area - Plugin Authority -->
        <main class="lgp-main-content" id="main-content">
            <?php
            /**
             * Load appropriate dashboard based on role
             * NO THEME TEMPLATES - Plugin templates only
             */
            if ( $is_support || $is_admin ) {
                include LGP_PLUGIN_DIR . 'templates/dashboard-support.php';
            } elseif ( $is_partner ) {
                include LGP_PLUGIN_DIR . 'templates/dashboard-partner.php';
            } else {
                echo '<div class="lgp-error">';
                echo '<h1>Access Denied</h1>';
                echo '<p>You do not have permission to access this portal.</p>';
                echo '</div>';
            }
            ?>
        </main>
        
    </div>
    
    <!-- Plugin-controlled JavaScript - NO THEME DEPENDENCIES -->
    <script src="<?php echo esc_url( LGP_ASSETS_URL . 'js/portal-init.js' ); ?>"></script>
    
    <?php
    /**
     * Only include essential WordPress hooks
     * NO get_footer() or theme functions
     */
    do_action( 'lgp_portal_footer' );
    ?>
</body>
</html>
