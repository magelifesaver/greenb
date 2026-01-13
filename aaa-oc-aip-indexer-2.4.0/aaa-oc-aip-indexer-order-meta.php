<?php
/**
 * Writes `aip_order_summary` meta for AIP indexing (COMPLETED ORDERS ONLY).
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-order-meta.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_ORDER_META_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_ORDER_META_LOADED', true );

class AAA_OC_AIP_Indexer_Order_Meta {

    private static $ran = [];

    public static function init() {
        // Stop bleeding: only run once the order becomes COMPLETED.
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'sync_meta' ], 10, 1 );
    }

    public static function sync_meta( $order, $data = null ) {
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

        $order_number = $order->get_order_number();
        $created_obj  = $order->get_date_created();
        $created      = $created_obj ? $created_obj->date( 'Y-m-d H:i:s' ) : '';
        $status_name  = wc_get_order_status_name( $order->get_status() );

        $currency     = $order->get_currency();
        $currency_sym = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $currency ) : '$';
        $total_str    = $currency_sym . number_format( (float) $order->get_total(), 2 );

        $paid_obj      = $order->get_date_paid();
        $paid_str      = $paid_obj ? $paid_obj->date( 'Y-m-d H:i:s' ) : '';
        $completed_obj = $order->get_date_completed();
        $completed_str = $completed_obj ? $completed_obj->date( 'Y-m-d H:i:s' ) : '';

        $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email = $order->get_billing_email();
        $customer_id    = $order->get_customer_id();

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

        $parts   = [];
        $parts[] = 'Order #' . $order_number;
        if ( $created ) { $parts[] = 'Created: ' . $created; }
        $parts[] = 'Status: ' . $status_name;
        $parts[] = 'Total: ' . $total_str;
        if ( $paid_str ) { $parts[] = 'Paid: ' . $paid_str; }
        if ( $completed_str ) { $parts[] = 'Completed: ' . $completed_str; }
        if ( $customer_name ) { $parts[] = 'Customer: ' . $customer_name; }
        if ( $customer_email ) { $parts[] = 'Email: ' . $customer_email; }
        if ( $customer_id ) { $parts[] = 'ID: ' . $customer_id; }

        $lat  = get_post_meta( $id, 'aaa_oc_latitude', true );
        $long = get_post_meta( $id, 'aaa_oc_longitude', true );
        if ( $lat && $long ) { $parts[] = 'Coords: ' . $lat . ', ' . $long; }

        $del_date  = get_post_meta( $id, 'delivery_date_formatted', true );
        $del_range = get_post_meta( $id, 'delivery_time_range', true );
        if ( $del_date ) {
            $parts[] = 'Delivery: ' . $del_date . ( $del_range ? ' ' . $del_range : '' );
        }

        if ( $payment_parts ) {
            $parts[] = 'Payment: ' . implode( '; ', $payment_parts );
        }

        $summary  = implode( ' | ', $parts );
        $existing = (string) get_post_meta( $id, 'aip_order_summary', true );

        if ( $existing !== $summary ) {
            update_post_meta( $id, 'aip_order_summary', $summary );
        }

        if ( defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) && AAA_OC_AIP_INDEXER_LOGGING_ENABLED ) {
            error_log( '[ORDER META] Updated summary for order ' . $order_number );
        }
    }
}

AAA_OC_AIP_Indexer_Order_Meta::init();
