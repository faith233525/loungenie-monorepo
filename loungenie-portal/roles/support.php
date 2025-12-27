<?php
/**
 * Support Team Role Definition
 *
 * Support Team Capabilities:
 * - View all companies and management companies
 * - View all LounGenie units
 * - Track installs, service, maintenance, updates
 * - View all tickets
 * - View partner locations on a map
 * - Full dashboard access
 * - Can filter, search, sort all data
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Support_Role {

	/**
	 * Register Support role
	 */
	public static function register() {
		// Remove role if it exists (to update capabilities)
		remove_role( 'lgp_support' );

		// Build capabilities array from capability infrastructure
		$caps = array( 'read' => true );
		foreach ( LGP_Capabilities::get_support_capabilities() as $cap ) {
			$caps[ $cap ] = true;
		}

		// Add role with capabilities
		add_role(
			'lgp_support',
			__( 'LounGenie Support Team', 'loungenie-portal' ),
			$caps
		);

		// Grant capabilities to the role
		LGP_Capabilities::grant_capabilities_to_role( 'lgp_support', LGP_Capabilities::get_support_capabilities() );
	}

	/**
	 * Remove Support role
	 */
	public static function remove() {
		remove_role( 'lgp_support' );
	}

	/**
	 * Check if user has specific capability
	 *
	 * @param string $capability
	 * @return bool
	 */
	public static function has_capability( $capability ) {
		return current_user_can( $capability );
	}
}
