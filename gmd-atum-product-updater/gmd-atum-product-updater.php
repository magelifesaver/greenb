<?php
/**
 * Plugin Name: ATUM Product Updater
 * Description: Secure admin-only REST endpoints to update ATUM product fields (single + batch) and log each write.
 * Version: 1.1.0
 * Author: GPT Dev
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    // Single product update
    register_rest_route( 'atum-helper/v1', '/products/update', [
        'methods'             => 'POST',
        'callback'            => 'atum_update_product_handler',
        'permission_callback' => function () {
            return current_user_can( 'manage_woocommerce' );
        },
        'args' => atum_get_field_args(),
    ]);

    // Batch product update
    register_rest_route( 'atum-helper/v1', '/products/batch-update', [
        'methods'             => 'POST',
        'callback'            => 'atum_batch_update_handler',
        'permission_callback' => function () {
            return current_user_can( 'manage_woocommerce' );
        },
        'args' => [
            'updates' => [
                'required' => true,
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => array_merge([
                        'product_id' => [ 'type' => 'integer' ]
                    ], atum_get_field_args())
                ]
            ]
        ]
    ]);
});

function atum_get_field_args() {
    return [
        'supplier_id' => [ 'required' => false, 'type' => 'integer' ],
        'purchase_price' => [ 'required' => false, 'type' => 'number' ],
        'supplier_sku' => [ 'required' => false, 'type' => 'string' ],
        'barcode' => [ 'required' => false, 'type' => 'string' ],
        'atum_controlled' => [ 'required' => false, 'type' => 'boolean' ],
        'sync_purchase_price' => [ 'required' => false, 'type' => 'boolean' ],
    ];
}

function atum_update_product_handler( WP_REST_Request $request ) {
    $product_id = $request->get_param( 'product_id' );
    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        return new WP_Error( 'invalid_product', 'Product not found.', [ 'status' => 404 ] );
    }

    $updated_fields = atum_apply_fields( $product_id, $request->get_params() );

    do_action( 'atum_product_updated', $product_id, $updated_fields );
    return rest_ensure_response( [
        'product_id' => $product_id,
        'updated_fields' => $updated_fields,
        'product' => wc_get_product( $product_id )
    ]);
}

function atum_batch_update_handler( WP_REST_Request $request ) {
    $payloads = $request->get_param( 'updates' );
    $results = [];

    foreach ( $payloads as $index => $data ) {
        if ( empty( $data['product_id'] ) ) {
            $results[] = [ 'index' => $index, 'error' => 'Missing product_id' ];
            continue;
        }

        $product_id = $data['product_id'];
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            $results[] = [ 'product_id' => $product_id, 'error' => 'Product not found' ];
            continue;
        }

        $updated_fields = atum_apply_fields( $product_id, $data );
        do_action( 'atum_product_updated', $product_id, $updated_fields );
        $results[] = [ 'product_id' => $product_id, 'updated_fields' => $updated_fields ];
    }

    return rest_ensure_response( $results );
}

function atum_apply_fields( $product_id, $data ) {
    $map = [
        'supplier_id'         => '_supplier_id',
        'purchase_price'      => '_purchase_price',
        'supplier_sku'        => '_supplier_sku',
        'barcode'             => '_barcode',
        'atum_controlled'     => '_atum_controlled',
        'sync_purchase_price' => '_sync_purchase_price',
    ];
    $updated = [];

    foreach ( $map as $field => $meta_key ) {
        if ( array_key_exists( $field, $data ) ) {
            update_post_meta( $product_id, $meta_key, $data[$field] );
            $updated[] = $field;
        }
    }

    // Log
    error_log( '[ATUM-BATCH-UPDATE] ' . wp_json_encode([
        'user' => get_current_user_id(),
        'time' => current_time( 'mysql' ),
        'product_id' => $product_id,
        'updated_fields' => $updated
    ]) );

    return $updated;
}
