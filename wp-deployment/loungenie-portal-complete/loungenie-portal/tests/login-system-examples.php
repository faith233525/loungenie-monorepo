<?php
/**
 * Custom Login System - Examples & Testing Guide
 * 
 * This file demonstrates how to use the custom login system
 * and provides examples for customization.
 * 
 * @package LounGenie Portal
 * @version 1.8.0
 */

// ============================================================================
// EXAMPLE 1: Get Custom Login URL
// ============================================================================

function get_partner_login_link() {
    return \LounGenie\Portal\Login_Handler::get_custom_login_url('partner');
}

function get_support_login_link() {
    return \LounGenie\Portal\Login_Handler::get_custom_login_url('support');
}

function get_main_login_link() {
    return \LounGenie\Portal\Login_Handler::get_custom_login_url('select');
}

// Usage:
// echo '<a href="' . get_partner_login_link() . '">Partner Login</a>';
// echo '<a href="' . get_support_login_link() . '">Support Login</a>';


// ============================================================================
// EXAMPLE 2: Custom Redirect After Login
// ============================================================================

add_filter('lgp_login_redirect', function($redirect, $fallback, $user_id) {
    $user = get_user_by('id', $user_id);
    
    // Redirect partners to their company page
    if ($user->has_cap('lgp_partner')) {
        $company_id = get_user_meta($user_id, 'lgp_company_id', true);
        if ($company_id) {
            return home_url('/companies/' . $company_id . '/dashboard');
        }
    }
    
    // Redirect support to tickets
    if ($user->has_cap('lgp_support')) {
        return home_url('/support/tickets');
    }
    
    return $redirect;
}, 10, 3);


// ============================================================================
// EXAMPLE 3: Monitor Login Events
// ============================================================================

// Log all successful logins
add_action('lgp_login_success', function($user_id, $role) {
    $user = get_user_by('id', $user_id);
    error_log(sprintf(
        '[LounGenie Login] Success: %s (%s) - Role: %s',
        $user->user_email,
        $user->user_login,
        $role
    ));
}, 10, 2);

// Log all failed logins
add_action('lgp_login_failed', function($error_code, $username) {
    error_log(sprintf(
        '[LounGenie Login] Failed: %s - Error: %s',
        $username,
        $error_code
    ));
}, 10, 2);

// Log unauthorized attempts
add_action('lgp_unauthorized_login', function($user_id, $required_role) {
    $user = get_user_by('id', $user_id);
    error_log(sprintf(
        '[LounGenie Login] Unauthorized: %s - Required: %s',
        $user->user_email,
        $required_role
    ));
}, 10, 2);


// ============================================================================
// EXAMPLE 4: Custom Error Messages
// ============================================================================

add_filter('lgp_login_error_message', function($message, $error_code) {
    switch($error_code) {
        case 'invalid_credentials':
            return __('Your login details are incorrect. Please try again.', 'loungenie-portal');
        case 'account_disabled':
            return __('Your account has been disabled. Please contact support.', 'loungenie-portal');
        case 'invalid_role':
            return __('Your account does not have access to this portal.', 'loungenie-portal');
        case 'sso_failed':
            return __('Microsoft authentication failed. Please try again.', 'loungenie-portal');
    }
    return $message;
}, 10, 2);


// ============================================================================
// EXAMPLE 5: SSO Configuration Management
// ============================================================================

function setup_microsoft_sso() {
    $client_id = 'your-client-id';
    $client_secret = 'your-client-secret';
    $tenant_id = 'your-tenant-id';
    
    // Save to database
    \LounGenie\Portal\Microsoft_SSO::save_config($client_id, $client_secret, $tenant_id);
}

function check_sso_configured() {
    $config = \LounGenie\Portal\Microsoft_SSO::get_config();
    return $config['configured'];
}

function get_sso_setup_help() {
    $docs = \LounGenie\Portal\Microsoft_SSO::get_setup_documentation();
    return $docs;
}


// ============================================================================
// EXAMPLE 6: Programmatic Login (For Testing/Migration)
// ============================================================================

function programmatic_login_user($user_id, $remember = false) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return new \WP_Error('user_not_found', 'User not found');
    }
    
    // Set current user
    wp_set_current_user($user_id);
    
    // Create session cookie
    wp_set_auth_cookie($user_id, $remember);
    
    // Update last login
    update_user_meta($user_id, 'lgp_last_login', current_time('mysql'));
    
    // Trigger hook
    do_action('lgp_login_success', $user_id, 'manual');
    
    return true;
}

// Usage:
// programmatic_login_user(123, true);


// ============================================================================
// EXAMPLE 7: Disable/Enable User Accounts
// ============================================================================

function disable_user_account($user_id) {
    update_user_meta($user_id, 'lgp_account_active', '0');
    do_action('lgp_account_disabled', $user_id);
}

function enable_user_account($user_id) {
    update_user_meta($user_id, 'lgp_account_active', '1');
    do_action('lgp_account_enabled', $user_id);
}

function is_user_account_active($user_id) {
    $active = get_user_meta($user_id, 'lgp_account_active', true);
    
    // Default to active for existing users without meta
    if (empty($active)) {
        return !current_user_can('manage_options');
    }
    
    return $active !== '0';
}


// ============================================================================
// EXAMPLE 8: Create Support Users (SSO)
// ============================================================================

function create_support_user($email, $name) {
    $username = sanitize_user(explode('@', $email)[0], true);
    
    // Ensure username is unique
    $counter = 1;
    $original_username = $username;
    while (username_exists($username)) {
        $username = $original_username . $counter;
        $counter++;
    }
    
    // Create user
    $user_id = wp_create_user($username, wp_generate_password(32), $email);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Update user info
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
    ]);
    
    // Add support role
    $user = get_user_by('id', $user_id);
    $user->add_role('support');
    
    // Mark as SSO user
    update_user_meta($user_id, 'lgp_sso_user', true);
    update_user_meta($user_id, 'lgp_account_active', '1');
    
    do_action('lgp_support_user_created', $user_id);
    
    return $user;
}

// Usage:
// $user = create_support_user('john@company.com', 'John Doe');


// ============================================================================
// EXAMPLE 9: Create Partner Users (WordPress Auth)
// ============================================================================

function create_partner_user($username, $email, $first_name = '', $last_name = '') {
    // Check if user exists
    if (username_exists($username) || email_exists($email)) {
        return new \WP_Error('user_exists', 'User already exists');
    }
    
    // Create user
    $user_id = wp_create_user($username, wp_generate_password(20), $email);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Update user info
    wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ]);
    
    // Add partner role
    $user = get_user_by('id', $user_id);
    $user->add_role('partner');
    
    // Mark as active
    update_user_meta($user_id, 'lgp_account_active', '1');
    
    do_action('lgp_partner_user_created', $user_id);
    
    return $user;
}

// Usage:
// $user = create_partner_user('john_doe', 'john@company.com', 'John', 'Doe');


// ============================================================================
// EXAMPLE 10: Get User Login History
// ============================================================================

function get_user_login_history($user_id, $limit = 10) {
    global $wpdb;
    
    // This assumes you have a login history table
    // You can use activity logging plugins or custom tables
    
    $last_login = get_user_meta($user_id, 'lgp_last_login', true);
    $sso_login = get_user_meta($user_id, 'lgp_last_sso_login', true);
    
    return [
        'last_login' => $last_login,
        'last_sso_login' => $sso_login,
        'created' => get_user_by('id', $user_id)->user_registered,
    ];
}


// ============================================================================
// EXAMPLE 11: Testing Login Links
// ============================================================================

function output_login_test_page() {
    if (!current_user_can('manage_options')) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="lgp-login-test">
        <h2>LounGenie Portal Login Links</h2>
        
        <h3>Login URLs</h3>
        <ul>
            <li><a href="<?php echo get_main_login_link(); ?>" target="_blank">Main Login</a></li>
            <li><a href="<?php echo get_partner_login_link(); ?>" target="_blank">Partner Login</a></li>
            <li><a href="<?php echo get_support_login_link(); ?>" target="_blank">Support Login</a></li>
        </ul>
        
        <h3>SSO Configuration</h3>
        <?php if (check_sso_configured()) : ?>
            <p style="color: green;">✓ Microsoft SSO is configured</p>
        <?php else : ?>
            <p style="color: red;">✗ Microsoft SSO is not configured</p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=lgp-settings&tab=sso'); ?>">
                    Configure SSO
                </a>
            </p>
        <?php endif; ?>
        
        <h3>Test Users</h3>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = get_users([
                    'role__in' => ['partner', 'support'],
                    'number' => 5,
                ]);
                
                foreach ($users as $user) {
                    $is_active = is_user_account_active($user->ID);
                    $roles = implode(', ', array_map(function($role) {
                        return ucfirst($role);
                    }, $user->roles));
                    $history = get_user_login_history($user->ID);
                    ?>
                    <tr>
                        <td><?php echo esc_html($user->user_login); ?></td>
                        <td><?php echo esc_html($roles); ?></td>
                        <td><?php echo $is_active ? '✓' : '✗'; ?></td>
                        <td><?php echo esc_html($history['last_login'] ?: 'Never'); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

// Usage: Add to admin page
// echo output_login_test_page();


// ============================================================================
// EXAMPLE 12: Add Login Link to Navigation Menu
// ============================================================================

add_filter('nav_menu_link_attributes', function($atts, $item, $args) {
    // Add login link to specific menu
    if ($args->theme_location === 'main-menu' && $item->title === 'Portal') {
        $atts['href'] = get_main_login_link();
    }
    return $atts;
}, 10, 3);


// ============================================================================
// TESTING CHECKLIST
// ============================================================================

/**
 * Run this to test all login functionality
 * 
 * function run_login_system_tests() {
 *     $tests = [];
 *     
 *     // Test 1: Login URLs exist
 *     $tests['partner_login_url'] = !empty(get_partner_login_link());
 *     $tests['support_login_url'] = !empty(get_support_login_link());
 *     
 *     // Test 2: SSO configuration
 *     $tests['sso_callable'] = class_exists('\LounGenie\Portal\Microsoft_SSO');
 *     
 *     // Test 3: Login handler exists
 *     $tests['login_handler_exists'] = class_exists('\LounGenie\Portal\Login_Handler');
 *     
 *     // Test 4: Test user creation
 *     $user = create_partner_user(
 *         'test_partner_' . time(),
 *         'test_' . time() . '@example.com'
 *     );
 *     $tests['partner_user_created'] = !is_wp_error($user);
 *     
 *     // Output results
 *     echo '<h2>Login System Tests</h2>';
 *     echo '<table>';
 *     foreach ($tests as $test => $result) {
 *         $status = $result ? '✓ PASS' : '✗ FAIL';
 *         echo '<tr><td>' . $test . '</td><td>' . $status . '</td></tr>';
 *     }
 *     echo '</table>';
 * }
 */

// To run tests, add this to a custom admin page or REST endpoint:
// add_action('admin_init', 'run_login_system_tests');
