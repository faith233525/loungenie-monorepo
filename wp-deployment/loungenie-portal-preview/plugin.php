<?php

/**
 * Plugin Name: LounGenie Portal Preview
 * Plugin URI: https://loungenie.com/portal
 * Description: Production preview of LounGenie Portal with Support & Partner dashboards
 * Version: 1.0.0
 * Author: LounGenie Team
 * Author URI: https://loungenie.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loungenie-portal-preview
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package LounGenie_Portal_Preview
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LOUNGENIE_PORTAL_PREVIEW_VERSION', '1.0.0');
define('LOUNGENIE_PORTAL_PREVIEW_FILE', __FILE__);
define('LOUNGENIE_PORTAL_PREVIEW_DIR', plugin_dir_path(__FILE__));
define('LOUNGENIE_PORTAL_PREVIEW_URL', plugin_dir_url(__FILE__));

/**
 * Plugin activation hook
 */
function loungenie_portal_preview_activate()
{
    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('LounGenie Portal Preview requires WordPress 5.8 or higher.');
    }
}
register_activation_hook(__FILE__, 'loungenie_portal_preview_activate');

/**
 * Load plugin text domain
 */
function loungenie_portal_preview_load_textdomain()
{
    load_plugin_textdomain(
        'loungenie-portal-preview',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'loungenie_portal_preview_load_textdomain');

/**
 * Add portal preview page to WordPress admin
 */
function loungenie_portal_preview_add_admin_page()
{
    add_menu_page(
        'LounGenie Portal Preview',
        'Portal Preview',
        'manage_options',
        'loungenie-portal-preview',
        'loungenie_portal_preview_render_page',
        'dashicons-visibility',
        25
    );
}
add_action('admin_menu', 'loungenie_portal_preview_add_admin_page');

/**
 * Render portal preview page
 */
function loungenie_portal_preview_render_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('LounGenie Portal Preview', 'loungenie-portal-preview'); ?></h1>

        <div class="updated notice">
            <p>
                <strong><?php esc_html_e('Portal Preview Available', 'loungenie-portal-preview'); ?></strong><br>
                <?php esc_html_e('Click the button below to view the production portal preview with all dashboard views.', 'loungenie-portal-preview'); ?>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e('Portal Preview', 'loungenie-portal-preview'); ?>
                </th>
                <td>
                    <a href="<?php echo esc_url(LOUNGENIE_PORTAL_PREVIEW_URL . 'PRODUCTION_PORTAL_PREVIEW.html'); ?>"
                        class="button button-primary" target="_blank">
                        <?php esc_html_e('Open Portal Preview', 'loungenie-portal-preview'); ?>
                    </a>
                    <p class="description">
                        <?php esc_html_e('Opens in a new window with Support Login, Partner Login, Support Dashboard, and Partner Dashboard.', 'loungenie-portal-preview'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Features', 'loungenie-portal-preview'); ?>
                </th>
                <td>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php esc_html_e('Support Login with Outlook 365 (Cyan theme)', 'loungenie-portal-preview'); ?></li>
                        <li><?php esc_html_e('Partner Login with Username/Password (Teal theme)', 'loungenie-portal-preview'); ?></li>
                        <li><?php esc_html_e('Support Dashboard with all companies', 'loungenie-portal-preview'); ?></li>
                        <li><?php esc_html_e('Partner Dashboard with company-scoped view', 'loungenie-portal-preview'); ?></li>
                        <li><?php esc_html_e('Complete service request form', 'loungenie-portal-preview'); ?></li>
                        <li><?php esc_html_e('Responsive design for all devices', 'loungenie-portal-preview'); ?></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Version', 'loungenie-portal-preview'); ?>
                </th>
                <td>
                    <?php echo esc_html(LOUNGENIE_PORTAL_PREVIEW_VERSION); ?>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Integration Notes', 'loungenie-portal-preview'); ?></h2>
        <p>
            <?php esc_html_e('This is a production-ready preview of the LounGenie Portal. To integrate with your WordPress site:', 'loungenie-portal-preview'); ?>
        </p>
        <ol style="margin-left: 20px;">
            <li><?php esc_html_e('Review the portal preview to understand the design and functionality', 'loungenie-portal-preview'); ?></li>
            <li><?php esc_html_e('Install the full LounGenie Portal plugin from the plugins directory', 'loungenie-portal-preview'); ?></li>
            <li><?php esc_html_e('Configure authentication and database connections', 'loungenie-portal-preview'); ?></li>
            <li><?php esc_html_e('Test with production data', 'loungenie-portal-preview'); ?></li>
        </ol>
    </div>
<?php
}

/**
 * Add plugin action links
 */
function loungenie_portal_preview_add_action_links($links)
{
    $portal_link = '<a href="' . admin_url('admin.php?page=loungenie-portal-preview') . '">' . esc_html__('Preview', 'loungenie-portal-preview') . '</a>';
    array_unshift($links, $portal_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'loungenie_portal_preview_add_action_links');
