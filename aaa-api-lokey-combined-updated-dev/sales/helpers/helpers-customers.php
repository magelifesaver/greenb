<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-customers.php
 * Version: 1.2.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Aggregates WooCommerce orders into per-customer metrics including:
 *     - Net and gross sales
 *     - Average order value
 *     - Items per order
 *     - First/last order timestamps
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'lokey_reports_aggregate_customers' ) ) {
	/**
	 * Aggregate orders into per-customer stats for the given range.
	 *
	 * @param WC_Order[] $orders Orders in the range.
	 * @return array[] Customer rows.
	 */
	function lokey_reports_aggregate_customers( array $orders ) {
		$rows = [];
		$tz   = lokey_reports_get_store_timezone();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) continue;

			$order_id    = $order->get_id();
			$customer_id = (int) $order->get_customer_id();
			$email       = strtolower( trim( (string) $order->get_billing_email() ) );
			$first_name  = trim( (string) $order->get_billing_first_name() );
			$last_name   = trim( (string) $order->get_billing_last_name() );

			if ( $customer_id > 0 ) {
				$key = 'u_' . $customer_id;
			} elseif ( $email !== '' ) {
				$key = 'e_' . sha1( $email );
			} else {
				$key = 'g_' . $order_id;
			}

			$customer_type = ( $customer_id > 0 ) ? 'registered' : 'guest';
			$name = trim( $first_name . ' ' . $last_name );
			if ( $name === '' ) {
				$name = ( $customer_type === 'registered' ) ? 'Registered Customer' : 'Guest';
			}

			$dt = $order->get_date_paid() ?: $order->get_date_created();
			if ( ! $dt ) continue;

			$dt->setTimezone( $tz );
			$ts = $dt->getTimestamp();

			$order_total    = (float) $order->get_total();
			$discount_total = (float) $order->get_discount_total() + (float) $order->get_discount_tax();
			$refund_total   = (float) $order->get_total_refunded();
			$gross_sales    = $order_total + $discount_total;
			$net_sales      = $order_total - $refund_total;

			$items_qty = 0;
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$items_qty += (int) $item->get_quantity();
			}

			if ( ! isset( $rows[ $key ] ) ) {
				$rows[ $key ] = [
					'customer_key'        => $key,
					'customer_id'         => $customer_id,
					'customer_type'       => $customer_type,
					'customer_name'       => $name,
					'orders'              => 0,
					'items'               => 0,
					'gross_sales'         => 0.0,
					'net_sales'           => 0.0,
					'discount_total'      => 0.0,
					'first_order_ts'      => $ts,
					'last_order_ts'       => $ts,
					'avg_order_value'     => 0.0,
					'avg_items_per_order' => 0.0,
				];
			}

			$rows[ $key ]['orders']++;
			$rows[ $key ]['items']          += $items_qty;
			$rows[ $key ]['gross_sales']    += $gross_sales;
			$rows[ $key ]['net_sales']      += $net_sales;
			$rows[ $key ]['discount_total'] += $discount_total;

			if ( $ts < $rows[ $key ]['first_order_ts'] ) {
				$rows[ $key ]['first_order_ts'] = $ts;
			}
			if ( $ts > $rows[ $key ]['last_order_ts'] ) {
				$rows[ $key ]['last_order_ts'] = $ts;
			}
		}

		foreach ( $rows as $key => $row ) {
			$orders_count = max( 1, (int) $row['orders'] );
			$rows[ $key ]['avg_order_value']     = $row['net_sales'] / $orders_count;
			$rows[ $key ]['avg_items_per_order'] = $row['items'] / $orders_count;

			$first_dt = new DateTime( '@' . $row['first_order_ts'] );
			$first_dt->setTimezone( $tz );
			$last_dt = new DateTime( '@' . $row['last_order_ts'] );
			$last_dt->setTimezone( $tz );

			$rows[ $key ]['first_order_date'] = $first_dt->format( 'Y-m-d H:i:s' );
			$rows[ $key ]['last_order_date']  = $last_dt->format( 'Y-m-d H:i:s' );

			unset( $rows[ $key ]['first_order_ts'], $rows[ $key ]['last_order_ts'] );
		}

		return array_values( $rows );
	}
}

