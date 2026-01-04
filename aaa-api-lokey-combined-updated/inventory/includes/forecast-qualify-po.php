<?php
/**
 * Forecast Purchase Order Qualification Endpoint
 *
 * Route: /lokey-inventory/v1/forecast/qualify-po
 *
 * Purpose:
 *   Evaluates forecast-enabled products to determine whether they qualify for
 *   a purchase order based on stock level, forecast metrics, and interval logic.
 *   Supports "sales_status=all" to include all forecast-enabled products.
 *
 * Author: Lokey Delivery DevOps
 * Version: 1.1.0
 * Updated: 2025-12-31
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/forecast/qualify-po', [
        'methods'  => 'GET',
        'callback' => 'lokey_inv_forecast_qualify_po',
        'permission_callback' => '__return_true',
        'args' => [
            'interval' => [
                'description' => 'Reorder evaluation interval: daily or monthly (default: daily).',
                'type'        => 'string',
                'enum'        => [ 'daily', 'monthly' ],
                'default'     => 'daily',
            ],
            'stock_below' => [
                'description' => 'Optional override for stock threshold (default 5).',
                'type'        => 'integer',
                'default'     => 5,
            ],
            'sales_status' => [
                'description' => 'Filter forecast sales status (default active, use all to include every status).',
                'type'        => 'string',
                'default'     => 'active',
            ],
            'limit' => [
                'description' => 'Maximum number of records (default 100).',
                'type'        => 'integer',
                'default'     => 100,
            ],
        ],
    ] );
} );

/**
 * Evaluate forecasted products to determine PO qualification.
 *
 * @param WP_REST_Request $req
 * @return WP_REST_Response
 */
function lokey_inv_forecast_qualify_po( WP_REST_Request $req ) {
    $interval      = sanitize_text_field( $req->get_param( 'interval' ) ?: 'daily' );
    $stock_below   = absint( $req->get_param( 'stock_below' ) ?: 5 );
    $sales_status  = sanitize_text_field( $req->get_param( 'sales_status' ) ?: 'active' );
    $limit         = absint( $req->get_param( 'limit' ) ?: 100 );

    /*
     * -------------------------------------------------------------------------
     * Phase 1: Fetch filtered products using a dual-stock logic.
     * This matches the Forecaster dashboard by checking BOTH:
     *   - WooCommerce _stock
     *   - forecast_stock_qty
     * -------------------------------------------------------------------------
     */
    $args = [
        'status'     => 'publish',
        'limit'      => $limit,
        'return'     => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key'   => 'forecast_enable_reorder',
                'value' => 'yes',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => '_stock',
                    'value'   => $stock_below,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'forecast_stock_qty',
                    'value'   => $stock_below,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ],
    ];

    // Handle sales_status filter (support "all" keyword)
    if ( ! empty( $sales_status ) && strtolower( $sales_status ) !== 'all' ) {
        $args['meta_query'][] = [
            'key'   => 'forecast_sales_status',
            'value' => $sales_status,
        ];
    }

    // Retrieve products directly with WooCommerce.
    $product_ids = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : [];

    $qualified = [];

    /*
     * -------------------------------------------------------------------------
     * Phase 2: Evaluate qualification criteria.
     * -------------------------------------------------------------------------
     */
    foreach ( $product_ids as $pid ) {
        $product = wc_get_product( $pid );
        if ( ! $product ) continue;

        $stock           = (float) get_post_meta( $pid, '_stock', true );
        $forecast_stock  = (float) get_post_meta( $pid, 'forecast_stock_qty', true );
        $status          = get_post_meta( $pid, 'forecast_sales_status', true );
        $enabled         = get_post_meta( $pid, 'forecast_enable_reorder', true );
        $lead_days       = (int) get_post_meta( $pid, 'forecast_lead_time_days', true );
        $sales_window    = (int) get_post_meta( $pid, 'forecast_sales_window_days', true );
        $min_order       = (int) get_post_meta( $pid, 'forecast_minimum_order_qty', true );
        $daily_sales     = (float) get_post_meta( $pid, 'forecast_sales_day', true );
        $monthly_sales   = (float) get_post_meta( $pid, 'forecast_sales_month', true );

        // Skip disabled forecast products.
        if ( $enabled !== 'yes' ) continue;
        // Skip inactive products unless "all" was requested.
        if ( strtolower( $sales_status ) !== 'all' && $status !== 'active' ) continue;

        if ( $interval === 'daily' && $daily_sales <= 0 && $monthly_sales > 0 ) {
            $daily_sales = $monthly_sales / 30;
        }

        $window_days   = max( 1, $lead_days + $sales_window );
        $threshold_qty = $daily_sales * $window_days;

        $current_qty   = min( $stock, $forecast_stock );
        $qualifies     = false;
        $reason        = '';
        $suggested_qty = 0;

        if ( $current_qty <= $stock_below ) {
            $reason .= 'Stock below threshold. ';
        }
        if ( $current_qty <= $threshold_qty ) {
            $reason .= 'Stock within reorder window. ';
        }

        if ( $current_qty <= $threshold_qty && $enabled === 'yes' ) {
            $qualifies = true;
            $suggested_qty = max( $min_order, ceil( $threshold_qty - $current_qty ) );
        }

        if ( $qualifies ) {
            $qualified[] = [
                'id'                   => $pid,
                'name'                 => $product->get_name(),
                'sku'                  => $product->get_sku(),
                'forecast_stock_qty'   => $forecast_stock,
                'forecast_sales_day'   => $daily_sales,
                'forecast_lead_time_days' => $lead_days,
                'forecast_minimum_order_qty' => $min_order,
                'forecast_reorder_date'=> get_post_meta( $pid, 'forecast_reorder_date', true ),
                'forecast_oos_date'    => get_post_meta( $pid, 'forecast_oos_date', true ),
                'qualifies'            => true,
                'reason'               => trim( $reason ),
                'suggested_order_qty'  => $suggested_qty,
            ];
        }
    }

    /*
     * -------------------------------------------------------------------------
     * Return structured response
     * -------------------------------------------------------------------------
     */
    return new WP_REST_Response( [
        'version'   => LOKEY_INV_API_VERSION,
        'status'    => 'success',
        'interval'  => $interval,
        'criteria'  => [
            'stock_below'  => $stock_below,
            'sales_status' => $sales_status,
        ],
        'count'     => count( $qualified ),
        'generated' => current_time( 'mysql' ),
        'qualified' => $qualified,
    ], 200 );
}
