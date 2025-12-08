<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:      WooCommerce Advanced Shipping
    * Since:            1.0.12
    * Last Checked on:  1.0.14
    */

    class WooGC_WooCommerce_Advanced_Shipping
        {
           
            function __construct() 
                {
                    
                    add_action('woocommerce_advanced_shipping_init', array ( $this, 'woocommerce_advanced_shipping_init') );
                }
                
                
            function woocommerce_advanced_shipping_init()
                {
                    
                    require_once WOOGC_PATH . '/compatibility/woocommerce-advanced-shipping/includes/class-was-match-conditions.php';
                    
                    new WooGC_WAS_Match_Conditions();
                    
                }
            
        }

    new WooGC_WooCommerce_Advanced_Shipping();

?>