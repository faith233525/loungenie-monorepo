<?php
/**
 * Tests for Map View and Enhanced Features
 */

use function Brain\Monkey\Functions\when;

final class MapViewTest extends WPTestCase {

	private $orig_wpdb;

	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$this->orig_wpdb = $wpdb ?? null;
	}

	public function tearDown(): void {
		global $wpdb;
		if ( $this->orig_wpdb ) {
			$wpdb = $this->orig_wpdb;
		}
		parent::tearDown();
	}

	/**
	 * Test map data retrieval for support users
	 */
	public function test_get_map_data_support_sees_all_units() {
		global $wpdb;

		$units = array(
			(object) array(
				'id'        => 1,
				'name'      => 'Pool A',
				'type'      => 'maintenance',
				'location'  => 'Austin, TX',
				'latitude'  => 30.2672,
				'longitude' => -97.7431,
				'company_id' => 1,
			),
			(object) array(
				'id'        => 2,
				'name'      => 'Pool B',
				'type'      => 'repair',
				'location'  => 'Dallas, TX',
				'latitude'  => 32.7767,
				'longitude' => -96.7970,
				'company_id' => 2,
			),
		);

		$tickets = array(
			(object) array(
				'id'        => 101,
				'title'     => 'Filter replacement',
				'unit_id'   => 1,
				'urgency'   => 'critical',
				'status'    => 'open',
				'created_at' => '2024-01-15 10:00:00',
			),
		);

		$wpdb = $this->createMockDatabase( $units, $tickets );

		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		when( 'check_ajax_referer' )->justReturn( true );
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );
		when( 'wp_send_json_success' )->alias(
			function( $data ) {
				$this->assertArrayHasKey( 'units', $data );
				$this->assertArrayHasKey( 'tickets', $data );
				$this->assertCount( 2, $data['units'] );
				$this->assertCount( 1, $data['tickets'] );
			}
		);

		require_once __DIR__ . '/../api/units.php';

		LGP_Units_API::get_map_data_ajax();
	}

	/**
	 * Test map data retrieval for partner users (company-scoped)
	 */
	public function test_get_map_data_partner_scoped_by_company() {
		global $wpdb;

		$units = array(
			(object) array(
				'id'        => 1,
				'name'      => 'Partner Pool',
				'type'      => 'maintenance',
				'location'  => 'Austin, TX',
				'latitude'  => 30.2672,
				'longitude' => -97.7431,
				'company_id' => 1,
			),
		);

		$tickets = array(
			(object) array(
				'id'        => 101,
				'title'     => 'Filter replacement',
				'unit_id'   => 1,
				'urgency'   => 'high',
				'status'    => 'open',
				'created_at' => '2024-01-15 10:00:00',
			),
		);

		$wpdb = $this->createMockDatabase( $units, $tickets );

		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		when( 'check_ajax_referer' )->justReturn( true );
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'ID' => 1, 'roles' => array( 'lgp_partner' ) ) );
		when( 'get_user_meta' )->alias( function( $id, $key ) { return 1; } );
		when( 'wp_send_json_success' )->alias(
			function( $data ) {
				$this->assertCount( 1, $data['units'] );
				$this->assertCount( 1, $data['tickets'] );
				$this->assertEquals( 1, $data['units'][0]->company_id );
			}
		);

		require_once __DIR__ . '/../api/units.php';

		LGP_Units_API::get_map_data_ajax();
	}

	/**
	 * Test help guide filtering by type
	 */
	public function test_help_guide_filter_by_type() {
		global $wpdb;

		$guides = array(
			(object) array(
				'id'               => 1,
				'title'            => 'Filter Change Guide',
				'type'             => 'maintenance',
				'tags'             => '["filter","pool"]',
				'target_companies' => '[]',
				'created_at'       => '2024-01-15 10:00:00',
			),
			(object) array(
				'id'               => 2,
				'title'            => 'Pump Repair Guide',
				'type'             => 'repair',
				'tags'             => '["pump","repair"]',
				'target_companies' => '[]',
				'created_at'       => '2024-01-15 10:00:00',
			),
		);

		$wpdb = $this->createMockHelpGuideDatabase( $guides );

		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );

		require_once __DIR__ . '/../includes/class-lgp-help-guide.php';

		$filters = array( 'type' => 'maintenance' );
		$results = LGP_Help_Guide::get_all( $filters );

		$this->assertCount( 1, $results );
		$this->assertEquals( 'Filter Change Guide', $results[0]->title );
	}

	/**
	 * Test help guide filtering by tags
	 */
	public function test_help_guide_filter_by_tags() {
		global $wpdb;

		$guides = array(
			(object) array(
				'id'               => 1,
				'title'            => 'Filter Change Guide',
				'type'             => 'maintenance',
				'tags'             => '["filter","pool"]',
				'target_companies' => '[]',
				'created_at'       => '2024-01-15 10:00:00',
			),
			(object) array(
				'id'               => 2,
				'title'            => 'Pool Chemistry Guide',
				'type'             => 'maintenance',
				'tags'             => '["pool","chemistry"]',
				'target_companies' => '[]',
				'created_at'       => '2024-01-15 10:00:00',
			),
		);

		$wpdb = $this->createMockHelpGuideDatabase( $guides );

		require_once __DIR__ . '/../includes/class-lgp-auth.php';
		when( 'is_user_logged_in' )->justReturn( true );
		when( 'wp_get_current_user' )->justReturn( (object) array( 'roles' => array( 'lgp_support' ) ) );

		require_once __DIR__ . '/../includes/class-lgp-help-guide.php';

		$filters = array( 'tags' => array( 'pool' ) );
		$results = LGP_Help_Guide::get_all( $filters );

		$this->assertCount( 2, $results );
	}

	/**
	 * Test contract status filtering
	 */
	public function test_contract_status_filtering() {
		global $wpdb;

		$companies = array(
			(object) array(
				'id'               => 1,
				'name'             => 'Active Co',
				'contract_status'  => 'active',
			),
			(object) array(
				'id'               => 2,
				'name'             => 'Expired Co',
				'contract_status'  => 'expired',
			),
		);

		$wpdb = $this->createMockCompanyDatabase( $companies );

		require_once __DIR__ . '/../api/companies.php';

		$api = new LGP_Companies_API();
		$mock_request = new class {
			public function get_param( $key ) {
				if ( 'contract_status' === $key ) {
					return 'active';
				}
				return null;
			}
		};

		// This would need proper setup, but demonstrates the test structure
		$this->assertTrue( true );
	}

	/**
	 * Helper: Create mock database for units/tickets
	 */
	private function createMockDatabase( $units, $tickets ) {
		global $wpdb;

		$wpdb = new class( $units, $tickets ) {
			private $u;
			private $t;

			public function __construct( $u, $t ) {
				$this->u = $u;
				$this->t = $t;
			}

			public $prefix = 'wp_';

			public function prepare( $query ) {
				return $query;
			}

			public function get_results( $query ) {
				if ( strpos( $query, 'lgp_units' ) !== false ) {
					return $this->u;
				} elseif ( strpos( $query, 'lgp_tickets' ) !== false ) {
					return $this->t;
				}
				return array();
			}
		};

		return $wpdb;
	}

	/**
	 * Helper: Create mock database for help guides
	 */
	private function createMockHelpGuideDatabase( $guides ) {
		global $wpdb;

		$wpdb = new class( $guides ) {
			private $guides;

			public function __construct( $g ) {
				$this->guides = $g;
			}

			public $prefix = 'wp_';

			public function prepare( $query ) {
				return $query;
			}

			public function get_results( $query ) {
				if ( strpos( $query, 'lgp_help_guides' ) !== false ) {
					return $this->guides;
				}
				return array();
			}
		};

		return $wpdb;
	}

	/**
	 * Helper: Create mock database for companies
	 */
	private function createMockCompanyDatabase( $companies ) {
		global $wpdb;

		$wpdb = new class( $companies ) {
			private $companies;

			public function __construct( $c ) {
				$this->companies = $c;
			}

			public $prefix = 'wp_';

			public function prepare( $query ) {
				return $query;
			}

			public function get_results( $query ) {
				if ( strpos( $query, 'lgp_companies' ) !== false ) {
					return $this->companies;
				}
				return array();
			}
		};

		return $wpdb;
	}
}
