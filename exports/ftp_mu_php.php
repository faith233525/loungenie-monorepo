<?php
/*
Plugin Name: Loungenie Home (MU)
Description: Auto-registers Loungenie home HTML and ensures it's set as the static front page. (Generated)
Version: 0.1
*/

defined( 'ABSPATH' ) || exit;

add_action( 'init', function() {
    $dir = dirname( __FILE__ );
    $html_file = $dir . '/block.html';
    $html = '';
    if ( file_exists( $html_file ) ) {
        $html = file_get_contents( $html_file );
    }

    if ( function_exists( 'register_block_type' ) ) {
        register_block_type( 'custom/loungenie-home', array( 'render_callback' => function( $attributes ) use ( $html ) {
            return $html;
        } ) );
    }

    // Ensure a single auto page exists and is published
    $slug = 'loungenie-home-auto';
    $title = 'Loungenie Home (auto)';
    $page = get_page_by_path( $slug );
    if ( ! $page ) {
        $page_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $html,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
    } else {
        $page_id = $page->ID;
        // Update content in case the HTML changed
        wp_update_post( array( 'ID' => $page_id, 'post_content' => $html ) );
    }

    if ( ! is_wp_error( $page_id ) && $page_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', (int) $page_id );
    }
} );

?>
