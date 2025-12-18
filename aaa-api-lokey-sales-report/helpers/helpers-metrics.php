<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-metrics.php
 * Version: 1.2.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Builds sales summary metrics from WooCommerce orders, including:
 *     - Totals (orders, items, gross/net sales, etc.)
 *     - Grouped series by day, week, or month
 *     - Average order values
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Build totals + series metrics from a list of WooCommerce orders.
 * --------------------------------------------------------------------------
 *
 * @param WC_Order[]    $orders   Array of orders.
 * @param string        $group_by none|day|week|month.
 * @param DateTimeZone  $tz       Store timezone.
 * @return array {
 *   @type array $totals Overall totals.
 *   @type array $series Period breakdowns.
 * }
 */
if ( ! function_exists( 'lokey_reports_build_sales_summary' ) ) {
	function lokey_reports_build_sales_summary( array $orders, $group_by, DateTimeZone $tz ) {

		$totals = [
			'orders'          => 0,
			'items_sold'      => 0,
			'gross_sales'     => 0.0,
			'discounts'       => 0.0,
			'refunds'         => 0.0,
			'net_sales'       => 0.0,
			'shipping'        => 0.0,
			'tax'             => 0.0,
			'avg_order_value' => 0.0,
		];

		$buckets = [];

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) continue;

			// Determine appropriate order date.
			$dt = $order->get_date_paid() ?: $order->get_date_created();
			if ( ! $dt ) continue;

			// Normalize to store timezone.
			$dt->setTimezone( $tz );

			$key = ( 'none' !== $group_by ) ? lokey_reports_build_period_key( $dt, $group_by ) : 'all';

			if ( ! isset( $buckets[ $key ] ) ) {
				$buckets[ $key ] = [
					'period'          => $key,
					'orders'          => 0,
					'items_sold'      => 0,
					'gross_sales'     => 0.0,
					'discounts'       => 0.0,
					'refunds'         => 0.0,
					'net_sales'       => 0.0,
					'shipping'        => 0.0,
					'tax'             => 0.0,
					'avg_order_value' => 0.0,
				];
			}

			// Extract order financial data.
			$order_total    = (float) $order->get_total();
			$discount_total = (float) $order->get_discount_total() + (float) $order->get_discount_tax();
			$refund_total   = (float) $order->get_total_refunded();
			$shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
			$tax_total      = (float) $order->get_total_tax();

			$gross_sales = $order_total + $discount_total;
			$net_sales   = $order_total - $refund_total;

			// Count line items.
			$items_qty = 0;
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$items_qty += (int) $item->get_quantity();
			}

			// Totals.
			$totals['orders']++;
			$totals['items_sold']   += $items_qty;
			$totals['gross_sales']  += $gross_sales;
			$totals['discounts']    += $discount_total;
			$totals['refunds']      += $refund_total;
			$totals['net_sales']    += $net_sales;
			$totals['shipping']     += $shipping_total;
			$totals['tax']          += $tax_total;

			// Bucket aggregation.
			$buckets[ $key ]['orders']++;
			$buckets[ $key ]['items_sold']   += $items_qty;
			$buckets[ $key ]['gross_sales']  += $gross_sales;
			$buckets[ $key ]['discounts']    += $discount_total;
			$buckets[ $key ]['refunds']      += $refund_total;
			$buckets[ $key ]['net_sales']    += $net_sales;
			$buckets[ $key ]['shipping']     += $shipping_total;
			$buckets[ $key ]['tax']          += $tax_total;
		}

		// Compute averages.
		if ( $totals['orders'] > 0 ) {
			$totals['avg_order_value'] = round( $totals['net_sales'] / $totals['orders'], 2 );
		}

		foreach ( $buckets as $bucket_key => $bucket ) {
			if ( $bucket['orders'] > 0 ) {
				$bucket['avg_order_value'] = round( $bucket['net_sales'] / $bucket['orders'], 2 );
			}
			$buckets[ $bucket_key ] = $bucket;
		}

		if ( 'none' !== $group_by ) {
			ksort( $buckets );
		}

		return [
			'totals' => $totals,
			'series' => array_values( $buckets ),
		];
	}
}
