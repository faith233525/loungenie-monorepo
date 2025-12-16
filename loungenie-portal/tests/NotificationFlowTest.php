<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class NotificationFlowTest extends WPTestCase
{
    public array $notificationRows = [];

    protected function setUp(): void
    {
        parent::setUp();

        when('current_time')->alias(fn($t,$gmt=false) => '2025-12-16 00:00:00');
        when('wp_json_encode')->alias(fn($v)=>json_encode($v));

        // Stub wp_mail expectations in individual tests

        global $wpdb;
        $this->notificationRows = [];
        $noteRef = &$this->notificationRows;
        $wpdb = new class($noteRef) {
            public $prefix = 'wp_';
            private $note;
            public function __construct(&$note){ $this->note = &$note; }
            public function insert($table,$data){
                $this->note[] = ['table'=>$table,'data'=>$data];
                return true;
            }
        };

        require_once __DIR__ . '/../includes/class-lgp-logger.php';
        require_once __DIR__ . '/../includes/class-lgp-notifications.php';
    }

    public function test_support_receives_email_on_new_ticket_and_update(): void
    {
        expect('wp_mail')->twice()->andReturnTrue();

        $ticket = [
            'ticket_id' => 77,
            'company_id' => 42,
            'support_email' => 'support@poolsafeinc.com',
            'partner_user_id' => 0,
        ];

        LGP_Notifications::notify_ticket_event($ticket, 'created', 'urgent');
        LGP_Notifications::notify_ticket_event($ticket, 'updated', 'high');

        $channels = array_column(array_column($this->notificationRows,'data'),'channel');
        $priorities = array_column(array_column($this->notificationRows,'data'),'priority');
        $this->assertSame(['email','email'], $channels);
        $this->assertSame(['urgent','high'], $priorities);
    }

    public function test_partner_receives_email_and_portal_alert_for_own_ticket_only(): void
    {
        expect('wp_mail')->twice()->andReturnTrue();
        expect('lgp_portal_alert')->once()->with(200, 'Ticket created for company 42', 'medium');

        $ticket = [
            'ticket_id' => 80,
            'company_id' => 42,
            'support_email' => 'support@poolsafeinc.com',
            'partner_user_id' => 200,
            'partner_email' => 'partner@example.com',
        ];

        LGP_Notifications::notify_ticket_event($ticket, 'created', 'medium');

        $channels = array_column(array_column($this->notificationRows,'data'),'channel');
        $this->assertSame(['email','email','portal'], $channels);
        $roles = array_map(fn($row) => json_decode($row['data']['meta'], true)['role'], $this->notificationRows);
        $this->assertSame(['support','partner','partner'], $roles);
    }

    public function test_priority_based_notifications_logged(): void
    {
        expect('wp_mail')->once()->andReturnTrue();
        $ticket = [
            'ticket_id' => 90,
            'company_id' => 55,
            'support_email' => 'support@poolsafeinc.com',
        ];

        LGP_Notifications::notify_ticket_event($ticket, 'updated', 'low');

        $this->assertSame('low', $this->notificationRows[0]['data']['priority']);
    }
}
