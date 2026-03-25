<?php

/**
 * Plugin Name: LounGenie Block Patterns
 * Description: Registers reusable Gutenberg block patterns and enqueues LounGenie blue theme styles for editor and front-end.
 * Version: 0.1
 * Author: Copilot
 */

defined('ABSPATH') || exit;

function lgbp_register_styles()
{
    $handle = 'lg-block-patterns-style';
    $url = plugin_dir_url(__FILE__) . 'assets/css/style.css';
    $path = plugin_dir_path(__FILE__) . 'assets/css/style.css';
    if (file_exists($path)) {
        wp_register_style($handle, $url, array(), filemtime($path));
        wp_enqueue_style($handle);
        // Make styles available in editor as well
        add_theme_support('editor-styles');
        add_editor_style($url);
    }
}
add_action('init', 'lgbp_register_styles');

function lgbp_register_patterns()
{
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern(
        'lg/hero',
        array(
            'title'       => __('LounGenie Hero', 'lg-block-patterns'),
            'categories'  => array('lg-patterns', 'hero'),
            'content'     => "<!-- wp:group {\"className\":\"lg9-hero lg9-shell\"} --><div class=\"wp-block-group lg9-hero lg9-shell\"><!-- wp:heading {\"level\":1,\"className\":\"lg9-title-xl\"} --><h1 class=\"lg9-title-xl\">Grow Premium Seating Revenue Across Resorts and Waterparks</h1><!-- /wp:heading --><!-- wp:paragraph {\"className\":\"lg9-copy\"} --><p class=\"lg9-copy\">Built for operations and commercial decision makers — improve guest flow, speed poolside service, and increase revenue.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class=\"wp-block-buttons\"><!-- wp:button {\"className\":\"lg9-btn-primary\"} --><div class=\"wp-block-button lg9-btn-primary\"><a class=\"wp-block-button__link\">Book a Property Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->",
        )
    );

    register_block_pattern(
        'lg/cta',
        array(
            'title'      => __('LounGenie Call To Action', 'lg-block-patterns'),
            'categories' => array('lg-patterns', 'cta'),
            'content'    => "<!-- wp:group {\"className\":\"lg9-cta lg9-shell\"} --><div class=\"wp-block-group lg9-cta lg9-shell\"><!-- wp:heading {\"level\":2,\"className\":\"lg9-title-md\"} --><h2 class=\"lg9-title-md\">Ready to upgrade premium seating performance?</h2><!-- /wp:heading --><!-- wp:buttons --><div class=\"wp-block-buttons\"><!-- wp:button {\"className\":\"lg9-btn-primary\"} --><div class=\"wp-block-button lg9-btn-primary\"><a class=\"wp-block-button__link\">Contact Sales</a></div><!-- /wp:button --><!-- wp:button {\"className\":\"lg9-btn-secondary\"} --><div class=\"wp-block-button lg9-btn-secondary\"><a class=\"wp-block-button__link\">Learn More</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->",
        )
    );

    register_block_pattern(
        'lg/card-grid',
        array(
            'title'      => __('LounGenie Card Grid', 'lg-block-patterns'),
            'categories' => array('lg-patterns', 'cards'),
            'content'    => "<!-- wp:columns {\"className\":\"lg9-grid-3\"} --><div class=\"wp-block-columns lg9-grid-3\"><!-- wp:column --><div class=\"wp-block-column\"><!-- wp:image --><figure class=\"wp-block-image\"><img src=\"https://via.placeholder.com/480x320\" alt=\"\"/></figure><!-- /wp:image --><!-- wp:heading {\"level\":3\"} --><h3>Feature Title</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Short description of the feature or benefit.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class=\"wp-block-column\"><!-- wp:image --><figure class=\"wp-block-image\"><img src=\"https://via.placeholder.com/480x320\" alt=\"\"/></figure><!-- /wp:image --><!-- wp:heading {\"level\":3\"} --><h3>Feature Title</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Short description of the feature or benefit.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class=\"wp-block-column\"><!-- wp:image --><figure class=\"wp-block-image\"><img src=\"https://via.placeholder.com/480x320\" alt=\"\"/></figure><!-- /wp:image --><!-- wp:heading {\"level\":3\"} --><h3>Feature Title</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Short description of the feature or benefit.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->",
        )
    );

    register_block_pattern(
        'lg/gallery-mosaic',
        array(
            'title'      => __('LounGenie Gallery Mosaic', 'lg-block-patterns'),
            'categories' => array('lg-patterns', 'gallery'),
            'content'    => "<!-- wp:gallery {\"columns\":3,\"linkTo\":\"none\"} --><figure class=\"wp-block-gallery columns-3\"><ul class=\"blocks-gallery-grid\"><li class=\"blocks-gallery-item\"><figure><img src=\"https://via.placeholder.com/640x480\"/></figure></li><li class=\"blocks-gallery-item\"><figure><img src=\"https://via.placeholder.com/640x480\"/></figure></li><li class=\"blocks-gallery-item\"><figure><img src=\"https://via.placeholder.com/640x480\"/></figure></li></ul></figure><!-- /wp:gallery -->",
        )
    );
}
add_action('init', 'lgbp_register_patterns');

function lgbp_register_pattern_category()
{
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('lg-patterns', array('label' => __('LounGenie Patterns', 'lg-block-patterns')));
    }
}
add_action('init', 'lgbp_register_pattern_category');
