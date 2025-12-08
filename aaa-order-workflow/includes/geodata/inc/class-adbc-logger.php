<?php
/**
 * File: wp-content/plugins/aaa-delivery-blocks-coords/includes/class-adbc-logger.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ADBC_Logger {
	const DEBUG_THIS_FILE = false;

	public static function log( $message, $context = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		if ( ! defined( 'ADBC_DEBUG' ) || ! ADBC_DEBUG ) return;
		if ( ! self::DEBUG_THIS_FILE ) return;

		if ( ! empty( $context ) ) {
			$message .= ' | ' . wp_json_encode( $context );
		}
		error_log( '[AAA-DBlocks-Coords] ' . $message ); // phpcs:ignore
	}
}
