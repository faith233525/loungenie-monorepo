<?php
/**
 * Training Videos REST API
 * 
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Training_Videos_API {
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get all videos (role-based filtering)
        register_rest_route( 'lgp/v1', '/training-videos', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_videos' ),
            'permission_callback' => array( $this, 'check_portal_access' ),
        ) );
        
        // Get single video
        register_rest_route( 'lgp/v1', '/training-videos/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_video' ),
            'permission_callback' => array( $this, 'check_portal_access' ),
        ) );
        
        // Create video (support-only)
        register_rest_route( 'lgp/v1', '/training-videos', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_video' ),
            'permission_callback' => array( $this, 'support_only_permission' ),
        ) );
        
        // Update video (support-only)
        register_rest_route( 'lgp/v1', '/training-videos/(?P<id>\d+)', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'update_video' ),
            'permission_callback' => array( $this, 'support_only_permission' ),
        ) );
        
        // Delete video (support-only)
        register_rest_route( 'lgp/v1', '/training-videos/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_video' ),
            'permission_callback' => array( $this, 'support_only_permission' ),
        ) );
        
        // Get categories
        register_rest_route( 'lgp/v1', '/training-videos/categories', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_categories' ),
            'permission_callback' => array( $this, 'check_portal_access' ),
        ) );
    }
    
    /**
     * Get all videos (filtered by role)
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_videos( $request ) {
        $filters = array();
        
        if ( $request->get_param( 'category' ) ) {
            $filters['category'] = $request->get_param( 'category' );
        }
        
        if ( $request->get_param( 'search' ) ) {
            $filters['search'] = $request->get_param( 'search' );
        }
        
        $videos = LGP_Training_Video::get_all( $filters );
        
        return rest_ensure_response( $videos );
    }
    
    /**
     * Get single video
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_video( $request ) {
        $id = $request->get_param( 'id' );
        $video = LGP_Training_Video::get( $id );
        
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
    public function create_video( $request ) {
        $data = array(
            'title'            => $request->get_param( 'title' ),
            'description'      => $request->get_param( 'description' ),
            'video_url'        => $request->get_param( 'video_url' ),
            'category'         => $request->get_param( 'category' ) ?? 'general',
            'target_companies' => $request->get_param( 'target_companies' ) ?? array(),
            'duration'         => $request->get_param( 'duration' ) ?? 0,
        );
        
        // Validate required fields
        if ( empty( $data['title'] ) || empty( $data['video_url'] ) ) {
            return new WP_Error( 'missing_fields', 'Title and video URL are required', array( 'status' => 400 ) );
        }
        
        $video_id = LGP_Training_Video::create( $data );
        
        if ( ! $video_id ) {
            return new WP_Error( 'create_failed', 'Failed to create video', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'video_id' => $video_id,
            'message' => 'Video created successfully'
        ) );
    }
    
    /**
     * Update video (support-only)
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_video( $request ) {
        $id = $request->get_param( 'id' );
        
        $data = array();
        
        if ( $request->get_param( 'title' ) !== null ) {
            $data['title'] = $request->get_param( 'title' );
        }
        if ( $request->get_param( 'description' ) !== null ) {
            $data['description'] = $request->get_param( 'description' );
        }
        if ( $request->get_param( 'video_url' ) !== null ) {
            $data['video_url'] = $request->get_param( 'video_url' );
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
        
        $success = LGP_Training_Video::update( $id, $data );
        
        if ( ! $success ) {
            return new WP_Error( 'update_failed', 'Failed to update video', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Video updated successfully'
        ) );
    }
    
    /**
     * Delete video (support-only)
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_video( $request ) {
        $id = $request->get_param( 'id' );
        
        $success = LGP_Training_Video::delete( $id );
        
        if ( ! $success ) {
            return new WP_Error( 'delete_failed', 'Failed to delete video', array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Video deleted successfully'
        ) );
    }
    
    /**
     * Get all categories
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_categories( $request ) {
        $categories = LGP_Training_Video::get_categories();
        
        return rest_ensure_response( $categories );
    }
    
    /**
     * Check if user has portal access (lgp_support or lgp_partner)
     * 
     * @return bool
     */
    public function check_portal_access() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user = wp_get_current_user();
        return in_array( 'lgp_support', $user->roles ) || in_array( 'lgp_partner', $user->roles );
    }
    
    /**
     * Check if user has support role
     * 
     * @return bool
     */
    public function support_only_permission() {
        return current_user_can( 'manage_options' );
    }
}

// Register API routes (only in WordPress context)
if ( function_exists( 'add_action' ) ) {
    add_action( 'rest_api_init', function() {
        $api = new LGP_Training_Videos_API();
        $api->register_routes();
    } );
}
