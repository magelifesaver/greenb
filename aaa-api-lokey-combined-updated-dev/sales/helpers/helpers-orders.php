<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-orders.php
 * Version: 1.4.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Unified order helper utilities for LokeyReports:
 *     - Date-based order retrieval (created, paid, completed)
 *     - Payment status and type filtering
 *     - Normalized payment breakdown extraction
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Fetch orders for a date range and given order statuses.
 * --------------------------------------------------------------------------
 *
 * @param DateTimeInterface $from
 * @param DateTimeInterface $to
 * @param array             $statuses
 * @param string            $date_type created|paid|completed
 * @return WC_Order[]
 */
if ( ! function_exists( 'lokey_reports_get_orders_for_range' ) ) {
	function lokey_reports_get_orders_for_range( DateTimeInterface $from, DateTimeInterface $to, array $statuses, $date_type = 'created' ) {

		$args = [
			'limit'   => -1,
			'type'    => 'shop_order',
			'orderby' => 'date',
			'order'   => 'ASC',
			'return'  => 'objects',
		];

		// Normalize statuses
		$wc_statuses = [];
		foreach ( $statuses as $status ) {
			$status = strtolower( trim( (string) $status ) );
			if ( $status !== '' ) $wc_statuses[] = $status;
		}
		if ( $wc_statuses ) $args['status'] = $wc_statuses;

		// Date range span
		$span = $from->format( 'Y-m-d H:i:s' ) . '...' . $to->format( 'Y-m-d H:i:s' );

		switch ( strtolower( $date_type ) ) {
			case 'paid':
				$args['date_paid'] = $span;
				break;
			case 'completed':
				$args['date_completed'] = $span;
				break;
			default:
				$args['date_created'] = $span;
				break;
		}

		$orders = function_exists( 'wc_get_orders' ) ? wc_get_orders( $args ) : [];

		// Fallback: created date if nothing found
		if ( empty( $orders ) && $date_type !== 'created' ) {
			$orders = wc_get_orders( array_merge( $args, [ 'date_created' => $span ] ) );
		}

		return is_array( $orders ) ? $orders : [];
	}
}

/**
 * --------------------------------------------------------------------------
 * Filter orders by custom payment status meta (aaa_oc_payment_status).
 * --------------------------------------------------------------------------
 *
 * @param WC_Order[] $orders
 * @param string|array $status
 * @return WC_Order[]
 */
if ( ! function_exists( 'lokey_reports_filter_orders_by_payment_status' ) ) {
	function lokey_reports_filter_orders_by_payment_status( array $orders, $status = 'paid' ) {
		if ( empty( $orders ) ) return [];

		$statuses = (array) array_map( 'strtolower', (array) $status );

		return array_filter( $orders, function ( $order ) use ( $statuses ) {
			if ( ! $order instanceof WC_Order ) return false;
			$current = strtolower( (string) $order->get_meta( 'aaa_oc_payment_status', true ) );
			return in_array( $current, $statuses, true );
		});
	}
}

/**
 * --------------------------------------------------------------------------
 * Filter orders by WooCommerce payment method key or title.
 * --------------------------------------------------------------------------
 *
 * @param WC_Order[] $orders
 * @param string|array $types
 * @return WC_Order[]
 */
if ( ! function_exists( 'lokey_reports_filter_orders_by_payment_type' ) ) {
	function lokey_reports_filter_orders_by_payment_type( array $orders, $types = 'pay_with_cod' ) {
		if ( empty( $orders ) ) return [];

		$types = (array) array_map( 'strtolower', (array) $types );

		return array_filter( $orders, function ( $order ) use ( $types ) {
			if ( ! $order instanceof WC_Order ) return false;

			$key   = strtolower( (string) $order->get_payment_method() );
			$title = strtolower( (string) $order->get_payment_method_title() );

			return in_array( $key, $types, true ) || in_array( $title, $types, true );
		});
	}
}

/**
 * --------------------------------------------------------------------------
 * Extract normalized payment breakdown meta fields.
 * --------------------------------------------------------------------------
 *
 * @param WC_Order $order
 * @return array
 */
if ( ! function_exists( 'lokey_reports_extract_payment_breakdown' ) ) {
	function lokey_reports_extract_payment_breakdown( WC_Order $order ) {
		if ( ! $order instanceof WC_Order ) return [];

		$map = [
			'aaa_oc_cash_amount'       => 'cash',
			'aaa_oc_zelle_amount'      => 'zelle',
			'aaa_oc_venmo_amount'      => 'venmo',
			'aaa_oc_cashapp_amount'    => 'cashapp',
			'aaa_oc_applepay_amount'   => 'applepay',
			'aaa_oc_creditcard_amount' => 'creditcard',
			'aaa_oc_epayment_total'    => 'epayment_total',
			'aaa_oc_payrec_total'      => 'payrec_total',
			'aaa_oc_order_balance'     => 'order_balance',
			'total_order_tip'          => 'total_tip',
			'aaa_oc_payment_status'    => 'payment_status',
		];

		$data = [];
		foreach ( $map as $meta_key => $label ) {
			$val = $order->get_meta( $meta_key, true );
			$data[ $label ] = is_numeric( $val ) ? (float) $val : (string) $val;
		}

		return $data;
	}
}

