<?php
// phpcs:ignoreFile
/**
 * Support Team Login Form
 * Custom branded login for Support Team members (no WordPress branding)
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle login
$error_message      = '';
$redirect_to        = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/portal' );
$partner_login_url  = home_url( '/partner-login' );

if ( isset( $_POST['lgp_support_login'] ) && isset( $_POST['lgp_login_nonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lgp_login_nonce'] ) ), 'lgp_support_login_action' ) ) {
		$username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
		$password = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '';
		$remember = isset( $_POST['rememberme'] );

		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( ! is_wp_error( $user ) ) {
			wp_safe_redirect( $redirect_to );
			exit;
		} else {
			$error_message = __( 'Invalid username or password. Please try again.', 'loungenie-portal' );
		}
	}
}

// If already logged in, redirect to portal
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/portal' ) );
	exit;
}

echo "<!DOCTYPE html>\n";
?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	 <title><?php esc_html_e( 'Support Team Login', 'loungenie-portal' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/design-tokens.css' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/login.css' ); ?>">
</head>
<body class="lgp-login-page lgp-screen-center">
	<div class="lgp-card lgp-max-w-520">
		<div class="logo-section">
			<?php
			// Check for WordPress custom logo
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
			<?php endif; ?>
			<div class="logo-subline"><?php esc_html_e( 'world • resorts • cruise • clubs • villa', 'loungenie-portal' ); ?></div>
		</div>

		<h1 class="lgp-login-heading"><?php esc_html_e( 'Support Team Login', 'loungenie-portal' ); ?></h1>
		<p class="lgp-login-subtitle"><?php esc_html_e( 'Sign in to manage companies, tickets, and training.', 'loungenie-portal' ); ?></p>

		<?php if ( ! empty( $error_message ) ) : ?>
			<div class="lgp-alert lgp-alert-error" role="alert">
				<?php echo esc_html( $error_message ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'lgp_support_login_action', 'lgp_login_nonce' ); ?>
			<div class="lgp-form-group">
				<label for="support_user_login" class="lgp-label"><?php esc_html_e( 'Username', 'loungenie-portal' ); ?></label>
				<input type="text" id="support_user_login" name="log" class="lgp-input" autocomplete="username" required />
			</div>

			<div class="lgp-form-group">
				<label for="support_user_pass" class="lgp-label"><?php esc_html_e( 'Password', 'loungenie-portal' ); ?></label>
				<input type="password" id="support_user_pass" name="pwd" class="lgp-input" autocomplete="current-password" required />
			</div>

			<div class="lgp-form-group">
				<label for="support_rememberme" class="lgp-checkbox-label">
					<input type="checkbox" id="support_rememberme" name="rememberme" class="lgp-checkbox" />
					<span><?php esc_html_e( 'Remember me', 'loungenie-portal' ); ?></span>
				</label>
			</div>

			<input type="hidden" name="lgp_support_login" value="1" />

			<button type="submit" class="lgp-btn lgp-btn-primary lgp-btn-lg lgp-w-full">
				<?php esc_html_e( 'Sign In', 'loungenie-portal' ); ?>
			</button>
		</form>

		<div class="lgp-login-footer">
			<p class="lgp-text-sm lgp-text-muted"><?php esc_html_e( 'Are you a Partner Company member?', 'loungenie-portal' ); ?></p>
			<a href="<?php echo esc_url( $partner_login_url ); ?>" class="lgp-link-back">
				<?php esc_html_e( 'Go to Partner Company Login →', 'loungenie-portal' ); ?>
			</a>
		</div>
	</div>
</body>
</html>
