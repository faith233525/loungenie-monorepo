<?php

use LounGenie\Portal\LGP_Auth;

/**
 * Knowledge Center REST API
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require the Knowledge Guide class (legacy class name retained for compatibility)
if ( ! class_exists( 'LGP_Help_Guide' ) ) {
	require_once LGP_PLUGIN_DIR . 'includes/class-lgp-knowledge-guide.php';
}

class LGP_Knowledge_Center_API {




	/**
	 * Initialize API endpoints
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		$api = new self();
		$api->register_endpoints();
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		// Support both legacy and new Knowledge Center base paths
		$route_bases = array( 'help-guides', 'knowledge-center' );

		foreach ( $route_bases as $base ) {
			// Get all guides (role-based filtering)
			register_rest_route(
				'lgp/v1',
				'/' . $base,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_guides' ),
					'permission_callback' => array( $this, 'check_portal_access' ),
				)
			);

			// Get single guide
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/(?P<id>\d+)',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_guide' ),
					'permission_callback' => array( $this, 'check_portal_access' ),
				)
			);

			// Create guide (support-only)
			register_rest_route(
				'lgp/v1',
				'/' . $base,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_guide' ),
					'permission_callback' => array( $this, 'support_only_permission' ),
				)
			);

			// Upload guide media (support-only)
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/upload',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'upload_media' ),
					'permission_callback' => array( $this, 'support_only_permission' ),
				)
			);

			// Update guide (support-only)
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/(?P<id>\d+)',
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_guide' ),
					'permission_callback' => array( $this, 'support_only_permission' ),
				)
			);

			// Delete guide (support-only)
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/(?P<id>\d+)',
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_guide' ),
					'permission_callback' => array( $this, 'support_only_permission' ),
				)
			);

			// Get categories
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/categories',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'check_portal_access' ),
				)
			);

			// User progress (partner users update watched status)
			register_rest_route(
				'lgp/v1',
				'/' . $base . '/(?P<id>\d+)/progress',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_progress' ),
					'permission_callback' => array( $this, 'check_portal_access' ),
				)
			);
		}
	}

	/**
	 * Get all videos (filtered by role)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_guides( $request ) {
		$filters = array();

		if ( $request->get_param( 'category' ) ) {
			$filters['category'] = $request->get_param( 'category' );
		}

		if ( $request->get_param( 'type' ) ) {
			$filters['type'] = $request->get_param( 'type' );
		}

		if ( $request->get_param( 'tags' ) ) {
			$tags            = $request->get_param( 'tags' );
			$filters['tags'] = is_string( $tags ) ? array_filter( explode( ',', $tags ) ) : $tags;
		}

		if ( $request->get_param( 'search' ) ) {
			$filters['search'] = $request->get_param( 'search' );
		}

		$videos = LGP_Help_Guide::get_all( $filters );

		return rest_ensure_response( $videos );
	}

	/**
	 * Get single video
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_guide( $request ) {
		$id    = (int) $request->get_param( 'id' );
		$video = LGP_Help_Guide::get( $id );

		if ( ! $video ) {
			return new WP_Error( 'not_found', 'Video not found or not accessible', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $video );
	}

	/**
	 * Create new video (support-only)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function create_guide( $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$data = array(
			'title'            => $request->get_param( 'title' ),
			'description'      => $request->get_param( 'description' ),
			'content_url'      => $request->get_param( 'content_url' ),
			'category'         => $request->get_param( 'category' ) ?? 'general',
			'target_companies' => $request->get_param( 'target_companies' ) ?? array(),
			'duration'         => $request->get_param( 'duration' ) ?? 0,
		);

		// Validate required fields
		if ( empty( $data['title'] ) || empty( $data['content_url'] ) ) {
			return new WP_Error( 'missing_fields', 'Title and content URL are required', array( 'status' => 400 ) );
		}

		$video_id = LGP_Help_Guide::create( $data );

		if ( ! $video_id ) {
			return new WP_Error( 'create_failed', 'Failed to create video', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'video_id' => $video_id,
				'message'  => 'Video created successfully',
			)
		);
	}

	/**
	 * Update video (support-only)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function update_guide( $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		$id = (int) $request->get_param( 'id' );

		$data = array();

		if ( $request->get_param( 'title' ) !== null ) {
			$data['title'] = $request->get_param( 'title' );
		}
		if ( $request->get_param( 'description' ) !== null ) {
			$data['description'] = $request->get_param( 'description' );
		}
		if ( $request->get_param( 'content_url' ) !== null ) {
			$data['content_url'] = $request->get_param( 'content_url' );
		}
		if ( $request->get_param( 'category' ) !== null ) {
			$data['category'] = $request->get_param( 'category' );
		}
		if ( $request->get_param( 'target_companies' ) !== null ) {
			$data['target_companies'] = $request->get_param( 'target_companies' );
		}
		if ( $request->get_param( 'duration' ) !== null ) {
			$data['duration'] = $request->get_param( 'duration' );
		}

		$success = LGP_Help_Guide::update( $id, $data );

		if ( ! $success ) {
			return new WP_Error( 'update_failed', 'Failed to update video', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Video updated successfully',
			)
		);
	}

	/**
	 * Delete video (support-only)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function delete_guide( $request ) {
		$id = (int) $request->get_param( 'id' );

		$success = LGP_Help_Guide::delete( $id );

		if ( ! $success ) {
			return new WP_Error( 'delete_failed', 'Failed to delete video', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Video deleted successfully',
			)
		);
	}

	/**
	 * Upload help guide media (support-only)
	 * Accepts video files (mp4, mov, webm, ogg) up to 10MB and returns a public URL.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_media( $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Nonce verification failed', 'loungenie-portal' ), array( 'status' => 403 ) );
		}

		if ( ! $this->support_only_permission() ) {
			return new WP_Error( 'forbidden', 'Not allowed', array( 'status' => 403 ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			return new WP_Error( 'no_file', 'No file uploaded', array( 'status' => 400 ) );
		}

		$file     = $_FILES['file'];
		$max_size = 10 * 1024 * 1024; // 10MB shared-hosting limit
		$allowed  = array( 'video/mp4', 'video/quicktime', 'video/webm', 'video/ogg' );

		if ( ! empty( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', 'Upload failed', array( 'status' => 400 ) );
		}

		if ( ! empty( $file['size'] ) && $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', 'File exceeds 10MB limit', array( 'status' => 400 ) );
		}

		// Validate mime
		$finfo    = finfo_open( FILEINFO_MIME_TYPE );
		$mime     = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : $file['type'];
		$detected = $mime ?: $file['type'];
		if ( $finfo ) {
			finfo_close( $finfo );
		}

		if ( ! in_array( $detected, $allowed, true ) ) {
			return new WP_Error( 'invalid_type', 'Only mp4, mov, webm, or ogg allowed', array( 'status' => 400 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'mp4'  => 'video/mp4',
				'mov'  => 'video/quicktime',
				'qt'   => 'video/quicktime',
				'webm' => 'video/webm',
				'ogg'  => 'video/ogg',
			),
		);

		$upload = wp_handle_upload( $file, $overrides );

		if ( isset( $upload['error'] ) ) {
			return new WP_Error( 'upload_failed', $upload['error'], array( 'status' => 400 ) );
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'url'       => esc_url_raw( $upload['url'] ),
				'file'      => basename( $upload['file'] ),
				'mime_type' => $detected,
				'size'      => (int) $file['size'],
			)
		);
	}

	/**
	 * Get all categories
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_categories( $request ) {
		$categories = LGP_Help_Guide::get_categories();

		return rest_ensure_response( $categories );
	}

	/**
	 * Check if user has portal access (lgp_support or lgp_partner)
	 *
	 * @return bool
	 */
	public function check_portal_access() {
		if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {
			return false;
		}

		// Prefer centralized auth helper when available
		if ( class_exists( '\LounGenie\Portal\LGP_Auth' ) || class_exists( 'LGP_Auth' ) ) {
			if ( LGP_Auth::is_support() || LGP_Auth::is_partner() ) {
				return true;
			}
		}

		// Capability-based allowlist
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Fallback to explicit role inspection (strict)
		if ( function_exists( 'wp_get_current_user' ) ) {
			$user  = wp_get_current_user();
			$roles = isset( $user->roles ) ? (array) $user->roles : array();
			if ( in_array( 'lgp_support', $roles, true ) || in_array( 'lgp_partner', $roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has support role (can manage help and guides)
	 *
	 * @return bool
	 */
	public function support_only_permission() {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( class_exists( '\LounGenie\Portal\LGP_Auth' ) || class_exists( 'LGP_Auth' ) ) {
			return LGP_Auth::is_support();
		}

		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
			if ( isset( $user->roles ) && in_array( 'lgp_support', (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update user progress for a guide (partner users)
	 */
	public function update_progress( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', 'Login required', array( 'status' => 401 ) );
		}

		$user     = wp_get_current_user();
		$guide_id = absint( $request->get_param( 'id' ) );
		$status   = sanitize_text_field( $request->get_param( 'status' ) ); // watched|unwatched|in_progress

		if ( ! in_array( $status, array( 'watched', 'unwatched', 'in_progress' ), true ) ) {
			return new WP_Error( 'invalid_status', 'Invalid progress status', array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'lgp_user_progress';

		// Upsert progress
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND guide_id = %d", $user->ID, $guide_id ) );

		$data = array(
			'user_id'    => $user->ID,
			'guide_id'   => $guide_id,
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
		);

		$result = $existing
			? $wpdb->update(
				$table,
				$data,
				array(
					'user_id'  => $user->ID,
					'guide_id' => $guide_id,
				)
			)
			: $wpdb->insert( $table, $data );

		if ( $result === false ) {
			return new WP_Error( 'update_failed', 'Failed to update progress', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}
}

// Legacy alias for backward compatibility
if ( ! class_exists( 'LGP_Help_Guides_API' ) ) {
	class_alias( 'LGP_Knowledge_Center_API', 'LGP_Help_Guides_API' );
}
