<?php
/**
 * Forecast Purchase Order Qualification Endpoint
 *
 * Route: /lokey-inventory/v1/forecast/qualify-po
 *
 * Purpose:
 *   Evaluates forecast-enabled products to determine whether they qualify for
 *   a purchase order based on stock level, forecast metrics, and interval logic.
 *
 * Depends on:
 *   - /lokey-inventory/v1/forecast/products
 *   - WooCommerce product meta fields populated by forecasting logic.
 *
 * Author: Lokey Delivery DevOps
 * Version: 1.0.0
 * Created: 2025-12-30
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
                'description' => 'Filter forecast sales status (default active).',
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

    // --- Phase 1: Fetch filtered products using existing endpoint
    $internal_req = new WP_REST_Request( 'GET', '/lokey-inventory/v1/forecast/products' );
    $internal_req->set_param( 'stock_below', $stock_below );
    $internal_req->set_param( 'sales_status', $sales_status );
    $internal_req->set_param( 'limit', $limit );
    $internal_req->set_param( 'order_by', 'forecast_reorder_date' );
    $internal_req->set_param( 'order', 'asc' );

    $response = rest_do_request( $internal_req );
    if ( $response->is_error() ) {
        return $response;
    }

    $data = $response->get_data();
    $products = $data['data'] ?? [];

    $qualified = [];

    // --- Phase 2: Apply qualification logic
    foreach ( $products as $p ) {
        $id     = (int) $p['id'];
        $name   = $p['name'] ?? '';
        $sku    = $p['sku'] ?? '';
        $stock  = (float) ( $p['forecast_stock_qty'] ?? 0 );
        $status = $p['forecast_sales_status'] ?? '';
        $enabled = $p['forecast_enable_reorder'] ?? '';

        $lead_days = (int) ( $p['forecast_lead_time_days'] ?? 0 );
        $sales_window = (int) ( $p['forecast_sales_window_days'] ?? 30 );
        $min_order = (int) ( $p['forecast_minimum_order_qty'] ?? 1 );
        $monthly_sales = (float) ( $p['forecast_sales_month'] ?? 0 );
        $daily_sales = (float) ( $p['forecast_sales_day'] ?? 0 );

        if ( $status !== 'active' || $enabled !== 'yes' ) {
            continue;
        }

        // Normalize daily sales rate if only monthly available
        if ( $interval === 'daily' && $daily_sales <= 0 && $monthly_sales > 0 ) {
            $daily_sales = $monthly_sales / 30;
        }
        if ( $interval === 'monthly' && $monthly_sales <= 0 && $daily_sales > 0 ) {
            $monthly_sales = $daily_sales * 30;
        }

        // Calculate reorder threshold window
        $window_days = max( 1, $lead_days + $sales_window );
        $threshold_qty = $daily_sales * $window_days;

        // Determine qualification
        $qualifies = false;
        $reason = '';
        $suggested_qty = 0;

        if ( $stock <= $stock_below ) {
            $reason .= 'Stock below threshold. ';
        }
        if ( $stock <= $threshold_qty ) {
            $reason .= 'Stock within reorder window. ';
        }

        if ( $stock <= $threshold_qty && $status === 'active' && $enabled === 'yes' ) {
            $qualifies = true;
            $suggested_qty = max( $min_order, ceil( $threshold_qty - $stock ) );
        }

        if ( $qualifies ) {
            $qualified[] = [
                'id'                   => $id,
                'name'                 => $name,
                'sku'                  => $sku,
                'forecast_stock_qty'   => $stock,
                'forecast_sales_day'   => $daily_sales,
                'forecast_lead_time_days' => $lead_days,
                'forecast_minimum_order_qty' => $min_order,
                'forecast_reorder_date'=> $p['forecast_reorder_date'] ?? '',
                'forecast_oos_date'    => $p['forecast_oos_date'] ?? '',
                'qualifies'            => true,
                'reason'               => trim( $reason ),
                'suggested_order_qty'  => $suggested_qty,
            ];
        }
    }

    // --- Return structured response
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
