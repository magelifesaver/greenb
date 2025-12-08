<?php
if (!defined('ABSPATH')) exit;

class AAA_OC_Payment_Modal {

    public static function render_button_and_modal($order_id) {
        include AAA_OC_PLUGIN_DIR . 'includes/partials/bottom/payment/payment-modal.php';
    }
}
