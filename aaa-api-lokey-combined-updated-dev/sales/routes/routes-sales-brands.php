<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-brands.php
 * Route: /wp-json/lokeyreports/v1/sales/brands
 * Version: 1.7.1
 * Updated: 2025-12-02
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides brand-level sales metrics aggregated by the configured brand taxonomy.
 *
 * Supports:
 *   ✅ JWT authorization (for GPT/external access)
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
 * Register Brand Sales Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_brand_routes' ) ) {
    function lokey_reports_register_brand_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/brands',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => 'lokey_reports_handle_sales_brands',
                // Permit access without JWT for GPT actions.
                'permission_callback' => '__return_true',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_brand_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/brands
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_brands' ) ) {
    function lokey_reports_handle_sales_brands( \WP_REST_Request $request ) {

        // Capture starting metrics for performance gauge
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-brands.php');
            return new \WP_Error(
                'lokey_reports_no_woocommerce',
                __( 'WooCommerce is required for Lokey brand reports.', 'lokey-reports' ),
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
        $limit    = min( absint( $request->get_param( 'limit' ) ?: 50 ), 500 );
        $order_by = in_array( $request->get_param( 'order_by' ), ['net_sales','gross_sales','qty_sold','orders_count'], true )
            ? $request->get_param( 'order_by' ) : 'net_sales';
        $order    = ( strtolower( $request->get_param( 'order' ) ) === 'asc' ) ? 'asc' : 'desc';

        // 🧾 Fetch and aggregate
        $orders = lokey_reports_get_orders_for_range( $range['from'], $range['to'], $statuses );
        $rows   = lokey_reports_aggregate_products( $orders, 'brand' );

        usort( $rows, function( $a, $b ) use ( $order_by, $order ) {
            $va = (float) ($a[ $order_by ] ?? 0);
            $vb = (float) ($b[ $order_by ] ?? 0);
            return ( 'asc' === $order ) ? $va <=> $vb : $vb <=> $va;
        });
        $rows = array_slice( $rows, 0, $limit );

        // 💰 Build response data
        $response_data = [
            'group_by'  => 'brand',
            'from'      => $range['from']->format( 'Y-m-d' ),
            'to'        => $range['to']->format( 'Y-m-d' ),
            'statuses'  => $statuses,
            'filters'   => [
                'order_by' => $order_by,
                'order'    => $order,
                'limit'    => $limit,
            ],
            'currency'  => get_woocommerce_currency(),
            'count'     => count( $rows ),
            'rows'      => $rows,
        ];

        lokey_reports_debug(
            sprintf("✅ Brand report generated (%d brands, %s → %s)",
                count($rows), $response_data['from'], $response_data['to']),
            'routes-sales-brands.php'
        );

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
