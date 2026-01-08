<?php
/*
 * Purchase Orders endpoints: list, create, get, delete and batch update.  Full
 * CRUD operations on ATUM purchase orders via the WooCommerce REST API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    // GET /purchase-orders — list all POs
    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            // Build filter array and run remote request.
            $filters = [];
            foreach ( [ 'status','supplier','date_after','date_before' ] as $key ) {
                if ( $req->get_param( $key ) ) {
                    $filters[ $key ] = sanitize_text_field( $req->get_param( $key ) );
                }
            }
            $filters['page']     = lokey_inv_sanitize_int( $req['page'], 1, PHP_INT_MAX );
            $filters['per_page'] = lokey_inv_sanitize_int( $req['per_page'], 20, 200 );

            // If no date or supplier/status filters are provided, limit results to the
            // last 30 days to prevent oversized responses from ATUM.  Convert to
            // Y-m-d format for the API.
            if ( empty( $filters['date_after'] ) && empty( $filters['date_before'] ) && empty( $filters['status'] ) && empty( $filters['supplier'] ) ) {
                $filters['date_after'] = gmdate( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
            }
            $res  = lokey_inv_request( 'atum/purchase-orders?' . http_build_query( $filters ), 'GET' );
            $code = $res['code'] ?? 500;
            $body = $res['body'] ?? [];
            $success = $code < 400;
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $success ? 'success' : 'error',
                'code'      => $code,
                'filters'   => $filters,
                'count'     => $success && is_array( $body ) ? count( $body ) : 0,
                'message'   => $success ? null : 'Failed to retrieve purchase orders',
                'data'      => $body,
                'timestamp' => current_time( 'mysql' ),
            ], $success ? 200 : $code );
        },
        // Allow listing of purchase orders without requiring JWT for GPT.
        'permission_callback' => '__return_true',
        'args' => [
            'page'     => [ 'type' => 'integer', 'default' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 20 ],
            'status'   => [ 'type' => 'string' ],
            'supplier' => [ 'type' => 'string' ],
            'date_after'=> [ 'type' => 'string' ],
            'date_before'=> [ 'type' => 'string' ],
        ],
    ] );

// POST /purchase-orders — create new PO
register_rest_route( LOKEY_INV_API_NS, '/purchase-orders', [
    'methods'  => 'POST',
    'callback' => function ( WP_REST_Request $req ) {

        // Retrieve and sanitize incoming payload
        $payload = $req->get_json_params();

        // --- 🔁 Compatibility shim: items → line_items ---
        // Support legacy "items" field by remapping to ATUM's "line_items"
        if ( ! empty( $payload['items'] ) && empty( $payload['line_items'] ) ) {
            $payload['line_items'] = $payload['items'];
            unset( $payload['items'] );
        }

        // --- 🔁 Supplier normalization ---
        // Convert supplier_id → supplier (ATUM expects "supplier")
        if ( ! empty( $payload['supplier_id'] ) && empty( $payload['supplier'] ) ) {
            $payload['supplier'] = intval( $payload['supplier_id'] );
            unset( $payload['supplier_id'] );
        }

        // --- ⏰ Date normalization ---
        // Normalize "date_expected" to ISO 8601 if provided
        if ( ! empty( $payload['date_expected'] ) ) {
            // If date provided without time, append T00:00:00
            if ( ! preg_match( '/T\d{2}:\d{2}:\d{2}/', $payload['date_expected'] ) ) {
                $payload['date_expected'] = $payload['date_expected'] . 'T00:00:00';
            }
        }

        // --- 🧩 Fallback validation ---
        // Ensure required minimum keys exist
        if ( empty( $payload['status'] ) ) {
            $payload['status'] = 'atum_pending';
        }

        // Forward normalized payload to ATUM
        $res = lokey_inv_request( 'atum/purchase-orders', 'POST', $payload );

        // Build response
        return new WP_REST_Response( [
            'version'   => LOKEY_INV_API_VERSION,
            'status'    => $res['code'] < 400 ? 'success' : 'error',
            'code'      => $res['code'],
            'data'      => $res['body'],
            'timestamp' => current_time( 'mysql' ),
        ], $res['code'] );
    },

    // Allow creating purchase orders without requiring JWT.
    'permission_callback' => '__return_true',
] );

    // GET /purchase-orders/{id} — retrieve by ID
    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $id   = absint( $req['id'] );
            $res  = lokey_inv_request( "atum/purchase-orders/{$id}?context=edit", 'GET' );
            $code = $res['code'];
            $success = $code < 400;
            return new WP_REST_Response( [
                'version' => LOKEY_INV_API_VERSION,
                'status'  => $success ? 'success' : 'error',
                'code'    => $code,
                'id'      => $id,
                'message' => $success ? null : 'Purchase order not found',
                'data'    => $res['body'],
            ], $success ? 200 : $code );
        },
        // Allow retrieving a purchase order by ID without JWT.
        'permission_callback' => '__return_true',
    ] );

    // DELETE /purchase-orders/{id} — delete PO by ID
    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => function ( WP_REST_Request $req ) {
            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return new WP_REST_Response( [ 'version' => LOKEY_INV_API_VERSION, 'status' => 'error', 'code' => 400, 'message' => 'Invalid purchase order ID.', 'id' => $id ], 400 );
            }
            $res  = lokey_inv_request( "atum/purchase-orders/{$id}", 'DELETE' );
            $code = $res['code'] ?? 500;
            $success = $code < 400;
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $success ? 'success' : 'error',
                'code'      => $code,
                'id'        => $id,
                'data'      => $res['body'],
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow deleting purchase orders without requiring JWT.
        'permission_callback' => '__return_true',
    ] );

    // PUT /purchase-orders/batch — bulk update
    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/batch', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {
            $res = lokey_inv_request( 'atum/purchase-orders/batch', 'PUT', $req->get_json_params() );
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $res['code'] < 400 ? 'success' : 'error',
                'code'      => $res['code'],
                'data'      => $res['body'],
                'timestamp' => current_time( 'mysql' ),
            ], $res['code'] );
        },
        // Allow batch update of purchase orders without requiring JWT.
        'permission_callback' => '__return_true',
    ] );
} );
