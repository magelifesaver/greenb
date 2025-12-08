<?php
/**
 * Plugin Name: LKU LKD Redirect to Checkout for Memberships
 * Plugin URI: http://yourwebsite.com/
 * Description: Redirects directly to checkout when a membership product is added to the cart. Ensures only products in the "memberships" category trigger this redirect.
 * Version: 1.0.1
 * Author: LKD
 * Author URI: http://yourwebsite.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Redirect to checkout on adding a membership product to the cart
function lku_redirect_to_checkout_on_membership_add_to_cart( $url ) {
    if ( isset( $_REQUEST['add-to-cart'] ) && is_numeric( $_REQUEST['add-to-cart'] ) ) {
        $product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
        $product = wc_get_product( $product_id );
        
        // Proceed if product exists and check for the "memberships" category
        if ( $product && has_term( 'memberships', 'product_cat', $product_id ) ) {
            $url = wc_get_checkout_url();
        }
    }

    return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'lku_redirect_to_checkout_on_membership_add_to_cart' );

// Plugin activation hook
function lku_redirect_checkout_activate() {
    // Activation logic here
}
register_activation_hook( __FILE__, 'lku_redirect_checkout_activate' );

// Plugin deactivation hook
function lku_redirect_checkout_deactivate() {
    // Deactivation logic here
}
register_deactivation_hook( __FILE__, 'lku_redirect_checkout_deactivate' );
