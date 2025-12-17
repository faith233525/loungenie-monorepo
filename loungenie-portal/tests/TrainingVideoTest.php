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

class TrainingVideoTest extends WPTestCase {
    
    protected function setUp(): void {
        parent::setUp();
        
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
        Functions\expect('wp_get_current_user')
            ->zeroOrMoreTimes()
            ->andReturn((object)['ID' => 1, 'user_email' => 'admin@test.com']);
        Functions\expect('current_time')
            ->zeroOrMoreTimes()
            ->andReturn('2024-01-15 10:00:00');
    }
    
    public function test_get_categories_returns_available_categories() {
        $categories = LGP_Training_Video::get_categories();
        
        $this->assertIsArray($categories);
        $this->assertContains('general', $categories);
        $this->assertContains('installation', $categories);
        $this->assertContains('troubleshooting', $categories);
        $this->assertContains('maintenance', $categories);
        $this->assertContains('product-overview', $categories);
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
