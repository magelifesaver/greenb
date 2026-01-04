<?php
/**
 * Extended Products CRUD endpoints for GPT automation.
 *
 * Adds safe creation and update routes that merge attributes, handle categories,
 * brands, suppliers, descriptions, and sale pricing automatically.
 *
 * Routes:
 *   - POST /lokey-inventory/v1/products/extended
 *   - PUT  /lokey-inventory/v1/products/extended/{id}
 *
 * These endpoints use lokey_inv_request() to proxy WooCommerce/ATUM REST calls.
 * They are non-destructive: only fields explicitly provided are updated.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {

    /**
     * 🔹 POST /products/extended — Create new product safely
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended', [
        'methods'  => 'POST',
        'callback' => function( WP_REST_Request $req ) {

            $body = $req->get_json_params() ?: [];

            // --- Defaults & Safety ---
            $body['type']           = $body['type'] ?? 'simple';
            $body['manage_stock']   = true;
            $body['stock_quantity'] = $body['stock_quantity'] ?? 0;
            $body['status']         = $body['status'] ?? 'publish';

            // --- Sale Price Calculation ---
            if ( ! empty( $body['discount_percent'] ) && ! empty( $body['regular_price'] ) ) {
                $body['sale_price'] = round( $body['regular_price'] * ( 1 - ( $body['discount_percent'] / 100 ) ), 2 );
            }

            // --- Attribute Handling ---
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // --- Create Product via WooCommerce API ---
            $res = lokey_inv_request( 'products', 'POST', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 || empty( $data['id'] ) ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product creation failed',
                    'data'    => $data,
                ], $code );
            }

            $id = absint( $data['id'] );

            // --- Optional: Supplier Link ---
            if ( ! empty( $body['supplier_id'] ) ) {
                update_post_meta( $id, '_supplier_id', absint( $body['supplier_id'] ) );
            }

            return new WP_REST_Response([
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'action'    => 'created',
                'id'        => $id,
                'data'      => $data,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 PUT /products/extended/{id} — Update product (merge attributes + descriptions)
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function( WP_REST_Request $req ) {

            $id   = absint( $req['id'] );
            $body = $req->get_json_params() ?: [];

            if ( $id <= 0 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'message' => 'Invalid product ID.',
                ], 400 );
            }

            // --- Allow description updates ---
            if ( isset( $body['description'] ) ) {
                $body['description'] = wp_kses_post( $body['description'] );
            }
            if ( isset( $body['short_description'] ) ) {
                $body['short_description'] = wp_kses_post( $body['short_description'] );
            }

            // --- Attribute Safety ---
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // --- Forward PUT to WooCommerce ---
            $res  = lokey_inv_request( "products/{$id}", 'PUT', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product update failed',
                    'data'    => $data,
                ], $code );
            }

            // --- Supplier Linking (if provided) ---
            if ( ! empty( $body['supplier_id'] ) ) {
                update_post_meta( $id, '_supplier_id', absint( $body['supplier_id'] ) );
            }

            return new WP_REST_Response([
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'action'    => 'updated',
                'id'        => $id,
                'data'      => $data,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

});
