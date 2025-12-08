<?php
/**
 * Plugin Name: Time Picker for WooCommerce Premium
 * Plugin URI: https://arosoft.se/product/time-date-picker-for-woocommerce/
 * Description: A checkout date & time picker for delivery and pickup.
 * Version: 1.0.3
 * Author: Arosoft.se
 * Author URI: https://arosoft.se
 * Developer: Arosoft.se
 * Developer URI: https://arosoft.se
 * Text Domain: checkout-time-picker-for-woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.1
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Copyright: Arosoft.se 2025
 * License: GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
define('TPFW_VERSION', '1.0.3');



define('TPFW_PLUGINDIRPATH', plugin_dir_path(__FILE__));
define('TPFW_PLUGINDIRURL', plugin_dir_url(__FILE__));

if (!defined('ABSPATH')) {
    exit;
}
register_activation_hook(__FILE__, array(
    'TPFW',
    'activate'
));
register_uninstall_hook(__FILE__, array(
    'TPFW',
    'uninstall'
));
if (!class_exists('TPFW')) {



    /**
     * Main Class TPFW
     *
     * @since 1.0
     */
    class TPFW
    {
        protected static $_instance = null;

        public static $schedules;

        public $orders;






        // The Constructor
        public function __construct()
        {

            // Declare compatibility with WooCommerce HPOS
            add_action('before_woocommerce_init', function () {
                if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
                }
            });




            add_action('plugins_loaded', array(
                $this,
                'init'
            ), 10);

            add_action('init', array(
                $this,
                'load_text_domain'
            ));



        }
        public function load_text_domain() {
            load_plugin_textdomain('checkout-time-picker-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }


        public static function get_all_categories($fields_ = 'all', $empty_ = 0, $includes = false)
        {
            $taxonomy = 'product_cat';
            $show_count = 0; // 1 for yes, 0 for no
            $pad_counts = 0; // 1 for yes, 0 for no
            $hierarchical = 1; // 1 for yes, 0 for no
            $title = '';
            $empty = $empty_;
            $fields = $fields_;
            $args = array(
                'taxonomy' => $taxonomy,
                'show_count' => $show_count,
                'pad_counts' => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li' => $title,
                'hide_empty' => $empty,
                'fields' => $fields,
                'menu_order' => 'asc',
            );
            if ($includes != false) {
                $args['include'] = $includes;
            }
            $cats = get_categories($args);
            $i = 1;
            foreach ($cats as $tpfw_cat) {
                if (is_object($tpfw_cat)) {
                    $tpfw_cat->sortIndex = $i;
                    $i++;
                    $tpfw_cat->id = $tpfw_cat->cat_ID;
                }
            }
            return $cats;
        }

        // Enqueues admin scripts and styles
        public function enqueue_scripts_back_end($hook)
        {

            if ($hook == 'woocommerce_page_wc-settings') {

                if (WP_DEBUG === true) {
                    wp_enqueue_script('tpfw-admin-script-handle', $this->get_plugin_url('assets/js/tpfw-order-admin.js'), array(
                        'wp-color-picker',
                        'jquery'
                    ), TPFW_VERSION, true);
                    wp_enqueue_script('tpfw-admin-script-handle2', $this->get_plugin_url('assets/js/tpfw-admin-scripts.js'), array(

                        'jquery'
                    ), TPFW_VERSION, true);

                    wp_register_script('tpfw-admin-availability-script', TPFW_PLUGINDIRURL . 'assets/js/tpfw-admin-availability.js', array(
                        'jquery',
                        'backbone'
                    ), TPFW_VERSION, true);

                    wp_register_script('tpfw-admin-ordertime-script', TPFW_PLUGINDIRURL . 'assets/js/tpfw-admin-ordertime.js', array(
                        'jquery',
                        'backbone'
                    ), TPFW_VERSION, true);


                } else {
                    wp_enqueue_script('tpfw-admin-script-handle', $this->get_plugin_url('assets/js/tpfw-order-admin.min.js'), array(
                        'wp-color-picker',
                        'jquery'
                    ), TPFW_VERSION, true);
                    wp_enqueue_script('tpfw-admin-script-handle2', $this->get_plugin_url('assets/js/tpfw-admin-scripts.min.js'), array(

                        'jquery'
                    ), TPFW_VERSION, true);

                    wp_register_script('tpfw-admin-availability-script', TPFW_PLUGINDIRURL . 'assets/js/tpfw-admin-availability.min.js', array(
                        'jquery',
                        'backbone'
                    ), TPFW_VERSION, true);

                    wp_register_script('tpfw-admin-ordertime-script', TPFW_PLUGINDIRURL . 'assets/js/tpfw-admin-ordertime.min.js', array(
                        'jquery',
                        'backbone'
                    ), TPFW_VERSION, true);


                }

            }


        }
        // Includes plugin files
        public function includes()
        {

            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

            require_once('classes/class-tpfw-orders.php');


            require_once('classes/class-tpfw-time.php');
            require_once('classes/class-tpfw-admin.php');


            require_once('classes/class-tpfw-timepick.php');
            require_once('classes/class-tpfw-settings.php');


            // Packages for blocks checkout

            require_once('packages/tpfw-checkout-pickup-options-block/tpfw-pickup-timepicker-block.php');
            require_once('packages/tpfw-checkout-shipping-options-block/tpfw-delivery-timepicker-block.php');



        }
        // Returns new class instance
        public static function instance()
        {
            if (!isset(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        // Plugin initiation
        public function init()
        {


            $this->includes();



            add_action('woocommerce_init', array(
                $this,
                'woocommerce_init'
            ));



            add_action('wp', array(
                $this,
                'wp'
            ), 99);
            add_action('admin_enqueue_scripts', array(
                $this,
                'enqueue_scripts_back_end'
            ));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                $this,
                'add_action_links'
            ));


        }




        public function wp()
        {



        }
        // Add setting link to Plugins page
        public function add_action_links($links)
        {


            $links_add = array(
                '<a href="' . admin_url('admin.php?page=wc-settings&tab=tpfw&section') . '">' . __('Settings', 'checkout-time-picker-for-woocommerce') . '</a>',

            );

            return array_merge($links, $links_add);
        }

        // Enqueues scripts and styles front end



        function array_flatten($items)
        {
            if (!is_array($items)) {
                return [$items];
            }
            return array_reduce(
                $items,
                function ($carry, $item) {
                    return array_merge($carry, self::array_flatten($item));
                }
                ,
                []
            );
        }
        public function woocommerce_init()
        {

            $this->orders = new TPFW_Orders();


        }






        // Gets the base path
        protected function base_path($relativePath = '')
        {
            $rc = new \ReflectionClass(get_class($this));
            return dirname($rc->getFileName()) . $relativePath;
        }

        // Gets the url to the plugin
        protected function get_plugin_url($relativePath = '')
        {
            return untrailingslashit(plugins_url($relativePath, $this->base_path_file()));
        }
        // Gets the class filenamne
        protected function base_path_file()
        {
            $rc = new \ReflectionClass(get_class($this));
            return $rc->getFileName();
        }


        static function activate()
        {
            update_option('TPFW_Activated_Plugin', 'TPFW');


        }


        // to be run on plugin uninstallation
        public static function uninstall()
        {
            if (get_option('tpfw_clean_settings', 'no') == 'yes') {
                foreach (wp_load_alloptions() as $option => $value) {
                    if (strpos($option, 'tpfw_') === 0) {
                        delete_option($option);
                    }
                }
            }
        }
        public static function cart_needs_shipping()
        {
            return WC()
                ->cart
                ->needs_shipping() === true;
        }



        static function is_whole_cart_approved($el)
        {
            $not_approved_cart_items = array();
            foreach (WC()->cart->get_cart_contents() as $cart_item) {
                if (!in_array($cart_item['product_id'], $el)) {
                    $product = wc_get_product($cart_item['product_id']);
                    $product_title = $product->get_title();
                    $not_approved_cart_items[] = $product_title;
                }
            }
            return empty($not_approved_cart_items) ? array(
                true,
                null
            ) : array(
                false,
                $not_approved_cart_items
            );
        }
        static function get_approved_cart_items($cats, $tags, $ok_pickup_locations = null, $pickup_location = null)
        {
            $approved = array();
            if (
                WC()
                    ->cart
                    ->is_empty()
            ) {
                $approved[] = 'empty_cart';
            }
            foreach (WC()->cart->get_cart_contents() as $cart_item) {
                $product = wc_get_product($cart_item['product_id']);
                if ($product !== null && self::is_schedule_intersection_ok($product, $cats, $tags, $ok_pickup_locations, $pickup_location)) {
                    $approved[] = $cart_item['product_id'];
                }
            }
            return array_values(array_unique($approved));
        }
        static function is_schedule_intersection_ok($product, $cats, $tags, $ok_pickup_locations, $pickup_location)
        {
            $location_ok = is_null($pickup_location) || is_null($ok_pickup_locations) ? true : in_array($pickup_location, $ok_pickup_locations);
            $cats_ok = in_array('tpfwallcategories', $cats) || !empty(array_intersect($product->get_category_ids(), $cats));
            $tags_ok = is_array($tags) && is_array($product->get_tag_ids()) && !empty(array_intersect($product->get_tag_ids(), $tags));
            if ((($cats_ok && empty($tags)) || ($tags_ok && empty($cats)) || ($cats_ok && $tags_ok)) && $location_ok) {
                return true;
            } else {
                return false;
            }
        }
    } // End of main class

}
$GLOBALS['wc_list_items'] = TPFW::instance();











