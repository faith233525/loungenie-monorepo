<?php

use Brain\Monkey\Functions;

if ( ! class_exists( 'LGP_Auth' ) ) {
    class LGP_Auth {
        public static $support = true;
        public static function is_support() { return self::$support; }
        public static function is_partner() { return ! self::$support; }
    }
}

require_once __DIR__ . '/../includes/class-lgp-geocode.php';

class LGPGeocodeTest extends WPTestCase {
    public function test_support_user_gets_markers_and_caches_coordinates() {
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public function get_results( $sql ) {
                return array(
                    (object) array(
                        'id' => 1,
                        'name' => 'Acme Pools',
                        'address' => '123 Main St',
                        'state' => 'CA',
                        'venue_type' => 'Resort',
                    ),
                );
            }
        };

        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)['ID' => 1, 'roles' => ['lgp_support']]);

        Functions\when( 'add_query_arg' )->alias( function( $args, $url ) {
            return $url . '?' . http_build_query( $args );
        });
        $fake_response = array( 'body' => json_encode( array( array( 'lat' => '10.1', 'lon' => '20.2' ) ) ) );
        Functions\when( 'wp_remote_get' )->justReturn( $fake_response );
        Functions\when( 'wp_remote_retrieve_body' )->alias( function( $resp ) { return $resp['body']; } );
        Functions\when( 'is_wp_error' )->justReturn( false );
        Functions\when( 'current_time' )->justReturn( '2024-01-01 00:00:00' );

        Functions\expect( 'get_option' )->once()->with( 'lgp_geocode_1' )->andReturn( null );
        Functions\expect( 'update_option' )->once()->with(
            'lgp_geocode_1',
            array(
                'lat' => 10.1,
                'lng' => 20.2,
                'cached_at' => '2024-01-01 00:00:00',
            ),
            false
        )->andReturn( true );

        $markers = LGP_Geocode::get_company_markers_for_map();

        $this->assertCount( 1, $markers );
        $this->assertSame( 10.1, $markers[0]['lat'] );
        $this->assertSame( 20.2, $markers[0]['lng'] );
        $this->assertSame( 'Acme Pools', $markers[0]['name'] );
        $this->assertSame( 'Resort', $markers[0]['type'] );
    }

    public function test_partner_users_do_not_receive_markers() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)['ID' => 1, 'roles' => ['lgp_partner']]);
        $this->assertSame( array(), LGP_Geocode::get_company_markers_for_map() );
    }
}
