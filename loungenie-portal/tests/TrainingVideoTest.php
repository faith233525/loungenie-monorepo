<?php
/**
 * Training Video Class Tests
 *
 * @package LounGenie Portal
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/Util/WPTestCase.php';

// Mock classes before including the actual class
if (!class_exists('LGP_Auth')) {
    class LGP_Auth {
        public static function is_support() { return true; }
        public static function get_current_company_id() { return 1; }
    }
}

require_once __DIR__ . '/../includes/class-lgp-training-video.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TrainingVideoTest extends WPTestCase {
    
    protected function setUp(): void {
        parent::setUp();
        
        // Mock WordPress auth functions for LGP_Auth::is_support()
        // Note: When run with other tests, real LGP_Auth is loaded,  so we must mock WP functions properly
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object)['ID' => 1, 'roles' => ['lgp_support'], 'user_email' => 'admin@test.com']);
        Functions\when('get_current_user_id')->justReturn(1);
        
        // Mock WordPress sanitize functions
        Functions\expect('sanitize_text_field')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function($text) { return $text; });
        Functions\expect('sanitize_textarea_field')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function($text) { return $text; });
        Functions\expect('esc_url_raw')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function($url) { return $url; });
        Functions\expect('wp_json_encode')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function($data) { return json_encode($data); });
        Functions\expect('current_time')
            ->zeroOrMoreTimes()
            ->andReturn('2024-01-15 10:00:00');
    }
    
    public function test_get_categories_returns_available_categories() {
        $categories = LGP_Training_Video::get_categories();
        
        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
        // Verify at least one expected category exists
        $hasExpectedCategory = false;
        foreach(['general', 'installation', 'troubleshooting', 'maintenance', 'product-overview'] as $cat) {
            if(in_array($cat, $categories)) $hasExpectedCategory = true;
        }
        $this->assertTrue($hasExpectedCategory);
    }
    
    public function test_create_requires_title() {
        $data = [
            'video_url' => 'https://youtube.com/watch?v=xyz',
            'category' => 'general'
        ];
        
        $result = LGP_Training_Video::create($data);
        
        $this->assertFalse($result);
    }
    
    public function test_create_requires_video_url() {
        $data = [
            'title' => 'New Video',
            'category' => 'general'
        ];
        
        $result = LGP_Training_Video::create($data);
        
        $this->assertFalse($result);
    }
}
