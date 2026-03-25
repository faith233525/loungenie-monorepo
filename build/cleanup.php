<?php
/**
 * cleanup.php
 * Moves .py, .csv, and .html files from the site root into /backups.
 * Usage: visit https://loungenie.com/cleanup.php?token=YOUR_TOKEN
 * Safety: script creates /backups and only moves files from the document root (not recursive into subfolders).
 */

// Simple token check — change before use
$expected = 'cleanup-token-please-change';
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== $expected) {
    http_response_code(403);
    echo 'Forbidden - invalid token';
    exit;
}

$docroot = dirname(__FILE__);
$backups = $docroot . '/backups';
if (!file_exists($backups)) {
    mkdir($backups, 0755, true);
}

$moved = array();
$errors = array();
$patterns = array('*.py','*.csv','*.html');
foreach ($patterns as $p) {
    foreach (glob($docroot . '/' . $p) as $file) {
        $base = basename($file);
        $dest = $backups . '/' . $base;
        if (rename($file, $dest)) {
            $moved[] = $base;
        } else {
            $errors[] = $base;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(array('moved'=>$moved,'errors'=>$errors), JSON_PRETTY_PRINT);
exit;
