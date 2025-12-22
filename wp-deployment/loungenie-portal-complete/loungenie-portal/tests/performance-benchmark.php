<?php
/**
 * Shared Server Performance Benchmarking
 * 
 * Measures plugin performance under shared hosting constraints
 * and identifies optimization opportunities
 * 
 * @package LounGenie Portal
 * @version 1.8.0
 */

namespace LounGenie\Portal\Tests;

class PerformanceBenchmark {
    
    private $benchmarks = [];
    
    /**
     * Run all performance benchmarks
     */
    public function run_benchmarks() {
        echo "<h1>⚡ LounGenie Portal - Performance Benchmarks</h1>";
        echo "<p>Measuring plugin performance on shared server environment...</p>";
        echo "<hr>";
        
        $this->benchmark_plugin_load();
        $this->benchmark_api_endpoints();
        $this->benchmark_caching();
        $this->benchmark_database_queries();
        $this->benchmark_template_rendering();
        $this->benchmark_asset_loading();
        
        $this->display_benchmarks();
    }
    
    /**
     * Benchmark plugin loading time
     */
    private function benchmark_plugin_load() {
        global $wp_start_time;
        
        // Measure plugin initialization
        $start = microtime(true);
        
        // Simulate plugin loading
        do_action('lgp_init');
        
        $duration = (microtime(true) - $start) * 1000; // ms
        
        $this->add_benchmark([
            'category' => 'Plugin Load Time',
            'metric' => 'Initialization',
            'value' => round($duration, 2),
            'unit' => 'ms',
            'target' => 100,
            'pass' => $duration < 100,
            'notes' => $duration < 100 
                ? 'Good performance for shared server'
                : 'May slow down page load on shared hosting'
        ]);
    }
    
    /**
     * Benchmark REST API endpoints
     */
    private function benchmark_api_endpoints() {
        $endpoints = [
            '/wp-json/lgp/v1/companies',
            '/wp-json/lgp/v1/tickets',
            '/wp-json/lgp/v1/units',
        ];
        
        foreach ($endpoints as $endpoint) {
            $start = microtime(true);
            
            // Simulate API request
            $response = rest_do_remote_post(
                home_url() . $endpoint,
                [
                    'blocking' => true,
                    'timeout' => 5,
                ]
            );
            
            $duration = (microtime(true) - $start) * 1000; // ms
            
            $this->add_benchmark([
                'category' => 'API Response Time',
                'metric' => basename($endpoint),
                'value' => round($duration, 2),
                'unit' => 'ms',
                'target' => 200,
                'pass' => $duration < 200,
            ]);
        }
    }
    
    /**
     * Benchmark caching mechanisms
     */
    private function benchmark_caching() {
        // Transient caching
        $start = microtime(true);
        
        set_transient('lgp_bench_test', ['data' => 'test'], 3600);
        get_transient('lgp_bench_test');
        
        $transient_time = (microtime(true) - $start) * 1000;
        
        delete_transient('lgp_bench_test');
        
        $this->add_benchmark([
            'category' => 'Caching Performance',
            'metric' => 'Transient Set/Get',
            'value' => round($transient_time, 2),
            'unit' => 'ms',
            'target' => 5,
            'pass' => $transient_time < 5,
        ]);
        
        // Option caching fallback
        $start = microtime(true);
        
        update_option('lgp_bench_test', ['data' => 'test']);
        get_option('lgp_bench_test');
        
        $option_time = (microtime(true) - $start) * 1000;
        
        delete_option('lgp_bench_test');
        
        $this->add_benchmark([
            'category' => 'Caching Performance',
            'metric' => 'Option Set/Get',
            'value' => round($option_time, 2),
            'unit' => 'ms',
            'target' => 10,
            'pass' => $option_time < 10,
        ]);
    }
    
    /**
     * Benchmark database queries
     */
    private function benchmark_database_queries() {
        global $wpdb;
        
        $queries = [
            [
                'name' => 'User Count',
                'query' => "SELECT COUNT(*) FROM {$wpdb->users}",
            ],
            [
                'name' => 'Posts Count',
                'query' => "SELECT COUNT(*) FROM {$wpdb->posts}",
            ],
            [
                'name' => 'Post Meta Query',
                'query' => "SELECT * FROM {$wpdb->postmeta} LIMIT 100",
            ],
        ];
        
        foreach ($queries as $test) {
            $start = microtime(true);
            
            $wpdb->get_results($test['query']);
            
            $duration = (microtime(true) - $start) * 1000;
            
            $this->add_benchmark([
                'category' => 'Database Performance',
                'metric' => $test['name'],
                'value' => round($duration, 2),
                'unit' => 'ms',
                'target' => 50,
                'pass' => $duration < 50,
            ]);
        }
    }
    
    /**
     * Benchmark template rendering
     */
    private function benchmark_template_rendering() {
        $template_files = [
            'templates/portal-shell.php',
            'templates/dashboard-partner.php',
            'templates/tickets-view.php',
        ];
        
        foreach ($template_files as $template) {
            $template_path = dirname(__DIR__, 2) . '/loungenie-portal/' . $template;
            
            if (file_exists($template_path)) {
                $start = microtime(true);
                
                // Measure file load time
                ob_start();
                include $template_path;
                ob_end_clean();
                
                $duration = (microtime(true) - $start) * 1000;
                
                $this->add_benchmark([
                    'category' => 'Template Rendering',
                    'metric' => basename($template),
                    'value' => round($duration, 2),
                    'unit' => 'ms',
                    'target' => 30,
                    'pass' => $duration < 30,
                ]);
            }
        }
    }
    
    /**
     * Benchmark asset loading
     */
    private function benchmark_asset_loading() {
        $assets = [
            'css' => [
                'design-tokens.css',
                'portal-components.css',
                'login.css',
            ],
            'js' => [
                'portal.js',
                'attachments.js',
                'gateway-view.js',
            ]
        ];
        
        foreach ($assets as $type => $files) {
            foreach ($files as $file) {
                $asset_path = dirname(__DIR__, 2) . '/loungenie-portal/assets/' . $type . '/' . $file;
                
                if (file_exists($asset_path)) {
                    $size = filesize($asset_path);
                    
                    // Measure file read time
                    $start = microtime(true);
                    file_get_contents($asset_path);
                    $duration = (microtime(true) - $start) * 1000;
                    
                    $this->add_benchmark([
                        'category' => 'Asset Loading',
                        'metric' => $file,
                        'value' => round($size / 1024, 2),
                        'unit' => 'KB',
                        'load_time' => round($duration, 2) . 'ms',
                        'target' => 100,
                        'pass' => $size < 100 * 1024, // 100KB target
                    ]);
                }
            }
        }
    }
    
    /**
     * Add benchmark result
     */
    private function add_benchmark($benchmark) {
        $this->benchmarks[] = $benchmark;
    }
    
    /**
     * Display benchmark results
     */
    private function display_benchmarks() {
        echo "<h2>Benchmark Results</h2>";
        
        $categories = [];
        foreach ($this->benchmarks as $bench) {
            $cat = $bench['category'];
            if (!isset($categories[$cat])) {
                $categories[$cat] = [];
            }
            $categories[$cat][] = $bench;
        }
        
        foreach ($categories as $category => $items) {
            echo "<h3>$category</h3>";
            echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
            echo "<tr style='background-color: #f2f2f2;'>";
            echo "<th>Metric</th><th>Value</th><th>Target</th><th>Status</th>";
            echo "</tr>";
            
            foreach ($items as $item) {
                $status = $item['pass'] ? '✅ Good' : '⚠️ Needs Optimization';
                $bg_color = $item['pass'] ? '#e8f5e9' : '#fff3e0';
                
                echo "<tr style='background-color: $bg_color;'>";
                echo "<td>" . $item['metric'] . "</td>";
                echo "<td>" . $item['value'] . " " . $item['unit'];
                if (!empty($item['load_time'])) {
                    echo " (" . $item['load_time'] . ")";
                }
                echo "</td>";
                echo "<td>" . $item['target'] . " " . $item['unit'] . "</td>";
                echo "<td>" . $status . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "<br>";
        }
        
        // Summary
        $passed = count(array_filter($this->benchmarks, fn($b) => $b['pass']));
        $total = count($this->benchmarks);
        
        echo "<h2>Summary</h2>";
        echo "<p><strong>Benchmarks Passing:</strong> $passed / $total</p>";
        echo "<p><strong>Performance Grade:</strong> " . $this->get_performance_grade($passed, $total) . "</p>";
    }
    
    /**
     * Calculate performance grade
     */
    private function get_performance_grade($passed, $total) {
        $percent = ($passed / $total) * 100;
        
        if ($percent >= 90) return "✓ Excellent (90-100%)";
        if ($percent >= 70) return "✓ Good (70-89%)";
        if ($percent >= 50) return "⚠ Fair (50-69%)";
        return "✗ Poor (<50%)";
    }
}

// Run benchmarks if accessed directly
if (!empty($_GET['run_benchmarks'])) {
    $benchmark = new PerformanceBenchmark();
    $benchmark->run_benchmarks();
}
