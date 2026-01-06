<?php
/**
 * File: includes/report-brands-categories-v2.php
 * Description: Enhanced brand and category summary with sortable tables for AAA Daily Reporting
 */

function aaa_render_brands_categories_summary_v2( $orders ) {
    $brand_data    = [];
    $category_data = [];

    // Build up brand_data and category_data
    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $qty      = $item->get_quantity();
            $revenue  = $item->get_total();
            $order_id = $order->get_id();

            // Brands
            $brands = wp_get_post_terms( $product->get_id(), 'berocket_brand' );
            foreach ( $brands as $brand ) {
                if ( ! isset( $brand_data[ $brand->term_id ] ) ) {
                    $brand_data[ $brand->term_id ] = [
                        'name'    => $brand->name,
                        'qty'     => 0,
                        'revenue' => 0,
                        'orders'  => [],
                        'link'    => get_edit_term_link( $brand->term_id, 'berocket_brand' ),
                    ];
                }
                $brand_data[ $brand->term_id ]['qty']     += $qty;
                $brand_data[ $brand->term_id ]['revenue'] += $revenue;
                $brand_data[ $brand->term_id ]['orders'][ $order_id ] = true;
            }

            // Categories
            $categories = get_the_terms( $product->get_id(), 'product_cat' );
            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $cat ) {
                    // Find bottom + top names
                    $bottom   = $cat->name;
                    $top_term = $cat;
                    while ( $top_term->parent != 0 ) {
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
    }

    // Sort by revenue descending
    uasort( $brand_data, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );
    uasort( $category_data, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );

    // — Brand Summary
    echo '<h1>Brand Summary</h1>';
    if ( ! empty( $brand_data ) ) {
        $top_brand    = reset( $brand_data );
        $bottom_brand = end( $brand_data );

        echo '<p><strong>Top Brand:</strong> ' . esc_html( $top_brand['name'] ) . ' &mdash; ' . wc_price( $top_brand['revenue'] ) . '</p>';
        echo '<p><strong>Bottom Brand:</strong> ' . esc_html( $bottom_brand['name'] ) . ' &mdash; ' . wc_price( $bottom_brand['revenue'] ) . '</p>';

        echo '<table class="widefat sortable"><thead><tr>'
           . '<th>Brand</th><th>Qty</th><th>Revenue</th><th>Orders</th>'
           . '</tr></thead><tbody>';
        foreach ( $brand_data as $b ) {
            echo '<tr>'
               . '<td><a href="' . esc_url( $b['link'] ) . '">' . esc_html( $b['name'] ) . '</a></td>'
               . '<td>' . esc_html( $b['qty'] ) . '</td>'
               . '<td>' . wc_price( $b['revenue'] ) . '</td>'
               . '<td>' . count( $b['orders'] ) . '</td>'
               . '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No brand data available for this date.</p>';
    }

    // — Category Summary
    echo '<h2>Category Summary</h2>';
    if ( ! empty( $category_data ) ) {
        echo '<table class="widefat sortable"><thead><tr>'
           . '<th>Bottom Category</th><th>Top Category</th><th>Qty</th><th>Revenue</th><th>Orders</th>'
           . '</tr></thead><tbody>';
        foreach ( $category_data as $bottom => $c ) {
            echo '<tr>'
               . '<td><a href="' . esc_url( $c['link'] ) . '">' . esc_html( $bottom ) . '</a></td>'
               . '<td>' . esc_html( $c['top'] ) . '</td>'
               . '<td>' . esc_html( $c['qty'] ) . '</td>'
               . '<td>' . wc_price( $c['revenue'] ) . '</td>'
               . '<td>' . count( $c['orders'] ) . '</td>'
               . '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No category data available for this date.</p>';
    }
}
