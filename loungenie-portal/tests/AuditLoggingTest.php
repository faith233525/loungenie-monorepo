<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class AuditLoggingTest extends WPTestCase
{
    public array $auditRows = [];
    public array $notificationRows = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Stub time and JSON helpers
        when('current_time')->alias(fn($t,$gmt=false) => '2025-12-16 00:00:00');
        when('wp_json_encode')->alias(fn($v)=>json_encode($v));

        // Minimal wp_die safety
        when('wp_die')->alias(function($msg=''){ throw new Exception((string)$msg); });

        // $wpdb stub capturing inserts
        global $wpdb;
        $this->auditRows = [];
        $this->notificationRows = [];
        $auditRef = &$this->auditRows;
        $noteRef = &$this->notificationRows;
        $wpdb = new class($auditRef, $noteRef) {
            public $prefix = 'wp_';
            private $audit;
            private $note;
            public function __construct(&$audit,&$note){ $this->audit = &$audit; $this->note = &$note; }
            public function insert($table,$data){
                if (str_contains($table,'audit')) {
                    $this->audit[] = ['table'=>$table,'data'=>$data];
                } else {
                    $this->note[] = ['table'=>$table,'data'=>$data];
                }
                return true;
            }
        };

        require_once __DIR__ . '/../includes/class-lgp-logger.php';
    }

    public function test_logs_login_success_and_failure(): void
    {
        LGP_Logger::log_event(101, 'login_success', 5, ['role'=>'partner']);
        LGP_Logger::log_event(102, 'login_failure', null, ['role'=>'support']);

        $this->assertCount(2, $this->auditRows);
        $this->assertSame('wp_lgp_audit_log', $this->auditRows[0]['table']);
        $this->assertSame(101, $this->auditRows[0]['data']['user_id']);
        $this->assertSame('login_success', $this->auditRows[0]['data']['action']);
        $this->assertSame('2025-12-16 00:00:00', $this->auditRows[0]['data']['created_at']);
    }

    public function test_logs_password_change(): void
    {
        LGP_Logger::log_event(201, 'password_change', 7, ['by'=>'user']);
        $this->assertSame('password_change', $this->auditRows[0]['data']['action']);
        $this->assertSame(7, $this->auditRows[0]['data']['company_id']);
    }

    public function test_logs_ticket_crud_actions(): void
    {
        LGP_Logger::log_event(301, 'ticket_create', 9, ['ticket_id'=>1]);
        LGP_Logger::log_event(301, 'ticket_update', 9, ['ticket_id'=>1]);
        LGP_Logger::log_event(302, 'ticket_reply', 9, ['ticket_id'=>1]);
        LGP_Logger::log_event(301, 'ticket_close', 9, ['ticket_id'=>1]);

        $actions = array_column(array_column($this->auditRows,'data'),'action');
        $this->assertSame(['ticket_create','ticket_update','ticket_reply','ticket_close'], $actions);
    }

    public function test_logs_notification_sends(): void
    {
        LGP_Logger::log_notification('support@poolsafeinc.com', 'email', 'urgent', 11, 9, ['role'=>'support']);
        LGP_Logger::log_notification(500, 'portal', 'high', 11, 9, ['role'=>'partner']);

        $this->assertCount(2, $this->notificationRows);
        $this->assertSame('wp_lgp_notification_log', $this->notificationRows[0]['table']);
        $this->assertSame('email', $this->notificationRows[0]['data']['channel']);
        $this->assertSame('urgent', $this->notificationRows[0]['data']['priority']);
        $this->assertSame(9, $this->notificationRows[0]['data']['company_id']);
    }
}
