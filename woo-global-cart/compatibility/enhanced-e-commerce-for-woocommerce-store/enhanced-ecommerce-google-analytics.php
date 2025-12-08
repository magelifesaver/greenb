<?php


    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:      Enhanced E-commerce for Woocommerce store
    * Start:            2.3.6.1
    */


    class WooGC_enhanced_ecwoo
        {
           
            function __construct() 
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    
                    include_once ('class/class-enhanced-ecommerce-google-analytics-public.php');
                    $plugin_public = new WooGC_Enhanced_Ecommerce_Google_Analytics_Public( 'enhanced-ecommerce-google-analytics', '2.0' );

                    $WooGC->functions->remove_class_filter( 'woocommerce_after_cart', 'Enhanced_Ecommerce_Google_Analytics_Public', 'remove_cart_tracking' );
                    add_filter( 'woocommerce_after_cart', array ( $plugin_public, 'remove_cart_tracking' ), 10, 3 );
                    
                    
                    $WooGC->functions->remove_class_filter( 'woocommerce_before_checkout_billing_form', 'Enhanced_Ecommerce_Google_Analytics_Public', 'checkout_step_1_tracking' );
                    add_filter( 'woocommerce_before_checkout_billing_form', array ( $plugin_public, 'checkout_step_1_tracking' ), 10, 3 );
                    
                                                      
                }

              
            
        }

    new WooGC_enhanced_ecwoo();


?>