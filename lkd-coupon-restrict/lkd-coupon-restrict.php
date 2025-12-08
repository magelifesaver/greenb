<?php
/**
 * Plugin Name: AAA LKD Coupon Restrict
 * Plugin URI: https://lokeydelivery.com/
 * Description: Restricts the coupon 'ftp30' to users' first orders only and ensures it applies only to products without promotions.
 * Version: 1.8
 * Author: WebMaster
 * Author URI: https://lokeydelivery.com/
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Function to check if a product is discounted by your promotions plugin.
 * Replace with your actual function/meta key from your promotions plugin.
 */
function is_product_discounted_by_promo($product_id) {
    $promo_applied = get_post_meta($product_id, '_your_promo_plugin_meta_key', true); 
    return !empty($promo_applied); // Returns true if promo is applied
}

/**
 * Validate 'ftp30' coupon AFTER WooCommerce calculates totals.
 */
function validate_ftp30_coupon_after_cart_calculations() {
    if ( ! WC()->cart ) {
        return;
    }

    $cart = WC()->cart->get_cart();
    $coupon_code = 'ftp30';
    $has_eligible_product = false; // Tracks if there's at least one non-discounted product

    // Check if coupon is applied
    if ( WC()->cart->has_discount( $coupon_code ) ) {
        foreach ( $cart as $cart_item ) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            $cart_item_price = (float) $cart_item['line_total'] / $cart_item['quantity'];

            // Detects if the product is discounted (via sale price, promotion, or cart discounts)
            $is_discounted = (
                ( $sale_price > 0 && $sale_price < $regular_price ) ||  // On sale?
                is_product_discounted_by_promo($product_id) ||           // Custom promo applied?
                $cart_item_price < $regular_price                        // Cart discount applied?
            );

            // If at least one product is NOT discounted, allow the coupon
            if ( ! $is_discounted ) {
                $has_eligible_product = true;
                break; // Stop checking once we find a valid product
            }
        }

        // If ALL products are discounted, remove the coupon
        if ( ! $has_eligible_product ) {
            WC()->cart->remove_coupon( $coupon_code );
            wc_add_notice( 'The coupon "ftp30" cannot be applied because all products in your cart already have a discount or promotion applied.', 'error' );
        }
    }
}

// Run this validation AFTER WooCommerce applies all discounts
add_action( 'woocommerce_after_calculate_totals', 'validate_ftp30_coupon_after_cart_calculations', 10, 1 );
