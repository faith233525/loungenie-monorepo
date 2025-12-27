<?php
/**
 * Partner Company Role Definition
 *
 * Partner Company Capabilities:
 * - View only their company and management company
 * - View their LounGenie unit count
 * - Submit service / install / update requests
 * - Track request status and history
 * - Access a stable form that does not change
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Partner_Role {

	/**
	 * Register Partner role
	 */
	public static function register() {
		// Remove role if it exists (to update capabilities)
		remove_role( 'lgp_partner' );

		// Build capabilities array from capability infrastructure
		$caps = array( 'read' => true );
		foreach ( LGP_Capabilities::get_partner_capabilities() as $cap ) {
			$caps[ $cap ] = true;
		}

		// Add role with capabilities
		add_role(
			'lgp_partner',
			__( 'LounGenie Partner Company', 'loungenie-portal' ),
			$caps
		);

		// Grant capabilities to the role
		LGP_Capabilities::grant_capabilities_to_role( 'lgp_partner', LGP_Capabilities::get_partner_capabilities() );
	}

	/**
	 * Remove Partner role
	 */
	public static function remove() {
		remove_role( 'lgp_partner' );
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
