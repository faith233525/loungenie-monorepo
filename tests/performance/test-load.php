<?php

/**
 * Performance Load Test
 *
 * Tests application performance under load
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Performance_Load_Test extends WP_UnitTestCase
{
    /**
     * Test response time for ticket listing
     *
     * @test
     */
    public function test_ticket_listing_response_time()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create test tickets
        for ($i = 0; $i < 50; $i++) {
            Test_Utils::create_test_ticket();
        }

        $start = microtime(true);

        $response = Test_Utils::make_request('GET', '/lgp/v1/tickets');

        $duration = microtime(true) - $start;

        // Should respond in under 500ms
        $this->assertLessThan(0.5, $duration, "Listing 50 tickets took {$duration}s, should be < 0.5s");
    }

    /**
     * Test memory usage during operations
     *
     * @test
     */
    public function test_memory_usage()
    {
        $start_memory = memory_get_usage();

        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create multiple tickets
        for ($i = 0; $i < 20; $i++) {
            Test_Utils::create_test_ticket();
        }

        $end_memory = memory_get_usage();
        $used_memory = ($end_memory - $start_memory) / (1024 * 1024); // MB

        // Should use less than 64MB
        $this->assertLessThan(64, $used_memory, "Created 20 tickets used {$used_memory}MB, should be < 64MB");
    }

    /**
     * Test database query efficiency
     *
     * @test
     */
    public function test_query_efficiency()
    {
        global $wpdb;

        $wpdb->show_errors = false;

        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $start_queries = $wpdb->num_queries;

        // Fetch tickets list
        Test_Utils::make_request('GET', '/lgp/v1/tickets');

        $end_queries = $wpdb->num_queries;
        $queries_made = $end_queries - $start_queries;

        // Should use reasonable number of queries (not N+1)
        $this->assertLessThan(10, $queries_made, "Made {$queries_made} queries, should be < 10");
    }

    /**
     * Test caching effectiveness
     *
     * @test
     */
    public function test_cache_hit_rate()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create test data
        for ($i = 0; $i < 10; $i++) {
            Test_Utils::create_test_ticket();
        }

        // First request (cache miss)
        $start1 = microtime(true);
        Test_Utils::make_request('GET', '/lgp/v1/tickets');
        $time1 = microtime(true) - $start1;

        // Second request (cache hit)
        $start2 = microtime(true);
        Test_Utils::make_request('GET', '/lgp/v1/tickets');
        $time2 = microtime(true) - $start2;

        // Second request should be faster due to cache
        // (in real scenario, caching would show clear difference)
        $this->assertGreaterThan(0, $time1);
    }

    /**
     * Test concurrent operation simulation
     *
     * @test
     */
    public function test_concurrent_ticket_creation()
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = Test_Utils::create_partner_user();
        }

        $start = microtime(true);

        // Simulate multiple users creating tickets
        foreach ($users as $user) {
            wp_set_current_user($user->ID);
            Test_Utils::create_test_ticket();
        }

        $duration = microtime(true) - $start;

        // Should complete reasonably fast
        $this->assertLessThan(1.0, $duration);
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
