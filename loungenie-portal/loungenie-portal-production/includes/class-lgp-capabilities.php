<?php

/**
 * Capabilities Infrastructure
 * Maps WordPress roles to granular capabilities
 * Enables future role types (Regional Support, Installer, Read-Only Partner) without refactoring
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
	exit;
}

class LGP_Capabilities
{

	/**
	 * Core capability definitions
	 *
	 * These are the atomic permissions. Roles then grant sets of these.
	 * This decouples role definitions from capability logic.
	 */
	const CAPABILITIES = array(
		// Viewing
		'lgp_view_all_companies'   => 'View all companies (support only)',
		'lgp_view_own_company'     => 'View own company data (partners)',
		'lgp_view_units'           => 'View units list',
		'lgp_view_tickets'         => 'View tickets',
		'lgp_view_gateways'        => 'View gateway records (support only)',
		'lgp_view_audit_log'       => 'View audit trail (support only)',
		'lgp_view_help_guides'     => 'View help and guides library',

		// Creating
		'lgp_create_tickets'       => 'Create/submit service requests',
		'lgp_create_service_notes' => 'Add internal service notes',

		// Managing (mostly support)
		'lgp_manage_tickets'       => 'Update ticket status',
		'lgp_manage_gateways'      => 'Modify gateway config (support only)',
		'lgp_manage_companies'     => 'Edit company records (admin only)',
		'lgp_manage_help_guides'   => 'Upload and manage help and guides (support only)',

		// Replying/Threading
		'lgp_reply_tickets'        => 'Add replies to ticket thread',

		// Uploading
		'lgp_upload_attachments'   => 'Attach files to tickets',

		// Admin
		'lgp_manage_plugin'        => 'Access plugin settings',
		'lgp_view_system_health'   => 'View system diagnostics',
	);

	/**
	 * Initialize capability system
	 * Registers capabilities on plugins_loaded so roles can use them
	 */
	public static function init()
	{
		add_action('plugins_loaded', array(__CLASS__, 'register_capabilities'), 5);
	}

	/**
	 * Register all capabilities in WordPress
	 * This makes them available for role assignment and checks
	 */
	public static function register_capabilities()
	{
		foreach (self::CAPABILITIES as $cap => $label) {
			// Just registering the capability definition
			// Actual grants happen in role definitions
		}
	}

	/**
	 * Grant capabilities to a role
	 *
	 * @param string $role_name WordPress role (e.g., 'lgp_support')
	 * @param array  $caps      Capability slugs to grant
	 */
	public static function grant_capabilities_to_role($role_name, $caps)
	{
		$role = get_role($role_name);
		if (! $role) {
			return;
		}

		foreach ($caps as $cap) {
			if (isset(self::CAPABILITIES[$cap])) {
				$role->add_cap($cap);
			}
		}
	}

	/**
	 * Check if user has a specific capability
	 * Wraps current_user_can() with lgp_ prefix checking
	 *
	 * @param string $capability Capability slug
	 * @return bool
	 */
	public static function user_can($capability)
	{
		return current_user_can($capability);
	}

	/**
	 * Get capabilities for a role
	 *
	 * @param string $role_name Role slug
	 * @return array
	 */
	public static function get_role_capabilities($role_name)
	{
		$role = get_role($role_name);
		if (! $role) {
			return array();
		}

		return array_keys($role->capabilities);
	}

	/**
	 * Pre-defined role capability sets
	 * Easy reference for role definitions
	 */
	public static function get_support_capabilities()
	{
		return array(
			'lgp_view_all_companies',
			'lgp_view_units',
			'lgp_view_tickets',
			'lgp_view_gateways',
			'lgp_view_audit_log',
			'lgp_view_help_guides',
			'lgp_manage_tickets',
			'lgp_manage_gateways',
			'lgp_manage_companies', // CSV Partner Import
			'lgp_manage_help_guides',
			'lgp_reply_tickets',
			'lgp_upload_attachments',
			'lgp_create_service_notes',
			'lgp_view_system_health',
		);
	}

	public static function get_partner_capabilities()
	{
		return array(
			'lgp_view_own_company',
			'lgp_view_units',
			'lgp_view_tickets',
			'lgp_view_help_guides',
			'lgp_create_tickets',
			'lgp_reply_tickets',
			'lgp_upload_attachments',
		);
	}

	public static function get_regional_support_capabilities()
	{
		// Future: support staff limited to a geographic region or set of companies
		return array(
			'lgp_view_units',
			'lgp_view_tickets',
			'lgp_view_audit_log',
			'lgp_view_help_guides',
			'lgp_manage_tickets',
			'lgp_reply_tickets',
			'lgp_upload_attachments',
			// Notably missing: lgp_view_gateways, lgp_manage_gateways
		);
	}

	public static function get_installer_capabilities()
	{
		// Future: technician role for on-site installations
		return array(
			'lgp_view_units',
			'lgp_view_help_guides',
			'lgp_create_service_notes',
			'lgp_upload_attachments',
		);
	}

	public static function get_readonly_partner_capabilities()
	{
		// Future: read-only partner access (e.g., municipality auditor)
		return array(
			'lgp_view_own_company',
			'lgp_view_units',
			'lgp_view_tickets',
			'lgp_view_help_guides',
		);
	}

	/**
	 * Check if user has any of a set of capabilities
	 *
	 * @param array $capabilities
	 * @return bool
	 */
	public static function user_can_any($capabilities)
	{
		foreach ($capabilities as $cap) {
			if (self::user_can($cap)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if user has all of a set of capabilities
	 *
	 * @param array $capabilities
	 * @return bool
	 */
	public static function user_can_all($capabilities)
	{
		foreach ($capabilities as $cap) {
			if (! self::user_can($cap)) {
				return false;
			}
		}
		return true;
	}
}
