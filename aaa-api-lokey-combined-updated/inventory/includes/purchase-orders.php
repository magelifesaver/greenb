<?php
/* Purchase Orders endpoints: list, properties, create, get, delete, batch. */

if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/purchase-orders-lib.php';

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/properties', [
        'methods'  => 'GET',
        'callback' => fn() => new WP_REST_Response( [
            'version' => LOKEY_INV_API_VERSION,
            'status'  => 'success',
            'data'    => [
                'allowed_statuses' => lokey_inv_po_allowed_statuses(),
                'default_status'   => lokey_inv_po_default_status(),
                'create_allowed_fields' => [ 'status','currency','supplier','multiple_suppliers','date_expected','line_items','shipping_lines','fee_lines','meta_data','description' ],
                'read_only_fields' => [ 'id','date_created','date_created_gmt','date_modified','date_modified_gmt','discount_total','discount_tax','shipping_total','shipping_tax','cart_tax','total','total_tax','prices_include_tax','tax_lines','date_completed','date_completed_gmt','date_expected_gmt' ],
            ],
            'timestamp' => current_time( 'mysql' ),
        ], 200 ),
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $filters = [
                'page'     => lokey_inv_sanitize_int( $req['page'], 1, PHP_INT_MAX ),
                'per_page' => lokey_inv_sanitize_int( $req['per_page'], 20, 200 ),
            ];
            if ( $req->get_param( 'supplier' ) ) { $filters['supplier'] = absint( $req->get_param( 'supplier' ) ); }
            if ( $req->get_param( 'status' ) ) {
                $st = lokey_inv_po_normalize_status( $req->get_param( 'status' ) );
                if ( ! in_array( $st, lokey_inv_po_allowed_statuses(), true ) ) {
                    return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => 'error', 'code' => 400, 'message' => 'Invalid status filter.', 'valid_statuses' => lokey_inv_po_allowed_statuses() ], 400 );
                }
                $filters['status'] = $st;
            }
            foreach ( [ 'date_after', 'date_before' ] as $k ) {
                $v = $req->get_param( $k );
                if ( $v ) {
                    $ts = strtotime( sanitize_text_field( $v ) );
                    if ( ! $ts ) { return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => 'error', 'code' => 400, 'message' => "$k must be a valid date." ], 400 ); }
                    $filters[ $k ] = gmdate( 'Y-m-d', $ts );
                }
            }
            if ( empty( $filters['date_after'] ) && empty( $filters['date_before'] ) && empty( $filters['status'] ) && empty( $filters['supplier'] ) ) {
                $filters['date_after'] = gmdate( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
            }

            $res  = lokey_inv_request( 'atum/purchase-orders?' . http_build_query( $filters ), 'GET' );
            $code = $res['code'] ?? 500;
            $body = $res['body'] ?? [];
            $ok   = $code < 400;
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $ok ? 'success' : 'error',
                'code'      => $code,
                'filters'   => $filters,
                'count'     => $ok && is_array( $body ) ? count( $body ) : 0,
                'message'   => $ok ? null : 'Failed to retrieve purchase orders',
                'data'      => $body,
                'timestamp' => current_time( 'mysql' ),
            ], $ok ? 200 : $code );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {
            $warnings = [];
            $payload  = lokey_inv_po_sanitize_payload( $req->get_json_params(), $warnings, 'create' );
            if ( is_wp_error( $payload ) ) {
                $data = $payload->get_error_data();
                $http = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
                return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => 'error', 'code' => $http, 'message' => $payload->get_error_message(), 'data' => $data ], $http );
            }
            $res  = lokey_inv_request( 'atum/purchase-orders', 'POST', $payload );
            $code = $res['code'] ?? 500;
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'warnings'  => $warnings,
                'data'      => $res['body'] ?? null,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $id   = absint( $req['id'] );
            $res  = lokey_inv_request( "atum/purchase-orders/{$id}?context=edit", 'GET' );
            $code = $res['code'] ?? 500;
            $ok   = $code < 400;
            return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => $ok ? 'success' : 'error', 'code' => $code, 'id' => $id, 'message' => $ok ? null : 'Purchase order not found', 'data' => $res['body'] ?? null ], $ok ? 200 : $code );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => function ( WP_REST_Request $req ) {
            $id = absint( $req['id'] );
            if ( $id <= 0 ) { return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => 'error', 'code' => 400, 'message' => 'Invalid purchase order ID.', 'id' => $id ], 400 ); }
            $res  = lokey_inv_request( "atum/purchase-orders/{$id}", 'DELETE' );
            $code = $res['code'] ?? 500;
            return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => $code < 400 ? 'success' : 'error', 'code' => $code, 'id' => $id, 'data' => $res['body'] ?? null, 'timestamp' => current_time( 'mysql' ) ], $code );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/batch', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {
            $res  = lokey_inv_request( 'atum/purchase-orders/batch', 'PUT', $req->get_json_params() );
            $code = $res['code'] ?? 500;
            return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => $code < 400 ? 'success' : 'error', 'code' => $code, 'data' => $res['body'] ?? null, 'timestamp' => current_time( 'mysql' ) ], $code );
        },
        'permission_callback' => 'lokey_inv_po_permission',
    ] );
} );
