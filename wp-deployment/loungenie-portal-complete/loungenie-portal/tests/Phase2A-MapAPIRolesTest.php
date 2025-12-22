<?php
/**
 * Map API Role-Based Access Tests (Phase 2A)
 *
 * Tests role-based filtering in Map API:
 * - Support role sees all units on map
 * - Partner role sees only own company's units
 * - Unauthorized access denied
 *
 * @package LounGenie Portal
 * @subpackage Tests
 */

final class Phase2A_MapAPIRolesTest extends WPTestCase {

	protected $support_user;
	protected $partner_user;
	protected $company1_id;
	protected $company2_id;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		global $wpdb;

		// Create companies
		$companies_table = $wpdb->prefix . 'lgp_companies';
		$wpdb->insert(
			$companies_table,
			array(
				'name'                    => 'Map Test Company 1',
				'primary_contract_status' => 'active',
			)
		);
		$this->company1_id = $wpdb->insert_id;

		$wpdb->insert(
			$companies_table,
			array(
				'name'                    => 'Map Test Company 2',
				'primary_contract_status' => 'active',
			)
		);
		$this->company2_id = $wpdb->insert_id;

		// Create users
		$this->support_user = $this->factory->user->create(
			array(
				'role'       => 'lgp_support',
				'user_login' => 'support_map_test',
			)
		);

		$this->partner_user = $this->factory->user->create(
			array(
				'role'       => 'lgp_partner',
				'user_login' => 'partner_map_test',
			)
		);
		update_user_meta( $this->partner_user, 'lgp_company_id', $this->company1_id );

		// Add geolocated units for company 1
		$units_table = $wpdb->prefix . 'lgp_units';
		for ( $i = 1; $i <= 3; $i++ ) {
			$wpdb->insert(
				$units_table,
				array(
					'company_id'  => $this->company1_id,
					'unit_number' => 'GEO-C1-' . $i,
					'latitude'    => 40.7128 + ( $i * 0.01 ),
					'longitude'   => -74.0060 + ( $i * 0.01 ),
					'status'      => 'active',
				)
			);
		}

		// Add geolocated units for company 2
		for ( $i = 1; $i <= 2; $i++ ) {
			$wpdb->insert(
				$units_table,
				array(
					'company_id'  => $this->company2_id,
					'unit_number' => 'GEO-C2-' . $i,
					'latitude'    => 34.0522 + ( $i * 0.01 ),
					'longitude'   => -118.2437 + ( $i * 0.01 ),
					'status'      => 'active',
				)
			);
		}
	}

	/**
	 * Test: Support user sees all geolocated units
	 */
	public function test_support_sees_all_map_units() {
		wp_set_current_user( $this->support_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'units', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertEquals( 5, $data['total'], 'Support should see all 5 geolocated units' );
		$this->assertCount( 5, $data['units'], 'Units array should contain 5 items' );
		$this->assertEquals( 'support', $data['role'] );
	}

	/**
	 * Test: Partner user sees only their company's units
	 */
	public function test_partner_sees_only_own_company_map_units() {
		wp_set_current_user( $this->partner_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'units', $data );
		$this->assertEquals( 3, $data['total'], 'Partner should see only their 3 units' );
		$this->assertCount( 3, $data['units'], 'Units array should contain 3 items' );
		$this->assertEquals( 'partner', $data['role'] );

		// Verify all returned units belong to partner's company
		foreach ( $data['units'] as $unit ) {
			$this->assertEquals( $this->company1_id, $unit->company_id, 'All units should belong to partner company' );
		}
	}

	/**
	 * Test: Unauthorized user gets 401
	 */
	public function test_unauthorized_map_access_denied() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertEquals( 'unauthorized', $data['code'] );
	}

	/**
	 * Test: Non-portal user gets 403
	 */
	public function test_non_portal_user_map_access_denied() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertEquals( 'forbidden', $data['code'] );
	}

	/**
	 * Test: Partner without company_id gets error
	 */
	public function test_partner_without_company_map_error() {
		$partner_no_company = $this->factory->user->create( array( 'role' => 'lgp_partner' ) );
		wp_set_current_user( $partner_no_company );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertEquals( 'invalid_company', $data['code'] );
	}

	/**
	 * Test: Map units include required geolocation fields
	 */
	public function test_map_units_have_coordinates() {
		wp_set_current_user( $this->support_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$data = $response->get_data();

		foreach ( $data['units'] as $unit ) {
			$this->assertObjectHasProperty( 'latitude', $unit, 'Unit should have latitude' );
			$this->assertObjectHasProperty( 'longitude', $unit, 'Unit should have longitude' );
			$this->assertNotNull( $unit->latitude, 'Latitude should not be null' );
			$this->assertNotNull( $unit->longitude, 'Longitude should not be null' );
		}
	}

	/**
	 * Test: Audit logging for map access
	 */
	public function test_map_access_logged() {
		global $wpdb;
		
		wp_set_current_user( $this->partner_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/map/units' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify audit log
		$audit_table = $wpdb->prefix . 'lgp_audit_log';
		$log_entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $audit_table WHERE user_id = %d AND action = %s ORDER BY created_at DESC LIMIT 1",
				$this->partner_user,
				'map_access'
			)
		);

		$this->assertNotNull( $log_entry, 'Map access should be logged' );
		$this->assertEquals( 'map_access', $log_entry->action );
		
		$metadata = json_decode( $log_entry->meta, true );
		$this->assertArrayHasKey( 'role', $metadata );
		$this->assertEquals( 'partner', $metadata['role'] );
		$this->assertArrayHasKey( 'units_returned', $metadata );
		$this->assertEquals( 3, $metadata['units_returned'] );
	}

	/**
	 * Cleanup
	 */
	public function tearDown(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_units WHERE company_id IN ({$this->company1_id}, {$this->company2_id})" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_companies WHERE id IN ({$this->company1_id}, {$this->company2_id})" );

		parent::tearDown();
	}
}
