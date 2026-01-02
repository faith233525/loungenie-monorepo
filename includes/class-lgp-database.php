<?php

/**
 * Database Schema Management
 * Handles creation and management of all database tables
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Database {



	/**
	 * Initialize database management
	 */
	public static function init() {
		// If activation was skipped (e.g., zip swap or DB restore), ensure tables exist.
		self::ensure_tables_exist();
	}

	/**
	 * Ensure required tables exist; auto-create if missing.
	 * Uses a short cache to avoid repeated checks per request.
	 */
	private static function ensure_tables_exist() {
		// @phpstan-ignore-next-line get_transient is WordPress core function
		$cache_key = 'lgp_schema_verified';
		if ( get_transient( $cache_key ) ) {
			return;
		}

		global $wpdb;
		$tables = array(
			$wpdb->prefix . 'lgp_companies',
			$wpdb->prefix . 'lgp_management_companies',
			$wpdb->prefix . 'lgp_units',
			$wpdb->prefix . 'lgp_service_requests',
			$wpdb->prefix . 'lgp_tickets',
			$wpdb->prefix . 'lgp_gateways',
			$wpdb->prefix . 'lgp_help_guides',
			$wpdb->prefix . 'lgp_user_progress',
			$wpdb->prefix . 'lgp_ticket_attachments',
		);

		$missing = array();
		foreach ( $tables as $table ) {
			if ( ! self::table_exists( $table ) ) {
				$missing[] = $table;
			}
		}

		if ( ! empty( $missing ) ) {
			self::create_tables();
		}

		// Cache the check for 10 minutes to reduce DB load.
		// @phpstan-ignore-next-line set_transient and MINUTE_IN_SECONDS are WordPress core
		set_transient( $cache_key, 1, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Check if a table exists in the database.
	 *
	 * @param string $table_name Fully qualified table name.
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		// Avoid deprecated warnings when $found is null (table missing), and compare case-insensitively.
		if ( empty( $found ) ) {
			return false;
		}
		return strcasecmp( (string) $found, (string) $table_name ) === 0;
	}

	/**
	 * Create all database tables
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once LGP_PLUGIN_DIR . 'includes/lgp-upgrade-shim.php';

		// Companies table
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$sql_companies   = "CREATE TABLE $companies_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            state varchar(50),
            venue_type varchar(50),
            contact_name varchar(255),
            contact_email varchar(255),
            contact_phone varchar(50),
            management_company_id bigint(20) UNSIGNED,
            company_name varchar(255),
            management_company varchar(255),
            primary_contract varchar(50),
            primary_contract_status varchar(20),
            secondary_contract varchar(50),
            secondary_contract_status varchar(20),
            contract_notes text,
            season varchar(20),
            street_address varchar(255),
            city varchar(100),
            zip varchar(20),
            country varchar(100),
            top_colour varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY management_company_id (management_company_id),
            KEY venue_type (venue_type),
            KEY primary_contract_status (primary_contract_status),
            KEY secondary_contract_status (secondary_contract_status),
            KEY season (season)
        ) $charset_collate;";

		// Management Companies table
		$mgmt_companies_table = $wpdb->prefix . 'lgp_management_companies';
		$sql_mgmt_companies   = "CREATE TABLE $mgmt_companies_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            contact_name varchar(255),
            contact_email varchar(255),
            contact_phone varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

		// LounGenie Units table
		$units_table = $wpdb->prefix . 'lgp_units';
		$sql_units   = "CREATE TABLE $units_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id bigint(20) UNSIGNED NOT NULL,
            management_company_id bigint(20) UNSIGNED,
            unit_number varchar(100),
            address text,
            lock_type varchar(100),
            lock_brand varchar(50),
            lock_part varchar(100),
            unit_key varchar(100),
            color_tag varchar(50),
            season varchar(20) DEFAULT 'year-round',
            venue_type varchar(50),
            status varchar(50) DEFAULT 'active',
            install_date date,
            installation_date date,
            master_code varbinary(255),
            sub_master_code varbinary(255),
            latitude decimal(10,6),
            longitude decimal(10,6),
            service_history text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unit_number (unit_number),
            KEY company_id (company_id),
            KEY management_company_id (management_company_id),
            KEY status (status),
            KEY color_tag (color_tag),
            KEY season (season),
            KEY venue_type (venue_type),
            KEY lock_brand (lock_brand)
        ) $charset_collate;";

		// Service Requests table
		$service_requests_table = $wpdb->prefix . 'lgp_service_requests';
		$sql_service_requests   = "CREATE TABLE $service_requests_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id bigint(20) UNSIGNED NOT NULL,
            unit_id bigint(20) UNSIGNED,
            request_type varchar(50) NOT NULL,
            priority varchar(20) DEFAULT 'normal',
            status varchar(50) DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY company_id (company_id),
            KEY unit_id (unit_id),
            KEY status (status),
            KEY request_type (request_type)
        ) $charset_collate;";

		// Tickets table
		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$sql_tickets   = "CREATE TABLE $tickets_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_request_id bigint(20) UNSIGNED NOT NULL,
            status varchar(50) DEFAULT 'open',
            thread_history longtext,
            email_reference varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_request_id (service_request_id),
            KEY status (status),
            KEY email_reference (email_reference),
            KEY created_at (created_at)
        ) $charset_collate;";

		// Gateways table (support-only management)
		$gateways_table = $wpdb->prefix . 'lgp_gateways';
		$sql_gateways   = "CREATE TABLE $gateways_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id bigint(20) UNSIGNED NOT NULL,
            channel_number varchar(50),
            gateway_address varchar(255),
            unit_capacity int(11) DEFAULT 0,
            call_button tinyint(1) DEFAULT 0,
            included_equipment text,
            admin_password varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY company_id (company_id),
            KEY call_button (call_button),
            KEY channel_number (channel_number)
        ) $charset_collate;";

		// Help and Guides table (support uploads, partners view assigned)
		$help_guides_table = $wpdb->prefix . 'lgp_help_guides';
		$sql_help_guides   = "CREATE TABLE $help_guides_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            content_url varchar(500) NOT NULL,
            type varchar(50) DEFAULT 'video',
            category varchar(100) DEFAULT 'general',
            tags longtext,
            target_companies longtext,
            duration int(11),
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY created_by (created_by)
        ) $charset_collate;";

		// User progress table for Knowledge Center
		$user_progress_table = $wpdb->prefix . 'lgp_user_progress';
		$sql_user_progress   = "CREATE TABLE $user_progress_table (
            user_id bigint(20) UNSIGNED NOT NULL,
            guide_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, guide_id),
            KEY status (status),
            KEY guide_id (guide_id)
        ) $charset_collate;";

		// Ticket Attachments table (secure file storage with metadata)
		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';
		$sql_attachments   = "CREATE TABLE $attachments_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            file_name varchar(255) NOT NULL,
            file_type varchar(100),
            file_size bigint(20),
            file_path varchar(500),
            uploaded_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ticket_id (ticket_id),
            KEY uploaded_by (uploaded_by),
            KEY created_at (created_at),
            KEY file_type (file_type)
        ) $charset_collate;";

		// Service Notes table (technician field notes for service work)
		$service_notes_table = $wpdb->prefix . 'lgp_service_notes';
		$sql_service_notes   = "CREATE TABLE $service_notes_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id bigint(20) UNSIGNED NOT NULL,
            unit_id bigint(20) UNSIGNED,
            user_id bigint(20) UNSIGNED,
            service_type varchar(50) NOT NULL,
            technician_name varchar(255),
            notes text,
            travel_time int(11) DEFAULT 0,
            service_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY company_id (company_id),
            KEY unit_id (unit_id),
            KEY user_id (user_id),
            KEY service_date (service_date),
            KEY service_type (service_type)
        ) $charset_collate;";

		// Audit Log table (comprehensive event logging for compliance)
		$audit_log_table = $wpdb->prefix . 'lgp_audit_log';
		$sql_audit_log   = "CREATE TABLE $audit_log_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED,
            action varchar(100) NOT NULL,
            company_id bigint(20) UNSIGNED,
            meta longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY company_id (company_id),
            KEY created_at (created_at)
        ) $charset_collate;";

		// Execute table creation
		dbDelta( $sql_companies );
		dbDelta( $sql_mgmt_companies );
		dbDelta( $sql_units );
		dbDelta( $sql_service_requests );
		dbDelta( $sql_tickets );
		dbDelta( $sql_gateways );
		dbDelta( $sql_help_guides );
		dbDelta( $sql_user_progress );
		dbDelta( $sql_attachments );
		dbDelta( $sql_service_notes );
		dbDelta( $sql_audit_log );

		// Store database version
		update_option( 'lgp_db_version', LGP_VERSION );
	}

	/**
	 * Drop all plugin tables (used on uninstall)
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'lgp_companies',
			$wpdb->prefix . 'lgp_management_companies',
			$wpdb->prefix . 'lgp_units',
			$wpdb->prefix . 'lgp_service_requests',
			$wpdb->prefix . 'lgp_tickets',
			$wpdb->prefix . 'lgp_gateways',
			$wpdb->prefix . 'lgp_help_guides',
			$wpdb->prefix . 'lgp_ticket_attachments',
			$wpdb->prefix . 'lgp_service_notes',
			$wpdb->prefix . 'lgp_audit_log',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		delete_option( 'lgp_db_version' );
	}
}
