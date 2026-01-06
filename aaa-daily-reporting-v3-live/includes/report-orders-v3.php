<?php
/**
 * File: includes/report-orders-v3.php
 * Description: Orders table with Net Sales, separate tip columns, shipping, and A4-friendly headers.
 */

function aaa_get_orders_for_date( $selected_date ) {
    global $wpdb;
    $start      = $selected_date . ' 00:00:00';
    $end        = $selected_date . ' 23:59:59';
    $statuses   = [ 'wc-completed','wc-lkd-delivered','wc-out-for-delivery','wc-processing','wc-lkd-packed-ready' ];
    $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
    $sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_date BETWEEN %s AND %s AND post_status IN ($placeholders)";
    $order_ids = $wpdb->get_col( $wpdb->prepare( $sql, $start, $end, ...$statuses ) );
    return array_map( 'wc_get_order', $order_ids );
}

function aaa_render_orders_section_v3( $orders ) {
    global $wpdb;

    // Initialize totals
    $order_count        = 0;
    $total_subtotal     = 0;
    $total_total        = 0;
    $total_discount     = 0;
    $total_website_tip  = 0;
    $total_epayment_tip = 0;
    $total_shipping     = 0;
    $total_net_sales    = 0;
    $total_cogs         = 0;
    $total_profit       = 0;
    $total_store_credit = 0;
    $real_payment_totals = [];

    echo '<h1>Orders</h1><table class="widefat sortable"><thead><tr>'
       . '<th>Date</th><th>Status</th><th>Order ID</th><th>External ID</th><th>Customer</th><th>Source</th>'
       . '<th>Subtotal</th><th>Total</th><th>Discount</th><th>% Off</th>'
       . '<th>Website Tip</th><th>ePayment Tip</th><th>Shipping</th><th>Net Sales</th>'
       . '<th>COGS</th><th>Profit</th>'
       . '<th># Items</th><th># U Items</th><th>SC</th><th>OG Payment</th><th>Real Payment</th>'
       . '<th>City</th><th>Time</th>'
       . '</tr></thead><tbody>';

    $row_classes = ['morning' => '#e3f7d3','afternoon' => '#fffcc9','evening' => '#ffe0b3','night' => '#ffd1d1'];

    foreach ( $orders as $order ) {
        $order_count++;
        $time_obj = $order->get_date_created();
        $hour     = (int) $time_obj->format( 'H' );
        $row_color = $hour < 12
            ? $row_classes['morning']
            : ( $hour < 16
                ? $row_classes['afternoon']
                : ( $hour < 19
                    ? $row_classes['evening']
                    : $row_classes['night']
                  )
              );

        // Core amounts
        $subtotal_amt   = (float) $order->get_subtotal();
        $total_amt      = (float) $order->get_total();
        $discount       = (float) $order->get_discount_total();
        $pct_off        = $subtotal_amt > 0 ? round( ( $discount / $subtotal_amt ) * 100, 1 ) : 0;
        $store_credit   = (float) $order->get_meta( '_funds_used', true );

// Fees
$fees_total     = array_sum( array_map(
    fn( $fee ) => (float) $fee->get_total(),
    $order->get_fees()
) );

	// Fetch full payment record (contains both tip columns now)
	$payment_row    = (array) $wpdb->get_row(
	    $wpdb->prepare(
	        "SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id = %d",
	        $order->get_id()
	    ),
	    ARRAY_A
	);

	// Web tip comes from the same index table
	$website_tip    = isset( $payment_row['website_tip'] )
	    ? (float) $payment_row['website_tip']
	    : (float) $order->get_meta( '_wpslash_tip', true );

	// ePayment tip as before
	$epayment_tip   = isset( $payment_row['epayment_tip'] )
	    ? (float) $payment_row['epayment_tip']
	    : 0;

	$shipping_total = (float) $order->get_shipping_total();

        // Cost & profit
        $cogs           = 0;

        // Initialize item counters
        $qty    = 0;
        $unique = 0;

        foreach ( $order->get_items() as $item ) {
            $unique++;
            $qty += $item->get_quantity();

            $product = $item->get_product();
            if ( $product ) {
                $atum_cost = $wpdb->get_var( $wpdb->prepare(
                    "SELECT purchase_price FROM {$wpdb->prefix}atum_product_data WHERE product_id = %d",
                    $product->get_id()
                ) );
                $meta_cost = get_post_meta( $product->get_id(), '_cogs_total_value', true );
                if ( $atum_cost !== null && $atum_cost !== '' ) {
                    $unit_cost = (float) $atum_cost;
                } elseif ( $meta_cost !== '' ) {
                    $unit_cost = (float) $meta_cost;
                } else {
                    $unit_cost = (float) $product->get_price() * 0.5;
                }
                $cogs += $unit_cost * $item->get_quantity();
            }
        }

        $profit     = $total_amt - $cogs;
        $net_sales  = $subtotal_amt - $discount + $fees_total;

        // Real payment breakdown
        $real_methods    = [];
        $payment_map     = [
            'aaa_oc_cash_amount'       => 'COD',
            'aaa_oc_zelle_amount'      => 'Zelle',
            'aaa_oc_venmo_amount'      => 'Venmo',
            'aaa_oc_applepay_amount'   => 'ApplePay',
            'aaa_oc_cashapp_amount'    => 'CashApp',
            'aaa_oc_creditcard_amount' => 'Credit Card',
        ];
        foreach ( $payment_map as $key => $label ) {
            $val = isset( $payment_row[ $key ] ) ? (float) $payment_row[ $key ] : 0;
            if ( $val > 0 ) {
                $real_methods[] = "$label (" . wc_price( $val ) . ')';
                $real_payment_totals[ $label ] = ( $real_payment_totals[ $label ] ?? 0 ) + $val;
            }
        }
        $real_payment_display = $real_methods ? implode( ', ', $real_methods ) : '—';

        // ** SOURCE now uses WooCommerce’s created_via **
        $via = $order->get_created_via();
        $source_label = $via
            ? esc_html( ucfirst( $via ) )
            : 'unknown';

        // Links & customer
        $order_link    = get_edit_post_link( $order->get_id() );
        $customer_link = $order->get_user_id() ? get_edit_user_link( $order->get_user_id() ) : '';

        // Tally totals
        $total_subtotal     += $subtotal_amt;
        $total_total        += $total_amt;
        $total_discount     += $discount;
        $total_website_tip  += $website_tip;
        $total_epayment_tip += $epayment_tip;
        $total_shipping     += $shipping_total;
        $total_net_sales    += $net_sales;
        $total_cogs         += $cogs;
        $total_profit       += $profit;
        $total_store_credit += $store_credit;

        // Prepare output cells
        $profit_display  = $profit < 0
            ? '<span style="color:red;">' . wc_price( $profit ) . '</span>'
            : wc_price( $profit );

        $cells = [
            esc_html( $time_obj->format( 'l n/j' ) ),
            esc_html( wc_get_order_status_name( $order->get_status() ) ),
            '<a href="' . esc_url( $order_link ) . '">#' . $order->get_id() . '</a>',
            $order->get_meta( '_external_order_number' ) ?: '—',
            $customer_link
                ? '<a href="' . esc_url( $customer_link ) . '">' . esc_html( $order->get_formatted_billing_full_name() ) . '</a>'
                : esc_html( $order->get_formatted_billing_full_name() ),
            esc_html( $source_label ),
            wc_price( $subtotal_amt ),
            wc_price( $total_amt ),
            wc_price( $discount ),
            esc_html( $pct_off ) . '%',
            wc_price( $website_tip ),
            wc_price( $epayment_tip ),
            wc_price( $shipping_total ),
            wc_price( $net_sales ),
            wc_price( $cogs ),
            $profit_display,
            esc_html( $qty ),
            esc_html( $unique ),
            wc_price( $store_credit ),
            esc_html( $order->get_payment_method_title() ),
            // raw HTML allowed
            $real_payment_display,
            esc_html( $order->get_shipping_city() ),
            esc_html( $time_obj->format( 'H:i' ) ),
        ];

        printf(
            '<tr style="background:%s;"><td>%s</td>%s</tr>',
            esc_attr( $row_color ),
            array_shift( $cells ),
            implode( '', array_map( fn( $cell ) => '<td>' . $cell . '</td>', $cells ) )
        );
    }

    // Footer totals
    echo '</tbody><tfoot><tr>'
       . '<th colspan="6" style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $order_count ) . ' orders</th>'
       . '<td>' . wc_price( $total_subtotal ) . '</td>'
       . '<td>' . wc_price( $total_total ) . '</td>'
       . '<td>' . wc_price( $total_discount ) . '</td>'
       . '<td></td>'
       . '<td>' . wc_price( $total_website_tip ) . '</td>'
       . '<td>' . wc_price( $total_epayment_tip ) . '</td>'
       . '<td>' . wc_price( $total_shipping ) . '</td>'
       . '<td>' . wc_price( $total_net_sales ) . '</td>'
       . '<td>' . wc_price( $total_cogs ) . '</td>'
       . '<td>' . wc_price( $total_profit ) . '</td>'
       . '<td colspan="2"></td>'
       . '<td>' . wc_price( $total_store_credit ) . '</td>'
       . '<td></td>'
       . '<td>' . implode(
            '<br>',
            array_map(
                fn( $k, $v ) => "$k: " . wc_price( $v ),
                array_keys( $real_payment_totals ),
                array_values( $real_payment_totals )
            )
         ) . '</td>'
       . '<td colspan="2"></td>'
       . '</tr></tfoot></table>';
}
