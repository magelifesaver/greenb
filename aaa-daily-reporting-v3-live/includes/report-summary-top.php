<?php
/**
 * File: includes/report-summary-top.php
 * Description: Top-performing metrics for AAA Daily Reporting
 */

function aaa_render_top_summary_section($orders) {
    $top_order = null;
    $max_total = 0;
    $max_discount = 0;
    $top_discount = null;
    $hour_count = array_fill(0, 24, 0);

    $product_sales = [];
    $brand_sales = [];

    foreach ($orders as $order) {
        $total = $order->get_total();
        $discount = $order->get_discount_total();
        $created = $order->get_date_created();
        $hour = (int) $created->format('H');
        $hour_count[$hour]++;

        if ($total > $max_total) {
            $max_total = $total;
            $top_order = $order;
        }

        if ($discount > $max_discount) {
            $max_discount = $discount;
            $top_discount = $order;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            $pid = $product->get_id();
            $qty = $item->get_quantity();

            if (!isset($product_sales[$pid])) {
                $product_sales[$pid] = [
                    'name' => $product->get_name(),
                    'qty' => 0
                ];
            }
            $product_sales[$pid]['qty'] += $qty;

            $brands = wp_get_post_terms($pid, 'berocket_brand');
            foreach ($brands as $b) {
                if (!isset($brand_sales[$b->term_id])) {
                    $brand_sales[$b->term_id] = ['name' => $b->name, 'qty' => 0];
                }
                $brand_sales[$b->term_id]['qty'] += $qty;
            }
        }
    }

    arsort($hour_count);
    arsort($product_sales);
    arsort($brand_sales);

    $top_hour = array_key_first($hour_count);
    $top_product = reset($product_sales);
    $top_brand = reset($brand_sales);

    echo '<h1>Top Performance Summary</h1><table class="widefat"><tbody>';
    if ($top_order) {
        echo '<tr><th>Top Order</th><td>#' . $top_order->get_id() . ' – ' . wc_price($top_order->get_total()) . '</td></tr>';
    }
    if ($top_discount) {
        echo '<tr><th>Biggest Discount</th><td>#' . $top_discount->get_id() . ' – ' . wc_price($top_discount->get_discount_total()) . '</td></tr>';
    }
    if ($top_product) {
        echo '<tr><th>Top Product</th><td>' . esc_html($top_product['name']) . ' – ' . esc_html($top_product['qty']) . ' sold</td></tr>';
    }
    if ($top_brand) {
        echo '<tr><th>Top Brand</th><td>' . esc_html($top_brand['name']) . ' – ' . esc_html($top_brand['qty']) . ' units</td></tr>';
    }
    echo '<tr><th>Busiest Hour</th><td>' . str_pad($top_hour, 2, '0', STR_PAD_LEFT) . ':00</td></tr>';
    echo '</tbody></table>';
}
