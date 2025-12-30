<?php
// phpcs:ignoreFile

/**
 * Partner Login Form
 * Custom branded login for Partners (no WordPress branding)
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

// Handle login
$error_message      = '';
$redirect_to        = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : home_url('/portal');
$support_login_url  = home_url('/support-login');

if (isset($_POST['lgp_partner_login']) && isset($_POST['lgp_login_nonce'])) {
	if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lgp_login_nonce'])), 'lgp_partner_login_action')) {
		$username = isset($_POST['log']) ? sanitize_user(wp_unslash($_POST['log'])) : '';
		$password = isset($_POST['pwd']) ? wp_unslash($_POST['pwd']) : '';
		$remember = isset($_POST['rememberme']);

		// Verify company credentials
		if (class_exists('LGP_Company_Credentials')) {
			$company_id = LGP_Company_Credentials::verify_partner_credentials($username, $password);

			if ($company_id) {
				// Create or get WordPress user for this company
				$user = self::get_or_create_partner_user($username, $company_id);

				if (! is_wp_error($user)) {
					// Sign in the user
					wp_set_current_user($user->ID);
					wp_set_auth_cookie($user->ID, $remember, is_ssl());
					do_action('wp_login', $user->user_login, $user);

					wp_safe_redirect($redirect_to);
					exit;
				} else {
					$error_message = __('Failed to create user session.', 'loungenie-portal');
				}
			} else {
				$error_message = __('Invalid company username or password. Please try again.', 'loungenie-portal');
			}
		} else {
			// Fallback to WordPress authentication if credentials class not available
			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			);

			$user = wp_signon($creds, is_ssl());

			if (! is_wp_error($user)) {
				wp_safe_redirect($redirect_to);
				exit;
			} else {
				$error_message = __('Invalid username or password. Please try again.', 'loungenie-portal');
			}
		}
	}
}

/**
 * Get or create a WordPress user for a partner company
 */
function get_or_create_partner_user($partner_username, $company_id)
{
	global $wpdb;

	// Check if user exists
	$user = get_user_by('login', $partner_username);

	if ($user) {
		// Update company association
		update_user_meta($user->ID, 'lgp_company_id', $company_id);
		return $user;
	}

	// Create new user
	$user_id = wp_create_user($partner_username, wp_generate_password(), 'partner' . $company_id . '@loungenie.local');

	if (is_wp_error($user_id)) {
		return $user_id;
	}

	// Assign partner role
	$user = get_user_by('id', $user_id);
	$user->set_role('lgp_partner');

	// Store company association
	update_user_meta($user_id, 'lgp_company_id', $company_id);

	return $user;
}

// If already logged in, redirect to portal
if (is_user_logged_in()) {
	wp_safe_redirect(home_url('/portal'));
	exit;
}

echo "<!DOCTYPE html>\n";
?>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e('Partner Login', 'loungenie-portal'); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url(LGP_ASSETS_URL . 'css/lgp-reset.css'); ?>">
	<link rel="stylesheet" href="<?php echo esc_url(LGP_ASSETS_URL . 'css/design-tokens.css'); ?>">
	<link rel="stylesheet" href="<?php echo esc_url(LGP_ASSETS_URL . 'css/login.css'); ?>">
</head>

<body class="lgp-login-page lgp-screen-center">
	<div class="lgp-portal-container">
		<div class="lgp-card lgp-max-w-520">
			<div class="logo-section">
				<?php
				// Check for WordPress custom logo
				$logo_url = get_option('lgp_custom_logo_url');
				if (! $logo_url && has_custom_logo()) {
					$custom_logo_id = get_theme_mod('custom_logo');
					$logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
				}

				if ($logo_url) :
				?>
					<img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('LounGenie Portal', 'loungenie-portal'); ?>" class="lgp-login-logo" />
				<?php else : ?>
					<div class="logo-container">
						<div class="logo-loungenie">LounGenie</div>
						<div class="logo-divider">×</div>
						<div class="logo-poolsafe">POOL SAFE Inc.</div>
					</div>
				<?php endif; ?>
				<div class="logo-subline"><?php esc_html_e('world • resorts • cruise • clubs • villa', 'loungenie-portal'); ?></div>
			</div>

			<h1 class="lgp-login-heading"><?php esc_html_e('Partner Login', 'loungenie-portal'); ?></h1>
			<p class="lgp-login-subtitle"><?php esc_html_e('Log in securely to your partner portal.', 'loungenie-portal'); ?></p>

			<?php if (! empty($error_message)) : ?>
				<div class="lgp-alert lgp-alert-error" role="alert">
					<?php echo esc_html($error_message); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field('lgp_partner_login_action', 'lgp_login_nonce'); ?>
				<div class="lgp-form-group">
					<label for="user_login" class="lgp-label"><?php esc_html_e('Username', 'loungenie-portal'); ?></label>
					<input type="text" id="user_login" name="log" class="lgp-input" autocomplete="username" required />
				</div>

				<div class="lgp-form-group">
					<label for="user_pass" class="lgp-label"><?php esc_html_e('Password', 'loungenie-portal'); ?></label>
					<input type="password" id="user_pass" name="pwd" class="lgp-input" autocomplete="current-password" required />
				</div>

				<div class="lgp-form-group">
					<label for="rememberme" class="lgp-checkbox-label">
						<input type="checkbox" id="rememberme" name="rememberme" class="lgp-checkbox" />
						<span><?php esc_html_e('Remember me', 'loungenie-portal'); ?></span>
					</label>
				</div>

				<input type="hidden" name="lgp_partner_login" value="1" />

				<button type="submit" class="lgp-btn lgp-btn-primary lgp-btn-lg lgp-w-full">
					<?php esc_html_e('Sign In', 'loungenie-portal'); ?>
				</button>
			</form>

			<div class="lgp-divider">
				<span><?php esc_html_e('or', 'loungenie-portal'); ?></span>
			</div>

			<a href="<?php echo esc_url($support_login_url); ?>" class="lgp-btn lgp-btn-secondary lgp-btn-lg lgp-w-full">
				<?php esc_html_e('Sign in with Microsoft 365', 'loungenie-portal'); ?>
			</a>

			<div class="lgp-login-footer">
				<a href="<?php echo esc_url(home_url('/portal')); ?>" class="lgp-link-back">
					<?php esc_html_e('← Back to Portal', 'loungenie-portal'); ?>
				</a>
			</div>
		</div>
	</div>
</body>

</html>