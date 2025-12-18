<?php
// phpcs:ignoreFile
/**
 * Portal Login Landing
 * - Support: one-click Microsoft SSO
 * - Partner: username/password via wp-login
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// URLs
$portal_url        = home_url( '/portal' );
$support_login_url = home_url( '/support-login' );
$partner_login_url = wp_login_url( $portal_url );

// Ensure strict standards mode without invoking theme APIs
echo "<!DOCTYPE html>\n";
?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Portal Login', 'loungenie-portal' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/design-tokens.css' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/portal.css' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/login.css' ); ?>">
</head>
<body class="lgp-portal lgp-screen-center">
	<div class="lgp-card" style="max-width: 720px;">
		<div class="logo-section">
			<?php
			$logo_url = get_option( 'lgp_custom_logo_url' );
			if ( ! $logo_url && has_custom_logo() ) {
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			}
			
			if ( $logo_url ) :
			?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'LounGenie Portal', 'loungenie-portal' ); ?>" class="lgp-login-logo" />
			<?php else : ?>
				<div class="logo-container">
					<div class="logo-loungenie">LounGenie</div>
					<div class="logo-divider">×</div>
					<div class="logo-poolsafe">POOL SAFE Inc.</div>
				</div>
				<div class="logo-subline"><?php esc_html_e( 'world • resorts • cruise • clubs • villa', 'loungenie-portal' ); ?></div>
			<?php endif; ?>
		</div>

		<div class="lgp-login-card" style="grid-template-columns: 1fr 1.2fr; border: none; box-shadow: none; background: transparent;">
			<aside class="lgp-login-sidebar" style="border-radius: var(--lgp-radius-lg) 0 0 var(--lgp-radius-lg);">
				<div class="lgp-badge lgp-badge-premium"><?php esc_html_e( 'Luxury Access', 'loungenie-portal' ); ?></div>
				<h1 class="lgp-login-title"><?php esc_html_e( 'Welcome to LounGenie', 'loungenie-portal' ); ?></h1>
				<p class="lgp-login-subtitle">
					<?php esc_html_e( 'Choose the sign-in method that matches your role. Support uses Microsoft 365, Partners use secure credentials.', 'loungenie-portal' ); ?>
				</p>
				<ul class="lgp-login-list">
					<li><?php esc_html_e( 'Support: one-click Microsoft SSO—no extra password needed.', 'loungenie-portal' ); ?></li>
					<li><?php esc_html_e( 'Partners: secure username & password login.', 'loungenie-portal' ); ?></li>
					<li><?php esc_html_e( 'Automatic redirect to your portal dashboard.', 'loungenie-portal' ); ?></li>
				</ul>
			</aside>
			<div class="lgp-login-main">
				<div>
					<h2><?php esc_html_e( 'Select Your Sign-In Method', 'loungenie-portal' ); ?></h2>
					<p><?php esc_html_e( 'Choose the option that matches your role:', 'loungenie-portal' ); ?></p>
				</div>
				<div class="lgp-login-actions">
					<a class="lgp-btn lgp-btn-secondary lgp-btn-lg" href="<?php echo esc_url( $support_login_url ); ?>">
						<?php esc_html_e( '🔐 Sign in with Microsoft 365 (Support)', 'loungenie-portal' ); ?>
					</a>
					<a class="lgp-btn lgp-btn-primary lgp-btn-lg" href="<?php echo esc_url( $partner_login_url ); ?>">
						<?php esc_html_e( '👤 Partner Login (Username & Password)', 'loungenie-portal' ); ?>
					</a>
					<p class="lgp-text-muted" style="font-size: 13px; margin-top: var(--lgp-space-4); text-align: center;">
						<?php esc_html_e( 'Need help? Use the password reset link on the partner login screen or contact support.', 'loungenie-portal' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
