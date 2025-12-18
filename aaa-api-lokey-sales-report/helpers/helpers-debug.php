<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-debug.php
 * Version: 1.0.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Centralized debug and logging helper for LokeyReports MU plugin.
 *   Provides a unified way to log messages to /wp-content/debug.log
 *   when WP_DEBUG is enabled.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * lokey_reports_debug()
 * --------------------------------------------------------------------------
 * Writes formatted messages to WordPress debug.log.
 *
 * @param string $message Log message.
 * @param string $context Optional filename or context tag.
 * @return void
 */
if ( ! function_exists( 'lokey_reports_debug' ) ) {
	function lokey_reports_debug( $message, $context = '' ) {

		// Only log if WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;

		// Normalize message.
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message, JSON_PRETTY_PRINT );
		}

		$prefix = '[LokeyReports]';
		$tag    = $context ? " [{$context}]" : '';

		error_log( "{$prefix}{$tag} {$message}" );
	}
}

/**
 * --------------------------------------------------------------------------
 * lokey_reports_debug_enabled()
 * --------------------------------------------------------------------------
 * Returns true if debug logging is active (WP_DEBUG constant).
 *
 * @return bool
 */
if ( ! function_exists( 'lokey_reports_debug_enabled' ) ) {
	function lokey_reports_debug_enabled() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}

/**
 * --------------------------------------------------------------------------
 * lokey_reports_debug_table()
 * --------------------------------------------------------------------------
 * Optional helper to log summarized array data as a readable table.
 *
 * @param array  $data   The associative array to log.
 * @param string $title  Optional title.
 * @return void
 */
if ( ! function_exists( 'lokey_reports_debug_table' ) ) {
	function lokey_reports_debug_table( array $data, $title = '' ) {
		if ( ! lokey_reports_debug_enabled() ) return;

		$output = '';
		if ( $title ) {
			$output .= "\n[{$title}]\n";
		}
		foreach ( $data as $key => $val ) {
			if ( is_array( $val ) || is_object( $val ) ) {
				$val = wp_json_encode( $val, JSON_UNESCAPED_SLASHES );
			}
			$output .= sprintf( "%-30s : %s\n", $key, $val );
		}

		lokey_reports_debug( trim( $output ), 'table' );
	}
}
