<?php

/**
 * Authentication Tests
 *
 * Tests for authentication and authorization logic
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use LounGenie\Portal\LGP_Auth;
use WP_UnitTestCase;

class LGP_Auth_Test extends WP_UnitTestCase
{
    /**
     * Test that support users are correctly identified
     *
     * @test
     */
    public function test_is_support_returns_true_for_support_users()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(LGP_Auth::is_support());
    }

    /**
     * Test that support users are not identified as partners
     *
     * @test
     */
    public function test_is_support_returns_false_for_non_support_users()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $this->assertFalse(LGP_Auth::is_support());
    }

    /**
     * Test that partner users are correctly identified
     *
     * @test
     */
    public function test_is_partner_returns_true_for_partner_users()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(LGP_Auth::is_partner());
    }

    /**
     * Test that partners are not identified as support
     *
     * @test
     */
    public function test_is_partner_returns_false_for_non_partner_users()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $this->assertFalse(LGP_Auth::is_partner());
    }

    /**
     * Test retrieving user's company ID
     *
     * @test
     */
    public function test_get_user_company_id_returns_correct_id()
    {
        $company_id = Test_Utils::create_test_company();
        $user = Test_Utils::create_partner_user();
        Test_Utils::set_user_company($user->ID, $company_id);

        wp_set_current_user($user->ID);

        $result = LGP_Auth::get_user_company_id();
        $this->assertEquals($company_id, $result);
    }

    /**
     * Test get_user_company_id returns 0 for users without company
     *
     * @test
     */
    public function test_get_user_company_id_returns_zero_for_users_without_company()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $result = LGP_Auth::get_user_company_id();
        $this->assertEquals(0, $result);
    }

    /**
     * Test that users without role are not authenticated
     *
     * @test
     */
    public function test_is_authenticated_returns_false_for_anonymous_users()
    {
        wp_set_current_user(0);

        $this->assertFalse(LGP_Auth::is_authenticated());
    }

    /**
     * Test that logged-in users are authenticated
     *
     * @test
     */
    public function test_is_authenticated_returns_true_for_logged_in_users()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(LGP_Auth::is_authenticated());
    }

    /**
     * Test redirect_after_login sends partners to /portal
     *
     * @test
     */
    public function test_redirect_after_login_goes_to_portal_for_partners()
    {
        $user = Test_Utils::create_partner_user();

        $redirect = LGP_Auth::redirect_after_login('', '', $user);

        $this->assertStringContainsString('/portal', $redirect);
    }

    /**
     * Test redirect_after_login sends support to /portal
     *
     * @test
     */
    public function test_redirect_after_login_goes_to_portal_for_support()
    {
        $user = Test_Utils::create_support_user();

        $redirect = LGP_Auth::redirect_after_login('', '', $user);

        $this->assertStringContainsString('/portal', $redirect);
    }

    /**
     * Test audit logging on login
     *
     * @test
     */
    public function test_login_success_is_logged()
    {
        global $wpdb;

        $user = Test_Utils::create_partner_user();

        do_action('wp_login', $user->user_login, $user);

        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}lgp_audit_logs 
             WHERE user_id = {$user->ID} AND action = 'user_login'"
        );

        $this->assertGreaterThan(0, count($logs));
    }

    /**
     * Test that session is created on login
     *
     * @test
     */
    public function test_session_created_on_login()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $session_token = wp_get_session_token();

        $this->assertNotEmpty($session_token);
    }

    /**
     * Test capability check for support
     *
     * @test
     */
    public function test_support_user_has_ticket_management_capability()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(current_user_can('manage_lgp_tickets'));
    }

    /**
     * Test capability check for partner
     *
     * @test
     */
    public function test_partner_user_has_view_own_tickets_capability()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $this->assertTrue(current_user_can('view_lgp_own_tickets'));
    }

    /**
     * Test that nonces are validated correctly
     *
     * @test
     */
    public function test_nonce_verification()
    {
        $nonce = wp_create_nonce('test_action');

        $this->assertTrue(wp_verify_nonce($nonce, 'test_action'));
    }

    /**
     * Test that invalid nonces are rejected
     *
     * @test
     */
    public function test_invalid_nonce_is_rejected()
    {
        $this->assertFalse(wp_verify_nonce('invalid_nonce', 'test_action'));
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
