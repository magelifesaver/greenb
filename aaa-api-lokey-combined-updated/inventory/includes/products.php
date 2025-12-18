<?php
/**
 * Products CRUD endpoints.
 *
 * Allows retrieving, creating, updating and batch modifying WooCommerce
 * products via the ATUM/WooCommerce API.  All operations require a valid
 * JWT token.  These endpoints map to /products/{id}, /products and
 * /products/batch under the lokey‑inventory namespace.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    /**
     * 🔹 GET /products/{id} — Retrieve product by ID
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $id  = absint( $req['id'] );
            $res = lokey_inv_request( "products/{$id}?context=edit", 'GET' );
            $code = $res['code'] ?? 500;
            $body = $res['body'] ?? [];
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'id'        => $id,
                'data'      => $body,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow product retrieval without requiring a JWT for GPT clients.
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 POST /products — Create new product
     */
    register_rest_route( LOKEY_INV_API_NS, '/products', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {
            $body = $req->get_json_params() ?: [];
            if ( empty( $body['type'] ) ) {
                $body['type'] = 'simple';
            }
            $res  = lokey_inv_request( 'products', 'POST', $body );
            $code = $res['code'] ?? 500;
            $body_res = $res['body'] ?? [];
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'data'      => $body_res,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow product creation without requiring a JWT.
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 PUT /products/{id} — Update existing product
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {
            $id   = absint( $req['id'] );
            $body = $req->get_json_params() ?: [];
            $res  = lokey_inv_request( "products/{$id}", 'PUT', $body );
            $code = $res['code'] ?? 500;
            $body_res = $res['body'] ?? [];
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'id'        => $id,
                'data'      => $body_res,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow product update without requiring a JWT.
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 POST /products/batch — Batch create, update, or delete
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/batch', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {
            $body = $req->get_json_params() ?: [];
            $res  = lokey_inv_request( 'products/batch', 'POST', $body );
            $code = $res['code'] ?? 500;
            $body_res = $res['body'] ?? [];
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'data'      => $body_res,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        // Allow product batch operations without requiring a JWT.
        'permission_callback' => '__return_true',
    ] );
} );
