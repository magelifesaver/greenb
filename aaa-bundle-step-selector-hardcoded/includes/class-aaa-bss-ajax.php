<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-ajax.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_AJAX {

    public function __construct() {
        add_action( 'wp_ajax_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
    }

    public function ajax_add_to_cart() {
        if ( ! function_exists( 'WC' ) ) {
            wp_send_json_error( [ 'message' => 'WooCommerce not available.' ], 400 );
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'aaa_bss_add_to_cart' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 );
        }

        $payload = json_decode( wp_unslash( $_POST['payload'] ?? '' ), true );
        if ( ! is_array( $payload ) ) {
            wp_send_json_error( [ 'message' => 'Invalid payload.' ], 400 );
        }

        $allowed_step1 = AAA_BSS_Config::step1_product_ids();
        $allowed_step2 = AAA_BSS_Config::step2_product_ids();

        $step1_max = (int) AAA_BSS_Config::step1_max();
        $step2_max = (int) AAA_BSS_Config::step2_max();

        $step1 = is_array( $payload['step1'] ?? null ) ? $payload['step1'] : [];
        $step2 = is_array( $payload['step2'] ?? null ) ? $payload['step2'] : [];

        $step1 = $this->sanitize_selection( $step1, $allowed_step1 );
        $step2 = $this->sanitize_selection( $step2, $allowed_step2 );

        if ( $this->sum_qty( $step1 ) > $step1_max ) {
            wp_send_json_error( [ 'message' => 'Step 1 exceeds max.' ], 400 );
        }
        if ( $this->sum_qty( $step2 ) > $step2_max ) {
            wp_send_json_error( [ 'message' => 'Step 2 exceeds max.' ], 400 );
        }
        if ( $this->sum_qty( $step1 ) < 1 ) {
            wp_send_json_error( [ 'message' => 'Select at least 1 item in Step 1.' ], 400 );
        }

        $cart = WC()->cart;
        if ( ! $cart ) {
            wp_send_json_error( [ 'message' => 'Cart not available.' ], 400 );
        }

        foreach ( $step1 as $pid => $qty ) {
            $cart->add_to_cart( (int) $pid, (int) $qty );
        }

        $step2_is_free = (bool) AAA_BSS_Config::step2_is_free();

        foreach ( $step2 as $pid => $qty ) {
            $cart_item_data = [];
            if ( $step2_is_free ) {
                $cart_item_data['aaa_bss_free'] = true;
            }
            $cart->add_to_cart( (int) $pid, (int) $qty, 0, [], $cart_item_data );
        }

        wp_send_json_success( [ 'message' => 'Added to cart.' ] );
    }

    private function sanitize_selection( array $raw, array $allowed_ids ) {
        $out = [];
        foreach ( $raw as $pid => $qty ) {
            $pid = absint( $pid );
            $qty = absint( $qty );
            if ( $pid < 1 || $qty < 1 ) { continue; }
            if ( ! in_array( $pid, $allowed_ids, true ) ) { continue; }
            $out[ $pid ] = $qty;
        }
        return $out;
    }

    private function sum_qty( array $selection ) {
        $sum = 0;
        foreach ( $selection as $qty ) { $sum += (int) $qty; }
        return $sum;
    }
}
