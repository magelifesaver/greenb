<?php
/**
 * Inventory endpoints.
 *
 * - GET  /lokey-inventory/v1/inventory
 * - PUT  /lokey-inventory/v1/inventory/{id}
 *
 * Notes:
 * - GET tries ATUM REST first, then falls back to a lightweight Woo query.
 * - PUT performs a non-destructive "LKD-SAFE" update for ATUM fields and stock:
 *     purchase_price, supplier_id, atum_locations, stock_quantity
 *   without writing raw post meta like _supplier_id.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    /**
     * GET /inventory
     */
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

            // Fallback: direct WooCommerce query if ATUM API is unavailable or returns no data.
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

                $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
                if ( ! $product ) {
                    continue;
                }

                // Stock quantity & status
                $stock_qty    = $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
                $stock_status = $product->get_stock_status();

                // Supplier & purchase price (best-effort).
                $supplier_id = get_post_meta( $product_id, '_supplier', true );
                if ( ! $supplier_id ) {
                    // Legacy/incorrect key used by earlier versions.
                    $supplier_id = get_post_meta( $product_id, '_supplier_id', true );
                }
                $supplier = $supplier_id ? get_the_title( $supplier_id ) : 'Unknown';

                $purchase_price = get_post_meta( $product_id, '_purchase_price', true );
                if ( $purchase_price === '' ) {
                    $purchase_price = get_post_meta( $product_id, 'purchase_price', true );
                }
                $purchase_price = $purchase_price !== '' ? floatval( $purchase_price ) : 0.0;

                $inventory[] = [
                    'id'             => (int) $product_id,
                    'name'           => $product->get_name(),
                    'sku'            => $product->get_sku(),
                    'stock_status'   => $stock_status,
                    'stock_quantity' => $stock_qty,
                    'supplier'       => $supplier,
                    'purchase_price' => $purchase_price,
                    'sale_price'     => (float) $product->get_sale_price(),
                    'regular_price'  => (float) $product->get_regular_price(),
                    'total_value'    => round( $purchase_price * (int) $stock_qty, 2 ),
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
        // Public for GPT actions (internal Woo/ATUM auth still applies for proxy calls).
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

    /**
     * PUT /inventory/{id}
     *
     * Updates only explicitly provided fields (LKD-SAFE).
     * This endpoint is intended for ATUM-related fields that must land in
     * wp_atum_product_data (supplier_id, purchase_price) + locations taxonomy.
     */
    register_rest_route( LOKEY_INV_API_NS, '/inventory/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {

            $id = absint( $req['id'] );
            if ( $id <= 0 ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Invalid product ID.',
                ], 400 );
            }

            $body = $req->get_json_params() ?: [];

            // Build a strict, non-destructive update payload.
            $update_data = [];

            if ( array_key_exists( 'purchase_price', $body ) && ! is_null( $body['purchase_price'] ) && $body['purchase_price'] !== '' ) {
                $update_data['purchase_price'] = floatval( $body['purchase_price'] );
            }

            if ( array_key_exists( 'supplier_id', $body ) && ! is_null( $body['supplier_id'] ) && $body['supplier_id'] !== '' ) {
                $update_data['supplier_id'] = absint( $body['supplier_id'] );
            }

            if ( array_key_exists( 'stock_quantity', $body ) && ! is_null( $body['stock_quantity'] ) ) {
                $update_data['stock_quantity'] = intval( $body['stock_quantity'] );
            }

            // Accept multiple aliases for locations.
            $loc_raw = null;
            if ( array_key_exists( 'atum_locations', $body ) ) {
                $loc_raw = $body['atum_locations'];
            } elseif ( array_key_exists( 'location_ids', $body ) ) {
                $loc_raw = $body['location_ids'];
            } elseif ( array_key_exists( 'atum_location_ids', $body ) ) {
                $loc_raw = $body['atum_location_ids'];
            } elseif ( array_key_exists( 'location_id', $body ) ) {
                $loc_raw = $body['location_id'];
            } elseif ( array_key_exists( 'atum_location_id', $body ) ) {
                $loc_raw = $body['atum_location_id'];
            }

            $loc_norm = lokey_inv_normalize_atum_locations( $loc_raw );
            if ( ! is_null( $loc_norm ) ) {
                // Keep ATUM's expected shape: [ {"id":1}, ... ] (can be empty array to clear).
                $update_data['atum_locations'] = $loc_norm;
            }

            if ( empty( $update_data ) && ! array_key_exists( 'atum_locations', $update_data ) ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'No valid fields provided.',
                ], 400 );
            }

            $result = lokey_inv_update_atum_product_data( $id, $update_data );
            if ( is_wp_error( $result ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'code'      => $result->get_error_data()['status'] ?? 500,
                    'message'   => $result->get_error_message(),
                    'error'     => $result->get_error_code(),
                    'timestamp' => current_time( 'mysql' ),
                ], $result->get_error_data()['status'] ?? 500 );
            }

            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'id'        => $id,
                'applied'   => $update_data,
                'result'    => $result,
                'message'   => 'ATUM fields + stock updated (no raw post meta writes).',
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

} );
