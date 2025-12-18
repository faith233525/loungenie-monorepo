<?php
/**
 * Versioned Database Migrations
 * Tracks schema changes and runs migrations on plugin updates
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Migrations {

	const MIGRATIONS_DIR = LGP_PLUGIN_DIR . 'migrations/';
	const SCHEMA_VERSION_OPTION = 'lgp_schema_version';

	/**
	 * Initialize migrations system
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_pending_migrations' ), 10 );
	}

	/**
	 * Check and run any pending migrations
	 */
	public static function run_pending_migrations() {
		if ( ! LGP_Loader::needs_migration() ) {
			return;
		}

		self::migrate_to_version( LGP_VERSION );
	}

	/**
	 * Run migrations to reach target version
	 *
	 * @param string $target_version Target version
	 */
	public static function migrate_to_version( $target_version ) {
		global $wpdb;

		$current_version = get_option( self::SCHEMA_VERSION_OPTION, '1.0.0' );

		// Define all migrations in order
		$migrations = array(
			'1.0.0' => array( __CLASS__, 'migrate_v1_0_0' ),
			'1.1.0' => array( __CLASS__, 'migrate_v1_1_0' ),
			'1.2.0' => array( __CLASS__, 'migrate_v1_2_0' ),
		);

		foreach ( $migrations as $version => $callback ) {
			if ( version_compare( $current_version, $version, '<' ) && version_compare( $version, $target_version, '<=' ) ) {
				call_user_func( $callback );
				update_option( self::SCHEMA_VERSION_OPTION, $version );

				// Log successful migration
				error_log( "LGP Migration: {$current_version} → {$version} ✓" );
			}
		}
	}

	/**
	 * Migration v1.0.0 → v1.1.0
	 * Example: Add ticket priority field if missing
	 */
	public static function migrate_v1_1_0() {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'lgp_tickets';

		// Check if column exists
		$column_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$tickets_table} LIKE 'priority'"
		);

		if ( empty( $column_exists ) ) {
			// Add column with safe defaults
			$wpdb->query(
				"ALTER TABLE {$tickets_table} 
                ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' 
                AFTER status"
			);

			// Log this change
			LGP_Logger::log_event(
				0,
				'migration_v1_1_0',
				0,
				array( 'action' => 'Added priority column to tickets table' )
			);
		}
	}

	/**
	 * Migration v1.1.0 → v1.2.0
	 * Example: Add attachment expiry tracking
	 */
	public static function migrate_v1_2_0() {
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_attachments';

		$column_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$attachments_table} LIKE 'expires_at'"
		);

		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE {$attachments_table} 
                ADD COLUMN expires_at DATETIME NULL 
                AFTER created_at"
			);

			LGP_Logger::log_event(
				0,
				'migration_v1_2_0',
				0,
				array( 'action' => 'Added expiry tracking to attachments' )
			);
		}
	}

	/**
	 * Get current schema version
	 */
	public static function current_version() {
		return get_option( self::SCHEMA_VERSION_OPTION, '1.0.0' );
	}

	/**
	 * Check if a migration has been applied
	 *
	 * @param string $version Version to check
	 * @return bool
	 */
	public static function migration_applied( $version ) {
		$current = self::current_version();
		return version_compare( $current, $version, '>=' );
	}

	/**
	 * Rollback to a previous version (caution: destructive)
	 * Only used in development/testing
	 *
	 * @param string $version Version to rollback to
	 */
	public static function rollback( $version ) {
		if ( defined( 'WP_ENV' ) && 'development' !== WP_ENV ) {
			wp_die( 'Rollback only allowed in development environment' );
		}

		update_option( self::SCHEMA_VERSION_OPTION, $version );
		error_log( "LGP Rollback: Schema version reset to {$version}" );
	}
}
