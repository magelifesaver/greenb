<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-product-fields.php
 * ---------------------------------------------------------------------------
 * Renders product-level forecast meta fields in the product edit page.
 * Adds: Minimum Stock Buffer (forecast_minimum_stock)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Forecast_Product_Fields {

    public static function init() {
        add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'render_fields']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_fields']);
    }

    public static function render_fields() {
        // Look up the current product
        $product = null;
        if (isset($GLOBALS['product_object']) && $GLOBALS['product_object'] instanceof WC_Product) {
            $product = $GLOBALS['product_object'];
        } elseif (function_exists('wc_get_product')) {
            $product = wc_get_product(get_the_ID());
        }
        // Only show field for simple products
        if (!$product || !$product->is_type('simple')) {
            return;
        }
        echo '<div class="options_group"><h3>Stock Forecast Settings</h3>';
        woocommerce_wp_text_input([
            'id'            => 'forecast_minimum_stock',
            'label'         => 'Minimum Stock Buffer',
            'description'   => 'Stock buffer before reorder is triggered. Used to calculate Reorder Date.',
            'type'          => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '0'],
            'desc_tip'      => true,
            'wrapper_class' => 'show_if_simple', // hide for non-simple products:contentReference[oaicite:3]{index=3}
        ]);
        echo '</div>';
    }

    public static function save_fields($post_id) {
        // Only save for simple products
        $product = function_exists('wc_get_product') ? wc_get_product($post_id) : null;
        if (!$product || !$product->is_type('simple')) {
            return;
        }
        $min_stock = wc_clean($_POST['forecast_minimum_stock'] ?? '');
        if ($min_stock !== '') {
            update_post_meta($post_id, 'forecast_minimum_stock', $min_stock);
        } else {
            delete_post_meta($post_id, 'forecast_minimum_stock');
        }
    }
}

WF_SFWF_Forecast_Product_Fields::init();
