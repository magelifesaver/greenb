<?php
/**
 * Purchase order manager.
 *
 * This class encapsulates the logic for creating purchase orders when
 * items are processed from the PO queue. It intentionally limits
 * responsibilities to order creation and leaves scheduling and queue
 * management to the queue classes. Hooks allow customisation of the
 * resulting order status and the order itself.
 *
 * @package AAA_Order_Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_PO_Manager
 *
 * Contains a single public static method for creating purchase orders
 * based on a product ID and quantity. The method gracefully fails if
 * WooCommerce order creation functions are unavailable and exposes
 * action and filter hooks for third-party customisation.
 */
class AAA_OC_PO_Manager {
    /**
     * Create a purchase order for a given product. If WooCommerce
     * functions are unavailable, the method returns silently. The
     * resulting order is left in 'pending' status by default but
     * pluggable via the 'aaa_oc_po_order_status' filter.
     *
     * @param int   $product_id Product ID.
     * @param int   $quantity   Quantity to order.
     * @param array $row        Original queue row for context.
     */
    public static function create_purchase_order( int $product_id, int $quantity = 1, array $row = [] ): void {
        // Bail out if WooCommerce order creation functions are unavailable.
        if ( ! function_exists( 'wc_create_order' ) || ! function_exists( 'wc_get_product' ) ) {
            return;
        }
        $product_id = absint( $product_id );
        $quantity   = max( 1, absint( $quantity ) );
        if ( ! $product_id ) {
            return;
        }
        try {
            $order   = wc_create_order();
            $product = wc_get_product( $product_id );
            if ( $order && $product ) {
                $order->add_product( $product, $quantity );
                /**
                 * Action fired after adding a product to a new PO order.
                 *
                 * Allows third parties to modify the order, e.g. set meta,
                 * shipping methods or recipient. The order is not yet
                 * calculated or saved.
                 *
                 * @param WC_Order $order The order object.
                 * @param array    $row   Original queue row.
                 */
                do_action( 'aaa_oc_po_order_created', $order, $row );
                // Calculate totals before setting status.
                $order->calculate_totals();
                // Determine desired order status via filter. Defaults to 'pending'.
                $status = apply_filters( 'aaa_oc_po_order_status', 'pending', $order, $row );
                $order->update_status( $status );
            }
        } catch ( Throwable $e ) {
            if ( defined( 'AAA_OC_FORECAST_DEBUG' ) && AAA_OC_FORECAST_DEBUG ) {
                error_log( '[PO Manager] Could not create order for product ' . $product_id . ': ' . $e->getMessage() );
            }
        }
    }
}