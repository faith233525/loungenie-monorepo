<?php

/**
 * LGP Upgrade Shim
 * Provides dbDelta in non-WordPress contexts (tests/offline) without requiring wp-admin stub files.
 */

if ( ! function_exists( 'dbDelta' ) ) {
	// @phpstan-ignore-next-line trailingslashit and ABSPATH are WordPress core
	$lgp_upgrade_path = trailingslashit( ABSPATH ) . 'wp-admin/includes/upgrade.php';

	if ( file_exists( $lgp_upgrade_path ) ) {
		require_once $lgp_upgrade_path;
	} elseif ( ! function_exists( 'dbDelta' ) ) {
		// Minimal no-op shim for offline/test contexts.
		function dbDelta( $queries = '', $execute = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			return true;
		}
	}
}
