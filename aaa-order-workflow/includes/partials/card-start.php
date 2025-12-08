<?php
/**
 * File Path: /aaa-order-workflow/includes/partials/card-start.php
 *
 * Purpose:
 * Starts the collapsed order card container and attaches all required dataset attributes
 * for board-toolbar-extras.js filters (envelope, tip, customer type, ID expired,
 * payment method, driver, created via, order source).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Defensive defaults
$order_status        = $order_status        ?? ($row->status ?? '');
$fulfillment_status  = $fulfillment_status  ?? ($row->fulfillment_status ?? 'not_picked');
$order_id            = $order_id            ?? ($row->order_id ?? 0);

// === Derived dataset flags === //
$payment_status       = $row->aaa_oc_payment_status ?? '';
$envelope_outstanding = (int) ($row->envelope_outstanding ?? 0);
$epayment_tip         = (float) ($row->epayment_tip ?? 0);
$total_order_tip      = (float) ($row->total_order_tip ?? 0);
$completed_orders     = (int) ($row->customer_completed_orders ?? 0);
$customer_type        = ($completed_orders === 0) ? 'new' : 'existing';

// ID expired
$id_expired = '0';
if ( ! empty($row->lkd_dl_exp) ) {
    $today_mid = strtotime( date('Y-m-d', current_time('timestamp')) . ' 00:00:00' );
    $exp_ts    = strtotime($row->lkd_dl_exp);
    if ( $exp_ts && $exp_ts < $today_mid ) {
        $id_expired = '1';
    }
}

// Payment method (real)
$real_payment_method = $row->real_payment_method ?? '';
$customer_name = $row->customer_name ?? '';


// Driver
$driver_id   = (int) ($row->driver_id ?? 0);
$driver_name = '';
if ( $driver_id ) {
    $drv_user = get_user_by('id', $driver_id);
    $driver_name = $drv_user ? $drv_user->display_name : ('#' . $driver_id);
}

// Created via + Source
$created_via_raw = $row->_created_via ?? '';
$mapped_source   = AAA_OC_Map_Order_Source::map($created_via_raw, $row->_wc_order_attribution_source_type ?? '');
$order_source    = $mapped_source['source'] ?? '';

?>
<!-- Collapsed Card -->
<div class="aaa-oc-order-card collapsed"
     data-expanded="false"
     data-order-id="<?php echo esc_attr($order_id); ?>"
     data-order-status="<?php echo esc_attr($order_status); ?>"
     data-fulfillment-status="<?php echo esc_attr($fulfillment_status); ?>"

     data-payment-status="<?php echo esc_attr($payment_status); ?>"
     data-envelope-outstanding="<?php echo esc_attr($envelope_outstanding); ?>"
     data-epayment-tip="<?php echo esc_attr(number_format($epayment_tip, 2, '.', '')); ?>"
     data-total-order-tip="<?php echo esc_attr(number_format($total_order_tip, 2, '.', '')); ?>"
     data-customer-type="<?php echo esc_attr($customer_type); ?>"
     data-id-expired="<?php echo esc_attr($id_expired); ?>"
     data-real-payment-method="<?php echo esc_attr($real_payment_method); ?>"
     data-customer-name="<?php echo esc_attr(strtolower($customer_name)); ?>"
     data-driver-id="<?php echo esc_attr($driver_id); ?>"
     data-driver-name="<?php echo esc_attr($driver_name); ?>"
     data-created-via="<?php echo esc_attr($created_via_raw); ?>"
     data-order-source="<?php echo esc_attr($order_source); ?>"

     style="background:#fff; border:10px solid transparent; border-radius:10px;
            border-left-color:<?php echo $left_color; ?>;
            border-right-color:<?php echo $right_color; ?>;
            <?php if ($has_special): ?>border-top-color: blue;<?php endif; ?>
            <?php if ($has_bday): ?>border-bottom-color: pink;<?php endif; ?>
            margin-bottom:0.5rem; margin-right:0.2rem; padding:0.3rem;">
