<?php

/**
 * Register LounGenie block patterns for Kadence
 * Drop this file into your `loungenie-block-patterns` plugin main file
 * or include it via your plugin's loader.
 */

if (! function_exists('loungenie_register_block_patterns')) {
    function loungenie_register_block_patterns()
    {
        if (function_exists('register_block_pattern')) {
            // Amenity Grid pattern
            register_block_pattern(
                'loungenie/amenity-grid',
                array(
                    'title'       => 'LounGenie — Amenity Grid',
                    'categories'  => array('loungenie', 'kadence'),
                    'content'     => "<!-- wp:kadence/rowlayout {\"uniqueID\":\"hero_amenity\",\"background\":\"#003366\",\"background2\":\"#0073e6\",\"backgroundType\":\"gradient\",\"backgroundGradientAngle\":135,\"paddingTop\":120,\"paddingBottom\":120} -->\n<div class=\"kt-row-column-wrap\">\n  <div class=\"kt-inside-inner-col\">\n    <!-- wp:heading {\"level\":1} --><h1 style=\"color:#ffffff\">LounGenie™ — Hospitality Technology for Premium Seating</h1><!-- /wp:heading -->\n    <!-- wp:group {\"className\":\"glass-card\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"40px\",\"bottom\":\"40px\",\"left\":\"30px\",\"right\":\"30px\"}},\"color\":{\"background\":\"rgba(255,255,255,0.16)\"}}} -->\n    <div class=\"glass-card\" style=\"backdrop-filter: blur(15px); border-radius:12px;\">\n      <!-- wp:kadence/rowlayout {\"uniqueID\":\"amenity_asym\",\"columns\":\"2\",\"columnControls\":{\"widths\":[\"60%\",\"40%\"]}} -->\n      <div class=\"kt-row-column-wrap\">\n        <div class=\"kt-inside-inner-col\">\n          <!-- wp:heading {\"level\":2,\"textAlign\":\"left\",\"style\":{\"typography\":{\"fontWeight\":\"700\",\"fontSize\":36}}} --><h2 class=\"has-text-align-left\" style=\"color:#ffffff;font-weight:700;font-size:36px\">Smart Cabana Features</h2><!-- /wp:heading -->\n          <!-- wp:paragraph {\"align\":\"left\",\"style\":{\"typography\":{\"fontSize\":18},\"color\":{\"text\":\"#e0e0e0\"}}} --><p class=\"has-text-align-left\" style=\"color:#e0e0e0;font-size:18px\">Bold Maximalism meets practical poolside tech — QR ordering, waterproof safe, and solar USB charging.</p><!-- /wp:paragraph -->\n\n          <!-- wp:kadence/rowlayout {\"uniqueID\":\"grid_1\",\"columns\":\"3\",\"columnControls\":{\"widths\":[\"33%\",\"33%\",\"34%\"]}} -->\n          <div class=\"kt-row-column-wrap\" style=\"margin-top:24px\">\n            <div class=\"kt-inside-inner-col\">\n              <div class=\"amenity-card\">\n                <img src=\"/wp-content/uploads/icons/qr-ordering.svg\" alt=\"QR Ordering\" style=\"height:56px;margin-bottom:12px\">\n                <h4 style=\"color:#ffffff;font-weight:700\">QR Ordering</h4>\n                <p style=\"color:#e0e0e0\">Scan, order, and print — no POS integration needed.</p>\n              </div>\n              <div class=\"amenity-card\">\n                <img src=\"/wp-content/uploads/icons/waterproof-safe.svg\" alt=\"Secure Storage\" style=\"height:56px;margin-bottom:12px\">\n                <h4 style=\"color:#ffffff;font-weight:700\">Secure Storage</h4>\n                <p style=\"color:#e0e0e0\">Waterproof safe with keypad — secure and accessible.</p>\n              </div>\n              <div class=\"amenity-card\">\n                <img src=\"/wp-content/uploads/icons/usb-charge.svg\" alt=\"USB Charging\" style=\"height:56px;margin-bottom:12px\">\n                <h4 style=\"color:#ffffff;font-weight:700\">USB Charging</h4>\n                <p style=\"color:#e0e0e0\">Solar-powered USB ports for guest devices.</p>\n              </div>\n            </div>\n          </div>\n          <!-- /wp:kadence/rowlayout -->\n        </div>\n      </div>\n      <!-- /wp:kadence/rowlayout -->\n    </div>\n    <!-- /wp:group -->\n  </div>\n</div>\n<!-- /wp:kadence/rowlayout -->"
                )
            );

            // Pricing table pattern (example)
            register_block_pattern(
                'loungenie/pricing-table',
                array(
                    'title'      => 'LounGenie — Pricing Table',
                    'categories' => array('loungenie', 'pricing'),
                    'content'    => "<!-- wp:kadence/rowlayout {\"background\":\"#ffffff\",\"paddingTop\":60,\"paddingBottom\":60} --><div class=\"kt-row-column-wrap\"><div class=\"kt-inside-inner-col\"><!-- wp:heading {\"level\":2\"} --><h2 style=\"color:#003366\">Pricing</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Three tiers: Classic, Service+, 2.0 — each tier cumulative.</p><!-- /wp:paragraph --></div></div><!-- /wp:kadence/rowlayout -->"
                )
            );
        }
    }
    add_action('init', 'loungenie_register_block_patterns');
}
