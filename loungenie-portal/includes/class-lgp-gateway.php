<?php
use LounGenie\Portal\LGP_Auth;
/**
 * Gateway Management Class (Support-Only)
 * Manages LounGenie gateways with audit logging
 *
 * @package LounGenie Portal
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Gateway
{

    /**
     * Get all gateways (support-only)
     *
     * @param  array $filters Optional filters (company_id, call_button, search)
     * @return array
     */
    public static function get_all( $filters = array() )
    {
        if (! LGP_Auth::is_support() ) {
            return array();
        }

        global $wpdb;
        $table           = $wpdb->prefix . 'lgp_gateways';
        $companies_table = $wpdb->prefix . 'lgp_companies';

        $sql = "SELECT g.*, c.name as company_name, c.type as company_type 
                FROM $table g 
                LEFT JOIN $companies_table c ON g.company_id = c.id 
                WHERE 1=1";

        $params = array();

        if (! empty($filters['company_id']) ) {
            $sql     .= ' AND g.company_id = %d';
            $params[] = (int) $filters['company_id'];
        }

        if (isset($filters['call_button']) && $filters['call_button'] !== '' ) {
            $sql     .= ' AND g.call_button = %d';
            $params[] = (int) $filters['call_button'];
        }

        if (! empty($filters['search']) ) {
            $sql     .= ' AND (g.channel_number LIKE %s OR g.gateway_address LIKE %s OR c.name LIKE %s)';
            $search   = '%' . $wpdb->esc_like($filters['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY c.name ASC, g.channel_number ASC';

        if (! empty($params) ) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get single gateway by ID
     *
     * @param  int $id
     * @return object|null
     */
    public static function get( $id )
    {
        if (! LGP_Auth::is_support() ) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lgp_gateways';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Create gateway (support-only, with audit log)
     *
     * @param  array $data
     * @return int|false Gateway ID or false on failure
     */
    public static function create( $data )
    {
        if (! LGP_Auth::is_support() ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lgp_gateways';

        $insert_data = array(
        'company_id'         => absint($data['company_id'] ?? 0),
        'channel_number'     => sanitize_text_field($data['channel_number'] ?? ''),
        'gateway_address'    => sanitize_text_field($data['gateway_address'] ?? ''),
        'unit_capacity'      => absint($data['unit_capacity'] ?? 0),
        'call_button'        => absint($data['call_button'] ?? 0),
        'included_equipment' => sanitize_textarea_field($data['included_equipment'] ?? ''),
        'admin_password'     => sanitize_text_field($data['admin_password'] ?? ''),
        );

        $result = $wpdb->insert($table, $insert_data, array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' ));

        if ($result ) {
            $gateway_id = $wpdb->insert_id;
            self::log_action('create', $gateway_id, $insert_data);
            return $gateway_id;
        }

        return false;
    }

    /**
     * Update gateway (support-only, with audit log)
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     */
    public static function update( $id, $data )
    {
        if (! LGP_Auth::is_support() ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lgp_gateways';

        $update_data = array(
        'company_id'         => absint($data['company_id'] ?? 0),
        'channel_number'     => sanitize_text_field($data['channel_number'] ?? ''),
        'gateway_address'    => sanitize_text_field($data['gateway_address'] ?? ''),
        'unit_capacity'      => absint($data['unit_capacity'] ?? 0),
        'call_button'        => absint($data['call_button'] ?? 0),
        'included_equipment' => sanitize_textarea_field($data['included_equipment'] ?? ''),
        );

        if (! empty($data['admin_password']) ) {
            $update_data['admin_password'] = sanitize_text_field($data['admin_password']);
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' ),
            array( '%d' )
        );

        if ($result !== false ) {
            self::log_action('update', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * Delete gateway (support-only, with audit log)
     *
     * @param  int $id
     * @return bool
     */
    public static function delete( $id )
    {
        if (! LGP_Auth::is_support() ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lgp_gateways';

        $gateway = self::get($id);
        if (! $gateway ) {
            return false;
        }

        $result = $wpdb->delete($table, array( 'id' => $id ), array( '%d' ));

        if ($result ) {
            self::log_action('delete', $id, (array) $gateway);
            return true;
        }

        return false;
    }

    /**
     * Log gateway action to audit log
     *
     * @param string $action
     * @param int    $gateway_id
     * @param array  $data
     */
    private static function log_action( $action, $gateway_id, $data )
    {
        if (! class_exists('LGP_Logger') ) {
            return;
        }

        $user       = wp_get_current_user();
        $company_id = $data['company_id'] ?? 0;

        LGP_Logger::log(
            'gateway',
            $action,
            array(
            'gateway_id' => $gateway_id,
            'user_id'    => $user->ID,
            'user_email' => $user->user_email,
            'company_id' => $company_id,
            'data'       => $data,
            )
        );
    }

    /**
     * Test gateway signal (support-only, with audit log)
     *
     * @param  int $id
     * @return array Status and message
     */
    public static function test_signal( $id )
    {
        if (! LGP_Auth::is_support() ) {
            return array(
            'success' => false,
            'message' => 'Unauthorized',
            );
        }

        $gateway = self::get($id);
        if (! $gateway ) {
            return array(
            'success' => false,
            'message' => 'Gateway not found',
            );
        }

        // Simulate signal test (in production, this would ping the actual gateway)
        self::log_action(
            'test_signal',
            $id,
            array(
            'gateway_address' => $gateway->gateway_address,
            'result'          => 'simulated_success',
            )
        );

        return array(
        'success' => true,
        'message' => 'Signal test initiated for gateway ' . esc_html($gateway->channel_number),
        );
    }

    /**
     * Get units connected to gateway
     *
     * @param  int $gateway_id
     * @return array
     */
    public static function get_connected_units( $gateway_id )
    {
        if (! LGP_Auth::is_support() ) {
            return array();
        }

        global $wpdb;
        $units_table    = $wpdb->prefix . 'lgp_units';
        $gateways_table = $wpdb->prefix . 'lgp_gateways';

        // Get gateway company
        $gateway = self::get($gateway_id);
        if (! $gateway ) {
            return array();
        }

        // Return units from same company (simplified relationship)
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $units_table WHERE company_id = %d ORDER BY id ASC",
                $gateway->company_id
            )
        );
    }
}
