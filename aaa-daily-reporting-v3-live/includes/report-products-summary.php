<?php
/**
 * File: includes/report-products-summary-v3.php
 * Description: Product totals (summary view) with ATUM COGS integration, sorting, stock highlighting, and links for AAA Daily Reporting
 * Version: 3.2.0
 */

function aaa_render_product_summary_table( $orders ) {
    global $wpdb;
    $products = [];

    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $pid     = $product->get_id();
            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            // Determine unit cost: ATUM → Woo COGS meta → 50% fallback
            $atum_table = $wpdb->prefix . 'atum_product_data';
            $atum_cost  = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT purchase_price FROM {$atum_table} WHERE product_id = %d",
                    $pid
                )
            );

            if ( $atum_cost !== null && $atum_cost !== '' ) {
                $unit_cost = (float) $atum_cost;
            } else {
                $meta_cost = get_post_meta( $pid, '_cogs_total_value', true );
                if ( $meta_cost !== '' ) {
                    $unit_cost = (float) $meta_cost;
                } else {
                    $sale_price    = (float) $product->get_sale_price();
                    $regular_price = (float) $product->get_regular_price();
                    $unit_cost     = $sale_price > 0
                        ? $sale_price * 0.5
                        : $regular_price * 0.5;
                }
            }

            $line_cost = $unit_cost * $qty;
            $profit    = $revenue - $line_cost;

            if ( ! isset( $products[ $pid ] ) ) {
                $products[ $pid ] = [
                    'name'    => $product->get_name(),
                    'sku'     => $product->get_sku(),
                    'qty'     => 0,
                    'revenue' => 0,
                    'cost'    => 0,
                    'profit'  => 0,
                    'stock'   => $product->get_stock_quantity(),
                    'link'    => get_edit_post_link( $pid ),
                ];
            }

            $products[ $pid ]['qty']     += $qty;
            $products[ $pid ]['revenue'] += $revenue;
            $products[ $pid ]['cost']    += $line_cost;
            $products[ $pid ]['profit']  += $profit;
        }
    }

    uasort( $products, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );

    echo '<h1>Product Totals</h1>';
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Product</th><th>SKU</th><th>Qty</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Stock Left</th>'
       . '</tr></thead><tbody>';

    foreach ( $products as $p ) {
        // Highlight out-of-stock rows
        $row_style      = ( $p['stock'] <= 0 ) ? ' style="background-color:#ffdddd;"' : '';
        $profit_display = $p['profit'] < 0
            ? '<span style="color:red;">' . wc_price( $p['profit'] ) . '</span>'
            : wc_price( $p['profit'] );

        echo '<tr' . $row_style . '>'
           . '<td><a href="' . esc_url( $p['link'] ) . '">' . esc_html( $p['name'] ) . '</a></td>'
           . '<td>' . esc_html( $p['sku'] ) . '</td>'
           . '<td>' . esc_html( $p['qty'] ) . '</td>'
           . '<td>' . wc_price( $p['revenue'] ) . '</td>'
           . '<td>' . wc_price( $p['cost'] ) . '</td>'
           . '<td>' . $profit_display . '</td>'
           . '<td>' . esc_html( $p['stock'] ) . '</td>'
           . '</tr>';
    }

    echo '</tbody></table>';
}
