<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class ApiCompaniesTest extends WPTestCase
{
    private function makeRequest(array $params = [])
    {
        // Minimal WP_REST_Request stub
        return new class($params) {
            private $p; public function __construct($p){$this->p=$p;}
            public function get_param($k){ return $this->p[$k] ?? null; }
        };
    }

    public function test_get_companies_support_sees_all(): void
    {
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public function prepare($q){ return $q; }
            public function get_results($sql){ return [(object)['id'=>1,'name'=>'Acme']]; }
            public function get_var($sql){ return 1; }
        };

        when('rest_ensure_response')->alias(fn($d)=>$d);
        when('__')->alias(fn($s)=>$s);
        when('sanitize_text_field')->alias(fn($s)=>$s);
        when('sanitize_textarea_field')->alias(fn($s)=>$s);
        when('sanitize_email')->alias(fn($s)=>$s);
        when('absint')->alias(fn($s)=> (int)$s);

        // Auth: support user
        require_once __DIR__ . '/../includes/class-lgp-auth.php';
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['ID'=>123,'roles'=>['lgp_support']] );

        require_once __DIR__ . '/../api/companies.php';

        $req = $this->makeRequest(['page'=>1,'per_page'=>20]);
        $res = LGP_Companies_API::get_companies($req);

        $this->assertSame(1, $res['total']);
        $this->assertCount(1, $res['companies']);
        $this->assertSame(1, $res['page']);
    }

    public function test_check_company_permission_partner_only_own_company(): void
    {
        require_once __DIR__ . '/../api/companies.php';
        require_once __DIR__ . '/../includes/class-lgp-auth.php';

        // Partner with company_id 42
        when('is_user_logged_in')->justReturn(true);
        when('wp_get_current_user')->justReturn((object)['ID'=>123,'roles'=>['lgp_partner']] );
        when('get_user_meta')->alias(function($id,$key){ return 42; });

        $req = $this->makeRequest(['id'=>42]);
        $this->assertTrue(LGP_Companies_API::check_company_permission($req));

        $req2 = $this->makeRequest(['id'=>7]);
        $this->assertFalse(LGP_Companies_API::check_company_permission($req2));
    }
}
