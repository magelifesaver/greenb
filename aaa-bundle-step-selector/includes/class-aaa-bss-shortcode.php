<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-shortcode.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_Shortcode {

    public function __construct() {
        add_shortcode( 'aaa_bss', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

        add_action( 'wp_ajax_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_aaa_bss_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
    }

    public function register_assets() {
        wp_register_style(
            'aaa-bss',
            AAA_BSS_URL . 'assets/css/aaa-bss.css',
            [],
            AAA_BSS_VERSION
        );

        wp_register_script(
            'aaa-bss',
            AAA_BSS_URL . 'assets/js/aaa-bss.js',
            [ 'jquery' ],
            AAA_BSS_VERSION,
            true
        );
    }

    public function render() {
        if ( ! function_exists( 'WC' ) ) {
            return '';
        }

        $settings = AAA_BSS_Settings::get();
        $step1_ids = AAA_BSS_Settings::parse_ids( $settings['step1_product_ids'] );
        $step2_ids = AAA_BSS_Settings::parse_ids( $settings['step2_product_ids'] );

        wp_enqueue_style( 'aaa-bss' );
        wp_enqueue_script( 'aaa-bss' );

        wp_localize_script( 'aaa-bss', 'AAA_BSS', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'aaa_bss_add_to_cart' ),
        ] );

        ob_start();
        ?>
        <div class="aaa-bss" data-step="1">
            <div class="aaa-bss-step aaa-bss-step-1">
                <div class="aaa-bss-title"><?php echo esc_html__( 'Step 1', 'aaa-bss' ); ?></div>
                <div class="aaa-bss-subtitle"><?php echo esc_html__( 'Select your items (up to the maximum).', 'aaa-bss' ); ?></div>
                <?php echo $this->render_product_rows( $step1_ids, 'step1', (int) $settings['step1_max'] ); ?>
                <div class="aaa-bss-actions">
                    <button type="button" class="aaa-bss-btn aaa-bss-next"><?php echo esc_html__( 'Next', 'aaa-bss' ); ?></button>
                </div>
            </div>

            <div class="aaa-bss-step aaa-bss-step-2" style="display:none;">
                <div class="aaa-bss-title"><?php echo esc_html__( 'Step 2', 'aaa-bss' ); ?></div>
                <div class="aaa-bss-subtitle"><?php echo esc_html__( 'Select your free item(s).', 'aaa-bss' ); ?></div>
                <?php echo $this->render_product_rows( $step2_ids, 'step2', (int) $settings['step2_max'] ); ?>
                <div class="aaa-bss-actions">
                    <button type="button" class="aaa-bss-btn aaa-bss-back"><?php echo esc_html__( 'Back', 'aaa-bss' ); ?></button>
                    <button type="button" class="aaa-bss-btn aaa-bss-submit"><?php echo esc_html__( 'Add to Cart', 'aaa-bss' ); ?></button>
                </div>
            </div>

            <div class="aaa-bss-msg" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_product_rows( array $product_ids, $group_key, $max_total ) {
        if ( empty( $product_ids ) ) {
            return '<div class="aaa-bss-empty">' . esc_html__( 'No products configured for this step.', 'aaa-bss' ) . '</div>';
        }

        $html = '<div class="aaa-bss-list" data-group="' . esc_attr( $group_key ) . '" data-max="' . esc_attr( (int) $max_total ) . '">';
        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( ! $product ) {
                continue;
            }

            $html .= '<div class="aaa-bss-row" data-product-id="' . esc_attr( $pid ) . '">';
            $html .= '<div class="aaa-bss-name">' . esc_html( $product->get_name() ) . '</div>';
            $html .= '<div class="aaa-bss-qty"><input type="number" min="0" step="1" value="0" /></div>';
            $html .= '</div>';
        }
        $html .= '<div class="aaa-bss-maxnote">' . sprintf(
            esc_html__( 'Maximum total for this step: %d', 'aaa-bss' ),
            (int) $max_total
        ) . '</div>';
        $html .= '</div>';

        return $html;
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

        $settings = AAA_BSS_Settings::get();
        $allowed_step1 = AAA_BSS_Settings::parse_ids( $settings['step1_product_ids'] );
        $allowed_step2 = AAA_BSS_Settings::parse_ids( $settings['step2_product_ids'] );

        $step1_max = (int) $settings['step1_max'];
        $step2_max = (int) $settings['step2_max'];

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

        $step2_is_free = ( 'yes' === ( $settings['step2_is_free'] ?? 'yes' ) );

        foreach ( $step2 as $pid => $qty ) {
            $cart_item_data = [];
            if ( $step2_is_free ) {
                $cart_item_data['aaa_bss_free'] = true;
            }
            $cart->add_to_cart( (int) $pid, (int) $qty, 0, [], $cart_item_data );
        }

        $redirect = ( 'checkout' === ( $settings['redirect_to'] ?? 'cart' ) ) ? wc_get_checkout_url() : wc_get_cart_url();

        wp_send_json_success( [
            'message' => 'Added to cart.',
            'redirectUrl' => $redirect,
        ] );
    }

    private function sanitize_selection( array $raw, array $allowed_ids ) {
        $out = [];
        foreach ( $raw as $pid => $qty ) {
            $pid = absint( $pid );
            $qty = absint( $qty );
            if ( $pid < 1 || $qty < 1 ) {
                continue;
            }
            if ( ! in_array( $pid, $allowed_ids, true ) ) {
                continue;
            }
            $out[ $pid ] = $qty;
        }
        return $out;
    }

    private function sum_qty( array $selection ) {
        $sum = 0;
        foreach ( $selection as $qty ) {
            $sum += (int) $qty;
        }
        return $sum;
    }
}
