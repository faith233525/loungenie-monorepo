<?php

/**
 * Router Class
 * Handles /portal route and redirects
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

class LGP_Router
{

	/**
	 * Initialize router
	 */
	public static function init()
	{
		// Run login route as early as possible so caches/optimizers can't intercept.
		add_action('template_redirect', array(__CLASS__, 'handle_portal_login_route'), 0);
		add_action('template_redirect', array(__CLASS__, 'handle_portal_route'), 1);
	}

	/**
	 * Render a template with clean output buffer and proper headers
	 *
	 * @param string $template_path Path to template file
	 * @param string $fallback_html Fallback HTML if template fails
	 * @param array  $extra_headers Optional extra headers
	 */
	private static function render_template($template_path, $fallback_html, $extra_headers = array())
	{
		// Clear all output buffers
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		// Set standard headers
		status_header(200);
		nocache_headers();
		header('Content-Type: text/html; charset=UTF-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('X-Content-Type-Options: nosniff');

		// Add extra headers
		foreach ($extra_headers as $header) {
			header($header);
		}

		// Render template
		ob_start();
		@include $template_path;
		$html = (string) ob_get_clean();

		// Use fallback if template failed
		if ('' === $html) {
			$html = $fallback_html;
		}

		// Send content
		if (! headers_sent()) {
			header('Content-Length: ' . strlen($html));
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
		exit;
	}

	/**
	 * Handle /portal/login, /support-login, /partner-login routes
	 */
	public static function handle_portal_login_route()
	{
		// Never handle login routes in admin area
		if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
			return;
		}

		// Derive the current request path safely.
		$raw_request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
		$request_uri     = untrailingslashit(sanitize_text_field(strtok($raw_request_uri, '?')));

		// Support auto-SSO entry: /support-login
		if ('/support-login' === $request_uri) {
			// Kick off Microsoft SSO immediately
			if (class_exists('LGP_Microsoft_SSO')) {
				wp_safe_redirect(LGP_Microsoft_SSO::get_authorization_url());
				exit;
			}
		}

		// Partner login: /partner-login -> custom branded login form
		if ('/partner-login' === $request_uri) {
			$fallback = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Partner Login</title></head><body><h1>Partner Login</h1><p>Template failed to load. <a href="' . esc_url(home_url('/portal/login')) . '">Return to login options</a></p></body></html>';
			self::render_template(LGP_PLUGIN_DIR . 'templates/partner-login.php', $fallback);
		}

		// Login landing page: /portal/login or rewrite via query var
		$login_qv = get_query_var('lgp_portal_login');
		if ('/portal/login' === $request_uri || ! empty($login_qv)) {
			$fallback = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Portal Login</title><style>body{font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;margin:0;padding:2rem;background:#f7fafc;color:#1f2937}</style></head><body><main><h1 style="margin:0 0 1rem">Portal Login</h1><p>If you are seeing this fallback page, the login template failed to load. Please reinstall the plugin or contact support.</p><p><a href="' . esc_url(home_url('/partner-login')) . '">Partner Login</a> · <a href="' . esc_url(home_url('/support-login')) . '">Support SSO</a></p></main></body></html>';
			self::render_template(LGP_PLUGIN_DIR . 'templates/portal-login.php', $fallback, array('X-LGP-Portal-Login: 1'));
		}
	}

	/**
	 * Handle /portal route
	 */
	public static function handle_portal_route()
	{
		// Never handle portal routes in admin area or when WP context isn't available
		$is_admin_context = function_exists('is_admin') ? is_admin() : false;
		$doing_ajax       = function_exists('wp_doing_ajax') ? wp_doing_ajax() : false;
		$doing_cron       = function_exists('wp_doing_cron') ? wp_doing_cron() : false;

		if ($is_admin_context || $doing_ajax || $doing_cron) {
			return;
		}

		if (! get_query_var('lgp_portal')) {
			return;
		}

		// Check if user is authenticated
		if (! is_user_logged_in()) {
			// If already on the login landing, avoid looping
			$raw_current_path = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
			$current_path     = untrailingslashit(strtok($raw_current_path, '?'));

			if ('/portal/login' !== $current_path) {
				wp_safe_redirect(home_url('/portal/login'));
			}
			exit;
		}

		// Check if user has portal access (Support Team or Partner Company role)
		$current_user  = wp_get_current_user();
		$allowed_roles = array('lgp_support', 'lgp_partner');

		if (! array_intersect($allowed_roles, $current_user->roles)) {
			wp_die(
				esc_html__('Access Denied: You do not have permission to access the portal.', 'loungenie-portal'),
				esc_html__('Access Denied', 'loungenie-portal'),
				array('response' => 403)
			);
		}

		// Load portal shell
		self::load_portal();
		exit;
	}

	/**
	 * Load portal template
	 */
	private static function load_portal()
	{
		// Enqueue our assets (scoped CSS ensures minimal theme interference)
		add_action('wp_enqueue_scripts', array('LGP_Assets', 'enqueue_portal_assets'));

		// Check if specific section is requested
		$section = get_query_var('lgp_section');

		// Tickets / requests view scripts
		if (in_array($section, array('tickets', 'requests', 'history'), true)) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					wp_enqueue_script('lgp-tickets-view', LGP_ASSETS_URL . 'js/tickets-view.js', array('lgp-portal'), LGP_VERSION, true);
				},
				20
			);
		}

		// If map section and user is support, load map view directly
		if ('map' === $section && LGP_Auth::is_support()) {
			self::load_map_view();
			return;
		}

		// If gateways section and user is support, load gateway view
		if ('gateways' === $section && LGP_Auth::is_support()) {
			self::load_gateway_view();
			return;
		}

		// If help section, load help and guides view
		if ('help' === $section) {
			self::load_help_guides_view();
			return;
		}

		// If company profile section, load company profile view
		if ('company-profile' === $section || 0 === strpos($section, 'company-profile/')) {
			self::load_company_profile_view();
			return;
		}

		// Otherwise load portal shell (which includes dashboards)
		require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
	}

	/**
	 * Load map view in portal shell
	 */
	private static function load_map_view()
	{
		require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
	}

	/**
	 * Load gateway view in portal shell (support-only)
	 */
	private static function load_gateway_view()
	{
		wp_enqueue_script('lgp-gateway-view', LGP_ASSETS_URL . 'js/gateway-view.js', array('lgp-portal'), LGP_VERSION, true);
		require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
	}

	/**
	 * Load help and guides view in portal shell
	 */
	private static function load_help_guides_view()
	{
		wp_enqueue_script('lgp-help-guides-view', LGP_ASSETS_URL . 'js/help-guides-view.js', array('lgp-portal'), LGP_VERSION, true);
		require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
	}

	/**
	 * Load company profile view in portal shell
	 */
	private static function load_company_profile_view()
	{
		// Authorization handled in template
		require_once LGP_PLUGIN_DIR . 'templates/portal-shell.php';
	}
}
