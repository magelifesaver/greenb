<?php
/**
 * Plugin Name: Cart & Promotion Minimum Notice Manager
 * Description: Displays unified notices for the store‑wide minimum cart amount and promotion‑specific minimums on the cart and checkout pages. Removes the default minimum purchase notice from the "Minimum Purchase Amount For WooCommerce" plugin to avoid duplicate messaging. Adjusts the minimum required amount when specified coupons are present.
 * Version: 1.0.0
 * Author: AI Assistant
 * License: GPL2.0+
 */

defined( 'ABSPATH' ) || exit;

// Configure the general minimum cart total and the promotion threshold.
define( 'CMT_GENERAL_MIN_TOTAL', 35 );
define( 'CMT_PROMO_MIN_TOTAL', 50 );

// List of coupon codes that qualify for the promotion. Case‑insensitive.
define( 'CMT_PROMO_COUPON_CODES', serialize( array( 'cbx20', 'cbx-20', 'cbxdiscount' ) ) );

/**
 * Display custom notices for cart minimums and promotion minimums.
 *
 * This function runs on cart/checkout validation and before rendering the cart
 * and checkout forms. It removes any existing minimum purchase notices added
 * by other plugins, calculates remaining amounts for the configured thresholds,
 * and then displays concise messages for the customer. Promotion notices are
 * only shown when qualifying coupon codes are applied.
 */
function cmt_display_cart_and_promo_notices() {
    // Only run when WooCommerce and the cart are available.
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart = WC()->cart;
    $cart_total = $cart->total;

    // Remove minimum purchase notices injected by the minimum cart plugin.
    $messages = WC()->session->get( 'wc_notices', array() );
    if ( ! empty( $messages['error'] ) ) {
        foreach ( $messages['error'] as $key => $message ) {
            // Look for text fragments used by the original plugin's notice.
            if ( strpos( strtolower( $message ), 'minimum amount' ) !== false && strpos( strtolower( $message ), 'add the products' ) !== false ) {
                unset( $messages['error'][ $key ] );
            }
        }
        WC()->session->set( 'wc_notices', $messages );
    }

    // Show general minimum cart notice if total is below the threshold.
    $general_min = floatval( CMT_GENERAL_MIN_TOTAL );
    if ( $general_min > 0 && $cart_total < $general_min ) {
        $missing = $general_min - $cart_total;
        $message = sprintf(
            /* translators: 1: minimum cart amount, 2: remaining amount */
            'The minimum cart total to proceed to checkout is %s. You need to add %s more to your cart.',
            wc_price( $general_min ),
            wc_price( $missing )
        );
        wc_add_notice( $message, 'error' );
    }

    // Determine if a promotion threshold should apply based on coupon codes.
    $promo_codes  = array_map( 'strtolower', maybe_unserialize( CMT_PROMO_COUPON_CODES ) );
    $applied      = array_map( 'strtolower', $cart->get_applied_coupons() );
    $promo_active = (bool) array_intersect( $promo_codes, $applied );

    if ( $promo_active ) {
        $promo_min = floatval( CMT_PROMO_MIN_TOTAL );
        if ( $cart_total < $promo_min ) {
            $missing = $promo_min - $cart_total;
            $message = sprintf(
                /* translators: 1: promotion minimum amount, 2: remaining amount */
                'To qualify for the 20%% CBX discount, your cart must total at least %s. Add %s more of qualifying products.',
                wc_price( $promo_min ),
                wc_price( $missing )
            );
            // Use "notice" instead of "error" so it doesn’t block checkout.
            wc_add_notice( $message, 'notice' );
        }
    }
}

// Execute our notice function at key points.
add_action( 'woocommerce_check_cart_items', 'cmt_display_cart_and_promo_notices', 100 );
add_action( 'woocommerce_before_cart', 'cmt_display_cart_and_promo_notices', 100 );
add_action( 'woocommerce_before_checkout_form', 'cmt_display_cart_and_promo_notices', 100 );