<?php
$token = '715ab1e8-3fbb-4143-a398-b3f87e570feb';
if(!isset($_GET['t']) || $_GET['t'] !== $token){ http_response_code(403); echo 'Forbidden'; exit;}
define('WP_INSTALLING', true);
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( !function_exists('wp_install') ) { require_once ABSPATH . 'wp-admin/includes/upgrade.php'; }
try {
  $result = wp_install('Staging Loungenie', 'copilot_admin', 'admin@loungenie.com', true, '', 'j>X:1Oh\zWp2ETmq');
  echo 'Installed';
} catch (Exception $e) {
  http_response_code(500);
  echo 'Error: ' . $e->getMessage();
}
?>
