<?php
/**
 * Handles product lookup functionality for the order workflow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AAA_Product_Lookup {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('wp_ajax_aaa_oc_find_product_by_new_sku', [__CLASS__, 'find_product_by_new_sku']);
    }
    
    /**
     * AJAX handler to find a product by lkd_wm_new_sku
     */
    public static function find_product_by_new_sku() {
        check_ajax_referer('aaa-oc-nonce', 'security');
        
        $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';
        
        if (empty($sku)) {
            wp_send_json_error(['message' => 'No SKU provided']);
            return;
        }
        
        global $wpdb;
        
        // First try to find the product by lkd_wm_new_sku meta field
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = 'lkd_wm_new_sku' 
             AND meta_value = %s 
             LIMIT 1",
            $sku
        ));
        
        if ($product_id) {
            // Get the original SKU for this product
            $original_sku = get_post_meta($product_id, '_sku', true);
            
            if ($original_sku) {
                wp_send_json_success([
                    'product_id' => $product_id,
                    'original_sku' => $original_sku,
                    'new_sku' => $sku
                ]);
                return;
            }
        }
        
        // If we get here, no product was found with this new SKU
        wp_send_json_error(['message' => 'Product not found']);
    }
}

// Initialize the class
AAA_Product_Lookup::init();
