<?php
/**
 * Plugin Name: AAA Coupon Error Notice
 * Description: Adds a custom "Error Notice" field to coupons and displays it when a coupon is no longer valid.
 * Version: 1.0.0
 * Author: Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) exit;
const DEBUG_THIS_FILE = true;

/**
 * Add custom field to coupon edit page
 */
add_action( 'woocommerce_coupon_options', function( $coupon_id ) {
    woocommerce_wp_textarea_input( array(
        'id'          => 'coupon_error_notice',
        'label'       => __( 'Error Notice', 'aaa-coupon-error' ),
        'placeholder' => __( 'Enter custom message for when this coupon is invalid or expired.', 'aaa-coupon-error' ),
        'description' => __( 'Shown to customers if they attempt to use this coupon after it becomes invalid.', 'aaa-coupon-error' ),
        'value'       => get_post_meta( $coupon_id, 'coupon_error_notice', true ),
        'rows'        => 3,
    ) );
});

/**
 * Save custom field
 */
add_action( 'woocommerce_coupon_options_save', function( $coupon_id ) {
    if ( isset( $_POST['coupon_error_notice'] ) ) {
        update_post_meta( $coupon_id, 'coupon_error_notice', sanitize_textarea_field( $_POST['coupon_error_notice'] ) );
    }
});

/**
 * Display custom notice when coupon is invalid or expired
 */
add_filter( 'woocommerce_coupon_error', function( $err, $err_code, $coupon ) {
    if ( ! $coupon instanceof WC_Coupon ) return $err;

    $custom_msg = get_post_meta( $coupon->get_id(), 'coupon_error_notice', true );

    if ( DEBUG_THIS_FILE ) {
        error_log( "[AAA-COUPON] Error code: $err_code | Coupon: " . $coupon->get_code() );
    }

    // Replace default message with custom one only if set and coupon is invalid
    if ( ! empty( $custom_msg ) && in_array( $err_code, array( 100, 101, 102, 103, 105 ), true ) ) {
        return wp_kses_post( $custom_msg );
    }

    return $err;
}, 10, 3 );
