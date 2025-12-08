<?php
/**
 * File: includes/report-summary.php
 * Description: Expanded Daily Summary with complete payment/tip/store credit logic.
 */

function aaa_render_report_summary( $orders ) {
    global $wpdb;

    $total_orders         = count( $orders );
    $gross                = 0;
    $discounts            = 0;
    $cogs                 = 0;
    $total_coupons        = 0;
    $tip_web              = 0;
    $tip_epayment         = 0;
    $store_credit         = 0;

    $customer_flags  = [ 'new' => 0, 'returning' => 0 ];
    $seen_customers  = [];

    $index_table = $wpdb->prefix . 'aaa_oc_payment_index';
    $order_ids   = array_map( fn($o) => $o->get_id(), $orders );
    if ( $order_ids ) {
        $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
        $sql = $wpdb->prepare(
            "SELECT SUM(epayment_tip) FROM {$index_table} WHERE order_id IN ($placeholders)",
            ...$order_ids
        );
        $tip_epayment = (float) $wpdb->get_var( $sql );
    }

    foreach ( $orders as $order ) {
        $gross     += (float) $order->get_total();
        $discounts += (float) $order->get_discount_total();

        $total_coupons += count( $order->get_items( 'coupon' ) );

        $tip_web      += (float) $order->get_meta( '_wpslash_tip', true );
        $store_credit += (float) $order->get_meta( '_funds_used', true );

        foreach ( $order->get_items() as $item ) {
            if ( $item->get_type() === 'coupon' ) continue;
            $pid = $item->get_product_id();
            $qty = $item->get_quantity();

            $atum_table = $wpdb->prefix . 'atum_product_data';
            $atum_cost  = $wpdb->get_var( $wpdb->prepare("SELECT purchase_price FROM {$atum_table} WHERE product_id = %d", $pid) );
            if ( $atum_cost !== null && $atum_cost !== '' ) {
                $unit_cost = (float) $atum_cost;
            } else {
                $meta_cost = get_post_meta( $pid, '_cogs_total_value', true );
                $unit_cost = $meta_cost !== '' ? (float) $meta_cost : (float) wc_get_product( $pid )->get_price() * 0.5;
            }
            $cogs += $unit_cost * $qty;
        }

        $user_id = $order->get_user_id();
        $email   = $order->get_billing_email();
        $key     = $user_id ? "user_{$user_id}" : "guest_{$email}";
        if ( ! isset( $seen_customers[ $key ] ) ) {
            $order_count = $user_id ? wc_get_customer_order_count( $user_id ) : 0;
            $is_new      = ! $user_id || $order_count <= 1;
            $customer_flags[ $is_new ? 'new' : 'returning' ]++;
            $seen_customers[ $key ] = true;
        }
    }

    $net        = $gross;
    $profit     = $net - $cogs;
    $margin     = $net > 0 ? round( ( $profit / $net ) * 100, 1 ) : 0;
    $avg_order  = $total_orders ? $net / $total_orders : 0;

    $total_customers = $customer_flags['new'] + $customer_flags['returning'];
    $new_pct         = $total_customers ? round( $customer_flags['new'] / $total_customers * 100, 1 ) : 0;
    $ret_pct         = $total_customers ? round( $customer_flags['returning'] / $total_customers * 100, 1 ) : 0;

    echo '<h1>Daily Summary</h1>';
    echo '<table class="widefat"><tbody>';
    echo '<tr><th>Orders</th><td>' . esc_html( $total_orders ) . '</td></tr>';
    echo '<tr><th>Gross Revenue</th><td>' . wc_price( $gross ) . '</td></tr>';
    echo '<tr><th>Discounts</th><td>' . wc_price( $discounts ) . '</td></tr>';
    echo '<tr><th>Net Revenue</th><td>' . wc_price( $net ) . '</td></tr>';
    echo '<tr><th>COGS</th><td>' . wc_price( $cogs ) . '</td></tr>';
    echo '<tr><th>Gross Profit</th><td>' . wc_price( $profit ) . '</td></tr>';
    echo '<tr><th>Gross Margin</th><td>' . esc_html( $margin ) . '%</td></tr>';
    echo '<tr><th>Avg Order Value</th><td>' . wc_price( $avg_order ) . '</td></tr>';
    echo '<tr><th>Total Coupons</th><td>' . esc_html( $total_coupons ) . '</td></tr>';
    echo '<tr><th>ePayment Tips</th><td>' . wc_price( $tip_epayment ) . '</td></tr>';
    echo '<tr><th>Web Tip</th><td>' . wc_price( $tip_web ) . '</td></tr>';
    echo '<tr><th>Store Credit</th><td>' . wc_price( $store_credit ) . '</td></tr>';
    echo '<tr><th>New Customers</th><td>' . esc_html( $customer_flags['new'] ) . ' (' . esc_html( $new_pct ) . '%)</td></tr>';
    echo '<tr><th>Returning Customers</th><td>' . esc_html( $customer_flags['returning'] ) . ' (' . esc_html( $ret_pct ) . '%)</td></tr>';
    echo '</tbody></table>';
}
