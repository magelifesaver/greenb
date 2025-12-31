<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_Payment_Modal {
    public static function render_button_and_modal( $order_id ) {
                <!-- Add Payment -->
                <button type="button" class="button-modern open-payment-modal"
                    data-order-id="<?php echo esc_attr( $order_id ); ?>">
                    Add <br>Payment
                </button>
        </div>
        <?php
        return ob_get_clean();
    }
}
