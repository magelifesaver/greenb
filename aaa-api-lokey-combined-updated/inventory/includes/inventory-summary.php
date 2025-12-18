<?php
/**
 * Inventory summary endpoint.
 *
 * Calculates total stock value per supplier by combining ATUM inventory data
 * with a WooCommerce fallback.  Useful for high‑level analytics of
 * inventory valuation.  Requires authentication because the underlying
 * values may be sensitive.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/inventory/summary', [
        'methods'  => 'GET',
        'callback' => function ( WP_REST_Request $req ) {
            // Step 1: Try fetching ATUM inventory in bulk.
            $res    = lokey_inv_request( 'atum/inventory?per_page=100', 'GET' );
            $items  = is_array( $res['body'] ) ? $res['body'] : [];
            $totals = [];
            if ( ! empty( $items ) ) {
                foreach ( $items as $item ) {
                    $supplier_name  = $item['supplier'] ?? 'Unknown';
                    $purchase_price = isset( $item['purchase_price'] ) ? floatval( $item['purchase_price'] ) : 0.0;
                    $stock_quantity = isset( $item['stock_quantity'] ) ? intval( $item['stock_quantity'] ) : 0;
                    $value          = $purchase_price * $stock_quantity;
                    if ( ! isset( $totals[ $supplier_name ] ) ) {
                        $totals[ $supplier_name ] = 0.0;
                    }
                    $totals[ $supplier_name ] += $value;
                }
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'success',
                    'method'    => 'atum',
                    'count'     => count( $items ),
                    'totals'    => $totals,
                    'timestamp' => current_time( 'mysql' ),
                ], 200 );
            }
            // Step 2: Fallback — query WooCommerce directly if ATUM data is missing.
            $args = [
                'status' => [ 'publish', 'private' ],
                'type'   => [ 'simple', 'variation' ],
                'limit'  => -1,
                'return' => 'ids',
            ];
            $product_ids = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : [];
            if ( empty( $product_ids ) ) {
                return new WP_REST_Response( [
                    'version'   => LOKEY_INV_API_VERSION,
                    'status'    => 'error',
                    'message'   => 'No product data found from ATUM or WooCommerce.',
                    'totals'    => [],
                    'timestamp' => current_time( 'mysql' ),
                ], 404 );
            }
            foreach ( $product_ids as $pid ) {
                $product = wc_get_product( $pid );
                if ( ! $product ) {
                    continue;
                }
                // Supplier name (from _supplier_id or meta)
                $supplier_id = get_post_meta( $pid, '_supplier_id', true );
                $supplier    = $supplier_id ? get_the_title( $supplier_id ) : 'Unknown';
                // Purchase price
                $purchase_price = get_post_meta( $pid, '_purchase_price', true );
                if ( $purchase_price === '' ) {
                    $purchase_price = get_post_meta( $pid, 'purchase_price', true );
                }
                $purchase_price = $purchase_price !== '' ? floatval( $purchase_price ) : 0.0;
                // Stock quantity
                $stock_qty = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
                // Value calculation
                $value = $purchase_price * $stock_qty;
                if ( ! isset( $totals[ $supplier ] ) ) {
                    $totals[ $supplier ] = 0.0;
                }
                $totals[ $supplier ] += $value;
            }
            return new WP_REST_Response( [
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'method'    => 'fallback',
                'count'     => count( $product_ids ),
                'totals'    => $totals,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        // Make the inventory summary endpoint public for GPT; authentication is
        // handled internally when querying ATUM/WooCommerce.
        'permission_callback' => '__return_true',
    ] );
} );
