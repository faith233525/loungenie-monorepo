<?php
/**
 * Plugin Uninstall File
 *
 * Handles cleanup when plugin is deleted from WordPress.
 * Note: This file only runs when plugin is DELETED (not deactivated).
 *
 * @package PoolSafe Portal
 */

// Exit if uninstall is not called from WordPress admin
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// ============================================================================
// REMOVE ALL PLUGIN OPTIONS
// ============================================================================

// Delete all options that start with psp_
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'psp_%'" );

// Delete all transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_psp_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_psp_%'" );

// ============================================================================
// REMOVE USER METADATA
// ============================================================================

// Partner ID associations
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'psp_%'" );

// ============================================================================
// REMOVE CUSTOM POST METADATA
// ============================================================================

// All PSP metadata from posts
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'psp_%'" );

// ============================================================================
// REMOVE ALL CUSTOM TABLES
// ============================================================================

// List of all PSP tables created in class-psp-activator.php
$tables = array(
	'psp_users',
	'psp_partners',
	'psp_company_users',
	'psp_tickets',
	'psp_ticket_replies',
	'psp_service_records',
	'psp_notifications',
	'psp_user_notification_preferences',
	'psp_notification_queue',
	'psp_audit_log',
	'psp_profile_notes',
	'psp_attachments',
	'psp_partner_activity',
	'psp_video_categories',
	'psp_videos',
	'psp_video_views',
	'psp_activity_logs',
	'psp_gdpr_consents',
	'psp_internal_notes',
);

foreach ( $tables as $table ) {
	$table_name = $wpdb->prefix . $table;
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

// ============================================================================
// REMOVE CUSTOM ROLES AND CAPABILITIES
// ============================================================================

// Remove custom roles
remove_role( 'pool_safe_partner' );
remove_role( 'pool_safe_support' );
remove_role( 'pool_safe_admin' );
remove_role( 'psp_partner' );
remove_role( 'psp_support' );
remove_role( 'psp_admin' );

// Remove capabilities from administrator role
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
	$capabilities = array(
		'psp_manage_portal',
		'psp_manage_settings',
		'psp_list_tickets',
		'psp_read_ticket',
		'psp_create_ticket',
		'psp_edit_ticket',
		'psp_delete_ticket',
		'psp_manage_all',
		'psp_support',
		'psp_view_audit_log',
		'psp_manage_api_settings',
	);
	
	foreach ( $capabilities as $cap ) {
		$admin_role->remove_cap( $cap );
	}
}

// ============================================================================
// REMOVE CUSTOM POST TYPES
// ============================================================================

// Delete all PSP custom post types
$post_types = array( 'psp_partner', 'psp_training_video', 'psp_notification', 'psp_csv_export' );

foreach ( $post_types as $post_type ) {
	$posts = get_posts(
		array(
			'post_type'   => $post_type,
			'numberposts' => -1,
			'post_status' => 'any',
		)
	);
	
	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}
}

// ============================================================================
// CLEAR SCHEDULED CRON EVENTS
// ============================================================================

// Remove all PSP cron events
$cron_hooks = array(
	'psp_cleanup_old_logs',
	'psp_sync_partners',
	'psp_send_notifications',
	'psp_backup_data',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
}

// ============================================================================
// FLUSH REWRITE RULES
// ============================================================================

flush_rewrite_rules();

// ============================================================================
// LOG UNINSTALL
// ============================================================================

// Load logger if available for final log
if ( file_exists( __DIR__ . '/includes/class-psp-logger.php' ) ) {
	require_once __DIR__ . '/includes/class-psp-logger.php';
	PSP_Logger::info( 'PoolSafe Portal plugin uninstalled and all data removed on ' . current_time( 'mysql' ) );
}
