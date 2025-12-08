<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/matcher/helpers/class-aaa-oc-pc-status.php
 * Purpose: Canonical order-status list + SQL IN() builder.
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PC_Status {
	public static function canonical_statuses() {
		$raw = [
			'pending', 'processing', 'order-processing', 'scheduled',
			'picking', 'lkd-packed-ready', 'driver-assigned', 'out-for-delivery', 'lkd-delivered',
		];
		$prefixed = array_map( fn($s) => 'wc-' . $s, $raw );
		return array_values( array_unique( array_merge( $raw, $prefixed ) ) );
	}

	public static function sql_status_in() {
		$st = array_map( 'esc_sql', self::canonical_statuses() );
		return "'" . implode( "','", $st ) . "'";
	}
}
