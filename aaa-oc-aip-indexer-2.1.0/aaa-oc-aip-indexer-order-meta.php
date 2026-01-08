<?php
/**
 * Order meta synchronisation for the AAA OC AIP Indexer Bridge.
 *
 * Regenerates a plain‑text summary for each WooCommerce order.  The summary
 * includes key information such as order number, creation date, status,
 * amounts, payment dates, customer name/email/ID and a concise payment
 * breakdown.  This single `aip_order_summary` meta field is used by the
 * AIP plugin when building vector content.  No other public meta keys
 * are written by this module to avoid duplication.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-order-meta.php
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
 * Class responsible for generating a summary meta field for orders.
 *
 * This class hooks into both order creation and update events to ensure
 * `aip_order_summary` is present on every order.  The summary string is
 * plain text; no HTML markup is included.  Payment totals are shown
 * only for methods with non‑zero amounts and are formatted with the
 * order’s currency symbol.
 */
class AAA_OC_AIP_Indexer_Order_Meta {

    /**
     * Bootstraps the hooks for order meta synchronisation.
     *
     * We hook into `woocommerce_checkout_create_order` so the summary is
     * written at creation time, and `woocommerce_update_order` so it is
     * regenerated whenever an order is updated.
     */
    public static function init() {
        // Fires when a new order is created during checkout.
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'sync_meta' ], 10, 2 );
        // Fires when an existing order is updated.
        add_action( 'woocommerce_update_order', [ __CLASS__, 'sync_meta' ], 10, 1 );
    }

    /**
     * Generates a plain‑text summary and saves it as meta.
     *
     * @param int|\WC_Order $order Order ID or object being saved.
     * @param array|null     $data  Unused when called from checkout.
     */
    public static function sync_meta( $order, $data = null ) {
        // Always work with a WC_Order object.
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        $id = $order->get_id();

        // Basic values.
        $order_number = $order->get_order_number();
        $created_obj  = $order->get_date_created();
        $created      = $created_obj ? $created_obj->date( 'Y-m-d H:i:s' ) : '';
        $status_name  = wc_get_order_status_name( $order->get_status() );
        $total        = (float) $order->get_total();
        $currency     = $order->get_currency();
        $currency_sym = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $currency ) : '';
        $total_str    = $currency_sym . number_format( $total, 2 );
        $paid_obj     = $order->get_date_paid();
        $paid_str     = $paid_obj ? $paid_obj->date( 'Y-m-d H:i:s' ) : '';
        $completed_obj = $order->get_date_completed();
        $completed_str = $completed_obj ? $completed_obj->date( 'Y-m-d H:i:s' ) : '';

        // Customer details.
        $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email = $order->get_billing_email();
        $customer_id    = $order->get_customer_id();

        // Payment summary: map private keys to friendly labels.
        $payment_keys = [
            'aaa_oc_creditcard_amount' => 'CreditCard',
            'aaa_oc_cash_amount'       => 'Cash',
            'aaa_oc_cashapp_amount'    => 'CashApp',
            'aaa_oc_venmo_amount'      => 'Venmo',
            'aaa_oc_zelle_amount'      => 'Zelle',
            'aaa_oc_applepay_amount'   => 'ApplePay',
        ];
        $payment_parts = [];
        foreach ( $payment_keys as $private_key => $label ) {
            $amt = (float) get_post_meta( $id, $private_key, true );
            if ( $amt > 0 ) {
                $payment_parts[] = $label . ': ' . $currency_sym . number_format( $amt, 2 );
            }
        }

        // Compose the summary.
        $parts = [];
        $parts[] = 'Order #' . $order_number;
        if ( $created ) {
            $parts[] = 'Created: ' . $created;
        }
        $parts[] = 'Status: ' . $status_name;
        $parts[] = 'Total: ' . $total_str;
        if ( $paid_str ) {
            $parts[] = 'Paid: ' . $paid_str;
        }
        if ( $completed_str ) {
            $parts[] = 'Completed: ' . $completed_str;
        }
        if ( $customer_name ) {
            $parts[] = 'Customer: ' . $customer_name;
        }
        if ( $customer_email ) {
            $parts[] = 'Email: ' . $customer_email;
        }
        if ( $customer_id ) {
            $parts[] = 'ID: ' . $customer_id;
        }
        // Include delivery coordinates if present.  These values are stored
        // on the order by the delivery module.  Including them helps
        // dispatch and logistics queries.  We only append coordinates
        // when both latitude and longitude are non‑empty.
        $lat  = get_post_meta( $id, 'aaa_oc_latitude', true );
        $long = get_post_meta( $id, 'aaa_oc_longitude', true );
        if ( $lat && $long ) {
            $parts[] = 'Coords: ' . $lat . ', ' . $long;
        }
        // Include delivery date and time range if present.  These fields
        // come from the delivery plugin and are stored on the order.
        $del_date  = get_post_meta( $id, 'delivery_date_formatted', true );
        $del_range = get_post_meta( $id, 'delivery_time_range', true );
        if ( $del_date ) {
            $str = 'Delivery: ' . $del_date;
            if ( $del_range ) {
                $str .= ' ' . $del_range;
            }
            $parts[] = $str;
        }
        if ( ! empty( $payment_parts ) ) {
            $parts[] = 'Payment: ' . implode( '; ', $payment_parts );
        }
        $summary = implode( ' | ', $parts );

        // Save the summary.  Delete any previous meta to ensure no duplicates.
        update_post_meta( $id, 'aip_order_summary', $summary );

        // Optionally log for debugging.
        if ( AAA_OC_AIP_INDEXER_ORDER_META_DEBUG ) {
            error_log( '[ORDER META] Updated summary for order ' . $order_number );
        }
    }
}

// Initialise the order meta synchronisation.
AAA_OC_AIP_Indexer_Order_Meta::init();