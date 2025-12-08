<?php
/**
 * Plugin Name: AAA Inventory Notices (live)(net)(addon)
 * Plugin URI:  https://yourwebsite.com
 * Description: Customizes WooCommerce low stock and out of stock email notifications with brand, edit links, and sales history.
 * Version: 1.0
 * Author: WebMaster
 * Text Domain: aaa-in
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Modify Low Stock Email Content
 */
add_filter('woocommerce_email_content_low_stock', 'aaa_in_custom_low_stock_email_content', 10, 2);
function aaa_in_custom_low_stock_email_content($message, $product) {
    return aaa_in_generate_email_content($message, $product);
}

/**
 * Modify Out of Stock Email Content
 */
add_filter('woocommerce_email_content_no_stock', 'aaa_in_custom_no_stock_email_content', 10, 2);
function aaa_in_custom_no_stock_email_content($message, $product) {
    return aaa_in_generate_email_content($message, $product);
}

/**
 * Generate Custom Email Content
 */
function aaa_in_generate_email_content($message, $product) {
    $product_id = $product->get_id();
    
    // Get Product Brand
    $brand = aaa_in_get_product_brand($product_id);
    
    // Get Product Edit URL
    $edit_url = admin_url('post.php?post=' . $product_id . '&action=edit');

    // Get Product SKU
    $sku = get_post_meta($product_id, '_sku', true);

    // Generate WeedMaps Link
    $weedmaps_url = 'https://weedmaps.com/new_admin/deliveries/lo-key-delivery/menu_items/' . $sku . '/edit?filters%5Bkeyword%5D=&offset=0';

    // Get Last 5 Sales History
    $sales_history = aaa_in_get_product_sales_history($product_id);

    // Format the Additional Info
    $additional_info = "\n\n";
    $additional_info .= sprintf(__('Brand: %s', 'aaa-in'), $brand) . "\n";
    $additional_info .= sprintf(__('Edit Product: <a href="%s" target="_blank">WooCommerce Admin</a>', 'aaa-in'), esc_url($edit_url)) . "\n";
    $additional_info .= sprintf(__('WeedMaps Link: <a href="%s" target="_blank">View on WeedMaps</a>', 'aaa-in'), esc_url($weedmaps_url)) . "\n";
    
    if (!empty($sales_history)) {
        $additional_info .= __('Last 5 Sales:', 'aaa-in') . "\n" . implode("\n", $sales_history);
    } else {
        $additional_info .= __('No recent sales found.', 'aaa-in');
    }

    return $message . $additional_info;
}

/**
 * Get the product brand from 'berocket_brand' taxonomy.
 */
function aaa_in_get_product_brand($product_id) {
    $terms = get_the_terms($product_id, 'berocket_brand');

    if (!empty($terms) && !is_wp_error($terms)) {
        $brand_names = wp_list_pluck($terms, 'name');
        return implode(', ', $brand_names); // Handle multiple brands
    }

    return 'No Brand'; // Fallback if no brand is assigned
}

/**
 * Get last 5 sales history of a product.
 */
function aaa_in_get_product_sales_history($product_id) {
    global $wpdb;
    
    $sales_history = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID as order_id, p.post_date as sale_date
        FROM {$wpdb->prefix}posts p
        JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        ORDER BY p.post_date DESC
        LIMIT 5
    ", $product_id));

    $formatted_sales = [];

    if (!empty($sales_history)) {
        foreach ($sales_history as $sale) {
            $formatted_sales[] = sprintf(__('Order #%d on %s', 'aaa-in'), $sale->order_id, date('Y-m-d', strtotime($sale->sale_date)));
        }
    }

    return $formatted_sales;
}
