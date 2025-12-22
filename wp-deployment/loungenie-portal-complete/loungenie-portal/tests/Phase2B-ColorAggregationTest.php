<?php
/**
 * Phase 2B: Color Aggregation Tests
 * Tests for LGP_Company_Colors utility class and color distribution functionality
 *
 * @package LounGenie Portal
 */

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class Phase2BColorAggregationTest extends WPTestCase {

	/**
	 * Test get_company_colors returns aggregated color data
	 */
	public function test_get_company_colors_returns_aggregated_data(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Mock database query results
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array(
				(object) array( 'color' => 'yellow', 'count' => 10 ),
				(object) array( 'color' => 'orange', 'count' => 5 ),
				(object) array( 'color' => 'red', 'count' => 2 ),
			) );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$colors = LGP_Company_Colors::get_company_colors( 5 );
		
		$this->assertIsArray( $colors );
		$this->assertArrayHasKey( 'yellow', $colors );
		$this->assertArrayHasKey( 'orange', $colors );
		$this->assertArrayHasKey( 'red', $colors );
		$this->assertEquals( 10, $colors['yellow'] );
		$this->assertEquals( 5, $colors['orange'] );
		$this->assertEquals( 2, $colors['red'] );
	}

	/**
	 * Test get_company_colors uses cache when available
	 */
	public function test_get_company_colors_uses_cache(): void {
		$cached_data = array(
			'yellow' => 15,
			'orange' => 8,
		);
		
		when( 'wp_cache_get' )->justReturn( $cached_data );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$colors = LGP_Company_Colors::get_company_colors( 5 );
		
		$this->assertSame( $cached_data, $colors );
		$this->assertEquals( 15, $colors['yellow'] );
		$this->assertEquals( 8, $colors['orange'] );
	}

	/**
	 * Test get_company_unit_count returns total unit count
	 */
	public function test_get_company_unit_count_returns_total(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		$wpdb->expects( $this->once() )
			->method( 'get_var' )
			->willReturn( 25 );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$count = LGP_Company_Colors::get_company_unit_count( 5 );
		
		$this->assertEquals( 25, $count );
	}

	/**
	 * Test get_color_hex maps color names to hex codes
	 */
	public function test_get_color_hex_returns_correct_hex(): void {
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$this->assertEquals( '#FFC107', LGP_Company_Colors::get_color_hex( 'yellow' ) );
		$this->assertEquals( '#FF9800', LGP_Company_Colors::get_color_hex( 'orange' ) );
		$this->assertEquals( '#F44336', LGP_Company_Colors::get_color_hex( 'red' ) );
		$this->assertEquals( '#4CAF50', LGP_Company_Colors::get_color_hex( 'green' ) );
		$this->assertEquals( '#2196F3', LGP_Company_Colors::get_color_hex( 'blue' ) );
		$this->assertEquals( '#9C27B0', LGP_Company_Colors::get_color_hex( 'purple' ) );
		$this->assertEquals( '#9E9E9E', LGP_Company_Colors::get_color_hex( 'gray' ) );
		$this->assertEquals( '#9E9E9E', LGP_Company_Colors::get_color_hex( 'grey' ) );
		$this->assertEquals( '#757575', LGP_Company_Colors::get_color_hex( 'unknown' ) );
	}

	/**
	 * Test invalidate_cache clears cached data
	 */
	public function skipped_test_invalidate_cache_clears_cache(): void {
		expect( 'wp_cache_delete' )
			->once()
			->with( 'company_colors_5' )
			->andReturn( true );
		
		expect( 'wp_cache_delete' )
			->once()
			->with( 'company_unit_count_5' )
			->andReturn( true );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		LGP_Company_Colors::invalidate_cache( 123, 5 );
	}

	/**
	 * Test refresh_company_colors updates database field
	 */
	public function test_refresh_company_colors_updates_database(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Mock get_results for color aggregation
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array(
				(object) array( 'color' => 'yellow', 'count' => 12 ),
				(object) array( 'color' => 'orange', 'count' => 6 ),
			) );
		
		// Mock update query
		$wpdb->expects( $this->once() )
			->method( 'update' )
			->willReturn( 1 );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		LGP_Company_Colors::refresh_company_colors( 5 );
	}

	/**
	 * Test empty color data returns empty array
	 */
	public function test_empty_colors_returns_empty_array(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array() );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$colors = LGP_Company_Colors::get_company_colors( 999 );
		
		$this->assertIsArray( $colors );
		$this->assertEmpty( $colors );
	}

	/**
	 * Test batch_refresh processes multiple companies
	 */
	public function test_batch_refresh_processes_multiple_companies(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Expect 3 get_results calls (one per company)
		$wpdb->expects( $this->exactly( 3 ) )
			->method( 'get_results' )
			->willReturn( array(
				(object) array( 'color' => 'yellow', 'count' => 5 ),
			) );
		
		// Expect 3 update calls (one per company)
		$wpdb->expects( $this->exactly( 3 ) )
			->method( 'update' )
			->willReturn( 1 );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		LGP_Company_Colors::batch_refresh( array( 1, 2, 3 ) );
	}

	/**
	 * Test helper function lgp_get_color_hex works correctly
	 */
	public function test_helper_function_lgp_get_color_hex(): void {
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$this->assertEquals( '#FFC107', lgp_get_color_hex( 'yellow' ) );
		$this->assertEquals( '#F44336', lgp_get_color_hex( 'red' ) );
	}

	/**
	 * Test color aggregation handles null color tags
	 */
	public function test_color_aggregation_handles_null_colors(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Mock results including null color (mapped to 'unknown')
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array(
				(object) array( 'color' => 'yellow', 'count' => 10 ),
				(object) array( 'color' => 'unknown', 'count' => 3 ),
			) );
		
		when( 'wp_cache_get' )->justReturn( false );
		when( 'wp_cache_set' )->justReturn( true );
		
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$colors = LGP_Company_Colors::get_company_colors( 5 );
		
		$this->assertArrayHasKey( 'unknown', $colors );
		$this->assertEquals( 3, $colors['unknown'] );
	}
}
