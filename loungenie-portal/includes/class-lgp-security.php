<?php
/**
 * LounGenie Portal Security Headers
 *
 * Implements security headers including:
 * - Content Security Policy (CSP)
 * - Strict-Transport-Security (HSTS)
 * - X-Content-Type-Options
 * - X-Frame-Options
 * - Referrer-Policy
 * - Permissions-Policy
 *
 * @package LounGenie Portal
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Security {

	/**
	 * Current CSP nonce
	 */
	private static $csp_nonce = null;

	/**
	 * Initialize security headers
	 */
	public static function init() {
		add_action( 'send_headers', array( __CLASS__, 'set_security_headers' ) );
	}

	/**
	 * Set HTTP security headers
	 */
	public static function set_security_headers() {
		// Only apply to HTTPS connections
		if ( ! is_ssl() ) {
			return;
		}

		// Allow filter to disable all headers
		if ( ! apply_filters( 'lgp_security_headers_enabled', true ) ) {
			return;
		}

		// HSTS: Force HTTPS for 2 years
		header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload' );

		// Prevent MIME type sniffing
		header( 'X-Content-Type-Options: nosniff' );

		// Control framing
		$frame_option = apply_filters( 'lgp_frame_options', 'SAMEORIGIN' );
		if ( $frame_option ) {
			header( 'X-Frame-Options: ' . $frame_option );
		}

		// Referrer policy
		$referrer = apply_filters( 'lgp_referrer_policy', 'strict-origin-when-cross-origin' );
		if ( $referrer ) {
			header( 'Referrer-Policy: ' . $referrer );
		}

		// Permissions policy (disable unnecessary features)
		$permissions = apply_filters( 'lgp_permissions_policy', 'geolocation=(), microphone=(), camera=()' );
		if ( $permissions ) {
			header( 'Permissions-Policy: ' . $permissions );
		}

		// XSS protection (legacy, but still useful)
		header( 'X-XSS-Protection: 1; mode=block' );

		// Content Security Policy
		self::set_csp_header();
	}

	/**
	 * Set Content Security Policy header
	 */
	private static function set_csp_header() {
		// Generate nonce for inline scripts/styles
		self::$csp_nonce = bin2hex( random_bytes( 16 ) );

		// Define CSP directives
		$directives = array(
			'default-src'     => "'self'",
			'connect-src'     => array(
				"'self'",
				'https://login.microsoftonline.com',
				'https://graph.microsoft.com',
				'https://api.hubapi.com',
				'https://unpkg.com',
				'https://cdnjs.cloudflare.com',
			),
			'img-src'         => array(
				"'self'",
				'data:',
				'https:',
				'https://*.tile.openstreetmap.org',
			),
			'style-src'       => array(
				"'self'",
				"'nonce-" . self::$csp_nonce . "'",
				"'unsafe-inline'", // Allow for WordPress admin compatibility
				'https://fonts.googleapis.com',
				'https://cdnjs.cloudflare.com',
				'https://unpkg.com',
			),
			'script-src'      => array(
				"'self'",
				"'nonce-" . self::$csp_nonce . "'",
				'https://login.microsoftonline.com',
				'https://cdnjs.cloudflare.com',
				'https://unpkg.com',
			),
			// Allow embedding videos from YouTube and Vimeo in Knowledge Center
			'frame-src'       => array(
				"'self'",
				'https://www.youtube.com',
				'https://www.youtube-nocookie.com',
				'https://player.vimeo.com',
			),
			// Allow direct video sources
			'media-src'       => array(
				"'self'",
				'https:'
			),
			'font-src'        => array(
				"'self'",
				'data:',
				'https://fonts.gstatic.com',
			),
			'frame-ancestors' => "'self'",
			'base-uri'        => "'self'",
			'form-action'     => array(
				"'self'",
				'https://login.microsoftonline.com',
			),
		);

		// Allow plugins to modify directives
		$directives = apply_filters( 'lgp_csp_directives', $directives, self::$csp_nonce );

		// Build CSP string
		$csp_parts = array();
		foreach ( $directives as $directive => $values ) {
			if ( is_array( $values ) ) {
				$values = implode( ' ', $values );
			}
			if ( empty( $values ) ) {
				continue;
			}
			$csp_parts[] = $directive . ' ' . $values;
		}

		if ( empty( $csp_parts ) ) {
			return;
		}

		$csp = implode( '; ', $csp_parts );

		// Add report-uri if configured
		$report_uri = apply_filters( 'lgp_csp_report_uri', '' );
		if ( $report_uri ) {
			$csp .= '; report-uri ' . $report_uri;
		}

		// Enforce or report-only mode
		$mode        = apply_filters( 'lgp_csp_mode', 'enforce' ); // enforce | report-only
		$header_name = ( 'report-only' === $mode )
			? 'Content-Security-Policy-Report-Only'
			: 'Content-Security-Policy';

		header( $header_name . ': ' . $csp );

		// Store nonce for later use
		if ( ! defined( 'LGP_CSP_NONCE' ) ) {
			define( 'LGP_CSP_NONCE', self::$csp_nonce );
		}
	}

	/**
	 * Get current CSP nonce
	 *
	 * @return string CSP nonce
	 */
	public static function get_csp_nonce() {
		if ( defined( 'LGP_CSP_NONCE' ) ) {
			return LGP_CSP_NONCE;
		}
		return self::$csp_nonce;
	}

	/**
	 * Enhanced nonce verification with timing attack prevention
	 *
	 * @param string $nonce  Nonce to verify
	 * @param string $action Action name
	 * @return bool True if valid
	 */
	public static function verify_nonce( $nonce, $action ) {
		$result = wp_verify_nonce( $nonce, $action );

		// Add small delay on failure to prevent timing attacks
		if ( ! $result ) {
			usleep( rand( 100000, 500000 ) ); // 100-500ms delay
		}

		return (bool) $result;
	}

	/**
	 * Sanitize and validate email addresses
	 *
	 * @param string $email Email address
	 * @return string|false Sanitized email or false if invalid
	 */
	public static function sanitize_email( $email ) {
		$email = sanitize_email( $email );
		return is_email( $email ) ? $email : false;
	}

	/**
	 * Sanitize URL with whitelist check
	 *
	 * @param string $url URL to sanitize
	 * @param array  $allowed_hosts Allowed host whitelist
	 * @return string|false Sanitized URL or false if not allowed
	 */
	public static function sanitize_url( $url, $allowed_hosts = array() ) {
		$url = esc_url_raw( $url );

		if ( empty( $allowed_hosts ) ) {
			return $url;
		}

		$parsed = parse_url( $url );
		$host   = isset( $parsed['host'] ) ? $parsed['host'] : '';

		foreach ( $allowed_hosts as $allowed_host ) {
			// PHP 7.4-compatible ends-with check (polyfill for str_ends_with)
			$dot_host = '.' . $allowed_host;
			$ends_with = false;
			if ( '' !== $host && '' !== $allowed_host ) {
				$len = strlen( $dot_host );
				$ends_with = ( $len <= strlen( $host ) ) && ( substr( $host, -$len ) === $dot_host );
			}

			if ( $host === $allowed_host || $ends_with ) {
				return $url;
			}
		}

		return false;
	}

	/**
	 * Generate secure random token
	 *
	 * @param int $length Token length
	 * @return string Hex token
	 */
	public static function generate_token( $length = 32 ) {
		return bin2hex( random_bytes( $length ) );
	}
}
