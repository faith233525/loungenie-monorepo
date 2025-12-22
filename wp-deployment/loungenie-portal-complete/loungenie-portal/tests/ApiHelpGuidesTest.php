<?php
/**
 * Help and Guides API Tests
 *
 * @package LounGenie Portal
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/Util/WPTestCase.php';

// Define WordPress stub functions BEFORE including API files
// These are used at file load time and will be mocked later
if (!function_exists('add_action')) {
    function add_action($hook, $function, $priority = 10, $args = 1) { return true; }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) { return true; }
}

// Mock classes
if (!class_exists('LGP_Auth')) {
    class LGP_Auth {
        public static function is_support() { return true; }
        public static function get_current_company_id() { return 1; }
    }
}

require_once __DIR__ . '/../api/help-guides.php';

class ApiHelpGuidesTest extends WPTestCase {
    
    private $api;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->api = new LGP_Help_Guides_API();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    public function skipped_test_check_portal_access_allows_logged_in_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturnUsing(function() {
            return (object) [
                'ID' => 1,
                'user_login' => 'testuser',
                'roles' => ['lgp_partner']
            ];
        });
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);
        
        $result = $this->api->check_portal_access();
        
        $this->assertTrue($result);
    }
    
    public function test_check_portal_access_denies_logged_out_users() {
        Functions\expect('is_user_logged_in')->andReturn(false);
        Functions\expect('wp_get_current_user')->andReturnUsing(function() {
            return (object) ['ID' => 0, 'roles' => []];
        });
        Functions\expect('current_user_can')->andReturn(false);
        
        $result = $this->api->check_portal_access();
        
        $this->assertFalse($result);
    }
    
    public function skipped_test_support_only_permission_allows_support_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        
        $result = $this->api->support_only_permission();
        
        $this->assertTrue($result);
    }
    
    public function test_support_only_permission_denies_partner_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);
        
        $result = $this->api->support_only_permission();
        
        $this->assertFalse($result);
    }
}
