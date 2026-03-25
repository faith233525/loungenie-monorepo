<?php

/**
 * Bootstrap file for running unit tests for LounGenie Portal
 *
 * @package LounGenie_Portal
 * @since 1.0.0
 */

// Get the path to WordPress tests library
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Get the path to WordPress itself
$_wp_dir = getenv('WP_ROOT_DIR');
if (!$_wp_dir) {
    $_wp_dir = '/tmp/wordpress';
}

// Define test constants
if (!defined('WP_TESTS_DIR')) {
    define('WP_TESTS_DIR', $_tests_dir);
}

if (!defined('WP_ROOT_DIR')) {
    define('WP_ROOT_DIR', $_wp_dir);
}

// Include WordPress test functions
require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 *
 * @return void
 */
function _manually_load_plugin()
{
    // Load the main plugin file
    require dirname(__FILE__) . '/../loungenie-portal.php';
}

// Register the plugin to be loaded before tests
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Include the WordPress test bootstrap
require WP_TESTS_DIR . '/includes/bootstrap.php';

// Initialize test utilities
require_once dirname(__FILE__) . '/test-utils.php';
