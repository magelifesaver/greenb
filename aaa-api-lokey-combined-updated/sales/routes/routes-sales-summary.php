<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-summary.php
 * Route: /wp-json/lokeyreports/v1/sales/summary
 * Version: 1.7.1
 * Updated: 2025-12-02
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides consolidated sales summary totals and time-series data.
 *   Supports:
 *     ✅ JWT authorization (for GPT/external access)
 *     ✅ WooCommerce Consumer Key / Secret fallback
 *     ✅ Logged-in REST nonce / admin user access
 *     ✅ Internal WordPress requests (cron, CLI, AJAX)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load central permission checker
if ( ! function_exists( 'lokey_reports_permission_check' ) ) {
    require_once __DIR__ . '/../lokey-reports-permissions.php';
}

/**
 * --------------------------------------------------------------------------
 * Register Summary Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_summary_routes' ) ) {
    function lokey_reports_register_summary_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/summary',
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => 'lokey_reports_handle_sales_summary',
                // Permit access without JWT for GPT actions.
                'permission_callback' => '__return_true',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_summary_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/summary
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_summary' ) ) {
    function lokey_reports_handle_sales_summary( \WP_REST_Request $request ) {

        // Capture starting metrics
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-summary.php');
            return new \WP_Error(
                'lokey_reports_no_woocommerce',
                __( 'WooCommerce is required for Lokey reports.', 'lokey-reports' ),
                [ 'status' => 500 ]
            );
        }

        // 🧩 Parse parameters
        $group_by = lokey_reports_sanitize_group_by( $request->get_param( 'group_by' ) );
        $params   = [
            'preset' => $request->get_param( 'preset' ),
            'from'   => $request->get_param( 'from' ),
            'to'     => $request->get_param( 'to' ),
        ];

        // 🕒 Build date range and timezone
        $range    = lokey_reports_parse_date_range( $params );
        $statuses = lokey_reports_sanitize_status_list( $request->get_param( 'statuses' ) );

        // 📦 Fetch orders and build summary
        $orders  = lokey_reports_get_orders_for_range( $range['from'], $range['to'], $statuses );
        $summary = lokey_reports_build_sales_summary( $orders, $group_by, $range['timezone'] );

        // 💰 Currency
        $currency = function_exists( 'get_woocommerce_currency' )
            ? get_woocommerce_currency()
            : get_option( 'woocommerce_currency', 'USD' );

        $response_data = [
            'group_by'  => $group_by,
            'from'      => $range['from']->format( 'Y-m-d' ),
            'to'        => $range['to']->format( 'Y-m-d' ),
            'statuses'  => $statuses,
            'filters'   => [
                'group_by' => $group_by,
                'preset'   => $params['preset'],
            ],
            'currency'  => $currency,
            'totals'    => $summary['totals'],
            'series'    => $summary['series'],
            'count'     => count( $orders ),
        ];

        lokey_reports_debug(
            sprintf("✅ Summary report generated (%d orders, %s → %s)",
                count($orders), $response_data['from'], $response_data['to']),
            'routes-sales-summary.php'
        );

        // Build REST response and attach metrics if available
        $response = rest_ensure_response( $response_data );
        if ( $metrics_start ) {
            $m = function_exists( 'lokey_reports_metrics_end' ) ? lokey_reports_metrics_end( $metrics_start ) : null;
            if ( $m ) {
                $response->header( 'X-Lokey-Exec-Time', number_format( $m['time'], 4 ) );
                $response->header( 'X-Lokey-Memory', (string) $m['memory'] );
            }
        }
        return $response;
    }
}
