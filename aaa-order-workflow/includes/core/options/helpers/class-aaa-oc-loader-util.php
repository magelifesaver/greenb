<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/helpers/class-aaa-oc-loader-util.php
 * Purpose: Shared utilities for all module loaders (require helper + debug logger).
 * Depends: aaa_oc_log() (optional), ABSPATH
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Loader_Util {
	public static function require_or_log( string $file, bool $must = false, string $ctx = 'loader' ) : bool {
		if ( is_file( $file ) ) { require_once $file; return true; }
		if ( function_exists( 'aaa_oc_log' ) ) {
			aaa_oc_log( sprintf( '[%s] missing: %s', strtoupper($ctx), $file ) );
		} else {
			error_log( sprintf( '[%s] missing: %s', strtoupper($ctx), $file ) );
		}
		if ( $must ) { /* reserved for hard fail */ }
		return false;
	}
	public static function dlog( string $msg, bool $enabled ) : void {
		if ( ! $enabled ) return;
		if ( function_exists( 'aaa_oc_log' ) ) aaa_oc_log( $msg ); else error_log( $msg );
	}
}
