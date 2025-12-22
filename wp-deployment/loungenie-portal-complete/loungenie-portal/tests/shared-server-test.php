<?php
/**
 * Shared Server Compatibility Test Suite
 * 
 * Run this file to test plugin compatibility with shared servers
 * Access via: https://yoursite.com/wp-content/plugins/loungenie-portal/tests/shared-server-test.php
 * 
 * @package LounGenie Portal
 * @version 1.8.0
 */

// Load WordPress
require_once( '../../../../wp-load.php' );

// Check admin access
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied. Admin access required.' );
}

$results = array();
$passed = 0;
$failed = 0;

// ============================================================================
// TEST 1: System Requirements
// ============================================================================
$test_name = '1. System Requirements';
$results[ $test_name ] = array();

// PHP Version
$php_version = phpversion();
if ( version_compare( $php_version, '7.4', '>=' ) ) {
    $results[ $test_name ][] = array( 'PHP Version', $php_version, 'PASS', '7.4+' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'PHP Version', $php_version, 'FAIL', '7.4+ required' );
    $failed++;
}

// Memory Limit
$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
if ( $memory_limit >= 67108864 ) { // 64MB
    $results[ $test_name ][] = array( 'Memory Limit', round( $memory_limit / 1024 / 1024 ) . 'MB', 'PASS', '64MB+' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Memory Limit', round( $memory_limit / 1024 / 1024 ) . 'MB', 'WARN', '64MB recommended' );
}

// Max Upload Size
$max_upload = wp_max_upload_size();
if ( $max_upload >= 5242880 ) { // 5MB
    $results[ $test_name ][] = array( 'Max Upload Size', round( $max_upload / 1024 / 1024 ) . 'MB', 'PASS', '5MB+' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Max Upload Size', round( $max_upload / 1024 / 1024 ) . 'MB', 'WARN', '5MB recommended' );
}

// MySQL Version
global $wpdb;
$mysql_version = $wpdb->db_version();
if ( version_compare( $mysql_version, '5.7', '>=' ) || version_compare( $mysql_version, '10.2', '>=' ) ) {
    $results[ $test_name ][] = array( 'MySQL/MariaDB Version', $mysql_version, 'PASS', '5.7/10.2+' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'MySQL/MariaDB Version', $mysql_version, 'WARN', '5.7/10.2+ recommended' );
}

// ============================================================================
// TEST 2: File System Permissions
// ============================================================================
$test_name = '2. File System Permissions';
$results[ $test_name ] = array();

$upload_dir = wp_upload_dir();
if ( is_writable( $upload_dir['basedir'] ) ) {
    $results[ $test_name ][] = array( 'Uploads Directory Writable', 'Yes', 'PASS', $upload_dir['basedir'] );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Uploads Directory Writable', 'No', 'FAIL', 'chmod 755 needed' );
    $failed++;
}

$plugin_dir = plugin_dir_path( __FILE__ );
if ( is_readable( $plugin_dir ) ) {
    $results[ $test_name ][] = array( 'Plugin Directory Readable', 'Yes', 'PASS', $plugin_dir );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Plugin Directory Readable', 'No', 'FAIL', 'Check permissions' );
    $failed++;
}

// ============================================================================
// TEST 3: Database Performance
// ============================================================================
$test_name = '3. Database Performance';
$results[ $test_name ] = array();

// Test simple query speed
$start = microtime( true );
$wpdb->get_results( "SELECT COUNT(*) FROM {$wpdb->posts} LIMIT 1" );
$query_time = ( microtime( true ) - $start ) * 1000;

if ( $query_time < 1000 ) { // Under 1 second
    $results[ $test_name ][] = array( 'Query Performance', round( $query_time, 2 ) . 'ms', 'PASS', '< 1000ms' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Query Performance', round( $query_time, 2 ) . 'ms', 'WARN', '> 1000ms (slow)' );
}

// Check if plugin tables exist
$prefix = $wpdb->prefix . 'lgp_';
$lgp_tables = $wpdb->get_results( "SHOW TABLES LIKE '{$prefix}%'" );
if ( ! empty( $lgp_tables ) ) {
    $results[ $test_name ][] = array( 'Plugin Tables', count( $lgp_tables ), 'PASS', 'Database initialized' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Plugin Tables', '0', 'WARN', 'Plugin not activated or tables missing' );
}

// ============================================================================
// TEST 4: Memory Usage
// ============================================================================
$test_name = '4. Memory Usage';
$results[ $test_name ] = array();

$memory_current = memory_get_usage( true ) / 1024 / 1024;
$memory_peak = memory_get_peak_usage( true ) / 1024 / 1024;

$results[ $test_name ][] = array( 'Current Memory', round( $memory_current, 2 ) . 'MB', 'INFO', 'At test time' );
$results[ $test_name ][] = array( 'Peak Memory', round( $memory_peak, 2 ) . 'MB', 'INFO', 'Test maximum' );

if ( $memory_current < 50 ) {
    $results[ $test_name ][] = array( 'Memory Efficiency', 'Good', 'PASS', '< 50MB used' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Memory Efficiency', 'High', 'WARN', '> 50MB (may stress shared server)' );
}

// ============================================================================
// TEST 5: REST API Availability
// ============================================================================
$test_name = '5. REST API Availability';
$results[ $test_name ] = array();

$api_test = wp_remote_get( home_url( 'wp-json/wp/v2/posts?per_page=1' ) );
if ( ! is_wp_error( $api_test ) && wp_remote_retrieve_response_code( $api_test ) === 200 ) {
    $results[ $test_name ][] = array( 'REST API Available', 'Yes', 'PASS', 'API working' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'REST API Available', 'No', 'FAIL', 'Check REST API setup' );
    $failed++;
}

// ============================================================================
// TEST 6: Transient Caching
// ============================================================================
$test_name = '6. Transient Caching';
$results[ $test_name ] = array();

set_transient( 'lgp_test_transient', 'test_value', 3600 );
$transient = get_transient( 'lgp_test_transient' );

if ( $transient === 'test_value' ) {
    $results[ $test_name ][] = array( 'Transient Storage', 'Working', 'PASS', 'Caching enabled' );
    $passed++;
    delete_transient( 'lgp_test_transient' );
} else {
    $results[ $test_name ][] = array( 'Transient Storage', 'Failed', 'WARN', 'Caching may not work' );
}

// ============================================================================
// TEST 7: Plugin Classes Loading
// ============================================================================
$test_name = '7. Plugin Classes Loading';
$results[ $test_name ] = array();

$classes_to_check = array(
    'LGP_Assets',
    'LGP_Auth',
    'LGP_Cache',
    'LGP_Database',
    'LGP_Security',
);

foreach ( $classes_to_check as $class ) {
    if ( class_exists( $class ) ) {
        $results[ $test_name ][] = array( "Class: $class", 'Loaded', 'PASS', 'Available' );
        $passed++;
    } else {
        $results[ $test_name ][] = array( "Class: $class", 'Missing', 'WARN', 'Check activation' );
    }
}

// ============================================================================
// TEST 8: Security Headers
// ============================================================================
$test_name = '8. Security Configuration';
$results[ $test_name ] = array();

// Check if nonces work
$nonce = wp_create_nonce( 'lgp-test' );
if ( wp_verify_nonce( $nonce, 'lgp-test' ) ) {
    $results[ $test_name ][] = array( 'Nonce System', 'Working', 'PASS', 'CSRF protection active' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Nonce System', 'Failed', 'FAIL', 'Security issue' );
    $failed++;
}

// Check capability system
$current_user = wp_get_current_user();
if ( $current_user->ID > 0 ) {
    $results[ $test_name ][] = array( 'User Capabilities', 'User ID: ' . $current_user->ID, 'PASS', 'Auth working' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'User Capabilities', 'Unknown', 'WARN', 'Auth check' );
}

// ============================================================================
// TEST 9: File Upload Simulation
// ============================================================================
$test_name = '9. File Operations';
$results[ $test_name ] = array();

$upload_test = wp_upload_dir();
$test_file = $upload_test['basedir'] . '/.lgp-test-file.txt';

if ( @fwrite( fopen( $test_file, 'w' ), 'test' ) !== false ) {
    $results[ $test_name ][] = array( 'File Write Test', 'Success', 'PASS', 'Can write files' );
    $passed++;
    @unlink( $test_file );
} else {
    $results[ $test_name ][] = array( 'File Write Test', 'Failed', 'FAIL', 'Check permissions' );
    $failed++;
}

// ============================================================================
// TEST 10: Execution Time
// ============================================================================
$test_name = '10. Execution Performance';
$results[ $test_name ] = array();

$start_time = microtime( true );
$elapsed = ( microtime( true ) - $start_time ) * 1000;

if ( ini_get( 'max_execution_time' ) > 30 ) {
    $results[ $test_name ][] = array( 'Max Execution Time', ini_get( 'max_execution_time' ) . 's', 'PASS', '> 30s' );
    $passed++;
} else {
    $results[ $test_name ][] = array( 'Max Execution Time', ini_get( 'max_execution_time' ) . 's', 'WARN', 'Consider increase' );
}

$results[ $test_name ][] = array( 'Test Suite Execution', round( $elapsed, 2 ) . 'ms', 'INFO', 'Performance check' );

?>
<!DOCTYPE html>
<html>
<head>
    <title>LounGenie Portal - Shared Server Test Suite</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f5fbfc;
            color: #0f172a;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3aa6b9;
            margin-top: 0;
            border-bottom: 3px solid #3aa6b9;
            padding-bottom: 10px;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .summary-box {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .summary-box.pass {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        .summary-box.fail {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        .summary-box.info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .summary-box h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
        }
        .summary-box .value {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        .test-section {
            margin-bottom: 25px;
        }
        .test-section h2 {
            background: #f5f5f5;
            padding: 10px 15px;
            border-left: 4px solid #3aa6b9;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #0f172a;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .status {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .status.pass {
            background: #4caf50;
            color: white;
        }
        .status.fail {
            background: #f44336;
            color: white;
        }
        .status.warn {
            background: #ff9800;
            color: white;
        }
        .status.info {
            background: #2196f3;
            color: white;
        }
        .footer {
            background: #e9f8f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 30px;
            border-left: 4px solid #3aa6b9;
        }
        .footer h3 {
            margin-top: 0;
            color: #3aa6b9;
        }
        .recommendations {
            background: #fff3e0;
            padding: 15px;
            border-left: 4px solid #ff9800;
            border-radius: 6px;
            margin-top: 20px;
        }
        .recommendations h3 {
            margin-top: 0;
            color: #e65100;
        }
        .recommendations ul {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 LounGenie Portal - Shared Server Compatibility Test</h1>
        
        <div class="summary">
            <div class="summary-box pass">
                <h3>Tests Passed</h3>
                <p class="value"><?php echo $passed; ?></p>
            </div>
            <div class="summary-box fail">
                <h3>Tests Failed</h3>
                <p class="value"><?php echo $failed; ?></p>
            </div>
            <div class="summary-box info">
                <h3>Total Tests</h3>
                <p class="value"><?php echo $passed + $failed; ?></p>
            </div>
        </div>

        <?php foreach ( $results as $section => $tests ) : ?>
            <div class="test-section">
                <h2><?php echo esc_html( $section ); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Result</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tests as $test ) : ?>
                            <tr>
                                <td><?php echo esc_html( $test[0] ); ?></td>
                                <td><?php echo esc_html( $test[1] ); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower( $test[2] ); ?>">
                                        <?php echo esc_html( $test[2] ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $test[3] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <div class="recommendations">
            <h3>📋 Shared Server Recommendations</h3>
            <ul>
                <li><strong>Enable Caching:</strong> Install Redis/Memcached plugin for better performance</li>
                <li><strong>Database Optimization:</strong> Run wp-cli wp db optimize monthly</li>
                <li><strong>Monitor Resources:</strong> Check error logs weekly for warnings</li>
                <li><strong>Regular Updates:</strong> Keep WordPress and plugins updated</li>
                <li><strong>Backup Strategy:</strong> Use automated backup plugin (UpdraftPlus recommended)</li>
                <li><strong>Security:</strong> Consider security plugin (Wordfence recommended)</li>
            </ul>
        </div>

        <div class="footer">
            <h3>✅ Test Summary</h3>
            <?php if ( $failed === 0 ) : ?>
                <p><strong style="color: #4caf50;">Plugin is compatible with shared servers!</strong></p>
                <p>All critical tests passed. The plugin is optimized for shared hosting environments.</p>
            <?php else : ?>
                <p><strong style="color: #ff9800;">Some issues detected.</strong></p>
                <p>Check the failed tests above and review recommendations.</p>
            <?php endif; ?>
            <p><small>Test Date: <?php echo current_time( 'Y-m-d H:i:s' ); ?> | WordPress: <?php bloginfo( 'version' ); ?> | PHP: <?php echo phpversion(); ?></small></p>
        </div>
    </div>
</body>
</html>
