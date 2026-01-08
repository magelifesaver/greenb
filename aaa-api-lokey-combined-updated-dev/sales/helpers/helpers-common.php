<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-common.php
 * Version: 1.2.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Shared helper functions for core LokeyReports operations:
 *     - Store timezone detection
 *     - Report permission check
 *     - Grouping/preset sanitization
 *     - Order status utilities
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Get store timezone as DateTimeZone.
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_get_store_timezone' ) ) {
	function lokey_reports_get_store_timezone() {
		$tz_string = get_option( 'timezone_string' );
		if ( $tz_string ) {
			try {
				return new DateTimeZone( $tz_string );
			} catch ( Exception $e ) {}
		}

		$offset  = (float) get_option( 'gmt_offset', 0 );
		$hours   = (int) $offset;
		$minutes = (int) abs( ( $offset - $hours ) * 60 );
		$sign    = $offset >= 0 ? '+' : '-';
		$tz_name = sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );

		try {
			return new DateTimeZone( $tz_name );
		} catch ( Exception $e ) {
			return new DateTimeZone( 'UTC' );
		}
	}
}

/**
 * --------------------------------------------------------------------------
 * Check if current user can view WooCommerce reports.
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_can_view_reports' ) ) {
	function lokey_reports_can_view_reports() {
		return current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' );
	}
}

/**
 * --------------------------------------------------------------------------
 * Sanitize group_by parameter (none|day|week|month).
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_sanitize_group_by' ) ) {
	function lokey_reports_sanitize_group_by( $value ) {
		$value   = is_string( $value ) ? strtolower( $value ) : '';
		$allowed = [ 'none', 'day', 'week', 'month' ];
		return in_array( $value, $allowed, true ) ? $value : 'none';
	}
}

/**
 * --------------------------------------------------------------------------
 * Sanitize preset keyword (e.g. last_7_days, this_month).
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_sanitize_preset' ) ) {
	function lokey_reports_sanitize_preset( $value ) {
		$value   = is_string( $value ) ? strtolower( trim( $value ) ) : '';
		$allowed = [
			'today',
			'yesterday',
			'last_7_days',
			'last_30_days',
			'this_month',
			'last_month',
		];
		return in_array( $value, $allowed, true ) ? $value : '';
	}
}

/**
 * --------------------------------------------------------------------------
 * Return all Woo order statuses without the "wc-" prefix.
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_get_all_statuses' ) ) {
	function lokey_reports_get_all_statuses() {
		$clean = [];

		if ( function_exists( 'wc_get_order_statuses' ) ) {
			foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
				$status = strtolower( (string) $status );
				if ( 0 === strpos( $status, 'wc-' ) ) $status = substr( $status, 3 );
				if ( $status ) $clean[ $status ] = $status;
			}
		}

		if ( empty( $clean ) ) {
			$clean = [ 'pending', 'processing', 'completed' ];
		}

		return array_values( $clean );
	}
}

/**
 * --------------------------------------------------------------------------
 * Sanitize order status list input into canonical status slugs (no wc- prefix).
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_sanitize_status_list' ) ) {
	function lokey_reports_sanitize_status_list( $statuses ) {

		if ( empty( $statuses ) ) {
			$statuses = lokey_reports_get_all_statuses();
		}

		if ( ! is_array( $statuses ) ) {
			$statuses = explode( ',', (string) $statuses );
		}

		$clean = [];

		foreach ( $statuses as $status ) {
			$status = strtolower( trim( (string) $status ) );
			if ( '' === $status ) continue;
			if ( 0 === strpos( $status, 'wc-' ) ) $status = substr( $status, 3 );
			if ( function_exists( 'wc_is_order_status' ) ) {
				if ( wc_is_order_status( 'wc-' . $status ) ) {
					$clean[ $status ] = $status;
				}
			} else {
				$clean[ $status ] = $status;
			}
		}

		if ( empty( $clean ) ) {
			return lokey_reports_get_all_statuses();
		}

		return array_values( $clean );
	}
}

