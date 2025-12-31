<?php
/**
 * Portal Hub Landing (Unified Entry)
 * Modern hub linking login, partner, and support experiences.
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH')) {
    exit;
}

$assets_url = trailingslashit(LGP_ASSETS_URL . 'css/');
$login_url  = home_url('/portal-login');
$partner_url = home_url('/portal');
$support_url = home_url('/portal');
$readme_url  = plugins_url('README.md', LGP_PLUGIN_FILE);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_bloginfo('name')); ?> — <?php esc_html_e('Portal Hub', 'loungenie-portal'); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($assets_url . 'design-tokens.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url($assets_url . 'portal-hub.css'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class('portal-hub'); ?>>
    <div class="portal-hub-shell">
        <div class="portal-hub-hero">
            <div>
                <div class="portal-hub-badge"><?php esc_html_e('LounGenie Portal by Pool Safe', 'loungenie-portal'); ?></div>
                <h1><?php esc_html_e('Unified Portal Access', 'loungenie-portal'); ?></h1>
                <p><?php esc_html_e('Choose your path: company partners manage properties and service requests; support agents handle tickets and queues.', 'loungenie-portal'); ?></p>
            </div>
            <div class="portal-hub-hero-actions">
                <a class="portal-hub-cta" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Go to Login', 'loungenie-portal'); ?></a>
                <a class="portal-hub-cta ghost" href="<?php echo esc_url($readme_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Portal README', 'loungenie-portal'); ?></a>
            </div>
        </div>

        <div class="portal-hub-grid">
            <div class="portal-hub-card">
                <div class="title"><?php esc_html_e('Partner Portal', 'loungenie-portal'); ?></div>
                <div class="desc"><?php esc_html_e('Company-centric dashboard: total units, properties, open requests, and service logs with quick actions.', 'loungenie-portal'); ?></div>
                <div class="portal-hub-meta">
                    <span class="portal-hub-pill"><?php esc_html_e('Company login', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill green"><?php esc_html_e('Units & requests', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill blue"><?php esc_html_e('Support tickets', 'loungenie-portal'); ?></span>
                </div>
                <a class="portal-hub-cta" href="<?php echo esc_url($partner_url); ?>"><?php esc_html_e('Open Partner Portal', 'loungenie-portal'); ?></a>
            </div>

            <div class="portal-hub-card">
                <div class="title"><?php esc_html_e('Support Portal', 'loungenie-portal'); ?></div>
                <div class="desc"><?php esc_html_e('Agent workspace with queues, ticket metrics, and recent tickets for faster responses and SLA tracking.', 'loungenie-portal'); ?></div>
                <div class="portal-hub-meta">
                    <span class="portal-hub-pill blue"><?php esc_html_e('Queues', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill"><?php esc_html_e('All tickets', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill green"><?php esc_html_e('Audit log', 'loungenie-portal'); ?></span>
                </div>
                <a class="portal-hub-cta secondary" href="<?php echo esc_url($support_url); ?>"><?php esc_html_e('Open Support Portal', 'loungenie-portal'); ?></a>
            </div>

            <div class="portal-hub-card">
                <div class="title"><?php esc_html_e('Login Page', 'loungenie-portal'); ?></div>
                <div class="desc"><?php esc_html_e('Split-screen brand login with company username/password for partners and Outlook sign-in for support.', 'loungenie-portal'); ?></div>
                <div class="portal-hub-meta">
                    <span class="portal-hub-pill"><?php esc_html_e('Partner sign-in', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill blue"><?php esc_html_e('Outlook (support)', 'loungenie-portal'); ?></span>
                    <span class="portal-hub-pill green"><?php esc_html_e('Brand palette', 'loungenie-portal'); ?></span>
                </div>
                <a class="portal-hub-cta" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Open Login', 'loungenie-portal'); ?></a>
            </div>
        </div>

        <div class="portal-hub-footer">
            <?php esc_html_e('Need a different start page? Wire your CMS/router to point to this hub for a unified entry.', 'loungenie-portal'); ?>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
