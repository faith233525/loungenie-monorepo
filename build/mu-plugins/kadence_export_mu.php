<?php
/**
 * Loungenie Kadence Export MU-plugin
 * Drops a JSON `.dat` file into the uploads folder when triggered via ?lou_export=1
 */

if ( ! defined( 'WPINC' ) ) {
    // Allow direct load via mu-plugin path bootstrap
}

function lou_kadence_export_mu_run() {
    if ( ! isset( $_GET['lou_export'] ) || $_GET['lou_export'] !== '1' ) {
        return;
    }

    $upload = wp_upload_dir();
    $filename = 'kadence-export-mu-' . date('Ymd-His') . '.dat';
    $full = trailingslashit( $upload['basedir'] ) . $filename;

    $data = array();
    $data['timestamp'] = time();
    $data['site_url'] = get_site_url();
    $data['theme'] = wp_get_theme()->get( 'Name' );
    $data['theme_mods'] = get_theme_mods();

    $kadence_lib = get_option( 'kadence_design_library' );
    if ( $kadence_lib !== false ) {
        $data['kadence_design_library'] = $kadence_lib;
    }

    // Write file to uploads
    @file_put_contents( $full, wp_json_encode( $data ) );
    // Also write a copy to the WP root (ABSPATH) so we can find it when uploads are blocked
    if ( defined( 'ABSPATH' ) ) {
        $root_file = trailingslashit( ABSPATH ) . 'kadence-export-mu-root.dat';
        @file_put_contents( $root_file, wp_json_encode( $data ) );
    }
}

add_action( 'init', 'lou_kadence_export_mu_run' );
