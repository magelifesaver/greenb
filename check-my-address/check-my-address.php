<?php
/**
 * Plugin Name: Check My Address for WooCommerce
 * Plugin URI: https://checkmyaddressplugin.com/
 * Description: Let users check if their address is deliverable.
 * Version: 1.5.1
 * Author: Arosoft.se
 * Author URI: https://arosoft.se
 * Developer: Arosoft.se
 * Developer URI: https://arosoft.se
 * Text Domain: check-my-address
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 9.8
 * Requires Plugins: woocommerce
 * Requires at least: 6.3.0
 * Requires PHP: 7.4
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
define('CMA_VERSION', '1.5.1');
define('CMA_PLUGINDIRPATH', plugin_dir_path(__FILE__));
define('CMA_PLUGINDIRURL', plugin_dir_url(__FILE__));
if (!defined('ABSPATH')) {
    exit;
}
register_activation_hook(__FILE__, array(
    'CMA',
    'activate'
));
register_uninstall_hook(__FILE__, array(
    'CMA',
    'uninstall'
));
if (!class_exists('CMA')) {
    /**
     * Main Class CMA
     *
     * @since 1.0
     */
    class CMA {
        // to be run on plugin activation
        protected static $_instance = null;

        public $notices;
        protected $settings;

        public static $is_shortcode;
        public static $is_shortcode_delivery;

        // The Constructor
        public function __construct() {
            
             // Declare compatibility of WooCommerce HPOS order structure
            add_action( 'before_woocommerce_init', function() {
                if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
                }
                } );
                 // Declare checkout blocks compatibility
            add_action('before_woocommerce_init', function () {

                if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
      
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
      
                }
      
                });
            
            add_action('admin_init', array(
                $this,
                'check_environment'
            ));
            add_action('plugins_loaded', array(
                $this,
                'init'
            ) , 10);
            add_action('init', array(
                $this,
                'load_text_domain'
            ));
            add_action('admin_notices', array(
                $this,
                'admin_notices'
            ) , 15);
             add_action( 'init', array( 'CMA_Shortcode_Address', 'init' ) );
        }
        public static function get_is_shortcode() {
            if (!isset(self::$is_shortcode)) {

                self::$is_shortcode = wc_post_content_has_shortcode('checkmyaddress');
            }
            return self::$is_shortcode;
        }

        // Includes plugin files
        public function includes() {
            include_once (ABSPATH . 'wp-admin/includes/plugin.php');
            include_once ('classes/class-cma-shortcodes.php');
            require_once ('includes/cma-template-functions.php');

            require_once ('classes/class-cma-checker.php');

            require_once ('classes/class-cma-settings.php');

            require_once ('classes/class-cma-checker-address.php');

             
             
               // Packages for blocks checkout integration
               
                require_once ('packages/cma-integration/cma-integration.php');

        }
        // Returns new class instance
        public static function instance() {
            if (!isset(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        // Plugin initiation
        public function init() {
            // check if environment is ok
            if (self::get_environment_warning()) {
                return;
            }
            if (is_admin() && get_option('CMA_Activated_Plugin') == 'CMA') {
                delete_option('CMA_Activated_Plugin');
            }
            $this->includes();

            add_action('admin_enqueue_scripts', array(
                $this,
                'admin_enqueue_scripts'
            ) , 99);

            add_filter('style_loader_tag', array(
                $this,
                'add_font_awesome_attributes'
            ) , 100, 2);
           

            add_filter('plugin_action_links_' . plugin_basename(__FILE__) , array(
                $this,
                'add_action_links'
            ));
        }
        // Checks if environment is ok
        public function check_environment() {
            if (is_admin() && get_option('CMA_Activated_Plugin') == 'CMA') {
                delete_option('CMA_Activated_Plugin');
            }
            $environment_warning = self::get_environment_warning();
            if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
                $this->add_admin_notice('bad_environment', 'error', $environment_warning);
                deactivate_plugins(plugin_basename(__FILE__));
            }
        }
        // Checks if WooCommerce is active and if not returns error message
        static function get_environment_warning() {
            include_once (ABSPATH . 'wp-admin/includes/plugin.php');
            if (!defined('WC_VERSION')) {
                return __('Check My Address requires WooCommerce to be activated to work.', 'check-my-address');
                die();
            }

            return false;
        }
        public function add_admin_notice($slug, $class, $message) {
            $this->notices[$slug] = array(
                'class' => $class,
                'message' => $message
            );
        }
        public function admin_notices() {
            foreach ((array)$this->notices as $notice_key => $notice) {
                echo "<div class='" . esc_attr($notice['class']) . "'><p>";
                echo wp_kses($notice['message'], array(
                    'a' => array(
                        'href' => array()
                    )
                ));
                echo '</p></div>';
            }
            unset($notice_key);
        }
        // Add setting link to Plugins page
        public function add_action_links($links) {

            $links_add = array(
                '<a href="' . admin_url('admin.php?page=wc-settings&tab=arocma&section') . '">' . __('Settings', 'check-my-address') . '</a>',
            );

            return array_merge($links, $links_add);
        }

        public function admin_enqueue_scripts() {

            if (WP_DEBUG === true && is_admin()) {
                wp_enqueue_script('cma-admin-script', $this->get_plugin_url('assets/js/cma-admin.js') , array(
                    'jquery',
                    'wp-color-picker'
                ) , CMA_VERSION);

            }
            elseif (is_admin()) {
                wp_enqueue_script('cma-admin-script', $this->get_plugin_url('assets/js/cma-admin.min.js') , array(
                    'jquery',
                    'wp-color-picker'
                ) , CMA_VERSION);

            }
        }
        function add_font_awesome_attributes($html, $handle) {
            if ('cma-order-font-2' === $handle || 'cma-order-font-1' === $handle || 'cma-order-font-3' === $handle) {
                return str_replace("media='all'", 'rel="preload" media="all"', $html);
            }
            return $html;
        }

        
        public function load_text_domain() {
            load_plugin_textdomain('check-my-address', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
        // Gets the url to the plugin
        protected function get_plugin_url($relativePath = '') {
            return untrailingslashit(plugins_url($relativePath, $this->base_path_file()));
        }
        // Gets the class filenamne
        protected function base_path_file() {
            $rc = new \ReflectionClass(get_class($this));
            return $rc->getFileName();
        }
        static function activate() {
            update_option('CMA_Activated_Plugin', 'CMA');

        }
        // to be run on plugin uninstallation
        public static function uninstall() {
            if (get_option('cma_clean_settings', 'no') == 'yes') {
                foreach (wp_load_alloptions() as $option => $value) {
                    if (strpos($option, 'cma_') === 0) {
                        delete_option($option);
                    }
                }
            }
        }

        //Method originally from fuxia @stackexchange
        static function remove_thirdparty_object_filter( $tag, $class, $method ){

        $filters = $GLOBALS['wp_filter'][ $tag ];

        if ( empty ( $filters ) )
        {
            return;
        }

        foreach ( $filters as $priority => $filter )
        {
            foreach ( $filter as $identifier => $function )
            {
                if ( is_array( $function)
                    and is_a( $function['function'][0], $class )
                    and $method === $function['function'][1]
                )
                {
                    remove_filter(
                        $tag,
                        array ( $function['function'][0], $method ),
                        $priority
                    );
                }
            }
        }
    }
        public static function third_party_compatibility(){

           if ( class_exists( 'pi_dtt_shipping_method' ) ){



            // For compatibility with PI Delivery Plugin
              self::remove_thirdparty_object_filter( 'woocommerce_package_rates', 'pi_dtt_shipping_method', 'filterShippingMethodAsPerDeliveryType');




           }
        }
    } // End of main class

}
$Check_My_Address = CMA::instance();
