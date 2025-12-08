<?php
// inc/core.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Should this product be considered archived due to OOS?
 * - Backorders (yes/notify): do NOT archive.
 * - Variable: only archive if ALL variations are out of stock.
 */
function aaa_should_archive_oos( $product ) {
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( $product );
    }
    if ( ! $product ) return false;

    $backorders = $product->get_backorders();
    if ( in_array( $backorders, array( 'yes', 'notify' ), true ) ) {
        return false;
    }

    if ( $product->is_type( 'variable' ) ) {
        $children = $product->get_children();
        if ( empty( $children ) ) {
            return 'outofstock' === $product->get_stock_status();
        }
        foreach ( $children as $child_id ) {
            $child = wc_get_product( $child_id );
            if ( ! $child ) continue;
            $st = $child->get_stock_status();
            if ( 'instock' === $st || 'onbackorder' === $st ) {
                return false;
            }
        }
        return true;
    }

    return 'outofstock' === $product->get_stock_status();
}

/**
 * Core evaluator — preserved name from v1.x for consistency.
 * No longer changes post_status. Only sets our meta flag + timestamp.
 */
function aaa_maybe_update_status( $product_id ) {
    if ( ! $product_id ) return;

    $pt = get_post_type( $product_id );
    if ( ! in_array( $pt, array( 'product', 'product_variation' ), true ) ) return;

    if ( 'product_variation' === $pt ) {
        $parent_id = (int) wp_get_post_parent_id( $product_id );
        if ( $parent_id ) $product_id = $parent_id;
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) return;

    $should = aaa_should_archive_oos( $product );
    $was    = get_post_meta( $product_id, 'aaa_oos_archived', true ) === 'yes';

    if ( $should && ! $was ) {
        update_post_meta( $product_id, 'aaa_oos_archived', 'yes' );
        update_post_meta( $product_id, 'aaa_oos_archived_date', time() );
        do_action( 'aaa_oos_archived_status_changed', $product_id, 'yes', 'no' );
    } elseif ( ! $should && $was ) {
        update_post_meta( $product_id, 'aaa_oos_archived', 'no' );
        do_action( 'aaa_oos_archived_status_changed', $product_id, 'no', 'yes' );
    }
}

// Hooks — driven by Woo stock events + meta changes + save.
add_action( 'woocommerce_product_set_stock_status', function( $product_id ) {
    aaa_maybe_update_status( $product_id );
}, 20, 1 );

add_action( 'woocommerce_variation_set_stock_status', function( $product_id ) {
    aaa_maybe_update_status( $product_id );
}, 20, 1 );

add_action( 'updated_post_meta', function( $mid, $obj_id, $key ) {
    if ( in_array( $key, array( '_stock', '_stock_status' ), true ) ) {
        $pt = get_post_type( $obj_id );
        if ( in_array( $pt, array( 'product', 'product_variation' ), true ) ) {
            aaa_maybe_update_status( $obj_id );
        }
    }
}, 10, 3 );

add_action( 'save_post_product', function( $post_id ) {
    aaa_maybe_update_status( $post_id );
}, 20, 1 );

/** Reconciler — preserved name from v1.x */
function aaa_reconcile_stock_statuses() {
    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => array( 'publish', 'private', 'draft', 'pending' ),
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ) );
    foreach ( $ids as $id ) {
        aaa_maybe_update_status( $id );
    }
}

/** Helper if needed in templates / admin columns */
function aaa_is_archived_oos( $product_id ) {
    return get_post_meta( $product_id, 'aaa_oos_archived', true ) === 'yes';
}
