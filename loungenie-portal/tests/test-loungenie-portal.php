<?php
/**
 * LounGenie Portal Unit Tests
 *
 * @package LounGenie_Portal
 */

use PHPUnit\Framework\TestCase;

class LounGenie_Portal_Test extends TestCase {

	public function test_plugin_file_exists() {
		$plugin_file = dirname( __DIR__ ) . '/loungenie-portal.php';
		$this->assertFileExists( $plugin_file );
	}

	public function test_required_directories_exist() {
		$base_dir = dirname( __DIR__ );
		$dirs = array( 'includes', 'assets', 'templates', 'api', 'roles' );
		foreach ( $dirs as $dir ) {
			$this->assertDirectoryExists( $base_dir . '/' . $dir );
		}
	}

	public function test_css_contains_design_system() {
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/portal.css' );
		$this->assertStringContainsString( '--primary', $css );
		$this->assertStringContainsString( '--secondary', $css );
	}
}
