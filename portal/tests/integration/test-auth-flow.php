<?php

/**
 * Authentication Flow Integration Test
 *
 * Tests complete authentication workflows
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Auth_Flow_Test extends WP_UnitTestCase
{
    /**
     * Test partner login flow
     *
     * @test
     */
    public function test_partner_login_flow()
    {
        $user = Test_Utils::create_partner_user();

        // Simulate login
        wp_set_current_user($user->ID);

        $this->assertTrue(is_user_logged_in());
        $this->assertTrue(LGP_Auth::is_partner());

        // Check session created
        $session_token = wp_get_session_token();
        $this->assertNotEmpty($session_token);
    }

    /**
     * Test support login flow
     *
     * @test
     */
    public function test_support_login_flow()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(is_user_logged_in());
        $this->assertTrue(LGP_Auth::is_support());
    }

    /**
     * Test logout flow
     *
     * @test
     */
    public function test_logout_flow()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);
        $this->assertTrue(is_user_logged_in());

        // Simulate logout
        wp_logout();

        $this->assertFalse(is_user_logged_in());
    }

    /**
     * Test session persistence across requests
     *
     * @test
     */
    public function test_session_persistence()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $company_id = Test_Utils::create_test_company();
        Test_Utils::set_user_company($user->ID, $company_id);

        // Verify data persists
        $stored_company = get_user_meta($user->ID, 'company_id', true);
        $this->assertEquals($company_id, $stored_company);
    }

    /**
     * Test permission cascade
     *
     * @test
     */
    public function test_permission_cascade()
    {
        $partner = Test_Utils::create_partner_user();
        wp_set_current_user($partner->ID);

        // Partner should have basic capabilities
        $this->assertTrue(current_user_can('read_lgp'));
        $this->assertFalse(current_user_can('manage_lgp_settings'));

        // Support should have more capabilities
        $support = Test_Utils::create_support_user();
        wp_set_current_user($support->ID);

        $this->assertTrue(current_user_can('read_lgp'));
        $this->assertTrue(current_user_can('manage_lgp_settings'));
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
