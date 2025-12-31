<?php

/**
 * Central Plugin Initialization Loader
 * Single point of orchestration for all plugin components
 * Ensures predictable startup order and no race conditions
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Loader
{


    /**
     * Initialize all plugin components in dependency order
     *
     * Order matters:
     * 1. Database (creates tables if needed)
     * 2. Security (headers, CSP)
     * 3. Capabilities (role → capability mapping)
     * 4. Authentication (relies on capabilities)
     * 5. Router (relies on auth)
     * 6. Assets (relies on router, auth)
     * 7. REST API (relies on auth, capabilities)
     * 8. Integrations (rely on everything above)
     */
    public static function init()
    {
        // Phase 1: Foundation
        self::maybe_init_class('LGP_Database');      // Schema, migrations
        self::maybe_init_class('LGP_Security');      // CSP headers, CORS
        self::maybe_init_class('LGP_Capabilities');  // Capability registry

        // Phase 2: Core
        self::maybe_init_class('LGP_Auth');          // Auth checks (uses capabilities)
        self::maybe_init_class('LGP_Router');        // Route handling (uses auth)
        self::maybe_init_class('LGP_Assets');        // Asset enqueue (conditional on route/auth)

        // Phase 3: APIs & Logging
        self::maybe_init_class('LGP_Logger');        // Audit logging
        self::maybe_init_class('LGP_Notifications'); // Alert system
        self::maybe_init_class('LGP_Company_Colors'); // Color aggregation (Phase 2B)
        self::register_rest_apis();

        // Phase 4: Features (optional, independently initialized)
        if (! self::use_new_email_pipeline() ) {
            // Legacy POP3/Graph hybrid handler
            self::maybe_init_class('LGP_Email_Handler');
        }
        self::maybe_init_class('LGP_Microsoft_SSO');
        self::maybe_init_class('LGP_HubSpot');
        self::maybe_init_class('LGP_Outlook');
        self::maybe_init_class('LGP_System_Health');
        self::maybe_init_class('LGP_CSV_Partner_Import'); // CSV Partner Import

        // Admin Tools
        if (is_admin() || current_user_can('manage_options') ) {
            $role_switcher_file = plugin_dir_path(__FILE__) . 'class-lgp-role-switcher.php';
            if (file_exists($role_switcher_file) ) {
                include_once $role_switcher_file;
            } else {
                // Avoid fatal errors if the optional admin helper is absent
                if (function_exists('error_log') ) {
                    error_log('LGP_Loader: class-lgp-role-switcher.php missing; skipping admin role switcher');
                }
            }
        }
    }

    /**
     * Register all REST API endpoints
     * Centralized so we can version and extend easily
     *
     * Note: service-notes.php and audit-log.php self-register via add_action
     */
    private static function maybe_init_class( $class )
    {
        if (class_exists($class) && method_exists($class, 'init') ) {
            $class::init();
            return true;
        }

        if (function_exists('error_log') ) {
            error_log(sprintf('LGP_Loader: %s init skipped (class or init missing)', $class));
        }

        return false;
    }

    private static function register_rest_apis()
    {
        // Core API endpoints
        if (class_exists('LGP_Companies_API') ) {
            LGP_Companies_API::init();
        }
        if (class_exists('LGP_Units_API') ) {
            LGP_Units_API::init();
        }
        if (class_exists('LGP_Tickets_API') ) {
            LGP_Tickets_API::init();
        }
        if (class_exists('LGP_Gateways_API') ) {
            LGP_Gateways_API::init();
        }
        if (file_exists(LGP_PLUGIN_DIR . 'api/knowledge-center.php') ) {
            include_once LGP_PLUGIN_DIR . 'api/knowledge-center.php';
        }
        if (class_exists('LGP_Knowledge_Center_API') ) {
            LGP_Knowledge_Center_API::init();
        } elseif (class_exists('LGP_Help_Guides_API') ) {
            // Fallback for legacy class name if present
            LGP_Help_Guides_API::init();
        }
        if (class_exists('LGP_Attachments_API') ) {
            LGP_Attachments_API::init();
        }

        // Feature API endpoints - with file existence checks
        if (file_exists(LGP_PLUGIN_DIR . 'api/dashboard.php') ) {
            include_once LGP_PLUGIN_DIR . 'api/dashboard.php';
            if (class_exists('LGP_Dashboard_API') ) {
                LGP_Dashboard_API::init();
            }
        }

        if (file_exists(LGP_PLUGIN_DIR . 'api/map.php') ) {
            include_once LGP_PLUGIN_DIR . 'api/map.php';
            if (class_exists('LGP_Map_API') ) {
                LGP_Map_API::init();
            }
        }

        // Service Notes and Audit Log self-register when loaded (functional approach)
    }

    /**
     * Get current schema version
     */
    public static function get_schema_version()
    {
        return get_option('lgp_schema_version', '1.0.0');
    }

    /**
     * Check if migrations are needed
     */
    public static function needs_migration()
    {
        $current = self::get_schema_version();
        return version_compare($current, LGP_VERSION, '<');
    }

    /**
     * Log initialization for debugging
     */
    public static function log_init_event( $component, $status = 'ok', $message = '' )
    {
        if (defined('LGP_DEBUG') && LGP_DEBUG ) {
            // eslint-disable-next-line no-console
            error_log("LGP_Loader: [{$component}] {$status}" . ( $message ? " - {$message}" : '' ));
        }
    }

    /**
     * Determine if the new Graph-based email pipeline is enabled.
     * Priority: Constant > Env var > Option.
     */
    private static function use_new_email_pipeline()
    {
        // Constant override
        if (defined('LGP_EMAIL_PIPELINE') ) {
            return 'new' === LGP_EMAIL_PIPELINE || true === LGP_EMAIL_PIPELINE || 1 === LGP_EMAIL_PIPELINE;
        }

        // Env var
        $env = getenv('LGP_EMAIL_PIPELINE');
        if ($env ) {
            $env = strtolower(trim($env));
            return in_array($env, array( 'new', 'true', '1', 'on' ), true);
        }

        // Option flag
        if (function_exists('get_option') ) {
            return (bool) get_option('lgp_use_new_email_pipeline', false);
        }

        return false;
    }
}
