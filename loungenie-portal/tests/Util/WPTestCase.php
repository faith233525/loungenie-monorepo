<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

// Simple fixture factory stub for test data creation
class TestFactoryStub {
    public $user;
    
    public function __construct() {
        $this->user = new UserFactoryStub();
    }
}

class UserFactoryStub {
    private $user_id = 100;
    
    public function create($args = []) {
        $id = $this->user_id++;
        
        // Set roles if provided
        if (isset($args['role'])) {
            global $test_user_roles;
            if (!isset($test_user_roles)) {
                $test_user_roles = [];
            }
            $test_user_roles[$id] = [$args['role']];
        }
        
        // Store user meta if provided
        if (isset($args['user_login'])) {
            update_user_meta($id, 'user_login', $args['user_login']);
        }
        if (isset($args['user_email'])) {
            update_user_meta($id, 'user_email', $args['user_email']);
        }
        
        return $id;
    }
}

abstract class WPTestCase extends TestCase
{
    protected $factory;
    
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Reset global test state
        global $test_current_user_id, $test_user_meta, $test_user_roles;
        $test_current_user_id = 0;
        $test_user_meta = [];
        $test_user_roles = [];
        
        // Create a fresh wpdb stub with empty in-memory tables
        global $wpdb;
        $wpdb = new WP_Database_Stub();
        
        // Define test helper functions AFTER Patchwork loads
        // These can be mocked by tests that need to
        $this->defineTestFunctions();
        
        // Initialize fixture factory
        $this->factory = new TestFactoryStub();
    }
    
    protected function defineTestFunctions() {
        // Set up default behavior for WordPress functions using Brain Monkey
        // Tests can override these if needed
        \Brain\Monkey\Functions\when('wp_set_current_user')->alias(function($user_id) {
            global $test_current_user_id;
            $test_current_user_id = $user_id;
        });
        
        \Brain\Monkey\Functions\when('get_current_user_id')->alias(function() {
            global $test_current_user_id;
            return $test_current_user_id ?? 0;
        });
        
        \Brain\Monkey\Functions\when('is_user_logged_in')->alias(function() {
            return (function_exists('get_current_user_id') ? get_current_user_id() : 0) > 0;
        });
        
        \Brain\Monkey\Functions\when('update_user_meta')->alias(function($user_id, $meta_key, $meta_value) {
            global $test_user_meta;
            if (!isset($test_user_meta[$user_id])) {
                $test_user_meta[$user_id] = [];
            }
            $test_user_meta[$user_id][$meta_key] = $meta_value;
            return true;
        });
        
        \Brain\Monkey\Functions\when('get_user_meta')->alias(function($user_id, $meta_key, $single = false) {
            global $test_user_meta;
            if (!isset($test_user_meta[$user_id])) {
                return $single ? '' : [];
            }
            if (isset($test_user_meta[$user_id][$meta_key])) {
                return $test_user_meta[$user_id][$meta_key];
            }
            return $single ? '' : [];
        });
        
        \Brain\Monkey\Functions\when('wp_get_current_user')->alias(function() {
            $user_id = (function_exists('get_current_user_id') ? get_current_user_id() : 0);
            if ($user_id <= 0) {
                return new WP_User();
            }
            global $test_user_roles;
            if (!isset($test_user_roles)) {
                $test_user_roles = [];
            }
            $user = new WP_User();
            $user->ID = $user_id;
            $user->roles = $test_user_roles[$user_id] ?? [];
            return $user;
        });
        
        \Brain\Monkey\Functions\when('current_time')->alias(function($type = 'mysql', $gmt = false) {
            if ($type === 'mysql') {
                return date('Y-m-d H:i:s');
            } elseif ($type === 'timestamp') {
                return time();
            }
            return date($type);
        });
        
        \Brain\Monkey\Functions\when('is_wp_error')->alias(function($thing) {
            return is_object($thing) && get_class($thing) === 'WP_Error';
        });
        
        \Brain\Monkey\Functions\when('current_user_can')->alias(function($capability) {
            $user = wp_get_current_user();
            // Admins can do everything
            if (!empty($user->ID) && in_array('administrator', $user->roles ?? [], true)) {
                return true;
            }
            // Support role can manage options
            if (!empty($user->ID) && in_array('lgp_support', $user->roles ?? [], true)) {
                return true;
            }
            return false;
        });
        
        \Brain\Monkey\Functions\when('rest_ensure_response')->alias(function($data) {
            if (is_wp_error($data)) {
                return new WP_REST_Response(
                    ['code' => $data->get_error_code(), 'message' => $data->get_error_message()],
                    $data->get_error_data()['status'] ?? 400
                );
            }
            if (is_object($data) && get_class($data) === 'WP_REST_Response') {
                return $data;
            }
            return new WP_REST_Response($data, 200);
        });
        
        \Brain\Monkey\Functions\when('sanitize_text_field')->alias(function($input) {
            return strip_tags((string)$input);
        });
        
        \Brain\Monkey\Functions\when('sanitize_textarea_field')->alias(function($input) {
            return strip_tags((string)$input);
        });
        
        \Brain\Monkey\Functions\when('wp_kses_post')->alias(function($input) {
            return strip_tags((string)$input);
        });

        // Common WP helpers/no-ops for tests
        \Brain\Monkey\Functions\when('do_action')->alias(function($hook, ...$args) {
            return null;
        });
        \Brain\Monkey\Functions\when('__')->alias(function($text, $domain = null) {
            return (string)$text;
        });
        \Brain\Monkey\Functions\when('_x')->alias(function($text, $context, $domain = null) {
            return (string)$text;
        });
        \Brain\Monkey\Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });
        
        \Brain\Monkey\Functions\when('sanitize_email')->alias(function($input) {
            return filter_var((string)$input, FILTER_SANITIZE_EMAIL);
        });
        
        \Brain\Monkey\Functions\when('is_email')->alias(function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
