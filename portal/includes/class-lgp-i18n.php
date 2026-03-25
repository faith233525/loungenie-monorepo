<?php

/**
 * Internationalization & localization system.
 *
 * Handles multi-language support with translation strings.
 *
 * @package LounGenie Portal
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LGP_I18n class.
 *
 * Handles multi-language support and translation string registration.
 */
class LGP_I18n {

	const TEXT_DOMAIN = 'loungenie-portal';

	/**
	 * Initialize i18n system.
	 *
	 * @return void
	 */
	public static function init() {
		// Load plugin translations.
		add_action( 'plugins_loaded', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Register translation strings for .pot file generation..
		add_action( 'plugins_loaded', array( __CLASS__, 'register_strings' ) );
	}

	/**
	 * Load plugin translations from languages directory.
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		$loaded = load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			plugin_basename( LGP_PLUGIN_DIR ) . '/languages'
		);

		if ( ! $loaded && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LGP: Failed to load text domain: ' . self::TEXT_DOMAIN );
		}
	}

	/**
	 * Register all translatable strings.
	 *
	 * Used for .pot file generation and string scanning.
	 *
	 * @return void
	 */
	public static function register_strings() {
		// Core plugin strings.
		esc_html_e( 'LounGenie Portal', 'loungenie-portal' );
		esc_html_e( 'Manage partner companies, units, and service requests', 'loungenie-portal' );
		__( 'Dashboard', 'loungenie-portal' );
		__( 'Companies', 'loungenie-portal' );
		__( 'Units', 'loungenie-portal' );
		__( 'Service Requests', 'loungenie-portal' );
		__( 'Tickets', 'loungenie-portal' );
		__( 'Settings', 'loungenie-portal' );

		// User roles.
		__( 'LounGenie Support Team', 'loungenie-portal' );
		__( 'LounGenie Partner Company', 'loungenie-portal' );

		// Actions.
		__( 'Add New', 'loungenie-portal' );
		__( 'Edit', 'loungenie-portal' );
		__( 'Delete', 'loungenie-portal' );
		__( 'Save', 'loungenie-portal' );
		__( 'Cancel', 'loungenie-portal' );
		__( 'Submit', 'loungenie-portal' );
		__( 'Create', 'loungenie-portal' );
		__( 'Update', 'loungenie-portal' );
		__( 'Remove', 'loungenie-portal' );
		__( 'Export', 'loungenie-portal' );
		__( 'Import', 'loungenie-portal' );

		// Status strings.
		__( 'Active', 'loungenie-portal' );
		__( 'Inactive', 'loungenie-portal' );
		__( 'Pending', 'loungenie-portal' );
		__( 'Approved', 'loungenie-portal' );
		__( 'Rejected', 'loungenie-portal' );
		__( 'Completed', 'loungenie-portal' );
		__( 'In Progress', 'loungenie-portal' );
		__( 'On Hold', 'loungenie-portal' );

		// Form labels
		__( 'Company Name', 'loungenie-portal' );
		__( 'Email Address', 'loungenie-portal' );
		__( 'Phone Number', 'loungenie-portal' );
		__( 'Address', 'loungenie-portal' );
		__( 'City', 'loungenie-portal' );
		__( 'State', 'loungenie-portal' );
		__( 'ZIP Code', 'loungenie-portal' );
		__( 'Contact Person', 'loungenie-portal' );
		__( 'Message', 'loungenie-portal' );
		__( 'Notes', 'loungenie-portal' );
		__( 'Attachment', 'loungenie-portal' );

		// Unit-related.
		__( 'Unit Color', 'loungenie-portal' );
		__( 'Lock Brand', 'loungenie-portal' );
		__( 'Installation Date', 'loungenie-portal' );
		__( 'Service Date', 'loungenie-portal' );
		__( 'Venue Type', 'loungenie-portal' );
		__( 'Season', 'loungenie-portal' );
		__( 'Yellow', 'loungenie-portal' );
		__( 'Red', 'loungenie-portal' );
		__( 'Classic Blue', 'loungenie-portal' );
		__( 'Ice Blue', 'loungenie-portal' );
		__( 'MAKE', 'loungenie-portal' );
		__( 'L&F', 'loungenie-portal' );

		// Service request types.
		__( 'Installation', 'loungenie-portal' );
		__( 'Service', 'loungenie-portal' );
		__( 'Maintenance', 'loungenie-portal' );
		__( 'Repair', 'loungenie-portal' );
		__( 'Update', 'loungenie-portal' );

		// Priority levels.
		__( 'Low', 'loungenie-portal' );
		__( 'Medium', 'loungenie-portal' );
		__( 'High', 'loungenie-portal' );
		__( 'Urgent', 'loungenie-portal' );

		// Error & success messages.
		__( 'Error', 'loungenie-portal' );
		__( 'Success', 'loungenie-portal' );
		__( 'Warning', 'loungenie-portal' );
		__( 'Info', 'loungenie-portal' );
		__( 'Something went wrong. Please try again.', 'loungenie-portal' );
		__( 'Operation completed successfully.', 'loungenie-portal' );
		__( 'Please fill in all required fields.', 'loungenie-portal' );
		__( 'You do not have permission to access this resource.', 'loungenie-portal' );
		__( 'Not found.', 'loungenie-portal' );

		// Authentication.
		__( 'Login', 'loungenie-portal' );
		__( 'Logout', 'loungenie-portal' );
		__( 'Sign in with Microsoft', 'loungenie-portal' );
		__( 'Username', 'loungenie-portal' );
		__( 'Password', 'loungenie-portal' );
		__( 'Forgot Password', 'loungenie-portal' );
		__( 'Remember me', 'loungenie-portal' );
		__( 'Invalid credentials.', 'loungenie-portal' );
		__( 'Session expired. Please log in again.', 'loungenie-portal' );

		// Dashboard.
		__( 'Welcome', 'loungenie-portal' );
		__( 'Dashboard', 'loungenie-portal' );
		__( 'Overview', 'loungenie-portal' );
		__( 'Statistics', 'loungenie-portal' );
		__( 'Recent Activity', 'loungenie-portal' );
		__( 'Total Companies', 'loungenie-portal' );
		__( 'Total Units', 'loungenie-portal' );
		__( 'Open Tickets', 'loungenie-portal' );
		__( 'Pending Requests', 'loungenie-portal' );

		// Tickets.
		__( 'Ticket', 'loungenie-portal' );
		__( 'New Ticket', 'loungenie-portal' );
		__( 'Ticket Details', 'loungenie-portal' );
		__( 'Ticket History', 'loungenie-portal' );
		__( 'Reply', 'loungenie-portal' );
		__( 'Add Reply', 'loungenie-portal' );
		__( 'Close Ticket', 'loungenie-portal' );
		__( 'Reopen Ticket', 'loungenie-portal' );

		// Admin tools.
		__( 'System Health', 'loungenie-portal' );
		__( 'Security Log', 'loungenie-portal' );
		__( 'Query Performance', 'loungenie-portal' );
		__( 'API Documentation', 'loungenie-portal' );
		__( 'Database Migrations', 'loungenie-portal' );
		__( 'Environment Config', 'loungenie-portal' );

		// Integration labels.
		__( 'HubSpot Integration', 'loungenie-portal' );
		__( 'Microsoft 365 SSO', 'loungenie-portal' );
		__( 'Microsoft Outlook Integration', 'loungenie-portal' );
		__( 'Email Notifications', 'loungenie-portal' );
		__( 'API Key', 'loungenie-portal' );
		__( 'API Token', 'loungenie-portal' );
		__( 'Client ID', 'loungenie-portal' );
		__( 'Client Secret', 'loungenie-portal' );

		// Pluralization examples
		_n_noop( '%s company', '%s companies', 'loungenie-portal' );
		_n_noop( '%s unit', '%s units', 'loungenie-portal' );
		_n_noop( '%s ticket', '%s tickets', 'loungenie-portal' );
		_n_noop( '%s attachment', '%s attachments', 'loungenie-portal' );
	}

	/**
	 * Get translated string with fallback.
	 *
	 * @param string $string String to translate.
	 * @param string $context Optional context.
	 * @return string Translated string.
	 */
	public static function get_string( $string, $context = '' ) {
		if ( ! empty( $context ) ) {
			return _x( $string, $context, self::TEXT_DOMAIN );
		}
		return __( $string, self::TEXT_DOMAIN );
	}

	/**
	 * Echo translated string.
	 *
	 * @param string $string String to translate.
	 * @param string $context Optional context.
	 * @return void
	public static function echo_string( $string, $context = '' ) {
		if ( ! empty( $context ) ) {
			_ex( $string, $context, self::TEXT_DOMAIN );
		} else {
			_e( $string, self::TEXT_DOMAIN );
		}
	}

	/**
	 * Get translated plural string.
	 *
	 * @param string $singular Singular form.
	 * @param string $plural Plural form.
	 * @param int    $count Count.
	 * @param string $context Optional context.
	 * @return string Translated string.
	 */
	public static function get_plural( $singular, $plural, $count, $context = '' ) {
		if ( ! empty( $context ) ) {
			return _nx( $singular, $plural, $count, $context, self::TEXT_DOMAIN );
		}
		return _n( $singular, $plural, $count, self::TEXT_DOMAIN );
	}

	/**
	 * Format number for display
	 *
	 * @param int|float $number Number to format
	 * @param int       $decimals Number of decimal places
	 * @return string Formatted number
	 */
	public static function format_number( $number, $decimals = 0 ) {
		return number_format_i18n( $number, $decimals );
	}

	/**
	 * Format date/time for display
	 *
	 * @param string|int $date Date string or timestamp
	 * @param string     $format WordPress date format option
	 * @return string Formatted date
	 */
	public static function format_date( $date, $format = 'date_format' ) {
		$timestamp = is_numeric( $date ) ? $date : strtotime( $date );
		$format    = get_option( $format, 'Y-m-d' );
		return date_i18n( $format, $timestamp );
	}

	/**
	 * Format datetime for display
	 *
	 * @param string|int $date Date string or timestamp
	 * @return string Formatted datetime
	 */
	public static function format_datetime( $date ) {
		$timestamp = is_numeric( $date ) ? $date : strtotime( $date );
		$date_fmt  = get_option( 'date_format', 'Y-m-d' );
		$time_fmt  = get_option( 'time_format', 'H:i:s' );
		return date_i18n( $date_fmt . ' ' . $time_fmt, $timestamp );
	}

	/**
	 * Get locale variants for dropdown/selection
	 *
	 * @return array Available locales
	 */
	public static function get_available_locales() {
		return array(
			'en_US' => 'English (US)',
			'es_ES' => 'Español (Spain)',
			'es_MX' => 'Español (Mexico)',
			'fr_FR' => 'Français',
			'de_DE' => 'Deutsch',
			'it_IT' => 'Italiano',
			'pt_BR' => 'Português (Brasil)',
			'ja'    => '日本語',
			'zh_CN' => '中文 (Simplified)',
			'zh_TW' => '中文 (Traditional)',
			'ko_KR' => '한국어',
		);
	}

	/**
	 * Generate .pot file for translators
	 * Usage: Add to admin page for translators to download template
	 *
	 * @return string POT file content
	 */
	public static function generate_pot_file() {
		$pot_header = <<<'POT'
# LounGenie Portal Translation Template
# Copyright (C) 2024 LounGenie
# This file is distributed under the same license as the LounGenie Portal package.
# 
msgid ""
msgstr ""
"Project-Id-Version: LounGenie Portal 1.9.0\n"
"Report-Msgid-Bugs-To: support@poolsafeinc.com\n"
"POT-Creation-Date: " . date( 'Y-m-d H:i:s' ) . "+0000\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language: \n"
"X-Generator: Loco https://localise.biz/\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

POT;

		return $pot_header;
	}

	/**
	 * Check if a language is installed
	 *
	 * @param string $locale Language locale code
	 * @return bool True if installed
	 */
	public static function is_language_installed( $locale ) {
		$languages_dir = LGP_PLUGIN_DIR . 'languages';
		$lang_file     = "{$languages_dir}/loungenie-portal-{$locale}.mo";
		return file_exists( $lang_file );
	}

	/**
	 * Get list of installed translations
	 *
	 * @return array List of installed language codes
	 */
	public static function get_installed_languages() {
		$languages_dir = LGP_PLUGIN_DIR . 'languages';
		$installed     = array( 'en_US' ); // English always available

		if ( ! is_dir( $languages_dir ) ) {
			return $installed;
		}

		$files = glob( "{$languages_dir}/loungenie-portal-*.mo" );
		foreach ( $files as $file ) {
			preg_match( '/loungenie-portal-(.+)\.mo/', basename( $file ), $matches );
			if ( ! empty( $matches[1] ) ) {
				$installed[] = $matches[1];
			}
		}

		return array_unique( $installed );
	}

	/**
	 * Text domain for use in plugins/themes
	 *
	 * @return string Text domain
	 */
	public static function text_domain() {
		return self::TEXT_DOMAIN;
	}
}
