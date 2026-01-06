<?php
/**
 * File: includes/report-customers-v2.php
 * Description: Customer insights (sortable + links) for AAA Daily Reporting
 */

function aaa_render_customer_summary_v2($orders) {
    $customer_data = [];

    foreach ($orders as $order) {
        $user_id = $order->get_user_id();
        $email = $order->get_billing_email();
        $key = $user_id ? 'user_' . $user_id : 'guest_' . $email;

        if (!isset($customer_data[$key])) {
            $user = $user_id ? get_user_by('ID', $user_id) : null;
            $created = $user ? strtotime($user->user_registered) : strtotime($order->get_date_created());
            $previous_orders = wc_get_orders([
                'customer_id' => $user_id,
                'exclude' => [$order->get_id()],
                'limit' => -1,
                'orderby' => 'date_created',
                'order' => 'DESC'
            ]);
            $last_order_before_today = '';
            foreach ($previous_orders as $po) {
                if ($po->get_date_created()->format('Y-m-d') < $order->get_date_created()->format('Y-m-d')) {
                    $last_order_before_today = human_time_diff($po->get_date_created()->getTimestamp(), time()) . ' ago';
                    break;
                }
            }

            $all_orders = wc_get_orders([
                'customer_id' => $user_id,
                'limit' => -1,
                'status' => ['completed', 'processing']
            ]);
            $total_spent = 0;
            foreach ($all_orders as $ao) {
                $total_spent += $ao->get_total();
            }
            $order_count = count($all_orders);

            $customer_data[$key] = [
                'name' => $order->get_formatted_billing_full_name(),
                'email' => $email,
                'city' => $order->get_billing_city(),
                'is_new' => !$user_id || $order_count <= 1,
                'since' => human_time_diff($created, time()) . ' ago',
                'last_seen' => $last_order_before_today ?: '—',
                'orders' => $order_count,
                'total' => $total_spent,
                'avg' => $order_count ? $total_spent / $order_count : 0,
                'user_link' => $user_id ? get_edit_user_link($user_id) : ''
            ];
        }
    }

    echo '<h1>Customer Summary</h1><table class="widefat sortable"><thead><tr>';
    echo '<th>Name</th><th>Email</th><th>City</th><th>Status</th><th>Customer Since</th><th>Last Order Before Today</th><th>Orders</th><th>Total Spent</th><th>Avg Order</th>';
    echo '</tr></thead><tbody>';

    foreach ($customer_data as $c) {
        $total_display = $c['total'] < 0 ? '<span style="color:red;">' . wc_price($c['total']) . '</span>' : wc_price($c['total']);
        $avg_display = $c['avg'] < 0 ? '<span style="color:red;">' . wc_price($c['avg']) . '</span>' : wc_price($c['avg']);
        $link_name = $c['user_link'] ? '<a href="' . esc_url($c['user_link']) . '">' . esc_html($c['name']) . '</a>' : esc_html($c['name']);

        echo '<tr>';
        echo '<td>' . $link_name . '</td>';
        echo '<td>' . esc_html($c['email']) . '</td>';
        echo '<td>' . esc_html($c['city']) . '</td>';
        echo '<td>' . ($c['is_new'] ? 'New' : 'Returning') . '</td>';
        echo '<td>' . esc_html($c['since']) . '</td>';
        echo '<td>' . esc_html($c['last_seen']) . '</td>';
        echo '<td>' . esc_html($c['orders']) . '</td>';
        echo '<td>' . $total_display . '</td>';
        echo '<td>' . $avg_display . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}
