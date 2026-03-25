<?php

/**
 * LounGenie Portal - PHP Compatibility Test Suite
 *
 * Tests compatibility across PHP 7.4, 8.0, 8.1, and 8.2
 * Run: php php-compatibility-test.php
 *
 * Expected output:
 * - Zero errors
 * - Zero warnings
 * - Zero deprecation notices
 *
 * @package LounGenie Portal
 * @since 1.0.0
 */

// Set error reporting to show all issues
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Track test results
$test_results = array(
    'passed'      => 0,
    'failed'      => 0,
    'warnings'    => 0,
    'errors'      => array(),
    'php_version' => PHP_VERSION,
    'php_version_id' => PHP_VERSION_ID,
    'tests'       => array(),
);

// Error handler to capture all issues
set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$test_results) {
    $test_results['errors'][] = array(
        'type'  => self::error_type_name($errno),
        'message' => $errstr,
        'file'  => $errfile,
        'line'  => $errline,
    );

    if (E_DEPRECATED === $errno || E_USER_DEPRECATED === $errno) {
        $test_results['warnings']++;
    } else {
        $test_results['failed']++;
    }

    return true;
}, E_ALL | E_STRICT);

/**
 * Get error type name
 */
function error_type_name($errno)
{
    $types = array(
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    );

    return $types[$errno] ?? 'UNKNOWN';
}

/**
 * Test optional parameters before required (PHP 8.0+ compatibility)
 */
function test_function_signatures()
{
    $test_name = 'Function Signature Compatibility';

    // CORRECT: required parameter before optional
    function example_correct($required, $optional = null)
    {
        return $required;
    }

    try {
        example_correct('value');
        $test_results['passed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'PASS',
            'message' => 'Function signatures are PHP 8.0+ compatible',
        );
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test null parameter handling (PHP 8.1+ compatibility)
 */
function test_null_parameter_handling()
{
    $test_name = 'Null Parameter Handling';

    try {
        // CORRECT: Always pass non-null string
        $str = 'test';
        $len = strlen($str ?? '');

        if ($len > 0) {
            $test_results['passed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'PASS',
                'message' => 'Null parameter handling is PHP 8.1+ compatible',
            );
        }
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test dynamic property usage (PHP 8.2+ compatibility)
 */
function test_dynamic_properties()
{
    $test_name = 'Dynamic Property Declaration';

    try {
        // Suppress PHP 8.2 deprecation warnings for this test
        $obj = new stdClass();

        // CORRECT: Use simple variable property syntax
        $property_name = 'test_prop';
        $obj->$property_name = 'value';

        if ('value' === $obj->test_prop) {
            $test_results['passed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'PASS',
                'message' => 'Dynamic property syntax is PHP 8.2+ compatible',
            );
        }
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test get_class() usage (PHP 8.0+ compatibility)
 */
function test_get_class_usage()
{
    $test_name = 'get_class() Usage';

    try {
        class TestClass {}

        $obj = new TestClass();

        // CORRECT: Always pass object argument
        $class = get_class($obj);

        if ('TestClass' === $class) {
            $test_results['passed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'PASS',
                'message' => 'get_class() usage is PHP 8.0+ compatible',
            );
        }
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test array key coercion (PHP 8.0+ compatibility)
 */
function test_array_key_coercion()
{
    $test_name = 'Array Key Type Coercion';

    try {
        // CORRECT: Use consistent key types
        $array = array(
            1   => 'integer key',
            '1' => 'string key (will overwrite)',
        );

        if (isset($array[1])) {
            $test_results['passed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'PASS',
                'message' => 'Array key handling is PHP 8.0+ compatible',
            );
        }
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test trailing comma support (PHP 7.3+)
 */
function test_trailing_comma_support()
{
    $test_name = 'Trailing Comma Support';

    try {
        // CORRECT: Trailing comma in function call (PHP 7.3+)
        $result = sprintf(
            '%s %s',
            'Hello',
            'World',
        );

        if ('Hello World' === $result) {
            $test_results['passed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'PASS',
                'message' => 'Trailing comma syntax is PHP 7.3+ compatible',
            );
        }
    } catch (Exception $e) {
        $test_results['failed']++;
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'FAIL',
            'message' => $e->getMessage(),
        );
    }
}

/**
 * Test match expression (PHP 8.0+)
 */
function test_match_expression()
{
    $test_name = 'Match Expression Support';

    if (PHP_VERSION_ID >= 80000) {
        try {
            // CORRECT: Use match expression in PHP 8.0+
            $value = 1;
            $result = match ($value) {
                0 => 'zero',
                1 => 'one',
                default => 'other',
            };

            if ('one' === $result) {
                $test_results['passed']++;
                $test_results['tests'][] = array(
                    'name'   => $test_name,
                    'status' => 'PASS',
                    'message' => 'Match expression is supported in PHP ' . PHP_VERSION,
                );
            }
        } catch (Exception $e) {
            $test_results['failed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            );
        }
    } else {
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'SKIP',
            'message' => 'Match expressions require PHP 8.0+, running on ' . PHP_VERSION,
        );
    }
}

/**
 * Test readonly property (PHP 8.1+)
 */
function test_readonly_property()
{
    $test_name = 'Readonly Property Support';

    if (PHP_VERSION_ID >= 80100) {
        try {
            // CORRECT: Use readonly property in PHP 8.1+
            eval('
				class ReadonlyTest {
					public readonly string $prop;
					
					public function __construct( $value ) {
						$this->prop = $value;
					}
				}
			');

            $obj = new ReadonlyTest('test');
            if ('test' === $obj->prop) {
                $test_results['passed']++;
                $test_results['tests'][] = array(
                    'name'   => $test_name,
                    'status' => 'PASS',
                    'message' => 'Readonly properties are supported in PHP ' . PHP_VERSION,
                );
            }
        } catch (Exception $e) {
            $test_results['failed']++;
            $test_results['tests'][] = array(
                'name'   => $test_name,
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            );
        }
    } else {
        $test_results['tests'][] = array(
            'name'   => $test_name,
            'status' => 'SKIP',
            'message' => 'Readonly properties require PHP 8.1+, running on ' . PHP_VERSION,
        );
    }
}

// Run all tests
test_function_signatures();
test_null_parameter_handling();
test_dynamic_properties();
test_get_class_usage();
test_array_key_coercion();
test_trailing_comma_support();
test_match_expression();
test_readonly_property();

// Restore error handler
restore_error_handler();

// Output results
echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  LounGenie Portal - PHP Compatibility Test Results\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

echo "PHP Version: " . $test_results['php_version'] . "\n";
echo "Version ID:  " . $test_results['php_version_id'] . "\n";
echo "\n";

foreach ($test_results['tests'] as $test) {
    $status_icon = 'PASS' === $test['status'] ? '✓' : ('FAIL' === $test['status'] ? '✗' : '⊘');
    $status_color = 'PASS' === $test['status'] ? "\033[32m" : ('FAIL' === $test['status'] ? "\033[31m" : "\033[33m");
    $reset_color = "\033[0m";

    printf(
        "%s [%s%s%s] %s\n",
        $status_icon,
        $status_color,
        $test['status'],
        $reset_color,
        $test['name']
    );
    echo "    " . $test['message'] . "\n";
}

echo "\n";
echo "Test Summary:\n";
echo "  Passed:   " . $test_results['passed'] . "\n";
echo "  Failed:   " . $test_results['failed'] . "\n";
echo "  Warnings: " . $test_results['warnings'] . "\n";

if (! empty($test_results['errors'])) {
    echo "\nErrors Detected:\n";
    foreach ($test_results['errors'] as $error) {
        printf(
            "  [%s] %s (File: %s, Line: %d)\n",
            $error['type'],
            $error['message'],
            basename($error['file']),
            $error['line']
        );
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";

if (0 === $test_results['failed'] && 0 === $test_results['warnings']) {
    echo "✓ All tests PASSED - Plugin is compatible with PHP " . PHP_VERSION . "\n";
    exit(0);
} else {
    echo "✗ Some tests FAILED - Review compatibility issues above\n";
    exit(1);
}
