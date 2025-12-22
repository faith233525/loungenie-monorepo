<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

/**
 * Tests to ensure that audit logging works across critical actions.
 *
 * @author Loungenie Team
 */
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
    
    public function test_logs_company_crud_operations(): void
    {
        // Create company
        LGP_Logger::log_event(101, 'company_created', 10, [
            'company_name' => 'Acme Corp',
            'state' => 'CA',
        ]);
        
        // Update company
        LGP_Logger::log_event(101, 'company_updated', 10, [
            'company_name' => 'Acme Corporation',
            'fields_updated' => ['name', 'address', 'contact_email'],
        ]);
        
        $this->assertCount(2, $this->auditRows);
        $this->assertSame('company_created', $this->auditRows[0]['data']['action']);
        $this->assertSame('company_updated', $this->auditRows[1]['data']['action']);
        $this->assertSame(10, $this->auditRows[0]['data']['company_id']);
    }
    
    public function test_logs_unit_crud_operations(): void
    {
        // Create unit
        LGP_Logger::log_event(102, 'unit_created', 15, [
            'unit_id' => 42,
            'address' => '123 Main St',
            'color_tag' => 'classic-blue',
            'status' => 'active',
        ]);
        
        // Update unit
        LGP_Logger::log_event(102, 'unit_updated', 15, [
            'unit_id' => 42,
            'fields_updated' => ['status', 'service_history'],
            'status' => 'maintenance',
        ]);
        
        $this->assertCount(2, $this->auditRows);
        $this->assertSame('unit_created', $this->auditRows[0]['data']['action']);
        $this->assertSame('unit_updated', $this->auditRows[1]['data']['action']);
        $this->assertSame(15, $this->auditRows[0]['data']['company_id']);
    }
    
    public function test_logs_authentication_events(): void
    {
        // Login success
        LGP_Logger::log_event(201, 'login_success', 20, [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'role' => 'lgp_support',
            'ip_address' => '192.168.1.1',
        ]);
        
        // Login failed
        LGP_Logger::log_event(0, 'login_failed', null, [
            'username_attempted' => 'hacker',
            'error_code' => 'invalid_username',
            'ip_address' => '192.168.1.100',
        ]);
        
        // Logout
        LGP_Logger::log_event(201, 'logout', 20, [
            'user_login' => 'testuser',
            'role' => 'lgp_support',
        ]);
        
        // Password reset
        LGP_Logger::log_event(201, 'password_reset', 20, [
            'user_login' => 'testuser',
            'reset_method' => 'email_link',
        ]);
        
        // Password changed
        LGP_Logger::log_event(201, 'password_changed', 20, [
            'user_login' => 'testuser',
            'change_method' => 'profile_update',
        ]);
        
        $this->assertCount(5, $this->auditRows);
        $actions = array_column(array_column($this->auditRows, 'data'), 'action');
        $this->assertSame([
            'login_success',
            'login_failed',
            'logout',
            'password_reset',
            'password_changed'
        ], $actions);
    }
    
    public function test_logs_attachment_operations(): void
    {
        // Upload attachment
        LGP_Logger::log_event(103, 'attachment_uploaded', 25, [
            'ticket_id' => 5,
            'file_name' => 'invoice.pdf',
            'file_size' => 1024000,
            'file_type' => 'application/pdf',
        ]);
        
        // Download attachment
        LGP_Logger::log_event(104, 'attachment_downloaded', 25, [
            'attachment_id' => 10,
            'file_name' => 'invoice.pdf',
        ]);
        
        // Delete attachment
        LGP_Logger::log_event(103, 'attachment_deleted', 25, [
            'attachment_id' => 10,
            'file_name' => 'invoice.pdf',
        ]);
        
        $this->assertCount(3, $this->auditRows);
        $actions = array_column(array_column($this->auditRows, 'data'), 'action');
        $this->assertContains('attachment_uploaded', $actions);
        $this->assertContains('attachment_downloaded', $actions);
        $this->assertContains('attachment_deleted', $actions);
    }
    
    public function test_audit_log_includes_timestamps(): void
    {
        LGP_Logger::log_event(105, 'test_action', 30, ['test' => 'data']);
        
        $this->assertCount(1, $this->auditRows);
        $this->assertArrayHasKey('created_at', $this->auditRows[0]['data']);
        $this->assertSame('2025-12-16 00:00:00', $this->auditRows[0]['data']['created_at']);
    }
    
    public function test_audit_log_stores_metadata_as_json(): void
    {
        $metadata = [
            'old_value' => 'pending',
            'new_value' => 'resolved',
            'reason' => 'Issue fixed',
        ];
        
        LGP_Logger::log_event(106, 'status_change', 35, $metadata);
        
        $this->assertCount(1, $this->auditRows);
        $storedMeta = $this->auditRows[0]['data']['meta'];
        $this->assertIsString($storedMeta);
        $decoded = json_decode($storedMeta, true);
        $this->assertSame($metadata, $decoded);
    }
}
