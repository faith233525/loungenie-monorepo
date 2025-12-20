<?php
/**
 * Standardized REST Error Codes
 * Consistent error handling across all API endpoints
 * Enables cleaner frontend handling and easier log analysis
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_REST_Errors {

	/**
	 * Standard error codes and messages
	 */
	const ERROR_CODES = array(
		// 400-level: Client errors
		'lgp_validation_failed' => array(
			'status'  => 400,
			'message' => 'Validation failed',
		),
		'lgp_missing_field'     => array(
			'status'  => 400,
			'message' => 'Missing required field',
		),
		'lgp_invalid_type'      => array(
			'status'  => 400,
			'message' => 'Invalid data type',
		),
		'lgp_invalid_format'    => array(
			'status'  => 400,
			'message' => 'Invalid format',
		),

		// 401-level: Auth errors
		'lgp_not_authenticated' => array(
			'status'  => 401,
			'message' => 'Authentication required',
		),

		// 403-level: Permission errors
		'lgp_forbidden'         => array(
			'status'  => 403,
			'message' => 'Access denied',
		),
		'lgp_insufficient_caps' => array(
			'status'  => 403,
			'message' => 'Insufficient permissions',
		),

		// 404-level: Not found
		'lgp_not_found'         => array(
			'status'  => 404,
			'message' => 'Resource not found',
		),

		// 409-level: Conflict
		'lgp_conflict'          => array(
			'status'  => 409,
			'message' => 'Resource conflict',
		),
		'lgp_duplicate'         => array(
			'status'  => 409,
			'message' => 'Resource already exists',
		),

		// 429-level: Rate limiting
		'lgp_rate_limited'      => array(
			'status'  => 429,
			'message' => 'Too many requests',
		),

		// 500-level: Server errors
		'lgp_database_error'    => array(
			'status'  => 500,
			'message' => 'Database error',
		),
		'lgp_internal_error'    => array(
			'status'  => 500,
			'message' => 'Internal server error',
		),
		'lgp_integration_error' => array(
			'status'  => 500,
			'message' => 'External service error',
		),
	);

	/**
	 * Create a standardized error response
	 *
	 * @param string $code    Error code (lgp_*)
	 * @param string $detail  Additional detail message
	 * @param array  $data    Extra contextual data (logged but not sent to client)
	 * @return WP_Error
	 */
	public static function error( $code, $detail = '', $data = array() ) {
		if ( ! isset( self::ERROR_CODES[ $code ] ) ) {
			$code = 'lgp_internal_error';
		}

		$error_spec = self::ERROR_CODES[ $code ];
		$message    = $error_spec['message'];

		if ( $detail ) {
			$message .= ": {$detail}";
		}

		// Log the error with context
		self::log_error( $code, $detail, $data );

		// Return as WP_Error with status
		$error = new WP_Error(
			$code,
			$message,
			array( 'status' => $error_spec['status'] )
		);

		return $error;
	}

	/**
	 * Validation error
	 *
	 * @param string $field Field name
	 * @param string $reason Validation reason
	 * @return WP_Error
	 */
	public static function validation_error( $field, $reason ) {
		return self::error(
			'lgp_validation_failed',
			"Field '{$field}': {$reason}",
			array( 'field' => $field )
		);
	}

	/**
	 * Not found error
	 *
	 * @param string $resource Resource type
	 * @param mixed  $id       Resource ID
	 * @return WP_Error
	 */
	public static function not_found( $resource, $id ) {
		return self::error(
			'lgp_not_found',
			"{$resource} #{$id} not found",
			array(
				'resource' => $resource,
				'id'       => $id,
			)
		);
	}

	/**
	 * Permission denied error
	 *
	 * @param string $capability Required capability
	 * @param string $reason     Why (optional context)
	 * @return WP_Error
	 */
	public static function forbidden( $capability = '', $reason = '' ) {
		$detail = $capability ? "Requires: {$capability}" : '';
		if ( $reason ) {
			$detail .= $detail ? " ({$reason})" : $reason;
		}
		return self::error( 'lgp_forbidden', $detail );
	}

	/**
	 * Rate limit error
	 *
	 * @param int $retry_after Seconds until retry
	 * @return WP_Error
	 */
	public static function rate_limited( $retry_after = 60 ) {
		$error = self::error( 'lgp_rate_limited' );
		$error->add_data( array( 'retry_after' => $retry_after ) );
		return $error;
	}

	/**
	 * Database error
	 *
	 * @param string $operation What operation failed
	 * @param string $detail    Error detail
	 * @return WP_Error
	 */
	public static function database_error( $operation, $detail = '' ) {
		return self::error(
			'lgp_database_error',
			"Failed to {$operation}" . ( $detail ? ": {$detail}" : '' )
		);
	}

	/**
	 * Integration error (HubSpot, Outlook, etc.)
	 *
	 * @param string $service Service name
	 * @param string $detail  Error detail
	 * @return WP_Error
	 */
	public static function integration_error( $service, $detail = '' ) {
		return self::error(
			'lgp_integration_error',
			"{$service} error" . ( $detail ? ": {$detail}" : '' )
		);
	}

	/**
	 * Log error for debugging/auditing
	 *
	 * @param string $code   Error code
	 * @param string $detail Detail message
	 * @param array  $data   Extra context
	 */
	private static function log_error( $code, $detail, $data ) {
		if ( ! function_exists( 'error_log' ) ) {
			return;
		}

		$context = array(
			'code'    => $code,
			'detail'  => $detail,
			'user_id' => get_current_user_id(),
			'url'     => $_SERVER['REQUEST_URI'] ?? '',
			'method'  => $_SERVER['REQUEST_METHOD'] ?? '',
		);

		if ( ! empty( $data ) ) {
			$context['data'] = $data;
		}

		error_log( 'LGP_REST_Error: ' . wp_json_encode( $context ) );
	}

	/**
	 * Convert error to REST response
	 * Automatically called by REST framework
	 *
	 * @param WP_Error $error
	 * @return array
	 */
	public static function to_response( $error ) {
		$code   = $error->get_error_code();
		$detail = $error->get_error_message();

		$response = array(
			'code'    => $code,
			'message' => $detail,
		);

		// Add status if available
		$error_data = $error->get_error_data();
		if ( isset( $error_data['status'] ) ) {
			$response['status'] = $error_data['status'];
		}

		return $response;
	}
}
