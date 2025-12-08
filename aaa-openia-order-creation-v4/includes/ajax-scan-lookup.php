<?php
// File: /aaa-openia-order-creation-v4/includes/ajax-scan-lookup.php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_aaa_v4_scan_lookup', function () {
    $barcode = isset($_POST['barcode']) ? sanitize_text_field($_POST['barcode']) : '';
    if (empty($barcode)) {
        wp_send_json_error(['message' => 'No barcode provided.']);
    }

    global $wpdb;
    $settings = get_option('aaa_v4_order_creator_settings', []);
    $source = $settings['product_lookup_source'] ?? 'woocommerce';

    $product = null;

    if ($source === 'custom') {
        $table = esc_sql($settings['custom_table_name'] ?? '');
        $match_col = esc_sql($settings['custom_id_column'] ?? '');

        if ($table && $match_col) {
            $table_name = $wpdb->prefix . $table;

            $row = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$table_name}
                WHERE {$match_col} = %s
                LIMIT 1
            ", $barcode));

            if ($row) {
                $product = [
                    'product_id' => $row->post_id ?? $row->ID ?? 0,
                    'name'       => $row->wm_og_name ?? $row->product_name ?? '',
                    'sku'        => $row->wm_external_id ?? '',
                    'price'      => $row->wm_unit_price ?? $row->price ?? 0,
                    'stock'      => $row->stock ?? $row->quantity ?? '',
                    'image_url'  => $row->wm_image_url ?? '',
                ];
            }
        }
    } else {
        // WooCommerce fallback
        $row = $wpdb->get_row($wpdb->prepare("
            SELECT p.ID, p.post_title, sku.meta_value AS sku, price.meta_value AS price, stock.meta_value AS stock_quantity
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} sku ON p.ID = sku.post_id AND sku.meta_key = '_sku'
            LEFT JOIN {$wpdb->postmeta} price ON p.ID = price.post_id AND price.meta_key = '_price'
            LEFT JOIN {$wpdb->postmeta} stock ON p.ID = stock.post_id AND stock.meta_key = '_stock'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND sku.meta_value = %s
            LIMIT 1
        ", $barcode));

        if ($row) {
            $image_url = get_the_post_thumbnail_url($row->ID, 'full') ?: '';

            $product = [
                'product_id' => $row->ID,
                'name'       => $row->post_title,
                'sku'        => $row->sku,
                'price'      => $row->price,
                'stock'      => $row->stock_quantity,
                'image_url'  => $image_url,
            ];
        }
    }

    if ($product) {
        wp_send_json_success($product);
    } else {
        wp_send_json_error(['message' => 'No product found for barcode.']);
    }
});
