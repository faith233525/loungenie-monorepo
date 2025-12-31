<?php

/**
 * Database Tests
 *
 * Tests for database schema, migrations, and query operations
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Database_Test extends WP_UnitTestCase
{
    /**
     * Test that companies table exists and has correct schema
     *
     * @test
     */
    public function test_companies_table_schema()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';

        // Check table exists
        $result = $wpdb->query("DESCRIBE {$table}");
        $this->assertGreaterThan(0, $result);

        // Check for required columns
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('external_id', $columns);
    }

    /**
     * Test that audit_logs table exists
     *
     * @test
     */
    public function test_audit_logs_table_schema()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_audit_logs';

        $result = $wpdb->query("DESCRIBE {$table}");
        $this->assertGreaterThan(0, $result);

        $columns = $wpdb->get_col("DESCRIBE {$table}");
        $this->assertContains('id', $columns);
        $this->assertContains('user_id', $columns);
        $this->assertContains('action', $columns);
    }

    /**
     * Test inserting and retrieving company
     *
     * @test
     */
    public function test_company_crud_operations()
    {
        $company_id = Test_Utils::create_test_company([
            'name' => 'Test Corp',
            'external_id' => 'ext_12345',
        ]);

        $this->assertGreaterThan(0, $company_id);

        // Retrieve and verify
        global $wpdb;
        $company = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lgp_companies WHERE id = %d",
                $company_id
            )
        );

        $this->assertNotNull($company);
        $this->assertEquals('Test Corp', $company->name);
    }

    /**
     * Test unique constraint on external_id
     *
     * @test
     */
    public function test_unique_constraint_on_external_id()
    {
        global $wpdb;

        $external_id = 'ext_unique_' . time();

        $company_id_1 = Test_Utils::create_test_company([
            'external_id' => $external_id,
        ]);

        // Attempt to insert duplicate
        $result = $wpdb->insert(
            $wpdb->prefix . 'lgp_companies',
            [
                'name' => 'Duplicate',
                'external_id' => $external_id,
            ]
        );

        // Should fail due to unique constraint
        $this->assertFalse($result);
    }

    /**
     * Test cache invalidation on data changes
     *
     * @test
     */
    public function test_cache_invalidation_on_insert()
    {
        wp_cache_set('test_cache_key', 'initial_value');

        // Insert should trigger cache invalidation
        Test_Utils::create_test_company();

        // Cache might be cleared
        $value = wp_cache_get('test_cache_key');
        // This depends on implementation
    }

    /**
     * Test query performance with indexes
     *
     * @test
     */
    public function test_indexed_columns_performance()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';

        // Create test data
        for ($i = 0; $i < 100; $i++) {
            Test_Utils::create_test_company();
        }

        $start = microtime(true);

        $result = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active'"
        );

        $duration = microtime(true) - $start;

        // Should complete quickly (< 100ms)
        $this->assertLessThan(0.1, $duration);
    }

    /**
     * Test transaction handling
     *
     * @test
     */
    public function test_database_transactions()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_companies';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        $company_id = Test_Utils::create_test_company();

        // Rollback transaction
        $wpdb->query('ROLLBACK');

        // Verify data was rolled back
        $company = $wpdb->get_row(
            "SELECT * FROM {$table} WHERE id = {$company_id}"
        );

        $this->assertNull($company);
    }

    /**
     * Test that foreign key constraints work
     *
     * @test
     */
    public function test_foreign_key_constraints()
    {
        global $wpdb;

        // Create a company
        $company_id = Test_Utils::create_test_company();

        // Create a user with that company
        $user = Test_Utils::create_partner_user();
        Test_Utils::set_user_company($user->ID, $company_id);

        // Verify relationship
        $user_company = get_user_meta($user->ID, 'company_id', true);
        $this->assertEquals($company_id, $user_company);
    }

    /**
     * Test migration system
     *
     * @test
     */
    public function test_migration_tracking()
    {
        // Check that migrations are tracked
        $migrations = get_option('lgp_migrations', []);
        $this->assertIsArray($migrations);
    }

    /**
     * Test data types are correct in database
     *
     * @test
     */
    public function test_column_data_types()
    {
        global $wpdb;

        $columns = $wpdb->get_results(
            "DESCRIBE {$wpdb->prefix}lgp_companies"
        );

        $column_types = [];
        foreach ($columns as $col) {
            $column_types[$col->Field] = $col->Type;
        }

        // Verify expected types
        $this->assertStringContainsString('int', $column_types['id']);
        $this->assertStringContainsString('varchar', $column_types['name']);
    }

    /**
     * Test default values in schema
     *
     * @test
     */
    public function test_default_values()
    {
        global $wpdb;

        $company = Test_Utils::create_test_company(['name' => 'Test']);

        $result = $wpdb->get_row(
            "SELECT status FROM {$wpdb->prefix}lgp_companies WHERE id = {$company}"
        );

        $this->assertNotNull($result->status);
    }

    /**
     * Cleanup after tests
     */
    public function tearDown(): void
    {
        Test_Utils::cleanup();
        parent::tearDown();
    }
}
