<?php
/**
 * WooCommerce attribute terms (proxy).
 *
 * Routes:
 *   - GET  /lokey-inventory/v1/attributes/{id}/terms
 *   - POST /lokey-inventory/v1/attributes/{id}/terms
 *   - POST /lokey-inventory/v1/attributes/{id}/terms/batch
 *   - GET  /lokey-inventory/v1/attributes/{id}/terms/{term_id}
 *   - PUT  /lokey-inventory/v1/attributes/{id}/terms/{term_id}
 *   - DELETE /lokey-inventory/v1/attributes/{id}/terms/{term_id}
 *
 * Uses Woo endpoints:
 *   /wc/v3/products/attributes/{id}/terms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/attributes/(?P<id>\d+)/terms', [
        [
            'methods'  => 'GET',
            'callback' => function ( WP_REST_Request $req ) {

                $id = absint( $req['id'] );
                if ( $id <= 0 ) {
                    return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid attribute ID.' ], 400 );
                }

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

                $res = lokey_inv_request( "products/attributes/{$id}/terms?" . http_build_query( $query ), 'GET' );
                $code = $res['code'] ?? 500;
                $data = is_array( $res['body'] ?? null ) ? $res['body'] : [];

                if ( $code >= 400 ) {
                    return new WP_REST_Response( [
                        'version'   => LOKEY_INV_API_VERSION,
                        'status'    => 'error',
                        'code'      => $code,
                        'message'   => 'Failed to list attribute terms.',
                        'data'      => $res['body'] ?? null,
                        'timestamp' => current_time( 'mysql' ),
                    ], $code );
                }

                if ( $lite ) {
                    $rows = [];
                    foreach ( $data as $t ) {
                        $rows[] = [
                            'id'   => isset( $t['id'] ) ? (int) $t['id'] : 0,
                            'name' => $t['name'] ?? '',
                            'slug' => $t['slug'] ?? '',
                        ];
                    }
                    $data = $rows;
                }

                return new WP_REST_Response( [
                    'version'      => LOKEY_INV_API_VERSION,
                    'status'       => 'success',
                    'attribute_id' => $id,
                    'page'         => $page,
                    'per_page'     => $per_page,
                    'search'       => $search,
                    'count'        => count( $data ),
                    'lite'         => (bool) $lite,
                    'rows'         => $data,
                    'timestamp'    => current_time( 'mysql' ),
                ], 200 );
            },
            'permission_callback' => '__return_true',
        ],
        [
            'methods'  => 'POST',
            'callback' => function ( WP_REST_Request $req ) {

                $id = absint( $req['id'] );
                if ( $id <= 0 ) {
                    return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid attribute ID.' ], 400 );
                }

                $body = $req->get_json_params() ?: [];

                $res  = lokey_inv_request( "products/attributes/{$id}/terms", 'POST', $body );
                $code = $res['code'] ?? 500;

                if ( $code >= 400 ) {
                    return new WP_REST_Response( [
                        'version'   => LOKEY_INV_API_VERSION,
                        'status'    => 'error',
                        'code'      => $code,
                        'message'   => 'Attribute term creation failed.',
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

    register_rest_route( LOKEY_INV_API_NS, '/attributes/(?P<id>\d+)/terms/(?P<term_id>\d+)', [
        'methods'  => [ 'GET', 'PUT', 'DELETE' ],
        'callback' => function ( WP_REST_Request $req ) {

            $id      = absint( $req['id'] );
            $term_id = absint( $req['term_id'] );

            if ( $id <= 0 || $term_id <= 0 ) {
                return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid attribute/term ID.' ], 400 );
            }

            $method = $req->get_method();
            $path   = "products/attributes/{$id}/terms/{$term_id}";

            $payload = null;
            if ( 'PUT' === $method ) {
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
                    'message'   => 'Attribute term request failed.',
                    'data'      => $res['body'] ?? null,
                    'timestamp' => current_time( 'mysql' ),
                ], $code );
            }

            return new WP_REST_Response( [
                'version'      => LOKEY_INV_API_VERSION,
                'status'       => 'success',
                'method'       => $method,
                'attribute_id' => $id,
                'term_id'      => $term_id,
                'data'         => $res['body'] ?? null,
                'timestamp'    => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'force' => [ 'type' => 'boolean' ],
        ],
    ] );

} );
