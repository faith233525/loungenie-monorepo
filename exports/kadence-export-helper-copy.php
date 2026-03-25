<?php
/**
 * Kadence Export Helper
 * Usage: visit /wp-content/uploads/kadence-export-helper.php?t=export-kadence
 * This script will attempt to export theme mods and Kadence design library option
 * and write a .dat (JSON) file into the uploads folder.
 */

if (!isset($_GET['t']) || $_GET['t'] !== 'export-kadence') {
    http_response_code(403);
    echo 'Forbidden - missing token.';
    exit;
}

if (!defined('WP_LOAD_PATH')) {
    // Try to locate wp-load.php
    $path = __DIR__;
    $found = false;
    for ($i = 0; $i < 6; $i++) {
        if (file_exists($path . '/wp-load.php')) { require_once $path . '/wp-load.php'; $found = true; break; }
        $path = dirname($path);
    }
    if (!$found) {
        http_response_code(500);
        echo 'Could not find wp-load.php to bootstrap WordPress.';
        exit;
    }
}

if (!function_exists('get_theme_mods')) {
    http_response_code(500);
    echo 'WordPress environment not loaded.';
    exit;
}

// Collect data
$data = array();
$data['timestamp'] = time();
$data['site_url'] = get_site_url();
$data['theme'] = wp_get_theme()->get('Name');
$data['theme_mods'] = get_theme_mods();

// Try to include Kadence design library option if present
$kadence_lib = get_option('kadence_design_library');
if ($kadence_lib !== false) { $data['kadence_design_library'] = $kadence_lib; }

// If Kadence provides an export function, attempt to call it
if (function_exists('kadence_design_library_export')) {
    try {
        $exported = kadence_design_library_export();
        if ($exported) { $data['kadence_export_raw'] = $exported; }
    } catch (Exception $e) {
        $data['kadence_export_error'] = $e->getMessage();
    }
}

$upload = wp_upload_dir();
$filename = 'kadence-export-' . date('Ymd-His') . '.dat';
$full = trailingslashit($upload['basedir']) . $filename;

$written = file_put_contents($full, json_encode($data, JSON_PRETTY_PRINT));
if ($written === false) {
    http_response_code(500);
    echo 'Failed to write export file.';
    exit;
}

echo 'OK: ' . trailingslashit($upload['baseurl']) . $filename;
exit;
