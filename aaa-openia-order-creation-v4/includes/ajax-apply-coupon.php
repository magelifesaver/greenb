<?php
// File: /aaa-openia-order-creation-v4/includes/ajax-apply-coupon.php
// Purpose: Validate coupon codes in Order Creator preview using WooCommerce's real coupon rules.
// Notes: Builds a fake WC_Cart with preview products so that product/category restrictions,
//        min/max spend, usage limits, and customer restrictions are all validated.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('wp_ajax_aaa_v4_apply_coupon', function() {
    $code    = isset($_POST['coupon']) ? sanitize_text_field($_POST['coupon']) : '';
    $cust_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
    $cust_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
    $products = isset($_POST['products']) && is_array($_POST['products']) ? $_POST['products'] : [];

    if ( empty($cust_id) && empty($cust_email) ) {
        wp_send_json_error([ 'message' => 'Please select a customer before applying a coupon.' ]);
    }

    if ( empty($code) ) {
        wp_send_json_error([ 'message' => 'No coupon provided.' ]);
    }

    try {
        $coupon = new WC_Coupon( $code );
        if ( ! $coupon || ! $coupon->get_id() ) {
            wp_send_json_error([ 'message' => 'Invalid coupon code.' ]);
        }

        // ───────────────────────────────────────────────
        // Build fake cart with preview products
        // ───────────────────────────────────────────────
        $cart = new WC_Cart();

        foreach ( $products as $p ) {
            $product_id = intval($p['product_id'] ?? 0);
            $qty        = intval($p['quantity'] ?? 1);
            $price      = floatval($p['special_price'] ?? $p['unit_price'] ?? 0);

            if ( $product_id > 0 && $qty > 0 ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $cart_item_key = $cart->add_to_cart( $product_id, $qty );

                    // Override unit price if "special_price" was set in preview
                    if ( $price > 0 && isset($cart->cart_contents[ $cart_item_key ]['data']) ) {
                        $cart->cart_contents[ $cart_item_key ]['data']->set_price( $price );
                    }
                }
            }
        }

        // ───────────────────────────────────────────────
        // Run coupon validation
        // ───────────────────────────────────────────────
        $discounts = new WC_Discounts( $cart );
        $valid     = $discounts->is_coupon_valid( $coupon );

        if ( is_wp_error( $valid ) ) {
            // Log exact error code + message for debugging
            if ( class_exists('AAA_V4_Logger') ) {
                AAA_V4_Logger::log(
                    "❌ Coupon '{$code}' failed validation: " .
                    $valid->get_error_code() . ' - ' . $valid->get_error_message()
                );
            }

            wp_send_json_error([
                'message' => $valid->get_error_message(),
                'code'    => $valid->get_error_code(),
            ]);
        }

        // ───────────────────────────────────────────────
        // Return coupon info if valid
        // ───────────────────────────────────────────────
        $amount = $coupon->get_amount();
        $type   = $coupon->get_discount_type();
        $desc   = $coupon->get_description();

        if ( class_exists('AAA_V4_Logger') ) {
            AAA_V4_Logger::log("✅ Coupon '{$code}' validated successfully for preview.");
        }

        wp_send_json_success([
            'code'   => $coupon->get_code(),
            'amount' => $amount,
            'type'   => $type,
            'desc'   => $desc,
        ]);

    } catch ( Exception $e ) {
        if ( class_exists('AAA_V4_Logger') ) {
            AAA_V4_Logger::log("❌ Exception validating coupon '{$code}': " . $e->getMessage());
        }
        wp_send_json_error([ 'message' => $e->getMessage() ]);
    }
});
