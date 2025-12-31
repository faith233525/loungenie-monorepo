<?php

/**
 * Dashboard Metrics API
 * Returns aggregated metrics for Support/Partner users
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Dashboard_API
{

    public static function init()
    {
        add_action('rest_api_init', array( __CLASS__, 'register_routes' ));
    }

    public static function register_routes()
    {
        $api = new self();
        register_rest_route(
            'lgp/v1',
            '/dashboard',
            array(
            'methods'             => 'GET',
            'callback'            => array( $api, 'get_metrics' ),
            'permission_callback' => array( $api, 'check_portal_access' ),
            )
        );
    }

    public function check_portal_access()
    {
        if (! is_user_logged_in() ) {
            return false;
        }
        return LGP_Auth::is_support() || LGP_Auth::is_partner();
    }

    public function get_metrics( $request )
    {
        global $wpdb;

        // Enhanced authentication check
        if (! is_user_logged_in() ) {
            return new WP_Error(
                'unauthorized',
                'Authentication required',
                array( 'status' => 401 )
            );
        }

        // Role-based access control
        $is_support = LGP_Auth::is_support();
        $is_partner = LGP_Auth::is_partner();

        if (! $is_support && ! $is_partner ) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions to access dashboard',
                array( 'status' => 403 )
            );
        }

        // Get company context for partners
        $company_id = LGP_Auth::get_user_company_id();

        if (! $is_support && empty($company_id) ) {
            return new WP_Error(
                'invalid_company',
                'No company associated with user account',
                array( 'status' => 400 )
            );
        }

        // Database tables
        $units_table    = $wpdb->prefix . 'lgp_units';
        $tickets_table  = $wpdb->prefix . 'lgp_tickets';
        $requests_table = $wpdb->prefix . 'lgp_service_requests';

        // Apply role-based filtering at database level
        if ($is_support ) {
            // Support sees all companies
            $where_units   = '1=1';
            $where_company = '1=1';
        } else {
            // Partner sees only their company
            $where_units   = $wpdb->prepare('company_id = %d', $company_id);
            $where_company = $wpdb->prepare('sr.company_id = %d', $company_id);
        }

        // Units total (use prepared statements when scoping by company)
        if ($is_support ) {
            $units_result = $wpdb->get_var("SELECT COUNT(*) FROM {$units_table}");
            $total_units  = ! empty($units_result) ? (int) $units_result : 0;
        } else {
            $units_result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$units_table} WHERE company_id = %d",
                    $company_id
                )
            );
            $total_units  = ! empty($units_result) ? (int) $units_result : 0;
        }

        // Ticket counts are derived by joining service requests -> tickets for company scope
        if ($is_support ) {
            $tickets_result = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE t.status NOT IN ('resolved','closed')"
            );
            $active_tickets = ! empty($tickets_result) ? (int) $tickets_result : 0;
        } else {
            $tickets_result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND t.status NOT IN ('resolved','closed')",
                    $company_id
                )
            );
            $active_tickets = ! empty($tickets_result) ? (int) $tickets_result : 0;
        }

        if ($is_support ) {
            $today_result   = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE DATE(t.updated_at) = CURDATE() AND t.status IN ('resolved','closed')"
            );
            $resolved_today = ! empty($today_result) ? (int) $today_result : 0;
        } else {
            $today_result   = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND DATE(t.updated_at) = CURDATE() AND t.status IN ('resolved','closed')",
                    $company_id
                )
            );
            $resolved_today = ! empty($today_result) ? (int) $today_result : 0;
        }

        // Average resolution time (hours)
        if ($is_support ) {
            $resolution_data = $wpdb->get_var(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
				 FROM {$tickets_table} t 
				 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
				 WHERE t.status IN ('resolved','closed') AND t.updated_at IS NOT NULL"
            );
        } else {
            $resolution_data = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) 
					 FROM {$tickets_table} t 
					 JOIN {$requests_table} sr ON sr.id = t.service_request_id 
					 WHERE sr.company_id = %d AND t.status IN ('resolved','closed') AND t.updated_at IS NOT NULL",
                    $company_id
                )
            );
        }
        $avg_resolution = $resolution_data !== null ? round((float) $resolution_data, 1) : null;

        // Log access for audit trail
        LGP_Logger::log_event(
            get_current_user_id(),
            'dashboard_access',
            $is_support ? null : $company_id,
            array(
            'role'             => $is_support ? 'support' : 'partner',
            'metrics_accessed' => true,
            )
        );

        return rest_ensure_response(
            array(
            'total_units'        => $total_units,
            'active_tickets'     => $active_tickets,
            'resolved_today'     => $resolved_today,
            'average_resolution' => $avg_resolution,
            'role'               => $is_support ? 'support' : 'partner',
            'company_id'         => $is_support ? null : $company_id,
            )
        );
    }
}

LGP_Dashboard_API::init();
