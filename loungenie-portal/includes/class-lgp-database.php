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
     * Create all database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Companies table
        $companies_table = $wpdb->prefix . 'lgp_companies';
        $sql_companies = "CREATE TABLE $companies_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            state varchar(50),
            venue_type varchar(50),
            contact_name varchar(255),
            contact_email varchar(255),
            contact_phone varchar(50),
            management_company_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY management_company_id (management_company_id),
            KEY venue_type (venue_type)
        ) $charset_collate;";
        
        // Management Companies table
        $mgmt_companies_table = $wpdb->prefix . 'lgp_management_companies';
        $sql_mgmt_companies = "CREATE TABLE $mgmt_companies_table (
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
        $sql_units = "CREATE TABLE $units_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id bigint(20) UNSIGNED NOT NULL,
            management_company_id bigint(20) UNSIGNED,
            address text,
            lock_type varchar(100),
            lock_brand varchar(50),
            color_tag varchar(50),
            season varchar(20) DEFAULT 'year-round',
            venue_type varchar(50),
            status varchar(50) DEFAULT 'active',
            install_date date,
            service_history text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
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
        $sql_service_requests = "CREATE TABLE $service_requests_table (
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
        $sql_tickets = "CREATE TABLE $tickets_table (
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
        $sql_gateways = "CREATE TABLE $gateways_table (
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
        
        // Execute table creation
        dbDelta( $sql_companies );
        dbDelta( $sql_mgmt_companies );
        dbDelta( $sql_units );
        dbDelta( $sql_service_requests );
        dbDelta( $sql_tickets );
        dbDelta( $sql_gateways );
        
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
            $wpdb->prefix . 'lgp_gateways'
        );
        
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS $table" );
        }
        
        delete_option( 'lgp_db_version' );
    }
}
