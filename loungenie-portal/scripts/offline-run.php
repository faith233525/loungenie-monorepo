<?php
/**
 * LounGenie Portal: Offline Development & Testing Script
 *
 * Provides:
 * 1. Mock data seeding (users, companies, units, gateways, tickets)
 * 2. Offline test execution (PHPUnit + Jest)
 * 3. Dashboard rendering simulation
 * 4. Audit log validation
 * 5. Data export (JSON/CSV)
 *
 * Usage:
 *   php scripts/offline-run.php [command] [options]
 *
 * Commands:
 *   seed              - Seed mock data
 *   test              - Run all tests offline
 *   dashboard         - Render dashboard simulations
 *   validate          - Validate data structures
 *   export            - Export mock data as JSON/CSV
 *   report            - Generate comprehensive report
 */

define('OFFLINE_MODE', true);
define('OFFLINE_BASEPATH', dirname(__DIR__));
define('OFFLINE_SCRIPTPATH', __DIR__);
define('OFFLINE_DATAPATH', __DIR__ . '/offline-data');

// Initialize offline environment
require_once __DIR__ . '/OfflineBootstrap.php';
require_once __DIR__ . '/OfflineDataSeeder.php';
require_once __DIR__ . '/OfflineHelpers.php';

// Parse arguments
$command = isset($argv[1]) ? $argv[1] : 'report';
$options = array_slice($argv, 2);

// Color output helpers
class OfflineOutput {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const RED = "\033[31m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";

    public static function success($msg) {
        echo self::GREEN . "✓ " . $msg . self::RESET . "\n";
    }

    public static function info($msg) {
        echo self::BLUE . "ℹ " . $msg . self::RESET . "\n";
    }

    public static function warn($msg) {
        echo self::YELLOW . "⚠ " . $msg . self::RESET . "\n";
    }

    public static function error($msg) {
        echo self::RED . "✗ " . $msg . self::RESET . "\n";
    }

    public static function header($title) {
        echo "\n" . self::CYAN . str_repeat("=", 70) . self::RESET . "\n";
        echo self::CYAN . "  " . $title . self::RESET . "\n";
        echo self::CYAN . str_repeat("=", 70) . self::RESET . "\n\n";
    }

    public static function section($title) {
        echo "\n" . self::CYAN . $title . self::RESET . "\n";
        echo str_repeat("-", strlen($title)) . "\n";
    }
}

// Command dispatcher
$commands = [
    'seed' => 'OfflineDataSeeder::run',
    'test' => 'OfflineTestRunner::run',
    'dashboard' => 'OfflineDashboardRenderer::run',
    'validate' => 'OfflineValidator::run',
    'export' => 'OfflineExporter::run',
    'report' => 'OfflineReporter::run',
    'help' => 'OfflineHelp::display',
];

if (!isset($commands[$command])) {
    OfflineOutput::error("Unknown command: $command");
    OfflineOutput::info("Use 'help' command to see available options");
    exit(1);
}

// Ensure offline data directory exists
if (!is_dir(OFFLINE_DATAPATH)) {
    mkdir(OFFLINE_DATAPATH, 0755, true);
}

try {
    call_user_func($commands[$command], $options);
} catch (Exception $e) {
    OfflineOutput::error("Fatal error: " . $e->getMessage());
    OfflineOutput::error("Stack trace:\n" . $e->getTraceAsString());
    exit(1);
}

exit(0);
