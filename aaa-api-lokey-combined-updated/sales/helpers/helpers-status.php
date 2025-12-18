<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-status.php
 * Version: 1.2.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides a list of WooCommerce order statuses that are considered "paid".
 *   Supports filters for extension and ensures consistent format (no wc- prefix).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Get all paid order statuses (normalized, no 'wc-' prefix).
 * --------------------------------------------------------------------------
 *
 * @return array
 */
if ( ! function_exists( 'lokey_reports_get_paid_statuses' ) ) {
	function lokey_reports_get_paid_statuses() {

		// Default WooCommerce paid statuses.
		if ( function_exists( 'wc_get_is_paid_statuses' ) ) {
			$statuses = wc_get_is_paid_statuses(); // e.g. ['processing', 'completed']
		} else {
			$statuses = [ 'processing', 'completed' ];
		}

		/**
		 * Filter the paid statuses list.
		 *
		 * Example:
		 * add_filter( 'lokey_reports_paid_statuses', function( $statuses ) {
		 *     $statuses[] = 'delivered';
		 *     return $statuses;
		 * });
		 */
		$statuses = apply_filters( 'lokey_reports_paid_statuses', $statuses );

		$clean = [];
		foreach ( (array) $statuses as $status ) {
			$status = strtolower( trim( (string) $status ) );
			if ( $status === '' ) continue;

			if ( 0 === strpos( $status, 'wc-' ) ) {
				$status = substr( $status, 3 );
			}

			if ( function_exists( 'wc_is_order_status' ) && wc_is_order_status( 'wc-' . $status ) ) {
				$clean[ $status ] = $status;
			}
		}

		return array_values( $clean );
	}
}

