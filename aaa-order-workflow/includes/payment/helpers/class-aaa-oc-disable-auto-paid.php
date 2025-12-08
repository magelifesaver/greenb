<?php
/**
 * File Path: /aaa-order-workflow/includes/payment/helpers/class-aaa-oc-disable-auto-paid.php
 *
 * Purpose:
 * Stop WooCommerce from auto-marking orders as paid (date_paid).
 * Only Workflow Payment logic should control payment state.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Disable_Auto_Paid {

    /**
     * Toggle logging for this helper.
     */
    private const DEBUG_THIS_FILE = true;

    public static function init() {
        self::log('[AutoPaid] Helper loaded.');

        // Force Woo to treat orders as unpaid unless Workflow says otherwise
        add_filter( 'woocommerce_order_get_date_paid', [ __CLASS__, 'filter_date_paid' ], 10, 2 );

        // Intercept Woo’s payment_complete BEFORE it sets date_paid
        add_action( 'woocommerce_pre_payment_complete', [ __CLASS__, 'block_payment_complete' ], 0 );
    }

    /**
     * Filter Woo’s getter: always return unpaid unless Workflow has marked as paid.
     */
    public static function filter_date_paid( $date_paid, $order ) {
        if ( ! $order instanceof WC_Order ) {
            return $date_paid;
        }

        $workflow_status = get_post_meta( $order->get_id(), 'aaa_oc_payment_status', true );

        if ( $workflow_status === 'paid' ) {
            return $date_paid; // Let Workflow-approved orders show paid date
        }

        // Debug
        self::log("[AutoPaid] Suppressed get_date_paid for order #{$order->get_id()} (status={$order->get_status()}).");

        return null; // Always unpaid unless Workflow says so
    }

    /**
     * Runs BEFORE WooCommerce finalizes payment_complete.
     * Prevents Woo from stamping date_paid automatically.
     */
    public static function block_payment_complete( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $workflow_status = get_post_meta( $order_id, 'aaa_oc_payment_status', true );

        if ( $workflow_status !== 'paid' ) {
            $order->set_date_paid( null );
            $order->save();

            self::log("[AutoPaid] Blocked Woo payment_complete() for order #$order_id.");
        }
    }

    /**
     * Internal logger (respects DEBUG_THIS_FILE).
     */
    private static function log( $msg ) {
        if ( ! self::DEBUG_THIS_FILE ) return;

        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( $msg );
        } else {
            error_log( $msg );
        }
    }
}

AAA_OC_Disable_Auto_Paid::init();
