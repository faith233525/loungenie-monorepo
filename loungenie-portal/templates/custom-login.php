<?php
/**
 * Custom Login Page Template
 *
 * Displays role-based authentication options:
 * - Support: Microsoft SSO (Outlook/Microsoft 365)
 * - Partners: WordPress username/password
 *
 * Design: Matches LounGenie Portal 60-30-10 color system
 * Security: Nonce validation, no user enumeration, secure errors
 *
 * @package LounGenie Portal
 * @version 1.8.0
 */

// Get login context
$login_type  = isset( $_GET['login_type'] ) ? sanitize_text_field( $_GET['login_type'] ) : 'select';
$error       = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( $_REQUEST['redirect_to'] ) : '';

// Security: Validate and sanitize redirects
if ( empty( $redirect_to ) ) {
	$redirect_to = admin_url( '' );
}

// Remove protocol for comparison
$redirect_base = str_replace( array( 'http://', 'https://' ), '', $redirect_to );
$home_base     = str_replace( array( 'http://', 'https://' ), '', home_url( '/' ) );

// Only allow redirects to same domain
if ( strpos( $redirect_base, $home_base ) !== 0 ) {
	$redirect_to = admin_url( '' );
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="LounGenie Portal - Secure Login">
	<title><?php echo get_bloginfo( 'name' ); ?> - Login</title>
	
	<!-- Favicon -->
	<link rel="icon" type="image/x-icon" href="<?php echo home_url( '/favicon.ico' ); ?>">
	
	<!-- Security Headers -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="referrer" content="strict-origin-when-cross-origin">
	
	<!-- CSS -->
	<link rel="stylesheet" href="<?php echo plugins_url( 'assets/css/design-tokens.css', dirname( __DIR__ ) . '/loungenie-portal.php' ); ?>">
	<link rel="stylesheet" href="<?php echo plugins_url( 'assets/css/login-page.css', dirname( __DIR__ ) . '/loungenie-portal.php' ); ?>">
	
	<!-- Preconnect for performance -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	
	<?php wp_head(); ?>
</head>
<body class="lgp-login-page">
	
	<div class="lgp-login-container">
		
		<!-- Left Panel: Branding -->
		<div class="lgp-login-panel lgp-login-branding">
			<div class="lgp-branding-content">
				<div class="lgp-logo">
					<?php
					$custom_logo = get_custom_logo();
					if ( $custom_logo ) {
						echo $custom_logo;
					} else {
						echo '<h1>' . esc_html( get_bloginfo( 'name' ) ) . '</h1>';
					}
					?>
				</div>
				
				<h2><?php esc_html_e( 'Welcome to LounGenie Portal', 'loungenie-portal' ); ?></h2>
				<p><?php esc_html_e( 'Streamlined property management for partners and support teams', 'loungenie-portal' ); ?></p>
				
				<div class="lgp-features">
					<div class="lgp-feature">
						<span class="lgp-feature-icon">🔐</span>
						<span class="lgp-feature-text"><?php esc_html_e( 'Secure Access', 'loungenie-portal' ); ?></span>
					</div>
					<div class="lgp-feature">
						<span class="lgp-feature-icon">⚡</span>
						<span class="lgp-feature-text"><?php esc_html_e( 'Fast & Reliable', 'loungenie-portal' ); ?></span>
					</div>
					<div class="lgp-feature">
						<span class="lgp-feature-icon">📱</span>
						<span class="lgp-feature-text"><?php esc_html_e( 'Mobile Friendly', 'loungenie-portal' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Right Panel: Login Forms -->
		<div class="lgp-login-panel lgp-login-forms">
			
			<!-- Role Selection -->
			<?php if ( $login_type === 'select' ) : ?>
				<div class="lgp-login-form lgp-role-selector" id="lgp-role-selector">
					<h2><?php esc_html_e( 'Select Your Role', 'loungenie-portal' ); ?></h2>
					<p><?php esc_html_e( 'Choose how you would like to log in', 'loungenie-portal' ); ?></p>
					
					<div class="lgp-role-options">
						
						<!-- Support Role -->
						<a href="<?php echo add_query_arg( 'login_type', 'support', wp_login_url() ); ?>" 
						   class="lgp-role-option lgp-role-support"
						   data-role="support">
							<div class="lgp-role-icon">🎧</div>
							<h3><?php esc_html_e( 'Support', 'loungenie-portal' ); ?></h3>
							<p><?php esc_html_e( 'Microsoft Outlook / Microsoft 365', 'loungenie-portal' ); ?></p>
							<span class="lgp-role-arrow">→</span>
						</a>
						
						<!-- Partner Role -->
						<a href="<?php echo add_query_arg( 'login_type', 'partner', wp_login_url() ); ?>" 
						   class="lgp-role-option lgp-role-partner"
						   data-role="partner">
							<div class="lgp-role-icon">👥</div>
							<h3><?php esc_html_e( 'Partner', 'loungenie-portal' ); ?></h3>
							<p><?php esc_html_e( 'Username & Password', 'loungenie-portal' ); ?></p>
							<span class="lgp-role-arrow">→</span>
						</a>
					</div>
				</div>
			
			<!-- Support SSO Login -->
			<?php elseif ( $login_type === 'support' ) : ?>
				<div class="lgp-login-form lgp-sso-login" id="lgp-sso-login">
					<div class="lgp-form-header">
						<a href="<?php echo wp_login_url(); ?>" class="lgp-back-link">← <?php esc_html_e( 'Back', 'loungenie-portal' ); ?></a>
						<h2><?php esc_html_e( 'Support Team Login', 'loungenie-portal' ); ?></h2>
					</div>
					
					<!-- Error Messages -->
					<?php if ( ! empty( $error ) ) : ?>
						<div class="lgp-error-message" role="alert">
							<?php
							switch ( $error ) {
								case 'sso_failed':
									esc_html_e( 'Authentication failed. Please try again.', 'loungenie-portal' );
									break;
								case 'user_not_found':
									esc_html_e( 'Your account is not authorized for this system.', 'loungenie-portal' );
									break;
								case 'invalid_role':
									esc_html_e( 'Your account does not have support team access.', 'loungenie-portal' );
									break;
								default:
									esc_html_e( 'An error occurred. Please try again.', 'loungenie-portal' );
							}
							?>
						</div>
					<?php endif; ?>
					
					<form method="post" class="lgp-sso-form">
						<?php wp_nonce_field( 'lgp_sso_login', 'lgp_sso_nonce', false ); ?>
						<input type="hidden" name="action" value="lgp_microsoft_sso">
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
						
						<button type="submit" class="lgp-btn lgp-btn-primary lgp-btn-large">
							<span class="lgp-btn-icon">🔐</span>
							<?php esc_html_e( 'Sign in with Microsoft', 'loungenie-portal' ); ?>
						</button>
					</form>
					
					<div class="lgp-form-info">
						<p>
							<?php esc_html_e( 'You will be redirected to Microsoft to securely authenticate using your Outlook / Microsoft 365 credentials.', 'loungenie-portal' ); ?>
						</p>
					</div>
				</div>
			
			<!-- Partner WordPress Login -->
			<?php elseif ( $login_type === 'partner' ) : ?>
				<div class="lgp-login-form lgp-wordpress-login" id="lgp-wordpress-login">
					<div class="lgp-form-header">
						<a href="<?php echo wp_login_url(); ?>" class="lgp-back-link">← <?php esc_html_e( 'Back', 'loungenie-portal' ); ?></a>
						<h2><?php esc_html_e( 'Partner Login', 'loungenie-portal' ); ?></h2>
					</div>
					
					<!-- Error Messages -->
					<?php if ( ! empty( $error ) ) : ?>
						<div class="lgp-error-message" role="alert">
							<?php
							switch ( $error ) {
								case 'invalid_credentials':
									esc_html_e( 'Invalid username or password.', 'loungenie-portal' );
									break;
								case 'account_disabled':
									esc_html_e( 'Your account has been disabled. Contact support for assistance.', 'loungenie-portal' );
									break;
								case 'invalid_nonce':
									esc_html_e( 'Security check failed. Please try again.', 'loungenie-portal' );
									break;
								case 'invalid_role':
									esc_html_e( 'Your account does not have partner access.', 'loungenie-portal' );
									break;
								default:
									esc_html_e( 'Login failed. Please try again.', 'loungenie-portal' );
							}
							?>
						</div>
					<?php endif; ?>
					
					<form method="post" class="lgp-login-form-fields">
						<?php wp_nonce_field( 'lgp_partner_login', 'lgp_login_nonce', false ); ?>
						<input type="hidden" name="action" value="lgp_partner_login">
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
						
						<!-- Username Field -->
						<div class="lgp-form-group">
							<label for="user_login" class="lgp-form-label">
								<?php esc_html_e( 'Username or Email', 'loungenie-portal' ); ?>
							</label>
							<input 
								type="text"
								id="user_login"
								name="user_login"
								class="lgp-form-input"
								placeholder="<?php esc_attr_e( 'Enter your username or email', 'loungenie-portal' ); ?>"
								required
								autofocus
								autocomplete="username"
								aria-required="true"
								aria-describedby="user-login-help">
						</div>
						
						<!-- Password Field -->
						<div class="lgp-form-group">
							<label for="user_password" class="lgp-form-label">
								<?php esc_html_e( 'Password', 'loungenie-portal' ); ?>
							</label>
							<input 
								type="password"
								id="user_password"
								name="user_password"
								class="lgp-form-input"
								placeholder="<?php esc_attr_e( 'Enter your password', 'loungenie-portal' ); ?>"
								required
								autocomplete="current-password"
								aria-required="true">
						</div>
						
						<!-- Remember Me -->
						<div class="lgp-form-group lgp-form-remember">
							<label for="rememberme" class="lgp-checkbox">
								<input 
									type="checkbox"
									id="rememberme"
									name="rememberme"
									value="forever"
									aria-label="<?php esc_attr_e( 'Remember me for 14 days', 'loungenie-portal' ); ?>">
								<span><?php esc_html_e( 'Remember me for 14 days', 'loungenie-portal' ); ?></span>
							</label>
						</div>
						
						<!-- Login Button -->
						<button type="submit" class="lgp-btn lgp-btn-primary lgp-btn-large" name="wp-submit">
							<?php esc_html_e( 'Sign In', 'loungenie-portal' ); ?>
						</button>
					</form>
					
					<!-- Help Links -->
					<div class="lgp-form-help">
						<a href="<?php echo wp_lostpassword_url( home_url( '/login' ) ); ?>" class="lgp-help-link">
							<?php esc_html_e( 'Forgot your password?', 'loungenie-portal' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>
			
			<!-- Footer -->
			<div class="lgp-login-footer">
				<p><?php esc_html_e( '© 2025 LounGenie. All rights reserved.', 'loungenie-portal' ); ?></p>
				<div class="lgp-footer-links">
					<?php if ( has_page_on_front() ) : ?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lgp-footer-link">
							<?php esc_html_e( 'Back to Home', 'loungenie-portal' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( function_exists( 'pll_e' ) ) : ?>
						<span class="lgp-footer-separator">•</span>
						<div class="lgp-language-selector">
							<?php pll_e( 'Language' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	
	<?php wp_footer(); ?>
	
	<!-- Accessibility: Skip Link -->
	<a href="#lgp-login-form" class="lgp-skip-link">
		<?php esc_html_e( 'Skip to login form', 'loungenie-portal' ); ?>
	</a>
	
	<!-- Accessibility: Loading Indicator -->
	<div class="lgp-loading-overlay" id="lgp-loading" style="display:none;">
		<div class="lgp-spinner"></div>
		<p><?php esc_html_e( 'Authenticating...', 'loungenie-portal' ); ?></p>
	</div>
</body>
</html>
