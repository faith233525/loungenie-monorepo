<?php
/**
 * PHPUnit Bootstrap
 */

// Ensure Composer autoload is available
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
	fwrite(STDERR, "Composer autoload not found. Run 'composer install' in loungenie-portal/.\n");
}
require_once $autoload;

// Minimal constants that some code may expect
if (!defined('LGP_VERSION')) {
	define('LGP_VERSION', '1.0.0-test');
}
if (!defined('LGP_PLUGIN_DIR')) {
	define('LGP_PLUGIN_DIR', __DIR__ . '/../');
}
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/../');
}

// Provide a very small $wpdb stub for tests that need prefix/
global $wpdb;
if (!isset($wpdb)) {
	$wpdb = new class {
		public $prefix = 'wp_';
		public function get_charset_collate() { return 'CHARSET'; }
		public function query($sql) { return true; }
	};
}

// Output basic info to help during local runs
fwrite(STDOUT, "LounGenie Portal Test Bootstrap\n");
fwrite(STDOUT, "PHP Version: " . PHP_VERSION . "\n");

// Load base WPTestCase class used by our tests
require_once __DIR__ . '/Util/WPTestCase.php';

// Create a minimal stub for wp-admin/includes/upgrade.php to satisfy require_once
$upgradeDir = ABSPATH . 'wp-admin/includes/';
if (!is_dir($upgradeDir)) {
	@mkdir($upgradeDir, 0777, true);
}
$upgradeFile = $upgradeDir . 'upgrade.php';
if (!file_exists($upgradeFile)) {
	file_put_contents($upgradeFile, '<?php
if (!function_exists("dbDelta")) { function dbDelta($sql) { return true; } }
');
}
