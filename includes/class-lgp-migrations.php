<?php

/**
 * Versioned Database Migrations.
 * Tracks schema changes and runs migrations on plugin updates.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database migration handler for schema versioning.
 */
class LGP_Migrations {



	const MIGRATIONS_DIR        = LGP_PLUGIN_DIR . 'migrations/';
	const SCHEMA_VERSION_OPTION = 'lgp_schema_version';

	/**
	 * Initialize migrations system.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_pending_migrations' ), 10 );
	}

	/**
	 * Check and run any pending migrations.
	 *
	 * @return void
	 */
	public static function run_pending_migrations() {
		if ( ! LGP_Loader::needs_migration() ) {
			return;
		}

		self::migrate_to_version( LGP_VERSION );
	}

	/**
	 * Run migrations to reach target version.
	 *
	 * @param string $target_version Target version.
	 * @return void
	 */
	public static function migrate_to_version( $target_version ) {
		global $wpdb;

		$current_version = get_option( self::SCHEMA_VERSION_OPTION, '1.0.0' );

		// Define all migrations in order.
		$migrations = array(
			'1.0.0' => array( __CLASS__, 'migrate_v1_0_0' ),
			'1.1.0' => array( __CLASS__, 'migrate_v1_1_0' ),
			'1.2.0' => array( __CLASS__, 'migrate_v1_2_0' ),
			'1.3.0' => array( __CLASS__, 'migrate_v1_3_0' ),
			'1.4.0' => array( __CLASS__, 'migrate_v1_4_0' ),
			'1.5.0' => array( __CLASS__, 'migrate_v1_5_0' ),
			'1.6.0' => array( __CLASS__, 'migrate_v1_6_0' ),
			'1.7.0' => array( __CLASS__, 'migrate_v1_7_0' ),
			'1.8.0' => array( __CLASS__, 'migrate_v1_8_0' ),
			'1.8.1' => array( __CLASS__, 'migrate_v1_8_1' ),
		);

		foreach ( $migrations as $version => $callback ) {
			if ( version_compare( $current_version, $version, '<' ) && version_compare( $version, $target_version, '<=' ) ) {
				call_user_func( $callback );
				update_option( self::SCHEMA_VERSION_OPTION, $version );

				// Log successful migration.
				error_log( "LGP Migration: {$current_version} → {$version} ✓" );
			}
		}
	}

	/**
	 * Migration v1.0.0 → v1.1.0.
	 * Example: Add ticket priority field if missing.
	 *
	 * @return void
	 */
	public static function migrate_v1_1_0() {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'lgp_tickets';

		// Check if column exists.
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.InterpolatedNotPrepared
		$column_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$tickets_table} LIKE 'priority'"
		);

		if ( empty( $column_exists ) ) {
			// Add column with safe defaults.
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.InterpolatedNotPrepared
			$wpdb->query(
				"ALTER TABLE {$tickets_table} 
                ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' 
                AFTER status"
			);

			// Log this change.
			LGP_Logger::log_event(
				0,
				'migration_v1_1_0',
				0,
				array( 'action' => 'Added priority column to tickets table' )
			);
		}
	}

	/**
	 * Migration v1.1.0 → v1.2.0.
	 * Example: Add attachment expiry tracking.
	 *
	 * @return void
	 */
	public static function migrate_v1_2_0() {
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'lgp_ticket_attachments';

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
	 * Migration v1.2.0 → v1.3.0.
	 * Add content_url/type/tags to help_guides and backfill from video_url.
	 *
	 * @return void
	 */
	public static function migrate_v1_3_0() {
		global $wpdb;

		$table = $wpdb->prefix . 'lgp_help_guides';

		// content_url.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'content_url'" );
		if ( empty( $col ) ) {
			// Note: ALTER TABLE on known table names (prefixed) is safe from injection.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN content_url VARCHAR(500) NOT NULL AFTER description" );
			// Backfill from legacy video_url - safe as it's copying between columns.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "UPDATE {$table} SET content_url = video_url WHERE (content_url IS NULL OR content_url = '') AND video_url IS NOT NULL" );
		}

		// type.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'type'" );
		if ( empty( $col ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN type VARCHAR(50) DEFAULT 'video' AFTER content_url" );
		}

		// tags.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'tags'" );
		if ( empty( $col ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN tags LONGTEXT AFTER category" );
		}

		LGP_Logger::log_event( 0, 'migration_v1_3_0', 0, array( 'action' => 'Added content_url/type/tags to help_guides' ) );
	}

	/**
	 * Migration v1.3.0 → v1.4.0.
	 * Add company and unit fields required by spec.
	 *
	 * @return void
	 */
	public static function migrate_v1_4_0() {
		global $wpdb;

		$companies = $wpdb->prefix . 'lgp_companies';
		$units     = $wpdb->prefix . 'lgp_units';

		// Companies table additions.
		$addCols = array(
			'company_name VARCHAR(255)',
			'management_company VARCHAR(255)',
			'primary_contract VARCHAR(50)',
			'primary_contract_status VARCHAR(20)',
			'secondary_contract VARCHAR(50)',
			'secondary_contract_status VARCHAR(20)',
			'contract_notes TEXT',
			'season VARCHAR(20)',
			'street_address VARCHAR(255)',
			'city VARCHAR(100)',
			'zip VARCHAR(20)',
			'country VARCHAR(100)',
			'top_colour VARCHAR(50)',
		);
		foreach ( $addCols as $def ) {
			list($name) = explode( ' ', $def, 2 );
			$exists     = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$companies} LIKE %s", $name ) );
			if ( empty( $exists ) ) {
				$wpdb->query( "ALTER TABLE {$companies} ADD COLUMN {$def} AFTER management_company_id" );
			}
		}

		// Units table additions.
		$unitCols = array(
			'unit_number VARCHAR(100)',
			'lock_part VARCHAR(100)',
			'key VARCHAR(100)',
			'installation_date DATE',
			'master_code VARBINARY(255)',
			'sub_master_code VARBINARY(255)',
			'latitude DECIMAL(10,6)',
			'longitude DECIMAL(10,6)',
		);
		foreach ( $unitCols as $def ) {
			list($name) = explode( ' ', $def, 2 );
			$exists     = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$units} LIKE %s", $name ) );
			if ( empty( $exists ) ) {
				$wpdb->query( "ALTER TABLE {$units} ADD COLUMN {$def} AFTER management_company_id" );
			}
		}

		// Unique index for unit_number.
		$idx = $wpdb->get_results( "SHOW INDEX FROM {$units} WHERE Key_name = 'unit_number'" );
		if ( empty( $idx ) ) {
			$wpdb->query( "ALTER TABLE {$units} ADD UNIQUE KEY unit_number (unit_number)" );
		}

		LGP_Logger::log_event( 0, 'migration_v1_4_0', 0, array( 'action' => 'Added spec fields to companies and units' ) );
	}

	/**
	 * Migration v1.4.0 → v1.5.0.
	 * Create user progress table for Knowledge Center.
	 *
	 * @return void
	 */
	public static function migrate_v1_5_0() {
		global $wpdb;
		require_once LGP_PLUGIN_DIR . 'includes/lgp-upgrade-shim.php';
		$charset_collate = $wpdb->get_charset_collate();

		$user_progress = $wpdb->prefix . 'lgp_user_progress';
		$sql           = "CREATE TABLE {$user_progress} (
            user_id bigint(20) UNSIGNED NOT NULL,
            guide_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, guide_id),
            KEY status (status),
            KEY guide_id (guide_id)
        ) {$charset_collate};";

		dbDelta( $sql );

		LGP_Logger::log_event( 0, 'migration_v1_5_0', 0, array( 'action' => 'Created user progress table' ) );
	}

	/**
	 * Migration v1.5.0 → v1.6.0.
	 * Add geolocation columns to units table for map view feature.
	 *
	 * @return void
	 */
	public static function migrate_v1_6_0() {
		global $wpdb;

		$units_table = $wpdb->prefix . 'lgp_units';

		// Check if latitude column exists.
		$latitude_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$units_table} LIKE 'latitude'"
		);

		if ( empty( $latitude_exists ) ) {
			// Add geolocation columns (place after an existing column to avoid SQL errors).
			$wpdb->query(
				"ALTER TABLE {$units_table}
				ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER status,
				ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude"
			);

			// Add index for geo queries.
			$wpdb->query(
				"ALTER TABLE {$units_table}
				ADD INDEX idx_geo (latitude, longitude)"
			);

			LGP_Logger::log_event(
				0,
				'migration_v1_6_0',
			);
		}
	}

	/**
	 * Migration v1.6.0 → v1.7.0.
	 * Add urgency column to tickets and contract_status to companies.
	 *
	 * @return void
	 */
	public static function migrate_v1_7_0() {
		global $wpdb;

		// Add urgency to tickets table.
		$tickets_table  = $wpdb->prefix . 'lgp_tickets';
		$urgency_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$tickets_table} LIKE 'urgency'"
		);

		if ( empty( $urgency_exists ) ) {
			$wpdb->query(
				"ALTER TABLE {$tickets_table}
				ADD COLUMN urgency VARCHAR(20) DEFAULT 'medium' AFTER status"
			);

			// Add index for urgency filtering.
			$wpdb->query(
				"ALTER TABLE {$tickets_table}
				ADD INDEX idx_urgency (urgency)"
			);
		}

		// Add contract_status to companies table.
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$contract_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$companies_table} LIKE 'contract_status'"
		);

		if ( empty( $contract_exists ) ) {
			$wpdb->query(
				"ALTER TABLE {$companies_table}
				ADD COLUMN contract_status VARCHAR(50) DEFAULT 'active' AFTER primary_contract_status"
			);
		}

		LGP_Logger::log_event(
			0,
			'migration_v1_7_0',
			0,
			array( 'action' => 'Added urgency column to tickets and contract_status to companies' )
		);
	}

	/**
	 * Migration v1.7.0 → v1.8.0.
	 * Add top_colors JSON column to companies for unit color aggregation (Phase 2B).
	 *
	 * @return void
	 */
	public static function migrate_v1_8_0() {
		global $wpdb;

		$companies_table = $wpdb->prefix . 'lgp_companies';

		// Check if top_colors column exists.
		$column_exists = $wpdb->get_results(
			"SHOW COLUMNS FROM {$companies_table} LIKE 'top_colors'"
		);

		if ( empty( $column_exists ) ) {
			// Add JSON column for color aggregates.
			$wpdb->query(
				"ALTER TABLE {$companies_table}
				ADD COLUMN top_colors JSON DEFAULT NULL
				AFTER contract_status"
			);

			// Populate initial color aggregates from existing units.
			self::populate_initial_color_aggregates();

			LGP_Logger::log_event(
				0,
				'migration_v1_8_0',
				0,
				array(
					'action' => 'Added top_colors JSON column to companies table',
					'note'   => 'Unit color aggregation - Phase 2B',
				)
			);
		}
	}

	/**
	 * Migration v1.8.0 → v1.8.1.
	 * Optional performance indexes for email threading lookups on meta tables.
	 *
	 * @return void
	 */
	public static function migrate_v1_8_1() {
		global $wpdb;

		$postmeta    = $wpdb->postmeta;
		$commentmeta = $wpdb->commentmeta;

		// Helper to test if index exists.
		$has_index = function ( $table, $index_name ) use ( $wpdb ) {
			$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM ' . $table . ' WHERE Key_name = %s', $index_name ) );
			return ! empty( $indexes );
		};

		// Add composite index on (meta_key, meta_value) with prefix length to support lookups by specific keys.
		if ( ! $has_index( $postmeta, 'idx_email_message_id' ) ) {
			// wp_postmeta.meta_value is LONGTEXT; use prefix to keep index size reasonable.
			$wpdb->query( 'ALTER TABLE ' . $postmeta . ' ADD INDEX idx_email_message_id (meta_key(191), meta_value(191))' );
		}
		if ( ! $has_index( $postmeta, 'idx_email_conversation_id' ) ) {
			$wpdb->query( 'ALTER TABLE ' . $postmeta . ' ADD INDEX idx_email_conversation_id (meta_key(191), meta_value(191))' );
		}

		if ( ! $has_index( $commentmeta, 'idx_comment_conversation_id' ) ) {
			$wpdb->query( 'ALTER TABLE ' . $commentmeta . ' ADD INDEX idx_comment_conversation_id (meta_key(191), meta_value(191))' );
		}

		LGP_Logger::log_event( 0, 'migration_v1_8_1', 0, array( 'action' => 'Added optional meta indexes for email threading keys' ) );
	}

	/**
	 * Populate initial color aggregates for all existing companies.
	 * Called by migrate_v1_8_0.
	 *
	 * @return void
	 */
	private static function populate_initial_color_aggregates() {
		global $wpdb;

		$companies_table = $wpdb->prefix . 'lgp_companies';
		$units_table     = $wpdb->prefix . 'lgp_units';

		// Get all companies
		$companies = $wpdb->get_results( "SELECT id FROM {$companies_table}" );

		foreach ( $companies as $company ) {
			// Aggregate color counts for this company.
			// @phpstan-ignore-next-line OBJECT_K is WordPress core constant
			$colors = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COALESCE(color_tag, 'unknown') as color, COUNT(*) as count
					 FROM {$units_table}
					 WHERE company_id = %d
					 GROUP BY color_tag
					 ORDER BY count DESC",
					$company->id
				),
				OBJECT_K
			);

			// Convert color results to associative array
			$color_counts = array();
			if ( ! empty( $colors ) ) {
				foreach ( $colors as $color => $row ) {
					$color_counts[ $color ] = (int) $row->count;
				}
			}

			// Update company with color aggregates
			if ( ! empty( $color_counts ) ) {
				$wpdb->update(
					$companies_table,
					array( 'top_colors' => wp_json_encode( $color_counts ) ),
					array( 'id' => $company->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**  * Get current schema version
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
			// @phpstan-ignore-next-line wp_die is WordPress core function
			wp_die( 'Rollback only allowed in development environment' );
		}

		update_option( self::SCHEMA_VERSION_OPTION, $version );
		error_log( "LGP Rollback: Schema version reset to {$version}" );
	}
}
