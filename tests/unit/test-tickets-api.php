<?php

/**
 * Tickets API Tests
 *
 * Tests for tickets REST API endpoints
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use LounGenie\Portal\LGP_Tickets_API;
use WP_UnitTestCase;

class LGP_Tickets_API_Test extends WP_UnitTestCase
{
    /**
     * Setup test fixtures
     */
    public function setUp(): void
    {
        parent::setUp();

        // Initialize REST API
        rest_api_init();
    }

    /**
     * Test getting list of tickets for support
     *
     * @test
     */
    public function test_support_can_list_all_tickets()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket1 = Test_Utils::create_test_ticket(['post_title' => 'Ticket 1']);
        $ticket2 = Test_Utils::create_test_ticket(['post_title' => 'Ticket 2']);

        $response = Test_Utils::make_request('GET', '/lgp/v1/tickets');

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(2, $response->get_data());
    }

    /**
     * Test partner can only list own tickets
     *
     * @test
     */
    public function test_partner_can_only_list_own_tickets()
    {
        $user1 = Test_Utils::create_partner_user();
        $user2 = Test_Utils::create_partner_user();

        Test_Utils::create_test_ticket(['post_author' => $user1->ID]);
        Test_Utils::create_test_ticket(['post_author' => $user2->ID]);

        wp_set_current_user($user1->ID);
        $response = Test_Utils::make_request('GET', '/lgp/v1/tickets');

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertCount(1, $data);
    }

    /**
     * Test creating ticket with valid data
     *
     * @test
     */
    public function test_create_ticket_with_valid_data()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/tickets',
            [
                'body' => [
                    'title'   => 'Test Ticket',
                    'content' => 'Test content',
                    'nonce'   => $nonce,
                ],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertNotEquals(400, $response->get_status());
    }

    /**
     * Test creating ticket with missing required fields
     *
     * @test
     */
    public function test_create_ticket_missing_required_fields()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/tickets',
            [
                'body' => ['title' => ''],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test updating ticket
     *
     * @test
     */
    public function test_update_ticket_status()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket();
        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}",
            [
                'body' => ['status' => 'closed'],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test deleting ticket
     *
     * @test
     */
    public function test_delete_ticket()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket();
        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'DELETE',
            "/lgp/v1/tickets/{$ticket_id}",
            ['headers' => ['X-WP-Nonce' => $nonce]]
        );

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test permission callback for listing tickets
     *
     * @test
     */
    public function test_permission_callback_denies_anonymous_users()
    {
        wp_set_current_user(0);

        $response = Test_Utils::make_request('GET', '/lgp/v1/tickets');

        $this->assertEquals(401, $response->get_status());
    }

    /**
     * Test CSRF protection on mutations
     *
     * @test
     */
    public function test_csrf_protection_on_create()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Request without nonce
        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/tickets',
            ['body' => ['title' => 'Test Ticket']]
        );

        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test adding reply to ticket
     *
     * @test
     */
    public function test_add_reply_to_ticket()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket();
        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}/replies",
            [
                'body' => [
                    'content' => 'Reply content',
                    'nonce'   => $nonce,
                ],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertEquals(201, $response->get_status());
    }

    /**
     * Test input validation on replies
     *
     * @test
     */
    public function test_reply_content_validation()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket();
        $nonce = wp_create_nonce('wp_rest');

        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}/replies",
            [
                'body' => ['content' => ''],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test filtering tickets by status
     *
     * @test
     */
    public function test_list_tickets_filter_by_status()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        Test_Utils::create_test_ticket(['post_status' => 'open']);
        Test_Utils::create_test_ticket(['post_status' => 'closed']);

        $response = Test_Utils::make_request(
            'GET',
            '/lgp/v1/tickets',
            ['query' => ['status' => 'open']]
        );

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test sorting tickets
     *
     * @test
     */
    public function test_list_tickets_sorted_by_date()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        $ticket1 = Test_Utils::create_test_ticket();
        sleep(1);
        $ticket2 = Test_Utils::create_test_ticket();

        $response = Test_Utils::make_request(
            'GET',
            '/lgp/v1/tickets',
            ['query' => ['orderby' => 'date', 'order' => 'desc']]
        );

        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test pagination
     *
     * @test
     */
    public function test_list_tickets_with_pagination()
    {
        $user = Test_Utils::create_support_user();
        wp_set_current_user($user->ID);

        for ($i = 0; $i < 25; $i++) {
            Test_Utils::create_test_ticket();
        }

        $response = Test_Utils::make_request(
            'GET',
            '/lgp/v1/tickets',
            ['query' => ['per_page' => 10, 'page' => 1]]
        );

        $this->assertEquals(200, $response->get_status());
        $headers = $response->get_headers();
        $this->assertNotEmpty($headers['X-WP-TotalPages']);
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
