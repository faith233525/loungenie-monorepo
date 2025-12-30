<?php
/**
 * Undefined Methods Fixer - Fixes actual missing method definitions
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

$basePath = __DIR__;
$phpFiles = array_merge(
    glob("$basePath/includes/class-*.php"),
    glob("$basePath/api/*.php")
);

echo "\n🔧 ANALYZING UNDEFINED METHOD CALLS...\n";
echo str_repeat("=", 70) . "\n\n";

$fixes = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $classMatch = [];
    
    // Extract class name
    if (preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $match)) {
        $className = $match[1];
        
        // Find all self:: calls
        preg_match_all('/self::([a-z_][a-z0-9_]*)\s*\(/i', $content, $matches);
        
        foreach ($matches[1] as $methodName) {
            // Check if method is defined
            if (!preg_match("/(?:public\s+|private\s+|protected\s+)?(?:static\s+)?function\s+$methodName\s*\(/i", $content)) {
                $fixes[$className][] = $methodName;
            }
        }
    }
}

// Get unique methods to define
$allMissingMethods = [];
foreach ($fixes as $class => $methods) {
    foreach ($methods as $method) {
        $allMissingMethods["$class::$method"] = true;
    }
}

if (empty($allMissingMethods)) {
    echo "✅ No undefined methods found - all self:: calls match defined methods\n\n";
} else {
    echo "⚠️  Found undefined methods that need to be added:\n\n";
    
    foreach ($allMissingMethods as $call => $true) {
        echo "  • $call()\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "ANALYSIS COMPLETE\n";
echo str_repeat("=", 70) . "\n";

// Details
echo "\nDETAILS BY CLASS:\n\n";

$count = 0;
foreach ($fixes as $class => $methods) {
    if (!empty($methods)) {
        echo "[$class] - " . count(array_unique($methods)) . " undefined methods\n";
        foreach (array_unique($methods) as $method) {
            echo "  • $method()\n";
            $count++;
        }
        echo "\n";
    }
}

echo str_repeat("=", 70) . "\n";
echo "Total undefined methods: $count\n";
