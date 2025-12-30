<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options and data.
delete_option( 'lgp_settings' );
delete_option( 'lgp_version' );
delete_option( 'lgp_activated' );
