<?php
/**
 * Plugin Name: A OpenIA Order Creation V4 (live)(XHV98-WF)
 * Description: Create WooCommerce orders from pasted HTML content and external order numbers, with AI fallback matching.
 * Version: 4.6.0
 * Author: WebMaster Delivery
 * File Path: /aaa-openia-order-creation.php
 */

defined('ABSPATH') || exit;

// Define paths
define('AAA_V4_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAA_V4_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
require_once AAA_V4_PLUGIN_DIR . 'includes/admin/add-order-source-column.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-form-handler.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-parser.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-product-matcher.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-preview-builder-top.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-preview-builder-bottom.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-customer-handler.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-order-creator.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-logger.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-relookup-customer.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-product-lookup.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-scan-lookup.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-settings.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-parser-table.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-use-address.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-upload-handler.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/ajax-apply-coupon.php';
require_once AAA_V4_PLUGIN_DIR . 'includes/class-aaa-v4-google-settings.php';

// Initialize settings
add_action('plugins_loaded', [ 'AAA_V4_Settings', 'init' ]);
add_action('admin_init', [ 'AAA_V4_Settings', 'handle_upload' ]);
add_action('plugins_loaded', [ 'AAA_V4_Google_Settings', 'init' ]);

// Table creation on activation
register_activation_hook(__FILE__, function () {
    if (class_exists('AAA_V4_Parser_Table')) {
        AAA_V4_Parser_Table::create_table();
    }
});

// Admin menu
add_action('admin_menu', function () {
    // OLD: keep top-level menu during transition
    add_menu_page(
        'AAA Order Creation V4',
        'Order Creator V4',
        'manage_woocommerce',
        'aaa-openia-order-creation-v4',
        ['AAA_V4_Form_Handler', 'render_form'],
        'dashicons-cart'
    );

    // NEW: also show under WooCommerce menu
    add_submenu_page(
        'woocommerce',
        'Order Creator V4',
        'Order Creator V4',
        'manage_woocommerce',
        'aaa-openia-order-creation-v4-alt',
        ['AAA_V4_Form_Handler', 'render_form']
    );
});

// Admin bar shortcut
add_action('admin_bar_menu', function($wp_admin_bar) {
    if ( ! current_user_can('manage_woocommerce') ) {
        return;
    }

    $wp_admin_bar->add_node([
        'id'    => 'aaa-order-creator',
        'title' => '➕ Order Creator',
        'href'  => admin_url('admin.php?page=aaa-openia-order-creation-v4'),
        'meta'  => [
            'title' => 'Quick Access to Order Creator',
            'target' => '_blank',
        ],
    ]);
}, 100);

// Only enqueue assets on this plugin’s page
add_action('admin_enqueue_scripts', 'aaa_v4_enqueue_assets');
function aaa_v4_enqueue_assets($hook) {
    if ($hook !== 'toplevel_page_aaa-openia-order-creation-v4') {
        return;
    }

    // CSS
    wp_enqueue_style(
        'aaa-v4-preview-css',
        AAA_V4_PLUGIN_URL . 'assets/css/aaa-v4-preview.css',
        [],
        '1.0'
    );

    // JS files
    $scripts = [
        'autocomplete',
        'barcode',
        'customers',
        'discounts',
        'dropzone',
        'orders',
        'products',
        'relookup',
	'address-loader',
	'stock-checks',
	'validation'
    ];

    foreach ($scripts as $handle) {
        wp_enqueue_script(
            "aaa-v4-{$handle}-js",
            AAA_V4_PLUGIN_URL . "assets/js/aaa-v4-{$handle}.js",
            ['jquery'],
            '1.0',
            true
        );

        if ($handle === 'dropzone') {
            wp_localize_script("aaa-v4-dropzone-js", 'ajaxurl', [
                'url' => admin_url('admin-ajax.php'),
                'user_id' => get_current_user_id(), // use fallback
            ]);
        }
    }

    // Localize required-field settings for validation.js
    $settings = get_option( 'aaa_v4_order_creator_settings', [] );
    wp_localize_script(
        'aaa-v4-validation-js',
        'AAA_V4_REQUIRED_SETTINGS',
        [
            'require_id_number'     => ! empty( $settings['require_id_number'] ),
            'require_dl_expiration' => ! empty( $settings['require_dl_expiration'] ),
            'require_birthday'      => ! empty( $settings['require_birthday'] ),
        ]
    );

    // Google Places Autocomplete (only if API key is set)
    if (class_exists('AAA_V4_Google_Settings')) {
        // Order Creator autocomplete
        // Prefer API key from coords plugin if available
        $apiKey = defined('ADBC_GOOGLE_BROWSER_API_KEY') ? ADBC_GOOGLE_BROWSER_API_KEY : AAA_V4_Google_Settings::get_api_key();
        if ($apiKey) {
            wp_enqueue_script(
                'aaa-v4-order-creator-autocomplete',
                AAA_V4_PLUGIN_URL . 'assets/js/aaa-v4-order-creator-autocomplete.js',
                ['jquery'],
                '1.0',
                true
            );
            wp_localize_script('aaa-v4-order-creator-autocomplete', 'AAA_V4_GOOGLE_API_KEY', $apiKey);
        }
    }
}
