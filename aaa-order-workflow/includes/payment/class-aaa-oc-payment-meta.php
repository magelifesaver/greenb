<?php
/**
 * File Path: /aaa-order-workflow/includes/payment/class-aaa-oc-payment-meta.php
 *
 * Purpose:
 * This file registers all payment-related post meta keys used in the order system.
 * By registering these with WordPress, we unlock the ability for:
 * - Admin Columns Pro (ACP) to detect and display them
 * - REST API tools to access them
 * - Post meta queries to work reliably (`meta_query`, etc.)
 *
 * Note: These registrations do not save data. They just declare type, visibility, and structure.
 *
 * Version: 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Meta {
    /**
     * Hook into WordPress init so meta fields are registered early.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_payment_meta' ] );
    }

    /**
     * Register all payment‑related meta keys for WooCommerce orders.
     *
     * Numeric fields are registered as type 'number' and single,
     * with REST visibility. The payment status includes a sanitize
     * callback to restrict allowed values.
     */
    public static function register_payment_meta() {
        // Payment status: enforce allowed values via sanitize callback.
        register_post_meta( 'shop_order', 'aaa_oc_payment_status', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => function ( $value ) {
                return in_array( $value, [ 'unpaid', 'partial', 'paid' ], true ) ? $value : 'unpaid';
            },
        ] );

        // Numeric amount fields stored on the order record.
        $amount_fields = [
            'aaa_oc_cash_amount',
            'aaa_oc_zelle_amount',
            'aaa_oc_venmo_amount',
            'aaa_oc_cashapp_amount',
            'aaa_oc_applepay_amount',
            'aaa_oc_creditcard_amount',
            'aaa_oc_epayment_total',
            'aaa_oc_payrec_total',
            'aaa_oc_order_balance',
            'aaa_oc_tip_total',
            'epayment_tip',
            'total_order_tip',
        ];
        foreach ( $amount_fields as $key ) {
            register_post_meta( 'shop_order', $key, [
                'type'          => 'number',
                'single'        => true,
                'show_in_rest'  => true,
                'auth_callback' => '__return_true',
            ] );
        }

        // Admin notes stored in the index and mirrored to the order.
        register_post_meta( 'shop_order', 'payment_admin_notes', [
            'type'          => 'string',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => '__return_true',
        ] );

        // Consolidated payment detail string (human-readable breakdown with tip info).
        register_post_meta( 'shop_order', 'epayment_detail', [
            'type'          => 'string',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => '__return_true',
        ] );
    }

    /**
     * Optional helper to update the payment status meta only when it changes.
     * Helps prevent redundant writes and unnecessary version bumps.
     *
     * @param int    $order_id  The WooCommerce order ID.
     * @param string $new_status New status (unpaid, partial, or paid).
     */
    public static function maybe_update_payment_status_meta( $order_id, $new_status ) {
        if ( ! $order_id || ! $new_status ) {
            return;
        }
        $current = get_post_meta( $order_id, 'aaa_oc_payment_status', true );
        if ( $new_status !== $current ) {
            update_post_meta( $order_id, 'aaa_oc_payment_status', $new_status );
        }
    }
}
