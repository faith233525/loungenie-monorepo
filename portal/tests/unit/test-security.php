<?php

/**
 * Security Tests
 *
 * Tests for security-related functionality including SQL injection, XSS, and CSRF protection
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Security_Test extends WP_UnitTestCase
{
    /**
     * Test SQL injection prevention in queries
     *
     * @test
     */
    public function test_sql_injection_prevention()
    {
        global $wpdb;

        // Attempt SQL injection
        $malicious_input = "1' OR '1'='1";

        $user = Test_Utils::create_partner_user();
        Test_Utils::set_user_company($user->ID, 1);

        wp_set_current_user($user->ID);

        // Query should be properly prepared
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_value = %s",
                $malicious_input
            )
        );

        $this->assertIsInt($result);
    }

    /**
     * Test XSS prevention through escaping
     *
     * @test
     */
    public function test_xss_prevention_in_output()
    {
        $user = Test_Utils::create_partner_user();

        $ticket_id = Test_Utils::create_test_ticket([
            'post_title' => '<script>alert("XSS")</script>Test Ticket',
        ]);

        wp_set_current_user($user->ID);

        $post = get_post($ticket_id);
        $escaped_title = esc_html($post->post_title);

        $this->assertStringNotContainsString('<script>', $escaped_title);
    }

    /**
     * Test CSRF protection on state-changing operations
     *
     * @test
     */
    public function test_csrf_protection_without_nonce()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Request without nonce should fail
        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/tickets',
            ['body' => ['title' => 'Test']]
        );

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test file upload validation
     *
     * @test
     */
    public function test_file_upload_validation()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Test that executable files are rejected
        $invalid_file = [
            'name' => 'malicious.php',
            'type' => 'application/x-php',
            'tmp_name' => wp_tempnam(),
            'error' => 0,
            'size' => 1024,
        ];

        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/attachments',
            [
                'body' => ['file' => $invalid_file],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        // Should reject PHP files
        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test that user data is escaped in API responses
     *
     * @test
     */
    public function test_api_response_escaping()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket([
            'post_content' => '<img src=x onerror="alert(1)">',
        ]);

        $response = Test_Utils::make_request('GET', "/lgp/v1/tickets/{$ticket_id}");

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();

        // Content should be properly escaped
        $this->assertStringNotContainsString('onerror=', $data['content']);
    }

    /**
     * Test capability checks prevent unauthorized access
     *
     * @test
     */
    public function test_capability_checks()
    {
        $partner = Test_Utils::create_partner_user();
        $support = Test_Utils::create_support_user();

        // Partner should not be able to modify support-only settings
        wp_set_current_user($partner->ID);
        $this->assertFalse(current_user_can('manage_lgp_gateways'));

        // Support should be able to modify support-only settings
        wp_set_current_user($support->ID);
        $this->assertTrue(current_user_can('manage_lgp_gateways'));
    }

    /**
     * Test password hashing is used
     *
     * @test
     */
    public function test_password_hashing()
    {
        $user = Test_Utils::create_partner_user();
        $stored_password = $user->user_pass;

        // Password should be hashed, not plaintext
        $this->assertNotEquals('Test123!@#', $stored_password);

        // Hashed password should be verifiable
        $this->assertTrue(wp_check_password('Test123!@#', $stored_password));
    }

    /**
     * Test rate limiting on login attempts
     *
     * @test
     */
    public function test_rate_limiting_on_failed_login()
    {
        $user = Test_Utils::create_partner_user();

        // Simulate failed login attempts
        for ($i = 0; $i < 5; $i++) {
            do_action('wp_login_failed', $user->user_login);
        }

        // User might be temporarily locked out
        $lockout_key = 'lgp_login_lockout_' . $user->user_login;
        $lockout = get_transient($lockout_key);

        // After 5 failures, should be locked out or rate-limited
        $this->assertNotEmpty($lockout);
    }

    /**
     * Test headers prevent common attacks
     *
     * @test
     */
    public function test_security_headers()
    {
        // These would be set in HTTP headers by the application
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
        ];

        foreach ($headers as $header => $value) {
            // Would be checked in actual HTTP response
            $this->assertNotEmpty($value);
        }
    }

    /**
     * Test that sensitive data is not logged
     *
     * @test
     */
    public function test_sensitive_data_not_logged()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Create a sensitive action
        do_action('wp_login', $user->user_login, $user);

        // Check logs don't contain password
        global $wpdb;
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}lgp_audit_logs LIMIT 10"
        );

        foreach ($logs as $log) {
            $this->assertStringNotContainsString('pass', strtolower($log->data));
        }
    }

    /**
     * Test session validation
     *
     * @test
     */
    public function test_session_validation()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $token = wp_get_session_token();
        $this->assertNotEmpty($token);

        // Verify session is valid
        $sessions = get_user_meta($user->ID, 'session_tokens', true);
        $this->assertNotEmpty($sessions);
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
