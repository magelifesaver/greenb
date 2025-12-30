<?php
/**
 * Order meta synchronisation for the AAA OC AIP Indexer Bridge.
 *
 * Copies select order properties and meta values into public keys so they can
 * be indexed by the AIP plugin. Builds a single summary field and skips
 * zero payment amounts. Keep this file under 150 lines.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_ORDER_META_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_ORDER_META_LOADED', true );

// Local debug constant for this module.
if ( ! defined( 'AAA_OC_AIP_INDEXER_ORDER_META_DEBUG' ) ) {
    define( 'AAA_OC_AIP_INDEXER_ORDER_META_DEBUG', true );
}

/**
 * Handles copying order properties and meta to public meta keys and
 * constructing a summary for indexing.
 */
class AAA_OC_AIP_Indexer_Order_Meta {

    /**
     * Bootstraps the hooks for order meta synchronisation.
     */
    public static function init() {
        // Use the WooCommerce hook that fires when an order is updated. It
        // receives a WC_Order object. Priority 10 ensures it runs after
        // WooCommerce has saved metadata.
        add_action( 'woocommerce_update_order', [ __CLASS__, 'sync_meta' ] );
    }

    /**
     * Copies key order data into public meta fields and creates a summary.
     *
     * @param int|\WC_Order $order Order ID or object being updated.
     */
    public static function sync_meta( $order ) {
        // Ensure we have a WC_Order object.
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        $order_id = $order->get_id();

        // Copy paid and completed dates into public meta keys.
        $date_paid      = $order->get_date_paid();
        $date_completed = $order->get_date_completed();
        if ( $date_paid ) {
            update_post_meta( $order_id, 'date_paid', $date_paid->date( 'Y-m-d H:i:s' ) );
        }
        if ( $date_completed ) {
            update_post_meta( $order_id, 'date_completed', $date_completed->date( 'Y-m-d H:i:s' ) );
        }

        // Copy customer billing and shipping details to public meta keys.
        $fields = [
            'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1',
            'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode',
            'billing_phone', 'billing_email', 'shipping_first_name', 'shipping_last_name',
            'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_state', 'shipping_postcode'
        ];
        foreach ( $fields as $field ) {
            $value = $order->get_meta( '_' . $field, true );
            if ( empty( $value ) ) {
                $value = $order->get_meta( $field, true );
            }
            if ( ! empty( $value ) ) {
                update_post_meta( $order_id, $field, wp_kses_post( $value ) );
            }
        }

        // Copy payment amounts if they exist and are greater than zero.
        $payment_keys = [
            'aaa_oc_creditcard_amount' => 'creditcard_amount',
            'aaa_oc_cash_amount'       => 'cash_amount',
            'aaa_oc_cashapp_amount'    => 'cashapp_amount',
            'aaa_oc_venmo_amount'      => 'venmo_amount',
            'aaa_oc_zelle_amount'      => 'zelle_amount',
            'aaa_oc_applepay_amount'   => 'applepay_amount',
        ];
        $payment_summary_parts = [];
        foreach ( $payment_keys as $private_key => $public_key ) {
            $amount = (float) get_post_meta( $order_id, $private_key, true );
            if ( $amount > 0 ) {
                update_post_meta( $order_id, $public_key, $amount );
                $payment_summary_parts[] = ucfirst( str_replace( '_amount', '', str_replace( 'aaa_oc_', '', $private_key ) ) ) . ': ' . wc_price( $amount );
            } else {
                // Remove existing public meta if the amount is zero.
                delete_post_meta( $order_id, $public_key );
            }
        }

        // Build a concise order summary for indexing.
        $created  = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
        $status   = wc_get_order_status_name( $order->get_status() );
        $total    = $order->get_total();
        $summary  = [];
        $summary[] = 'Order #' . $order->get_order_number();
        if ( $created ) {
            $summary[] = 'Created: ' . $created;
        }
        $summary[] = 'Status: ' . $status;
        $summary[] = 'Total: ' . wc_price( $total );
        if ( $date_paid ) {
            $summary[] = 'Paid: ' . $date_paid->date( 'Y-m-d H:i:s' );
        }
        if ( $date_completed ) {
            $summary[] = 'Completed: ' . $date_completed->date( 'Y-m-d H:i:s' );
        }
        // Customer contact details.
        $billing_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $billing_email = $order->get_billing_email();
        if ( $billing_name ) {
            $summary[] = 'Customer: ' . $billing_name;
        }
        if ( $billing_email ) {
            $summary[] = 'Email: ' . $billing_email;
        }
        // Payment summary.
        if ( ! empty( $payment_summary_parts ) ) {
            $summary[] = 'Payment: ' . implode( '; ', $payment_summary_parts );
        }
        // Combine into a single string.
        $summary_text = implode( ' | ', $summary );
        update_post_meta( $order_id, 'aip_order_summary', $summary_text );

        // Debug logging.
        if ( AAA_OC_AIP_INDEXER_ORDER_META_DEBUG ) {
            error_log( '[AIP Order Meta] Synced order #' . $order->get_order_number() );
        }
    }
}

// Initialize the order meta synchronisation.
AAA_OC_AIP_Indexer_Order_Meta::init();