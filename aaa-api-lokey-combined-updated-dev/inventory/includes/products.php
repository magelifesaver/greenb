<?php
/**
 * Products CRUD endpoints.
 *
 * Allows retrieving, creating, updating and batch modifying WooCommerce
 * products via the ATUM/WooCommerce API.
 *
 * Note: GET /products/{id} supports ?lite=1 to reduce payload size.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lokey_inv_product_lite_view' ) ) {
    /**
     * Produce a lightweight product representation from a Woo REST product object/array.
     *
     * @param array $p Full product array from Woo REST.
     * @return array
     */
    function lokey_inv_product_lite_view( array $p ) {
        $categories = [];
        foreach ( $p['categories'] ?? [] as $c ) {
            if ( isset( $c['id'] ) ) {
                $categories[] = [ 'id' => (int) $c['id'], 'name' => $c['name'] ?? '', 'slug' => $c['slug'] ?? '' ];
            }
        }

        $attributes = [];
        foreach ( $p['attributes'] ?? [] as $a ) {
            if ( isset( $a['id'] ) || isset( $a['name'] ) ) {
                $attributes[] = [
                    'id'      => isset( $a['id'] ) ? (int) $a['id'] : 0,
                    'name'    => $a['name'] ?? '',
                    'options' => $a['options'] ?? [],
                ];
            }
        }

        $images = [];
        foreach ( $p['images'] ?? [] as $img ) {
            if ( isset( $img['id'] ) || isset( $img['src'] ) ) {
                $images[] = [
                    'id'  => isset( $img['id'] ) ? (int) $img['id'] : 0,
                    'src' => $img['src'] ?? '',
                    'alt' => $img['alt'] ?? '',
                ];
            }
        }

        return [
            'id'             => isset( $p['id'] ) ? (int) $p['id'] : 0,
            'name'           => $p['name'] ?? '',
            'slug'           => $p['slug'] ?? '',
            'type'           => $p['type'] ?? '',
            'status'         => $p['status'] ?? '',
            'sku'            => $p['sku'] ?? '',
            'regular_price'  => $p['regular_price'] ?? '',
            'sale_price'     => $p['sale_price'] ?? '',
            'price'          => $p['price'] ?? '',
            'stock_status'   => $p['stock_status'] ?? '',
            'stock_quantity' => $p['stock_quantity'] ?? null,
            'categories'     => $categories,
            'attributes'     => $attributes,
            'images'         => $images,

            // ATUM extended fields (present only if ATUM extender is active).
            'purchase_price' => $p['purchase_price'] ?? null,
            'supplier_id'    => $p['supplier_id'] ?? null,
            'atum_locations' => $p['atum_locations'] ?? null,
        ];
    }
}

add_action( 'rest_api_init', function () {

    /**
     * 🔹 GET /products/{id} — Retrieve product by ID
     * - ?lite=1 (default) returns a compact shape.
     * - ?lite=0 returns the full Woo payload.
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            $id   = absint( $req['id'] );
            $lite = absint( $req->get_param( 'lite' ) ?: 1 );

            $res  = lokey_inv_request( "products/{$id}?context=edit", 'GET' );
            $code = $res['code'] ?? 500;
            $body = $res['body'] ?? [];

            if ( $code < 400 && $lite && is_array( $body ) ) {
                $body = lokey_inv_product_lite_view( $body );
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'id'        => $id,
                'lite'      => (bool) $lite,
                'data'      => $body,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
        'permission_callback' => '__return_true',
        'args' => [
            'lite' => [ 'type' => 'integer', 'default' => 1 ],
        ],
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
            $res      = lokey_inv_request( 'products', 'POST', $body );
            $code     = $res['code'] ?? 500;
            $body_res = $res['body'] ?? [];
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => $code < 400 ? 'success' : 'error',
                'code'      => $code,
                'data'      => $body_res,
                'timestamp' => current_time( 'mysql' ),
            ], $code );
        },
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
        'permission_callback' => '__return_true',
    ] );

} );
