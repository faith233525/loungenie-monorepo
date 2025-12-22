<?php
/**
 * Dashboard API Role-Based Access Tests (Phase 2A)
 *
 * Tests role-based filtering in Dashboard API:
 * - Support role sees all companies
 * - Partner role sees only own company
 *
 * @package LounGenie Portal
 * @subpackage Tests
 */

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

final class Phase2A_DashboardAPIRolesTest extends WPTestCase {

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
				'name'               => 'Test Company 1',
				'contact_email'      => 'company1@test.com',
				'primary_contract_status' => 'active',
			)
		);
		$this->company1_id = $wpdb->insert_id;

		$wpdb->insert(
			$companies_table,
			array(
				'name'               => 'Test Company 2',
				'contact_email'      => 'company2@test.com',
				'primary_contract_status' => 'active',
			)
		);
		$this->company2_id = $wpdb->insert_id;

		// Create Support user
		$this->support_user = $this->factory->user->create(
			array(
				'role'       => 'lgp_support',
				'user_login' => 'support_test',
				'user_email' => 'support@test.com',
			)
		);

		// Create Partner user for company1
		$this->partner_user = $this->factory->user->create(
			array(
				'role'       => 'lgp_partner',
				'user_login' => 'partner_test',
				'user_email' => 'partner@test.com',
			)
		);
		update_user_meta( $this->partner_user, 'lgp_company_id', $this->company1_id );

		// Add some test units for metrics
		$units_table = $wpdb->prefix . 'lgp_units';
		
		// 10 units for company 1
		for ( $i = 1; $i <= 10; $i++ ) {
			$wpdb->insert(
				$units_table,
				array(
					'company_id'  => $this->company1_id,
					'unit_number' => 'UNIT-C1-' . str_pad( $i, 3, '0', STR_PAD_LEFT ),
					'status'      => 'active',
				)
			);
		}

		// 5 units for company 2
		for ( $i = 1; $i <= 5; $i++ ) {
			$wpdb->insert(
				$units_table,
				array(
					'company_id'  => $this->company2_id,
					'unit_number' => 'UNIT-C2-' . str_pad( $i, 3, '0', STR_PAD_LEFT ),
					'status'      => 'active',
				)
			);
		}

		// Add some test tickets
		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';

		// Create service request for company 1
		$wpdb->insert(
			$requests_table,
			array(
				'company_id'   => $this->company1_id,
				'request_type' => 'maintenance',
				'priority'     => 'high',
				'status'       => 'pending',
			)
		);
		$sr1_id = $wpdb->insert_id;

		// Create ticket for company 1
		$wpdb->insert(
			$tickets_table,
			array(
				'service_request_id' => $sr1_id,
				'status'             => 'open',
				'thread_history'     => wp_json_encode( array() ),
			)
		);

		// Create service request for company 2
		$wpdb->insert(
			$requests_table,
			array(
				'company_id'   => $this->company2_id,
				'request_type' => 'general',
				'priority'     => 'normal',
				'status'       => 'pending',
			)
		);
		$sr2_id = $wpdb->insert_id;

		// Create ticket for company 2
		$wpdb->insert(
			$tickets_table,
			array(
				'service_request_id' => $sr2_id,
				'status'             => 'open',
				'thread_history'     => wp_json_encode( array() ),
			)
		);
	}

	/**
	 * Test: Support user sees all units across all companies
	 */
	public function test_support_sees_all_units() {
		wp_set_current_user( $this->support_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Support should get 200 response' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'total_units', $data );
		$this->assertEquals( 15, $data['total_units'], 'Support should see all 15 units' );
		$this->assertEquals( 'support', $data['role'], 'Role should be identified as support' );
		$this->assertNull( $data['company_id'], 'Support should not have company_id' );
	}

	/**
	 * Test: Partner user sees only their company's units
	 */
	public function test_partner_sees_only_own_company_units() {
		wp_set_current_user( $this->partner_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status(), 'Partner should get 200 response' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'total_units', $data );
		$this->assertEquals( 10, $data['total_units'], 'Partner should see only their 10 units' );
		$this->assertEquals( 'partner', $data['role'], 'Role should be identified as partner' );
		$this->assertEquals( $this->company1_id, $data['company_id'], 'Company ID should match' );
	}

	/**
	 * Test: Support user sees all tickets across all companies
	 */
	public function test_support_sees_all_tickets() {
		wp_set_current_user( $this->support_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'active_tickets', $data );
		$this->assertEquals( 2, $data['active_tickets'], 'Support should see all 2 active tickets' );
	}

	/**
	 * Test: Partner user sees only their company's tickets
	 */
	public function test_partner_sees_only_own_company_tickets() {
		wp_set_current_user( $this->partner_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'active_tickets', $data );
		$this->assertEquals( 1, $data['active_tickets'], 'Partner should see only their 1 active ticket' );
	}

	/**
	 * Test: Unauthorized user gets 401
	 */
	public function test_unauthorized_user_denied() {
		wp_set_current_user( 0 ); // No user logged in

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status(), 'Unauthenticated request should return 401' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'unauthorized', $data['code'] );
	}

	/**
	 * Test: User without portal roles gets 403
	 */
	public function test_non_portal_user_denied() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status(), 'Non-portal user should get 403' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'forbidden', $data['code'] );
	}

	/**
	 * Test: Partner without company_id gets error
	 */
	public function test_partner_without_company_id_gets_error() {
		$partner_no_company = $this->factory->user->create(
			array(
				'role'       => 'lgp_partner',
				'user_login' => 'partner_no_company',
			)
		);
		// Deliberately don't set lgp_company_id meta

		wp_set_current_user( $partner_no_company );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status(), 'Partner without company should get 400' );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'invalid_company', $data['code'] );
	}

	/**
	 * Test: Audit logging for dashboard access
	 */
	public function test_dashboard_access_logged() {
		global $wpdb;
		
		wp_set_current_user( $this->support_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify audit log entry exists
		$audit_table = $wpdb->prefix . 'lgp_audit_log';
		$log_entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $audit_table WHERE user_id = %d AND event_type = %s ORDER BY created_at DESC LIMIT 1",
				$this->support_user,
				'dashboard_access'
			)
		);

		$this->assertNotNull( $log_entry, 'Audit log entry should exist' );
		$this->assertEquals( $this->support_user, $log_entry->user_id );
		$this->assertEquals( 'dashboard_access', $log_entry->action );
	}

	/**
	 * Test: Average resolution time calculation
	 */
	public function test_average_resolution_calculation() {
		global $wpdb;
		
		// Create resolved tickets with known resolution times
		$tickets_table = $wpdb->prefix . 'lgp_tickets';
		$requests_table = $wpdb->prefix . 'lgp_service_requests';

		// Create service request
		$wpdb->insert(
			$requests_table,
			array(
				'company_id'   => $this->company1_id,
				'request_type' => 'general',
				'status'       => 'resolved',
			)
		);
		$sr_id = $wpdb->insert_id;

		// Create resolved ticket (created 2 hours ago, resolved now)
		$created_time = date( 'Y-m-d H:i:s', strtotime( '-2 hours' ) );
		$resolved_time = current_time( 'mysql' );

		$wpdb->insert(
			$tickets_table,
			array(
				'service_request_id' => $sr_id,
				'status'             => 'resolved',
				'created_at'         => $created_time,
				'updated_at'         => $resolved_time,
				'thread_history'     => wp_json_encode( array() ),
			)
		);

		wp_set_current_user( $this->partner_user );

		$request  = new WP_REST_Request( 'GET', '/lgp/v1/dashboard' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'average_resolution', $data );
		$this->assertIsFloat( $data['average_resolution'] );
		$this->assertGreaterThan( 0, $data['average_resolution'], 'Average resolution should be > 0' );
	}

	/**
	 * Cleanup after tests
	 */
	public function tearDown(): void {
		global $wpdb;

		// Clean up test data
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_units WHERE company_id IN ({$this->company1_id}, {$this->company2_id})" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_tickets WHERE id > 0" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_service_requests WHERE company_id IN ({$this->company1_id}, {$this->company2_id})" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}lgp_companies WHERE id IN ({$this->company1_id}, {$this->company2_id})" );

		parent::tearDown();
	}
}
