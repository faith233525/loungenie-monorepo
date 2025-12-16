<?php

use function Brain\Monkey\Functions\when;

final class ApiTicketsTest extends WPTestCase
{
    private function makeRequest(array $params = [])
    {
        return new class($params) {
            private $p; public function __construct($p){$this->p=$p;}
            public function get_param($k){ return $this->p[$k] ?? null; }
        };
    }

    public function test_get_tickets_partner_scoped_by_company(): void
    {
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public function prepare($q){ return $q; }
            public function get_results($sql){ return [(object)['id'=>99,'service_request_id'=>5,'company_id'=>42]]; }
            public function get_var($sql){ return 1; }
        };

        when('rest_ensure_response')->alias(fn($d)=>$d);

        // Partner user with company_id 42
        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['ID'=>123,'roles'=>['lgp_partner']] );
        when('get_user_meta')->alias(function($id,$key){ return 42; });

        require_once __DIR__ . '/../api/tickets.php';

        $req = $this->makeRequest(['page'=>1,'per_page'=>20]);
        $res = LGP_Tickets_API::get_tickets($req);

        $this->assertSame(1, $res['total']);
        $this->assertCount(1, $res['tickets']);
        $this->assertSame(1, $res['page']);
    }
}
