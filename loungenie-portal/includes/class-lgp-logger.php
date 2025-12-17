<?php
/**
 * Lightweight audit and notification logger
 * (Test-focused helper; safe for shared hosting)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LGP_Logger' ) ) {
	class LGP_Logger {
		/**
		 * Log a generic audit event.
		 *
		 * @param int      $user_id
		 * @param string   $action
		 * @param int|null $company_id
		 * @param array    $meta
		 * @return bool|int   insert result
		 */
		public static function log_event( $user_id, $action, $company_id = null, $meta = array() ) {
			global $wpdb;
			if ( ! isset( $wpdb ) ) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_audit_log';
			$data  = array(
				'user_id'    => (int) $user_id,
				'action'     => $action,
				'company_id' => $company_id,
				'meta'       => wp_json_encode( $meta ),
				'created_at' => current_time( 'mysql', true ),
			);

			if ( method_exists( $wpdb, 'insert' ) ) {
				return $wpdb->insert( $table, $data );
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
		public static function log_notification( $recipient_id, $channel, $priority, $ticket_id = null, $company_id = null, $meta = array() ) {
			global $wpdb;
			if ( ! isset( $wpdb ) ) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_notification_log';
			$data  = array(
				'recipient'  => $recipient_id,
				'channel'    => $channel,
				'priority'   => $priority,
				'ticket_id'  => $ticket_id,
				'company_id' => $company_id,
				'meta'       => wp_json_encode( $meta ),
				'created_at' => current_time( 'mysql', true ),
			);

			if ( method_exists( $wpdb, 'insert' ) ) {
				return $wpdb->insert( $table, $data );
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
		public static function log( $type, $action, $meta = array(), $user_id = null, $company_id = null ) {
			global $wpdb;
			if ( ! isset( $wpdb ) ) {
				return false;
			}

			$table = $wpdb->prefix . 'lgp_audit_log';
			$data  = array(
				'user_id'    => $user_id ?? get_current_user_id(),
				'action'     => $type . '_' . $action,
				'company_id' => $company_id,
				'meta'       => wp_json_encode( $meta ),
				'created_at' => current_time( 'mysql', true ),
			);

			if ( method_exists( $wpdb, 'insert' ) ) {
				return $wpdb->insert( $table, $data );
			}

			return false;
		}
	}
}
