<?php
/**
 * Example PHPUnit Test
 * 
 * @package LounGenie\Portal\Tests
 */

use PHPUnit\Framework\TestCase;

/**
 * Basic placeholder test to verify PHPUnit setup
 */
class ExampleTest extends TestCase {
    
    /**
     * Test that PHPUnit is properly configured
     */
    public function testPhpUnitIsWorking(): void {
        $this->assertTrue(true, 'PHPUnit is configured correctly');
    }
    
    /**
     * Test basic PHP functionality
     */
    public function testBasicPHPFunctions(): void {
        $this->assertEquals(4, 2 + 2);
        $this->assertIsString('LounGenie Portal');
        $this->assertIsArray([]);
    }
    
    /**
     * Test plugin file exists
     */
    public function testPluginFileExists(): void {
        $pluginFile = __DIR__ . '/../loungenie-portal.php';
        $this->assertFileExists($pluginFile, 'Main plugin file should exist');
    }
}
