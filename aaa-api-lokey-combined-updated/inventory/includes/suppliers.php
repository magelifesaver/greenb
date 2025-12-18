<?php
/**
 * Suppliers endpoints.
 *
 * Provides CRUD operations for ATUM suppliers with unified response
 * formatting and error handling.  Requires a valid JWT token for all
 * operations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    /**
     * 🔹 GET /suppliers — list suppliers (with pagination and search)
     */
    register_rest_route( LOKEY_INV_API_NS, '/suppliers', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $filters = [];
            if ( $req->get_param( 'search' ) ) {
                $filters['search'] = sanitize_text_field( $req->get_param( 'search' ) );
            }
            $filters['page']     = lokey_inv_sanitize_int( $req['page'], 1, PHP_INT_MAX );
            $filters['per_page'] = lokey_inv_sanitize_int( $req['per_page'], 20, 100 );
            $query               = http_build_query( $filters );

            $res  = lokey_inv_request( "atum/suppliers?{$query}", 'GET' );
            $code = $res['code'] ?? 500;
            $body = $res['body'] ?? [];

            if ( $code >= 400 ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => $code,
                    'filters'   => $filters,
                    'message'   => 'Failed to retrieve suppliers.',
                    'data'      => $body,
                    'timestamp' => current_time( 'mysql' ),
                ], $code );
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'count'     => is_array( $body ) ? count( $body ) : 0,
                'filters'   => $filters,
                'data'      => $body,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        // Allow listing suppliers without requiring JWT.
        'permission_callback' => '__return_true',
        'args' => [
            'search'   => [ 'type' => 'string' ],
            'page'     => [ 'type' => 'integer', 'default' => 1 ],
            'per_page' => [ 'type' => 'integer', 'default' => 20 ],
        ],
    ] );

    /**
     * 🔹 POST /suppliers — create a new supplier
     */
    register_rest_route( LOKEY_INV_API_NS, '/suppliers', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {
            $body = $req->get_json_params();
            $res  = lokey_inv_request( 'atum/suppliers', 'POST', $body );
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $res['code'] < 400 ? 'success' : 'error',
                'code'      => $res['code'],
                'data'      => $res['body'],
                'timestamp' => current_time( 'mysql' ),
            ], $res['code'] );
        },
        // Allow creating a supplier without requiring JWT.
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 GET/PUT/DELETE /suppliers/{id} — single supplier operations
     */
    register_rest_route( LOKEY_INV_API_NS, '/suppliers/(?P<id>\d+)', [
        'methods'  => [ 'GET', 'PUT', 'DELETE' ],
        'callback' => function ( WP_REST_Request $req ) {
            $id     = absint( $req['id'] );
            $method = $req->get_method();
            $body   = $req->get_json_params();
            $path   = "atum/suppliers/{$id}";
            $res    = [];

            switch ( $method ) {
                case 'GET':
                    $res = lokey_inv_request( "{$path}?context=edit", 'GET' );
                    break;
                case 'PUT':
                    $res = lokey_inv_request( $path, 'PUT', $body );
                    break;
                case 'DELETE':
                    $res = lokey_inv_request( $path, 'DELETE' );
                    break;
            }
            $code    = $res['code'] ?? 500;
            $success = $code < 400;
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $success ? 'success' : 'error',
                'code'      => $code,
                'id'        => $id,
                'method'    => $method,
                'data'      => $res['body'] ?? [],
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow supplier CRUD operations without requiring JWT.
        'permission_callback' => '__return_true',
    ] );
} );
