<?php

use function Brain\Monkey\Functions\when;

final class ApiUnitsTest extends WPTestCase
{
    private function makeRequest(array $params = [])
    {
        return new class($params) {
            private $p; public function __construct($p){$this->p=$p;}
            public function get_param($k){ return $this->p[$k] ?? null; }
        };
    }

    public function test_get_units_partner_scoped_by_company(): void
    {
        global $wpdb;
        $captured = [];
        $capRef = &$captured;
        $wpdb = new class($capRef) {
            public $prefix = 'wp_';
            private $cap; public function __construct($c){$this->cap=$c;}
            public function prepare($q){ $this->cap[] = $q; return $q; }
            public function get_results($sql){ return [(object)['id'=>10,'company_id'=>42]]; }
            public function get_var($sql){ return 1; }
        };

        when('rest_ensure_response')->alias(fn($d)=>$d);

        // Partner with company_id 42
        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['ID'=>123,'roles'=>['lgp_partner']] );
        when('get_user_meta')->alias(function($id,$key){ return 42; });

        require_once __DIR__ . '/../api/units.php';

        $req = $this->makeRequest(['page'=>1,'per_page'=>10]);
        $res = LGP_Units_API::get_units($req);

        $this->assertSame(1, $res['total']);
        $this->assertSame(10, $res['per_page']);
        $this->assertCount(1, $res['units']);
    }
}
