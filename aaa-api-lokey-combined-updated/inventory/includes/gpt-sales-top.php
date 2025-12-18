<?php
/**
 * GPT Sales Top Endpoint
 *
 * Returns the top‑selling products for a time range using WooCommerce order
 * data.  The route is registered at GET /gpt/v1/sales/top and requires
 * JWT authentication via lokey_require_jwt_auth.  Optional query parameters
 * allow callers to specify the number of days to look back and the number of
 * results to return.  The response includes product ID, name, quantity sold,
 * total sales and average price per unit.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'gpt/v1', '/sales/top', [
        'methods'  => 'GET',
        'callback' => 'lokey_gpt_sales_top',
        // Do not enforce JWT for the GPT top endpoint.  The internal
        // handler operates entirely on WooCommerce data and does not
        // expose sensitive information, so it can be public.
        'permission_callback' => '__return_true',
        'args'     => [
            'days' => [
                'description' => 'Number of days to include (optional).',
                'type'        => 'integer',
                'required'    => false,
            ],
            'limit' => [
                'description' => 'Maximum number of results (optional).',
                'type'        => 'integer',
                'required'    => false,
            ],
        ],
    ] );
} );

/**
 * Callback for the GPT sales top route.  Calculates product sales over a
 * single day window.  It uses WooCommerce’s wc_get_orders() function to
 * retrieve completed orders and aggregates line items by product.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function lokey_gpt_sales_top( WP_REST_Request $request ) {
    if ( ! function_exists( 'wc_get_orders' ) ) {
        return new WP_Error( 'woocommerce_missing', 'WooCommerce not active', [ 'status' => 500 ] );
    }

    $days  = absint( $request->get_param( 'days' ) );
    if ( $days <= 0 ) {
        $days = 1;
    }
    $limit = absint( $request->get_param( 'limit' ) );
    if ( $limit <= 0 ) {
        $limit = 100;
    }
    // Cap limit at 500 to avoid excessive payloads
    if ( $limit > 500 ) {
        $limit = 500;
    }

    // Determine the site timezone; fallback to Los Angeles
    $tz_string = get_option( 'timezone_string' ) ?: 'America/Los_Angeles';
    $tz  = new DateTimeZone( $tz_string );
    $utc = new DateTimeZone( 'UTC' );

    $now = new DateTime( 'now', $tz );
    /*
     * Calculate a start/end range based on the number of days requested.
     * When days > 1 we subtract (days - 1) because the range is inclusive of today.
     * Example: days=7 will yield a 7‑day window including today.
     */
    $start_local = ( clone $now )->sub( new DateInterval( 'P' . ( $days - 1 ) . 'D' ) )->setTime( 0, 0, 0 );
    $end_local   = ( clone $now )->setTime( 23, 59, 59 );
    // Convert to UTC for WooCommerce query
    $start_utc = ( clone $start_local )->setTimezone( $utc );
    $end_utc   = ( clone $end_local )->setTimezone( $utc );

    // Fetch orders within the date range.  Include both completed and processing
    // statuses to provide a fuller picture of current sales.
    $orders = wc_get_orders( [
        'status'       => [ 'completed', 'processing' ],
        'date_created' => $start_utc->format( 'Y-m-d H:i:s' ) . '...' . $end_utc->format( 'Y-m-d H:i:s' ),
        'limit'        => -1,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'return'       => 'objects',
    ] );

    $products = [];
    foreach ( $orders as $order ) {
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            $pid = $item->get_product_id();
            if ( ! $pid ) {
                continue;
            }
            $name  = $item->get_name();
            $qty   = (int) $item->get_quantity();
            $total = (float) $item->get_total();
            if ( ! isset( $products[ $pid ] ) ) {
                $products[ $pid ] = [
                    'product_id'    => $pid,
                    'product_name'  => $name,
                    'quantity_sold' => 0,
                    'total_sales'   => 0.0,
                ];
            }
            $products[ $pid ]['quantity_sold'] += $qty;
            $products[ $pid ]['total_sales']   += $total;
        }
    }

    // Compute average price and round totals
    foreach ( $products as &$p ) {
        $p['avg_price_sold'] = $p['quantity_sold'] > 0
            ? round( $p['total_sales'] / $p['quantity_sold'], 2 )
            : 0.0;
        $p['total_sales'] = round( $p['total_sales'], 2 );
    }
    unset( $p );

    // Sort by quantity sold descending
    usort( $products, function ( $a, $b ) {
        return $b['quantity_sold'] <=> $a['quantity_sold'];
    } );
    $top = array_slice( array_values( $products ), 0, $limit );

    // Build and return response
    return new WP_REST_Response( [
        'days'      => $days,
        'limit'     => $limit,
        'timezone'  => $tz_string,
        'date_from' => $start_local->format( 'Y-m-d H:i:s' ),
        'date_to'   => $end_local->format( 'Y-m-d H:i:s' ),
        'count'     => count( $top ),
        'generated' => gmdate( 'Y-m-d H:i:s' ),
        'top'       => $top,
    ], 200 );
}
