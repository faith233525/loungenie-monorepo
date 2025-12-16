<?php
/**
 * Tests for LGP_Gateway class
 * 
 * @package LounGenie_Portal
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once dirname(__DIR__) . '/includes/class-lgp-gateway.php';
require_once dirname(__DIR__) . '/includes/class-lgp-auth.php';
require_once dirname(__DIR__) . '/includes/class-lgp-logger.php';

class GatewayTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Mock global $wpdb
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", str_replace('%d', '%d', $query)), $args);
        });

        // Mock LGP_Logger::log method (stub to prevent errors)
        if (class_exists('LGP_Logger') && !method_exists('LGP_Logger', 'log')) {
            eval('namespace { class_alias("LGP_Logger", "LGP_Logger_Original"); }');
        }
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test that support users can get all gateways
     */
    public function test_support_can_get_all_gateways() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_support']
        ]);

        // Mock database query
        $wpdb->shouldReceive('get_results')->once()->andReturn([
            (object)[
                'id' => 1,
                'company_id' => 10,
                'channel_number' => 'CH-001',
                'gateway_address' => '192.168.1.100',
                'unit_capacity' => 50,
                'call_button' => 1,
                'included_equipment' => 'Router,Switch',
                'admin_password' => 'encrypted_pass',
                'created_at' => '2025-12-01 10:00:00',
                'updated_at' => '2025-12-01 10:00:00',
                'company_name' => 'Acme Corp'
            ]
        ]);

        $result = LGP_Gateway::get_all();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('CH-001', $result[0]->channel_number);
        $this->assertEquals('Acme Corp', $result[0]->company_name);
    }

    /**
     * Test that partners cannot access gateway management
     */
    public function test_partner_cannot_access_gateways() {
        // Mock WordPress user functions for partner role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_partner']
        ]);

        $result = LGP_Gateway::get_all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test gateway filtering by call_button
     */
    public function test_filter_gateways_by_call_button() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_support']
        ]);

        // Mock esc_like for search parameter
        $wpdb->shouldReceive('esc_like')->andReturnUsing(function($str) { return $str; });

        // Mock database query with WHERE clause
        $wpdb->shouldReceive('get_results')->once()->with(
            Mockery::pattern("/WHERE.*call_button = 1/")
        )->andReturn([
            (object)[
                'id' => 1,
                'call_button' => 1,
                'channel_number' => 'CH-001'
            ]
        ]);

        $result = LGP_Gateway::get_all(['call_button' => 1]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->call_button);
    }

    /**
     * Test gateway creation works for support
     */
    public function test_create_gateway_works_for_support() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'ID' => 1,
            'roles' => ['lgp_support'],
            'user_email' => 'support@example.com'
        ]);
        Functions\expect('get_current_user_id')->andReturn(1);

        // Mock WordPress sanitize functions
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('absint')->returnArg();

        // Mock database insert
        $wpdb->shouldReceive('insert')->once()->andReturn(1);
        $wpdb->insert_id = 123;

        // Mock current_time for timestamps
        Functions\expect('current_time')->with('mysql')->andReturn('2025-12-16 10:00:00');

        $data = [
            'company_id' => 10,
            'channel_number' => 'CH-NEW',
            'gateway_address' => '192.168.1.200',
            'unit_capacity' => 30,
            'call_button' => 0,
            'included_equipment' => 'Router',
            'admin_password' => 'secure_pass'
        ];

        $result = LGP_Gateway::create($data);

        $this->assertIsInt($result);
        $this->assertEquals(123, $result);
    }

    /**
     * Test gateway update requires support role
     */
    public function test_update_gateway_requires_support() {
        // Mock WordPress user functions for partner role (no access)
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_partner']
        ]);

        $result = LGP_Gateway::update(1, ['channel_number' => 'CH-UPDATED']);

        $this->assertFalse($result);
    }

    /**
     * Test gateway delete works for support
     */
    public function test_delete_gateway_works_for_support() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'ID' => 1,
            'roles' => ['lgp_support'],
            'user_email' => 'support@example.com'
        ]);
        Functions\expect('get_current_user_id')->andReturn(1);

        // Mock get gateway data before delete
        $wpdb->shouldReceive('get_row')->once()->andReturn((object)[
            'id' => 1,
            'company_id' => 10,
            'channel_number' => 'CH-001'
        ]);

        // Mock database delete
        $wpdb->shouldReceive('delete')->once()->andReturn(1);

        // Mock current_time for timestamps
        Functions\expect('current_time')->with('mysql', true)->andReturn('2025-12-16 10:00:00');
        $wpdb->shouldReceive('insert')->andReturn(1);

        $result = LGP_Gateway::delete(1);

        $this->assertTrue($result);
    }

    /**
     * Test test_signal action works for support
     */
    public function test_signal_test_works_for_support() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'ID' => 1,
            'roles' => ['lgp_support'],
            'user_email' => 'support@example.com'
        ]);
        Functions\expect('get_current_user_id')->andReturn(1);

        // Mock get gateway data
        $wpdb->shouldReceive('get_row')->once()->andReturn((object)[
            'id' => 1,
            'company_id' => 10,
            'channel_number' => 'CH-001',
            'gateway_address' => '192.168.1.100',
            'call_button' => 1
        ]);

        // Mock current_time for timestamps
        Functions\expect('current_time')->with('mysql', true)->andReturn('2025-12-16 10:00:00');
        $wpdb->shouldReceive('insert')->andReturn(1);

        // Mock esc_html for output
        Functions\when('esc_html')->returnArg();

        $result = LGP_Gateway::test_signal(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Signal test initiated', $result['message']);
    }

    /**
     * Test get_connected_units returns proper structure
     */
    public function test_get_connected_units_returns_units() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_support']
        ]);

        // Mock get_row to return gateway data (required by get_connected_units)
        $wpdb->shouldReceive('get_row')->once()->andReturn((object)[
            'id' => 1,
            'company_id' => 10
        ]);

        // Mock database query
        $wpdb->shouldReceive('get_results')->once()->andReturn([
            (object)[
                'id' => 1,
                'unit_number' => 'U-101',
                'location' => 'Building A',
                'status' => 'active'
            ],
            (object)[
                'id' => 2,
                'unit_number' => 'U-102',
                'location' => 'Building A',
                'status' => 'active'
            ]
        ]);

        $result = LGP_Gateway::get_connected_units(1);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('U-101', $result[0]->unit_number);
    }

    /**
     * Test highlight rows with call button enabled
     */
    public function test_gateways_with_call_button_identified() {
        global $wpdb;

        // Mock WordPress user functions for support role
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturn((object)[
            'roles' => ['lgp_support']
        ]);

        // Mock database query - mix of call button enabled/disabled
        $wpdb->shouldReceive('get_results')->once()->andReturn([
            (object)['id' => 1, 'call_button' => 1, 'channel_number' => 'CH-001'],
            (object)['id' => 2, 'call_button' => 0, 'channel_number' => 'CH-002'],
            (object)['id' => 3, 'call_button' => 1, 'channel_number' => 'CH-003']
        ]);

        $result = LGP_Gateway::get_all();

        $this->assertCount(3, $result);
        
        // Count gateways with call button
        $with_call_button = array_filter($result, function($g) {
            return $g->call_button == 1;
        });
        
        $this->assertCount(2, $with_call_button);
    }
}
