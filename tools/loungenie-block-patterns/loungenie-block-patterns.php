<?php

/**
 * Plugin Name: LounGenie Block Patterns
 * Description: Registers LounGenie block patterns for Kadence (amenity grid, pricing table).
 * Version: 1.0.0
 * Author: GitHub Copilot
 * Text Domain: loungenie-block-patterns
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the block pattern registration implementation.
if (file_exists(__DIR__ . '/register_loungenie_block_patterns.php')) {
    include __DIR__ . '/register_loungenie_block_patterns.php';
}
