<?php
/**
 * Plugin Name: Shipping Zones by Drawing Premium for WooCommerce
 * Plugin URI: https://shippingzonesplugin.com
 * Description: Limit shipping with drawn zones or transportation distances and times. Premium version.
 * Version: 3.1.4.3
 * Author: Arosoft.se
 * Author URI: https://arosoft.se
 * Developer: Arosoft.se
 * Developer URI: https://arosoft.se
 * Text Domain: szbd
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 10.3
 * Requires at least: 6.0
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
if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

define('SZBD_PREM_VERSION', '3.1.4.3');
define('SZBD_PREM_PLUGINDIRURL', plugin_dir_url(__FILE__));
define('SZBD_PREM_PLUGINDIRPATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, array(
    'SZBD',
    'activate'
)
);
register_deactivation_hook(__FILE__, array(
    'SZBD',
    'deactivate'
)
);



if (!class_exists('SZBD')) {
    class SZBD
    {
        const TEXT_DOMAIN = 'szbd';
        const POST_TITLE = 'szbdzones';
        const POST_TITLE2 = 'szbdorigins';

        protected static $_instance = null;
        protected $admin;
        public $notices;
        public $products;
        static $store_address;
        public static $message;
        public static $message_original;
        public $shortcode;


        public static function activate()
        {

            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-the-post.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-the-post-shipping-origin.php');
            $originObj = new Sbdorigins_Post();
            $originObj->register_post_szbdorigins();
            $zonesObj = new Sbdzones_Post();
            $zonesObj->register_post_szbdzones();
            self::add_admin_caps();
            flush_rewrite_rules();


        }
        // to be run on plugin deactivation
        public static function deactivate()
        {
            unregister_post_type('szbdzones');
            unregister_post_type('szbdorigins');
            flush_rewrite_rules();
        }
        static function add_admin_caps()
        {
            $admin_capabilities = array(
                'delete_szbdzones',
                'delete_others_szbdzones',
                'delete_private_szbdzones',
                'delete_published_szbdzones',
                'edit_szbdzones',
                'edit_others_szbdzones',
                'edit_private_szbdzones',
                'edit_published_szbdzones',
                'publish_szbdzones',
                'read_private_szbdzones',
                'delete_szbdorigins',
                'delete_others_szbdorigins',
                'delete_private_szbdorigins',
                'delete_published_szbdorigins',
                'edit_szbdorigins',
                'edit_others_szbdorigins',
                'edit_private_szbdorigins',
                'edit_published_szbdorigins',
                'publish_szbdorigins',
                'read_private_szbdorigins'
            );
            $admin = get_role('administrator');
            foreach ($admin_capabilities as $capability) {
                $admin->add_cap($capability);
            }
        }
        public static function instance()
        {
            NULL === self::$_instance and self::$_instance = new self;
            return self::$_instance;
        }

        public function __construct()
        {

            // Declare WooCommerce HPOS compatibility
            add_action('before_woocommerce_init', function () {
                if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
                }
            });
             // Declare checkout blocks compatibility
      add_action('before_woocommerce_init', function () {

        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
      
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
      
        }
      
      });
  


            add_action('wp', array(
                $this,
                'check_conditionals'
            )
            );
            add_action('woocommerce_checkout_update_order_review', 'szbd_clear_wc_shipping_rates_cache');

            add_action('woocommerce_checkout_update_order_review', 'szbd_clear_session');

            

           // Filter shipping rates when blocks loads
            add_action('woocommerce_blocks_loaded', function () {


    
                //Filter shipping rates
               
                if(self::is_arosoft_validation_request()){
                     return;
                }
           
                       
                      
                add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'), 999);
                   
                   
            
            });

            

            // Update shipping rates when updating shipping address from blocks checkout
            add_action('woocommerce_store_api_cart_update_customer_from_request', function ($customer, $request) {

             
             
                self::update_shipping_rates_from_customer_request($request, $customer);
                
            }, 3,2);
             // Update shipping rates when updating shipping method from blocks checkout
             add_action('woocommerce_store_api_cart_select_shipping_rate', function ($package_id, $rate_id, $request) {
             
               
                self::update_shipping_rates_from_new_method_request($request);
                
            }, 3,3);

             // Update shipping rates when new coupon from blocks checkout
             add_action('woocommerce_applied_coupon', function ($coupon) {
             
               
                self:: update_shipping_rates_from_coupon();
                
            }, 3,1);



           


            //Keep this silent for now
           add_action('woocommerce_store_api_cart_update_order_from_request', function ($order, $request) {
             
             

                
            }, 11,2);
            





            add_action('szbd_clear_session', 'szbd_clear_session');



            add_action('szbd_clear_shipping_rates_cache', 'szbd_clear_wc_shipping_rates_cache');


            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                $this,
                'add_action_links'
            )
            );
            add_action('init', array(
                $this,
                'load_text_domain'
            )
            );
            add_action('admin_init', array(
                $this,
                'check_environment'
            )
            );
            add_action('woocommerce_loaded', array(
                $this,
                'init'
            ), 10);
            add_action('admin_notices', array(
                $this,
                'admin_notices'
            ), 15);
              add_action('wp_enqueue_scripts', array(
                  $this,
                  'maybe_enqueue_scripts'
              ) , 998);

           

            add_action('wp', array(
                $this,
                'init_shortcode'
            )
            );
            add_filter('manage_edit-szbdzones_columns', array(
                $this,
                'posts_columns_id'
            ), 2);
            add_action('manage_posts_custom_column', array(
                $this,
                'posts_custom_id_columns'
            ), 5, 2);
            add_filter('manage_edit-szbdorigins_columns', array(
                $this,
                'posts_columns_id_origin'
            ), 2);
            add_action('manage_posts_custom_column', array(
                $this,
                'posts_custom_id_columns_origin'
            ), 5, 2);
            add_filter('woocommerce_cart_ready_to_calc_shipping', array($this, 'szbd_disable_shipping_calc_on_cart'), 999);

            add_filter('woocommerce_email_recipient_new_order', array(
                $this,
                'new_order_filter_recipient'
            ), 998, 2);



            $placement = get_option('szbd_precise_address', 'no') != 'no' ? get_option('szbd_map_placement', 'before_payment') : 'none';
            switch ($placement) {
                case 'none':
                    break;
                case 'before_details':
                    add_action('woocommerce_checkout_before_customer_details', array(
                        $this,
                        'insert_to_checkout'
                    )
                    );
                    break;
                case 'before_payment':
                    add_action('woocommerce_review_order_before_payment', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                case 'after_order_notes':
                    add_action('woocommerce_after_order_notes', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                case 'before_order_review':
                    add_action('woocommerce_checkout_before_order_review', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;
                case 'after_billing_form':
                    add_action('woocommerce_after_checkout_billing_form', array(
                        $this,
                        'insert_to_checkout'
                    ), 99);
                    break;



                    break;

            }

            add_action('woocommerce_checkout_update_order_meta', array(
                $this,
                'szbd_checkout_field_update_order_meta'
            ), 10, 1);
            add_action('woocommerce_admin_order_data_after_shipping_address', array(
                $this,
                'szbd_show_checkout_field_admin_order_meta'
            ), 10, 1);
            add_action('woocommerce_email_order_meta', array(
                $this,
                'szbd_add_picked_location_to_emails'
            ), 20, 4);
            add_action('woocommerce_thankyou', array(
                $this,
                'szbd_add_picked_location_to_thankyou_page'
            ), 99, 1);
            add_action('woocommerce_checkout_process', array(
                $this,
                'validate_checkout_field_process'
            )
            );
            add_action('woocommerce_admin_order_preview_start', array(
                $this,
                'szbd_preview_meta'
            )
            );

            add_action('woocommerce_order_details_after_customer_details', array(
                $this,
                'szbd_add_picked_location_to_order_customer_details'
            ), 99, 1);

         // Add "not any shipping methods avalible" even if local_pickup exists
        add_action('woocommerce_review_order_after_shipping',function(){

        if(get_option('szbd_servermode_message', 'no') == 'yes' && ( is_null(self::$message_original) || empty(self::$message_original) )&& !is_null(self::$message) && !empty(self::$message)){

          ob_start();
          echo '</tr><tr><th>&nbsp;</th><td>';
         _e( self::$message,'szbd');
         echo '</td>';

         echo ob_get_clean(); 

        }


      });    
           


        }

        public static function is_arosoft_validation_request(){
            if(isset($_POST['action']) && ( $_POST['action'] == 'cma_validate_shipping_address' || $_POST['action'] == 'cma_validate_shipping_location_' || $_POST['action'] == 'cmp_validate_postcode')){
            
                return true;
            }
            
            return false;
            
            }

            static function update_shipping_rates_from_customer_request($request, $customer)
            {
               
                
                if (get_option('szbd_server_mode', 'yes') != 'yes') {
                    return;
                }
    
    
    
    
                // Skip recalculate shipping logic if client sends empty shipping adress update
                if ((isset($request['shipping_address']) && empty($request['shipping_address']))) {
    
                    szbd_clear_wc_shipping_rates_cache();
    
                } else {
                    szbd_clear_session();
                    $delivery_location = json_decode($request['shipping_address']['szbd/shipping_point']);
                  
                    if ($delivery_location->lat != null) {
                        WC()
                            ->session
                            ->set('szbd_delivery_address', (object) ['lat' => (float) $delivery_location->lat, 'lng' => (float) $delivery_location->lng, 'fromUI' => true]);
    
                    }
    
                    szbd_clear_wc_shipping_rates_cache();
    
                }
    
                add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'), 999);
            }
static function update_shipping_rates_from_new_method_request($request){
    if (get_option('szbd_server_mode', 'yes') != 'yes') {
        return;
    }



 

    add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'),999);
}

static function update_shipping_rates_from_coupon(){
    if (get_option('szbd_server_mode', 'yes') != 'yes') {
        return;
    }

   

 

    add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'),999);
}

        function new_order_filter_recipient($recipient, $order)
        {

            if (!$order instanceof WC_Order) {
                return $recipient;
            }
            if (!is_null($order) && !OrderUtil::is_order($order->get_id(), wc_get_order_types())) {
                return $recipient;
            }


            $meta = $order->get_meta('szbd_origin_email', true);


            if (isset($meta) && $meta != '' && is_email($meta)) {
                $recipient = $meta;
            }




            return $recipient;
        }


        function get_shipping_origins()
        {


            $origins = array(__("Main Location", 'szbd'));

            $args_ori = array(
                'numberposts' => -1,
                'posts_per_page' => -1,
                'post_type' => 'szbdorigins',
                'post_status' => 'publish',
                'orderby' => 'title',


                'category' => 0,

                'order' => 'DESC',
                'include' => array(),
                'exclude' => array(),
                'meta_key' => '',
                'meta_value' => '',

                'suppress_filters' => true,
            );
            $origin_posts = get_posts($args_ori);

            $attr_option_ = array();
            if (is_array($origin_posts) || is_object($origin_posts)) {

                $calc_1_ = array();
                foreach ($origin_posts as $calc_2_) {
                    setup_postdata($calc_2_);
                    $calc_3_ = get_the_title($calc_2_);
                    $calc_1_[] = $calc_3_;


                }
                $attr_option_ = array_merge($attr_option_, $calc_1_);


            }

            $origins = array_merge($origins, $attr_option_);
            wp_reset_postdata();
            return $origins;
        }

        function check_conditionals()
        {
            if (get_option('szbd_server_mode', 'yes') == 'yes') {

                add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'),999);

  

                if (is_cart() && get_option('szbd_enable_at_cart', 'no') == 'yes') {

                    add_action('woocommerce_before_calculate_totals', 'szbd_clear_session');

                    add_filter('woocommerce_cart_shipping_packages', array($this, 'wc_shipping_rate_cache_invalidation'), 100);



                }

               


                //Testing checkout blocks - used to clear point session on every page load
                if (is_checkout() && class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page( get_the_ID(), 'woocommerce/checkout' )) {

                    add_action('woocommerce_checkout_init', 'szbd_clear_session');
                }
               



            }

        }
        function wc_shipping_rate_cache_invalidation($packages)
        {

            foreach ($packages as &$package) {
                $package['rate_cache'] = wp_rand(0, 1000);
            }

            return $packages;
        }


        public function maybe_enqueue_scripts()
        {

            if (is_checkout() && class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page( get_the_ID(), 'woocommerce/checkout' )) {

                
            }
           else if (get_option('szbd_server_mode', 'yes') == 'no') {
                self::enqueue_scripts_aro();
            } else {
                self::enque_scripts_server_mode();
            }

            if (wc_post_content_has_shortcode('szbd') || get_option('szbd_force_shortcode', 'no') == 'yes') {
                self::enqueue_shortcode_scripts();
            }
        }
        static function enqueue_scripts_aro()
        {

            $do_cart = get_option('szbd_enable_at_cart', 'no') == 'yes' && is_cart();

            if (
                !(is_checkout() || $do_cart) || WC()
                    ->cart
                    ->needs_shipping() === false || is_wc_endpoint_url('order-pay')
            ) {
                return;
            }

            if (class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_pickup' && get_option('fdoe_disable_checkout_validation', 'no') !== 'yes' && (get_option('fdoe_skip_address_validation', 'no') !== 'yes' && 'skip' !== WC()->session->get('fdoe_bypass_validation', false))) {
                return;
            }
            if (class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_delivery' && ('local_pickup' == WC()->session->get('fdoe_shipping') || 'eathere' == WC()->session->get('fdoe_shipping'))) {
                return;
            }
            if ($do_cart) {
                add_action('woocommerce_calculated_shipping', 'szbd_clear_session');
            }


            $country_pos = null;
            if (get_option('szbd_precise_address', 'no') != 'no') {

                $request = wp_remote_get(SZBD_PREM_PLUGINDIRURL . 'assets/json/countries.json');


                if (!is_wp_error($request)) {
                    $body = wp_remote_retrieve_body($request);
                    $country_pos = json_decode($body);
                }
            }
            $to_localize = array(
                'customer_stored_location' => get_option('szbd_auto_marker_saved', 'no') == 'yes' ? get_user_meta(get_current_user_id(), 'shipping_szbd-picked-location', true) : null,
                'countries' => $country_pos,
                'checkout_string_1' => __('There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce'),
                'checkout_string_2' => __('Minimum order value is', 'szbd'),
                'checkout_string_3' => __('You are too far away. We only make deliveries within', 'szbd'),
                'checkout_string_4' => __('Some items in your cart don’t ship to your location', 'szbd'),
                'cart_string_1' => __('More shipping alternatives may exist when a full shipping address is entered.', 'szbd'),

                'no_marker_error' => __('You have to precise a location at the map', 'szbd'),
                'store_address' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? json_decode(get_option('SZbD_settings_test', ''), true) : SZBD::get_store_address('comma_seperated'),

                'debug' => get_option('szbd_debug', 'no') == 'yes' ? 1 : 0,
                'deactivate_postcode' => get_option('szbd_deactivate_postcode', 'no') == 'yes' ? 1 : 0,
                'select_top_method' => get_option('szbd_select_top_method', 'no') == 'yes' ? 1 : 0,
                'store_address_picked' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? 1 : 0,
                'precise_address' => get_option('szbd_precise_address', 'no'),
                'nonce' => wp_create_nonce('szbd-script-nonce'),
                'is_cart' => $do_cart ? 1 : 0,
                'is_checkout' => is_checkout() ? 1 : 0,
                'auto_marker' => get_option('szbd_auto_marker', 'no') == 'yes' ? 1 : 0,
                'is_custom_types' => get_option('szbd_types_custom', 'no') == 'yes' ? 1 : 0,
                'result_types' => get_option('szbd_result_types', array(
                    "establishment",
                    "subpremise",
                    "premise",
                    "street_address",
                    "plus_code"
                )
                ),
                'no_map_types' => get_option('szbd_no_map_types', array(
                    "establishment",
                    "subpremise",
                    "premise",
                    "street_address",
                    "plus_code",
                    "route",
                    "intersection"
                )
                ),
                'iw_areaLabel' =>  __("Your latest shipping location",'szbd'),
				'iw_content'  => __("This was the shipping location for your last delivery.",'szbd'),
                'maptype' => get_option('szbd_map_type','roadmap'),
                'mapid' => !empty(get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP')) ? get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP') : 'SZBD_CHECKOUT_MAP' ,  


            );

           if ((is_checkout() || $do_cart) ) {

                if (WP_DEBUG === true) {
                    wp_enqueue_script('shipping-del-aro', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-prem.js', array(
                        'jquery',
                        'wc-checkout',
                        'underscore'

                    ), SZBD_PREM_VERSION, true);
                } else {
                    wp_enqueue_script('shipping-del-aro', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-prem.min.js', array(
                        'jquery',
                        'wc-checkout',
                        'underscore'

                    ), SZBD_PREM_VERSION, true);
                }
                wp_localize_script('shipping-del-aro', 'szbd', $to_localize);
                 $google_api_key = get_option( 'szbd_google_api_key', '' );
                wp_add_inline_script( 'shipping-del-aro', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. SZBD Client Checkout, Ignoring...",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                key: "'.$google_api_key.'",
                v: "quarterly",});','before' );
                wp_enqueue_style('shipping-del-aro-style', SZBD_PREM_PLUGINDIRURL . '/assets/szbd.css', array(), SZBD_PREM_VERSION);

            }
        }
       
        static function enque_scripts_server_mode()
        {


            if (
                !is_checkout() || WC()
                    ->cart
                    ->needs_shipping() === false || is_wc_endpoint_url('order-pay')
            ) {
                return;
            }
            if (get_option('szbd_precise_address', 'no') == 'no') {

                return;
            }

            if (class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_pickup' && get_option('fdoe_disable_checkout_validation', 'no') !== 'yes' && (get_option('fdoe_skip_address_validation', 'no') !== 'yes' && 'skip' !== WC()->session->get('fdoe_bypass_validation', false))) {
                return;
            }
            if (class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_delivery' && ('local_pickup' == WC()->session->get('fdoe_shipping') || 'eathere' == WC()->session->get('fdoe_shipping'))) {
                return;
            }




            $country_pos = null;
            if (get_option('szbd_precise_address', 'no') != 'no') {

                $request = wp_remote_get(SZBD_PREM_PLUGINDIRURL . 'assets/json/countries.json');


                if (!is_wp_error($request)) {
                    $body = wp_remote_retrieve_body($request);
                    $country_pos = json_decode($body);
                }
            }
            $to_localize = array(
                'customer_stored_location' => get_option('szbd_auto_marker_saved', 'no') == 'yes' ? get_user_meta(get_current_user_id(), 'shipping_szbd-picked-location', true) : null,
                'countries' => $country_pos,
                'checkout_string_1' => __('There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce'),
                'checkout_string_2' => __('Minimum order value is', 'szbd'),
                'checkout_string_3' => __('You are too far away. We only make deliveries within', 'szbd'),
                'checkout_string_4' => __('Some items in your cart don’t ship to your location', 'szbd'),
                'cart_string_1' => __('More shipping alternatives may exist when a full shipping address is entered.', 'szbd'),

                'no_marker_error' => __('You have to precise a location at the map', 'szbd'),
                'store_address' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? json_decode(get_option('SZbD_settings_test', ''), true) : SZBD::get_store_address(),

                'debug' => get_option('szbd_debug', 'no') == 'yes' ? 1 : 0,
                'deactivate_postcode' => get_option('szbd_deactivate_postcode', 'no') == 'yes' ? 1 : 0,
                'select_top_method' => get_option('szbd_select_top_method', 'no') == 'yes' ? 1 : 0,
                'store_address_picked' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? 1 : 0,
                'precise_address' => get_option('szbd_precise_address', 'no'),
                'nonce' => wp_create_nonce('szbd-script-nonce'),

                'is_checkout' => is_checkout() ? 1 : 0,
                'auto_marker' => get_option('szbd_auto_marker', 'no') == 'yes' ? 1 : 0,
                'is_custom_types' => get_option('szbd_types_custom', 'no') == 'yes' ? 1 : 0,
                'result_types' => get_option('szbd_result_types', array(
                    "establishment",
                    "subpremise",
                    "premise",
                    "street_address",
                    "plus_code"
                )
                ),
                'no_map_types' => get_option('szbd_no_map_types', array(
                    "establishment",
                    "subpremise",
                    "premise",
                    "street_address",
                    "plus_code",
                    "route",
                    "intersection"
                )
                ),
                'iw_areaLabel' =>  __("Your latest shipping location",'szbd'),
				'iw_content'  => __("This was the shipping location for your last delivery.",'szbd'),
                'maptype' => get_option('szbd_map_type','roadmap'),
                'mapid' => !empty(get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP')) ? get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP') : 'SZBD_CHECKOUT_MAP' ,  


            );

            if ((is_checkout()) ) {

                if (WP_DEBUG === true) {
                    wp_enqueue_script('shipping-del-aro', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-checkout-map.js', array(
                        'jquery',
                        'wc-checkout',
                        'underscore'

                    ), SZBD_PREM_VERSION, true);
                } else {
                    wp_enqueue_script('shipping-del-aro', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-checkout-map.min.js', array(
                        'jquery',
                        'wc-checkout',
                        'underscore'

                    ), SZBD_PREM_VERSION, true);
                }
                wp_localize_script('shipping-del-aro', 'szbd', $to_localize);

                 $google_api_key = get_option( 'szbd_google_api_key', '' );
                wp_add_inline_script( 'shipping-del-aro', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. SZBD Checkout, Ignoring...",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                key: "'.$google_api_key.'",
                v: "quarterly",});','before' );

                wp_enqueue_style('shipping-del-aro-style', SZBD_PREM_PLUGINDIRURL . '/assets/szbd-checkout-map.css', array(), SZBD_PREM_VERSION);
            }

        }
        static function enqueue_shortcode_scripts()
        {
            $deps = array(
                'jquery',
                'underscore'
            );
          
            if (WP_DEBUG === true) {
                wp_enqueue_style('szbd-style-shortcode', SZBD_PREM_PLUGINDIRURL . 'assets/style-shortcode.css', array(), SZBD_PREM_VERSION);
                wp_register_script('szbd-script-short', SZBD_PREM_PLUGINDIRURL . 'assets/szbd-shortcode.js', $deps, SZBD_PREM_VERSION, array('strategy'=> 'async','in_footer'=> true) );
            } else {
                wp_enqueue_style('szbd-style-shortcode', SZBD_PREM_PLUGINDIRURL . 'assets/style-shortcode.min.css', array(), SZBD_PREM_VERSION);
                wp_register_script('szbd-script-short', SZBD_PREM_PLUGINDIRURL . 'assets/szbd-shortcode.min.js', $deps, SZBD_PREM_VERSION, array('strategy'=> 'async','in_footer'=> true) );
            }
            wp_enqueue_script('szbd-script-short');
            wp_localize_script('szbd-script-short', 'szbd_map_monitor', array(
                'monitor' => get_option('szbd_monitor', 'no') == 'yes' ? 1 : 0
            )
            );
        }
        // Includes plugin files
        public function includes()
        {
            
          
           // Packages for blocks checkout
           if (get_option('szbd_server_mode', 'yes') == 'yes') {

           


                if ( CartCheckoutUtils::is_checkout_block_default() ){
                     require_once (SZBD_PREM_PLUGINDIRPATH . 'packages/szbd-shipping-message/szbd-shipping-message.php');
                     require_once (SZBD_PREM_PLUGINDIRPATH . 'packages/szbd-method-selection/szbd-method-selection.php');
                     require_once (SZBD_PREM_PLUGINDIRPATH . 'packages/szbd-shipping-map/szbd-shipping-map.php');
                  }
            
            
          
        }
          
                
           
            if (is_admin()) {
                require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-settings.php');
                require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-admin.php');
                $this->admin = new SZBD_Admin();
            }
            require_once(SZBD_PREM_PLUGINDIRPATH . 'includes/szbd-template-functions.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-google-server-request.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-ajax.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-helping-classes.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-shippingmethod.php');

            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-the-post.php');
            require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-the-post-shipping-origin.php');

            new Sbdorigins_Post();

            new Sbdzones_Post();

        }
        public function init_shortcode()
        {
            if (!is_admin() && !wp_doing_ajax() && !self::get_environment_warning()) {
                require_once(SZBD_PREM_PLUGINDIRPATH . 'classes/class-szbd-shortcode.php');
                $this->shortcode = SZBD_Shortcode::instance();
            }
        }
        // For use in future versions. Loads text domain files
        public function load_text_domain()
        {
            load_plugin_textdomain(SZBD::TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
        public function insert_to_checkout()
        {
            if (
                !is_checkout() || WC()
                    ->cart
                    ->needs_shipping() === false
            ) {
                return;
            }
            if (
                class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_pickup' && get_option('fdoe_disable_checkout_validation', 'no') !== 'yes' && (get_option('fdoe_skip_address_validation', 'no') !== 'yes' && 'skip' !== WC()
                    ->session
                    ->get('fdoe_bypass_validation', false))
            ) {
                return;
            }
            if (
                class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_delivery' && ('local_pickup' == WC()
                    ->session
                    ->get('fdoe_shipping') || 'eathere' == WC()
                        ->session
                        ->get('fdoe_shipping'))
            ) {
                return;
            }

            $class = '';
            $option = get_option('szbd_precise_address', 'no');
            if ($option == 'no')
            {
            return;
            }
          else if ($option == 'at_fail') {
                $class = 'szbd-hide';
            }
            if (get_option('szbd_precise_address_mandatory', 'no') == 'yes') {
                $string = __('Please Precise Your Location', 'szbd');
            } else {
                $string = __('Precise Address?', 'szbd');
            }
            ob_start();
            echo '<div id="szbd_checkout_field" class="shop_table ' . $class . '"><h3>' . $string . '</h3>';
            if (get_option('szbd_precise_address_plus_code', 'no') == 'yes') {
                self::insert_plus_code_to_checkout();
            }
            woocommerce_form_field('szbd-picked', array(
                'type' => 'text',
                'class' => array(
                    'szbd-hidden'
                ),
                'label' => __('Precise Address?', 'szbd'),
            )
            );
            woocommerce_form_field('szbd-map-open', array(
                'type' => 'checkbox',
                'class' => array(
                    'szbd-hidden'
                ),
            )
            );

         
            echo '<div id="szbd-pick-content"><div class="szbd-checkout-map"><div id="szbd_map"></div></div></div></div>';
            echo ob_get_clean();


        }
        function szbd_disable_shipping_calc_on_cart($show_shipping)
        {

            if (is_cart() && get_option('szbd_hide_shipping_cart', 'no') == 'yes') {
                return false;
            }
            return $show_shipping;
        }
        public static function insert_plus_code_to_checkout()
        {

            woocommerce_form_field('szbd-plus-code', array(
                'type' => 'text',

                'class' => array(
                    'szbd-plus-code-form'
                ),
                'label' => __('Find Location with Google Plus Code', 'szbd'),
                'placeholder' => __('Enter Plus Code...', 'szbd'),
            )
            );
        }
        function validate_checkout_field_process()
        {
            if ((get_option('szbd_precise_address_mandatory', 'no') == 'yes' && get_option('szbd_precise_address', 'no') == 'always' && isset($_POST['szbd-picked']) && !$_POST['szbd-picked']) ||
             (get_option('szbd_precise_address_mandatory', 'no') == 'yes' && get_option('szbd_precise_address', 'no') == 'at_fail' && isset($_POST['szbd-picked']) && !$_POST['szbd-picked'] && isset($_POST['szbd-map-open']) && 1 == $_POST['szbd-map-open'])) {
                wc_add_notice(__('Please precise your address at the map', 'szbd'), 'error');
            }
        }
        // Add setting link to Plugins page
        public function add_action_links($links)
        {
            if (plugin_basename(__FILE__) == "shipping-zones-by-drawing-for-woocommerce/shipping-zones-by-drawing.php") {
                $links_add = array(
                    '<a href="' . admin_url('admin.php?page=wc-settings&tab=szbdtab') . '">Settings</a>',
                    '<a target="_blank" href="https://shippingzonesplugin.com">Go Premium</a>'
                );
            } else {
                $links_add = array(
                    '<a href="' . admin_url('admin.php?page=wc-settings&tab=szbdtab') . '">' . __('Settings', 'woocommerce') . '</a>',
                    '<a target="_blank" href="https://arosoft.se/wordpress-plugins/shipping-zones-by-drawing-documentation">' . __('Documentation', 'szbd') . '</a>',

                );
            }
            return array_merge($links, $links_add);
        }
        // Checks if WooCommerce is active and if not returns error message
        static function get_environment_warning()
        {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            if (!defined('WC_VERSION')) {
                return __('Shipping Zones by Drawing requires WooCommerce to be activated to work.', SZBD::TEXT_DOMAIN);
                die();
            }
            //if this is Premium
            else if (is_plugin_active('shipping-zones-by-drawing-for-woocommerce/shipping-zones-by-drawing.php')) {
                return __('Shipping Zones by Drawing Premium can not be activated when the free version is active.', SZBD::TEXT_DOMAIN);
                die();
            }
            // If this is free version
            /*    else if ( is_plugin_active( 'shipping-zones-by-drawing-premium/shipping-zones-by-drawing.php') ) {

            return __( 'Shipping Zones by Drawing can not be activated when the premuim version is active.', SZBD::TEXT_DOMAIN );

            die();

            }*/
            return false;
        }
        // Checks if environment is ok
        public function check_environment()
        {
            $environment_warning = self::get_environment_warning();
            if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
                $this->add_admin_notice('bad_environment', 'error', $environment_warning);
                deactivate_plugins(plugin_basename(__FILE__));
            }
        }
        public function add_admin_notice($slug, $class, $message)
        {
            $this->notices[$slug] = array(
                'class' => $class,
                'message' => $message
            );
        }
        public function admin_notices()
        {
            foreach ((array) $this->notices as $notice_key => $notice) {
                echo "<div class='" . esc_attr($notice['class']) . "'><p>";
                echo wp_kses($notice['message'], array(
                    'a' => array(
                        'href' => array()
                    )
                )
                );
                echo '</p></div>';
            }
            unset($notice_key);
        }
        function posts_columns_id($defaults)
        {
            $defaults['szbd_post_id'] = __('ID');
            return $defaults;
        }
        function posts_columns_id_origin($defaults)
        {
            $defaults['szbdorigin_post_id'] = __('ID');
            return $defaults;
        }
        function posts_custom_id_columns($column_name, $id)
        {
            if ($column_name === 'szbd_post_id') {
                echo $id;
            }
        }
        function posts_custom_id_columns_origin($column_name, $id)
        {
            if ($column_name === 'szbdorigin_post_id') {
                echo $id;
            }
        }

        public function init()
        {
            // check if environment is ok
            if (self::get_environment_warning()) {
                return;
            }
            $this->includes();
        }
        function szbd_checkout_field_update_order_meta($order_id)
        {
           
            $order = wc_get_order($order_id);
            $do_save = false;
            if (isset($_POST['szbd-picked']) && !empty($_POST['szbd-picked']) && $_POST['szbd-picked'] != '') {
                $meta = sanitize_text_field($_POST['szbd-picked']);
                $order->update_meta_data('szbd_picked_delivery_location', stripslashes($meta));
                $do_save = true;
            }
            if (isset($_POST['szbd-plus-code']) && !empty($_POST['szbd-plus-code']) && $_POST['szbd-plus-code'] != '') {
                $meta = sanitize_text_field($_POST['szbd-plus-code']);
                $order->update_meta_data('szbd_picked_delivery_location_plus_code', $meta);
                $do_save = true;
            }

            if (sizeof(array_intersect(wc_get_chosen_shipping_method_ids(), apply_filters('woocommerce_local_pickup_methods', array('legacy_local_pickup', 'local_pickup')))) == 0 && isset($_POST['shipping_method']) && is_array($_POST['shipping_method']) && isset($_POST['shipping_method'][0]) && !empty($_POST['shipping_method'][0]) && $_POST['shipping_method'][0] != '') {
                $meta = sanitize_text_field($_POST['shipping_method'][0]);


                if (($pos = strpos($meta, ":")) !== false) {
                    $instance_id = (int) substr($meta, $pos + 1);

                    $shipping_class_names = WC()->shipping->get_shipping_method_class_names();


                    $method_instance = new $shipping_class_names['szbd-shipping-method']($instance_id);


                    $shipping_origin = $method_instance->shipping_origin;

                    if (is_numeric($shipping_origin)) {
                        $title = get_the_title((int) $shipping_origin);


                        $order->update_meta_data('szbd_shipping_origin', $title);
                        //  echo "<script type='text/javascript'> alert('".json_encode( (int) $shipping_origin  )."') </script>"; 
                        $meta = get_post_meta((int) $shipping_origin, 'szbdorigins_metakey', true);
                        $mail = isset($meta['email']) ? $meta['email'] : '';
                        if ($mail != '' && is_email($mail)) {
                            $order->update_meta_data('szbd_origin_email', $mail);
                        }


                        $do_save = true;
                    } else if ($shipping_origin == 'default') {
                        $title = __('Main Location', 'szbd');
                        $order->update_meta_data('szbd_shipping_origin', $title);
                        $do_save = true;
                    }
                }

            }
            if ($do_save) {
                $order->save();
            }

        }
        static function szbd_get_plus_code($order_id)
        {
            $order = wc_get_order($order_id);
            $output = '';
            $meta = $order->get_meta('szbd_picked_delivery_location_plus_code', true);
            if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {

                $output .= '<p><strong>' . __('Plus Code', 'szbd') . ': </strong>' . $meta . '</p>';
            }
            return $output;
        }
        function szbd_show_checkout_field_admin_order_meta($order)
        {

            if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
                if (!OrderUtil::is_order($order->get_id(), wc_get_order_types())) {
                    return;
                }
            } else {

                if ('shop_order' !== get_post_type($order->get_id())) {
                    return;
                }
            }
            $meta = $order->get_meta('szbd_picked_delivery_location', true);
            if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
                $location = json_decode($meta);
                if ($location !== null && isset($location->lat)) {
                    $lat = $location->lat;
                    $long = $location->lng;
                    echo '<p><strong>' . __('Picked Delivery Location', 'szbd') . ':</strong> ';
                    echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
                    echo self::szbd_get_plus_code($order->get_id());
                }
            }
        }
        function szbd_add_picked_location_to_emails($order, $sent_to_admin, $plain_text, $email)
        {

            if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
                if (!OrderUtil::is_order($order->get_id(), wc_get_order_types())) {
                    return;
                }
            } else {

                if ('shop_order' !== get_post_type($order->get_id())) {
                    return;
                }
            }
            $meta = $order->get_meta('szbd_picked_delivery_location', true);
            if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
                $location = json_decode($meta);
                if ($location !== null && isset($location->lat)) {
                    if ($email->id == 'customer_processing_order') {
                        $lat = $location->lat;
                        $long = $location->lng;
                        echo '<p><strong>' . __('Picked Delivery Location', 'szbd') . ':</strong> ';
                        echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';

                    }
                    if ($email->id == 'customer_completed_order') {
                        $lat = $location->lat;
                        $long = $location->lng;
                        echo '<p><strong>' . __('Picked Delivery Location', 'szbd') . ':</strong>';
                        echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
                        echo self::szbd_get_plus_code($order->get_id());
                    }
                    if ($email->id == 'new_order') {
                        $lat = $location->lat;
                        $long = $location->lng;
                        echo '<p><strong>' . __('Picked Delivery Location', 'szbd') . ':</strong>';
                        echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
                        echo self::szbd_get_plus_code($order->get_id());
                    }
                }
            }


            if (get_option('szbd_origin_table', 'no') == 'yes' && $email->id == 'new_order') {

                $meta3 = $order->get_meta('szbd_shipping_origin', true);
                if (is_string($meta3) && $meta3 != '' && !empty($meta3) && $meta3 !== false && $meta3 != 'undefined') {


                    echo '<p><strong>' . __('Shipping Origin', 'szbd') . ': </strong> ';
                    echo $meta3 . '</p>';

                }
            }



        }
         // Add shipping meta to order details - my-account , [woocommerce_order_tracking], Thankyou page etc.
        function szbd_add_picked_location_to_order_customer_details($order)
        {
          
            $meta = $order->get_meta('szbd_picked_delivery_location', true);
            if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
                $location = json_decode($meta);
                if ($location !== null && isset($location->lat)) {
                    $lat = $location->lat;
                    $long = $location->lng;
                    echo '<p><strong>' . __( 'Picked Delivery Location', 'szbd' ) . '</strong>';
                    echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
                  
                }

                
        }

        $pluscode = $order->get_meta('szbd_picked_delivery_location_plus_code', true);
        if ( $pluscode != '' ) {
            echo '<p><strong>' . __( 'Shipping Pluscode', 'szbd' ) . '</strong></p>';
            echo '<p>' . $pluscode . '</p>';

    }
    }
    
    // Add shipping meta to thankyou page - blocks and old checkout page
    function szbd_add_picked_location_to_thankyou_page($order_id)
        {
            // We now insert this meta from order details hook
            return;
            $order = wc_get_order($order_id);
            $meta = $order->get_meta('szbd_picked_delivery_location', true);
            if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
                $location = json_decode($meta);
                if ($location !== null && isset($location->lat)) {
                    $lat = $location->lat;
                    $long = $location->lng;
                    echo '<p><strong>' . __('Picked Delivery Location', 'szbd') . ':</strong> ';
                    echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
                   // echo self::szbd_get_plus_code($order_id);
                }
            }
            $pluscode = $order->get_meta('szbd_picked_delivery_location_plus_code', true);
        if ( $pluscode != '' ) {
            echo '<p><strong>' . __( 'Shipping Pluscode', 'szbd' ) . '</strong></p>';
            echo '<p>' . $pluscode . '</p>';

    }
        }
        function szbd_preview_meta()
        {
            ?>
            <# var location, pluscode; _.each(data.data.meta_data,function(el,i){ if ( el.key=='szbd_picked_delivery_location' ) {
                try{ let obj=JSON.parse(el.value); location='https://www.google.com/maps/search/?api=1&query=' + obj.lat + ',' +
                obj.lng; }catch(err){} } if ( el.key=='szbd_picked_delivery_location_plus_code' ) { try{ pluscode=el.value;
                }catch(err){} } }); if(typeof location !=='undefined' ){ #>

                <div class="wc-order-preview-addresses">
                    <div class="wc-order-preview-address">
                        <h2>
                            <?php esc_html_e('Picked delivery location', 'szbd'); ?>
                        </h2>
                        <# if(typeof pluscode !=='undefined' ){ #>

                            <p><strong>
                                    <?php esc_html_e('Plus Code', 'szbd'); ?>
                                </strong>

                                {{ pluscode }} </p>


                            <# } #>
                                <a target="_blank" href="{{ location }}">
                                    <?php esc_html_e('Open delivery location with Google Maps', 'szbd') ?>
                                </a>

                    </div>
                </div>
                <# } #>


                    <?php
        }
        public static function get_cart_products()
        {
            $products = array();
            foreach (WC()->cart->get_cart_contents() as $cart_item) {
                $product = wc_get_product($cart_item['product_id']);
                if ($product !== null) {
                    $products[] = $product;
                }
            }
            return empty($products) ? null : $products;
        }
        public static function is_cart_ok($cats_)
        {
            $products = isset(self::$products) ? self::$products : self::get_cart_products();
            $cats = $cats_;
            if (is_array($products) && is_array($cats)) {
                foreach ($products as $product) {
                    if (empty(array_intersect($product->get_category_ids(), $cats))) {
                        return false;
                    }
                }
            }
            return true;
        }
        // Used if to filter out shipping methods with non allowed categories
        public static function szbd_filter_shipping_methods_for_checkout($rates)
        {

            $ok_methods = array();
            foreach ($rates as $rate_id => $rate) {
                if ($rate->method_id == 'szbd-shipping-method') {
                    $shipping_class_names = WC()
                        ->shipping
                        ->get_shipping_method_class_names();
                    $method_instance = new $shipping_class_names['szbd-shipping-method']($rate->get_instance_id());

                    if ((empty($method_instance->ok_categories) || self::is_cart_ok($method_instance->ok_categories))) {
                        $ok_methods[$rate_id] = $rate;
                    } else {
                        continue;
                    }
                } else {
                    $ok_methods[$rate_id] = $rate;
                }
            }
            return $ok_methods;
        }
        // Used if to filter out shipping methods at server mode
        public static function szbd_filter_shipping_methods_for_checkout_server_mode($rates)
        {
           
            if (is_cart() && get_option('szbd_enable_at_cart', 'no') == 'no') {

                return $rates;
            }
            // Keep this for backward compatibility. This filter is removed from food online premium since version 5.4.1.10 and then no longer needed here       
            if (!CartCheckoutUtils::is_checkout_block_default() && class_exists('Food_Online_Del') && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' && get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_pickup' && get_option('fdoe_disable_checkout_validation', 'no') !== 'yes' && (get_option('fdoe_skip_address_validation', 'no') !== 'yes' && 'skip' !== WC()->session->get('fdoe_bypass_validation', false))) {
                return $rates;
            }

            $ok_methods = array();
            $not_ok_methods = array();
            $pickup_location_methods = array();
            $ok_methods_else = array();
            $min = array();
            foreach ($rates as $rate_id => $rate) {
             
                if ($rate->method_id == 'szbd-shipping-method') {
                    $shipping_class_names = WC()
                        ->shipping
                        ->get_shipping_method_class_names();
                    $method_instance = new $shipping_class_names['szbd-shipping-method']($rate->get_instance_id());

                    $minamountok = szbd_minAmountOk($method_instance);


                    if (
                       

                        (empty($method_instance->ok_categories) || self::is_cart_ok($method_instance->ok_categories)) &&
                        $minamountok &&
                        szbd_polygonContainsPoint($method_instance) &&
                        szbd_radiusIsOk($method_instance) &&
                        szbd_distanceOk($method_instance) &&
                        szbd_durationOk($method_instance)


                    ) {
                        // Check if free shipping coupon exists
                        if ( $method_instance->coupons_freeshipping == 'yes' ) {
                            $coupons = WC()->cart->get_coupons();
                
                            if ( $coupons ) {
                                foreach ( $coupons as $code => $coupon ) {
                                    if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
                                        $rate->cost = 0;
                                        break;
                                    }
                                }
                            }
                        }

                        $ok_methods[$rate_id] = $rate;
                        // Filter out the lowest cost methods
                        if (get_option('szbd_exclude_shipping_methods', 'no') == 'yes') {
                            foreach ($ok_methods as $rate_id_ => $rate_) {
                                if ($rate_->method_id != 'szbd-shipping-method') {

                                    continue;
                                }
                                if ($rate->cost < $rate_->cost) {

                                    unset($ok_methods[$rate_id_]);

                                } elseif ($rate->cost > $rate_->cost) {

                                    unset($ok_methods[$rate_id]);
                                } 
                            }
                            if(sizeof($ok_methods) > 1){
                             array_pop($ok_methods);
                            }
                           
                        }
                    } else {

                        // Collect data about why this method is unavalible. The data can be used to build front-end messages to the customer
                        if ( get_option('szbd_servermode_message', 'no') == 'yes') {
                            $array = array();

                            if (!empty($method_instance->ok_categories) && !self::is_cart_ok($method_instance->ok_categories)) {

                                $cats = $method_instance->ok_categories;




                                $fail_products = $cats;



                                $array['nonproducts'] = $fail_products;
                                goto next;
                            }


                            $min = !$minamountok ? (float) $method_instance->minamount : false;
                            if (is_numeric($min)) {
                                $array['min'] = $min;
                            }

                            $radius = $method_instance->map == 'radius' && !szbd_radiusIsOk($method_instance);
                            if ($radius) {
                                $array['radius'] = (float) $method_instance->max_radius;
                            }

                            $outside = $method_instance->map != 'none' && $method_instance->map != 'radius' && !szbd_polygonContainsPoint($method_instance);
                            if ($outside) {
                                $array['outside'] = true;
                            }




                            $outside = !szbd_distanceOk($method_instance) || !szbd_durationOk($method_instance);
                            if ($outside) {
                                $array['outside'] = true;
                            }

                            next:

                           


                            $not_ok_methods[$rate_id] = $array;
                        }

                        continue;
                    }
                } else {
                    // Check if is a pickup method. Add 3rd party methods for compatibility...
                 if($rate->method_id == 'pickup_location' || $rate->method_id == 'local_pickup' || $rate->method_id == 'ds_local_pickup'){
                    $pickup_location_methods[$rate_id] = $rate;
                   }else{
                    $ok_methods_else[$rate_id] = $rate;
                   }
                   
                }
            }

          

            
            $new_shipping_rates = array_merge($ok_methods, $ok_methods_else);
          
              // Change message at checkout if no ordinary shipping methods are avalible
              if (get_option('szbd_servermode_message', 'no') == 'yes' && empty( $new_shipping_rates )  ) {

                self::add_checkout_message($not_ok_methods, !empty($pickup_location_methods));
            }
            $new_rates = array_merge($new_shipping_rates, $pickup_location_methods);
            $hierarchy = array_flip( array_keys($rates));
            uksort( $new_rates, fn($a, $b) => $hierarchy[$a] <=> $hierarchy[$b] );
            return $new_rates;
        }

        public static function add_checkout_message($methods, $pickup_exists)
        {

            if (!empty($methods)) {

                foreach ($methods as $rate_id => $rate) {

                    // First, collect minimum order amounts if this is the single cause of why this method can´t be used
                    if (isset($rate['min']) && sizeof($rate) == 1) {
                        $min[] = $rate['min'];

                    }
                    // Now collect categories that can be used with this method. Exclude methods that still not fulfill a minimum order amount rule
                    elseif (isset($rate['nonproducts']) && !isset($rate['radius']) && !isset($rate['outside'])) {

                        if (!isset($rate['min'])) {
                            $cats[] = ($rate['nonproducts']);
                        }

                    }
                    // From here and below, we check if to set this method unavalible because of location rules of the shipping address that are not fulfilled
                    elseif (isset($rate['radius'])) {

                        $radius = true;

                    } elseif (isset($rate['outside'])) {

                        $outside = true;
                    }
                }
                //Loop again to add categories if they has not been added. Now we accept methods with not fulfilled order amounts
                $is_min = isset($min) ? true : false;
                foreach ($methods as $rate_id => $rate) {
                    if (!isset($cats) && isset($rate['nonproducts']) && !isset($rate['radius']) && !isset($rate['outside'])) {

                        $cats[] = ($rate['nonproducts']);
                    }
                }


                // Build the message to show at checkout when no methods are avalible
                $message = isset($min) ? (__('Minimum order value for your address is', 'szbd') . ' ' . wc_price(min($min)) . '.') :
                    (isset($rate['radius']) ? __('Your delivery address is too far away from us to make deliveries.', 'szbd') : (isset($rate['outside']) ? __('We do not make deliveries to your area.', 'szbd') : ''));

                if ($message == '' && isset($cats)) {
                    $message = __('Your products must be of category', 'szbd') . ' ';
                    $index = 0;
                    foreach ($cats as $cat) {
                        $message .= $index == 1 ? ' ' . __('They may also be of category', 'szbd') . ' ' : '';

                        $index2 = 0;
                        foreach ($cat as $ca) {

                            $cat_term = get_term_by('id', (int) $ca, 'product_cat');
                            $message .= $cat_term->name;
                            $message .= sizeof($cat) - 2 == $index2 ? ' ' . __('or') . ' ' : (sizeof($cat) - 2 > $index2 ? ',' : '');


                            $index2++;
                        }
                        $message .= ' ' . __('for the given address', 'szbd') . '.';

                        if ($index == 1) {
                            break;
                        }

                        $index++;
                    }




                }
                $errormessage = html_entity_decode(wp_strip_all_tags($message,true));

                // Save message as a class variable
                SZBD::$message = esc_html($errormessage) ;

                //Filter the original message
              if(!$pickup_exists){
                SZBD::$message_original = esc_html($errormessage) ;
              add_filter('woocommerce_no_shipping_available_html', function ($message_) {

                  $message = SZBD::$message_original;
                  $message = is_string($message) ? $message : $message_;
                  return $message;
              });
            }

            }

        }

        public static function get_customer_address_string($package, $separator = ' ')
        {
            $package['destination']['postcode'] = wc_format_postcode($package['destination']['postcode'], $package['destination']['country']);
            add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats', 999, 1);
            $formatted_address_string = WC()->countries->get_formatted_address($package['destination'], $separator);
            return $formatted_address_string;
        }








        static function get_store_address($format = 'array')
        {

            if (!isset(self::$store_address)) {
                $store_address = get_option('woocommerce_store_address', '');
                $store_address_2 = get_option('woocommerce_store_address_2', '');
                $store_city = get_option('woocommerce_store_city', '');


                $store_raw_country = get_option('woocommerce_default_country', '');
                $split_country = explode(":", $store_raw_country);
                // Country and state
                $store_country = $split_country[0];
                $store_postcode = wc_format_postcode(get_option('woocommerce_store_postcode', ''), $store_country);
                // Convert country code to full name if available
                if (
                    isset(WC()
                        ->countries
                        ->countries[$store_country])
                ) {
                    $store_country = WC()
                        ->countries
                        ->countries[$store_country];
                }
                $store_state = isset($split_country[1]) ? $split_country[1] : '';
                $store_loc = array(
                    'store_address' => $store_address,
                    'store_address_2' => $store_address_2,
                    'store_postcode' => $store_postcode,
                    'store_city' => $store_city,

                    'store_state' => $store_state,
                    'store_country' => $store_country,
                    'store_country_code' => $split_country[0],

                );
                self::$store_address = $store_loc;
            }
            if ($format == 'comma_seperated') {

                $address = array(

                    'address_1' => self::$store_address['store_address'],
                    'address_2' => self::$store_address['store_address_2'],
                    'city' => self::$store_address['store_city'],
                    'state' => self::$store_address['store_state'],
                    'postcode' => self::$store_address['store_postcode'],
                    'country' => self::$store_address['store_country_code'],
                );
                add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats', 999, 1);
                return WC()->countries->get_formatted_address($address, ',') . ', ' . self::$store_address['store_country'];

            }
            return self::$store_address;
        }


    }
}
$GLOBALS['szbd_item'] = SZBD::instance();


// Save picked location checkout field value as user meta data
add_action('woocommerce_checkout_update_customer', 'szbd_save_picked_location_to_customer', 10, 2);
function szbd_save_picked_location_to_customer($customer, $data)
{
    if (!is_user_logged_in() /*|| is_admin()*/)
        return;



    if (isset($_POST['szbd-picked']) && $_POST['szbd-picked'] != '')
        update_user_meta($customer->get_id(), 'shipping_szbd-picked-location', $_POST['szbd-picked']);
}

add_action('show_user_profile', 'szbd_show_picked_location_account_details', 15);
add_action('edit_user_profile', 'szbd_show_picked_location_account_details', 15);
function szbd_show_picked_location_account_details($user)
{
    $meta = get_user_meta($user->ID, 'shipping_szbd-picked-location', true);

    if (empty($meta)) {
        return;
    }
    if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
        $location = json_decode($meta);
        if ($location !== null && isset($location->lat)) {
            $lat = $location->lat;
            $long = $location->lng;




            $dob = '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';
            ?>
                    <h3>
                        <?php esc_html_e('Customer shipping', 'szbd'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th>
                                <?php esc_html_e('Latest shipping location', 'szbd'); ?></label>
                            </th>
                            <td>
                                <?php echo __('Latitude', 'szbd') . ': ' . $lat; ?><br>
                                <?php echo __('Longitude', 'szbd') . ': ' . $long; ?><br>

                                <?php echo ($dob); ?>
                            </td>
                        </tr>
                    </table>
                    <?php
        }
    }

}




add_action('woocommerce_after_edit_address_form_shipping', 'szbd_show_picked_location', 12, 2);


function szbd_show_picked_location($r)
{
   
    $dob = get_user_meta(get_current_user_id(), 'shipping_szbd-picked-location', true);
    $meta = $dob;
    if (is_string($meta) && $meta != '' && !empty($meta) && $meta !== false) {
        $location = json_decode($meta);
        if ($location !== null && isset($location->lat)) {
            $lat = $location->lat;
            $long = $location->lng;
            echo '<p><strong>' . __('Latest Picked Shipping Location', 'szbd') . ':</strong> ';
            echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $long . '" ><br>' . __('Open delivery location with Google Maps', 'szbd') . '</a></p>';

        }
    }
}


