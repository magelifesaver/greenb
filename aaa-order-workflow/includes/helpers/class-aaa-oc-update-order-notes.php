<?php
/**
 * File Path: /includes/payment/class-aaa-oc-update-order-notes.php
 * Purpose: Adds order notes for payment activity and status changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Update_Order_Notes {

    /**
     * Attach WooCommerce hook for status changes.
     */
    public static function init() {
        add_action(
            'woocommerce_order_status_changed',
            [ __CLASS__, 'record_order_status_change' ],
            10,
            4
        );
    }

    /**
     * Log order status changes as order notes.
     */
    public static function record_order_status_change( $order_id, $old_status, $new_status, $order ) {
        // Log the status change (optional debugging)
        error_log( "Order #{$order_id} status changed from {$old_status} to {$new_status}" );

        // Ensure the order object is valid
        if ( ! is_a( $order, 'WC_Order' ) || ! method_exists( $order, 'add_order_note' ) ) {
            error_log( "Order object invalid for order #{$order_id}" );
            return;
        }

        // Build the note text
        $note = sprintf(
            'Order status changed from %s to %s.',
            strtoupper( $old_status ),
            strtoupper( $new_status )
        );

        // Add the note to the order
        $order->add_order_note( $note );
    }

    /**
     * record_payment_info
     * Adds an order note showing who updated the payment, amounts, tip, pay status,
     * and the current WooCommerce order status at time of update.
     */
    public static function record_payment_info(
        $order_id,
        $cash,
        $zelle,
        $venmo,
        $applepay,
        $creditcard,
        $cashapp,
        $tip,
        $payrec_total,
        $order_balance,
        $pay_status,
        $wc_status
    ) {
        // Get the WooCommerce order
        $order = wc_get_order( $order_id );
        if ( ! $order || ! method_exists( $order, 'add_order_note' ) ) {
            error_log("record_payment_info: invalid order object #{$order_id}");
            return;
        }

        // Identify who is saving the payment
        $current_user = wp_get_current_user();
        $username     = $current_user->exists() ? $current_user->user_login : 'Guest/Unknown';

        // Build the multi-line note
        $note_lines = [
            "Payment updated by {$username}:",
            "- Cash: \${$cash}",
            "- Zelle: \${$zelle}",
            "- Venmo: \${$venmo}",
            "- ApplePay: \${$applepay}",
            "- Credit Card: \${$creditcard}",
            "- CashApp: \${$cashapp}",
            "- Tip: \${$tip}",
            "Payment Status: " . strtoupper($pay_status),
            "PayRec Total: \${$payrec_total}",
            "Order Balance: \${$order_balance}",
            "Current WC Status: " . strtoupper($wc_status)
        ];

        $order->add_order_note( implode( "\n", $note_lines ) );
    }
}
