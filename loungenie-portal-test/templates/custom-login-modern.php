<?php

/**
 * Modern Login Page Template - LounGenie Portal
 * Clean, centered design matching portal dashboard
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
	exit;
}

// Get login context
$login_type  = isset($_GET['login_type']) ? sanitize_text_field($_GET['login_type']) : 'partner';
$error       = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : admin_url('');

// Error messages
$error_messages = array(
	'invalid_credentials' => __('Invalid username or password. Please try again.', 'loungenie-portal'),
	'sso_failed'          => __('SSO authentication failed. Please try again.', 'loungenie-portal'),
	'access_denied'       => __('Access denied. Please contact support.', 'loungenie-portal'),
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php bloginfo('name'); ?> - Login</title>

	<?php wp_head(); ?>

	<!-- Isolation reset must load AFTER theme styles -->
	<link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/lgp-reset.css', dirname(__DIR__) . '/loungenie-portal.php')); ?>">
	<!-- Template styles load after reset to establish design -->
	<link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/login-page-modern.css', dirname(__DIR__) . '/loungenie-portal.php')); ?>">
</head>

<body class="lgp-login-page lgp-screen-center">

	<div class="lgp-portal-container">
		<div class="lgp-login-container">

			<!-- Logo -->
			<svg class="lgp-logo-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect x="10" y="10" width="80" height="80" rx="16" fill="url(#gradient)" />
				<path d="M35 30h30v6H35z" fill="white" opacity="0.95" />
				<rect x="35" y="42" width="18" height="18" rx="2" fill="white" opacity="0.95" />
				<rect x="58" y="42" width="7" height="18" rx="2" fill="white" opacity="0.95" />
				<path d="M35 66h30v6H35z" fill="white" opacity="0.95" />
				<defs>
					<linearGradient id="gradient" x1="10" y1="10" x2="90" y2="90" gradientUnits="userSpaceOnUse">
						<stop offset="0%" stop-color="#3AA6B9" />
						<stop offset="100%" stop-color="#25D0EE" />
					</linearGradient>
				</defs>
			</svg>

			<!-- Title -->
			<h1 class="lgp-form-title"><?php bloginfo('name'); ?></h1>
			<p class="lgp-form-subtitle"><?php esc_html_e('Partner Company Management System', 'loungenie-portal'); ?></p>

			<!-- Error Message -->
			<?php if ($error && isset($error_messages[$error])) : ?>
				<div class="lgp-error-message" role="alert">
					<?php echo esc_html($error_messages[$error]); ?>
				</div>
			<?php endif; ?>

			<!-- Role Selector -->
			<div class="lgp-role-selector">
				<button class="lgp-role-btn <?php echo ($login_type === 'partner') ? 'active' : ''; ?>"
					onclick="switchLoginType('partner')">
					<?php esc_html_e('Partner Company', 'loungenie-portal'); ?>
				</button>
				<button class="lgp-role-btn <?php echo ($login_type === 'support') ? 'active' : ''; ?>"
					onclick="switchLoginType('support')">
					<?php esc_html_e('Support Team', 'loungenie-portal'); ?>
				</button>
			</div>

			<!-- PARTNER LOGIN FORM -->
			<div id="partnerForm" style="display: <?php echo ($login_type === 'partner') ? 'block' : 'none'; ?>;">
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<?php wp_nonce_field('lgp_partner_login', 'lgp_partner_nonce'); ?>
					<input type="hidden" name="action" value="lgp_partner_login">
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

					<div class="lgp-form-group">
						<div class="lgp-input-wrapper">
							<input type="text"
								name="log"
								class="lgp-form-input"
								placeholder="<?php esc_attr_e('Username', 'loungenie-portal'); ?>"
								required
								autocomplete="username">
							<span class="lgp-input-icon">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
									<path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
								</svg>
							</span>
						</div>
					</div>

					<div class="lgp-form-group">
						<div class="lgp-input-wrapper">
							<input type="password"
								name="pwd"
								class="lgp-form-input"
								placeholder="<?php esc_attr_e('Password', 'loungenie-portal'); ?>"
								required
								autocomplete="current-password">
							<span class="lgp-input-icon">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
									<path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z" />
								</svg>
							</span>
						</div>
					</div>

					<button type="submit" class="lgp-btn-login">
						<?php esc_html_e('Sign In', 'loungenie-portal'); ?>
					</button>

					<div class="lgp-trust-badge">
						<svg class="lgp-trust-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M8 0L2 3v5c0 3.5 2.4 6.8 6 7.6 3.6-.8 6-4.1 6-7.6V3L8 0zm4 8c0 2.8-1.9 5.4-4 6-2.1-.6-4-3.2-4-6V4l4-2 4 2v4zm-1.3-1.3L7 10.4 5.3 8.7l1-1 .7.7 2.7-2.7 1 1z" />
						</svg>
						<?php esc_html_e('Secure Partner Company Access', 'loungenie-portal'); ?>
					</div>
				</form>
			</div>

			<!-- SUPPORT SSO FORM -->
			<div id="supportForm" style="display: <?php echo ($login_type === 'support') ? 'block' : 'none'; ?>;">
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<?php wp_nonce_field('lgp_sso_login', 'lgp_sso_nonce'); ?>
					<input type="hidden" name="action" value="lgp_sso_login">
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

					<button type="submit" class="lgp-btn-sso">
						<svg class="lgp-ms-icon" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect width="11" height="11" fill="#3AA6B9" />
							<rect x="12" width="11" height="11" fill="#3AA6B9" />
							<rect y="12" width="11" height="11" fill="#3AA6B9" />
							<rect x="12" y="12" width="11" height="11" fill="#3AA6B9" />
						</svg>
						<?php esc_html_e('Microsoft Sign-In', 'loungenie-portal'); ?>
					</button>

					<div class="lgp-trust-badge">
						<svg class="lgp-trust-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M8 0L2 3v5c0 3.5 2.4 6.8 6 7.6 3.6-.8 6-4.1 6-7.6V3L8 0zm4 8c0 2.8-1.9 5.4-4 6-2.1-.6-4-3.2-4-6V4l4-2 4 2v4zm-1.3-1.3L7 10.4 5.3 8.7l1-1 .7.7 2.7-2.7 1 1z" />
						</svg>
						<?php esc_html_e('SSO Protected • Enterprise Security', 'loungenie-portal'); ?>
					</div>
				</form>
			</div>

		</div>
	</div>

	<?php $lgp_nonce = method_exists('LGP_Security', 'get_csp_nonce') ? LGP_Security::get_csp_nonce() : ''; ?>
	<script<?php echo $lgp_nonce ? ' nonce="' . esc_attr($lgp_nonce) . '"' : ''; ?>>
		function switchLoginType(type) {
		const partnerForm = document.getElementById('partnerForm');
		const supportForm = document.getElementById('supportForm');
		const partnerBtn = document.querySelector('.lgp-role-btn:first-child');
		const supportBtn = document.querySelector('.lgp-role-btn:last-child');

		if (type === 'partner') {
		partnerForm.style.display = 'block';
		supportForm.style.display = 'none';
		partnerBtn.classList.add('active');
		supportBtn.classList.remove('active');
		} else {
		partnerForm.style.display = 'none';
		supportForm.style.display = 'block';
		partnerBtn.classList.remove('active');
		supportBtn.classList.add('active');
		}

		// Update URL without reload
		const url = new URL(window.location);
		url.searchParams.set('login_type', type);
		window.history.pushState({}, '', url);
		}
		</script>

		<?php wp_footer(); ?>
</body>

</html>