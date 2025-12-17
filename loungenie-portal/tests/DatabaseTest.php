<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class DatabaseTest extends WPTestCase
{
    public function test_create_tables_calls_dbDelta_with_prefixed_names(): void
    {
        global $wpdb;
        // Fresh stub with prefix and capturing query if needed
        $wpdb = new class {
            public $prefix = 'wp_';
            public function get_charset_collate() { return 'CHARSET'; }
        };

        $captured = [];
        when('update_option')->justReturn(true);
        expect('dbDelta')->times(10)->andReturnUsing(function($sql) use (&$captured) {
            $captured[] = $sql;
            return true;
        });
        // dbDelta will be mocked via Brain Monkey; the required file is stubbed in bootstrap

        require_once __DIR__ . '/../includes/class-lgp-database.php';
        LGP_Database::create_tables();

        $this->assertCount(10, $captured);
        $joined = implode("\n\n", $captured);
        $this->assertStringContainsString('CREATE TABLE wp_lgp_companies', $joined);
        $this->assertStringContainsString('CREATE TABLE wp_lgp_management_companies', $joined);
        $this->assertStringContainsString('CREATE TABLE wp_lgp_units', $joined);
        $this->assertStringContainsString('CREATE TABLE wp_lgp_service_requests', $joined);
        $this->assertStringContainsString('CREATE TABLE wp_lgp_tickets', $joined);
    }

    public function test_drop_tables_issues_drop_queries(): void
    {
        global $wpdb;
        $queries = [];
        $wpdb = new class($queries) {
            public $prefix = 'wp_';
            public $queriesRef;
            public function __construct(& $ref) { $this->queriesRef = & $ref; }
            public function query($sql) { $this->queriesRef[] = $sql; return true; }
        };
        // bind reference back
        $refProp = new ReflectionProperty($wpdb, 'queriesRef');
        $refProp->setAccessible(true);
        $refProp->setValue($wpdb, $queries);
        when('delete_option')->justReturn(true);

        require_once __DIR__ . '/../includes/class-lgp-database.php';
        LGP_Database::drop_tables();

        $this->assertNotEmpty($queries);
        $this->assertStringContainsString('DROP TABLE IF EXISTS wp_lgp_companies', implode("\n", $queries));
    }
}
