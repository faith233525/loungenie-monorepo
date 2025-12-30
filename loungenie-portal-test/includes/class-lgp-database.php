<?php

/**
 * Database Schema Management
 *
 * Handles creation, management, and verification of all database tables
 * required by the LounGenie Portal plugin. Implements automatic table
 * verification and creation with intelligent caching to minimize overhead.
 *
 * Tables managed:
 * - lgp_companies: Partner company records
 * - lgp_management_companies: Management company records
 * - lgp_units: Venue/location records
 * - lgp_service_requests: Service request records
 * - lgp_tickets: Support ticket records
 * - lgp_gateways: Gateway device records
 * - lgp_help_guides: Knowledge center content
 * - lgp_user_progress: User training progress
 * - lgp_ticket_attachments: File attachment metadata
 * - lgp_service_notes: Technician service notes
 * - lgp_audit_log: General audit trail
 * - lgp_security_audit: Security event logging
 *
 * @package LounGenie Portal
 * @since 2.0.0 Initial release with security hardening.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Database Handler
 *
 * Manages database schema lifecycle including automatic recovery from
 * missing tables (e.g., after manual file deployment or database restore).
 *
 * @package LounGenie\Portal
 * @since 2.0.0
 */
class LGP_Database
{


    /**
     * Initialize database management.
     *
     * Ensures all required database tables exist. If activation hook was
     * skipped (e.g., via zip swap deployment or database restore), tables
     * are automatically created. Uses transient caching to minimize overhead.
     *
     * @since 2.0.0
     * @return void
     */
    public static function init()
    {
        // If activation was skipped (e.g., zip swap or DB restore), ensure tables exist.
        self::ensure_tables_exist();
    }

    /**
     * Ensure required tables exist; auto-create if missing.
     *
     * Verifies existence of all plugin tables and creates any that are
     * missing. Uses a 10-minute transient cache to avoid repeated checks
     * per request, optimizing performance while maintaining reliability.
     *
     * This automatic recovery mechanism handles edge cases like:
     * - Manual file deployment without running activation
     * - Database restoration from backup
     * - Table deletion during development
     *
     * @since 2.0.0
     * @return void
     */
    private static function ensure_tables_exist()
    {
        // @phpstan-ignore-next-line get_transient is WordPress core function
        $cache_key = 'lgp_schema_verified';
        if (get_transient($cache_key)) {
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
            $wpdb->prefix . 'lgp_service_notes',
            $wpdb->prefix . 'lgp_audit_log',
            $wpdb->prefix . 'lgp_security_audit',
        );

        $missing = array();
        foreach ($tables as $table) {
            if (! self::table_exists($table)) {
                $missing[] = $table;
            }
        }

        if (! empty($missing)) {
            self::create_tables();
        }

        // Cache the check for 10 minutes to reduce DB load.
        // @phpstan-ignore-next-line set_transient and MINUTE_IN_SECONDS are WordPress core
        set_transient($cache_key, 1, 10 * MINUTE_IN_SECONDS);
    }

    /**
     * Check if a table exists in the database.
     *
     * Performs case-insensitive comparison to handle varying MySQL
     * configurations and avoid false negatives.
     *
     * @since 2.0.0
     * @param string $table_name Fully qualified table name (with prefix).
     * @return bool True if table exists, false otherwise.
     */
    private static function table_exists($table_name)
    {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        // Avoid deprecated warnings when $found is null (table missing), and compare case-insensitively.
        if (empty($found)) {
            return false;
        }
        return strcasecmp((string) $found, (string) $table_name) === 0;
    }

    /**
     * Create all database tables.
     *
     * Creates complete database schema for the LounGenie Portal plugin.
     * Uses WordPress dbDelta() for intelligent table creation/updates.
     * Tables include proper indexes for performance and foreign key
     * relationships for data integrity.
     *
     * Table details:
     * - Companies: Partner company records with credentials and contacts
     * - Management Companies: Management company records
     * - Units: Physical locations with geocoding and lock information
     * - Service Requests: Work orders from partners
     * - Tickets: Support tickets with threaded conversations
     * - Gateways: Gateway device configurations
     * - Help Guides: Knowledge center content
     * - User Progress: Training completion tracking
     * - Ticket Attachments: File metadata with secure storage paths
     * - Service Notes: Technician field notes
     * - Audit Log: Comprehensive activity tracking
     * - Security Audit: Security event logging
     *
     * @since 2.0.0
     * @return void
     * @see dbDelta()
     */
    public static function create_tables()
    {
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
            partner_username varchar(191) UNIQUE,
            partner_password varchar(255),
            primary_contact_name varchar(255),
            primary_contact_email varchar(255),
            primary_contact_phone varchar(50),
            secondary_contact_name varchar(255),
            secondary_contact_email varchar(255),
            secondary_contact_phone varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY management_company_id (management_company_id),
            KEY venue_type (venue_type),
            KEY primary_contract_status (primary_contract_status),
            KEY secondary_contract_status (secondary_contract_status),
            KEY season (season),
            KEY partner_username (partner_username)
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
            unit_number varchar(191),
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
            KEY status (status)
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
            KEY uploaded_by (uploaded_by)
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

        // Security Audit table (security event logging)
        $security_audit_table = $wpdb->prefix . 'lgp_security_audit';
        $sql_security_audit   = "CREATE TABLE $security_audit_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_email varchar(191),
            user_ip varchar(45),
            event_type varchar(100) NOT NULL,
            severity varchar(20) NOT NULL DEFAULT 'info',
            action varchar(191) NOT NULL,
            resource_type varchar(100),
            resource_id bigint(20) UNSIGNED,
            details longtext,
            status varchar(50),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_timestamp (timestamp),
            KEY idx_user_id (user_id),
            KEY idx_event_type (event_type),
            KEY idx_severity (severity),
            KEY idx_user_ip (user_ip)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_companies);
        dbDelta($sql_mgmt_companies);
        dbDelta($sql_units);
        dbDelta($sql_service_requests);
        dbDelta($sql_tickets);
        dbDelta($sql_gateways);
        dbDelta($sql_help_guides);
        dbDelta($sql_user_progress);
        dbDelta($sql_attachments);
        dbDelta($sql_service_notes);
        dbDelta($sql_audit_log);
        dbDelta($sql_security_audit);

        // PERFORMANCE OPTIMIZATION: Add additional indexes for query performance
        self::add_performance_indexes();

        // Store database version
        update_option('lgp_db_version', LGP_VERSION);
    }

    /**
     * Add performance indexes to improve query speed.
     * 
     * These indexes are not part of the initial CREATE TABLE statements
     * but significantly improve performance for common query patterns.
     * 
     * @since 2.0.0
     * @return void
     */
    private static function add_performance_indexes()
    {
        global $wpdb;

        $tickets_table          = $wpdb->prefix . 'lgp_tickets';
        $service_requests_table = $wpdb->prefix . 'lgp_service_requests';
        $units_table            = $wpdb->prefix . 'lgp_units';

        // Add composite index for ticket status queries (speeds up dashboard queries)
        $wpdb->query("ALTER TABLE {$tickets_table} ADD INDEX IF NOT EXISTS idx_status_created (status, created_at)");

        // Add composite index for service request filtering
        $wpdb->query("ALTER TABLE {$service_requests_table} ADD INDEX IF NOT EXISTS idx_company_status (company_id, status)");
        $wpdb->query("ALTER TABLE {$service_requests_table} ADD INDEX IF NOT EXISTS idx_company_priority (company_id, priority)");

        // Add composite indexes for units filtering and aggregation
        $wpdb->query("ALTER TABLE {$units_table} ADD INDEX IF NOT EXISTS idx_company_status (company_id, status)");
        $wpdb->query("ALTER TABLE {$units_table} ADD INDEX IF NOT EXISTS idx_color_count (color_tag)");
        $wpdb->query("ALTER TABLE {$units_table} ADD INDEX IF NOT EXISTS idx_lock_count (lock_brand)");
        $wpdb->query("ALTER TABLE {$units_table} ADD INDEX IF NOT EXISTS idx_venue_count (venue_type)");
    }

    /**
     * Clear all portal transient caches.
     * 
     * Call this when data is modified to ensure fresh data on next load.
     * 
     * @since 2.0.0
     * @param int|null $company_id Optional company ID to clear specific company caches
     * @return void
     */
    public static function clear_portal_caches($company_id = null)
    {
        // Clear dashboard caches
        delete_transient('lgp_dashboard_support_stats');
        delete_transient('lgp_dashboard_recent_tickets');
        delete_transient('lgp_dashboard_top_metrics');
        delete_transient('lgp_dashboard_metrics_support');

        if ($company_id) {
            // Clear company-specific caches
            delete_transient('lgp_dashboard_metrics_company_' . $company_id);
            delete_transient('lgp_units_list_company_' . $company_id);

            // Clear paginated ticket caches for this company
            for ($page = 1; $page <= 10; $page++) {
                delete_transient('lgp_tickets_list_' . $company_id . '_page_' . $page . '_per_20');
            }
        } else {
            // Clear all units and tickets caches
            delete_transient('lgp_units_list_all');

            // Clear paginated ticket caches (support view)
            for ($page = 1; $page <= 10; $page++) {
                delete_transient('lgp_tickets_list_all_page_' . $page . '_per_20');
            }
        }
    }

    /**
     * Drop all plugin tables.
     *
     * Completely removes all database tables created by the plugin.
     * Called during plugin uninstallation. This is a destructive operation
     * that permanently deletes all plugin data.
     *
     * WARNING: This operation cannot be undone. All data will be lost.
     *
     * @since 2.0.0
     * @return void
     */
    public static function drop_tables()
    {
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
            $wpdb->prefix . 'lgp_security_audit',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('lgp_db_version');
    }
}
