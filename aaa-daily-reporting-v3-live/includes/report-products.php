<?php
/**
 * File: includes/report-products-v3.php
 * Description: Product breakdown with order links and status.
 */

function aaa_render_product_breakdown( $orders ) {
    global $wpdb;

    $time_blocks = [
        'morning'   => '#e3f7d3',
        'afternoon' => '#fffcc9',
        'evening'   => '#ffe0b3',
        'night'     => '#ffd1d1',
    ];

    $sum_qty = $sum_revenue = $sum_cost = $sum_profit = 0;

    echo '<h1>Product Breakdown</h1>';
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Order ID</th><th>Status</th><th>Date</th><th>Product</th><th>SKU</th>'
       . '<th>Qty</th><th>Revenue</th><th>Cost</th><th>Profit</th>'
       . '<th>Brand</th><th>Category</th><th>Stock</th>'
       . '</tr></thead><tbody>';

    foreach ( $orders as $order ) {
        $order_id   = $order->get_id();
        $order_time = $order->get_date_created();
        $hour       = $order_time ? (int) $order_time->format('H') : 0;
        $color      = $hour < 12 ? $time_blocks['morning'] : ( $hour < 16 ? $time_blocks['afternoon'] : ( $hour < 19 ? $time_blocks['evening'] : $time_blocks['night'] ) );
        $date_label = $order_time ? $order_time->date_i18n( 'l n/j H:i' ) : '';
        $status     = ucfirst( $order->get_status() );
        $order_link = esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) );

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            $pid     = $product->get_id();
            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            $atum_table = $wpdb->prefix . 'atum_product_data';
            $atum_cost  = $wpdb->get_var( $wpdb->prepare( "SELECT purchase_price FROM {$atum_table} WHERE product_id = %d", $pid ) );
            if ( $atum_cost !== null && $atum_cost !== '' ) {
                $unit_cost = (float) $atum_cost;
            } else {
                $meta_cost = get_post_meta( $pid, '_cogs_total_value', true );
                $unit_cost = $meta_cost !== '' ? (float) $meta_cost : (float) $product->get_price() * 0.5;
            }

            $cost   = $unit_cost * $qty;
            $profit = $revenue - $cost;

            $sum_qty     += $qty;
            $sum_revenue += $revenue;
            $sum_cost    += $cost;
            $sum_profit  += $profit;

            printf(
                '<tr style="background:%s;"><td><a href="%s">#%d</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
              . '<td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
              . '<td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_attr( $color ),
                $order_link,
                $order_id,
                esc_html( $status ),
                esc_html( $date_label ),
                esc_html( $product->get_name() ),
                esc_html( $product->get_sku() ),
                esc_html( $qty ),
                wc_price( $revenue ),
                wc_price( $cost ),
                wc_price( $profit ),
                esc_html( aaa_get_brand_name( $pid ) ),
                esc_html( aaa_get_category_path( $pid ) ),
                esc_html( (int) $product->get_stock_quantity() )
            );
        }
    }

    echo '</tbody><tfoot><tr>'
       . '<th colspan="5" style="border-top:2px solid #444;font-weight:bold;">Totals</th>'
       . '<th>' . esc_html( $sum_qty ) . '</th>'
       . '<th>' . wc_price( $sum_revenue ) . '</th>'
       . '<th>' . wc_price( $sum_cost ) . '</th>'
       . '<th>' . wc_price( $sum_profit ) . '</th>'
       . '<th colspan="3"></th>'
       . '</tr></tfoot></table>';
}

function aaa_get_brand_name( $product_id ) {
    $brands = wp_get_post_terms( $product_id, 'berocket_brand' );
    return ! empty( $brands ) ? $brands[0]->name : '—';
}

function aaa_get_category_path( $product_id ) {
    $terms = get_the_terms( $product_id, 'product_cat' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) return '—';
    $term = $terms[0];
    $path = [ $term->name ];
    while ( $term->parent ) {
        $term = get_term( $term->parent, 'product_cat' );
        if ( is_wp_error( $term ) ) break;
        array_unshift( $path, $term->name );
    }
    return implode( ' → ', $path );
}
