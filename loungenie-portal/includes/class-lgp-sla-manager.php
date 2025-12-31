<?php

/**
 * SLA Manager
 * Service Level Agreement tracking and enforcement
 *
 * @package LounGenie Portal
 * @since   1.9.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class LGP_SLA_Manager
{

    /**
     * SLA response times in hours
     */
    const SLA_TIMES = array(
        'critical' => 4,   // 4 hours
        'high'     => 24,  // 24 hours
        'medium'   => 48,  // 48 hours (2 days)
        'low'      => 120, // 120 hours (5 days)
    );

    /**
     * Calculate SLA due date
     *
     * @param  string $priority   Priority level
     * @param  string $created_at Creation timestamp
     * @return string Due date in MySQL format
     */
    public static function calculate_due_date($priority, $created_at = null)
    {
        $created_at = $created_at ?? current_time('mysql');
        $hours      = self::SLA_TIMES[$priority] ?? self::SLA_TIMES['medium'];

        $created_timestamp = strtotime($created_at);
        $due_timestamp     = $created_timestamp + ($hours * 3600);

        return gmdate('Y-m-d H:i:s', $due_timestamp);
    }

    /**
     * Get time remaining until SLA breach
     *
     * @param  string $sla_due_date SLA due date
     * @return array Array with hours, minutes, status
     */
    public static function get_time_remaining($sla_due_date)
    {
        $now           = current_time('timestamp');
        $due_timestamp = strtotime($sla_due_date);
        $diff_seconds  = $due_timestamp - $now;

        if ($diff_seconds < 0) {
            return array(
                'status'       => 'breached',
                'hours'        => 0,
                'minutes'      => 0,
                'display'      => 'SLA BREACHED',
                'color'        => '#DC2626',
                'badge_class'  => 'lgp-badge-danger',
                'overdue_by'   => abs($diff_seconds),
            );
        }

        $hours   = floor($diff_seconds / 3600);
        $minutes = floor(($diff_seconds % 3600) / 60);

        // Determine status based on remaining time
        $total_hours = $hours + ($minutes / 60);

        if ($total_hours < 2) {
            $status = 'critical';
            $color  = '#DC2626'; // Red
            $badge  = 'lgp-badge-danger';
        } elseif ($total_hours < 8) {
            $status = 'warning';
            $color  = '#D97706'; // Orange
            $badge  = 'lgp-badge-warning';
        } else {
            $status = 'good';
            $color  = '#16A34A'; // Green
            $badge  = 'lgp-badge-success';
        }

        return array(
            'status'      => $status,
            'hours'       => $hours,
            'minutes'     => $minutes,
            'display'     => sprintf('%dh %dm remaining', $hours, $minutes),
            'color'       => $color,
            'badge_class' => $badge,
        );
    }

    /**
     * Check if ticket is breaching SLA
     *
     * @param  string $sla_due_date SLA due date
     * @return bool
     */
    public static function is_breaching($sla_due_date)
    {
        return strtotime($sla_due_date) < current_time('timestamp');
    }

    /**
     * Get SLA status badge HTML
     *
     * @param  string $sla_due_date SLA due date
     * @return string HTML badge
     */
    public static function get_status_badge($sla_due_date)
    {
        $time_remaining = self::get_time_remaining($sla_due_date);

        return sprintf(
            '<span class="lgp-badge %s" style="background-color: %s; color: white;">⏱️ %s</span>',
            esc_attr($time_remaining['badge_class']),
            esc_attr($time_remaining['color']),
            esc_html($time_remaining['display'])
        );
    }

    /**
     * Update ticket SLA on creation
     *
     * @param int    $ticket_id Ticket ID
     * @param string $priority  Priority level
     */
    public static function set_ticket_sla($ticket_id, $priority)
    {
        global $wpdb;

        $table    = $wpdb->prefix . 'lgp_tickets';
        $due_date = self::calculate_due_date($priority);

        $wpdb->update(
            $table,
            array(
                'sla_due_date'    => $due_date,
                'priority_level'  => $priority,
            ),
            array('id' => $ticket_id)
        );
    }

    /**
     * Get tickets breaching SLA
     *
     * @return array Ticket IDs
     */
    public static function get_breaching_tickets()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'lgp_tickets';

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM $table 
				WHERE sla_due_date IS NOT NULL 
				AND sla_due_date < %s 
				AND status NOT IN ('resolved', 'closed')
				ORDER BY sla_due_date ASC",
                current_time('mysql')
            )
        );
    }
}
