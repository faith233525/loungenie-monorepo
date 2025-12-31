<?php
/**
 * Admin Role Switcher
 *
 * Allows administrators to switch between Support and Partner views
 * for testing and troubleshooting purposes without logging out.
 *
 * @package LounGeniePortal
 * @since   2.0.0
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Role_Switcher
{

    /**
     * Session key for storing view mode
     */
    const VIEW_MODE_KEY = 'lgp_admin_view_mode';

    /**
     * Initialize role switcher
     */
    public function __construct()
    {
        add_action('init', array( $this, 'handle_role_switch' ));
        add_action('wp_ajax_lgp_switch_role', array( $this, 'ajax_switch_role' ));
        add_action('admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100);
        add_filter('lgp_current_user_role', array( $this, 'filter_user_role' ));
    }

    /**
     * Check if current user is admin
     *
     * @return bool
     */
    public function is_admin_user()
    {
        return current_user_can('manage_options') || current_user_can('administrator');
    }

    /**
     * Get current view mode
     *
     * @return string 'support', 'partner', or actual user role
     */
    public function get_view_mode()
    {
        if (! $this->is_admin_user() ) {
            return $this->get_actual_user_role();
        }

        $view_mode = get_user_meta(get_current_user_id(), self::VIEW_MODE_KEY, true);

        if (empty($view_mode) || ! in_array($view_mode, array( 'support', 'partner' )) ) {
            return $this->get_actual_user_role();
        }

        return $view_mode;
    }

    /**
     * Get actual user role
     *
     * @return string
     */
    private function get_actual_user_role()
    {
        $user = wp_get_current_user();

        if (in_array('lgp_support', $user->roles) ) {
            return 'support';
        }

        if (in_array('lgp_partner', $user->roles) ) {
            return 'partner';
        }

        if (current_user_can('administrator') ) {
            return 'support'; // Default admin view
        }

        return 'guest';
    }

    /**
     * Set view mode
     *
     * @param  string $mode 'support' or 'partner'
     * @return bool
     */
    public function set_view_mode( $mode )
    {
        if (! $this->is_admin_user() ) {
            return false;
        }

        if (! in_array($mode, array( 'support', 'partner' )) ) {
            return false;
        }

        update_user_meta(get_current_user_id(), self::VIEW_MODE_KEY, $mode);

        // Log the switch for audit purposes
        do_action(
            'lgp_role_switched',
            array(
            'user_id'   => get_current_user_id(),
            'from'      => $this->get_actual_user_role(),
            'to'        => $mode,
            'timestamp' => current_time('mysql'),
            )
        );

        return true;
    }

    /**
     * Clear view mode (return to actual role)
     *
     * @return bool
     */
    public function clear_view_mode()
    {
        if (! $this->is_admin_user() ) {
            return false;
        }

        delete_user_meta(get_current_user_id(), self::VIEW_MODE_KEY);
        return true;
    }

    /**
     * Handle role switch request
     */
    public function handle_role_switch()
    {
        if (! isset($_GET['lgp_switch_to']) || ! isset($_GET['_wpnonce']) ) {
            return;
        }

        if (! wp_verify_nonce($_GET['_wpnonce'], 'lgp_switch_role') ) {
            wp_die(__('Security check failed', 'loungenie-portal'));
        }

        if (! $this->is_admin_user() ) {
            wp_die(__('Insufficient permissions', 'loungenie-portal'));
        }

        $switch_to = sanitize_text_field($_GET['lgp_switch_to']);

        if ($switch_to === 'actual' ) {
            $this->clear_view_mode();
        } else {
            $this->set_view_mode($switch_to);
        }

        // Redirect to dashboard
        $redirect_url = remove_query_arg(array( 'lgp_switch_to', '_wpnonce' ));
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * AJAX handler for role switching
     */
    public function ajax_switch_role()
    {
        check_ajax_referer('lgp_role_switcher', 'nonce');

        if (! $this->is_admin_user() ) {
            wp_send_json_error(array( 'message' => 'Insufficient permissions' ));
        }

        $switch_to = sanitize_text_field($_POST['role']);

        if ($switch_to === 'actual' ) {
            $this->clear_view_mode();
        } elseif (! $this->set_view_mode($switch_to) ) {
            wp_send_json_error(array( 'message' => 'Invalid role' ));
        }

        wp_send_json_success(
            array(
            'message'      => 'View mode switched successfully',
            'current_mode' => $this->get_view_mode(),
            'redirect_url' => home_url('/dashboard/'),
            )
        );
    }

    /**
     * Add admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_menu( $wp_admin_bar )
    {
        if (! $this->is_admin_user() || ! is_admin_bar_showing() ) {
            return;
        }

        $current_mode = $this->get_view_mode();
        $actual_role  = $this->get_actual_user_role();
        $is_switched  = ( $current_mode !== $actual_role );

        $title = $is_switched
        ? sprintf('👁️ Viewing as: %s', ucfirst($current_mode))
        : sprintf('Admin View: %s', ucfirst($current_mode));

        $wp_admin_bar->add_node(
            array(
            'id'    => 'lgp_role_switcher',
            'title' => $title,
            'href'  => '#',
            'meta'  => array(
                    'class' => 'lgp-role-switcher-menu',
            ),
            )
        );

        // Add submenu items
        $wp_admin_bar->add_node(
            array(
            'parent' => 'lgp_role_switcher',
            'id'     => 'lgp_view_support',
            'title'  => ( $current_mode === 'support' ? '✓ ' : '' ) . 'Support View',
            'href'   => wp_nonce_url(add_query_arg('lgp_switch_to', 'support'), 'lgp_switch_role'),
            )
        );

        $wp_admin_bar->add_node(
            array(
            'parent' => 'lgp_role_switcher',
            'id'     => 'lgp_view_partner',
            'title'  => ( $current_mode === 'partner' ? '✓ ' : '' ) . 'Partner View',
            'href'   => wp_nonce_url(add_query_arg('lgp_switch_to', 'partner'), 'lgp_switch_role'),
            )
        );

        if ($is_switched ) {
            $wp_admin_bar->add_node(
                array(
                'parent' => 'lgp_role_switcher',
                'id'     => 'lgp_view_actual',
                'title'  => 'Return to Actual Role',
                'href'   => wp_nonce_url(add_query_arg('lgp_switch_to', 'actual'), 'lgp_switch_role'),
                'meta'   => array( 'class' => 'lgp-return-actual' ),
                )
            );
        }
    }

    /**
     * Filter user role for plugin functionality
     *
     * @param  string $role
     * @return string
     */
    public function filter_user_role( $role )
    {
        if ($this->is_admin_user() ) {
            return $this->get_view_mode();
        }
        return $role;
    }

    /**
     * Get role switcher widget HTML
     *
     * @return string
     */
    public function get_switcher_widget()
    {
        if (! $this->is_admin_user() ) {
            return '';
        }

        $current_mode = $this->get_view_mode();
        $actual_role  = $this->get_actual_user_role();
        $is_switched  = ( $current_mode !== $actual_role );

        ob_start();
        ?>
        <div class="lgp-role-switcher-widget" data-current-mode="<?php echo esc_attr($current_mode); ?>">
            <div class="lgp-role-switcher-header">
                <svg class="lgp-switcher-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 2a6 6 0 110 12 6 6 0 010-12zm-1 3v4l3 1.5.5-1-2.5-1.25V7H9z"/>
                </svg>
                <span class="lgp-switcher-title">
        <?php if ($is_switched ) : ?>
                        <span class="lgp-viewing-badge">Viewing as</span>
                        <strong><?php echo esc_html(ucfirst($current_mode)); ?></strong>
                    <?php else : ?>
                        Role Switcher
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="lgp-role-options">
                <button type="button" 
                        class="lgp-role-option <?php echo ( $current_mode === 'support' ? 'active' : '' ); ?>"
                        data-role="support">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zm0 16a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                    </svg>
                    Support View
                </button>
                
                <button type="button" 
                        class="lgp-role-option <?php echo ( $current_mode === 'partner' ? 'active' : '' ); ?>"
                        data-role="partner">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.455.894l-4-2-4 2A1 1 0 015 16V4z"/>
                    </svg>
                    Partner View
                </button>
                
        <?php if ($is_switched ) : ?>
                <button type="button" 
                        class="lgp-role-option lgp-role-reset"
                        data-role="actual">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    Return to Actual
                </button>
        <?php endif; ?>
            </div>
            
            <div class="lgp-switcher-footer">
                <small>Actual role: <strong><?php echo esc_html(ucfirst($actual_role)); ?></strong></small>
            </div>
        </div>
        
        <script>
        (function() {
            const widget = document.querySelector('.lgp-role-switcher-widget');
            if (!widget) return;
            
            const buttons = widget.querySelectorAll('.lgp-role-option');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    
                    // Show loading state
                    buttons.forEach(btn => btn.disabled = true);
                    this.classList.add('loading');
                    
                    // Send AJAX request
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'lgp_switch_role',
                            role: role,
                            nonce: '<?php echo wp_create_nonce('lgp_role_switcher'); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to dashboard
                            window.location.href = data.data.redirect_url;
                        } else {
                            alert(data.data.message || 'Failed to switch role');
                            buttons.forEach(btn => btn.disabled = false);
                            this.classList.remove('loading');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        buttons.forEach(btn => btn.disabled = false);
                        this.classList.remove('loading');
                    });
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize as singleton and expose helper for templates
if (! isset($GLOBALS['lgp_role_switcher_instance']) ) {
    $GLOBALS['lgp_role_switcher_instance'] = new LGP_Role_Switcher();
}

if (! function_exists('lgp_role_switcher_widget') ) {
    /**
     * Render the role switcher widget markup for admins.
     *
     * @return string
     */
    function lgp_role_switcher_widget()
    {
        if (isset($GLOBALS['lgp_role_switcher_instance']) && $GLOBALS['lgp_role_switcher_instance'] instanceof LGP_Role_Switcher ) {
            return $GLOBALS['lgp_role_switcher_instance']->get_switcher_widget();
        }
        return '';
    }
}
