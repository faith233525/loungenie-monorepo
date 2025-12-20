<?php

/**
 * Enhanced Modern Login Page
 *
 * Features:
 * - Role switcher with memory
 * - Password visibility toggle
 * - Remember me checkbox
 * - Forgot password link
 * - Loading states
 * - Keyboard shortcuts (Alt+P, Alt+S)
 * - Auto-focus on username
 * - Form validation
 *
 * @package LounGeniePortal
 * @since 2.1.0
 */

if (! defined('ABSPATH')) {
	exit;
}

// Get login type from URL parameter (ensure unslash + sanitize)
$login_type_raw  = isset($_GET['login_type']) ? wp_unslash($_GET['login_type']) : '';
$redirect_raw    = isset($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : '';
$login_type      = $login_type_raw ? sanitize_text_field($login_type_raw) : 'partner';
$redirect_to     = $redirect_raw ? esc_url_raw($redirect_raw) : home_url('/dashboard/');

// Enqueue modern styles
wp_enqueue_style('lgp-login-enhanced', plugin_dir_url(__FILE__) . '../assets/css/login-page-enhanced.css', array(), '2.1.0');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html(get_bloginfo('name')); ?> - <?php esc_html_e('Sign In', 'loungenie-portal'); ?></title>
	<?php wp_head(); ?>
</head>

<body class="lgp-login-page">

	<div class="lgp-login-container">

		<!-- Logo & Title -->
		<div class="lgp-login-header">
			<svg class="lgp-logo" width="64" height="64" viewBox="0 0 100 100" fill="none">
				<defs>
					<linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
						<stop offset="0%" style="stop-color:#3AA6B9;stop-opacity:1" />
						<stop offset="100%" style="stop-color:#25D0EE;stop-opacity:1" />
					</linearGradient>
				</defs>
				<rect x="10" y="10" width="80" height="80" rx="16" fill="url(#logoGradient)" />
				<rect x="25" y="40" width="14" height="28" rx="2" fill="white" opacity="0.9" />
				<rect x="43" y="30" width="14" height="38" rx="2" fill="white" opacity="0.9" />
				<rect x="61" y="35" width="14" height="33" rx="2" fill="white" opacity="0.9" />
				<circle cx="32" cy="32" r="4" fill="white" />
				<circle cx="50" cy="26" r="4" fill="white" />
				<circle cx="68" cy="28" r="4" fill="white" />
			</svg>

			<h1 class="lgp-login-title"><?php echo esc_html(get_bloginfo('name')); ?></h1>
			<p class="lgp-login-subtitle"><?php esc_html_e('Partner Company Management System', 'loungenie-portal'); ?></p>
		</div>

		<!-- Role Selector -->
		<div class="lgp-role-selector">
			<button type="button"
				class="lgp-role-btn <?php echo esc_attr($login_type === 'partner' ? 'active' : ''); ?>"
				onclick="switchLoginType('partner')"
				data-role="partner">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
					<path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.455.894l-4-2-4 2A1 1 0 015 16V4z" />
				</svg>
				<?php esc_html_e('Partner Company', 'loungenie-portal'); ?>
				<kbd class="lgp-keyboard-hint">Alt+P</kbd>
			</button>
			<button type="button"
				class="lgp-role-btn <?php echo esc_attr($login_type === 'support' ? 'active' : ''); ?>"
				onclick="switchLoginType('support')"
				data-role="support">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
					<path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zm0 16a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
				</svg>
				<?php esc_html_e('Support Team', 'loungenie-portal'); ?>
				<kbd class="lgp-keyboard-hint">Alt+S</kbd>
			</button>
		</div>

		<!-- PARTNER LOGIN FORM -->
		<div id="partnerForm" style="display: <?php echo esc_attr(($login_type === 'partner') ? 'block' : 'none'); ?>;">
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="lgp-login-form" id="lgpPartnerForm">
				<?php wp_nonce_field('lgp_partner_login', 'lgp_partner_nonce'); ?>
				<input type="hidden" name="action" value="lgp_partner_login">
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

				<div class="lgp-form-group">
					<label for="lgp-username" class="lgp-label">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
							<path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
						</svg>
						<?php esc_html_e('Username', 'loungenie-portal'); ?>
					</label>
					<div class="lgp-input-wrapper">
						<input type="text"
							id="lgp-username"
							name="log"
							class="lgp-form-input"
							placeholder="<?php esc_attr_e('Enter your username', 'loungenie-portal'); ?>"
							required
							autofocus
							autocomplete="username"
							autocapitalize="off">
					</div>
				</div>

				<div class="lgp-form-group">
					<label for="lgp-password" class="lgp-label">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
							<path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z" />
						</svg>
						<?php esc_html_e('Password', 'loungenie-portal'); ?>
					</label>
					<div class="lgp-input-wrapper lgp-password-wrapper">
						<input type="password"
							id="lgp-password"
							name="pwd"
							class="lgp-form-input"
							placeholder="<?php esc_attr_e('Enter your password', 'loungenie-portal'); ?>"
							required
							autocomplete="current-password">
						<button type="button" class="lgp-toggle-password" aria-label="<?php esc_attr_e('Toggle password visibility', 'loungenie-portal'); ?>">
							<svg class="lgp-eye-open" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
								<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
								<path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
							</svg>
							<svg class="lgp-eye-closed" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
								<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
								<path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.742L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
							</svg>
						</button>
					</div>
				</div>

				<div class="lgp-form-options">
					<label class="lgp-checkbox">
						<input type="checkbox" name="rememberme" value="1" checked>
						<span class="lgp-checkbox-label"><?php esc_html_e('Remember me', 'loungenie-portal'); ?></span>
					</label>
					<a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="lgp-forgot-link">
						<?php esc_html_e('Forgot password?', 'loungenie-portal'); ?>
					</a>
				</div>

				<button type="submit" class="lgp-btn-login">
					<span class="lgp-btn-text"><?php esc_html_e('Sign In', 'loungenie-portal'); ?></span>
					<span class="lgp-btn-loader">
						<svg width="20" height="20" viewBox="0 0 20 20">
							<circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="50" stroke-linecap="round">
								<animateTransform attributeName="transform" type="rotate" from="0 10 10" to="360 10 10" dur="1s" repeatCount="indefinite" />
							</circle>
						</svg>
					</span>
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
		<div id="supportForm" style="display: <?php echo esc_attr(($login_type === 'support') ? 'block' : 'none'); ?>;">
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="lgp-login-form" id="lgpSupportForm">
				<?php wp_nonce_field('lgp_sso_login', 'lgp_sso_nonce'); ?>
				<input type="hidden" name="action" value="lgp_sso_login">
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

				<button type="submit" class="lgp-btn-sso">
					<svg class="lgp-ms-icon" width="24" height="24" viewBox="0 0 23 23" fill="none">
						<rect width="11" height="11" fill="#F25022" />
						<rect x="12" width="11" height="11" fill="#7FBA00" />
						<rect y="12" width="11" height="11" fill="#00A4EF" />
						<rect x="12" y="12" width="11" height="11" fill="#FFB900" />
					</svg>
					<span class="lgp-btn-text"><?php esc_html_e('Microsoft Sign-In', 'loungenie-portal'); ?></span>
					<span class="lgp-btn-loader">
						<svg width="20" height="20" viewBox="0 0 20 20">
							<circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="50" stroke-linecap="round">
								<animateTransform attributeName="transform" type="rotate" from="0 10 10" to="360 10 10" dur="1s" repeatCount="indefinite" />
							</circle>
						</svg>
					</span>
				</button>

				<div class="lgp-sso-info">
					<p><?php esc_html_e('Use your Microsoft 365 credentials to access the Support Team portal.', 'loungenie-portal'); ?></p>
				</div>

				<div class="lgp-trust-badge">
					<svg class="lgp-trust-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
						<path d="M8 0L2 3v5c0 3.5 2.4 6.8 6 7.6 3.6-.8 6-4.1 6-7.6V3L8 0zm4 8c0 2.8-1.9 5.4-4 6-2.1-.6-4-3.2-4-6V4l4-2 4 2v4zm-1.3-1.3L7 10.4 5.3 8.7l1-1 .7.7 2.7-2.7 1 1z" />
					</svg>
					<?php esc_html_e('SSO Protected • Enterprise Security', 'loungenie-portal'); ?>
				</div>
			</form>
		</div>

	</div>

	<script>
		(function() {
			'use strict';

			// State management
			const state = {
				lastRole: localStorage.getItem('lgp_last_role') || 'partner'
			};

			// Initialize on DOM ready
			document.addEventListener('DOMContentLoaded', function() {
				initRoleSwitcher();
				initPasswordToggle();
				initFormSubmission();
				initKeyboardShortcuts();

				// Restore last selected role
				if (state.lastRole && !new URL(window.location).searchParams.has('login_type')) {
					switchLoginType(state.lastRole);
				}
			});

			/**
			 * Role switcher with memory
			 */
			function initRoleSwitcher() {
				const roleButtons = document.querySelectorAll('.lgp-role-btn');

				roleButtons.forEach(button => {
					button.addEventListener('click', function() {
						const role = this.getAttribute('data-role');
						if (role) {
							localStorage.setItem('lgp_last_role', role);
						}
					});
				});
			}

			/**
			 * Password visibility toggle
			 */
			function initPasswordToggle() {
				const toggleBtn = document.querySelector('.lgp-toggle-password');
				const passwordInput = document.getElementById('lgp-password');

				if (!toggleBtn || !passwordInput) return;

				toggleBtn.addEventListener('click', function() {
					const isPassword = passwordInput.type === 'password';
					passwordInput.type = isPassword ? 'text' : 'password';

					// Toggle icons
					const openEye = this.querySelector('.lgp-eye-open');
					const closedEye = this.querySelector('.lgp-eye-closed');

					if (openEye && closedEye) {
						openEye.style.display = isPassword ? 'none' : 'block';
						closedEye.style.display = isPassword ? 'block' : 'none';
					}

					this.classList.toggle('active');

					// Refocus password field
					passwordInput.focus();
				});
			}

			/**
			 * Form submission with loading states
			 */
			function initFormSubmission() {
				const forms = document.querySelectorAll('.lgp-login-form');

				forms.forEach(form => {
					form.addEventListener('submit', function(e) {
						const submitBtn = this.querySelector('button[type="submit"]');

						if (submitBtn) {
							submitBtn.classList.add('loading');
							submitBtn.disabled = true;

							// Re-enable after 10 seconds (timeout fallback)
							setTimeout(function() {
								submitBtn.classList.remove('loading');
								submitBtn.disabled = false;
							}, 10000);
						}
					});
				});
			}

			/**
			 * Keyboard shortcuts
			 */
			function initKeyboardShortcuts() {
				document.addEventListener('keydown', function(e) {
					// Alt+P for Partner Company, Alt+S for Support Team
					if (e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
						const key = e.key.toLowerCase();

						if (key === 'p') {
							e.preventDefault();
							switchLoginType('partner');
						} else if (key === 's') {
							e.preventDefault();
							switchLoginType('support');
						}
					}
				});
			}

			/**
			 * Switch login type (exposed globally)
			 */
			window.switchLoginType = function(type) {
				const partnerForm = document.getElementById('partnerForm');
				const supportForm = document.getElementById('supportForm');
				const partnerBtn = document.querySelector('[data-role="partner"]');
				const supportBtn = document.querySelector('[data-role="support"]');

				if (!partnerForm || !supportForm) return;

				// Switch forms
				if (type === 'partner') {
					partnerForm.style.display = 'block';
					supportForm.style.display = 'none';

					if (partnerBtn) partnerBtn.classList.add('active');
					if (supportBtn) supportBtn.classList.remove('active');

					// Focus username field
					setTimeout(() => {
						const usernameField = document.getElementById('lgp-username');
						if (usernameField) usernameField.focus();
					}, 100);
				} else {
					partnerForm.style.display = 'none';
					supportForm.style.display = 'block';

					if (partnerBtn) partnerBtn.classList.remove('active');
					if (supportBtn) supportBtn.classList.add('active');
				}

				// Update URL without reload
				const url = new URL(window.location);
				url.searchParams.set('login_type', type);
				window.history.replaceState({}, '', url);

				// Store preference
				localStorage.setItem('lgp_last_role', type);
			};
		})();
	</script>

	<?php wp_footer(); ?>
</body>

</html>