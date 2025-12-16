<?php
/**
 * Training Video Management Class
 * Support can upload/manage, Partners can view assigned videos
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LGP_Training_Video {
    
    /**
     * Get all videos (filtered by role)
     * Support sees all, Partners see only assigned videos
     * 
     * @param array $filters Optional filters (category, search)
     * @return array
     */
    public static function get_all( $filters = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lgp_training_videos';
        
        $sql = "SELECT * FROM $table WHERE 1=1";
        $params = array();
        
        // Filter by category
        if ( ! empty( $filters['category'] ) ) {
            $sql .= " AND category = %s";
            $params[] = $filters['category'];
        }
        
        // Filter by search term
        if ( ! empty( $filters['search'] ) ) {
            $sql .= " AND (title LIKE %s OR description LIKE %s)";
            $search = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        // Partner role: filter by company assignment
        if ( ! LGP_Auth::is_support() ) {
            $company_id = LGP_Auth::get_current_company_id();
            if ( ! $company_id ) {
                return array(); // Partners without company see nothing
            }
            
            // After fetching, filter by target_companies JSON
            $sql .= " ORDER BY created_at DESC";
            
            if ( ! empty( $params ) ) {
                $results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
            } else {
                $results = $wpdb->get_results( $sql );
            }
            
            // Filter by target_companies
            return array_filter( $results, function( $video ) use ( $company_id ) {
                $targets = json_decode( $video->target_companies, true );
                if ( empty( $targets ) ) {
                    return true; // No targets = available to all
                }
                return in_array( $company_id, $targets );
            } );
        }
        
        // Support sees all
        $sql .= " ORDER BY created_at DESC";
        
        if ( ! empty( $params ) ) {
            return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        }
        
        return $wpdb->get_results( $sql );
    }
    
    /**
     * Get single video by ID (role-based access)
     * 
     * @param int $id Video ID
     * @return object|null
     */
    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lgp_training_videos';
        
        $video = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ) );
        
        if ( ! $video ) {
            return null;
        }
        
        // Check partner access
        if ( ! LGP_Auth::is_support() ) {
            $company_id = LGP_Auth::get_current_company_id();
            $targets = json_decode( $video->target_companies, true );
            
            if ( ! empty( $targets ) && ! in_array( $company_id, $targets ) ) {
                return null; // Not assigned to this partner
            }
        }
        
        return $video;
    }
    
    /**
     * Create new video (support-only)
     * 
     * @param array $data Video data
     * @return int|false Video ID or false on failure
     */
    public static function create( $data ) {
        if ( ! LGP_Auth::is_support() ) {
            return false;
        }
        
        // Validate required fields
        if ( empty( $data['title'] ) || empty( $data['video_url'] ) ) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'lgp_training_videos';
        
        $insert_data = array(
            'title'             => sanitize_text_field( $data['title'] ),
            'description'       => sanitize_textarea_field( $data['description'] ?? '' ),
            'video_url'         => esc_url_raw( $data['video_url'] ),
            'category'          => sanitize_text_field( $data['category'] ?? 'general' ),
            'target_companies'  => wp_json_encode( $data['target_companies'] ?? array() ),
            'duration'          => absint( $data['duration'] ?? 0 ),
            'created_by'        => get_current_user_id(),
            'created_at'        => current_time( 'mysql' ),
            'updated_at'        => current_time( 'mysql' )
        );
        
        $result = $wpdb->insert( $table, $insert_data );
        
        if ( $result ) {
            $video_id = $wpdb->insert_id;
            self::log_action( 'create', $video_id, $insert_data );
            return $video_id;
        }
        
        return false;
    }
    
    /**
     * Update video (support-only)
     * 
     * @param int $id Video ID
     * @param array $data Updated data
     * @return bool Success
     */
    public static function update( $id, $data ) {
        if ( ! LGP_Auth::is_support() ) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'lgp_training_videos';
        
        $update_data = array(
            'updated_at' => current_time( 'mysql' )
        );
        
        if ( isset( $data['title'] ) ) {
            $update_data['title'] = sanitize_text_field( $data['title'] );
        }
        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
        }
        if ( isset( $data['video_url'] ) ) {
            $update_data['video_url'] = esc_url_raw( $data['video_url'] );
        }
        if ( isset( $data['category'] ) ) {
            $update_data['category'] = sanitize_text_field( $data['category'] );
        }
        if ( isset( $data['target_companies'] ) ) {
            $update_data['target_companies'] = wp_json_encode( $data['target_companies'] );
        }
        if ( isset( $data['duration'] ) ) {
            $update_data['duration'] = absint( $data['duration'] );
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            null,
            array( '%d' )
        );
        
        if ( $result !== false ) {
            self::log_action( 'update', $id, $update_data );
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete video (support-only)
     * 
     * @param int $id Video ID
     * @return bool Success
     */
    public static function delete( $id ) {
        if ( ! LGP_Auth::is_support() ) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'lgp_training_videos';
        
        // Get video data before delete for logging
        $video = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ) );
        
        if ( ! $video ) {
            return false;
        }
        
        $result = $wpdb->delete(
            $table,
            array( 'id' => $id ),
            array( '%d' )
        );
        
        if ( $result ) {
            self::log_action( 'delete', $id, array(
                'title' => $video->title,
                'category' => $video->category
            ) );
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all available categories
     * 
     * @return array
     */
    public static function get_categories() {
        return array(
            'general',
            'installation',
            'troubleshooting',
            'maintenance',
            'product-overview'
        );
    }
    
    /**
     * Get videos by category (role-based access)
     * 
     * @param string $category Category name
     * @return array
     */
    public static function get_by_category( $category ) {
        return self::get_all( array( 'category' => $category ) );
    }
    
    /**
     * Log training video action
     * 
     * @param string $action Action type
     * @param int $video_id Video ID
     * @param array $data Video data
     */
    private static function log_action( $action, $video_id, $data ) {
        if ( ! class_exists( 'LGP_Logger' ) ) {
            return;
        }
        
        $user = wp_get_current_user();
        
        LGP_Logger::log(
            'training_video',
            $action,
            array(
                'video_id'   => $video_id,
                'user_id'    => $user->ID,
                'user_email' => $user->user_email,
                'data'       => $data,
            ),
            $user->ID
        );
    }
}
