<?php
/**
 * Writes `aip_order_item_summary` meta for AIP indexing (COMPLETED ORDERS ONLY).
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-order-items.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_ORDER_ITEMS_LOADED', true );

class AAA_OC_AIP_Indexer_Order_Items {

    private static $ran = [];

    public static function init() {
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'sync_items' ], 10, 1 );
    }

    public static function sync_items( $order, $data = null ) {
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( (int) $order );
        }
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $id = $order->get_id();
        if ( isset( self::$ran[ $id ] ) ) {
            return;
        }
        self::$ran[ $id ] = true;

        if ( $order->get_status() !== 'completed' ) {
            return;
        }

        $currency     = $order->get_currency();
        $currency_sym = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $currency ) : '$';

        $items = [];
        foreach ( $order->get_items() as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }
            $name  = $item->get_name();
            $qty   = (int) $item->get_quantity();
            $total = (float) $item->get_total();
            $items[] = $qty . ' × ' . $name . ' (' . $currency_sym . number_format( $total, 2 ) . ')';
        }

        if ( ! $items ) {
            return;
        }

        $summary  = 'Items: ' . implode( '; ', $items );
        $existing = (string) get_post_meta( $id, 'aip_order_item_summary', true );

        if ( $existing !== $summary ) {
            update_post_meta( $id, 'aip_order_item_summary', $summary );
        }

        if ( defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) && AAA_OC_AIP_INDEXER_LOGGING_ENABLED ) {
            error_log( '[ORDER ITEMS] Updated item summary for order ' . $order->get_order_number() );
        }
    }
}

AAA_OC_AIP_Indexer_Order_Items::init();
