<?php
/**
 * File: includes/report-brand-summary-v2.php
 * Description: Brand summary with sortable table for AAA Daily Reporting
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function aaa_render_brand_summary_v2( $orders ) {
    // 1) Build brand data
    $brand_data = [];

    foreach ( $orders as $order ) {
        $order_id = $order->get_id();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $qty     = $item->get_quantity();
            $revenue = $item->get_total();

            $terms = wp_get_post_terms( $product->get_id(), 'berocket_brand' ) ?: [];
            foreach ( $terms as $brand ) {
                $bid = $brand->term_id;
                if ( ! isset( $brand_data[ $bid ] ) ) {
                    $brand_data[ $bid ] = [
                        'name'    => $brand->name,
                        'qty'     => 0,
                        'revenue' => 0,
                        'orders'  => [],
                        'link'    => get_edit_term_link( $bid, 'berocket_brand' ),
                    ];
                }
                $brand_data[ $bid ]['qty']     += $qty;
                $brand_data[ $bid ]['revenue'] += $revenue;
                $brand_data[ $bid ]['orders'][ $order_id ] = true;
            }
        }
    }

    echo '<h1>Brand Summary</h1>';

    // 2) No data?
    if ( empty( $brand_data ) ) {
        echo '<p>No brand data available for this date.</p>';
        return;
    }

    // 3) Sort by revenue desc
    uasort( $brand_data, function( $a, $b ) {
        return $b['revenue'] <=> $a['revenue'];
    } );

    // 4) Top & bottom highlights
    $top_brand    = reset( $brand_data );
    $bottom_brand = end( $brand_data );

    echo '<p><strong>Top Brand:</strong> '
       . esc_html( $top_brand['name'] )
       . ' &mdash; ' . wc_price( $top_brand['revenue'] )
       . '</p>';

    echo '<p><strong>Bottom Brand:</strong> '
       . esc_html( $bottom_brand['name'] )
       . ' &mdash; ' . wc_price( $bottom_brand['revenue'] )
       . '</p>';

    // 5) Totals accumulator
    $sum_qty     = 0;
    $sum_revenue = 0;
    $sum_orders  = 0;

    // 6) Render table
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Brand</th>'
       . '<th>Qty</th>'
       . '<th>Revenue</th>'
       . '<th>Orders</th>'
       . '</tr></thead><tbody>';

    foreach ( $brand_data as $b ) {
        $row_orders = count( $b['orders'] );

        printf(
            '<tr>
                <td><a href="%1$s">%2$s</a></td>
                <td>%3$d</td>
                <td>%4$s</td>
                <td>%5$d</td>
            </tr>',
            esc_url( $b['link'] ),
            esc_html( $b['name'] ),
            intval( $b['qty'] ),
            wc_price( $b['revenue'] ),
            $row_orders
        );

        // accumulate
        $sum_qty     += $b['qty'];
        $sum_revenue += $b['revenue'];
        $sum_orders  += $row_orders;
    }

    echo '</tbody>';

// 7) Totals row
echo '<tfoot><tr>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">Totals</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $sum_qty ) . '</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . wc_price( $sum_revenue ) . '</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $sum_orders ) . '</th>'
   . '</tr></tfoot>';

    echo '</table>';
}
