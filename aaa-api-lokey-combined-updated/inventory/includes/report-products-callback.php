<?php
/*
 * Sales report callback for /report/products.
 * Computes units sold, sales value, stock quantity and average
 * units per day for WooCommerce products within a look‑back period.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Handle GET requests to /report/products. Accepts optional
 * query params stock_below, stock_above, days, limit, order_by and order.
 * Returns a WP_REST_Response with performance metrics and meta data. */
function lokey_inv_report_products_v200( WP_REST_Request $req ) {
    global $wpdb;
    $time_start = microtime( true );

    // Sanitise and normalise inputs.
    $stock_below = absint( $req->get_param( 'stock_below' ) );
    $stock_above = absint( $req->get_param( 'stock_above' ) );
    $days        = absint( $req->get_param( 'days' ) ) ?: 30;
    $limit       = absint( $req->get_param( 'limit' ) ) ?: 50;

    $order_by_valid = [ 'units_sold', 'sales_value', 'stock_qty', 'sales_per_day' ];
    $order_by       = in_array( $req->get_param( 'order_by' ), $order_by_valid, true )
        ? $req->get_param( 'order_by' )
        : 'units_sold';
    $order = strtolower( $req->get_param( 'order' ) ) === 'asc' ? 'asc' : 'desc';

    // Define the cutoff date based on the look‑back period.
    $start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );

    // Build the WooCommerce product query.  We fetch IDs only to reduce
    // memory usage.  Meta queries filter by stock thresholds when provided.
    $args = [
        'status'     => [ 'publish', 'private' ],
        'type'       => [ 'simple', 'variation' ],
        'limit'      => $limit,
        'return'     => 'ids',
        'meta_query' => [],
    ];
    if ( $stock_below > 0 ) {
        $args['meta_query'][] = [
            'key'     => '_stock',
            'value'   => $stock_below,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ];
    }
    if ( $stock_above > 0 ) {
        $args['meta_query'][] = [
            'key'     => '_stock',
            'value'   => $stock_above,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ];
    }
    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    $product_ids = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : [];
    $results     = [];

    try {
        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( ! $product ) {
                continue;
            }

            // Skip products that do not manage stock.
            $stock_qty = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;

            // Efficient SQL to count quantity sold since the cutoff date.  We
            // query directly against the order item meta tables to avoid
            // loading entire orders into memory.  This query returns the
            // total quantity of the product sold within the look‑back window.
            $query      = 'SELECT SUM(qty.meta_value) FROM ' . $wpdb->posts . ' p ' .
                'INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items oi ON p.ID = oi.order_id ' .
                'INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta pid ON oi.order_item_id = pid.order_item_id ' .
                'INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta qty ON oi.order_item_id = qty.order_item_id ' .
                "WHERE p.post_type = 'shop_order' AND p.post_status IN ('wc-completed','wc-processing') " .
                'AND p.post_date >= %s AND pid.meta_key = \'_product_id\' AND pid.meta_value = %d ' .
                "AND qty.meta_key = '_qty' LIMIT 1";
            $units_sold = (int) $wpdb->get_var( $wpdb->prepare( $query, $start_date, $pid ) );

            $price       = (float) $product->get_price();
            $sales_value = round( $units_sold * $price, 2 );
            $sales_per_day = round( $days > 0 ? ( $units_sold / $days ) : 0, 4 );

            $results[] = [
                'id'            => (int) $pid,
                'name'          => $product->get_name(),
                'sku'           => $product->get_sku(),
                'stock_qty'     => $stock_qty,
                'units_sold'    => $units_sold,
                'sales_value'   => $sales_value,
                'sales_per_day' => $sales_per_day,
            ];
        }
    } catch ( Exception $e ) {
        // Return a structured error if the query fails.
        return new WP_REST_Response( [
            'version' => LOKEY_INV_API_VERSION,
            'status'  => 'error',
            'error'   => 'query_failed',
            'message' => $e->getMessage(),
        ], 500 );
    }

    // Sort results by the requested metric and order using the spaceship operator.
    usort( $results, function ( $a, $b ) use ( $order_by, $order ) {
        return ( $order === 'asc' )
            ? $a[ $order_by ] <=> $b[ $order_by ]
            : $b[ $order_by ] <=> $a[ $order_by ];
    } );

    // Cap results to the requested limit.  Although wc_get_products already
    // applies the limit, we still slice here to be safe.
    if ( count( $results ) > $limit ) {
        $results = array_slice( $results, 0, $limit );
    }

    $time_end    = microtime( true );
    $query_time  = round( ( $time_end - $time_start ) * 1000, 2 );

    return new WP_REST_Response( [
        'version'       => LOKEY_INV_API_VERSION,
        'count'         => count( $results ),
        'limit'         => $limit,
        'days'          => $days,
        'order_by'      => $order_by,
        'order'         => $order,
        'query_time_ms' => $query_time,
        'timestamp'     => current_time( 'mysql' ),
        'data'          => $results,
    ], 200 );
}
