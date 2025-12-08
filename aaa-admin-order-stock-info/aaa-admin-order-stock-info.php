<?php
/**
 * Plugin Name: AAA Admin Order Stock Info (live)(net)(addon)
 * Description: Displays current stock quantity under each line item in WooCommerce order edit screen.
 * Version: 1.0.0
 * Author: Webmaster
 * License: GPL2
 *
 * File Path: wp-content/plugins/aaa-admin-order-stock-info.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Show stock quantity in the order item meta area (admin order edit).
 *
 * @param int        $item_id The item ID.
 * @param WC_Order_Item $item The order item object.
 * @param WC_Product $product The related product object.
 */
function aaa_admin_order_stock_info_show_qty( $item_id, $item, $product ) {
    // Only run on actual line items (skip shipping/fees).
    if ( $item->get_type() !== 'line_item' ) {
        return;
    }

    // Make sure we have a valid product object.
    if ( ! is_object( $product ) ) {
        return;
    }

    // If the product manages stock, fetch and display the current quantity.
    if ( $product->managing_stock() ) {
        $stock_qty = $product->get_stock_quantity();

        echo '<p style="margin:4px 0 0; font-weight:bold;">';
        echo esc_html__( 'Current stock:', 'woocommerce' ) . ' ' . ( $stock_qty !== null ? intval( $stock_qty ) : 'N/A' );
        echo '</p>';
    }
}
add_action( 'woocommerce_after_order_itemmeta', 'aaa_admin_order_stock_info_show_qty', 10, 3 );

// END Stock Quantity on Order Edit
