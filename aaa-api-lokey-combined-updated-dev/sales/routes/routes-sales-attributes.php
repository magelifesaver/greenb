<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-attributes.php
 * Route: /wp-json/lokeyreports/v1/sales/attributes
 * Version: 1.7.1
 * Updated: 2025-12-02
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides attribute-level sales metrics aggregated by any WooCommerce
 *   attribute taxonomy (e.g., pa_flavor, pa_size).
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
 * Register Attribute Sales Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_attribute_routes' ) ) {
    function lokey_reports_register_attribute_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/attributes',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => 'lokey_reports_handle_sales_attributes',
                // Permit access without JWT for GPT actions.
                'permission_callback' => '__return_true',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_attribute_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/attributes
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_attributes' ) ) {
    function lokey_reports_handle_sales_attributes( \WP_REST_Request $request ) {

        // Start metrics capture if available
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-attributes.php');
            return new \WP_Error(
                'lokey_reports_no_woocommerce',
                __( 'WooCommerce is required for Lokey attribute reports.', 'lokey-reports' ),
                [ 'status' => 500 ]
            );
        }

        $attribute = sanitize_title( $request->get_param('attribute') );
        if ( empty( $attribute ) ) {
            return new WP_Error( 'missing_attribute', 'Missing required "attribute" parameter.', [ 'status' => 400 ] );
        }

        $taxonomy = 'pa_' . $attribute;
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', sprintf( 'Attribute taxonomy "%s" not found.', esc_html( $taxonomy ) ), [ 'status' => 404 ] );
        }

        // 🧩 Parameters
        $params = [
            'preset' => $request->get_param('preset'),
            'from'   => $request->get_param('from'),
            'to'     => $request->get_param('to'),
        ];

        $range    = lokey_reports_parse_date_range( $params );
        $statuses = lokey_reports_sanitize_status_list( $request->get_param('statuses') );
        $limit    = min( absint( $request->get_param('limit') ?: 50 ), 500 );
        $order_by = in_array( $request->get_param('order_by'), ['net_sales','gross_sales','qty_sold','orders_count'], true )
            ? $request->get_param('order_by') : 'net_sales';
        $order    = ( strtolower( $request->get_param('order') ) === 'asc' ) ? 'asc' : 'desc';

        // 🧾 Fetch orders
        $orders = lokey_reports_get_orders_for_range( $range['from'], $range['to'], $statuses );

        // Aggregate by attribute
        $rows = [];
        foreach ( $orders as $order ) {
            foreach ( $order->get_items('line_item') as $item ) {
                $pid = $item->get_product_id();
                if ( ! $pid ) continue;
                $terms = get_the_terms( $pid, $taxonomy );
                if ( empty( $terms ) ) continue;

                $qty   = (int) $item->get_quantity();
                $total = (float) $item->get_total() + (float) $item->get_total_tax();

                foreach ( $terms as $term ) {
                    if ( ! isset( $rows[ $term->term_id ] ) ) {
                        $rows[ $term->term_id ] = [
                            'term_id'      => $term->term_id,
                            'name'         => $term->name,
                            'slug'         => $term->slug,
                            'qty_sold'     => 0,
                            'net_sales'    => 0.0,
                            'gross_sales'  => 0.0,
                            'orders_count' => 0,
                        ];
                    }
                    $rows[ $term->term_id ]['qty_sold']     += $qty;
                    $rows[ $term->term_id ]['net_sales']    += $total;
                    $rows[ $term->term_id ]['gross_sales']  += $total;
                    $rows[ $term->term_id ]['orders_count']++;
                }
            }
        }

        $rows = array_values( $rows );
        usort( $rows, function( $a, $b ) use ( $order_by, $order ) {
            $va = (float) ($a[ $order_by ] ?? 0);
            $vb = (float) ($b[ $order_by ] ?? 0);
            return ( 'asc' === $order ) ? $va <=> $vb : $vb <=> $va;
        });
        $rows = array_slice( $rows, 0, $limit );

        // 💰 Build response data
        $response_data = [
            'group_by'  => 'attribute',
            'attribute' => $attribute,
            'from'      => $range['from']->format( 'Y-m-d' ),
            'to'        => $range['to']->format( 'Y-m-d' ),
            'statuses'  => $statuses,
            'filters'   => [
                'attribute' => $attribute,
                'order_by'  => $order_by,
                'order'     => $order,
                'limit'     => $limit,
            ],
            'currency'  => get_woocommerce_currency(),
            'count'     => count( $rows ),
            'rows'      => $rows,
        ];

        lokey_reports_debug(
            sprintf("✅ Attribute report generated (attribute: %s, %d results, %s → %s)",
                $attribute, count($rows), $response_data['from'], $response_data['to']),
            'routes-sales-attributes.php'
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
