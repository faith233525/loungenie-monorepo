<?php

/**
 * OpenAPI/Swagger API Documentation
 * Provides interactive API reference and Swagger UI
 *
 * @package LounGenie Portal
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAPI/Swagger API documentation endpoints.
 */
class LGP_API_Docs {

	const API_VERSION = '1.0.0';
	const BASE_PATH   = '/wp-json/lgp/v1';

	/**
	 * Initialize API docs.
	 *
	 * @return void
	 */
	public static function init() {
		// Register OpenAPI endpoint.
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );

		// Register admin page.
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	/**
	 * Register REST endpoints.
	 *
	 * @return void
	 */
	public static function register_endpoints() {
		register_rest_route(
			'lgp/v1',
			'/openapi.json',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_openapi_spec' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get OpenAPI 3.0 specification
	 */
	public static function get_openapi_spec() {
		$spec = array(
			'openapi'      => '3.0.0',
			'info'         => array(
				'title'       => 'LounGenie Portal API',
				'description' => 'Enterprise SaaS Portal for Partner Management',
				'version'     => self::API_VERSION,
				'contact'     => array(
					'name'  => 'Pool Safe Inc Support',
					'email' => 'support@poolsafeinc.com',
					'url'   => 'https://poolsafeinc.com',
				),
				'license'     => array(
					'name' => 'GPLv2 or later',
					'url'  => 'https://www.gnu.org/licenses/gpl-2.0.html',
				),
			),
			'externalDocs' => array(
				'description' => 'Full Documentation',
				'url'         => admin_url( 'admin.php?page=lgp-api-docs' ),
			),
			'servers'      => array(
				array(
					'url'         => get_rest_url( null, 'lgp/v1' ),
					'description' => 'LounGenie API Server',
				),
			),
			'paths'        => self::get_api_paths(),
			'components'   => self::get_components(),
			'tags'         => self::get_tags(),
		);

		return rest_ensure_response( $spec );
	}

	/**
	 * Get API paths definition
	 */
	private static function get_api_paths() {
		return array(
			'/companies'      => array(
				'get'  => array(
					'summary'     => 'List all companies',
					'operationId' => 'getCompanies',
					'tags'        => array( 'Companies' ),
					'description' => 'Retrieve a list of all companies. Support users can see all, partners can see only their company.',
					'parameters'  => array(
						array(
							'name'        => 'page',
							'in'          => 'query',
							'description' => 'Page number',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 1,
							),
						),
						array(
							'name'        => 'per_page',
							'in'          => 'query',
							'description' => 'Results per page',
							'schema'      => array(
								'type'    => 'integer',
								'default' => 10,
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Successful response',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array( '$ref' => '#/components/schemas/Company' ),
									),
								),
							),
						),
						'403' => array(
							'description' => 'Unauthorized',
						),
					),
				),
				'post' => array(
					'summary'     => 'Create a new company',
					'operationId' => 'createCompany',
					'tags'        => array( 'Companies' ),
					'description' => 'Create a new company. Support users only.',
					'requestBody' => array(
						'required' => true,
						'content'  => array(
							'application/json' => array(
								'schema' => array( '$ref' => '#/components/schemas/CompanyInput' ),
							),
						),
					),
					'responses'   => array(
						'201' => array(
							'description' => 'Company created',
							'content'     => array(
								'application/json' => array(
									'schema' => array( '$ref' => '#/components/schemas/Company' ),
								),
							),
						),
						'400' => array(
							'description' => 'Invalid input',
						),
						'403' => array(
							'description' => 'Unauthorized',
						),
					),
				),
			),

			'/companies/{id}' => array(
				'get'    => array(
					'summary'     => 'Get company details',
					'operationId' => 'getCompany',
					'tags'        => array( 'Companies' ),
					'parameters'  => array(
						array(
							'name'        => 'id',
							'in'          => 'path',
							'required'    => true,
							'description' => 'Company ID',
							'schema'      => array( 'type' => 'integer' ),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Company details',
							'content'     => array(
								'application/json' => array(
									'schema' => array( '$ref' => '#/components/schemas/Company' ),
								),
							),
						),
						'404' => array(
							'description' => 'Company not found',
						),
					),
				),
				'put'    => array(
					'summary'     => 'Update company',
					'operationId' => 'updateCompany',
					'tags'        => array( 'Companies' ),
					'parameters'  => array(
						array(
							'name'     => 'id',
							'in'       => 'path',
							'required' => true,
							'schema'   => array( 'type' => 'integer' ),
						),
					),
					'requestBody' => array(
						'required' => true,
						'content'  => array(
							'application/json' => array(
								'schema' => array( '$ref' => '#/components/schemas/CompanyInput' ),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Company updated',
							'content'     => array(
								'application/json' => array(
									'schema' => array( '$ref' => '#/components/schemas/Company' ),
								),
							),
						),
						'404' => array(
							'description' => 'Company not found',
						),
					),
				),
				'delete' => array(
					'summary'     => 'Delete company',
					'operationId' => 'deleteCompany',
					'tags'        => array( 'Companies' ),
					'parameters'  => array(
						array(
							'name'     => 'id',
							'in'       => 'path',
							'required' => true,
							'schema'   => array( 'type' => 'integer' ),
						),
					),
					'responses'   => array(
						'204' => array(
							'description' => 'Company deleted',
						),
						'404' => array(
							'description' => 'Company not found',
						),
					),
				),
			),

			'/units'          => array(
				'get' => array(
					'summary'     => 'List LounGenie units',
					'operationId' => 'getUnits',
					'tags'        => array( 'Units' ),
					'parameters'  => array(
						array(
							'name'   => 'company_id',
							'in'     => 'query',
							'schema' => array( 'type' => 'integer' ),
						),
						array(
							'name'   => 'color',
							'in'     => 'query',
							'schema' => array(
								'type' => 'string',
								'enum' => array( 'yellow', 'red', 'blue', 'white', 'black', 'gray' ),
							),
						),
						array(
							'name'   => 'season',
							'in'     => 'query',
							'schema' => array(
								'type' => 'string',
								'enum' => array( 'seasonal', 'year-round' ),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'List of units',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array( '$ref' => '#/components/schemas/Unit' ),
									),
								),
							),
						),
					),
				),
			),

			'/tickets'        => array(
				'get' => array(
					'summary'     => 'List support tickets',
					'operationId' => 'getTickets',
					'tags'        => array( 'Tickets' ),
					'parameters'  => array(
						array(
							'name'   => 'status',
							'in'     => 'query',
							'schema' => array(
								'type' => 'string',
								'enum' => array( 'open', 'closed', 'pending' ),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'List of tickets',
							'content'     => array(
								'application/json' => array(
									'schema' => array(
										'type'  => 'array',
										'items' => array( '$ref' => '#/components/schemas/Ticket' ),
									),
								),
							),
						),
					),
				),
			),

			'/health'         => array(
				'get' => array(
					'summary'     => 'System health check',
					'operationId' => 'getHealth',
					'tags'        => array( 'System' ),
					'description' => 'Check system status, database, cache, and dependencies',
					'responses'   => array(
						'200' => array(
							'description' => 'System is healthy',
							'content'     => array(
								'application/json' => array(
									'schema' => array( '$ref' => '#/components/schemas/HealthStatus' ),
								),
							),
						),
						'503' => array(
							'description' => 'System is unhealthy',
						),
					),
				),
			),
		);
	}

	/**
	 * Get component schemas
	 */
	private static function get_components() {
		return array(
			'schemas'         => array(
				'Company'      => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'integer' ),
						'name'     => array( 'type' => 'string' ),
						'email'    => array(
							'type'   => 'string',
							'format' => 'email',
						),
						'phone'    => array( 'type' => 'string' ),
						'address'  => array( 'type' => 'string' ),
						'city'     => array( 'type' => 'string' ),
						'state'    => array( 'type' => 'string' ),
						'zip'      => array( 'type' => 'string' ),
						'created'  => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'modified' => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
					),
				),

				'CompanyInput' => array(
					'type'       => 'object',
					'required'   => array( 'name', 'email' ),
					'properties' => array(
						'name'    => array(
							'type'        => 'string',
							'description' => 'Company name',
						),
						'email'   => array(
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Contact email',
						),
						'phone'   => array(
							'type'        => 'string',
							'description' => 'Contact phone',
						),
						'address' => array(
							'type'        => 'string',
							'description' => 'Street address',
						),
						'city'    => array(
							'type'        => 'string',
							'description' => 'City',
						),
						'state'   => array(
							'type'        => 'string',
							'description' => 'State/Province',
						),
						'zip'     => array(
							'type'        => 'string',
							'description' => 'ZIP/Postal code',
						),
					),
				),

				'Unit'         => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'company_id'   => array( 'type' => 'integer' ),
						'color'        => array( 'type' => 'string' ),
						'lock_brand'   => array( 'type' => 'string' ),
						'season'       => array( 'type' => 'string' ),
						'venue_type'   => array( 'type' => 'string' ),
						'status'       => array( 'type' => 'string' ),
						'install_date' => array(
							'type'   => 'string',
							'format' => 'date',
						),
					),
				),

				'Ticket'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'integer' ),
						'subject'  => array( 'type' => 'string' ),
						'status'   => array( 'type' => 'string' ),
						'priority' => array( 'type' => 'string' ),
						'created'  => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'updated'  => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
					),
				),

				'HealthStatus' => array(
					'type'       => 'object',
					'properties' => array(
						'status'           => array(
							'type' => 'string',
							'enum' => array( 'healthy', 'degraded', 'unhealthy' ),
						),
						'database'         => array( 'type' => 'boolean' ),
						'cache'            => array( 'type' => 'boolean' ),
						'disk_space_mb'    => array( 'type' => 'integer' ),
						'memory_usage_pct' => array( 'type' => 'number' ),
						'version'          => array( 'type' => 'string' ),
						'timestamp'        => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
					),
				),
			),

			'securitySchemes' => array(
				'bearerAuth' => array(
					'type'         => 'http',
					'scheme'       => 'bearer',
					'bearerFormat' => 'JWT',
					'description'  => 'WordPress REST API token',
				),
			),
		);
	}

	/**
	 * Get API tags
	 */
	private static function get_tags() {
		return array(
			array(
				'name'        => 'Companies',
				'description' => 'Manage partner companies',
			),
			array(
				'name'        => 'Units',
				'description' => 'Manage LounGenie units',
			),
			array(
				'name'        => 'Tickets',
				'description' => 'Support ticket management',
			),
			array(
				'name'        => 'System',
				'description' => 'System monitoring and health',
			),
		);
	}

	/**
	 * Register admin page
	 */
	public static function register_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'tools.php',
			'API Documentation',
			'API Docs',
			'manage_options',
			'lgp-api-docs',
			array( __CLASS__, 'render_swagger_ui' )
		);
	}

	/**
	 * Render Swagger UI
	 */
	public static function render_swagger_ui() {
		$spec_url = get_rest_url( null, 'lgp/v1/openapi.json' );
		?>
		<div class="wrap">
			<h1>LounGenie Portal - API Documentation</h1>

			<div style="background: white; padding: 20px; border-radius: 4px; margin-top: 20px;">
				<h2>Interactive API Reference</h2>
				<p>
					Explore the complete API using the Swagger UI below. All endpoints are documented with request/response examples.
				</p>

				<!-- Swagger UI CDN -->
				<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui.css">
				<style>
					#swagger-ui {
						margin-top: 20px;
					}
				</style>

				<div id="swagger-ui"></div>

				<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
				<script>
					window.onload = function() {
						const ui = SwaggerUIBundle({
							url: "<?php echo esc_url( $spec_url ); ?>",
							dom_id: '#swagger-ui',
							presets: [
								SwaggerUIBundle.presets.apis,
								SwaggerUIBundle.SwaggerUIStandalonePreset
							],
							layout: "StandaloneLayout"
						});
						window.ui = ui;
					};
				</script>
			</div>

			<!-- Documentation -->
			<div style="background: #f5f5f5; padding: 20px; border-radius: 4px; margin-top: 20px;">
				<h2>API Details</h2>

				<h3>Base URL</h3>
				<code><?php echo esc_html( get_rest_url( null, 'lgp/v1' ) ); ?></code>

				<h3>Authentication</h3>
				<p>All endpoints require WordPress REST API authentication. Use:</p>
				<ul>
					<li><strong>WordPress Nonce:</strong> Include <code>_wpnonce</code> parameter</li>
					<li><strong>Session Cookie:</strong> Authenticate via WordPress login</li>
				</ul>

				<h3>Response Format</h3>
				<p>All responses are in JSON format. Standard HTTP status codes are used:</p>
				<ul>
					<li><code>200</code> - Successful GET/PUT</li>
					<li><code>201</code> - Successful POST (resource created)</li>
					<li><code>204</code> - Successful DELETE</li>
					<li><code>400</code> - Bad request (invalid input)</li>
					<li><code>403</code> - Forbidden (permission denied)</li>
					<li><code>404</code> - Not found</li>
					<li><code>500</code> - Server error</li>
				</ul>

				<h3>Pagination</h3>
				<p>List endpoints support pagination with:</p>
				<ul>
					<li><code>page</code> - Page number (default: 1)</li>
					<li><code>per_page</code> - Results per page (default: 10, max: 100)</li>
				</ul>

				<h3>Rate Limiting</h3>
				<p>API requests are rate limited based on user role:</p>
				<ul>
					<li><strong>Support Users:</strong> 100 requests/hour</li>
					<li><strong>Partner Users:</strong> 50 requests/hour</li>
					<li><strong>Anonymous:</strong> 10 requests/hour</li>
				</ul>

				<h3>Error Responses</h3>
				<p>Errors include a JSON response with details:</p>
				<pre>
				<?php
				echo esc_html(
					wp_json_encode(
						array(
							'code'    => 'rest_invalid_param',
							'message' => 'Invalid parameter',
							'data'    => array( 'status' => 400 ),
						),
						JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
					)
				);
				?>
				</pre>

				<h3>Useful Links</h3>
				<ul>
					<li><a href="<?php echo esc_url( $spec_url ); ?>" target="_blank">OpenAPI 3.0 Specification (JSON)</a></li>
					<li><a href="https://swagger.io/docs/" target="_blank">Swagger Documentation</a></li>
					<li><a href="https://developer.wordpress.org/rest-api/" target="_blank">WordPress REST API Handbook</a></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
