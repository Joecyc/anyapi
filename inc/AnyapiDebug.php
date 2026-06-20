<?php
/**
 * AnyapiDebug.php (v1.0)
 *
 * Centralised debug logger for AnyAPI.
 * Writes to PHP error_log when Debug Mode is enabled in Settings.
 * Output goes to wp-content/debug.log (if WP_DEBUG_LOG is on)
 * or server PHP error log otherwise.
 *
 * @package AnyApi
 */

namespace Anyapi;

if ( ! defined( 'ABSPATH' ) ) exit;

class AnyapiDebug {

	/**
	 * Write a debug entry to error_log if Debug Mode is enabled.
	 *
	 * @param string     $context  Short label identifying the caller (e.g. 'OrderIntegrations').
	 * @param string     $message  Human-readable description.
	 * @param mixed|null $data     Optional data to dump via print_r().
	 */
	public static function log( string $context, string $message, $data = null ): void {
		if ( get_option( 'anyapi_debug_mode', '0' ) !== '1' ) {
			return;
		}

		error_log( '[AnyAPI Debug] [' . $context . '] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		if ( null !== $data ) {
			error_log( '[AnyAPI Debug] [' . $context . '] Data: ' . print_r( $data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}

}
