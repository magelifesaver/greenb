<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:              NP Quote Request WooCommerce Plus
    * Since:      1.3.217
    */

    class WooGC_rfqtk
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_action('plugins_loaded', array ( $this, 'plugins_loaded' ), 10001 );
                    
                }
                
                
            
            function plugins_loaded()
                {
                    include_once ( WOOGC_PATH . '/compatibility/rfqtk/classes/rfqtk-functions.php');
                    
                    if (!is_admin()) 
                        {
                            remove_filter('woocommerce_cart_total', 'gpls_woo_rfqtk_hide_woocommerce_cart_total', 1000, 1);
                            remove_filter('woocommerce_cart_total', 'gpls_woo_rfqtk_hide_woocommerce_cart_total', 1000, 1);
                            
                            add_filter('woocommerce_cart_total', 'WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_total', 1000, 1);
                            add_filter('woocommerce_cart_total', 'WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_total', 1000, 1);
                            
                            remove_filter('woocommerce_cart_item_subtotal', 'gpls_woo_rfq_hide_subtotal_price_function', 1000, 3);
                            remove_filter('woocommerce_cart_item_subtotal', 'gpls_woo_rfq_hide_subtotal_price_function', 1000, 3);
                            
                            add_filter('woocommerce_cart_item_subtotal', 'WooGC_gpls_woo_rfq_hide_subtotal_price_function', 1000, 3);
                            add_filter('woocommerce_cart_item_subtotal', 'WooGC_gpls_woo_rfq_hide_subtotal_price_function', 1000, 3);
                            
                            remove_filter('woocommerce_cart_subtotal', 'gpls_woo_rfqtk_hide_woocommerce_cart_subtotal', 1000, 3);
                            remove_filter('woocommerce_cart_subtotal', 'gpls_woo_rfqtk_hide_woocommerce_cart_subtotal', 1000, 3);
                            
                            add_filter('woocommerce_cart_subtotal', 'WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_subtotal', 1000, 3);
                            add_filter('woocommerce_cart_subtotal', 'WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_subtotal', 1000, 3);
                        }
                }
            
        }

    new WooGC_rfqtk();

?>