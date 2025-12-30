<?php
/**
 * Register payment‑related meta keys for WooCommerce orders.
 *
 * This class declares all post meta used by the AAA Order Workflow payment
 * subsystem. Registration exposes them via the REST API (for Admin Columns,
 * API integrations, etc.) but does not save or manipulate any values.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Meta {

    /**
     * Bootstrap meta registration on the `init` hook.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_payment_meta' ] );
    }

    /**
     * Register all payment meta keys with WordPress.
     *
     * Numeric keys are registered as numbers; others are registered as strings.
     */
    public static function register_payment_meta() {
        // Payment status: restrict to allowed values
        register_post_meta( 'shop_order', 'aaa_oc_payment_status', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => function ( $value ) {
                return in_array( $value, [ 'unpaid', 'partial', 'paid' ], true ) ? $value : 'unpaid';
            },
        ] );

        // Numeric payment amount fields
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

        // Internal notes saved by admins
        register_post_meta( 'shop_order', 'payment_admin_notes', [
            'type'          => 'string',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => '__return_true',
        ] );

        // Human‑readable consolidated payment detail summary
        register_post_meta( 'shop_order', 'epayment_detail', [
            'type'          => 'string',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => '__return_true',
        ] );
    }

    /**
     * Helper: update the payment status only if it changed.
     *
     * @param int    $order_id
     * @param string $new_status
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