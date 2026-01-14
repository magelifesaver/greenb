<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-ajax.php
 *
 * v1.1.3:
 * - Enforce Step 2 selection required (min 1).
 * - Provide cart status endpoint so UI can lock after fulfilled and re-open when missing.
 * - Return Woo fragments so header/FastCart updates without refresh.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_AJAX {

    public function __construct() {
        add_action( 'wp_ajax_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );

        add_action( 'wp_ajax_aaa_bss_status', [ $this, 'ajax_status' ] );
        add_action( 'wp_ajax_nopriv_aaa_bss_status', [ $this, 'ajax_status' ] );
    }

    public function ajax_status() {
        if ( ! function_exists( 'WC' ) ) {
            wp_send_json_error( [ 'message' => 'WooCommerce not available.' ], 400 );
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'aaa_bss_add_to_cart' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 );
        }

        $state = $this->get_cart_state();

        wp_send_json_success( [
            'state'     => $state,
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
            'cart_hash' => WC()->cart ? WC()->cart->get_cart_hash() : '',
        ] );
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

        // NEW: Step 2 required (min 1).
        if ( $this->sum_qty( $step2 ) < 1 ) {
            wp_send_json_error( [ 'message' => 'Select 1 item in Step 2.' ], 400 );
        }

        $cart = WC()->cart;
        if ( ! $cart ) {
            wp_send_json_error( [ 'message' => 'Cart not available.' ], 400 );
        }

        foreach ( $step1 as $pid => $qty ) {
            $cart->add_to_cart( (int) $pid, (int) $qty );
        }
        foreach ( $step2 as $pid => $qty ) {
            $cart->add_to_cart( (int) $pid, (int) $qty );
        }

        $state     = $this->get_cart_state();
        $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );
        $cart_hash = WC()->cart->get_cart_hash();
        $cart_count = (int) WC()->cart->get_cart_contents_count();

        aaa_bss_log( 'AJAX add_to_cart ok', [
            'cart_count' => $cart_count,
            'cart_hash'  => $cart_hash,
            'state'      => $state,
        ] );

        wp_send_json_success( [
            'message'    => 'Added to cart.',
            'state'      => $state,
            'fragments'  => $fragments,
            'cart_hash'  => $cart_hash,
            'cart_count' => $cart_count,
        ] );
    }

    private function get_cart_state() {
        $cart = WC()->cart;
        if ( ! $cart ) {
            return [
                'has_step1'      => false,
                'has_step2'      => false,
                'promo_fulfilled'=> false,
                'gift_missing'   => false,
            ];
        }

        $step1_ids = array_map( 'intval', AAA_BSS_Config::step1_product_ids() );
        $step2_ids = array_map( 'intval', AAA_BSS_Config::step2_product_ids() );

        $has_step1 = false;
        $has_step2 = false;

        foreach ( $cart->get_cart() as $item ) {
            $pid = (int) ( $item['product_id'] ?? 0 );
            $vid = (int) ( $item['variation_id'] ?? 0 );

            if ( in_array( $pid, $step1_ids, true ) || in_array( $vid, $step1_ids, true ) ) {
                $has_step1 = true;
            }
            if ( in_array( $pid, $step2_ids, true ) || in_array( $vid, $step2_ids, true ) ) {
                $has_step2 = true;
            }
        }

        $promo_fulfilled = ( $has_step1 && $has_step2 );
        $gift_missing    = ( $has_step1 && ! $has_step2 );

        return [
            'has_step1'       => $has_step1,
            'has_step2'       => $has_step2,
            'promo_fulfilled' => $promo_fulfilled,
            'gift_missing'    => $gift_missing,
        ];
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
