<?php
/**
 * Uninstall Script
 * Handles cleanup when plugin is uninstalled
 *
 * @package LounGenie Portal
 */

// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load database class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lgp-database.php';
require_once plugin_dir_path( __FILE__ ) . 'roles/support.php';
require_once plugin_dir_path( __FILE__ ) . 'roles/partner.php';

// Remove custom roles
LGP_Support_Role::remove();
LGP_Partner_Role::remove();

// Drop all database tables
LGP_Database::drop_tables();

// Remove options
delete_option( 'lgp_db_version' );
delete_option( 'lgp_settings' );

// Remove user meta for all users
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'lgp_company_id'" );

// Clear transients and cached data
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lgp_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lgp_%'" );

// Flush rewrite rules
flush_rewrite_rules();
