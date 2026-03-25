<?php

/**
 * Test utilities and helper functions
 *
 * @package LounGenie_Portal
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_User;
use WP_Post;

/**
 * Test utilities class
 */
class Test_Utils
{
    /**
     * Create a test user with specified role
     *
     * @param string $role User role
     * @param array  $args Additional user arguments
     * @return WP_User
     */
    public static function create_test_user($role = 'subscriber', $args = [])
    {
        static $counter = 0;
        $counter++;

        $default_args = [
            'user_login' => "testuser_{$counter}",
            'user_email' => "testuser_{$counter}@test.local",
            'user_pass'  => 'Test123!@#',
            'role'       => $role,
        ];

        $args = array_merge($default_args, $args);
        $user_id = wp_create_user(
            $args['user_login'],
            $args['user_pass'],
            $args['user_email']
        );

        if (is_wp_error($user_id)) {
            throw new \Exception('Failed to create test user: ' . $user_id->get_error_message());
        }

        $user = get_user_by('id', $user_id);
        $user->set_role($role);

        return $user;
    }

    /**
     * Create a support user
     *
     * @return WP_User
     */
    public static function create_support_user()
    {
        return self::create_test_user('support');
    }

    /**
     * Create a partner user
     *
     * @return WP_User
     */
    public static function create_partner_user()
    {
        return self::create_test_user('partner');
    }

    /**
     * Create a test ticket
     *
     * @param array $args Ticket arguments
     * @return int|WP_Error Post ID or error
     */
    public static function create_test_ticket($args = [])
    {
        $defaults = [
            'post_type'   => 'lgp_ticket',
            'post_title'  => 'Test Ticket',
            'post_content' => 'Test ticket content',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ];

        $args = array_merge($defaults, $args);
        return wp_insert_post($args);
    }

    /**
     * Create a test company
     *
     * @param array $args Company arguments
     * @return int Company ID
     */
    public static function create_test_company($args = [])
    {
        global $wpdb;

        $defaults = [
            'name'        => 'Test Company',
            'external_id' => 'ext_' . wp_generate_uuid4(),
            'status'      => 'active',
        ];

        $args = array_merge($defaults, $args);

        $wpdb->insert(
            $wpdb->prefix . 'lgp_companies',
            [
                'name'        => $args['name'],
                'external_id' => $args['external_id'],
                'status'      => $args['status'],
            ],
            ['%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Set user company association
     *
     * @param int $user_id User ID
     * @param int $company_id Company ID
     * @return bool
     */
    public static function set_user_company($user_id, $company_id)
    {
        return update_user_meta($user_id, 'company_id', $company_id);
    }

    /**
     * Make request to REST API endpoint
     *
     * @param string $method HTTP method
     * @param string $route API route
     * @param array  $args Request arguments
     * @return WP_REST_Response|WP_Error
     */
    public static function make_request($method, $route, $args = [])
    {
        $request = new \WP_REST_Request($method, $route);

        if (!empty($args['body'])) {
            $request->set_json_params($args['body']);
        }

        if (!empty($args['query'])) {
            foreach ($args['query'] as $key => $value) {
                $request->set_param($key, $value);
            }
        }

        if (!empty($args['headers'])) {
            foreach ($args['headers'] as $key => $value) {
                $request->set_header($key, $value);
            }
        }

        $response = rest_get_server()->dispatch($request);
        return $response;
    }

    /**
     * Authenticate request as user
     *
     * @param \WP_REST_Request $request Request object
     * @param WP_User          $user User object
     * @return void
     */
    public static function authenticate_request(&$request, $user)
    {
        wp_set_current_user($user->ID);
    }

    /**
     * Clean up test data
     *
     * @return void
     */
    public static function cleanup()
    {
        global $wpdb;

        // Delete test posts
        $posts = get_posts([
            'post_type'   => 'lgp_ticket',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);

        foreach ($posts as $post_id) {
            wp_delete_post($post_id, true);
        }

        // Delete test users
        $users = get_users();
        foreach ($users as $user) {
            if (strpos($user->user_login, 'testuser_') === 0) {
                wp_delete_user($user->ID);
            }
        }

        // Delete test companies
        $wpdb->query("DELETE FROM {$wpdb->prefix}lgp_companies WHERE external_id LIKE 'ext_%'");
    }
}
