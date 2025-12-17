<?php
/**
 * Training Videos API Tests
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

if (!class_exists('LGP_Logger')) {
    class LGP_Logger {
        public static function log($action, $details, $user_email) {}
    }
}

if (!class_exists('LGP_Training_Video')) {
    class LGP_Training_Video {
        public static function get_all($filters = array()) { return []; }
        public static function get($id) { return ['id' => $id, 'title' => 'Test Video']; }
        public static function create($data) { return 42; }
        public static function update($id, $data) { return true; }
        public static function delete($id) { return true; }
        public static function get_categories() { return ['general', 'installation']; }
        public static function get_by_category($category) { return []; }
    }
}

require_once __DIR__ . '/../api/training-videos.php';

class ApiTrainingVideosTest extends WPTestCase {
    
    private $api;
    
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->api = new LGP_Training_Videos_API();
    }
    
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    public function test_check_portal_access_allows_logged_in_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('wp_get_current_user')->andReturnUsing(function() {
            return (object) ['ID' => 1, 'user_login' => 'testuser'];
        });
        
        $result = $this->api->check_portal_access();
        
        $this->assertTrue($result);
    }
    
    public function test_check_portal_access_denies_logged_out_users() {
        Functions\expect('is_user_logged_in')->andReturn(false);
        
        $result = $this->api->check_portal_access();
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }
    
    public function test_support_only_permission_allows_support_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        
        $result = $this->api->support_only_permission();
        
        $this->assertTrue($result);
    }
    
    public function test_support_only_permission_denies_partner_users() {
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('manage_options')->andReturn(false);
        
        $result = $this->api->support_only_permission();
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }
}
