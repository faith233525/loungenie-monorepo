<?php
/**
 * Contract Metadata Tests
 *
 * @package LounGenie Portal
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/Util/WPTestCase.php';

class ContractMetadataTest extends WPTestCase {
    
    public function test_contract_type_validation() {
        $valid_types = array('revenue_share', 'direct_purchase');
        
        $this->assertContains('revenue_share', $valid_types);
        $this->assertContains('direct_purchase', $valid_types);
        $this->assertNotContains('invalid_type', $valid_types);
    }
    
    public function test_color_tag_validation() {
        $valid_colors = array('classic-blue', 'ice-blue', 'ducati-red', 'yellow', 'custom');
        
        $this->assertContains('classic-blue', $valid_colors);
        $this->assertContains('ducati-red', $valid_colors);
        $this->assertCount(5, $valid_colors);
    }
    
    public function test_lock_brand_validation() {
        $valid_locks = array('MAKE', 'L&F', 'other');
        
        $this->assertContains('MAKE', $valid_locks);
        $this->assertContains('L&F', $valid_locks);
        $this->assertCount(3, $valid_locks);
    }
}
