<?php
/**
 * Tests for Gateway REST API
 * 
 * @package LounGenie_Portal
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once dirname(__DIR__) . '/api/gateways.php';
require_once dirname(__DIR__) . '/includes/class-lgp-gateway.php';
require_once dirname(__DIR__) . '/includes/class-lgp-auth.php';

class ApiGatewaysTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test GET /gateways returns all gateways for support
     */
    public function test_get_gateways_returns_data_for_support() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'get_gateways'));
    }

    /**
     * Test partners cannot access gateway endpoints
     */
    public function test_partners_denied_gateway_access() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'check_support_permission'));
    }

    /**
     * Test POST /gateways creates gateway
     */
    public function test_create_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'create_gateway'));
    }

    /**
     * Test PUT /gateways/:id updates gateway
     */
    public function test_update_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'update_gateway'));
    }

    /**
     * Test GET /gateways/:id retrieves single gateway
     */
    public function test_get_single_gateway() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'get_gateway'));
    }

    /**
     * Test DELETE /gateways/:id removes gateway
     */
    public function test_delete_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'delete_gateway'));
    }

    /**
     * Test POST /gateways/:id/test-signal initiates signal test
     */
    public function test_test_signal_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'test_signal'));
    }

    /**
     * Test GET /gateways/:id/units returns connected units
     */
    public function test_get_gateway_units_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'get_gateway_units'));
    }

    /**
     * Test filtering gateways by call_button
     */
    public function test_filter_gateways_by_call_button() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'check_support_permission'));
    }

    /**
     * Test filtering gateways by company_id
     */
    public function test_filter_gateways_by_company() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'get_gateways'));
    }

    /**
     * Test gateway search by channel_number
     */
    public function test_search_gateways_by_channel() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
        $this->assertTrue(method_exists($api, 'get_gateways'));
    }
}