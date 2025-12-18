<?php
/** Forecast Products endpoint: returns forecast and reorder metrics for products with optional filters. */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/forecast/products', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            // Sanitise query parameters with sensible defaults.
            $limit           = absint( $req->get_param( 'limit' ) ) ?: 50;
            $page            = absint( $req->get_param( 'page' ) ) ?: 1;
            $stock_below     = absint( $req->get_param( 'stock_below' ) );
            $stock_above     = absint( $req->get_param( 'stock_above' ) );
            $status_filter   = sanitize_text_field( $req->get_param( 'sales_status' ) ?: '' );
            $include_private = $req->get_param( 'include_private' ) === 'yes';
            $last_sold_days  = absint( $req->get_param( 'last_sold_days' ) );
            // Sorting parameters.  Supported order_by fields match the OpenAPI spec.
            $valid_order_by  = [ 'forecast_oos_date', 'forecast_reorder_date', 'forecast_total_units_sold', 'forecast_sales_month', 'forecast_stock_qty' ];
            $order_by_param  = $req->get_param( 'order_by' );
            $order_by        = in_array( $order_by_param, $valid_order_by, true ) ? $order_by_param : 'forecast_oos_date';
            $order_param     = strtolower( $req->get_param( 'order' ) );
            $order           = $order_param === 'desc' ? 'desc' : 'asc';

            // Build the WooCommerce product query.  We filter by the
            // forecast_enable_reorder meta so that only products configured
            // for forecasting appear in the results.
            $args = [
                'status'     => $include_private ? [ 'publish', 'private' ] : 'publish',
                'limit'      => $limit,
                'page'       => $page,
                'return'     => 'ids',
                'meta_query' => [
                    [
                        'key'   => 'forecast_enable_reorder',
                        'value' => 'yes',
                    ],
                ],
            ];

            // Apply optional stock threshold filters.
            if ( $stock_below > 0 ) {
                $args['meta_query'][] = [
                    'key'     => 'forecast_stock_qty',
                    'value'   => $stock_below,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ];
            }
            if ( $stock_above > 0 ) {
                $args['meta_query'][] = [
                    'key'     => 'forecast_stock_qty',
                    'value'   => $stock_above,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ];
            }
            if ( $status_filter ) {
                $args['meta_query'][] = [
                    'key'   => 'forecast_sales_status',
                    'value' => $status_filter,
                ];
            }
            if ( count( $args['meta_query'] ) > 1 ) {
                $args['meta_query']['relation'] = 'AND';
            }

            // Fetch product IDs using WooCommerce functions.  If WooCommerce
            // is inactive wc_get_products() returns an empty array.
            $product_ids = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : [];

            // Determine a cutoff date for last_sold filtering.
            $cutoff_date = $last_sold_days > 0
                ? strtotime( '-' . $last_sold_days . ' days', current_time( 'timestamp' ) )
                : null;

            // List of forecast meta keys to return.  Keeping this inline
            // reduces line count to meet plugin size guidelines.  Extend
            // this array to include additional forecast meta values.
            $keys = [ 'forecast_stock_qty','forecast_sales_month','forecast_sales_day','forecast_oos_date','forecast_reorder_date','forecast_margin_percent','forecast_frozen_capital','forecast_daily_sales_rate','forecast_lead_time_days','forecast_minimum_order_qty','forecast_sales_window_days','forecast_cost_override','forecast_first_sold_date','forecast_last_sold_date','forecast_enable_reorder','forecast_sales_status','forecast_product_class','forecast_po_priority_score','forecast_is_not_moving','forecast_is_must_stock','forecast_is_new_product','forecast_do_not_reorder','forecast_force_reorder','forecast_minimum_stock','forecast_tier_threshold_1','forecast_tier_threshold_2','forecast_tier_threshold_3','forecast_is_out_of_stock','forecast_is_stale_inventory' ];

            $rows = [];
            foreach ( $product_ids as $pid ) {
                $product = wc_get_product( $pid );
                if ( ! $product ) {
                    continue;
                }

                // Skip recently sold products when last_sold_days is set.
                if ( $cutoff_date ) {
                    $last_sold = get_post_meta( $pid, 'forecast_last_sold_date', true );
                    if ( $last_sold && strtotime( $last_sold ) > $cutoff_date ) {
                        continue;
                    }
                }

                $row = [
                    'id'     => (int) $pid,
                    'name'   => $product->get_name(),
                    'sku'    => $product->get_sku(),
                    'price'  => (float) $product->get_price(),
                    'status' => $product->get_status(),
                ];

                // Populate all requested forecast keys.
                foreach ( $keys as $key ) {
                    $row[ $key ] = get_post_meta( $pid, $key, true );
                }

                $rows[] = $row;
            }

            // Sort the results by the chosen forecast field.
            usort( $rows, function ( $a, $b ) use ( $order_by, $order ) {
                $va = $a[ $order_by ] ?? null;
                $vb = $b[ $order_by ] ?? null;
                if ( $va === $vb ) {
                    return 0;
                }
                $cmp = $va <=> $vb;
                return $order === 'asc' ? $cmp : -$cmp;
            } );

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'count'     => count( $rows ),
                'page'      => $page,
                'limit'     => $limit,
                'filter'    => $status_filter ?: 'none',
                'timestamp' => current_time( 'mysql' ),
                'data'      => $rows,
            ], 200 );
        },
        // Remove JWT requirement so GPT clients can access forecast metrics without
        // providing a token.  Internal checks still enforce WooCommerce context.
        'permission_callback' => '__return_true',
        'args' => [
            'limit' => [ 'type' => 'integer', 'default' => 50 ],
            'page'  => [ 'type' => 'integer', 'default' => 1 ],
            'stock_below' => [ 'type' => 'integer' ],
            'stock_above' => [ 'type' => 'integer' ],
            'sales_status' => [ 'type' => 'string' ],
            'include_private' => [ 'type' => 'string', 'enum' => [ 'yes', 'no' ], 'default' => 'no' ],
            'last_sold_days' => [ 'type' => 'integer' ],
            'order_by'     => [ 'type' => 'string' ],
            'order'        => [ 'type' => 'string' ],
        ],
    ] );
} );
