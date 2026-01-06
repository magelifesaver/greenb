<?php
/**
 * File: includes/report-tip-distribution.php
 * Description: Tip Distribution (Detail + Summary by Method + Summary by Driver)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function aaa_render_tip_distribution( $orders ) {
	global $wpdb;

	if ( empty( $orders ) ) {
		echo '<h1>Tip Distribution</h1><p>No orders found.</p>';
		return;
	}

	$first_order = $orders[0] instanceof WC_Order ? $orders[0] : wc_get_order( $orders[0] );
	$report_date = $first_order?->get_date_created()?->setTimezone( wp_timezone() )->format( 'Y-m-d' ) ?? wp_date( 'Y-m-d' );

	$pi = $wpdb->prefix . 'aaa_oc_payment_index';
	$p  = $wpdb->posts;
	$pm = $wpdb->postmeta;

	$sql = $wpdb->prepare("
		SELECT pi.*, p.post_date,
		       fname.meta_value AS billing_first_name,
		       lname.meta_value AS billing_last_name,
		       cust.meta_value  AS customer_id
		  FROM {$pi} pi
		INNER JOIN {$p} p         ON p.ID = pi.order_id
		LEFT JOIN {$pm} fname     ON fname.post_id = pi.order_id AND fname.meta_key = '_billing_first_name'
		LEFT JOIN {$pm} lname     ON lname.post_id = pi.order_id AND lname.meta_key = '_billing_last_name'
		LEFT JOIN {$pm} cust      ON cust.post_id  = pi.order_id AND cust.meta_key  = '_customer_user'
		 WHERE p.post_type = 'shop_order'
		   AND p.post_status IN ('wc-completed','wc-lkd-delivered','wc-out-for-delivery','wc-processing','wc-lkd-packed-ready')
		   AND DATE(p.post_date) = %s
		   AND (pi.epayment_tip > 0 OR pi.aaa_oc_tip_total > 0 OR pi.total_order_tip > 0)
	", $report_date );

	$raw = $wpdb->get_results( $sql, ARRAY_A );
	if ( ! $raw ) {
		echo '<h1>Tip Distribution</h1><p>No tips recorded for this date.</p>';
		return;
	}

	$payment_map = [
		'aaa_oc_venmo_amount'      => 'Venmo',
		'aaa_oc_zelle_amount'      => 'Zelle',
		'aaa_oc_applepay_amount'   => 'ApplePay',
		'aaa_oc_cashapp_amount'    => 'CashApp',
		'aaa_oc_creditcard_amount' => 'Credit Card',
		'aaa_oc_cash_amount'       => 'COD',
	];

	$rows           = [];
	$tip_by_method  = [];
	$tip_by_driver  = [];

	foreach ( $raw as $r ) {
		$oid      = (int) $r['order_id'];
		$datetime = wp_date( 'n/j g:i a', strtotime( $r['post_date'] ) );
		$customer = trim( $r['billing_first_name'] . ' ' . $r['billing_last_name'] ) ?: '—';
		$driver_id = (int) $r['driver_id'] ?: (int) get_post_meta( $oid, 'lddfw_driverid', true );
		$driver    = $driver_id ? get_the_author_meta( 'display_name', $driver_id ) : '—';

		$methods = [];
		foreach ( $payment_map as $col => $label ) {
			if ( isset( $r[ $col ] ) && (float) $r[ $col ] > 0 ) {
				$methods[] = $label;
				$tip_by_method[ $label ] = ( $tip_by_method[ $label ] ?? 0 ) + (float) $r['total_order_tip'];
			}
		}
		$payment_label = implode( ' + ', $methods ) ?: 'Unknown';

		$epay  = (float) $r['epayment_tip'];
		$web   = (float) $r['aaa_oc_tip_total'];
		$total = (float) $r['total_order_tip'];

		$rows[] = [
			'order'    => $oid,
			'date'     => $datetime,
			'payment'  => $payment_label,
			'epay'     => $epay,
			'web'      => $web,
			'total'    => $total,
			'driver'   => $driver,
			'customer' => $customer,
		];

		$tip_by_driver[ $driver ] = ( $tip_by_driver[ $driver ] ?? 0 ) + $total;
	}

	// Render table (same as before)
	echo '<h1>Tip Distribution</h1>';
	echo '<table class="widefat sortable"><thead><tr>'
	   . '<th>Order ID</th><th>Date/Time</th><th>Real Payment Method</th>'
	   . '<th>ePayment Tip</th><th>Web Tip</th><th>Total Tip</th>'
	   . '<th>Driver</th><th>Customer</th>'
	   . '</tr></thead><tbody>';

	$sum_epay  = 0;
	$sum_web   = 0;
	$sum_total = 0;

	foreach ( $rows as $r ) {
		$sum_epay  += $r['epay'];
		$sum_web   += $r['web'];
		$sum_total += $r['total'];

		printf(
			'<tr><td><a href="%s">#%d</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
			esc_url( admin_url( 'post.php?post=' . $r['order'] . '&action=edit' ) ),
			$r['order'],
			esc_html( $r['date'] ),
			esc_html( $r['payment'] ),
			wc_price( $r['epay'] ),
			wc_price( $r['web'] ),
			wc_price( $r['total'] ),
			esc_html( $r['driver'] ),
			esc_html( $r['customer'] )
		);
	}

	echo '</tbody><tfoot><tr>'
	   . '<th colspan="3" style="text-align:right;border-top:2px solid #444;">Totals:</th>'
	   . '<th style="border-top:2px solid #444;">' . wc_price( $sum_epay )  . '</th>'
	   . '<th style="border-top:2px solid #444;">' . wc_price( $sum_web )   . '</th>'
	   . '<th style="border-top:2px solid #444;">' . wc_price( $sum_total ) . '</th>'
	   . '<th colspan="2" style="border-top:2px solid #444;"></th>'
	   . '</tr></tfoot></table>';

	// Summary by payment method
	echo '<h2 style="margin-top:2em;">Summary – Tips by Payment Method</h2>';
	echo '<table class="widefat sortable"><thead><tr><th>Method</th><th>Total Tips</th></tr></thead><tbody>';
	$sum_method = 0;
	foreach ( $payment_map as $key => $label ) {
		$tip = $tip_by_method[ $label ] ?? 0;
		echo '<tr><td>' . esc_html( $label ) . '</td><td>' . wc_price( $tip ) . '</td></tr>';
		$sum_method += $tip;
	}
	echo '</tbody><tfoot><tr><th>Total</th><th>' . wc_price( $sum_method ) . '</th></tr></tfoot></table>';

	// Summary by driver
	echo '<h2 style="margin-top:2em;">Summary – Tips by Driver</h2>';
	echo '<table class="widefat sortable"><thead><tr><th>Driver</th><th>Total Tip</th></tr></thead><tbody>';
	foreach ( $tip_by_driver as $driver => $tot ) {
		echo '<tr><td>' . esc_html( $driver ) . '</td><td>' . wc_price( $tot ) . '</td></tr>';
	}
	$driver_total = array_sum( $tip_by_driver );
	echo '</tbody><tfoot><tr><th>Total</th><th>' . wc_price( $driver_total ) . '</th></tr></tfoot></table>';
}
