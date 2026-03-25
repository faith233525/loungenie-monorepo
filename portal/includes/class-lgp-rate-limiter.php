<?php
/**
 * Soft Rate Limiting for Shared Hosting
 * Per-user and per-IP limits to prevent abuse
 *
 * Uses WordPress transients (soft limiting, not enforced strictly)
 * Prevents accidental overuse and basic bot damage.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate limiting per-user and per-IP to prevent shared hosting abuse.
 */
class LGP_Rate_Limiter {

	/**
	 * Check if user has exceeded rate limit.
	 *
	 * @param string $action      Action identifier (e.g., 'ticket_create').
	 * @param int    $limit       Max actions allowed.
	 * @param int    $window_secs Time window in seconds.
	 * @return bool True if limit exceeded, false otherwise
	 */
	public static function is_limited( $action, $limit = 5, $window_secs = 3600 ) {
		$user_id = get_current_user_id();
		$ip      = self::get_client_ip();

		$key      = "lgp_rate_{$action}_{$user_id}";
		$ip_key   = "lgp_rate_{$action}_ip_{$ip}";
		$count    = (int) get_transient( $key );
		$ip_count = (int) get_transient( $ip_key );

		// Log attempt.
		if ( $count >= $limit || $ip_count >= ( $limit * 5 ) ) {
			self::log_limit_exceeded( $action, $user_id, $ip );
			return true;
		}

		return false;
	}

	/**
	 * Increment action counter.
	 *
	 * @param string $action      Action identifier.
	 * @param int    $window_secs Time window in seconds.
	 */
	public static function increment( $action, $window_secs = 3600 ) {
		$user_id = get_current_user_id();
		$ip      = self::get_client_ip();

		$key      = "lgp_rate_{$action}_{$user_id}";
		$ip_key   = "lgp_rate_{$action}_ip_{$ip}";
		$count    = (int) get_transient( $key );
		$ip_count = (int) get_transient( $ip_key );

		set_transient( $key, $count + 1, $window_secs );
		set_transient( $ip_key, $ip_count + 1, $window_secs );
	}

	/**
	 * Reset counter for action.
	 *
	 * @param string $action Action identifier.
	 */
	public static function reset( $action ) {
		$user_id = get_current_user_id();
		$ip      = self::get_client_ip();

		$key    = "lgp_rate_{$action}_{$user_id}";
		$ip_key = "lgp_rate_{$action}_ip_{$ip}";

		delete_transient( $key );
		delete_transient( $ip_key );
	}

	/**
	 * Get remaining quota.
	 *
	 * @param string $action Action identifier.
	 * @param int    $limit  Max allowed.
	 * @return int Remaining quota.
	 */
	public static function get_remaining( $action, $limit = 5 ) {
		$user_id = get_current_user_id();
		$key     = "lgp_rate_{$action}_{$user_id}";
		$count   = (int) get_transient( $key );

		return max( 0, $limit - $count );
	}

	/**
	 * Get safe client IP.
	 *
	 * @return string Client IP address.
	 */
	private static function get_client_ip() {
		// Check for IP from shared internet.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Handle multiple IPs (take first).
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} else {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		}

		// Validate IP.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '0.0.0.0';
		}

		return $ip;
	}

	/**
	 * Log rate limit exceeded event.
	 *
	 * @param string $action  Action identifier.
	 * @param int    $user_id User ID.
	 * @param string $ip      Client IP.
	 */
	private static function log_limit_exceeded( $action, $user_id, $ip ) {
		LGP_Logger::log_event(
			$user_id,
			'rate_limit_exceeded',
			0,
			array(
				'action' => $action,
				'ip'     => $ip,
			)
		);
	}
}
