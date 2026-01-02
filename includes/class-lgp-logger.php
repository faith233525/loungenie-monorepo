<?php

/**
 * Lightweight audit and notification logger
 * (Test-focused helper; safe for shared hosting)
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('LGP_Logger')) {
	class LGP_Logger
	{
		/**
		 * Initialize logger
		 */
		public static function init()
		{
			// Logger is passive - no hooks needed
			// Just provides static methods for audit logging

			// SHARED HOSTING: Schedule weekly log cleanup
			if (! wp_next_scheduled('lgp_cleanup_old_logs')) {
				wp_schedule_event(time(), 'weekly', 'lgp_cleanup_old_logs');
			}
			add_action('lgp_cleanup_old_logs', array(__CLASS__, 'cleanup_old_logs'));
		}

		/**
		 * Log a generic audit event.
		 *
		 * @param int      $user_id
		 * @param string   $action
		 * @param int|null $company_id
		 * @param array    $meta
		 * @return bool|int   insert result
		 */
		public static function log_event($user_id, $action, $company_id = null, $meta = array())
		{
			global $wpdb;
			if (! isset($wpdb)) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_audit_log';
			$data  = array(
				'user_id'    => (int) $user_id,
				'action'     => $action,
				'company_id' => $company_id,
				'meta'       => wp_json_encode($meta),
				'created_at' => current_time('mysql', true),
			);

			if (method_exists($wpdb, 'insert')) {
				return $wpdb->insert($table, $data);
			}

			return false;
		}

		/**
		 * Log a notification send attempt.
		 *
		 * @param int|string $recipient_id user ID or email
		 * @param string     $channel      email|portal
		 * @param string     $priority     low|medium|high|urgent
		 * @param int|null   $ticket_id
		 * @param int|null   $company_id
		 * @param array      $meta
		 * @return bool|int
		 */
		public static function log_notification($recipient_id, $channel, $priority, $ticket_id = null, $company_id = null, $meta = array())
		{
			global $wpdb;
			if (! isset($wpdb)) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_notification_log';
			$data  = array(
				'recipient'  => $recipient_id,
				'channel'    => $channel,
				'priority'   => $priority,
				'ticket_id'  => $ticket_id,
				'company_id' => $company_id,
				'meta'       => wp_json_encode($meta),
				'created_at' => current_time('mysql', true),
			);

			if (method_exists($wpdb, 'insert')) {
				return $wpdb->insert($table, $data);
			}

			return false;
		}

		/**
		 * Generic log method for audit logging
		 *
		 * @param string   $type Type of log (gateway, ticket, etc)
		 * @param string   $action Action performed
		 * @param array    $meta Metadata array
		 * @param int|null $user_id User ID
		 * @param int|null $company_id Company ID
		 * @return bool|int
		 */
		public static function log($type, $action, $meta = array(), $user_id = null, $company_id = null)
		{
			global $wpdb;
			if (! isset($wpdb)) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_audit_log';
			$data  = array(
				'user_id'    => $user_id ?? get_current_user_id(),
				'action'     => $type . '_' . $action,
				'company_id' => $company_id,
				'meta'       => wp_json_encode($meta),
				'created_at' => current_time('mysql', true),
			);

			if (method_exists($wpdb, 'insert')) {
				return $wpdb->insert($table, $data);
			}

			return false;
		}

		/**
		 * Cleanup old logs to prevent database bloat on shared hosting
		 * Removes logs older than 90 days
		 *
		 * @return int Number of deleted rows
		 */
		public static function cleanup_old_logs()
		{
			global $wpdb;
			if (! isset($wpdb)) {
				return 0;
			}

			$audit_table = $wpdb->prefix . 'lgp_audit_log';

			// SHARED HOSTING: Delete logs older than 90 days
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$audit_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
					90
				)
			);

			// Log the cleanup
			if ($deleted > 0) {
				error_log(sprintf('LGP Logger: Cleaned up %d old audit log entries', $deleted));
			}

			// SHARED HOSTING: Optimize table after cleanup
			$wpdb->query("OPTIMIZE TABLE {$audit_table}");

			return (int) $deleted;
		}

		/**
		 * Get log statistics
		 *
		 * @return array Log statistics
		 */
		public static function get_stats()
		{
			global $wpdb;
			if (! isset($wpdb)) {
				return array();
			}

			$audit_table = $wpdb->prefix . 'lgp_audit_log';

			$total     = $wpdb->get_var("SELECT COUNT(*) FROM {$audit_table}");
			$today     = $wpdb->get_var("SELECT COUNT(*) FROM {$audit_table} WHERE DATE(created_at) = CURDATE()");
			$this_week = $wpdb->get_var("SELECT COUNT(*) FROM {$audit_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

			// Get table size
			$size = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT (data_length + index_length) 
					FROM information_schema.TABLES 
					WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$audit_table
				)
			);

			return array(
				'total_entries'     => (int) $total,
				'entries_today'     => (int) $today,
				'entries_this_week' => (int) $this_week,
				'table_size'        => size_format((int) $size),
				'table_size_bytes'  => (int) $size,
			);
		}
	}
}
