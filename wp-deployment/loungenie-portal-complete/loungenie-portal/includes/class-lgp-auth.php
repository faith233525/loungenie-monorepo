<?php
/**
 * Authentication Class
 * Handles user authentication and session management
 *
 * @package LounGenie Portal
 */

namespace LounGenie\Portal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_User;
use WP_Error;

/**
 * Authentication Handler
 * Manages user authentication, redirects, and audit logging.
 *
 * @package LounGenie\Portal
 */
class LGP_Auth {

	/**
	 * Initialize authentication system
	 */
	public static function init() {
		// Redirect after login to /portal if user has portal role.
		add_filter( 'login_redirect', array( __CLASS__, 'redirect_after_login' ), 10, 3 );

		// Audit logging for authentication events.
		add_action( 'wp_login', array( __CLASS__, 'log_login_success' ), 10, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'log_login_failed' ), 10, 2 );
		add_action( 'wp_logout', array( __CLASS__, 'log_logout' ) );
		add_action( 'password_reset', array( __CLASS__, 'log_password_reset' ), 10, 2 );
		add_action( 'profile_update', array( __CLASS__, 'log_password_change' ), 10, 2 );

		// Prevent partners from landing in WordPress admin; send to /portal instead.
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_admin_to_portal' ) );
	}

	/**
	 * Redirect users to portal after successful login.
	 *
	 * @param string           $redirect_to URL to redirect to.
	 * @param string           $request URL the user is coming from.
	 * @param WP_User|WP_Error $user User object.
	 * @return string Redirect URL.
	 */
	public static function redirect_after_login( $redirect_to, $request, $user ) {
		if ( ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
			return $redirect_to;
		}

		// Check if user has portal access.
		$portal_roles = array( 'lgp_support', 'lgp_partner' );
		if ( array_intersect( $portal_roles, (array) $user->roles ) ) {
			return home_url( '/portal' );
		}

		return $redirect_to;
	}

	/**
	 * Redirect non-admin portal users away from /wp-admin to /portal.
	 */
	public static function maybe_redirect_admin_to_portal() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return; // Allow AJAX.
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Only act on dashboard/admin pages.
		if ( ! is_admin() ) {
			return;
		}

		$current_user = wp_get_current_user();
		if ( empty( $current_user->ID ) ) {
			return;
		}

		// Allow users with management capabilities to access admin.
		if ( user_can( $current_user, 'manage_options' ) ) {
			return;
		}

		$portal_roles = array( 'lgp_support', 'lgp_partner' );
		if ( array_intersect( $portal_roles, (array) $current_user->roles ) ) {
			wp_safe_redirect( home_url( '/portal' ) );
			exit;
		}
	}

	/**
	 * Check if current user has Support Team role.
	 *
	 * @return bool True if user is support.
	 */
	public static function is_support() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();
		return in_array( 'lgp_support', (array) $current_user->roles, true );
	}

	/**
	 * Check if current user has Partner Company role.
	 *
	 * @return bool True if user is partner.
	 */
	public static function is_partner() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();
		return in_array( 'lgp_partner', (array) $current_user->roles, true );
	}

	/**
	 * Get current user's company ID (for partners)
	 *
	 * @return int|null
	 */
	public static function get_user_company_id() {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, 'lgp_company_id', true );
	}

	/**
	 * Log successful login
	 *
	 * @param string  $user_login Username
	 * @param WP_User $user User object
	 */
	public static function log_login_success( $user_login, $user ) {
		$company_id = self::get_user_company_id();

		LGP_Logger::log_event(
			$user->ID,
			'login_success',
			$company_id,
			array(
				'user_login' => $user_login,
				'user_email' => $user->user_email,
				'role'       => implode( ', ', $user->roles ),
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
			)
		);
	}

	/**
	 * Log failed login attempt
	 *
	 * @param string   $username Username attempted
	 * @param WP_Error $error Error object
	 */
	public static function log_login_failed( $username, $error ) {
		LGP_Logger::log_event(
			0,
			'login_failed',
			null,
			array(
				'username_attempted' => $username,
				'error_code'         => $error->get_error_code(),
				'ip_address'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
			)
		);
	}

	/**
	 * Log user logout
	 */
	public static function log_logout() {
		$user = wp_get_current_user();
		if ( $user->ID ) {
			$company_id = get_user_meta( $user->ID, 'lgp_company_id', true );

			LGP_Logger::log_event(
				$user->ID,
				'logout',
				$company_id,
				array(
					'user_login' => $user->user_login,
					'role'       => implode( ', ', $user->roles ),
				)
			);
		}
	}

	/**
	 * Log password reset
	 *
	 * @param WP_User $user User object
	 * @param string  $new_pass New password
	 */
	public static function log_password_reset( $user, $new_pass ) {
		$company_id = get_user_meta( $user->ID, 'lgp_company_id', true );

		LGP_Logger::log_event(
			$user->ID,
			'password_reset',
			$company_id,
			array(
				'user_login'   => $user->user_login,
				'reset_method' => 'email_link',
			)
		);
	}

	/**
	 * Log password change (on profile update)
	 *
	 * @param int     $user_id User ID
	 * @param WP_User $old_user_data Old user data
	 */
	public static function log_password_change( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );

		// Check if password changed
		if ( $user && $user->user_pass !== $old_user_data->user_pass ) {
			$company_id = get_user_meta( $user_id, 'lgp_company_id', true );

			LGP_Logger::log_event(
				$user_id,
				'password_changed',
				$company_id,
				array(
					'user_login'    => $user->user_login,
					'change_method' => 'profile_update',
				)
			);
		}
	}
}

// Provide global alias for legacy references
if ( ! class_exists( '\\LGP_Auth', false ) ) {
	class_alias( __NAMESPACE__ . '\\LGP_Auth', 'LGP_Auth' );
}
