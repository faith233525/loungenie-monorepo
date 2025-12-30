<?php
/**
 * Plugin Name: LounGenie Portal
 * Plugin URI: https://github.com/faith233525/Pool-Safe-Portal
 * Description: Enterprise-grade partner management and SaaS portal plugin for WordPress
 * Version: 1.9.1
 * Author: Pool Safe Inc
 * Author URI: https://poolsafe.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loungenie-portal
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires WP: 5.8
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
if ( ! defined( 'LGP_VERSION' ) ) {
	define( 'LGP_VERSION', '1.9.1' );
}
if ( ! defined( 'LGP_PLUGIN_DIR' ) ) {
	define( 'LGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LGP_PLUGIN_URL' ) ) {
	define( 'LGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'LGP_ASSETS_URL' ) ) {
	define( 'LGP_ASSETS_URL', LGP_PLUGIN_URL . 'assets/' );
}

/**
 * Load the plugin loader class
 */
require_once LGP_PLUGIN_DIR . 'includes/class-lgp-loader.php';

/**
 * Begin execution of the plugin
 */
function run_loungenie_portal() {
	$plugin = new LGP_Loader();
	$plugin->run();
}

// Run the plugin.
run_loungenie_portal();

/**
 * Activation hook
 */
register_activation_hook( __FILE__, function() {
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-activator.php';
	LGP_Activator::activate();
});

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-deactivator.php';
	LGP_Deactivator::deactivate();
});
