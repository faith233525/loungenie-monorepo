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
        // Mock WordPress capability check for support user
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        $api = new LGP_Gateways_API();
        $has_permission = $api->support_only_permission();
        
        $this->assertTrue($has_permission);
    }

    /**
     * Test partners cannot access gateway endpoints
     */
    public function test_partners_denied_gateway_access() {
        // Mock partner user (no manage_options capability)
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);

        $api = new LGP_Gateways_API();
        $has_permission = $api->support_only_permission();

        $this->assertFalse($has_permission);
    }

    /**
     * Test POST /gateways creates gateway
     */
    public function test_create_gateway_endpoint() {
        // Mock support user
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        // Mock request data
        $request_data = [
            'company_id' => 10,
            'channel_number' => 'CH-NEW',
            'gateway_address' => '192.168.1.200',
            'unit_capacity' => 30,
            'call_button' => 0,
            'included_equipment' => 'Router',
            'admin_password' => 'secure_pass'
        ];

        $api = new LGP_Gatepermission check
     */
    public function test_create_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();y
     */
    public function test_update_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        $api = new LGP_Gatewaypermission check
     */
    public function test_get_single_gateway() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();emoves gateway
     */
    public function test_delete_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        $api = new LGP_Gatewaypermission check
     */
    public function test_update_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();t-signal initiates signal test
     */
    public function test_test_signal_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        $api = new LGP_Gateways_Apermission check
     */
    public function test_delete_gateway_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();s returns connected units
     */
    public function test_get_gateway_units_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        $api = new LGP_Gateways_API();permission check
     */
    public function test_test_signal_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();call_button
     */
    public function test_filter_gateways_by_call_button() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\expect('LGP_Auth::get_current_user_role')->andReturn('lgp_support');
permission check
     */
    public function test_get_gateway_units_endpoint() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
     * Test filtering gateways by company_id
     */
    public function test_filter_gateways_by_company() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\expepermission for call_button
     */
    public function test_filter_gateways_by_call_button() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
     * Test gateway search by channel_number
     */
    public function test_search_gateways_by_channel() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\expepermission for company_id
     */
    public function test_filter_gateways_by_company() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();
     */
    public function test_search_gateways_by_channel() {
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        $api = new LGP_Gateways_API();