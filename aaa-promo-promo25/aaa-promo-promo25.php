<?php
/**
 * Plugin Name: AAA Promo - Promo25 (XHV98-PROMO)
 * Description: Creates and applies the "promo25" coupon ($25 off every $100 spent, one-time use).
 * Version: 1.0.2
 * Author: AAA Workflow
 * License: GPL2
 *
 * File Path: wp-content/plugins/aaa-promo-promo25.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Local debug toggle (this file only). */
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
    define( 'DEBUG_THIS_FILE', false );
}

/**
 * Create the "promo25" coupon once (idempotent), using wc_get_coupon_id_by_code().
 * No logs when the coupon already exists (to avoid spam).
 */
function aaa_promo25_create_coupon() {
    if ( ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
        if ( DEBUG_THIS_FILE ) error_log( '[aaa-promo25] WooCommerce not active; skipping creation.' );
        return;
    }

    $code      = 'promo25';
    $coupon_id = wc_get_coupon_id_by_code( $code );

    if ( $coupon_id ) {
        // Quiet: do nothing if it already exists.
        return;
    }

    // Create a placeholder coupon (discount handled dynamically via fee).
    $coupon = new WC_Coupon();
    $coupon->set_code( $code );
    $coupon->set_description( '$25 off every $100 spent' );
    $coupon->set_discount_type( 'fixed_cart' );
    $coupon->set_amount( 0 );
    $coupon->set_usage_limit( 1 );
    $coupon->set_usage_limit_per_user( 1 );
    $coupon->set_individual_use( true );
    $coupon->set_free_shipping( false );
    $coupon->save();

    if ( DEBUG_THIS_FILE ) error_log( '[aaa-promo25] Created coupon "promo25" (ID ' . $coupon->get_id() . ').' );
}
add_action( 'init', 'aaa_promo25_create_coupon' );

/** Apply dynamic $25 per $100 logic when "promo25" is applied. */
function aaa_promo25_apply_discount( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( ! $cart || ! method_exists( $cart, 'has_discount' ) ) return;
    if ( ! $cart->has_discount( 'promo25' ) ) return;

    $subtotal       = (float) $cart->get_subtotal();
    $units          = (int) floor( $subtotal / 100 );
    $discount_total = $units * 25;

    if ( $discount_total > 0 ) {
        $cart->add_fee( 'Promo Discount (promo25)', -$discount_total );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'aaa_promo25_apply_discount', 20 );

/** Hide WC coupon row for promo25 since we use a fee for the actual discount. */
function aaa_promo25_hide_coupon_label( $label, $coupon ) {
    return ( strtolower( $coupon->get_code() ) === 'promo25' ) ? '' : $label;
}
add_filter( 'woocommerce_cart_totals_coupon_label', 'aaa_promo25_hide_coupon_label', 10, 2 );

function aaa_promo25_hide_coupon_html( $html, $coupon, $discount_amount_html ) {
    return ( strtolower( $coupon->get_code() ) === 'promo25' ) ? '' : $html;
}
add_filter( 'woocommerce_cart_totals_coupon_html', 'aaa_promo25_hide_coupon_html', 10, 3 );

// END PROMO25
