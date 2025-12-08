<?php
/**
 * File: includes/report-payments-v2.php
 * Purpose: Payment method totals + order-level payment breakdown (two tables).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function aaa_render_payment_summary_v2( $orders ) {
    global $wpdb;

    $index_table = $wpdb->prefix . 'aaa_oc_payment_index';

    $methods_map = [
        'aaa_oc_cash_amount'       => 'Cash',
        'aaa_oc_zelle_amount'      => 'Zelle',
        'aaa_oc_venmo_amount'      => 'Venmo',
        'aaa_oc_applepay_amount'   => 'ApplePay',
        'aaa_oc_cashapp_amount'    => 'CashApp',
        'aaa_oc_creditcard_amount' => 'Credit Card',
    ];

    $summary            = [];
    $grand_total        = 0.0;
    $store_credit_total = 0.0;
    $payment_rows       = [];

    $totals = [
        'order_total'   => 0.0,
        'epayment_tip'  => 0.0,
        'manual_tip'    => 0.0,
        'store_credit'  => 0.0,
        'balance'       => 0.0,
    ];

    foreach ( $orders as $order ) {
        $order_id       = $order->get_id();
        $customer_name  = $order->get_formatted_billing_full_name();
        $original_title = $order->get_payment_method_title();

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$index_table} WHERE order_id = %d", $order_id ), ARRAY_A );
        if ( ! $row ) {
            continue;
        }

        $total         = (float) $row['aaa_oc_order_total'];
        $paid          = (float) $row['aaa_oc_payrec_total'];
        $epayment_tip  = isset($row['epayment_tip']) ? (float) $row['epayment_tip'] : 0.0;
        $manual_tip    = isset($row['aaa_oc_tip_total']) ? (float) $row['aaa_oc_tip_total'] : 0.0;
        $credit        = (float) $order->get_meta( '_funds_used', true );

        $balance_raw = $epayment_tip > 0 ? $total - $paid + $epayment_tip : $total - $paid;
        $balance     = abs( round( $balance_raw, 2 ) ) < 0.01 ? 0.00 : round( $balance_raw, 2 );
        $payment_status = $row['aaa_oc_payment_status'] ?? 'unknown';

        foreach ( $methods_map as $col => $label ) {
            $amt = isset( $row[ $col ] ) ? (float) $row[ $col ] : 0.0;
            if ( $amt > 0 ) {
                if ( ! isset( $summary[ $col ] ) ) {
                    $summary[ $col ] = [ 'label' => $label, 'count' => 0, 'total' => 0.0 ];
                }
                $summary[ $col ]['count']++;
                $summary[ $col ]['total'] += $amt;
                $grand_total += $amt;
            }
        }

        $real_methods = [];
        foreach ( $methods_map as $col => $label ) {
            if ( isset( $row[ $col ] ) && (float) $row[ $col ] > 0 ) {
                $real_methods[] = $label . ' ($' . number_format( (float) $row[ $col ], 2 ) . ')';
            }
        }

        $payment_rows[] = [
            'order_id'     => $order_id,
            'customer'     => $customer_name,
            'original'     => $original_title,
            'real'         => implode( ', ', $real_methods ),
            'total'        => $total,
            'balance'      => $balance,
            'epay_tip'     => $epayment_tip,
            'manual_tip'   => $manual_tip,
            'store_credit' => $credit,
            'status'       => ucfirst( $payment_status ),
        ];

        $totals['order_total']   += $total;
        $totals['balance']       += $balance;
        $totals['epayment_tip']  += $epayment_tip;
        $totals['manual_tip']    += $manual_tip;
        $totals['store_credit']  += $credit;
    }

    echo '<h1>Payment Method Summary</h1>';
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Method</th><th>Order Count</th><th>Total Collected</th>'
       . '</tr></thead><tbody>';

    foreach ( $summary as $s ) {
        echo '<tr><td>' . esc_html( $s['label'] ) . '</td>'
           . '<td>' . esc_html( $s['count'] ) . '</td>'
           . '<td>' . wc_price( $s['total'] ) . '</td></tr>';
    }

    echo '</tbody><tfoot><tr>'
       . '<th style="border-top:2px solid #444;">Grand Total</th><th></th>'
       . '<th style="border-top:2px solid #444;">' . wc_price( $grand_total ) . '</th>'
       . '</tr></tfoot></table>';

    if ( $totals['store_credit'] > 0 ) {
        echo '<p><em>Store credit applied: ' . wc_price( $totals['store_credit'] ) . '</em></p>';
    }

    echo '<h2 style="margin-top:2em;">Per-Order Payment Details</h2>';
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Order ID</th><th>Customer</th><th>Original Method</th>'
       . '<th>Real Payment Method</th><th>Order Total</th><th>Balance</th>'
       . '<th>ePayment Tip</th><th>Store Tip</th><th>Store Credit</th><th>Payment Status</th>'
       . '</tr></thead><tbody>';

    foreach ( $payment_rows as $r ) {
        echo '<tr>'
           . '<td><a href="' . esc_url( admin_url( 'post.php?post=' . $r['order_id'] . '&action=edit' ) ) . '">#' . esc_html( $r['order_id'] ) . '</a></td>'
           . '<td>' . esc_html( $r['customer'] ) . '</td>'
           . '<td>' . esc_html( $r['original'] ) . '</td>'
           . '<td>' . esc_html( $r['real'] ) . '</td>'
           . '<td>' . wc_price( $r['total'] ) . '</td>'
           . '<td>' . wc_price( $r['balance'] ) . '</td>'
           . '<td>' . wc_price( $r['epay_tip'] ) . '</td>'
           . '<td>' . wc_price( $r['manual_tip'] ) . '</td>'
           . '<td>' . wc_price( $r['store_credit'] ) . '</td>'
           . '<td>' . esc_html( $r['status'] ) . '</td>'
           . '</tr>';
    }

    echo '</tbody><tfoot><tr style="border-top:2px solid #444;font-weight:bold;">'
       . '<th colspan="4">Totals</th>'
       . '<th>' . wc_price( $totals['order_total'] ) . '</th>'
       . '<th>' . wc_price( $totals['balance'] ) . '</th>'
       . '<th>' . wc_price( $totals['epayment_tip'] ) . '</th>'
       . '<th>' . wc_price( $totals['manual_tip'] ) . '</th>'
       . '<th>' . wc_price( $totals['store_credit'] ) . '</th>'
       . '<th></th>'
       . '</tr></tfoot></table>';
}
