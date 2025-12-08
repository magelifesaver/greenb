<?php
/**
 * Simple debug logger
 *
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/inc/class-aaa-pm-logger.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'aaa_pm_log' ) ) {
	function aaa_pm_log( $msg, $ctx = array() ) {
		$line = '[AAA-OGB] ' . ( is_string( $msg ) ? $msg : wp_json_encode( $msg ) );
		if ( ! empty( $ctx ) ) {
			$line .= ' ' . wp_json_encode( $ctx );
		}
		if ( false ) {
		    error_log( $line );
		}
	}
}
