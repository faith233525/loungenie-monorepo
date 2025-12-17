<?php
// phpcs:ignoreFile
/**
 * Partner Login Form
 * Custom branded login for Partners (no WordPress branding)
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle login
$error_message      = '';
$redirect_to        = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/portal' );
$support_login_url  = home_url( '/support-login' );

if ( isset( $_POST['lgp_partner_login'] ) && isset( $_POST['lgp_login_nonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lgp_login_nonce'] ) ), 'lgp_partner_login_action' ) ) {
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
	<title><?php esc_html_e( 'Partner Login', 'loungenie-portal' ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
		:root {
			/* LounGenie Brand (STRICT) */
			--bg: #E9F8F9;            /* Soft White/Cyan */
			--card: #FFFFFF;          /* Card surface */
			--ink: #222222;           /* Main text */
			--muted: #454F5E;         /* Slate Gray labels */
			--border: #D8E9EC;        /* Soft border */
			--primary: #3AA6B9;       /* Primary Teal */
			--accent: #25D0EE;        /* Accent Cyan */
			--navy: #0F172A;          /* Structural/Dark */
			--outlook: #0A5BD5;       /* Outlook icon */
			--shadow: 0 20px 60px rgba(15, 23, 42, 0.10);
			--radius: 16px;           /* Rounded corners */
		}
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			background: var(--bg);
			color: var(--ink);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 32px 20px;
		}
		.login-card {
			width: 520px;
			max-width: 100%;
			background: var(--card);
			border: none;
			border-radius: var(--radius);
			box-shadow: var(--shadow);
			padding: 42px 44px 36px;
		}
		.logo-section {
			text-align: center;
			margin-bottom: 28px;
		}
		.logo-container {
			display: inline-flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 10px;
		}
		.logo-loungenie {
			font-size: 22px;
			font-weight: 800;
			letter-spacing: -0.3px;
			color: var(--primary);
		}
		.logo-divider {
			color: #cbd5e1;
			font-size: 18px;
			font-weight: 500;
		}
		.logo-poolsafe {
			font-size: 18px;
			font-weight: 800;
			color: #0f172a;
			letter-spacing: 0.15px;
		}
		.logo-subline {
			color: var(--muted);
			font-size: 12px;
			letter-spacing: 0.5px;
			text-transform: uppercase;
		}
		h1 {
			font-size: 24px;
			font-weight: 700;
			margin-bottom: 6px;
			color: var(--ink);
		}
		.subtitle {
			color: var(--muted);
			font-size: 14px;
			margin-bottom: 24px;
		}
		.error-message {
			background: #fee2e2;
			color: #b91c1c;
			padding: 12px 14px;
			border-radius: 10px;
			font-size: 14px;
			margin-bottom: 18px;
			border: 1px solid rgba(185, 28, 28, 0.16);
		}
		.form-group { margin-bottom: 16px; }
		label {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: var(--muted);
			margin-bottom: 6px;
		}
		input[type="text"],
		input[type="password"] {
			width: 100%;
			padding: 13px 14px;
			border: 1.25px solid var(--border);
			border-radius: 10px;
			font-size: 15px;
			font-family: inherit;
			background: #f9fafb;
			transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
		}
		input[type="text"]:focus,
		input[type="password"]:focus {
			outline: none;
			border-color: var(--accent);
			box-shadow: 0 0 0 3px rgba(37, 208, 238, 0.25);
			background: #ffffff;
		}
		.submit-button {
			width: 100%;
			padding: 14px;
			background: var(--primary);
			color: #ffffff;
			border: none;
			border-radius: 10px;
			font-size: 16px;
			font-weight: 700;
			cursor: pointer;
			transition: transform 0.12s ease, box-shadow 0.18s ease, background 0.18s ease;
			box-shadow: 0 10px 26px rgba(58, 166, 185, 0.25);
		}
		.submit-button:hover { transform: translateY(-1px); box-shadow: 0 12px 32px rgba(58, 166, 185, 0.3); background: #3cb1c4; }
		.submit-button:active { transform: translateY(0); }
		.outlook-button {
			margin: 12px auto 0;
			width: 220px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			padding: 12px 14px;
			border-radius: 10px;
			border: 1.5px solid var(--primary);
			background: transparent;
			color: var(--ink);
			font-weight: 600;
			text-decoration: none;
			transition: border-color 0.15s ease, box-shadow 0.18s ease, transform 0.12s ease;
		}
		.outlook-button:hover { border-color: var(--accent); box-shadow: 0 8px 22px rgba(37, 208, 238, 0.20); transform: translateY(-1px); }
		.outlook-icon {
			width: 24px;
			height: 24px;
			border-radius: 6px;
			background: linear-gradient(135deg, #0b62e0, var(--outlook));
			display: inline-flex;
			align-items: center;
			justify-content: center;
			color: #ffffff;
			font-weight: 800;
			font-size: 12px;
			box-shadow: inset 0 -1px 0 rgba(0,0,0,0.12);
		}
		.security-note {
			margin-top: 16px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			color: #4b5563;
			font-weight: 600;
			font-size: 13px;
		}
		.security-pill {
			width: 22px;
			height: 22px;
			border-radius: 7px;
			background: linear-gradient(135deg, #e5e7eb, #d1d5db);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			color: #111827;
			font-size: 11px;
			font-weight: 800;
		}
		.footer-links {
			margin-top: 22px;
			text-align: center;
			font-size: 14px;
			color: var(--muted);
		}
		.footer-links a {
			color: #0f172a;
			text-decoration: none;
			font-weight: 600;
			transition: color 0.15s ease;
		}
		.footer-links a:hover { color: var(--accent); }
		@media (max-width: 520px) {
			.login-card { padding: 32px 28px; }
			h1 { font-size: 22px; }
		}
	</style>
</head>
<body>
	<div class="login-card">
		<div class="logo-section">
			<div class="logo-container">
				<div class="logo-loungenie">LounGenie</div>
				<div class="logo-divider">×</div>
				<div class="logo-poolsafe">POOL SAFE Inc.</div>
			</div>
			<div class="logo-subline"><?php esc_html_e( 'world • resorts • cruise • clubs • villa', 'loungenie-portal' ); ?></div>
		</div>

		<h1><?php esc_html_e( 'Partner Login', 'loungenie-portal' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Log in securely to your partner portal.', 'loungenie-portal' ); ?></p>

		<?php if ( ! empty( $error_message ) ) : ?>
			<div class="error-message" role="alert">
				<?php echo esc_html( $error_message ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'lgp_partner_login_action', 'lgp_login_nonce' ); ?>
			<div class="form-group">
				<label for="user_login"><?php esc_html_e( 'Email Address', 'loungenie-portal' ); ?></label>
				<input type="text" name="log" id="user_login" required autocomplete="username" placeholder="Email Address" />
			</div>

			<div class="form-group">
				<label for="user_pass"><?php esc_html_e( 'Password', 'loungenie-portal' ); ?></label>
				<input type="password" name="pwd" id="user_pass" required autocomplete="current-password" placeholder="Password" />
			</div>

			<input type="hidden" name="lgp_partner_login" value="1" />

			<button type="submit" class="submit-button">
				<?php esc_html_e( 'Log In Securely', 'loungenie-portal' ); ?>
			</button>
		</form>

		<div style="text-align: center; margin-top: 12px;">
			<a class="outlook-button" href="<?php echo esc_url( $support_login_url ); ?>">
				<span class="outlook-icon" aria-hidden="true">O</span>
				<span><?php esc_html_e( 'Sign in with Outlook (Microsoft 365)', 'loungenie-portal' ); ?></span>
			</a>
		</div>

		<div class="security-note">
			<span class="security-pill" aria-hidden="true">🔒</span>
			<span><?php esc_html_e( 'Encrypted • Secure Access', 'loungenie-portal' ); ?></span>
		</div>

		<div class="footer-links">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'loungenie-portal' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/portal/login' ) ); ?>"><?php esc_html_e( '← Back to login options', 'loungenie-portal' ); ?></a>
		</div>
	</div>
</body>
</html>
