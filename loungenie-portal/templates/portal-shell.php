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
$is_support   = LGP_Auth::is_support();
$is_partner   = LGP_Auth::is_partner();
$section      = get_query_var( 'lgp_section', 'dashboard' );

// Determine which dashboard to load
$dashboard_template = $is_support ? 'dashboard-support.php' : 'dashboard-partner.php';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> - <?php esc_html_e( 'Portal', 'loungenie-portal' ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="lgp-portal-body" data-role="<?php echo $is_support ? 'support' : 'partner'; ?>">

<!-- Skip to main content for accessibility -->
<a href="#main-content" class="lgp-skip-link"><?php esc_html_e( 'Skip to main content', 'loungenie-portal' ); ?></a>

<div class="lgp-portal lgp-container" data-role="<?php echo $is_support ? 'support' : 'partner'; ?>">
	
	<!-- Header with HubSpot-style spacing -->
	<header class="lgp-header">
		<!-- Logo with Hover Effects -->
		<div class="lgp-logo">
			<?php
			// Check for WordPress custom logo
			$logo_url = get_option( 'lgp_custom_logo_url' );
			if ( ! $logo_url && has_custom_logo() ) {
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			}
			
			if ( $logo_url ) :
			?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'LounGenie Portal', 'loungenie-portal' ); ?>" class="lgp-logo-image" />
			<?php else : ?>
				<span class="lgp-logo-loungenie">LounGenie</span>
				<span class="lgp-logo-separator">×</span>
				<span class="lgp-logo-poolsafe">MyPOOLSAFE</span>
				<span class="lgp-logo-suffix">Inc.</span>
			<?php endif; ?>
		</div>
		
		<div class="lgp-header-actions">
			<!-- Notification Icon with Hover -->
			<div class="lgp-notification-icon">
				<span class="lgp-notification-bell">🔔</span>
				<span class="lgp-notification-badge">3</span>
			</div>
			
			<!-- User Menu -->
			<div class="lgp-user-menu">
				<span class="lgp-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
				<span class="lgp-user-role">(<?php echo $is_support ? esc_html__( 'Support', 'loungenie-portal' ) : esc_html__( 'Partner', 'loungenie-portal' ); ?>)</span>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="lgp-btn lgp-btn-secondary lgp-logout-btn">
					<?php esc_html_e( 'Logout', 'loungenie-portal' ); ?>
				</a>
			</div>
			
			<!-- Mobile Toggle -->
			<button id="lgp-sidebar-toggle" class="lgp-btn lgp-btn-secondary lgp-sidebar-toggle-btn">
				☰
			</button>
		</div>
	</header>
	
	<!-- Sidebar Navigation with HubSpot Spacing -->
	<aside class="lgp-sidebar" role="navigation" aria-label="<?php esc_attr_e( 'Main Navigation', 'loungenie-portal' ); ?>">
		<nav>
			<ul class="lgp-nav" role="menu">
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
						<a href="<?php echo esc_url( home_url( '/portal/units' ) ); ?>" class="lgp-nav-link <?php echo $section === 'units' || $section === 'gateways' ? 'active' : ''; ?>">
							<span class="lgp-nav-icon">📦</span>
							<?php esc_html_e( 'Units & Gateways', 'loungenie-portal' ); ?>
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
						<a href="<?php echo esc_url( home_url( '/portal/training' ) ); ?>" class="lgp-nav-link <?php echo $section === 'training' ? 'active' : ''; ?>">
							<span class="lgp-nav-icon">🎓</span>
							<?php esc_html_e( 'Training Videos', 'loungenie-portal' ); ?>
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
					<li class="lgp-nav-item">
						<a href="<?php echo esc_url( home_url( '/portal/training' ) ); ?>" class="lgp-nav-link <?php echo $section === 'training' ? 'active' : ''; ?>">
							<span class="lgp-nav-icon">🎓</span>
							<?php esc_html_e( 'Training Videos', 'loungenie-portal' ); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	</aside>
	
	<!-- Main Content Area with HubSpot Spacing -->
	<main id="main-content" class="lgp-main" role="main" aria-label="<?php esc_attr_e( 'Main Content', 'loungenie-portal' ); ?>">
		<div class="lgp-content-wrapper">
			<?php
			// Load the appropriate template based on section
			if ( $section === 'map' && $is_support ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/map-view.php';
			} elseif ( $section === 'gateways' && $is_support ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/gateway-view.php';
			} elseif ( $section === 'training' ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/training-view.php';
			} elseif ( in_array( $section, array( 'tickets', 'requests', 'history' ), true ) ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/tickets-view.php';
			} elseif ( $section === 'units' ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/units-view.php';
			} elseif ( strpos( $section, 'company-profile' ) === 0 ) {
				$template_file = LGP_PLUGIN_DIR . 'templates/company-profile.php';
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
		</div>
	</main>
	
</div>

<?php wp_footer(); ?>


</body>
</html>
