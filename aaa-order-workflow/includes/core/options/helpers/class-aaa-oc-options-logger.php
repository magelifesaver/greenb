<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/helpers/class-aaa-oc-options-logger.php
 * Purpose: Shared lightweight logging utility for AAA_OC_Options module.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Options_Logger {

	/**
	 * Local toggle (true = log all module actions to debug.log)
	 */
	const ENABLED = false; // set false to silence all logging

	/**
	 * Write a line to debug.log (guarded by ENABLED)
	 *
	 * @param string $message Message to write
	 * @param string $context Optional context label (e.g., INSTALL, SET, GET)
	 * @return void
	 */
	public static function log( $message, $context = 'OPTIONS' ) {
		if ( ! self::ENABLED ) return;
		if ( empty( $message ) ) return;

		$time = date( 'Y-m-d H:i:s' );
		$prefix = "[AAA_OC][$context]";
		$line = sprintf( "%s %s %s", $time, $prefix, $message );

		error_log( $line );
	}

	/**
	 * Convenience shortcut for installer logs
	 */
	public static function install( $message ) {
		self::log( $message, 'INSTALL' );
	}

	/**
	 * Convenience shortcut for option set/get events
	 */
	public static function event( $message ) {
		self::log( $message, 'EVENT' );
	}
}

if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
	define( 'DEBUG_THIS_FILE', false );
}

if ( DEBUG_THIS_FILE ) {
	AAA_OC_Options_Logger::log( 'Logger file loaded successfully.', 'BOOT' );
}
