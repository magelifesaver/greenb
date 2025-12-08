<?php
/**
 * File: /plugins/aaa-add-custom-product-status/aaa-add-custom-product-status.php
 * Plugin Name: AAA Add Custom Product Status 1.4 (live)(net)(addon)
 * Description: Automatically manages product statuses based on stock levels, switching between "Private" and "Publish."
 * Version: 1.4
 * Author: WebMaster
 * Text Domain: aaa-custom-status
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core function to evaluate stock and update post status.
 */
function aaa_maybe_update_status( $product_id ) {
    if ( 'product' !== get_post_type( $product_id ) ) {
        return;
    }

    $manage_stock   = get_post_meta( $product_id, '_manage_stock', true );
    if ( 'yes' !== $manage_stock ) {
        return;
    }

    $stock_quantity = (int) get_post_meta( $product_id, '_stock', true );
    $current_status = get_post_status( $product_id );

    if ( $stock_quantity < 1 && 'private' !== $current_status ) {
        wp_update_post( [
            'ID'          => $product_id,
            'post_status' => 'private',
        ] );
    } elseif ( $stock_quantity > 0 && 'publish' !== $current_status ) {
        wp_update_post( [
            'ID'          => $product_id,
            'post_status' => 'publish',
        ] );
    }
}

// 1. Hook when WooCommerce sets stock status
add_action( 'woocommerce_product_set_stock_status', function( $product_id ) {
    aaa_maybe_update_status( $product_id );
}, 20, 1 );

// 2. Hook general meta updates for stock quantity changes
add_action( 'updated_post_meta', function( $meta_id, $object_id, $meta_key, $_meta_value ) {
    if ( '_stock' === $meta_key && 'product' === get_post_type( $object_id ) ) {
        aaa_maybe_update_status( $object_id );
    }
}, 10, 4 );

// 3. Ensure quick-edit and bulk edits also trigger status updates
add_action( 'woocommerce_product_quick_edit_save', function( $product ) {
    aaa_maybe_update_status( $product->get_id() );
} );

// 4. Hook into post-save to catch any remaining timing issues
add_action( 'save_post_product', function( $post_id, $post, $update ) {
    // Run after all metadata is saved
    aaa_maybe_update_status( $post_id );
}, 20, 3 );

// 5. Initial sync on activation and schedule hourly reconciliation
register_activation_hook( __FILE__, function() {
    // One-time initial sync
    if ( function_exists( 'aaa_reconcile_stock_statuses' ) ) {
        aaa_reconcile_stock_statuses();
    }

    // Schedule hourly reconciliation if not already scheduled
    if ( ! wp_next_scheduled( 'aaa_reconcile_stock_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'aaa_reconcile_stock_event' );
    }
} );

// 6. Clear scheduled event on deactivation
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'aaa_reconcile_stock_event' );
} );

// 7. Reconciliation callback
add_action( 'aaa_reconcile_stock_event', 'aaa_reconcile_stock_statuses' );

/**
 * Loop through all managed-stock products and enforce correct post status.
 */
function aaa_reconcile_stock_statuses() {
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'private' ],
        'meta_query'     => [
            [
                'key'     => '_manage_stock',
                'value'   => 'yes',
                'compare' => '=',
            ],
        ],
        'fields'         => 'ids',
    ];

    $products = get_posts( $args );
    foreach ( $products as $id ) {
        aaa_maybe_update_status( $id );
    }
}
