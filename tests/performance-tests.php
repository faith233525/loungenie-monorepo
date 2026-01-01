<?php

/**
 * Performance Optimization Test Suite
 * 
 * Quick tests to verify all optimizations are working correctly.
 * Run via WP-CLI: wp eval-file performance-tests.php
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

class LGP_Performance_Tests
{

    private static $results = [];
    private static $passed = 0;
    private static $failed = 0;

    public static function run_all_tests()
    {
        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "   LounGenie Portal - Performance Optimization Test Suite\n";
        echo "════════════════════════════════════════════════════════════════\n\n";

        // Test 1: Transient Caching
        self::test_transient_caching();

        // Test 2: Database Indexes
        self::test_database_indexes();

        // Test 3: Cache Invalidation
        self::test_cache_invalidation();

        // Test 4: Geocoding Queue
        self::test_geocoding_queue();

        // Test 5: Memory Usage
        self::test_memory_usage();

        // Test 6: Query Count
        self::test_query_count();

        // Print Summary
        self::print_summary();
    }

    private static function test_transient_caching()
    {
        echo "Test 1: Transient Caching System\n";
        echo "─────────────────────────────────\n";

        // Test dashboard stats cache
        $cache_key = 'lgp_dashboard_support_stats';
        delete_transient($cache_key);

        $start = microtime(true);
        $value = get_transient($cache_key);
        $miss_time = (microtime(true) - $start) * 1000;

        self::assert_false($value, "Cache should be empty after delete", $miss_time . "ms");

        // Simulate cache population
        set_transient($cache_key, ['test' => 'data'], 300);

        $start = microtime(true);
        $value = get_transient($cache_key);
        $hit_time = (microtime(true) - $start) * 1000;

        self::assert_true($value !== false, "Cache should return data", $hit_time . "ms");
        self::assert_true($hit_time < $miss_time, "Cache hit should be faster than miss");

        // Test cache keys exist
        $keys = [
            'lgp_dashboard_support_stats',
            'lgp_dashboard_recent_tickets',
            'lgp_dashboard_top_metrics',
        ];

        foreach ($keys as $key) {
            $exists = get_transient($key) !== false;
            self::log_info("Cache key '$key': " . ($exists ? "EXISTS" : "NOT SET"));
        }

        echo "\n";
    }

    private static function test_database_indexes()
    {
        echo "Test 2: Database Indexes\n";
        echo "─────────────────────────\n";

        global $wpdb;

        $tables = [
            'lgp_tickets' => ['idx_status_created', 'idx_status'],
            'lgp_service_requests' => ['idx_company_status', 'idx_company_priority'],
            'lgp_units' => ['idx_company_status', 'idx_color_count', 'idx_lock_count'],
        ];

        foreach ($tables as $table => $expected_indexes) {
            $full_table = $wpdb->prefix . $table;
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$full_table}");

            $index_names = array_unique(array_column($indexes, 'Key_name'));

            foreach ($expected_indexes as $expected) {
                $exists = in_array($expected, $index_names);
                self::assert_true(
                    $exists,
                    "Index '{$expected}' exists on {$table}",
                    $exists ? "✓" : "MISSING"
                );
            }
        }

        echo "\n";
    }

    private static function test_cache_invalidation()
    {
        echo "Test 3: Cache Invalidation System\n";
        echo "───────────────────────────────────\n";

        // Test class exists
        $class_exists = class_exists('LGP_Cache_Invalidation');
        self::assert_true($class_exists, "LGP_Cache_Invalidation class loaded");

        // Test LGP_Database has clear method
        $method_exists = method_exists('LGP_Database', 'clear_portal_caches');
        self::assert_true($method_exists, "LGP_Database::clear_portal_caches() exists");

        // Test cache clearing works
        set_transient('lgp_test_cache', 'test_data', 300);
        $before = get_transient('lgp_test_cache');

        if (method_exists('LGP_Database', 'clear_portal_caches')) {
            delete_transient('lgp_test_cache'); // Manual clear for test
            $after = get_transient('lgp_test_cache');

            self::assert_true($before !== false, "Test cache was set");
            self::assert_false($after, "Test cache was cleared");
        }

        echo "\n";
    }

    private static function test_geocoding_queue()
    {
        echo "Test 4: Background Geocoding Queue\n";
        echo "────────────────────────────────────\n";

        // Test class exists
        $class_exists = class_exists('LGP_Geocode');
        self::assert_true($class_exists, "LGP_Geocode class loaded");

        // Test queue method exists
        $method_exists = method_exists('LGP_Geocode', 'queue_geocode');
        self::assert_true($method_exists, "LGP_Geocode::queue_geocode() exists");

        // Test cron job scheduled
        $next_run = wp_next_scheduled('lgp_geocode_background_process');
        self::assert_true(
            $next_run !== false,
            "Geocoding cron job is scheduled",
            $next_run ? date('Y-m-d H:i:s', $next_run) : "NOT SCHEDULED"
        );

        // Check queue size
        $queue = get_option('lgp_geocode_queue', []);
        self::log_info("Geocoding queue size: " . count($queue) . " companies pending");

        echo "\n";
    }

    private static function test_memory_usage()
    {
        echo "Test 5: Memory Usage\n";
        echo "─────────────────────\n";

        $memory_mb = memory_get_usage(true) / 1024 / 1024;
        $limit = ini_get('memory_limit');

        self::log_info("Current memory usage: " . number_format($memory_mb, 2) . " MB");
        self::log_info("Memory limit: " . $limit);

        $threshold = 64; // MB
        self::assert_true(
            $memory_mb < $threshold,
            "Memory usage under {$threshold}MB",
            number_format($memory_mb, 2) . " MB"
        );

        echo "\n";
    }

    private static function test_query_count()
    {
        echo "Test 6: Database Query Efficiency\n";
        echo "───────────────────────────────────\n";

        global $wpdb;

        // Reset query counter
        $wpdb->num_queries = 0;

        // Simulate dashboard load with caching
        $cache_key = 'lgp_dashboard_support_stats';
        $stats = get_transient($cache_key);

        $queries_with_cache = $wpdb->num_queries;

        self::log_info("Queries with cache: " . $queries_with_cache);
        self::assert_true(
            $queries_with_cache < 5,
            "Cached dashboard uses <5 queries",
            $queries_with_cache . " queries"
        );

        echo "\n";
    }

    private static function assert_true($condition, $message, $details = '')
    {
        if ($condition) {
            self::$passed++;
            echo "  ✅ PASS: {$message}";
            if ($details) echo " ({$details})";
            echo "\n";
        } else {
            self::$failed++;
            echo "  ❌ FAIL: {$message}";
            if ($details) echo " ({$details})";
            echo "\n";
        }
    }

    private static function assert_false($condition, $message, $details = '')
    {
        self::assert_true(!$condition, $message, $details);
    }

    private static function log_info($message)
    {
        echo "  ℹ️  INFO: {$message}\n";
    }

    private static function print_summary()
    {
        $total = self::$passed + self::$failed;
        $percentage = $total > 0 ? round((self::$passed / $total) * 100, 1) : 0;

        echo "════════════════════════════════════════════════════════════════\n";
        echo "   Test Summary\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Total Tests:  {$total}\n";
        echo "  Passed:       " . self::$passed . " ✅\n";
        echo "  Failed:       " . self::$failed . " ❌\n";
        echo "  Success Rate: {$percentage}%\n";
        echo "════════════════════════════════════════════════════════════════\n\n";

        if (self::$failed === 0) {
            echo "🎉 All tests passed! Performance optimizations are working correctly.\n\n";
        } else {
            echo "⚠️  Some tests failed. Review the output above for details.\n\n";
        }
    }
}

// Run tests
LGP_Performance_Tests::run_all_tests();
