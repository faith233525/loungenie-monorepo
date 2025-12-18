<?php
// phpcs:ignoreFile
/**
 * Support Team Login Form
 * Custom branded login for Support team members (no WordPress branding)
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
	<title><?php esc_html_e( 'Support Team Portal', 'loungenie-portal' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( LGP_ASSETS_URL . 'css/login.css' ); ?>">
</head>
<body class="lgp-login-page lgp-support-login">
	<div class="login-container">
		<div class="login-box">
			<!-- Logo/Branding -->
			<div class="login-header">
				<div class="logo-section">
					<h1><?php esc_html_e( 'LounGenie', 'loungenie-portal' ); ?></h1>
					<p><?php esc_html_e( 'Support Portal', 'loungenie-portal' ); ?></p>
				</div>
			</div>

			<!-- Error Message -->
			<?php if ( ! empty( $error_message ) ) : ?>
				<div class="error-message" role="alert">
					<?php echo esc_html( $error_message ); ?>
				</div>
			<?php endif; ?>

			<!-- Login Form -->
			<form method="post" class="login-form" novalidate>
				<?php wp_nonce_field( 'lgp_support_login_action', 'lgp_login_nonce' ); ?>
				<input type="hidden" name="lgp_support_login" value="1">

				<!-- Username Field -->
				<div class="form-group">
					<label for="user-login"><?php esc_html_e( 'Username', 'loungenie-portal' ); ?></label>
					<input
						type="text"
						name="log"
						id="user-login"
						class="form-input"
						placeholder="<?php esc_attr_e( 'Enter your username', 'loungenie-portal' ); ?>"
						required
						autofocus
					>
				</div>

				<!-- Password Field -->
				<div class="form-group">
					<label for="user-pass"><?php esc_html_e( 'Password', 'loungenie-portal' ); ?></label>
					<input
						type="password"
						name="pwd"
						id="user-pass"
						class="form-input"
						placeholder="<?php esc_attr_e( 'Enter your password', 'loungenie-portal' ); ?>"
						required
					>
				</div>

				<!-- Remember Me -->
				<div class="form-group checkbox">
					<input type="checkbox" name="rememberme" id="rememberme" value="1">
					<label for="rememberme"><?php esc_html_e( 'Remember me', 'loungenie-portal' ); ?></label>
				</div>

				<!-- Submit Button -->
				<button type="submit" class="btn-login">
					<?php esc_html_e( 'Sign In', 'loungenie-portal' ); ?>
				</button>
			</form>

			<!-- Switch Role Links -->
			<div class="login-footer">
				<p><?php esc_html_e( 'Are you a partner?', 'loungenie-portal' ); ?></p>
				<a href="<?php echo esc_url( $partner_login_url ); ?>" class="link-switch">
					<?php esc_html_e( 'Go to Partner Portal', 'loungenie-portal' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Security Badge -->
	<div class="security-badge">
		<span>🔒 Secure</span>
	</div>

	<script src="<?php echo esc_url( LGP_ASSETS_URL . 'js/portal-init.js' ); ?>"></script>
</body>
</html>
<?php
// Prevent execution of the rest of WordPress
wp_die();
