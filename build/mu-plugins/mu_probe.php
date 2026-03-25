<?php
/* MU Probe: write a timestamp to uploads to confirm MU plugins run */
defined('ABSPATH') || exit;
add_action('init', function() {
    $upload_dir = wp_upload_dir();
    $path = $upload_dir['basedir'] . '/loungenie-mu-run.txt';
    $data = "MU probe run at: " . date('c') . "\nPHP: " . php_uname() . "\n";
    @file_put_contents($path, $data);
});
