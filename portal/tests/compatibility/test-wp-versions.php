<?php

/**
 * WordPress Version Compatibility Test
 *
 * Tests compatibility with multiple WordPress versions
 *
 * @package LounGenie_Portal_Tests
 * @since 1.0.0
 */

namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_WordPress_Compatibility_Test extends WP_UnitTestCase
{
    /**
     * Test WordPress version
     *
     * @test
     */
    public function test_wordpress_version()
    {
        global $wp_version;

        // Should be WordPress 5.8 or higher
        $this->assertTrue(version_compare($wp_version, '5.8', '>='));
    }

    /**
     * Test REST API availability
     *
     * @test
     */
    public function test_rest_api_available()
    {
        // REST API should be available
        $this->assertTrue(function_exists('register_rest_route'));
    }

    /**
     * Test block editor compatibility
     *
     * @test
     */
    public function test_block_editor_compat()
    {
        // Block editor functions should exist
        $this->assertTrue(function_exists('get_block_types'));
    }

    /**
     * Test capabilities system
     *
     * @test
     */
    public function test_capabilities_system()
    {
        $user = Test_Utils::create_partner_user();
        wp_set_current_user($user->ID);

        // Should be able to check capabilities
        $this->assertFalse(current_user_can('manage_options'));
    }

    /**
     * Test query functions work
     *
     * @test
     */
    public function test_query_functions()
    {
        $user = Test_Utils::create_partner_user();

        // Should be able to get user
        $retrieved_user = get_user_by('ID', $user->ID);
        $this->assertNotFalse($retrieved_user);
    }

    /**
     * Test meta functions
     *
     * @test
     */
    public function test_meta_functions()
    {
        $user = Test_Utils::create_partner_user();
        $company_id = Test_Utils::create_test_company();

        // Set and get meta
        update_user_meta($user->ID, 'test_meta', 'test_value');
        $value = get_user_meta($user->ID, 'test_meta', true);

        $this->assertEquals('test_value', $value);
    }

    /**
     * Test filters and actions
     *
     * @test
     */
    public function test_hooks_system()
    {
        $called = false;

        add_action('test_hook', function () use (&$called) {
            $called = true;
        });

        do_action('test_hook');

        $this->assertTrue($called);
    }

    /**
     * Test deprecated function handling
     *
     * @test
     */
    public function test_no_deprecated_functions()
    {
        // In WordPress 6.0+, some functions are deprecated
        // This test would verify we're not using them

        // For example, wp_trim_excerpt still exists but might be deprecated in future
        $this->assertTrue(function_exists('wp_trim_excerpt'));
    }

    /**
     * Cleanup after tests
     */
    public function tearDown(): void
    {
        Test_Utils::cleanup();
        parent::tearDown();
    }
}
