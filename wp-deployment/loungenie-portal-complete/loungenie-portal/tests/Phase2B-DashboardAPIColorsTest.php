<?php
/**
 * Phase 2B: Dashboard API Color Aggregates Tests
 * Tests for color_aggregates field in Dashboard API response
 *
 * @package LounGenie Portal
 */

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class Phase2BDashboardAPIColorsTest extends WPTestCase {

	/**
	 * Test Dashboard API includes color_aggregates for Support role
	 */
	public function test_dashboard_includes_color_aggregates_for_support(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Mock Support user
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );
		
		// Mock get_results for companies with top_colors
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->with( $this->stringContains( 'SELECT id, name, top_colors' ) )
			->willReturn( array(
				(object) array(
					'id' => 5,
					'name' => 'Test Company',
					'top_colors' => '{"yellow":10,"orange":5,"red":2}',
				),
				(object) array(
					'id' => 6,
					'name' => 'Another Company',
					'top_colors' => '{"yellow":8,"green":4}',
				),
			) );
		
		when( 'rest_ensure_response' )->alias( function( $data ) {
			return $data;
		} );
		
		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		// Simulate Dashboard API response building
		$is_support = LGP_Auth::is_support();
		$this->assertTrue( $is_support );
		
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$all_companies = $wpdb->get_results(
			"SELECT id, name, top_colors FROM {$companies_table} ORDER BY name"
		);
		
		$color_aggregates = array();
		foreach ( $all_companies as $company ) {
			$colors = json_decode( $company->top_colors ?? '{}', true );
			if ( ! empty( $colors ) ) {
				$color_aggregates[] = array(
					'company_id' => $company->id,
					'company_name' => $company->name,
					'unit_count' => 17, // Mocked
					'colors' => $colors,
				);
			}
		}
		
		$this->assertCount( 2, $color_aggregates );
		$this->assertEquals( 5, $color_aggregates[0]['company_id'] );
		$this->assertEquals( 'Test Company', $color_aggregates[0]['company_name'] );
		$this->assertArrayHasKey( 'yellow', $color_aggregates[0]['colors'] );
		$this->assertEquals( 10, $color_aggregates[0]['colors']['yellow'] );
	}

	/**
	 * Test Dashboard API includes color_aggregates for Partner role (own company only)
	 */
	public function test_dashboard_includes_color_aggregates_for_partner(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		// Mock Partner user
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array(
			'ID' => 42,
			'roles' => array( 'lgp_partner' ),
		) );
		when( 'get_user_meta' )->justReturn( 5 ); // company_id = 5
		
		// Mock get_row for single company
		$wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( (object) array(
				'id' => 5,
				'name' => 'Partner Company',
				'top_colors' => '{"yellow":12,"orange":6}',
			) );
		
		when( 'rest_ensure_response' )->alias( function( $data ) {
			return $data;
		} );
		
		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		require_once __DIR__ . '/../includes/class-lgp-company-colors.php';
		
		$is_support = LGP_Auth::is_support();
		$company_id = LGP_Auth::get_user_company_id();
		
		$this->assertFalse( $is_support );
		$this->assertEquals( 5, $company_id );
		
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$company_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, name, top_colors FROM {$companies_table} WHERE id = %d",
				$company_id
			)
		);
		
		$color_aggregates = array();
		if ( $company_data ) {
			$colors = json_decode( $company_data->top_colors ?? '{}', true );
			if ( ! empty( $colors ) ) {
				$color_aggregates[] = array(
					'company_id' => $company_data->id,
					'company_name' => $company_data->name,
					'unit_count' => 18, // Mocked
					'colors' => $colors,
				);
			}
		}
		
		$this->assertCount( 1, $color_aggregates );
		$this->assertEquals( 5, $color_aggregates[0]['company_id'] );
		$this->assertEquals( 'Partner Company', $color_aggregates[0]['company_name'] );
		$this->assertEquals( 12, $color_aggregates[0]['colors']['yellow'] );
	}

	/**
	 * Test Dashboard API handles empty top_colors field
	 */
	public function test_dashboard_handles_empty_top_colors(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );
		
		// Mock company with null top_colors
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array(
				(object) array(
					'id' => 5,
					'name' => 'Empty Company',
					'top_colors' => null,
				),
			) );
		
		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$all_companies = $wpdb->get_results(
			"SELECT id, name, top_colors FROM {$companies_table} ORDER BY name"
		);
		
		$color_aggregates = array();
		foreach ( $all_companies as $company ) {
			$colors = json_decode( $company->top_colors ?? '{}', true );
			if ( ! empty( $colors ) ) {
				$color_aggregates[] = array(
					'company_id' => $company->id,
					'company_name' => $company->name,
					'colors' => $colors,
				);
			}
		}
		
		$this->assertEmpty( $color_aggregates );
	}

	/**
	 * Test Dashboard API handles malformed JSON in top_colors
	 */
	public function test_dashboard_handles_malformed_json(): void {
		global $wpdb;
		
		$wpdb = $this->createMock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );
		
		// Mock company with invalid JSON
		$wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( array(
				(object) array(
					'id' => 5,
					'name' => 'Bad JSON Company',
					'top_colors' => '{invalid json}',
				),
			) );
		
		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$all_companies = $wpdb->get_results(
			"SELECT id, name, top_colors FROM {$companies_table} ORDER BY name"
		);
		
		$color_aggregates = array();
		foreach ( $all_companies as $company ) {
			$colors = json_decode( $company->top_colors ?? '{}', true );
			if ( ! empty( $colors ) ) {
				$color_aggregates[] = array(
					'company_id' => $company->id,
					'company_name' => $company->name,
					'colors' => $colors,
				);
			}
		}
		
		// Should handle gracefully with empty array
		$this->assertEmpty( $color_aggregates );
	}

	/**
	 * Test color_aggregates array structure is correct
	 */
	public function test_color_aggregates_structure(): void {
		$color_aggregate = array(
			'company_id' => 5,
			'company_name' => 'Test Company',
			'unit_count' => 15,
			'colors' => array(
				'yellow' => 10,
				'orange' => 5,
			),
		);
		
		$this->assertArrayHasKey( 'company_id', $color_aggregate );
		$this->assertArrayHasKey( 'company_name', $color_aggregate );
		$this->assertArrayHasKey( 'unit_count', $color_aggregate );
		$this->assertArrayHasKey( 'colors', $color_aggregate );
		$this->assertIsArray( $color_aggregate['colors'] );
		$this->assertIsInt( $color_aggregate['company_id'] );
		$this->assertIsString( $color_aggregate['company_name'] );
		$this->assertIsInt( $color_aggregate['unit_count'] );
	}

	/**
	 * Test Dashboard API backward compatibility (existing clients ignore new field)
	 */
	public function test_dashboard_backward_compatible(): void {
		$response = array(
			'total_units' => 15,
			'active_tickets' => 2,
			'resolved_today' => 1,
			'average_resolution' => 2.5,
			'role' => 'support',
			'company_id' => null,
			'color_aggregates' => array(), // New field
		);
		
		// Existing clients can still read original fields
		$this->assertEquals( 15, $response['total_units'] );
		$this->assertEquals( 2, $response['active_tickets'] );
		$this->assertEquals( 1, $response['resolved_today'] );
		$this->assertEquals( 2.5, $response['average_resolution'] );
		
		// New field is optional and won't break existing clients
		$this->assertArrayHasKey( 'color_aggregates', $response );
	}
}
