<?php
/**
 * File: /wp-content/plugins/aaa-product-type-promo/aaa-product-type-promo.php
 * Plugin Name: A Product Type Promo Banner (Standalone)(workflow)(live)
 * Description: Registers a custom WooCommerce product type "promo" with a banner image field and frontend banner display.
 * Version: 1.0
 * Author: Workflow Delivery
 */

defined('ABSPATH') || exit;

// 🔷 Register "promo" product type
add_filter('product_type_selector', function ($types) {
    $types['promo'] = __('Promo Banner', 'aaa');
    return $types;
});

add_filter('woocommerce_product_class', function ($classname, $product_type) {
    return $product_type === 'promo' ? 'WC_Product_Promo' : $classname;
}, 10, 2);

// 🔷 Define WC_Product_Promo
add_action('plugins_loaded', function () {
    if (!class_exists('WC_Product')) return;

    class WC_Product_Promo extends WC_Product {
        public function get_type() { return 'promo'; }
        public function is_virtual() { return true; }
 //       public function is_purchasable() { return false; }
 //       public function get_price($context = 'view') { return 'false'; }
 //       public function get_regular_price($context = 'view') { return 'false'; }
 //       public function get_sale_price($context = 'view') { return 'false'; }
 //       public function get_image($size = 'woocommerce_thumbnail', $attr = [], $placeholder = true) { return ''; }
        public function get_title() { return ''; }
 //       public function add_to_cart_text() { return 'false'; }
 //       public function get_add_to_cart_url() { return 'false'; }
    }
});

// 🔷 Save correct _product_type on save
add_action('save_post_product', function ($post_id) {
    if (get_post_type($post_id) !== 'product') return;
    $terms = wp_get_post_terms($post_id, 'product_type', ['fields' => 'slugs']);
    if (in_array('promo', $terms, true)) {
        update_post_meta($post_id, '_product_type', 'promo');
    }
});

// 🔷 Add custom meta field for banner image (Meta Box plugin required)
add_filter('rwmb_meta_boxes', function ($meta_boxes) {
    $meta_boxes[] = [
        'title'      => 'Promo Banner Image',
        'id'         => 'promo_banner_box',
        'post_types' => ['product'],
        'context'    => 'side',
        'priority'   => 'low',
        'fields'     => [
            [
                'name' => 'Banner Image',
                'id'   => '_promo_banner_image',
                'type' => 'image_advanced',
                'max_file_uploads' => 1,
            ],
        ],
    ];
    return $meta_boxes;
});

// 🔷 Render banner on frontend for promo products
add_action('init', function () {
    add_action('woocommerce_before_shop_loop_item', function () {
        global $product;
        if (!is_object($product) || $product->get_type() !== 'promo') return;

        $image_id = get_post_meta($product->get_id(), '_promo_banner_image', true);
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        if (!$image_url) return;

        echo '<div class="aaa-promo-banner"><img src="' . esc_url($image_url) . '" alt="Promo Banner" /></div>';
    }, 5);
});

// 🔷 Inline CSS (or you can move this to your theme’s stylesheet)
add_action('wp_head', function () {
    ?>
    <style>
        .product-type-promo .product-details {
            display: none !important;
        }
	.woocommerce ul.products.products-list-view li.product.virtual.product-type-promo {
	    display: block !important;
	}
        .aaa-promo-banner img {
            width: 100%;
            height: auto;
            display: block;
        }
	.product-type-promo .my-custom-image-container {
	    display: none;
	    }
    </style>
    <?php
});
