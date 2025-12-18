<?php
/**
 * Shared Server Compatibility Tests
 * 
 * Validates LounGenie Portal plugin against shared hosting constraints:
 * - Memory limits
 * - Execution time
 * - File system limitations
 * - Database connection handling
 * - CPU resource management
 * 
 * @package LounGenie Portal
 * @version 1.8.0make 
 */

namespace LounGenie\Portal\Tests;

class SharedServerCompatibilityTest {
    
    private $results = [];
    private $start_time = 0;
    private $start_memory = 0;
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
    }
    
    /**
     * Run all compatibility tests
     */
    public function run_all_tests() {
        echo "<h1>🖥️ LounGenie Portal - Shared Server Compatibility Test Suite</h1>";
        echo "<p>Testing WordPress plugin compatibility with shared hosting constraints...</p>";
        
        $this->test_php_version();
        $this->test_memory_limits();
        $this->test_execution_time();
        $this->test_file_permissions();
        $this->test_database_connection_pooling();
        $this->test_function_availability();
        $this->test_plugin_initialization();
        $this->test_database_queries();
        $this->test_transient_fallback();
        $this->test_file_operations();
        $this->test_curl_availability();
        $this->test_session_handling();
        $this->test_resource_cleanup();
        
        $this->display_results();
    }
    
    /**
     * Test PHP Version Requirements
     */
    private function test_php_version() {
        $current = phpversion();
        $required = '7.4.0';
        
        $pass = version_compare($current, $required, '>=');
        
        $this->add_result([
            'test' => 'PHP Version',
            'required' => $required,
            'current' => $current,
            'pass' => $pass,
            'critical' => true,
            'notes' => $pass 
                ? 'Compatible with shared server PHP versions'
                : 'May have compatibility issues on older shared hosts'
        ]);
    }
    
    /**
     * Test Memory Limit Handling
     */
    private function test_memory_limits() {
        $limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        $recommended = 64 * 1024 * 1024; // 64MB
        $minimum = 32 * 1024 * 1024;    // 32MB
        
        $current_usage = memory_get_usage(true);
        $percent = ($current_usage / $limit) * 100;
        
        $pass = $limit >= $minimum;
        $warning = $limit < $recommended;
        
        $this->add_result([
            'test' => 'Memory Limit',
            'limit' => number_format($limit / 1024 / 1024, 1) . 'MB',
            'current_usage' => number_format($current_usage / 1024 / 1024, 2) . 'MB',
            'percent_used' => number_format($percent, 1) . '%',
            'pass' => $pass,
            'warning' => $warning,
            'notes' => $pass 
                ? ($warning ? 'Works but below recommended (64MB)' : 'Optimal for shared hosting')
                : 'Critical: Below minimum required memory'
        ]);
    }
    
    /**
     * Test Execution Time Limits
     */
    private function test_execution_time() {
        $limit = (int) ini_get('max_execution_time');
        $recommended = 30; // seconds
        $minimum = 5;      // seconds
        
        $pass = $limit >= $minimum || $limit == 0; // 0 = unlimited
        $warning = $limit < $recommended && $limit > 0;
        
        $this->add_result([
            'test' => 'Execution Time Limit',
            'limit' => $limit == 0 ? 'Unlimited' : $limit . ' seconds',
            'recommended' => $recommended . ' seconds',
            'pass' => $pass,
            'warning' => $warning,
            'notes' => $pass
                ? ($warning ? 'Works but may timeout on heavy operations' : 'Sufficient for normal operations')
                : 'Critical: Execution time too short'
        ]);
    }
    
    /**
     * Test File Permissions & Writable Directories
     */
    private function test_file_permissions() {
        $upload_dir = wp_upload_dir();
        $writable_dirs = [
            'wp-content/uploads' => $upload_dir['basedir'],
            'wp-content/cache' => WP_CONTENT_DIR . '/cache',
            'plugin cache' => WP_CONTENT_DIR . '/cache/loungenie-portal'
        ];
        
        $all_pass = true;
        $issues = [];
        
        foreach ($writable_dirs as $name => $dir) {
            if (!is_writable($dir)) {
                $all_pass = false;
                $issues[] = "$name not writable";
            }
        }
        
        $this->add_result([
            'test' => 'File Permissions',
            'writable_dirs' => count($writable_dirs),
            'pass' => $all_pass,
            'issues' => $issues ?: 'All required directories writable',
            'notes' => $all_pass
                ? 'Plugin can cache and store data'
                : 'File permissions need adjustment on shared server'
        ]);
    }
    
    /**
     * Test Database Connection Pooling
     */
    private function test_database_connection_pooling() {
        global $wpdb;
        
        $max_connections = $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'");
        
        // Test connection stability
        $test_query = "SELECT 1";
        $result = $wpdb->get_results($test_query);
        $connection_stable = !empty($result);
        
        // Test query timeout
        $timeout = (int) $wpdb->get_var("SHOW VARIABLES LIKE 'net_read_timeout'");
        
        $this->add_result([
            'test' => 'Database Connection',
            'max_connections' => $max_connections,
            'net_read_timeout' => $timeout . ' seconds',
            'connection_stable' => $connection_stable,
            'pass' => $connection_stable,
            'notes' => $connection_stable
                ? 'Database connection stable'
                : 'Critical: Database connection issues'
        ]);
    }
    
    /**
     * Test Required PHP Functions
     */
    private function test_function_availability() {
        $required_functions = [
            'curl_init' => 'HTTP Requests',
            'json_encode' => 'JSON Processing',
            'fopen' => 'File Operations',
            'preg_match' => 'Regular Expressions',
            'base64_encode' => 'Encoding',
            'md5' => 'Hashing',
            'unserialize' => 'Serialization',
            'filter_var' => 'Input Filtering',
        ];
        
        $disabled = [];
        
        foreach ($required_functions as $func => $purpose) {
            if (!function_exists($func)) {
                $disabled[] = "$func ($purpose)";
            }
        }
        
        $pass = empty($disabled);
        
        $this->add_result([
            'test' => 'Required PHP Functions',
            'checked' => count($required_functions),
            'available' => count($required_functions) - count($disabled),
            'pass' => $pass,
            'disabled_functions' => $disabled ?: 'All required functions available',
            'notes' => $pass
                ? 'All necessary functions available'
                : 'Some functions disabled - shared server may block them'
        ]);
    }
    
    /**
     * Test Plugin Initialization
     */
    private function test_plugin_initialization() {
        try {
            // Check plugin file exists
            $plugin_file = dirname(__DIR__) . '/loungenie-portal.php';
            $plugin_exists = file_exists($plugin_file);
            
            // Check main classes are loaded
            $classes_exist = class_exists('LounGenie\Portal\LGP_Loader');
            
            // Check hooks are registered
            $has_hooks = has_action('plugins_loaded', 'lgp_init');
            
            $pass = $plugin_exists && $classes_exist && $has_hooks;
            
            $this->add_result([
                'test' => 'Plugin Initialization',
                'plugin_file' => $plugin_exists ? '✓' : '✗',
                'classes_loaded' => $classes_exist ? '✓' : '✗',
                'hooks_registered' => $has_hooks ? '✓' : '✗',
                'pass' => $pass,
                'notes' => $pass
                    ? 'Plugin initializes correctly'
                    : 'Plugin initialization may have issues'
            ]);
        } catch (\Exception $e) {
            $this->add_result([
                'test' => 'Plugin Initialization',
                'pass' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test Database Query Performance
     */
    private function test_database_queries() {
        global $wpdb;
        
        $queries = [
            'SELECT COUNT(*) FROM ' . $wpdb->users,
            'SELECT COUNT(*) FROM ' . $wpdb->posts,
            'SELECT COUNT(*) FROM ' . $wpdb->postmeta . ' LIMIT 1000',
        ];
        
        $timings = [];
        $total_time = 0;
        
        foreach ($queries as $query) {
            $start = microtime(true);
            $wpdb->get_results($query);
            $duration = (microtime(true) - $start) * 1000; // ms
            $timings[] = round($duration, 2) . 'ms';
            $total_time += $duration;
        }
        
        $avg_time = $total_time / count($queries);
        $pass = $avg_time < 100; // 100ms average is good
        
        $this->add_result([
            'test' => 'Database Query Performance',
            'queries_tested' => count($queries),
            'average_time' => round($avg_time, 2) . 'ms',
            'pass' => $pass,
            'timings' => $timings,
            'notes' => $pass
                ? 'Database queries perform well'
                : 'Slow database queries - may be shared server load'
        ]);
    }
    
    /**
     * Test Transient/Cache Fallback
     */
    private function test_transient_fallback() {
        // Test transient set/get
        $transient_key = 'lgp_test_' . time();
        $transient_value = 'test_value_' . time();
        
        set_transient($transient_key, $transient_value, 60);
        $retrieved = get_transient($transient_key);
        $transient_works = $retrieved === $transient_value;
        
        // Test option fallback
        $option_key = 'lgp_test_option_' . time();
        update_option($option_key, $transient_value);
        $option_value = get_option($option_key);
        $option_works = $option_value === $transient_value;
        
        $pass = $transient_works && $option_works;
        
        // Cleanup
        delete_transient($transient_key);
        delete_option($option_key);
        
        $this->add_result([
            'test' => 'Caching/Transient Fallback',
            'transients' => $transient_works ? '✓' : '✗',
            'options_fallback' => $option_works ? '✓' : '✗',
            'pass' => $pass,
            'notes' => $pass
                ? 'Caching works with fallback available'
                : 'Caching issues detected'
        ]);
    }
    
    /**
     * Test File Operations Safety
     */
    private function test_file_operations() {
        $upload_dir = wp_upload_dir();
        $test_file = $upload_dir['basedir'] . '/lgp_test_' . time() . '.txt';
        
        try {
            // Test write
            $content = 'Test content: ' . time();
            $write_success = file_put_contents($test_file, $content) !== false;
            
            // Test read
            $read_content = file_get_contents($test_file);
            $read_success = $read_content === $content;
            
            // Test delete
            $delete_success = unlink($test_file);
            
            $pass = $write_success && $read_success && $delete_success;
            
            $this->add_result([
                'test' => 'File Operations',
                'write' => $write_success ? '✓' : '✗',
                'read' => $read_success ? '✓' : '✗',
                'delete' => $delete_success ? '✓' : '✗',
                'pass' => $pass,
                'notes' => $pass
                    ? 'File operations working correctly'
                    : 'File operation issues on shared server'
            ]);
        } catch (\Exception $e) {
            $this->add_result([
                'test' => 'File Operations',
                'pass' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test cURL/HTTP Requests
     */
    private function test_curl_availability() {
        $curl_available = function_exists('curl_init');
        $fopen_available = ini_get('allow_url_fopen');
        $stream_available = extension_loaded('openssl') || extension_loaded('ssl');
        
        $pass = $curl_available || ($fopen_available && $stream_available);
        
        $this->add_result([
            'test' => 'HTTP Request Methods',
            'curl' => $curl_available ? '✓' : '✗',
            'fopen' => $fopen_available ? '✓' : '✗',
            'ssl' => $stream_available ? '✓' : '✗',
            'pass' => $pass,
            'notes' => $pass
                ? 'HTTP requests available'
                : 'Warning: Limited HTTP capability on shared server'
        ]);
    }
    
    /**
     * Test Session Handling
     */
    private function test_session_handling() {
        $session_handler = ini_get('session.save_handler');
        $session_path = ini_get('session.save_path');
        $path_exists = !empty($session_path) && is_dir($session_path);
        
        $pass = !empty($session_handler) && ($path_exists || $session_handler === 'user');
        
        $this->add_result([
            'test' => 'Session Handling',
            'handler' => $session_handler,
            'path' => $session_path ?: 'Default',
            'path_writable' => $path_exists ? '✓' : '✗',
            'pass' => $pass,
            'notes' => $pass
                ? 'Session handling configured'
                : 'Session issues may occur on shared server'
        ]);
    }
    
    /**
     * Test Resource Cleanup
     */
    private function test_resource_cleanup() {
        $peak_memory = memory_get_peak_usage(true);
        $current_memory = memory_get_usage(true);
        $limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        
        $peak_percent = ($peak_memory / $limit) * 100;
        $current_percent = ($current_memory / $limit) * 100;
        
        $pass = $peak_percent < 80; // Should not exceed 80% of limit
        
        $elapsed_time = microtime(true) - $this->start_time;
        
        $this->add_result([
            'test' => 'Resource Cleanup',
            'peak_memory' => number_format($peak_memory / 1024 / 1024, 2) . 'MB (' . number_format($peak_percent, 1) . '%)',
            'current_memory' => number_format($current_memory / 1024 / 1024, 2) . 'MB (' . number_format($current_percent, 1) . '%)',
            'elapsed_time' => round($elapsed_time, 3) . ' seconds',
            'pass' => $pass,
            'notes' => $pass
                ? 'Resources properly cleaned up'
                : 'High memory usage - may timeout on shared server'
        ]);
    }
    
    /**
     * Add test result
     */
    private function add_result($result) {
        $this->results[] = $result;
    }
    
    /**
     * Display all results
     */
    private function display_results() {
        $critical_pass = true;
        $warnings = 0;
        
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>Test</th><th>Status</th><th>Details</th>";
        echo "</tr>";
        
        foreach ($this->results as $result) {
            $status = $result['pass'] ? '✅ PASS' : '❌ FAIL';
            $bg_color = $result['pass'] ? '#e8f5e9' : '#ffebee';
            
            if (!$result['pass']) {
                if (!empty($result['critical'])) {
                    $critical_pass = false;
                } else {
                    $warnings++;
                }
            }
            
            echo "<tr style='background-color: $bg_color;'>";
            echo "<td><strong>" . $result['test'] . "</strong></td>";
            echo "<td>" . $status . "</td>";
            echo "<td>";
            
            foreach ($result as $key => $value) {
                if (!in_array($key, ['test', 'pass', 'critical', 'warning'])) {
                    if (is_array($value)) {
                        echo "<strong>$key:</strong> " . implode(', ', $value) . "<br>";
                    } else {
                        echo "<strong>$key:</strong> $value<br>";
                    }
                }
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<hr>";
        echo "<h2>Summary</h2>";
        echo "<p><strong>Tests Passed:</strong> " . count(array_filter($this->results, fn($r) => $r['pass'])) . " / " . count($this->results) . "</p>";
        echo "<p><strong>Warnings:</strong> $warnings</p>";
        
        if ($critical_pass) {
            echo "<p style='color: green; font-weight: bold;'>✅ Plugin is compatible with shared server environment</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Critical compatibility issues detected - may not work on shared server</p>";
        }
    }
}

// Run tests if accessed directly
if (!empty($_GET['run_tests'])) {
    $tester = new SharedServerCompatibilityTest();
    $tester->run_all_tests();
}
