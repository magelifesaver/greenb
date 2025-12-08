<?php
/**
 * File: includes/report-refunds-v2.php
 * Description: Refund and cancellation summary with reason/admin columns for AAA Daily Reporting
 */

function aaa_render_refunds_and_cancels_v2($selected_date) {
    global $wpdb;
    $start = $selected_date . ' 00:00:00';
    $end = $selected_date . ' 23:59:59';

    $refunded_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order_refund' AND post_date BETWEEN %s AND %s",
        $start, $end
    ));

    $cancelled_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'wc-cancelled' AND post_date BETWEEN %s AND %s",
        $start, $end
    ));

    echo '<h1>Refund Summary</h1>';
    if (!empty($refunded_ids)) {
        echo '<p>Total Refunds: ' . count($refunded_ids) . '</p>';
        echo '<table class="widefat"><thead><tr><th>Refund ID</th><th>Amount</th><th>Date</th><th>Reason</th><th>Processed By</th></tr></thead><tbody>';
        foreach ($refunded_ids as $id) {
            $refund = wc_get_order($id);
            $parent_id = $refund->get_parent_id();
            $meta_reason = $refund->get_reason();
            $processed_by = get_post_meta($id, '_edit_last', true);
            $admin_name = $processed_by ? get_user_by('ID', $processed_by)->display_name : '—';
            echo '<tr><td>' . esc_html($id) . '</td><td>' . wc_price($refund->get_total()) . '</td><td>' . esc_html($refund->get_date_created()->date_i18n('Y-m-d H:i')) . '</td><td>' . esc_html($meta_reason ?: '—') . '</td><td>' . esc_html($admin_name) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No refunds recorded for this date.</p>';
    }

    echo '<h1>Cancellation Summary</h1>';
    if (!empty($cancelled_ids)) {
        echo '<p>Total Cancelled Orders: ' . count($cancelled_ids) . '</p>';
        echo '<table class="widefat"><thead><tr><th>Order ID</th><th>Total</th><th>Date</th><th>Customer</th></tr></thead><tbody>';
        foreach ($cancelled_ids as $id) {
            $order = wc_get_order($id);
            echo '<tr><td>' . esc_html($id) . '</td><td>' . wc_price($order->get_total()) . '</td><td>' . esc_html($order->get_date_created()->date_i18n('Y-m-d H:i')) . '</td><td>' . esc_html($order->get_formatted_billing_full_name()) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No cancelled orders for this date.</p>';
    }
}
