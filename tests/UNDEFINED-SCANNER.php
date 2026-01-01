<?php
/**
 * Undefined Methods/Functions Scanner - Find and log all issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

$basePath = __DIR__;
$issues = [];

echo "\n🔍 SCANNING FOR UNDEFINED METHODS & FUNCTIONS...\n";
echo str_repeat("=", 70) . "\n\n";

// Get all PHP files
$phpFiles = array_merge(
    glob("$basePath/includes/class-*.php"),
    glob("$basePath/api/*.php")
);

// Track all defined classes and functions
$definedClasses = [];
$definedMethods = [];
$definedFunctions = [];

// PASS 1: Collect all definitions
echo "PASS 1: Collecting all defined classes and methods...\n";
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    // Find class definitions
    if (preg_match_all('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $matches)) {
        foreach ($matches[1] as $class) {
            $definedClasses[$class] = basename($file);
        }
    }
    
    // Find function definitions
    if (preg_match_all('/^\s*(?:public\s+)?(?:static\s+)?function\s+([a-z_][a-z0-9_]*)/mi', $content, $matches)) {
        foreach ($matches[1] as $func) {
            $definedFunctions[$func] = basename($file);
        }
    }
}

echo "  Found " . count($definedClasses) . " classes\n";
echo "  Found " . count($definedFunctions) . " functions\n\n";

// PASS 2: Find all calls and check if they exist
echo "PASS 2: Checking for undefined calls...\n";
$undefinedCalls = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $filename = basename($file);
    
    foreach ($lines as $lineNum => $line) {
        // Skip comments
        if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
            continue;
        }
        
        // Check for static method calls (Class::method)
        if (preg_match_all('/([A-Za-z_][A-Za-z0-9_]*)::([a-z_][a-z0-9_]*)\s*\(/i', $line, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $class = $matches[1][$i];
                $method = $matches[2][$i];
                
                // Check if class exists
                if (!isset($definedClasses[$class]) && !in_array($class, ['WP_Error', 'WP_User', 'WP_Query', 'WP_Post'])) {
                    $undefinedCalls[] = [
                        'type' => 'UNDEFINED_CLASS',
                        'file' => $filename,
                        'line' => $lineNum + 1,
                        'call' => "$class::$method",
                        'code' => trim($line)
                    ];
                }
            }
        }
        
        // Check for function calls
        if (preg_match_all('/(?<!::|->|\\\)([a-z_][a-z0-9_]*)\s*\(/i', $line, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $func = $matches[1][$i];
                
                // Skip WP and PHP built-ins
                if (in_array($func, ['if', 'for', 'foreach', 'while', 'switch', 'function', 'class', 'return', 'array', 'isset', 'empty', 'die', 'exit', 'echo', 'print', 'list', 'unset', 'include', 'require', 'include_once', 'require_once', 'new', 'clone'])) {
                    continue;
                }
                
                if (in_array($func, ['file_get_contents', 'file_put_contents', 'strlen', 'substr', 'strpos', 'preg_match', 'preg_match_all', 'explode', 'implode', 'count', 'is_array', 'is_string', 'is_numeric', 'floatval', 'intval', 'trim', 'strtolower', 'strtoupper', 'str_replace', 'json_encode', 'json_decode'])) {
                    continue;
                }
                
                if (in_array($func, ['get_option', 'update_option', 'delete_option', 'add_action', 'add_filter', 'do_action', 'apply_filters', 'wp_kses', 'wp_kses_post', 'esc_html', 'esc_attr', 'esc_url', 'wp_nonce_field', 'wp_verify_nonce', 'wp_create_nonce', 'wp_localize_script', 'wp_enqueue_style', 'wp_enqueue_script', 'wp_register_script', 'wp_register_style', 'add_menu_page', 'get_current_user_id', 'current_user_can', 'wp_redirect', 'is_user_logged_in', 'is_admin', 'register_rest_route', 'rest_ensure_response'])) {
                    continue;
                }
            }
        }
    }
}

if (empty($undefinedCalls)) {
    echo "  ✅ No undefined calls found\n\n";
} else {
    echo "  ⚠️  Found " . count($undefinedCalls) . " undefined calls\n\n";
    
    echo "UNDEFINED CALLS:\n";
    foreach ($undefinedCalls as $issue) {
        echo "  • {$issue['file']}:{$issue['line']} - {$issue['call']}\n";
    }
}

// PASS 3: Check for missing helper functions
echo "\nPASS 3: Checking for missing helper functions...\n";

$helperFunctions = [
    'lgp_get_ticket_statuses',
    'lgp_get_ticket_priorities',
    'lgp_get_request_types',
];

$missingHelpers = [];
$allCode = implode("\n", array_map('file_get_contents', $phpFiles));

foreach ($helperFunctions as $func) {
    // Check if function is called but not defined
    if (preg_match("/$func\s*\(/", $allCode)) {
        if (!preg_match("/function\s+$func\s*\(/", $allCode)) {
            $missingHelpers[] = $func;
        }
    }
}

if (empty($missingHelpers)) {
    echo "  ✅ All helper functions present\n\n";
} else {
    echo "  ⚠️  Found " . count($missingHelpers) . " missing helper functions:\n";
    foreach ($missingHelpers as $func) {
        echo "    • $func()\n";
    }
    echo "\n";
}

// Summary
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY:\n";
echo "  Undefined Classes: " . count(array_filter($undefinedCalls, fn($x) => $x['type'] === 'UNDEFINED_CLASS')) . "\n";
echo "  Undefined Methods: " . count(array_filter($undefinedCalls, fn($x) => $x['type'] === 'UNDEFINED_METHOD')) . "\n";
echo "  Missing Helpers: " . count($missingHelpers) . "\n";
echo "\n";

if (empty($undefinedCalls) && empty($missingHelpers)) {
    echo "✅ NO UNDEFINED METHODS OR FUNCTIONS FOUND\n";
} else {
    echo "⚠️  ISSUES FOUND - SEE ABOVE\n";
}

echo str_repeat("=", 70) . "\n";
