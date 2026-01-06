<?php
/**
 * File: includes/report-delivery-city.php
 * Description: Report by delivery city — number of orders and total revenue
 */

function aaa_render_delivery_city_report( $orders ) {
    $city_data     = [];
    $sum_orders    = 0;
    $sum_revenue   = 0;

    // 1) Collect per-city data
    foreach ( $orders as $order ) {
        $city = $order->get_shipping_city() ?: '—';

        if ( ! isset( $city_data[ $city ] ) ) {
            $city_data[ $city ] = [ 'orders' => 0, 'revenue' => 0 ];
        }

        $city_data[ $city ]['orders']++;
        $city_data[ $city ]['revenue'] += $order->get_total();
    }

    // 2) Sort by revenue desc
    uasort( $city_data, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );

    // 3) Sum totals
    foreach ( $city_data as $data ) {
        $sum_orders  += $data['orders'];
        $sum_revenue += $data['revenue'];
    }

    // 4) Render table
    echo '<h1>Delivery Cities</h1>';
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>City</th><th># Orders</th><th>Total Revenue</th>'
       . '</tr></thead><tbody>';

    foreach ( $city_data as $city => $data ) {
        printf(
            '<tr><td>%1$s</td><td>%2$d</td><td>%3$s</td></tr>',
            esc_html( $city ),
            intval(   $data['orders'] ),
            wc_price( $data['revenue'] )
        );
    }

    // 5) Totals footer
    echo '</tbody><tfoot><tr>'
       . '<th style="border-top:2px solid #444;font-weight:bold;">Totals</th>'
       . '<th style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $sum_orders )  . '</th>'
       . '<th style="border-top:2px solid #444;font-weight:bold;">' . wc_price(   $sum_revenue ) . '</th>'
       . '</tr></tfoot></table>';
}
