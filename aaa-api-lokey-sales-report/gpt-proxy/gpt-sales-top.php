<?php
/**
 * Plugin Name: GPT Sales Top Endpoint (v1.2)
 * Description: Returns top-selling products for a given time range with quantity, price sold, and total sales value. Timezone-aware.
 * Version: 1.2
 * Author: Lokey Delivery DevOps
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {

    register_rest_route('gpt/v1', '/sales/top', [
        'methods'  => 'GET',
        'callback' => function($request) {

            // 🔐 Ensure WooCommerce is active
            if (!function_exists('wc_get_orders')) {
                return new WP_Error('woocommerce_missing', 'WooCommerce not active', ['status' => 500]);
            }

            // 🧭 Parameters
            $days  = absint($request->get_param('days')) ?: 1;
            $limit = absint($request->get_param('limit')) ?: 100;
            if ($limit > 500) $limit = 500;

            // 🕓 Timezone-safe date range
            $tz_string = get_option('timezone_string') ?: 'America/Los_Angeles';
            $tz = new DateTimeZone($tz_string);
            $utc = new DateTimeZone('UTC');

            $now = new DateTime('now', $tz);
            $start_local = (clone $now)->setTime(0, 0, 0);
            $end_local   = (clone $now)->setTime(23, 59, 59);

            // Convert to UTC for query consistency
            $start_utc = (clone $start_local)->setTimezone($utc);
            $end_utc   = (clone $end_local)->setTimezone($utc);

            // 🧾 Fetch completed orders in this range
            $orders = wc_get_orders([
                'status'       => ['completed'],
                'date_created' => $start_utc->format('Y-m-d H:i:s') . '...' . $end_utc->format('Y-m-d H:i:s'),
                'limit'        => -1,
                'orderby'      => 'date',
                'order'        => 'DESC',
                'return'       => 'objects',
            ]);

            $products = [];

            // 🧮 Aggregate product-level sales
            foreach ($orders as $order) {
                foreach ($order->get_items('line_item') as $item) {
                    $pid  = $item->get_product_id();
                    if (!$pid) continue;

                    $name = $item->get_name();
                    $qty  = (int)$item->get_quantity();
                    $total = (float)$item->get_total();
                    $price = $qty > 0 ? round($total / $qty, 2) : 0;

                    if (!isset($products[$pid])) {
                        $products[$pid] = [
                            'product_id'    => $pid,
                            'product_name'  => $name,
                            'quantity_sold' => 0,
                            'total_sales'   => 0.0,
                            'avg_price_sold'=> 0.0,
                        ];
                    }

                    $products[$pid]['quantity_sold'] += $qty;
                    $products[$pid]['total_sales']   += $total;
                }
            }

            // 🎯 Compute average price sold
            foreach ($products as &$p) {
                $p['avg_price_sold'] = $p['quantity_sold'] > 0
                    ? round($p['total_sales'] / $p['quantity_sold'], 2)
                    : 0.00;
                $p['total_sales'] = round($p['total_sales'], 2);
            }

            // 🔝 Sort by quantity sold
            usort($products, function($a, $b) {
                return $b['quantity_sold'] <=> $a['quantity_sold'];
            });

            $top = array_slice(array_values($products), 0, $limit);

            // ✅ Build response
            return new WP_REST_Response([
                'ok'        => true,
                'days'      => $days,
                'limit'     => $limit,
                'timezone'  => $tz_string,
                'date_from' => $start_local->format('Y-m-d H:i:s'),
                'date_to'   => $end_local->format('Y-m-d H:i:s'),
                'count'     => count($top),
                'generated' => gmdate('Y-m-d H:i:s'),
                'top'       => $top,
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
