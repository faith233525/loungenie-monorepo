<?php
/*
Plugin Name: Loungenie Home
Description: Auto-generated plugin that registers a server-rendered block outputting provided HTML.
Version: 0.1
Author: Copilot
*/

function {loungenie-home}_render_block(  ) {
     = plugin_dir_path( __FILE__ ) . 'block.html';
    if ( file_exists(  ) ) {
        return file_get_contents(  );
    }
    return '';
}

function {loungenie-home}_register_block() {
    register_block_type( 'custom/{loungenie-home}', array( 'render_callback' => '{loungenie-home}_render_block' ) );
}
add_action( 'init', '{loungenie-home}_register_block' );

?>
