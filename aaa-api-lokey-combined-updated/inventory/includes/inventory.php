<?php
/**
 * Inventory endpoint.
 *
 * Provides a read‑only endpoint to retrieve ATUM or WooCommerce inventory
 * data filtered by location, brand, supplier, category or stock status.
 * Includes fallback logic when the ATUM REST API is unavailable.  This
 * endpoint is not currently described in the OpenAPI spec but remains
 * available for internal use.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/inventory', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            // Build filter parameters from the request.
            $filters = [];
            foreach ( [ 'location', 'brand', 'supplier', 'category', 'stock_status' ] as $key ) {
                if ( $req->get_param( $key ) ) {
                    $filters[ $key ] = sanitize_text_field( $req->get_param( $key ) );
                }
            }

            // Pagination controls
            $filters['per_page'] = lokey_inv_sanitize_int( $req['per_page'], 20 );
            $filters['page']     = lokey_inv_sanitize_int( $req['page'], 1, PHP_INT_MAX );
            $query               = http_build_query( $filters );

            // Attempt to query the ATUM REST API first.
            $atum_res = lokey_inv_request( "atum/inventory?{$query}", 'GET' );
            if ( $atum_res['code'] >= 200 && $atum_res['code'] < 300 && ! empty( $atum_res['body'] ) ) {
                return new WP_REST_Response( $atum_res['body'], 200 );
            }

            // 🔄 Fallback: direct WooCommerce query if ATUM is not available or returns no data.
            $args = [
                'status' => [ 'publish', 'private' ],
                'type'   => [ 'simple', 'variation' ],
                'limit'  => $filters['per_page'],
                'page'   => $filters['page'],
                'return' => 'ids',
            ];
            if ( ! empty( $filters['category'] ) ) {
                $args['category'] = [ $filters['category'] ];
            }
            if ( ! empty( $filters['stock_status'] ) ) {
                $args['stock_status'] = $filters['stock_status'];
            }

            $product_ids = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : [];
            $inventory   = [];

            foreach ( $product_ids as $product_id ) {
                $product = wc_get_product( $product_id );
                if ( ! $product ) {
                    continue;
                }
                // Stock quantity & status
                $stock_qty    = $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
                $stock_status = $product->get_stock_status();
                // Supplier, purchase price & ATUM meta (if available)
                $supplier_id = get_post_meta( $product_id, '_supplier_id', true );
                $supplier    = $supplier_id ? get_the_title( $supplier_id ) : 'Unknown';
                $purchase_price = get_post_meta( $product_id, 'purchase_price', true );
                if ( $purchase_price === '' ) {
                    $purchase_price = get_post_meta( $product_id, '_purchase_price', true );
                }
                $purchase_price = $purchase_price !== '' ? floatval( $purchase_price ) : 0.0;
                $inventory[] = [
                    'id'              => $product_id,
                    'name'            => $product->get_name(),
                    'sku'             => $product->get_sku(),
                    'stock_status'    => $stock_status,
                    'stock_quantity'  => $stock_qty,
                    'supplier'        => $supplier,
                    'purchase_price'  => $purchase_price,
                    'sale_price'      => (float) $product->get_sale_price(),
                    'regular_price'   => (float) $product->get_regular_price(),
                    'total_value'     => round( $purchase_price * (int) $stock_qty, 2 ),
                ];
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'count'     => count( $inventory ),
                'page'      => $filters['page'],
                'per_page'  => $filters['per_page'],
                'fallback'  => true,
                'timestamp' => current_time( 'mysql' ),
                'data'      => $inventory,
            ], 200 );
        },
        // Expose the inventory listing without requiring a JWT.  Downstream calls
        // still authenticate with ATUM/WooCommerce using API keys.
        'permission_callback' => '__return_true',
        'args'                => [
            'page'         => [ 'type' => 'integer', 'default' => 1 ],
            'per_page'     => [ 'type' => 'integer', 'default' => 20 ],
            'location'     => [ 'type' => 'string' ],
            'brand'        => [ 'type' => 'string' ],
            'supplier'     => [ 'type' => 'string' ],
            'category'     => [ 'type' => 'string' ],
            'stock_status' => [ 'type' => 'string' ],
        ],
    ] );
} );
/**
 * --------------------------------------------------------------------------
 * PUT /lokey-inventory/v1/inventory/{id}
 * --------------------------------------------------------------------------
 * Proxies cost/stock updates to WooCommerce’s /wc/v3/products/{id}
 * so ATUM’s purchase_price field syncs to wp_atum_product_data.
 * 
 * 🔒 JWT in, 🔁 Woo API call out (Basic Auth internally)
 * Follows LKD-SAFE merge policy.
 */
add_action( 'rest_api_init', function () {

    register_rest_route( LOKEY_INV_API_NS, '/inventory/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {

            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Invalid product ID.',
                ], 400 );
            }

            $body = $req->get_json_params() ?: [];
            $allowed = [ 'purchase_price', 'stock_quantity' ];
            $update_data = array_intersect_key( $body, array_flip( $allowed ) );

            if ( empty( $update_data ) ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'No valid fields provided.',
                ], 400 );
            }

            // 🔐 WooCommerce REST keys stored securely in wp-config.php
            $ck = defined( 'WC_CONSUMER_KEY' ) ? WC_CONSUMER_KEY : '';
            $cs = defined( 'WC_CONSUMER_SECRET' ) ? WC_CONSUMER_SECRET : '';

            if ( empty( $ck ) || empty( $cs ) ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'Missing internal WooCommerce REST credentials.',
                ], 401 );
            }

            // 🔁 Forward the payload to WooCommerce’s /wc/v3/products/{id}
            $url = get_rest_url( null, 'wc/v3/products/' . $id );

            $response = wp_remote_request( $url, [
                'method'  => 'PUT',
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( "$ck:$cs" ),
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $update_data ),
                'timeout' => 20,
            ]);

            if ( is_wp_error( $response ) ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => 500,
                    'message' => 'WooCommerce request failed: ' . $response->get_error_message(),
                ], 500 );
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $code < 200 || $code >= 300 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'WooCommerce API error.',
                    'response' => $body,
                ], $code );
            }

            // ✅ Success — ATUM hooks have now updated cost table
            return new WP_REST_Response([
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'id'        => $id,
                'data'      => $update_data,
                'message'   => 'ATUM cost + stock synced via WooCommerce API.',
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

} );
