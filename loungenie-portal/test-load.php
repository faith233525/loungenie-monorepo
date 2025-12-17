<?php
if (!function_exists('add_action')) {
    function add_action($h, $f, $p=10, $a=1) { return true; }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route($n, $r, $a) { return true; }
}
include 'api/training-videos.php';
echo "OK: API file loaded\n";
