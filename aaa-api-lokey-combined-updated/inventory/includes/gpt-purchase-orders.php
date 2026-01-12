<?php
/**
 * GPT Purchase Orders endpoints.
 *
 * These routes mirror the Lokey Inventory Purchase Order routes but expose
 * raw ATUM responses (no envelope) for clients that expect a direct object/array.
 *
 * Namespace: /wp-json/gpt/v1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/purchase-orders-lib.php';

add_action( 'rest_api_init', function () {

    register_rest_route( 'gpt/v1', '/purchase-orders/properties', [
        'methods'             => 'GET',
        'callback'            => function () {
            return new WP_REST_Response( [
                'allowed_statuses' => lokey_inv_po_allowed_statuses(),
                'default_status'   => lokey_inv_po_default_status(),
            ], 200 );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders', [
        'methods'             => 'GET',
        'callback'            => 'lokey_gpt_po_list',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders', [
        'methods'             => 'POST',
        'callback'            => 'lokey_gpt_po_create',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'lokey_gpt_po_get',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders/(?P<id>\d+)', [
        'methods'             => 'PUT',
        'callback'            => 'lokey_gpt_po_update',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'lokey_gpt_po_delete',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( 'gpt/v1', '/purchase-orders/batch', [
        'methods'             => 'PUT',
        'callback'            => 'lokey_gpt_po_batch',
        'permission_callback' => 'lokey_inv_po_permission',
    ] );
} );

function lokey_gpt_po_list( WP_REST_Request $req ) {

    $filters = [
        'page'     => lokey_inv_sanitize_int( $req['page'], 1, PHP_INT_MAX ),
        'per_page' => lokey_inv_sanitize_int( $req['per_page'], 20, 200 ),
    ];

    if ( $req->get_param( 'supplier' ) ) {
        $filters['supplier'] = absint( $req->get_param( 'supplier' ) );
    }

    if ( $req->get_param( 'status' ) ) {
        $st = lokey_inv_po_normalize_status( $req->get_param( 'status' ) );
        if ( ! in_array( $st, lokey_inv_po_allowed_statuses(), true ) ) {
            return new WP_REST_Response( [
                'error'          => 'Invalid status filter.',
                'valid_statuses' => lokey_inv_po_allowed_statuses(),
            ], 400 );
        }
        $filters['status'] = $st;
    }

    foreach ( [ 'date_after', 'date_before' ] as $k ) {
        $v = $req->get_param( $k );
        if ( ! $v ) {
            continue;
        }
        $ts = strtotime( sanitize_text_field( (string) $v ) );
        if ( ! $ts ) {
            return new WP_REST_Response( [ 'error' => "{$k} must be a valid date." ], 400 );
        }
        $filters[ $k ] = gmdate( 'Y-m-d', $ts );
    }

    if ( empty( $filters['date_after'] ) && empty( $filters['date_before'] ) && empty( $filters['status'] ) && empty( $filters['supplier'] ) ) {
        $filters['date_after'] = gmdate( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
    }

    $res  = lokey_inv_request( 'atum/purchase-orders?' . http_build_query( $filters ), 'GET' );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 200 : $code );
}

function lokey_gpt_po_create( WP_REST_Request $req ) {

    $warnings = [];
    $payload  = lokey_inv_po_sanitize_payload( $req->get_json_params(), $warnings, 'create' );
    if ( is_wp_error( $payload ) ) {
        $data = $payload->get_error_data();
        $http = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
        return new WP_REST_Response( [ 'error' => $payload->get_error_message(), 'data' => $data ], $http );
    }

    $res  = lokey_inv_request( 'atum/purchase-orders', 'POST', $payload );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 201 : $code );
}

function lokey_gpt_po_get( WP_REST_Request $req ) {

    $id = absint( $req['id'] );
    if ( $id <= 0 ) {
        return new WP_REST_Response( [ 'error' => 'Invalid purchase order ID.' ], 400 );
    }

    $res  = lokey_inv_request( "atum/purchase-orders/{$id}?context=edit", 'GET' );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 200 : $code );
}

function lokey_gpt_po_update( WP_REST_Request $req ) {

    $id       = absint( $req['id'] );
    $incoming = $req->get_json_params() ?: [];

    if ( $id <= 0 ) {
        return new WP_REST_Response( [ 'error' => 'Invalid purchase order ID.' ], 400 );
    }

    // Fetch existing PO for merge context.
    $existing_res  = lokey_inv_request( "atum/purchase-orders/{$id}?context=edit", 'GET' );
    $existing_code = $existing_res['code'] ?? 500;
    if ( $existing_code >= 400 ) {
        return new WP_REST_Response( $existing_res['body'] ?? [ 'error' => 'Unable to fetch purchase order.' ], $existing_code );
    }

    $existing_po    = is_array( $existing_res['body'] ?? null ) ? $existing_res['body'] : [];
    $existing_lines = is_array( $existing_po['line_items'] ?? null ) ? $existing_po['line_items'] : [];

    $warnings = [];
    $clean    = lokey_inv_po_sanitize_payload( $incoming, $warnings, 'update' );
    if ( is_wp_error( $clean ) ) {
        $data = $clean->get_error_data();
        $http = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
        return new WP_REST_Response( [ 'error' => $clean->get_error_message(), 'data' => $data ], $http );
    }

    // Merge line_items when provided.
    if ( array_key_exists( 'line_items', $clean ) ) {
        $new_lines = is_array( $clean['line_items'] ) ? $clean['line_items'] : [];
        if ( empty( $new_lines ) ) {
            return new WP_REST_Response( [ 'error' => 'line_items cannot be empty when provided.' ], 400 );
        }

        foreach ( $new_lines as $new_line ) {
            $product_id   = absint( $new_line['product_id'] ?? 0 );
            $variation_id = absint( $new_line['variation_id'] ?? 0 );
            $quantity     = absint( $new_line['quantity'] ?? 0 );
            if ( $product_id <= 0 || $quantity <= 0 ) {
                continue;
            }

            $purchase_price = isset( $new_line['purchase_price'] ) ? (float) $new_line['purchase_price'] : null;

            $merged = false;
            foreach ( $existing_lines as &$line ) {
                $existing_pid   = (int) ( $line['product_id'] ?? 0 );
                $existing_varid = (int) ( $line['variation_id'] ?? 0 );
                if ( $existing_pid === $product_id && $existing_varid === $variation_id ) {
                    $line['quantity'] = isset( $line['quantity'] ) ? (int) $line['quantity'] + $quantity : $quantity;
                    if ( null !== $purchase_price ) {
                        $line['purchase_price'] = $purchase_price;
                    }
                    $merged = true;
                    break;
                }
            }
            unset( $line );

            if ( ! $merged ) {
                $existing_lines[] = $new_line;
            }
        }

        $clean['line_items'] = array_values( $existing_lines );
    }

    $res  = lokey_inv_request( "atum/purchase-orders/{$id}", 'PUT', $clean );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 200 : $code );
}

function lokey_gpt_po_delete( WP_REST_Request $req ) {

    $id = absint( $req['id'] );
    if ( $id <= 0 ) {
        return new WP_REST_Response( [ 'error' => 'Invalid purchase order ID.' ], 400 );
    }

    $res  = lokey_inv_request( "atum/purchase-orders/{$id}", 'DELETE' );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 200 : $code );
}

function lokey_gpt_po_batch( WP_REST_Request $req ) {

    $res  = lokey_inv_request( 'atum/purchase-orders/batch', 'PUT', $req->get_json_params() );
    $code = $res['code'] ?? 500;

    return new WP_REST_Response( $res['body'] ?? null, $code < 400 ? 200 : $code );
}
