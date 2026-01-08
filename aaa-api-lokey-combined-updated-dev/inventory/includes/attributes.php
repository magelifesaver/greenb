<?php
/**
 * WooCommerce global attributes (proxy).
 *
 * Routes:
 *   - GET  /lokey-inventory/v1/attributes
 *   - POST /lokey-inventory/v1/attributes
 *   - GET  /lokey-inventory/v1/attributes/{id}
 *   - PUT  /lokey-inventory/v1/attributes/{id}
 *   - DELETE /lokey-inventory/v1/attributes/{id}
 *
 * Uses lokey_inv_request() to call Woo endpoints:
 *   /wc/v3/products/attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/attributes', [
        [
            'methods'  => 'GET',
            'callback' => function ( WP_REST_Request $req ) {

                $page     = max( 1, absint( $req->get_param( 'page' ) ?: 1 ) );
                $per_page = absint( $req->get_param( 'per_page' ) ?: 50 );
                $per_page = min( max( 1, $per_page ), 100 );
                $search   = $req->get_param( 'search' );
                $search   = is_string( $search ) ? sanitize_text_field( $search ) : '';
                $lite     = absint( $req->get_param( 'lite' ) ?: 1 );

                $query = [
                    'page'     => $page,
                    'per_page' => $per_page,
                ];
                if ( $search !== '' ) {
                    $query['search'] = $search;
                }

                $res = lokey_inv_request( 'products/attributes?' . http_build_query( $query ), 'GET' );
                $code = $res['code'] ?? 500;
                $data = is_array( $res['body'] ?? null ) ? $res['body'] : [];

                if ( $code >= 400 ) {
                    return new WP_REST_Response( [
                        'version'   => LOKEY_INV_API_VERSION,
                        'status'    => 'error',
                        'code'      => $code,
                        'message'   => 'Failed to list attributes.',
                        'data'      => $res['body'] ?? null,
                        'timestamp' => current_time( 'mysql' ),
                    ], $code );
                }

                if ( $lite ) {
                    $rows = [];
                    foreach ( $data as $a ) {
                        $rows[] = [
                            'id'           => isset( $a['id'] ) ? (int) $a['id'] : 0,
                            'name'         => $a['name'] ?? '',
                            'slug'         => $a['slug'] ?? '',
                            'type'         => $a['type'] ?? '',
                            'order_by'     => $a['order_by'] ?? '',
                            'has_archives' => isset( $a['has_archives'] ) ? (bool) $a['has_archives'] : false,
                        ];
                    }
                    $data = $rows;
                }

                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'success',
                    'page'      => $page,
                    'per_page'  => $per_page,
                    'search'    => $search,
                    'count'     => count( $data ),
                    'lite'      => (bool) $lite,
                    'rows'      => $data,
                    'timestamp' => current_time( 'mysql' ),
                ], 200 );
            },
            'permission_callback' => '__return_true',
        ],
        [
            'methods'  => 'POST',
            'callback' => function ( WP_REST_Request $req ) {
                $body = $req->get_json_params() ?: [];

                $res  = lokey_inv_request( 'products/attributes', 'POST', $body );
                $code = $res['code'] ?? 500;

                if ( $code >= 400 ) {
                    return new WP_REST_Response( [
                        'version'   => LOKEY_INV_API_VERSION,
                        'status'    => 'error',
                        'code'      => $code,
                        'message'   => 'Attribute creation failed.',
                        'data'      => $res['body'] ?? null,
                        'timestamp' => current_time( 'mysql' ),
                    ], $code );
                }

                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'success',
                    'action'    => 'created',
                    'data'      => $res['body'] ?? null,
                    'timestamp' => current_time( 'mysql' ),
                ], 200 );
            },
            'permission_callback' => '__return_true',
        ],
    ] );

    register_rest_route( LOKEY_INV_API_NS, '/attributes/(?P<id>\d+)', [
        'methods'  => [ 'GET', 'PUT', 'DELETE' ],
        'callback' => function ( WP_REST_Request $req ) {

            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid attribute ID.' ], 400 );
            }

            $method = $req->get_method();
            $path   = "products/attributes/{$id}";

            $payload = null;
            if ( in_array( $method, [ 'PUT' ], true ) ) {
                $payload = $req->get_json_params() ?: [];
            } elseif ( 'DELETE' === $method ) {
                $force = $req->get_param( 'force' );
                if ( ! is_null( $force ) ) {
                    $path .= '?' . http_build_query( [ 'force' => (bool) $force ] );
                }
            }

            $res  = lokey_inv_request( $path, $method, $payload );
            $code = $res['code'] ?? 500;

            if ( $code >= 400 ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => $code,
                    'message'   => 'Attribute request failed.',
                    'data'      => $res['body'] ?? null,
                    'timestamp' => current_time( 'mysql' ),
                ], $code );
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'method'    => $method,
                'id'        => $id,
                'data'      => $res['body'] ?? null,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'force' => [ 'type' => 'boolean' ],
        ],
    ] );

} );
