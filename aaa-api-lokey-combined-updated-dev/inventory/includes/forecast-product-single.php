<?php
/**
 * Single Forecast Product endpoint.
 *
 * Retrieves forecast metadata for a single product ID.  Returns
 * HTTP 404 if the product does not exist or WooCommerce is inactive.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/forecast/products/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $id      = absint( $req['id'] );
            $product = wc_get_product( $id );

            if ( ! $product ) {
                return new WP_REST_Response( [
                    'version' => LOKEY_INV_API_VERSION,
                    'status'  => 'error',
                    'message' => 'Product not found',
                    'id'      => $id,
                ], 404 );
            }

            // List of forecast meta keys to return.  Keeping this in one
            // place makes it easy to extend in the future.
            $keys = [
                'forecast_stock_qty',
                'forecast_sales_month',
                'forecast_sales_day',
                'forecast_oos_date',
                'forecast_reorder_date',
                'forecast_margin_percent',
                'forecast_frozen_capital',
                'forecast_daily_sales_rate',
                'forecast_lead_time_days',
                'forecast_minimum_order_qty',
                'forecast_sales_window_days',
                'forecast_cost_override',
                'forecast_first_sold_date',
                'forecast_last_sold_date',
                'forecast_enable_reorder',
                'forecast_sales_status',
                'forecast_product_class',
                'forecast_po_priority_score',
                'forecast_is_not_moving',
                'forecast_is_must_stock',
                'forecast_is_new_product',
                'forecast_do_not_reorder',
                'forecast_force_reorder',
                'forecast_minimum_stock',
                'forecast_tier_threshold_1',
                'forecast_tier_threshold_2',
                'forecast_tier_threshold_3',
                'forecast_is_out_of_stock',
                'forecast_is_stale_inventory',
            ];

            $row = [
                'id'     => $id,
                'name'   => $product->get_name(),
                'sku'    => $product->get_sku(),
                'price'  => (float) $product->get_price(),
                'status' => $product->get_status(),
            ];

            foreach ( $keys as $key ) {
                $row[ $key ] = get_post_meta( $id, $key, true );
            }

            return new WP_REST_Response( [
                'version' => LOKEY_INV_API_VERSION,
                'status'  => 'success',
                'data'    => $row,
            ], 200 );
        },
        // Allow public access to forecast details; JWT not required for GPT actions.
        'permission_callback' => '__return_true',
    ] );
} );
