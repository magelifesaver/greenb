<?php
/**
 * Payment Indexer: updates the payment index and syncs values into the order.
 *
 * This class rebuilds the `aaa_oc_payment_index` row for an order whenever a
 * payment or tip changes. It derives a human‑readable breakdown string
 * (`epayment_detail`) that includes both payment amounts and tip details,
 * writes it back to the index table, then mirrors it into order meta for
 * display. It never alters the schema of either index table.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Allow a per‑file debug constant to toggle verbose logging without polluting
// global debug flags. When false, no logs are emitted from this file.
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
    define( 'DEBUG_THIS_FILE', false );
}

class AAA_OC_Payment_Indexer {

    /**
     * Register hooks that trigger payment reindexing.
     */
    public static function init() {
        add_action( 'save_post_shop_order', [ __CLASS__, 'sync_payment_totals' ], 20 );
        add_action( 'updated_post_meta', [ __CLASS__, 'maybe_sync_on_meta_change' ], 10, 4 );
    }

    /**
     * Watch for tip meta updates and reindex when `_wpslash_tip` changes.
     */
    public static function maybe_sync_on_meta_change( $meta_id, $object_id, $meta_key, $_meta_value ) {
        if ( $meta_key === '_wpslash_tip' && get_post_type( $object_id ) === 'shop_order' ) {
            self::sync_payment_totals( $object_id );
        }
    }

    /**
     * Recompute and persist payment fields for the given order.
     *
     * @param int $post_id Order ID being saved or updated.
     */
    public static function sync_payment_totals( $post_id ) {
        // Skip autosaves and revisions
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        $order = wc_get_order( $post_id );
        if ( ! $order ) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'aaa_oc_payment_index';

        // Pull basics from the Woo order and meta
        $order_total     = (float) $order->get_total();
        $order_subtotal  = (float) $order->get_subtotal();
        $driver_id       = get_post_meta( $post_id, 'lddfw_driverid', true );
        $original_method = get_post_meta( $post_id, '_payment_method', true );
        $checkout_tip    = (float) get_post_meta( $post_id, '_wpslash_tip', true );

        // Update core columns on the payment index row; create row if needed later
        $wpdb->update( $table, [
            'aaa_oc_order_total'      => $order_total,
            'subtotal'                => $order_subtotal,
            'driver_id'               => $driver_id ?: null,
            'original_payment_method' => $original_method ?: '',
            'aaa_oc_tip_total'        => $checkout_tip,
        ], [ 'order_id' => $post_id ], [ '%f','%f','%s','%s','%f' ], [ '%d' ] );

        // Ensure a row exists in the payment index table
        $payment_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE order_id = %d", $post_id ), ARRAY_A );
        if ( ! $payment_row ) {
            $wpdb->insert( $table, [ 'order_id' => $post_id ] );
            $payment_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE order_id = %d", $post_id ), ARRAY_A );
            if ( ! $payment_row ) {
                if ( function_exists( 'aaa_oc_log' ) ) {
                    aaa_oc_log( '[AAA_OC][PaymentIndexer] ❌ No payment row for order #' . $post_id );
                }
                return;
            }
        }

        // Pull existing computed values
        $epayment_tip    = isset( $payment_row['epayment_tip'] )         ? (float) $payment_row['epayment_tip']         : 0;
        $total_order_tip = isset( $payment_row['total_order_tip'] )      ? (float) $payment_row['total_order_tip']      : ( $checkout_tip + $epayment_tip );
        $order_balance   = isset( $payment_row['aaa_oc_order_balance'] ) ? (float) $payment_row['aaa_oc_order_balance'] : 0;

        // Build human‑readable breakdown
        $methods = [
            'aaa_oc_zelle_amount'      => 'Zelle',
            'aaa_oc_cash_amount'       => 'Cash',
            'aaa_oc_venmo_amount'      => 'Venmo',
            'aaa_oc_applepay_amount'   => 'ApplePay',
            'aaa_oc_cashapp_amount'    => 'CashApp',
            'aaa_oc_creditcard_amount' => 'Credit Card',
        ];
        $details = [];
        foreach ( $methods as $key => $label ) {
            $amt = isset( $payment_row[ $key ] ) ? (float) $payment_row[ $key ] : 0;
            if ( $amt > 0 ) {
                $details[] = $label . ': $' . number_format( $amt, 2 );
            }
        }
        // Append tips with explicit labels containing "Tip:" so the tip sync patch does not append again
        if ( $checkout_tip > 0 ) {
            $details[] = 'Checkout Tip: $' . number_format( $checkout_tip, 2 );
        }
        if ( $epayment_tip > 0 ) {
            $details[] = 'Excess Tip: $' . number_format( $epayment_tip, 2 );
        }
        if ( $total_order_tip > 0 ) {
            $details[] = 'Total Tip: $' . number_format( $total_order_tip, 2 );
        }
        $epayment_detail = implode( ', ', $details );

        // Derive the real payment method based on non‑zero amounts
        $amount_map = [
            'Zelle'       => isset( $payment_row['aaa_oc_zelle_amount'] )      ? (float) $payment_row['aaa_oc_zelle_amount']      : 0,
            'Cash'        => isset( $payment_row['aaa_oc_cash_amount'] )       ? (float) $payment_row['aaa_oc_cash_amount']       : 0,
            'Venmo'       => isset( $payment_row['aaa_oc_venmo_amount'] )      ? (float) $payment_row['aaa_oc_venmo_amount']      : 0,
            'ApplePay'    => isset( $payment_row['aaa_oc_applepay_amount'] )   ? (float) $payment_row['aaa_oc_applepay_amount']   : 0,
            'CashApp'     => isset( $payment_row['aaa_oc_cashapp_amount'] )    ? (float) $payment_row['aaa_oc_cashapp_amount']    : 0,
            'Credit Card' => isset( $payment_row['aaa_oc_creditcard_amount'] ) ? (float) $payment_row['aaa_oc_creditcard_amount'] : 0,
        ];
        $nonzero = array_filter( $amount_map, static function ( $v ) { return $v > 0; } );
        if ( count( $nonzero ) === 1 ) {
            $real_payment_method = array_key_first( $nonzero );
        } elseif ( count( $nonzero ) > 1 ) {
            $max = max( $nonzero );
            $real_payment_method = array_search( $max, $nonzero, true );
        } else {
            $real_payment_method = (string) $original_method;
        }

        // Persist computed values back into the payment index
        $wpdb->update( $table, [
            'aaa_oc_order_balance' => $order_balance,
            'epayment_tip'         => $epayment_tip,
            'total_order_tip'      => $total_order_tip,
            'epayment_detail'      => $epayment_detail,
            'real_payment_method'  => $real_payment_method,
        ], [ 'order_id' => $post_id ], [ '%f','%f','%f','%s','%s' ], [ '%d' ] );

        // Mirror the extended detail string into order meta for display (ACP, REST, etc.)
        update_post_meta( $post_id, 'epayment_detail', $epayment_detail );

        if ( defined( 'DEBUG_THIS_FILE' ) && DEBUG_THIS_FILE ) {
            error_log( '[AAA_OC][PaymentIndexer] Updated payment index for order #' . $post_id . ' with detail: ' . $epayment_detail );
        }
    }
}

// Kick off hook registration
AAA_OC_Payment_Indexer::init();