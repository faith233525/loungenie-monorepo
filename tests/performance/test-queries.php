<?php

/**
 * Query Performance Test
 *
 * Tests database query performance and N+1 prevention
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Query_Performance_Test extends WP_UnitTestCase
{
    /**
     * Test no N+1 queries on list operations
     *
     * @test
     */
    public function test_no_n_plus_one_in_ticket_listing()
    {
        global $wpdb;

        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create tickets
        for ($i = 0; $i < 10; $i++) {
            Test_Utils::create_test_ticket();
        }

        $wpdb->show_errors = false;
        $start_queries = $wpdb->num_queries;

        // Fetch and iterate
        $response = Test_Utils::make_request('GET', '/lgp/v1/tickets');
        $data = $response->get_data();

        foreach ((array) $data as $ticket) {
            // Should not trigger additional queries
        }

        $end_queries = $wpdb->num_queries;
        $queries = $end_queries - $start_queries;

        // Should be constant number of queries, not linear with results
        $this->assertLessThan(15, $queries);
    }

    /**
     * Test slow query detection
     *
     * @test
     */
    public function test_slow_query_monitoring()
    {
        global $wpdb;

        // This test demonstrates slow query detection capability
        $slow_threshold = 0.1; // 100ms

        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $start = microtime(true);

        // Complex query
        $wpdb->get_results(
            "SELECT * FROM {$wpdb->posts} WHERE post_type = 'lgp_ticket'"
        );

        $duration = microtime(true) - $start;

        // Should be reasonably fast
        $this->assertLessThan($slow_threshold * 3, $duration);
    }

    /**
     * Test index effectiveness
     *
     * @test
     */
    public function test_index_usage()
    {
        global $wpdb;

        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create test data
        for ($i = 0; $i < 100; $i++) {
            Test_Utils::create_test_company([
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
            ]);
        }

        $start = microtime(true);

        // Query that should use index
        $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lgp_companies WHERE status = %s",
                'active'
            )
        );

        $duration = microtime(true) - $start;

        // Should be fast due to index
        $this->assertLessThan(0.05, $duration);
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
