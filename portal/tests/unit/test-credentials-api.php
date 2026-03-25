<?php

/**
 * Credentials API Tests
 *
 * Tests for credentials management REST API endpoints
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Credentials_API_Test extends WP_UnitTestCase
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
     * Test that credentials list requires support role
     *
     * @test
     */
    public function test_credentials_list_requires_support_role()
    {
        $partner = Test_Utils::create_partner_user();
        wp_set_current_user($partner->ID);

        $response = Test_Utils::make_request('GET', '/lgp/v1/credentials');

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test support can list credentials
     *
     * @test
     */
    public function test_support_can_list_credentials()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $response = Test_Utils::make_request('GET', '/lgp/v1/credentials');

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test credentials are not returned in plaintext
     *
     * @test
     */
    public function test_credentials_are_masked()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $response = Test_Utils::make_request('GET', '/lgp/v1/credentials');

        if ($response->get_status() === 200) {
            $data = $response->get_data();

            // Credentials should be masked or encrypted
            foreach ((array) $data as $credential) {
                if (isset($credential['secret'])) {
                    // Should not contain the actual secret
                    $this->assertStringNotContainsString('api_key_', $credential['secret']);
                }
            }
        }
    }

    /**
     * Test credential creation with validation
     *
     * @test
     */
    public function test_create_credential_with_validation()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/credentials',
            [
                'body' => [
                    'name' => 'Test Credential',
                    'type' => 'api_key',
                    'value' => 'test_key_value',
                ],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertNotEquals(400, $response->get_status());
    }

    /**
     * Test credential deletion requires authentication
     *
     * @test
     */
    public function test_delete_credential_requires_auth()
    {
        wp_set_current_user(0);

        $response = Test_Utils::make_request('DELETE', '/lgp/v1/credentials/1');

        $this->assertEquals(401, $response->get_status());
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
