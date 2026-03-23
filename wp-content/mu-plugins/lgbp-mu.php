<?php

/**
 * LounGenie Block Patterns MU Loader
 * Loads the lg-block-patterns plugin automatically when placed in mu-plugins.
 */

$loader = __DIR__ . '/../plugins/lg-block-patterns/lg-block-patterns.php';
if (file_exists($loader)) {
    require_once $loader;
}
