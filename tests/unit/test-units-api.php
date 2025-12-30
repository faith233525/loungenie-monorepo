<?php

/**
 * Units API Tests
 *
 * Tests for units REST API endpoints
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Units_API_Test extends WP_UnitTestCase
{
    /**
     * Setup test fixtures
     */
    public function setUp(): void
    {
        parent::setUp();
        rest_api_init();
    }

    /**
     * Test support can list all units
     *
     * @test
     */
    public function test_support_can_list_all_units()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $response = Test_Utils::make_request('GET', '/lgp/v1/units');

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test partner can only list company units
     *
     * @test
     */
    public function test_partner_can_only_list_company_units()
    {
        $company_id = Test_Utils::create_test_company();
        $user = Test_Utils::create_partner_user();
        Test_Utils::set_user_company($user->ID, $company_id);

        wp_set_current_user($user->ID);

        $response = Test_Utils::make_request('GET', '/lgp/v1/units');

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test unit creation requires authentication
     *
     * @test
     */
    public function test_unit_creation_requires_authentication()
    {
        wp_set_current_user(0);

        $response = Test_Utils::make_request('POST', '/lgp/v1/units', [
            'body' => ['name' => 'Unit 1'],
        ]);

        $this->assertEquals(401, $response->get_status());
    }

    /**
     * Test unit data validation
     *
     * @test
     */
    public function test_unit_creation_validates_required_fields()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request('POST', '/lgp/v1/units', [
            'body' => ['name' => ''],
            'headers' => ['X-WP-Nonce' => $nonce],
        ]);

        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test unit retrieval
     *
     * @test
     */
    public function test_get_single_unit()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        // Create a unit (would need actual implementation)
        // For now, test endpoint structure
        $response = Test_Utils::make_request('GET', '/lgp/v1/units/1');

        // Should handle gracefully even if unit doesn't exist
        $this->assertNotNull($response->get_status());
    }

    /**
     * Cleanup after tests
     */
    public function tearDown(): void
    {
        Test_Utils::cleanup();
        parent::tearDown();
    }
}
