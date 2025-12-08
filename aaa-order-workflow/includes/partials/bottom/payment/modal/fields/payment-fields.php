<?php
/**
 * File: aaa-order-workflow/includes/partials/bottom/payment/modal/fields/payment-fields.php
 * Purpose: 
 * Notes: 
 * Version: 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$payment_data = AAA_OC_Payment_Fields::get_payment_fields( $order_id );
$current_user = wp_get_current_user();
$options      = AAA_OC_Payment_Fields::get_driver_options();
?>

<div class="aaa-payment-fields" data-order-id="<?php echo esc_attr( $order_id ); ?>">
    <h3 style="display:flex; justify-content:space-between; align-items:center;">
        <span>Payment Information</span>
        <span style="font-weight:bold;">
            Order Total: $<?php echo esc_html( number_format( $payment_data['aaa_oc_order_total'] ?? 0, 2 ) ); ?>
        </span>
    </h3>

    <input type="hidden" name="aaa_oc_order_total"
           value="<?php echo esc_attr($payment_data['aaa_oc_order_total'] ?? '0.00'); ?>">

    <!-- Zelle -->
    <div class="payment-row">
        <label>Zelle:</label>
        <input type="number" step="0.01" name="aaa_oc_zelle_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_zelle_amount']); ?>" />
    </div>

    <!-- Venmo -->
    <div class="payment-row">
        <label>Venmo:</label>
        <input type="number" step="0.01" name="aaa_oc_venmo_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_venmo_amount']); ?>" />
    </div>

    <!-- ApplePay -->
    <div class="payment-row">
        <label>Apple Pay:</label>
        <input type="number" step="0.01" name="aaa_oc_applepay_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_applepay_amount']); ?>" />
    </div>

    <!-- Credit Card -->
    <div class="payment-row">
        <label>Credit Card:</label>
        <input type="number" step="0.01" name="aaa_oc_creditcard_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_creditcard_amount'] ?? '0.00'); ?>" />
    </div>

    <!-- CashApp -->
    <div class="payment-row">
        <label>CashApp:</label>
        <input type="number" step="0.01" name="aaa_oc_cashapp_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_cashapp_amount']); ?>" />
    </div>

    <!-- ePayment Total -->
    <div class="payment-row total-row">
        <label>ePayment Total:</label>
        <div class="total-display"><?php echo esc_html(number_format($payment_data['aaa_oc_epayment_total'] ?? 0, 2)); ?></div>
        <input type="hidden" name="aaa_oc_epayment_total"
               value="<?php echo esc_attr($payment_data['aaa_oc_epayment_total'] ?? '0.00'); ?>">
    </div>

    <!-- Cash -->
    <div class="payment-row cash-row">
        <label>Cash:</label>
        <input type="number" step="0.01" name="aaa_oc_cash_amount"
               value="<?php echo esc_attr($payment_data['aaa_oc_cash_amount']); ?>" />
    </div>

    <!-- Payment Total -->
    <div class="payment-row total-row">
        <label>Payment Total:</label>
        <div class="total-display"><?php echo esc_html(number_format($payment_data['aaa_oc_payrec_total'] ?? 0, 2)); ?></div>
        <input type="hidden" name="aaa_oc_payrec_total"
               value="<?php echo esc_attr($payment_data['aaa_oc_payrec_total'] ?? '0.00'); ?>">
    </div>

    <!-- Web Tip -->
    <div class="payment-row tip-row">
        <label>Web Tip:</label>
        <input type="number" step="0.01" name="aaa_oc_tip_total"
               value="<?php echo esc_attr($payment_data['aaa_oc_tip_total'] ?? '0.00'); ?>" readonly />
    </div>

    <!-- ePayment Tip -->
    <div class="payment-row">
        <label>ePayment Tip:</label>
        <input type="number" step="0.01" name="epayment_tip"
               value="<?php echo esc_attr($payment_data['epayment_tip'] ?? '0.00'); ?>" readonly />
    </div>

    <!-- Total Order Tip -->
    <div class="payment-row total-row">
        <label>Total Driver Tip:</label>
        <div class="total-display"><?php echo esc_html(number_format($payment_data['total_order_tip'] ?? 0, 2)); ?></div>
        <input type="hidden" name="total_order_tip"
               value="<?php echo esc_attr($payment_data['total_order_tip'] ?? '0.00'); ?>">
    </div>

    <!-- Order Balance + Status -->
    <div class="payment-row status-row">
        <label>Order Balance:</label>
        <input type="number" step="0.01" name="aaa_oc_order_balance"
               value="<?php echo esc_attr($payment_data['aaa_oc_order_balance']); ?>" readonly />
        <label>Payment Status:</label>
        <select name="aaa_oc_payment_status">
            <option value="unpaid"  <?php selected($payment_data['aaa_oc_payment_status'], 'unpaid'); ?>>Unpaid</option>
            <option value="partial" <?php selected($payment_data['aaa_oc_payment_status'], 'partial'); ?>>Partial</option>
            <option value="paid"    <?php selected($payment_data['aaa_oc_payment_status'], 'paid'); ?>>Paid</option>
        </select>
    </div>

    <!-- Envelope Outstanding -->
    <div class="payment-row">
        <label>
            <input type="checkbox" name="cleared" value="1" <?php checked($payment_data['cleared'], 1); ?> />
            Payment verified by <?php echo esc_html($current_user->display_name); ?>
        </label>
        <label>
            <input type="checkbox" name="envelope_outstanding" value="1" <?php checked($payment_data['envelope_outstanding'] ?? 0, 1); ?> />
            Envelope Outstanding (driver to return envelope next shift)
        </label>
    </div>

    <button class="button button-modern save-payment-button" data-order-id="<?php echo esc_attr( $order_id ); ?>">
        Save Payment
    </button>
</div>
