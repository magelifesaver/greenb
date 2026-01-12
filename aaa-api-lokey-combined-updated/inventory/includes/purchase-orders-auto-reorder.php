<?php
/**
 * Auto-reorder: build (and optionally create) ATUM purchase orders from forecast qualifiers.
 *
 * Endpoint:
 * POST /lokey-inventory/v1/purchase-orders/auto-reorder
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function() {
    register_rest_route(
        LOKEY_INV_API_NS,
        '/purchase-orders/auto-reorder',
        [
            'methods'  => 'POST',
            'callback' => 'lokey_inv_auto_reorder_purchase_orders',
            'permission_callback' => 'lokey_inv_po_permission',
        ]
    );
} );

function lokey_inv_auto_reorder_purchase_orders( WP_REST_Request $request ) {
    if ( ! function_exists( 'lokey_inv_forecast_qualify_po' ) ) {
        return new WP_REST_Response(
            [
                'version' => LOKEY_INV_API_VERSION,
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Forecast qualification module not loaded.',
                'timestamp' => current_time( 'mysql' ),
            ],
            500
        );
    }

    $body = (array) $request->get_json_params();

    $interval    = isset( $body['interval'] ) ? sanitize_text_field( $body['interval'] ) : 'daily';
    $stock_below = isset( $body['stock_below'] ) ? absint( $body['stock_below'] ) : 5;
    $sales_status= isset( $body['sales_status'] ) ? sanitize_text_field( $body['sales_status'] ) : 'active';
    $limit       = isset( $body['limit'] ) ? absint( $body['limit'] ) : 100;
    $po_status   = isset( $body['po_status'] ) ? sanitize_text_field( $body['po_status'] ) : 'atum_pending';
    $date_expected = isset( $body['date_expected'] ) ? sanitize_text_field( $body['date_expected'] ) : '';
    $dry_run     = isset( $body['dry_run'] ) ? (bool) $body['dry_run'] : true;
    $confirm     = isset( $body['confirm'] ) ? (string) $body['confirm'] : '';
    $desc        = isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : '';

    if ( ! in_array( $interval, [ 'daily', 'monthly' ], true ) ) { $interval = 'daily'; }
    if ( $limit < 1 ) { $limit = 1; }
    if ( $limit > 300 ) { $limit = 300; }

    if ( ! $dry_run && $confirm !== 'CONFIRM' ) {
        return new WP_REST_Response(
            [
                'version' => LOKEY_INV_API_VERSION,
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Refusing to create purchase orders without confirm=CONFIRM. Run with dry_run=true first.',
                'timestamp' => current_time( 'mysql' ),
            ],
            400
        );
    }

    // Get candidates by calling the existing qualifier callback directly.
    $q = new WP_REST_Request( 'GET', '/' . LOKEY_INV_API_NS . '/forecast/qualify-po' );
    $q->set_query_params(
        array_filter( [
            'interval' => $interval,
            'stock_below' => $stock_below,
            'sales_status' => $sales_status,
            'limit' => $limit,
        ] )
    );

    $q_resp = lokey_inv_forecast_qualify_po( $q );
    if ( is_wp_error( $q_resp ) ) {
        return new WP_REST_Response(
            [
                'version' => LOKEY_INV_API_VERSION,
                'status'  => 'error',
                'code'    => 500,
                'message' => $q_resp->get_error_message(),
                'timestamp' => current_time( 'mysql' ),
            ],
            500
        );
    }

    $q_data = ( $q_resp instanceof WP_REST_Response ) ? $q_resp->get_data() : (array) $q_resp;
    $items  = isset( $q_data['qualified'] ) && is_array( $q_data['qualified'] ) ? $q_data['qualified'] : [];

    $groups = [];
    $warnings = [];
    foreach ( $items as $it ) {
        if ( ! is_array( $it ) ) { continue; }
        $supplier_id = isset( $it['supplier_id'] ) ? absint( $it['supplier_id'] ) : 0;
        if ( ! $supplier_id ) {
            $warnings[] = [
                'type' => 'missing_supplier',
                'product_id' => $it['id'] ?? null,
                'sku' => $it['sku'] ?? null,
                'message' => 'Product is missing _supplier_id; cannot auto-group into a purchase order.',
            ];
            continue;
        }
        if ( ! isset( $groups[ $supplier_id ] ) ) { $groups[ $supplier_id ] = []; }
        $groups[ $supplier_id ][] = $it;
    }

    $plan = [];
    $total_lines = 0;
    foreach ( $groups as $supplier_id => $rows ) {
        $line_items = [];
        foreach ( $rows as $r ) {
            $qty = isset( $r['suggested_order_qty'] ) ? absint( $r['suggested_order_qty'] ) : 0;
            if ( $qty < 1 ) { continue; }

            $li = [
                'product_id' => absint( $r['id'] ?? 0 ),
                'quantity'   => $qty,
            ];

            $pp = $r['purchase_price'] ?? null;
            if ( is_numeric( $pp ) ) {
                $li['purchase_price'] = floatval( $pp );
            } else {
                $warnings[] = [
                    'type' => 'missing_purchase_price',
                    'product_id' => $r['id'] ?? null,
                    'supplier_id' => $supplier_id,
                    'message' => 'Missing purchase price; ATUM may default cost to 0. Consider setting _purchase_price.',
                ];
            }

            $line_items[] = $li;
        }

        if ( empty( $line_items ) ) { continue; }

        $total_lines += count( $line_items );

        $plan[] = [
            'supplier_id' => (int) $supplier_id,
            'supplier_name' => isset( $rows[0]['supplier_name'] ) ? $rows[0]['supplier_name'] : null,
            'status' => $po_status,
            'date_expected' => $date_expected ?: null,
            'description' => $desc ?: null,
            'line_items' => $line_items,
        ];
    }

    $out = [
        'version' => LOKEY_INV_API_VERSION,
        'status'  => 'success',
        'code'    => 200,
        'dry_run' => $dry_run,
        'criteria'=> [
            'interval' => $interval,
            'stock_below' => $stock_below,
            'sales_status' => $sales_status,
            'limit' => $limit,
            'po_status' => $po_status,
        ],
        'count_suppliers' => count( $plan ),
        'count_line_items' => $total_lines,
        'warnings' => $warnings,
        'purchase_orders' => $plan,
        'timestamp' => current_time( 'mysql' ),
    ];

    if ( $dry_run ) {
        return new WP_REST_Response( $out, 200 );
    }

    // Commit: create POs in ATUM.
    if ( ! function_exists( 'lokey_inv_po_sanitize_payload' ) || ! function_exists( 'lokey_inv_request' ) ) {
        $out['status'] = 'error';
        $out['code'] = 500;
        $out['message'] = 'Purchase order library not loaded.';
        return new WP_REST_Response( $out, 500 );
    }

    $created = [];
    foreach ( $plan as $po ) {
        $payload = [
            'supplier' => absint( $po['supplier_id'] ),
            'status' => $po_status,
            'date_expected' => $date_expected ?: null,
            'description' => $desc ?: null,
            'line_items' => $po['line_items'],
        ];

        $san = lokey_inv_po_sanitize_payload( $payload );
        if ( ! empty( $san['errors'] ) ) {
            $created[] = [
                'ok' => false,
                'supplier_id' => $po['supplier_id'],
                'errors' => $san['errors'],
            ];
            continue;
        }

        $resp = lokey_inv_request( 'purchase-orders', 'POST', $san['data'] );
        $created[] = [
            'ok' => ( ( $resp['code'] ?? 500 ) >= 200 && ( $resp['code'] ?? 500 ) < 300 ),
            'supplier_id' => $po['supplier_id'],
            'code' => $resp['code'] ?? 500,
            'body' => $resp['body'] ?? null,
        ];
    }

    $out['code'] = 201;
    $out['created'] = $created;

    return new WP_REST_Response( $out, 201 );
}
