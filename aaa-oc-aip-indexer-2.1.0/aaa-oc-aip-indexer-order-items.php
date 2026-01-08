<?php
/**
 * Order items summary for the AAA OC AIP Indexer Bridge.
 *
 * Generates a plain‑text summary of the items purchased in an order.
 * This summary lists each line item with its quantity, name, and
 * line total using the order’s currency symbol.  The summary is
 * saved as the public `aip_order_item_summary` meta key on the
 * order so that the AIP plugin can index it.  Keeping this logic
 * in its own file preserves the wide‑and‑thin architecture.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-order-items.php
 * Version: 2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_LOADED', true );

// Local debug toggle for this module.
if ( ! defined( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_DEBUG' ) ) {
    define( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_DEBUG', true );
}

/**
 * Handles generation of the item summary for an order.
 */
class AAA_OC_AIP_Indexer_Order_Items {

    /**
     * Registers WordPress hooks for item summary generation.
     * Hooks into order creation and update events to ensure the
     * summary is present on all orders.
     */
    public static function init() {
        // When a new order is created at checkout.
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'sync_items' ], 10, 2 );
        // When an existing order is updated.
        add_action( 'woocommerce_update_order', [ __CLASS__, 'sync_items' ], 10, 1 );
    }

    /**
     * Builds and saves the item summary for a given order.
     *
     * @param int|\WC_Order $order Order ID or object.
     * @param array|null      $data  Unused; present for checkout hook compatibility.
     */
    public static function sync_items( $order, $data = null ) {
        // Always work with a WC_Order object.
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        if ( ! $order instanceof \WC_Order ) {
            return;
        }
        $id = $order->get_id();
        // Determine currency symbol for formatting totals.
        $currency     = $order->get_currency();
        $currency_sym = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $currency ) : '$';
        $items        = [];
        // Loop through order items and build a summary entry for each.
        foreach ( $order->get_items() as $item ) {
            if ( ! $item instanceof \WC_Order_Item_Product ) {
                continue;
            }
            $name  = $item->get_name();
            $qty   = (int) $item->get_quantity();
            $total = (float) $item->get_total();
            $items[] = $qty . ' × ' . $name . ' (' . $currency_sym . number_format( $total, 2 ) . ')';
        }
        if ( empty( $items ) ) {
            return;
        }
        $summary = 'Items: ' . implode( '; ', $items );
        update_post_meta( $id, 'aip_order_item_summary', $summary );
        if ( AAA_OC_AIP_INDEXER_ORDER_ITEMS_DEBUG ) {
            $num = $order->get_order_number();
            error_log( '[ORDER ITEMS] Updated item summary for order ' . $num );
        }
    }
}

// Bootstrap the item summary module.
AAA_OC_AIP_Indexer_Order_Items::init();