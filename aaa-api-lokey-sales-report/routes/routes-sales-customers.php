<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-customers.php
 * Route: /wp-json/lokeyreports/v1/sales/customers
 * Version: 1.7.1
 * Updated: 2025-12-02
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides customer-level sales metrics including total orders, revenue,
 *   and average order value per customer.
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
 * Register Customer Sales Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_customer_routes' ) ) {
    function lokey_reports_register_customer_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/customers',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => 'lokey_reports_handle_sales_customers',
                'permission_callback' => 'lokey_reports_permission_check',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_customer_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/customers
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_customers' ) ) {
    function lokey_reports_handle_sales_customers( \WP_REST_Request $request ) {

        // Start metrics capturing
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-customers.php');
            return new \WP_Error(
                'lokey_reports_no_woocommerce',
                __( 'WooCommerce is required for Lokey customer reports.', 'lokey-reports' ),
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

        $limit = min( absint( $request->get_param( 'limit' ) ?: 100 ), 1000 );
        $order_by = in_array( $request->get_param( 'order_by' ), ['net_sales','gross_sales','orders','items'], true )
            ? $request->get_param( 'order_by' ) : 'net_sales';
        $order = ( strtolower( $request->get_param( 'order' ) ) === 'asc' ) ? 'asc' : 'desc';

        // 🧾 Fetch & aggregate
        $orders   = lokey_reports_get_orders_for_range( $range['from'], $range['to'], $statuses );
        $rows     = lokey_reports_aggregate_customers( $orders );
        $currency = function_exists( 'get_woocommerce_currency' )
            ? get_woocommerce_currency()
            : get_option( 'woocommerce_currency', 'USD' );

        usort( $rows, function( $a, $b ) use ( $order_by, $order ) {
            $va = (float) ($a[ $order_by ] ?? 0);
            $vb = (float) ($b[ $order_by ] ?? 0);
            return ( 'asc' === $order ) ? $va <=> $vb : $vb <=> $va;
        });
        $rows = array_slice( $rows, 0, $limit );

        // 📊 Summary
        $customers_count = count( $rows );
        $total_orders    = array_sum( array_column( $rows, 'orders' ) );
        $total_net       = array_sum( array_column( $rows, 'net_sales' ) );

        // 💰 Response data
        $response_data = [
            'group_by'  => 'customer',
            'from'      => $range['from']->format( 'Y-m-d' ),
            'to'        => $range['to']->format( 'Y-m-d' ),
            'statuses'  => $statuses,
            'filters'   => [
                'order_by' => $order_by,
                'order'    => $order,
                'limit'    => $limit,
            ],
            'currency'  => $currency,
            'summary'   => [
                'customers_count'         => $customers_count,
                'total_orders'            => $total_orders,
                'total_net_sales'         => $total_net,
                'avg_orders_per_customer' => $customers_count > 0 ? $total_orders / $customers_count : 0.0,
                'avg_net_per_customer'    => $customers_count > 0 ? $total_net / $customers_count : 0.0,
            ],
            'count'     => count( $rows ),
            'rows'      => $rows,
        ];

        lokey_reports_debug(
            sprintf("✅ Customer report generated (%d customers, %s → %s)",
                count($rows), $response_data['from'], $response_data['to']),
            'routes-sales-customers.php'
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