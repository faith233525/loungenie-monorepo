<?php
// Staging preview: outputs the MU-plugin block.html directly
$path = __DIR__ . '/..' . '/wp-content/mu-plugins/block.html';
if (file_exists($path)) {
    echo file_get_contents($path);
} else {
    // try relative from webroot
    $alt = __DIR__ . '/../../wp-content/mu-plugins/block.html';
    if (file_exists($alt)) {
        echo file_get_contents($alt);
    } else {
        echo 'Preview not available.';
    }
}
