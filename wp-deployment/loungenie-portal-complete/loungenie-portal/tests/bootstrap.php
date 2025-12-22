<?php

/**
 * PHPUnit Bootstrap
 */

// Ensure Composer autoload is available
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
	fwrite(STDERR, "Composer autoload not found. Run 'composer install' in loungenie-portal/.\n");
}
require_once $autoload;

// Minimal constants that some code may expect
if (!defined('LGP_VERSION')) {
	define('LGP_VERSION', '1.0.0-test');
}
if (!defined('LGP_PLUGIN_DIR')) {
	define('LGP_PLUGIN_DIR', __DIR__ . '/../');
}
if (!defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/../');
}

// Define common WordPress return type constants used by $wpdb helpers
if (!defined('OBJECT')) {
	define('OBJECT', 'OBJECT');
}
if (!defined('OBJECT_K')) {
	define('OBJECT_K', 'OBJECT_K');
}
if (!defined('ARRAY_A')) {
	define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
	define('ARRAY_N', 'ARRAY_N');
}

// Define a named WPDB stub class for better PHPUnit compatibility
class WP_Database_Stub
{
	public $prefix = 'wp_';
	public $insert_id = 0;

	// In-memory tables
	private $companies = [];
	private $units = [];
	private $service_requests = [];
	private $tickets = [];
	private $audit_log = [];

	// Auto-increment counters
	private $auto = [
		'lgp_companies' => 1,
		'lgp_units' => 1,
		'lgp_service_requests' => 1,
		'lgp_tickets' => 1,
		'lgp_audit_log' => 1,
	];

	public function get_charset_collate()
	{
		return 'CHARSET';
	}
	public function query($sql)
	{
		return true;
	}
	public function esc_like($s)
	{
		return $s;
	}
	public function prepare($q, ...$args)
	{
		// Minimal replacement; tests generally ignore placeholders in mocks
		if (strpos($q, '%d') !== false || strpos($q, '%s') !== false) {
			foreach ($args as $a) {
				$q = preg_replace('/%d|%s/', is_numeric($a) ? (int)$a : addslashes((string)$a), $q, 1);
			}
		}
		return $q;
	}

	public function insert($table, $data)
	{
		$name = $this->normalize($table);
		$id = $this->auto[$name] ?? 1;
		$this->auto[$name] = $id + 1;
		$data = $this->withTimestamps($data);
		$data['id'] = $id;
		$this->insert_id = $id;
		switch ($name) {
			case 'lgp_companies':
				$this->companies[$id] = (object)$data;
				break;
			case 'lgp_units':
				$this->units[$id] = (object)$data;
				break;
			case 'lgp_service_requests':
				$this->service_requests[$id] = (object)$data;
				break;
			case 'lgp_tickets':
				$this->tickets[$id] = (object)$data;
				break;
			case 'lgp_audit_log':
				$this->audit_log[$id] = (object)$data;
				break;
			default:
				break;
		}
		return 1;
	}

	public function update($table, $data, $where)
	{
		$name = $this->normalize($table);
		if ($name === 'lgp_tickets' && isset($where['id'])) {
			$id = (int)$where['id'];
			if (isset($this->tickets[$id])) {
				$this->tickets[$id] = (object) array_merge((array)$this->tickets[$id], $data);
			}
		}
		return 1;
	}
	public function delete($table, $where, $where_format = [])
	{
		return 1;
	}

	public function get_var($sql)
	{
		$sql = trim($sql);
		if (stripos($sql, 'FROM ' . $this->prefix . 'lgp_units') !== false) {
			$company = $this->extractCompanyId($sql);
			$units = $this->units;
			if ($company !== null) {
				$units = array_filter($units, fn($u) => isset($u->company_id) && (int)$u->company_id === (int)$company);
			}
			return (int)count($units);
		}
		if (stripos($sql, 'AVG(TIMESTAMPDIFF') !== false && stripos($sql, 'FROM ' . $this->prefix . 'lgp_tickets') !== false) {
			$company = $this->extractCompanyId($sql);
			$tickets = $this->filterTicketsByCompany($company);
			$tickets = array_filter($tickets, fn($t) => in_array($t->status ?? '', ['resolved', 'closed'], true) && !empty($t->updated_at) && !empty($t->created_at));
			if (!count($tickets)) return null;
			$sum = 0;
			$n = 0;
			foreach ($tickets as $t) {
				$sum += max(0, (strtotime($t->updated_at) - strtotime($t->created_at)) / 3600);
				$n++;
			}
			return $n ? $sum / $n : null;
		}
		if (stripos($sql, 'FROM ' . $this->prefix . 'lgp_tickets') !== false && stripos($sql, 'COUNT(*)') !== false) {
			$company = $this->extractCompanyId($sql);
			$tickets = $this->filterTicketsByCompany($company);
			if (stripos($sql, 'status NOT IN') !== false) {
				$tickets = array_filter($tickets, fn($t) => !in_array($t->status ?? '', ['resolved', 'closed'], true));
			}
			if (stripos($sql, 'DATE(updated_at) = CURDATE()') !== false) {
				$today = date('Y-m-d');
				$tickets = array_filter($tickets, fn($t) => in_array($t->status ?? '', ['resolved', 'closed'], true) && substr((string)$t->updated_at, 0, 10) === $today);
			}
			return (int)count($tickets);
		}

		// Audit log COUNT by action
		if (stripos($sql, 'FROM ' . $this->prefix . 'lgp_audit_log') !== false && stripos($sql, 'COUNT(') !== false) {
			$action = $this->extractStringAfter($sql, 'action =');
			$uid    = $this->extractNumberAfter($sql, 'user_id =');
			$cnt    = 0;
			foreach ($this->audit_log as $row) {
				$matchesAction = empty($action) || ((isset($row->action) ? $row->action : '') === $action);
				$matchesUser   = ($uid === 0) || ((int)$row->user_id === (int)$uid);
				if ($matchesAction && $matchesUser) {
					$cnt++;
				}
			}
			return (int)$cnt;
		}
		return 0;
	}

	public function get_results($sql)
	{
		// Map units query for map endpoint
		if (stripos($sql, 'FROM ' . $this->prefix . 'lgp_units') !== false) {
			$units = array_values($this->units);
			if (preg_match('/u\.company_id\s*=\s*(\d+)/i', $sql, $m)) {
				$cid   = (int) $m[1];
				$units = array_filter($units, fn($u) => (int)($u->company_id ?? 0) === $cid);
			}
			$units = array_filter($units, fn($u) => isset($u->latitude, $u->longitude));
			return array_values($units);
		}

		if (stripos($sql, 'FROM ' . $this->prefix . 'lgp_companies') !== false) {
			return array_values($this->companies);
		}
		return [];
	}
	public function get_row($sql)
	{
		// Support audit log check in tests
		if (stripos($sql, $this->prefix . 'lgp_audit_log') !== false) {
			$uid = $this->extractNumberAfter($sql, 'user_id =');
			// Try both 'action' and 'event_type' column names
			$etype = $this->extractStringAfter($sql, 'event_type =');
			if (empty($etype)) {
				$etype = $this->extractStringAfter($sql, 'action =');
			}
			foreach (array_reverse($this->audit_log) as $row) {
				$matchesUser  = ($uid === 0) || ((int)$row->user_id === (int)$uid);
				$matchesEvent = empty($etype) || (isset($row->action) && $row->action === $etype);
				if ($matchesUser && $matchesEvent) {
					return $row;
				}
			}
			// Fallback: return last log if no filters or no match
			if (empty($uid) && empty($etype) && !empty($this->audit_log)) {
				$logs = array_values($this->audit_log);
				return end($logs);
			}
			return null;
		}

		// Tickets by ID
		if (stripos($sql, $this->prefix . 'lgp_tickets') !== false) {
			$id = $this->extractNumberAfter($sql, 'id =');
			return $this->tickets[$id] ?? null;
		}

		// Service requests by ID
		if (stripos($sql, $this->prefix . 'lgp_service_requests') !== false) {
			$id = $this->extractNumberAfter($sql, 'id =');
			return $this->service_requests[$id] ?? null;
		}
		return null;
	}

	private function normalize($table)
	{
		return str_replace($this->prefix, '', $table);
	}
	private function withTimestamps($data)
	{
		$now = date('Y-m-d H:i:s');
		if (!isset($data['created_at'])) $data['created_at'] = $now;
		if (!isset($data['updated_at'])) $data['updated_at'] = $now;
		return $data;
	}
	private function extractCompanyId($sql)
	{
		if (preg_match('/company_id\s*=\s*(\d+)/i', $sql, $m)) return (int)$m[1];
		return null;
	}
	private function extractNumberAfter($sql, $token)
	{
		if (preg_match('/' . preg_quote($token, '/') . '\s*(\d+)/', $sql, $m)) return (int)$m[1];
		return 0;
	}
	private function extractStringAfter($sql, $token)
	{
		if (preg_match('/' . preg_quote($token, '/') . '\s*\'?([\w_-]+)\'?/i', $sql, $m)) {
			return $m[1];
		}
		return '';
	}
	private function filterTicketsByCompany($company)
	{
		// Map tickets via service_request_id to company_id
		$map = [];
		foreach ($this->service_requests as $sr) {
			$map[$sr->id] = $sr->company_id ?? null;
		}
		$t = array_values($this->tickets);
		if ($company !== null) {
			$t = array_filter($t, function ($row) use ($map, $company) {
				$srid = $row->service_request_id ?? null;
				return isset($map[$srid]) && (int)$map[$srid] === (int)$company;
			});
		}
		return $t;
	}
}

// Provide a minimal wpdb class so PHPUnit tests can create mocks like $this->createMock('wpdb')
if (!class_exists('wpdb')) {
	class wpdb
	{
		public $prefix = 'wp_';
		public function prepare($query, ...$args)
		{
			return $query;
		}
		public function get_results($query)
		{
			return [];
		}
		public function get_row($query)
		{
			return null;
		}
		public function get_var($query)
		{
			return null;
		}
		public function insert($table, $data)
		{
			return 1;
		}
		public function update($table, $data, $where)
		{
			return 1;
		}
		public function delete($table, $where, $where_format = [])
		{
			return 1;
		}
		public function query($query)
		{
			return true;
		}
	}
}

// Provide a richer $wpdb stub suitable for our tests
global $wpdb;
if (!isset($wpdb)) {
	$wpdb = new WP_Database_Stub();
}

// Output basic info to help during local runs
fwrite(STDOUT, "LounGenie Portal Test Bootstrap\n");
fwrite(STDOUT, "PHP Version: " . PHP_VERSION . "\n");

// ===== Global test state for user/meta simulation =====
global $test_current_user_id, $test_user_meta;
$test_current_user_id = 0;
$test_user_meta = [];  // user_id => [meta_key => meta_value]

// ===== IMPORTANT: DO NOT DEFINE WordPress FUNCTIONS HERE =====
// Brain Monkey uses Patchwork to intercept function calls,
// and Patchwork requires functions to be defined AFTER it loads.
// Each test file will mock the functions it needs via Monkey\Functions\expect()
// 
// STUB FUNCTIONS: Only define stub functions that tests cannot mock (those not intercepted by Patchwork)
// These are typically functions used at file-load time (not in test methods)

// Note: add_action/add_filter are intentionally NOT defined here to allow
// Brain Monkey/Patchwork to intercept these functions during tests.
if (!function_exists('register_rest_route')) {
	function register_rest_route($namespace, $route, $args)
	{
		// Record routes for tests that assert uniqueness
		global $test_registered_routes;
		if (!isset($test_registered_routes) || !is_array($test_registered_routes)) {
			$test_registered_routes = [];
		}

		// Methods may be provided as a string or array in $args['methods']
		$methods = [];
		if (is_array($args) && isset($args['methods'])) {
			$m = $args['methods'];
			if (is_array($m)) {
				$methods = $m;
			} else {
				$methods = [$m];
			}
		} else {
			// Default to GET when not explicitly provided
			$methods = ['GET'];
		}

		foreach ($methods as $meth) {
			$method = strtoupper(is_string($meth) ? $meth : (string)$meth);
			$key = $namespace . ' ' . $route . ' ' . $method;
			$test_registered_routes[] = $key;
		}

		return true;
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code($response)
	{
		return isset($response['response']['code']) ? $response['response']['code'] : 200;
	}
}

// NOTE: wp_set_current_user(), get_current_user_id(), is_user_logged_in(), and wp_get_current_user()
// are NOT defined here - they are handled by Brain Monkey's when() calls in tests.
// Patchwork needs these to be undefined until after it loads so tests can mock them.

if (!function_exists('wp_create_user')) {
	function wp_create_user($username, $password, $email = '')
	{
		// Mock user creation - just return a new user ID
		return mt_rand(1, 99999);
	}
}

if (!class_exists('WP_User')) {
	class WP_User
	{
		public $ID = 0;
		public $roles = [];
		public $caps = [];
		public $data;
		public $user_login = '';
		public $user_email = '';
		public $display_name = '';

		public function __construct($id = 0)
		{
			$this->ID = $id;
			if ($id) {
				$this->user_login   = 'user_' . $id;
				$this->user_email   = 'user_' . $id . '@test.local';
				$this->display_name = $this->user_login;
				$this->data         = (object) ['ID' => $id, 'user_login' => $this->user_login, 'user_email' => $this->user_email, 'display_name' => $this->display_name];
			} else {
				$this->data         = (object) ['ID' => 0, 'user_login' => '', 'user_email' => '', 'display_name' => ''];
			}
		}

		public function has_cap($cap)
		{
			return in_array($cap, $this->caps, true);
		}
	}
}

// NOTE: current_time(), update_user_meta() and get_user_meta() are handled by Brain Monkey when() calls.
// Not defining them here to allow Patchwork to patch them for tests that need to mock them.

// Stub REST API classes
if (!class_exists('WP_REST_Request')) {
	class WP_REST_Request
	{
		private $method = 'GET';
		private $route = '/';
		private $params = [];

		public function __construct($method = 'GET', $route = '')
		{
			$this->method = $method;
			$this->route = $route;
		}

		public function get_param($name)
		{
			return $this->params[$name] ?? null;
		}

		public function set_param($name, $value)
		{
			$this->params[$name] = $value;
		}

		public function get_method()
		{
			return $this->method;
		}

		public function get_route()
		{
			return $this->route;
		}
	}
}

if (!class_exists('WP_REST_Response')) {
	class WP_REST_Response
	{
		public $data;
		public $status;

		public function __construct($data = null, $status = 200)
		{
			$this->data = $data;
			$this->status = $status;
		}

		public function get_status()
		{
			return $this->status;
		}

		public function get_data()
		{
			return $this->data;
		}
	}
}

if (!function_exists('rest_do_request')) {
	function rest_do_request($request)
	{
		// Simple REST dispatcher for testing
		$route = $request->get_route();
		$method = $request->get_method();

		// Dashboard endpoint
		if (strpos($route, '/lgp/v1/dashboard') !== false) {
			return _rest_dispatch_dashboard($request, $method);
		}

		// Map units endpoint
		if ($method === 'GET' && strpos($route, '/lgp/v1/map/units') !== false) {
			return _rest_dispatch_map_units($request);
		}

		// Tickets endpoint
		if (strpos($route, '/lgp/v1/tickets') !== false) {
			return _rest_dispatch_tickets($request, $method);
		}

		return new WP_REST_Response(['error' => 'Endpoint not found'], 404);
	}

	function _rest_dispatch_dashboard($request, $method)
	{
		if ($method !== 'GET') {
			return new WP_REST_Response(['error' => 'Method not allowed'], 405);
		}

		// Require the dashboard API class if not already loaded
		if (!class_exists('LGP_Dashboard_API')) {
			require_once ABSPATH . 'api/dashboard.php';
		}

		$api = new LGP_Dashboard_API();

		// Check permission first
		if (!$api->check_portal_access()) {
			// Determine if it's unauthenticated (401) or forbidden (403)
			if (!is_user_logged_in()) {
				return new WP_REST_Response(
					['code' => 'unauthorized', 'message' => 'Authentication required'],
					401
				);
			} else {
				return new WP_REST_Response(
					['code' => 'forbidden', 'message' => 'Insufficient permissions'],
					403
				);
			}
		}

		// Call the endpoint
		$response_data = $api->get_metrics($request);

		// Check if it's an error
		if (is_wp_error($response_data)) {
			return new WP_REST_Response(
				['code' => $response_data->get_error_code(), 'message' => $response_data->get_error_message()],
				$response_data->get_error_data()['status'] ?? 400
			);
		}

		// Extract data from response if it's a response object
		$data = $response_data;
		if (is_object($response_data) && method_exists($response_data, 'get_data')) {
			$data = $response_data->get_data();
		}

		return new WP_REST_Response($data, 200);
	}

	function _rest_dispatch_map_units($request)
	{
		// Require the map API class if not already loaded
		if (!class_exists('LGP_Map_API')) {
			require_once ABSPATH . 'api/map.php';
		}

		$api = new LGP_Map_API();

		// Call the endpoint
		$response_data = $api->get_units($request);

		// Check if it's an error
		if (is_wp_error($response_data)) {
			return new WP_REST_Response(
				['code' => $response_data->get_error_code(), 'message' => $response_data->get_error_message()],
				$response_data->get_error_data()['status'] ?? 400
			);
		}

		// Extract data from response if it's a response object
		$data = $response_data;
		if (is_object($response_data) && method_exists($response_data, 'get_data')) {
			$data = $response_data->get_data();
		}

		return new WP_REST_Response($data, 200);
	}

	function _rest_dispatch_tickets($request, $method)
	{
		// Require the tickets API class if not already loaded
		if (!class_exists('LGP_Tickets_API')) {
			require_once ABSPATH . 'api/tickets.php';
		}

		// Route to appropriate method
		if ($method === 'GET') {
			// Check if it's a single ticket or list
			$route = $request->get_route();
			if (preg_match('#/lgp/v1/tickets/(\d+)#', $route, $matches)) {
				// Single ticket - set the ID parameter
				$request->set_param('id', $matches[1]);
				$response_data = LGP_Tickets_API::get_ticket($request);
			} else {
				// List tickets
				$response_data = LGP_Tickets_API::get_tickets($request);
			}
		} elseif ($method === 'POST') {
			// Create ticket
			$response_data = LGP_Tickets_API::create_ticket($request);
		} elseif ($method === 'PUT') {
			// Update ticket - extract ID from route
			$route = $request->get_route();
			if (preg_match('#/lgp/v1/tickets/(\d+)#', $route, $matches)) {
				$request->set_param('id', $matches[1]);
				$response_data = LGP_Tickets_API::update_ticket($request);
			} else {
				return new WP_REST_Response(['error' => 'Invalid ticket ID'], 400);
			}
		} else {
			return new WP_REST_Response(['error' => 'Method not allowed'], 405);
		}

		// Check if it's an error
		if (is_wp_error($response_data)) {
			return new WP_REST_Response(
				['code' => $response_data->get_error_code(), 'message' => $response_data->get_error_message()],
				$response_data->get_error_data()['status'] ?? 400
			);
		}

		// Extract data from response if it's a response object
		$data = $response_data;
		if (is_object($response_data) && method_exists($response_data, 'get_data')) {
			$data = $response_data->get_data();
		}

		return new WP_REST_Response($data, 200);
	}
}

// NOTE: rest_ensure_response() and is_wp_error() are handled by Brain Monkey when() calls.
// Not defining them here to allow Patchwork to patch them for tests that need to mock them.

if (!class_exists('WP_Error')) {
	class WP_Error
	{
		public $errors = [];
		public $error_data = [];

		public function __construct($code = '', $message = '', $data = '')
		{
			if (!empty($code)) {
				$this->errors[$code] = [$message];
				if (!empty($data)) {
					$this->error_data[$code] = $data;
				}
			}
		}

		public function get_error_code()
		{
			$codes = array_keys($this->errors);
			return $codes[0] ?? '';
		}

		public function get_error_message($code = '')
		{
			if (empty($code)) {
				$code = $this->get_error_code();
			}
			return $this->errors[$code][0] ?? '';
		}

		public function get_error_data($code = '')
		{
			if (empty($code)) {
				$code = $this->get_error_code();
			}
			return $this->error_data[$code] ?? null;
		}
	}
}

// Load base WPTestCase class used by our tests
require_once __DIR__ . '/Util/WPTestCase.php';

// Load the real LGP_Logger class so all tests use consistent logging
require_once __DIR__ . '/../includes/class-lgp-logger.php';

// Load authentication helper so permission checks in APIs work during tests
require_once __DIR__ . '/../includes/class-lgp-auth.php';

// Create a minimal stub for wp-admin/includes/upgrade.php to satisfy require_once
$upgradeDir = ABSPATH . 'wp-admin/includes/';
if (!is_dir($upgradeDir)) {
	@mkdir($upgradeDir, 0777, true);
}
$upgradeFile = $upgradeDir . 'upgrade.php';
if (!file_exists($upgradeFile)) {
	file_put_contents($upgradeFile, '<?php
if (!function_exists("dbDelta")) { function dbDelta($sql) { return true; } }
');
}
