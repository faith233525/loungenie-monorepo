<?php

/**
 * Ticket Lifecycle Integration Test
 *
 * Tests complete ticket workflow from creation to closure
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Ticket_Lifecycle_Test extends WP_UnitTestCase
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
     * Test complete ticket workflow
     *
     * 1. Partner creates ticket
     * 2. Support views ticket
     * 3. Support updates status
     * 4. Email notification sent
     * 5. Partner receives update
     * 6. Partner adds reply
     * 7. Ticket closed
     *
     * @test
     */
    public function test_complete_ticket_workflow()
    {
        // Step 1: Partner creates ticket
        $partner = Test_Utils::create_partner_user();
        $company_id = Test_Utils::create_test_company();
        Test_Utils::set_user_company($partner->ID, $company_id);

        wp_set_current_user($partner->ID);

        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            '/lgp/v1/tickets',
            [
                'body' => [
                    'title' => 'Broken Door Lock',
                    'content' => 'Unit 101 door lock not working',
                    'priority' => 'high',
                ],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertNotEquals(400, $response->get_status());
        $ticket_data = $response->get_data();
        $ticket_id = $ticket_data['id'] ?? null;

        $this->assertNotNull($ticket_id);

        // Step 2: Support views ticket
        $support = Test_Utils::create_support_user();
        wp_set_current_user($support->ID);

        $response = Test_Utils::make_request('GET', "/lgp/v1/tickets/{$ticket_id}");
        $this->assertEquals(200, $response->get_status());

        // Step 3: Support updates status
        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}",
            [
                'body' => ['status' => 'in_progress'],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );
        $this->assertNotEquals(400, $response->get_status());

        // Step 4: Check audit log for status change
        global $wpdb;
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lgp_audit_logs 
                 WHERE post_id = %d AND action = 'status_change'",
                $ticket_id
            )
        );
        $this->assertGreaterThan(0, count($logs));

        // Step 5: Partner checks ticket update
        wp_set_current_user($partner->ID);
        $response = Test_Utils::make_request('GET', "/lgp/v1/tickets/{$ticket_id}");
        $ticket_data = $response->get_data();
        $this->assertEquals('in_progress', $ticket_data['status']);

        // Step 6: Partner adds reply
        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}/replies",
            [
                'body' => ['content' => 'Any update on the lock?'],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );
        $this->assertEquals(201, $response->get_status());

        // Step 7: Support closes ticket
        wp_set_current_user($support->ID);
        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}",
            [
                'body' => ['status' => 'closed'],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        // Verify final state
        $response = Test_Utils::make_request('GET', "/lgp/v1/tickets/{$ticket_id}");
        $ticket_data = $response->get_data();
        $this->assertEquals('closed', $ticket_data['status']);
    }

    /**
     * Test ticket assignment workflow
     *
     * @test
     */
    public function test_ticket_assignment_workflow()
    {
        // Create ticket
        $partner = Test_Utils::create_partner_user();
        wp_set_current_user($partner->ID);

        $ticket_id = Test_Utils::create_test_ticket();

        // Support assigns ticket to themselves
        $support = Test_Utils::create_support_user();
        wp_set_current_user($support->ID);

        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}",
            [
                'body' => ['assigned_to' => $support->ID],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertNotEquals(400, $response->get_status());
    }

    /**
     * Test ticket priority changes
     *
     * @test
     */
    public function test_ticket_priority_workflow()
    {
        $support = Test_Utils::create_support_user();
        wp_set_current_user($support->ID);

        $ticket_id = Test_Utils::create_test_ticket();

        // Change priority
        $nonce = wp_create_nonce('wp_rest');
        $response = Test_Utils::make_request(
            'POST',
            "/lgp/v1/tickets/{$ticket_id}",
            [
                'body' => ['priority' => 'critical'],
                'headers' => ['X-WP-Nonce' => $nonce],
            ]
        );

        $this->assertNotEquals(400, $response->get_status());
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
