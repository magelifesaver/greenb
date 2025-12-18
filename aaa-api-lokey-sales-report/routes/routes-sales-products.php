<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-products.php
 * Route: /wp-json/lokeyreports/v1/sales/products
 * Version: 1.8.0
 * Updated: 2025-12-06
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides product-level sales metrics, allowing grouping by product,
 *   category, or brand. Returns detailed totals and item-level aggregation.
 *
 * Supports:
 *   ✅ JWT authorization via Lokey JWT Bridge
 *   ✅ WooCommerce Consumer Key / Secret fallback
 *   ✅ Logged-in REST nonce / admin user access
 *   ✅ Internal WordPress requests (cron, CLI, AJAX)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load central permission checker
if ( ! function_exists( 'lokey_reports_permission_check' ) ) {
    require_once __DIR__ . '/../lokey-reports-permissions.php';
}

/**
 * --------------------------------------------------------------------------
 * Register Product Sales Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_product_routes' ) ) {
    function lokey_reports_register_product_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/products',
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => 'lokey_reports_handle_sales_products',
                'permission_callback' => 'lokey_reports_permission_check',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_product_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/products
 * --------------------------------------------------------------------------
 * Fetches and aggregates product-level sales data
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_products' ) ) {
    function lokey_reports_handle_sales_products( \WP_REST_Request $request ) {

        // Capture starting metrics if helper exists.  We use this later to
        // compute execution time and memory usage.
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-products.php');
            return new \WP_Error(
                'lokey_reports_no_woocommerce',
                __( 'WooCommerce is required for Lokey product reports.', 'lokey-reports' ),
                [ 'status' => 500 ]
            );
        }

        // 🧩 Parameters
        $params = [
            'preset' => $request->get_param('preset'),
            'from'   => $request->get_param('from'),
            'to'     => $request->get_param('to'),
        ];

        $range    = lokey_reports_parse_date_range( $params );
        $statuses = lokey_reports_sanitize_status_list( $request->get_param( 'statuses' ) );
        $group_by = lokey_reports_sanitize_product_group( $request->get_param( 'group_by' ) );

        $limit = min( absint( $request->get_param( 'limit' ) ?: 50 ), 500 );
        $order_by = in_array( $request->get_param( 'order_by' ), ['net_sales','gross_sales','qty_sold','orders_count'], true )
            ? $request->get_param( 'order_by' ) : 'net_sales';
        $order = ( strtolower( $request->get_param( 'order' ) ) === 'asc' ) ? 'asc' : 'desc';

        // 🧾 Fetch and aggregate
        $orders = lokey_reports_get_orders_for_range( $range['from'], $range['to'], $statuses );
        $rows   = lokey_reports_aggregate_products( $orders, $group_by );

        usort( $rows, function( $a, $b ) use ( $order_by, $order ) {
            $va = (float) ($a[ $order_by ] ?? 0);
            $vb = (float) ($b[ $order_by ] ?? 0);
            return ( 'asc' === $order ) ? $va <=> $vb : $vb <=> $va;
        });
        $rows = array_slice( $rows, 0, $limit );

        // 💰 Build response data (do not use WP_REST_Response yet, we add metrics later)
        $response_data = [
            'group_by'  => $group_by,
            'from'      => $range['from']->format( 'Y-m-d' ),
            'to'        => $range['to']->format( 'Y-m-d' ),
            'statuses'  => $statuses,
            'filters'   => [
                'group_by' => $group_by,
                'order_by' => $order_by,
                'order'    => $order,
                'limit'    => $limit,
            ],
            'currency'  => get_woocommerce_currency(),
            'count'     => count( $rows ),
            'rows'      => $rows,
        ];

        lokey_reports_debug(
            sprintf("✅ Product report generated (group_by: %s, %d results, %s → %s)",
                $group_by, count($rows), $response_data['from'], $response_data['to']),
            'routes-sales-products.php'
        );

        // Build WP_REST_Response and attach performance metrics if available.
        $response = rest_ensure_response( $response_data );
        if ( $metrics_start ) {
            $metrics = function_exists( 'lokey_reports_metrics_end' ) ? lokey_reports_metrics_end( $metrics_start ) : null;
            if ( $metrics ) {
                $response->header( 'X-Lokey-Exec-Time', number_format( $metrics['time'], 4 ) );
                $response->header( 'X-Lokey-Memory', (string) $metrics['memory'] );
            }
        }
        return $response;
    }
}