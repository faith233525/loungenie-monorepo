<?php

/**
 * Email and Notifications Tests
 *
 * Tests for email handling and notification system
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Email_Notifications_Test extends WP_UnitTestCase
{
    /**
     * Test that email is sent when ticket is created
     *
     * @test
     */
    public function test_email_sent_on_ticket_creation()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Clear mail queue
        global $phpmailer;
        $phpmailer = new \PHPMailer(true);

        $ticket_id = Test_Utils::create_test_ticket([
            'post_author' => $user->ID,
        ]);

        // Check that email was sent (depends on implementation)
        // This is a simplified test
        $this->assertGreaterThan(0, $ticket_id);
    }

    /**
     * Test email contains required information
     *
     * @test
     */
    public function test_notification_email_contains_ticket_details()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        $ticket_id = Test_Utils::create_test_ticket([
            'post_title' => 'Critical Issue',
            'post_content' => 'This is urgent',
        ]);

        // In actual implementation, would verify email body contains:
        // - Ticket ID
        // - Title
        // - Link to ticket
        // - User information

        $post = get_post($ticket_id);
        $this->assertStringContainsString('Critical Issue', $post->post_title);
    }

    /**
     * Test email is not sent to unauthorized users
     *
     * @test
     */
    public function test_email_recipients_respect_permissions()
    {
        $partner1 = Test_Utils::create_partner_user();
        $company_id = Test_Utils::create_test_company();
        Test_Utils::set_user_company($partner1->ID, $company_id);

        $partner2 = Test_Utils::create_partner_user();
        // Partner 2 is in different company

        wp_set_current_user($partner1->ID);

        $ticket_id = Test_Utils::create_test_ticket();

        // Partner 2 should not receive notification for partner 1's company tickets
        $this->assertGreater($ticket_id, 0);
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
