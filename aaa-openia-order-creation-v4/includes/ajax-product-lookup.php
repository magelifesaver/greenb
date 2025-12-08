<?php
// File: /aaa-openia-order-creation-v4/includes/ajax-product-lookup.php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_aaa_v4_product_lookup', function () {
    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
    if (empty($term)) {
        wp_send_json_error(['message' => 'No search term provided.']);
    }

    global $wpdb;
    $settings = get_option('aaa_v4_order_creator_settings', []);
    $source = $settings['product_lookup_source'] ?? 'woocommerce';

    $products = [];

    if ($source === 'custom') {
        $table = esc_sql($settings['custom_table_name'] ?? '');
        $match_col = esc_sql($settings['custom_id_column'] ?? '');

        if ($table && $match_col) {
            $table_name = $wpdb->prefix . $table;

            $query = $wpdb->prepare("
                SELECT * FROM {$table_name}
                WHERE {$match_col} LIKE %s
                LIMIT 10
            ", '%' . $wpdb->esc_like($term) . '%');

            $rows = $wpdb->get_results($query);

            foreach ($rows as $row) {
                $products[] = [
                    'product_id'     => $row->post_id ?? $row->ID ?? 0,
                    'product_name'   => $row->wm_og_name ?? $row->product_name ?? '',
                    'sku'            => $row->wm_external_id ?? '',
                    'price'          => number_format((float) ($row->wm_unit_price ?? $row->price ?? 0), 2, '.', ''),
                    'stock_quantity' => $row->stock ?? $row->quantity ?? '',
                ];
            }
        }
    } else {
        // Default: WooCommerce
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, sku.meta_value AS sku, price.meta_value AS price, stock.meta_value AS stock_quantity
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} sku ON p.ID = sku.post_id AND sku.meta_key = '_sku'
            LEFT JOIN {$wpdb->postmeta} price ON p.ID = price.post_id AND price.meta_key = '_price'
            LEFT JOIN {$wpdb->postmeta} stock ON p.ID = stock.post_id AND stock.meta_key = '_stock'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND (p.post_title LIKE %s OR sku.meta_value LIKE %s)
            LIMIT 10
        ", '%' . $wpdb->esc_like($term) . '%', '%' . $wpdb->esc_like($term) . '%');

        $rows = $wpdb->get_results($query);

        foreach ($rows as $row) {
	$products[] = [
	    'product_id'     => $row->ID,
	    'product_name'   => $row->post_title,
	    'sku'            => $row->sku,
	    'price'          => number_format((float) $row->price, 2, '.', ''),
	    'stock_quantity' => (int) $row->stock_quantity,
	    'image_url'      => get_the_post_thumbnail_url($row->ID, 'thumbnail') ?: '',
	];
        }
    }

    if (!empty($products)) {
        wp_send_json_success($products);
    } else {
        wp_send_json_error(['message' => 'No matching products found.']);
    }
});
