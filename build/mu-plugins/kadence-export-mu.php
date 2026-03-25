<?php
/**
 * Kadence Export MU Helper
 * Token-gated MU plugin to export theme mods and Kadence options into a .dat file in uploads.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'kadence_export_mu_init' );
function kadence_export_mu_init() {
    if ( ! isset( $_GET['t'] ) || $_GET['t'] !== 'export-kadence' ) {
        return;
    }

    // Change this token to something secret before triggering on a public site
    $expected_token = 'export-kadence-token';
    $token = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';

    if ( $token !== $expected_token ) {
        status_header( 403 );
        echo 'ERR: invalid token';
        exit;
    }

    if ( ! function_exists( 'get_theme_mods' ) ) {
        echo 'ERR: WP not ready';
        exit;
    }

    $mods = get_theme_mods();
    $kadence_options = get_option( 'kadence_blocks' );
    if ( false === $kadence_options ) {
        $kadence_options = get_option( 'kadence' );
    }

    $data = array(
        'generated' => current_time( 'mysql' ),
        'site_url'  => get_bloginfo( 'url' ),
        'mods'      => $mods,
        'kadence'   => $kadence_options,
    );

    $upload = wp_upload_dir();
    $filename = 'kadence-export-' . date( 'Ymd-His' ) . '.dat';
    $path = trailingslashit( $upload['basedir'] ) . $filename;

    $content = wp_json_encode( $data, JSON_PRETTY_PRINT );

    $written = @file_put_contents( $path, $content );
    if ( false === $written ) {
        echo 'ERR: write failed to ' . esc_html( $path );
    } else {
        $url = trailingslashit( $upload['baseurl'] ) . $filename;
        echo 'OK: ' . esc_url( $url );
    }

    // End execution after responding
    exit;
}
<?php
/**
 * Plugin Name: Kadence Export MU (temporary)
 * Description: Temporary MU-plugin to run Kadence export via a tokenized web request. Remove after use.
 */

if (php_sapi_name() === 'cli') {
    return;
}

add_action('init', function () {
    if (!isset($_GET['t']) || $_GET['t'] !== 'export-kadence-mu') {
        return;
    }

    try {
        if (!function_exists('get_theme_mods')) {
            // Try to bootstrap if not available (defensive)
            $path = __DIR__;
            $found = false;
            for ($i = 0; $i < 6; $i++) {
                if (file_exists($path . '/wp-load.php')) { require_once $path . '/wp-load.php'; $found = true; break; }
                $path = dirname($path);
            }
        }

        if (!function_exists('get_theme_mods')) {
            throw new Exception('WordPress environment not available.');
        }

        $data = array();
        $data['timestamp'] = time();
        $data['site_url'] = get_site_url();
        $data['theme'] = wp_get_theme()->get('Name');
        $data['theme_mods'] = get_theme_mods();

        $kadence_lib = get_option('kadence_design_library');
        if ($kadence_lib !== false) { $data['kadence_design_library'] = $kadence_lib; }

        if (function_exists('kadence_design_library_export')) {
            try {
                $exported = kadence_design_library_export();
                if ($exported) { $data['kadence_export_raw'] = $exported; }
            } catch (Exception $e) {
                $data['kadence_export_error'] = $e->getMessage();
            }
        }

        $upload = wp_upload_dir();
        $filename = 'kadence-export-' . date('Ymd-His') . '.dat';
        $full = trailingslashit($upload['basedir']) . $filename;

        $written = @file_put_contents($full, json_encode($data, JSON_PRETTY_PRINT));
        if ($written === false) {
            throw new Exception('Failed to write export file to ' . $full);
        }

        // success echo
        echo 'OK: ' . trailingslashit($upload['baseurl']) . $filename;

        // write debug log entry
        $logpath = trailingslashit($upload['basedir']) . 'kadence-export-mu.log';
        @file_put_contents($logpath, date('c') . " - wrote $filename\n", FILE_APPEND);

        exit;

    } catch (Exception $ex) {
        // attempt to log error to uploads if possible
        try {
            $up = function_exists('wp_upload_dir') ? wp_upload_dir() : array('basedir' => __DIR__);
            $logpath = trailingslashit($up['basedir']) . 'kadence-export-mu.log';
            @file_put_contents($logpath, date('c') . ' - ERROR: ' . $ex->getMessage() . "\n", FILE_APPEND);
        } catch (Exception $_) {
            // swallow
        }
        http_response_code(500);
        echo 'ERROR: ' . $ex->getMessage();
        exit;
    }
});
<?php
/** Kadence export MU-plugin: writes a .dat export into uploads when run. */
if (!defined('WP_LOAD_PATH')) {
    // allow MU-plugin to run normally
}
add_action('init', function() {
    // Only run for admin or front requests, and only once per hour
    if (defined('WP_INSTALLING') && WP_INSTALLING) return;
    if (get_transient('kadence_mu_export_done')) return;

    try {
        $data = array();
        $data['timestamp'] = time();
        $data['site_url'] = get_site_url();
        $theme = wp_get_theme();
        $data['theme'] = is_object($theme) ? $theme->get('Name') : '';
        if (function_exists('get_theme_mods')) { $data['theme_mods'] = get_theme_mods(); }
        $kadence_lib = get_option('kadence_design_library');
        if ($kadence_lib !== false) { $data['kadence_design_library'] = $kadence_lib; }
        if (function_exists('kadence_design_library_export')) {
            try { $exp = kadence_design_library_export(); $data['kadence_export_raw'] = $exp; } catch (Exception $e) { $data['kadence_export_error'] = $e->getMessage(); }
        }
        $upload = wp_upload_dir();
        $filename = 'kadence-export-mu-' . date('Ymd-His') . '.dat';
        $full = trailingslashit($upload['basedir']) . $filename;
        @file_put_contents($full, wp_json_encode($data, JSON_PRETTY_PRINT));
        set_transient('kadence_mu_export_done', 1, 3600);
    } catch (Throwable $e) {
        // swallow errors to avoid crashing the site
        error_log('kadence-export-mu error: ' . $e->getMessage());
    }
}, 1);
