<?php
/**
 * Class: AAA_OC_Payment_Indexer
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexers/class-aaa-oc-payment-indexer.php
 * Purpose: Updates the payment index table with accurate totals and payment detail,
 *          then syncs key fields into the order index table (e.g. balance, tip, payment breakdown).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Local debug toggle (guard any noisy logs with this).
 * Set to true only when troubleshooting this file specifically.
 */
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
    define( 'DEBUG_THIS_FILE', false );
}

class AAA_OC_Payment_Indexer {

    /**
     * Initialize hooks to trigger payment index sync.
     */
    public static function init() {
        // Run after manual updates or backend order saves
        add_action( 'save_post_shop_order', [ __CLASS__, 'sync_payment_totals' ], 20 );

        // Also trigger when _wpslash_tip is updated directly
        add_action( 'updated_post_meta', [ __CLASS__, 'maybe_sync_on_meta_change' ], 10, 4 );
    }

    /**
     * If tip meta is changed, re-sync payment totals
     */
    public static function maybe_sync_on_meta_change( $meta_id, $object_id, $meta_key, $_meta_value ) {
        if ( $meta_key === '_wpslash_tip' && get_post_type( $object_id ) === 'shop_order' ) {
            self::sync_payment_totals( $object_id );
        }
    }

    /**
     * Sync all payment fields for a given order ID
     */
    public static function sync_payment_totals( $post_id ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;

        $order = wc_get_order( $post_id );
        if ( ! $order ) return;

        global $wpdb;
        $table = $wpdb->prefix . 'aaa_oc_payment_index';

        $order_total     = (float) $order->get_total();
        $order_subtotal  = (float) $order->get_subtotal();
        $driver_id       = get_post_meta( $post_id, 'lddfw_driverid', true );
        $original_method = get_post_meta( $post_id, '_payment_method', true );
        $checkout_tip    = (float) get_post_meta( $post_id, '_wpslash_tip', true );

        // Update core values from order/meta (keep as-is)
        $wpdb->update(
            $table,
            [
                'aaa_oc_order_total'      => $order_total,
                'subtotal'                => $order_subtotal,
                'driver_id'               => $driver_id ?: null,
                'original_payment_method' => $original_method ?: '',
                'aaa_oc_tip_total'        => $checkout_tip,
            ],
            [ 'order_id' => $post_id ],
            [ '%f', '%f', '%s', '%s', '%f' ],
            [ '%d' ]
        );

        // Ensure a row exists
        $payment_row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE order_id = %d", $post_id ),
            ARRAY_A
        );
        if ( ! $payment_row ) {
            $wpdb->insert( $table, [ 'order_id' => $post_id ] );
            $payment_row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM $table WHERE order_id = %d", $post_id ),
                ARRAY_A
            );
            if ( ! $payment_row ) {
                // Keep existing project logger untouched
                aaa_oc_log("[AAA_OC][PaymentIndexer] ❌ No row in payment_index for order #$post_id after insert.");
                return;
            }
        }

        // Pull computed values that may already be present
        $epayment_tip    = isset( $payment_row['epayment_tip'] ) ? (float) $payment_row['epayment_tip'] : 0;
        $total_order_tip = isset( $payment_row['total_order_tip'] ) ? (float) $payment_row['total_order_tip'] : ( $checkout_tip + $epayment_tip );
        $order_balance   = isset( $payment_row['aaa_oc_order_balance'] ) ? (float) $payment_row['aaa_oc_order_balance'] : 0;

        // Build human-readable breakdown (unchanged)
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
                $details[] = "{$label}: $" . number_format( $amt, 2 );
            }
        }
        if ( $checkout_tip > 0 ) {
            $details[] = 'Tip: $' . number_format( $checkout_tip, 2 );
        }
        $epayment_detail = implode( ', ', $details );

        // Determine real_payment_method from non-zero amounts
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

        // Final update (add real_payment_method only)
        $result = $wpdb->update(
            $table,
            [
                'aaa_oc_order_balance' => $order_balance,
                'epayment_tip'         => $epayment_tip,
                'total_order_tip'      => $total_order_tip,
                'epayment_detail'      => $epayment_detail,
                'real_payment_method'  => $real_payment_method,
            ],
            [ 'order_id' => $post_id ],
            [ '%f', '%f', '%f', '%s', '%s' ],
            [ '%d' ]
        );

        // Silence bottom logs unless DEBUG_THIS_FILE is true
        if ( defined( 'DEBUG_THIS_FILE' ) && DEBUG_THIS_FILE ) {
            if ( $result !== false ) {
                error_log( "[AAA_OC][PaymentIndexer] ✅ Payment index updated for order #$post_id" );
            } else {
                error_log( "[AAA_OC][PaymentIndexer] ❌ Update failed: " . $wpdb->last_error );
            }
        }
    }
}

AAA_OC_Payment_Indexer::init();
