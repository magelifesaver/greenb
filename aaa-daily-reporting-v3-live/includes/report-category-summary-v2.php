<?php
/**
 * File: includes/report-category-summary-v2.php
 * Description: Category summary with sortable table for AAA Daily Reporting
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function aaa_render_category_summary_v2( $orders ) {
    // 1) Build category data
    $category_data = [];

    foreach ( $orders as $order ) {
        $order_id = $order->get_id();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $qty     = $item->get_quantity();
            $revenue = $item->get_total();

            $terms = get_the_terms( $product->get_id(), 'product_cat' ) ?: [];
            foreach ( $terms as $cat ) {
                $bottom = $cat->name;

                // climb to top-level parent
                $top_term = $cat;
                while ( $top_term->parent ) {
                    $top_term = get_term( $top_term->parent, 'product_cat' );
                }
                $top = $top_term->name;

                if ( ! isset( $category_data[ $bottom ] ) ) {
                    $category_data[ $bottom ] = [
                        'top'     => $top,
                        'qty'     => 0,
                        'revenue' => 0,
                        'orders'  => [],
                        'link'    => get_edit_term_link( $cat->term_id, 'product_cat' ),
                    ];
                }

                $category_data[ $bottom ]['qty']     += $qty;
                $category_data[ $bottom ]['revenue'] += $revenue;
                $category_data[ $bottom ]['orders'][ $order_id ] = true;
            }
        }
    }

    echo '<h1>Category Summary</h1>';

    // 2) No data?
    if ( empty( $category_data ) ) {
        echo '<p>No category data available for this date.</p>';
        return;
    }

    // 3) Sort by revenue desc
    uasort( $category_data, function( $a, $b ) {
        return $b['revenue'] <=> $a['revenue'];
    } );

    // 4) Top & bottom highlights
    $top_cat    = reset( $category_data );
    $bottom_cat = end( $category_data );

    echo '<p><strong>Top Category:</strong> '
       . esc_html( array_key_first( $category_data ) )
       . ' &mdash; ' . wc_price( $top_cat['revenue'] )
       . '</p>';

    echo '<p><strong>Bottom Category:</strong> '
       . esc_html( array_key_last( $category_data ) )
       . ' &mdash; ' . wc_price( $bottom_cat['revenue'] )
       . '</p>';

    // 5) Totals accumulator
    $sum_qty     = 0;
    $sum_revenue = 0;
    $sum_orders  = 0;

    // 6) Render table
    echo '<table class="widefat sortable"><thead><tr>'
       . '<th>Bottom Category</th>'
       . '<th>Top Category</th>'
       . '<th>Qty</th>'
       . '<th>Revenue</th>'
       . '<th>Orders</th>'
       . '</tr></thead><tbody>';

    foreach ( $category_data as $bottom => $c ) {
        $row_orders = count( $c['orders'] );

        printf(
            '<tr>
                <td><a href="%1$s">%2$s</a></td>
                <td>%3$s</td>
                <td>%4$d</td>
                <td>%5$s</td>
                <td>%6$d</td>
            </tr>',
            esc_url( $c['link'] ),
            esc_html( $bottom ),
            esc_html( $c['top'] ),
            intval( $c['qty'] ),
            wc_price( $c['revenue'] ),
            $row_orders
        );

        // accumulate
        $sum_qty     += $c['qty'];
        $sum_revenue += $c['revenue'];
        $sum_orders  += $row_orders;
    }

    echo '</tbody>';

// 7) Totals row
echo '<tfoot><tr>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">Totals</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;"></th>' // empty under “Top Category”
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $sum_qty ) . '</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . wc_price( $sum_revenue ) . '</th>'
   . '<th style="border-top:2px solid #444;font-weight:bold;">' . esc_html( $sum_orders ) . '</th>'
   . '</tr></tfoot>';

    echo '</table>';
}
