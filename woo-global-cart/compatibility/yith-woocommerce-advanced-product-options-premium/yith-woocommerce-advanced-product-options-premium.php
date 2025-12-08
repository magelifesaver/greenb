<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name       :   YITH WooCommerce Product Add-ons & Extra Options Premium
    * Since             :   4.11.0
    */

    class WooGC_yith_woocommerce_advanced_product_options_premium
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter( 'woocommerce_get_item_data', array( $this, 'start_get_item_data' ), -1, 2 );
                    add_filter( 'woocommerce_get_item_data', array( $this, 'end_get_item_data' ), 9999, 2 );
                      
                }
                
            function start_get_item_data ( $cart_data, $cart_item )
                {
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                }         
                
            function end_get_item_data ( $cart_data, $cart_item )
                {
                    do_action( 'woocommerce/cart_loop/end', $cart_item );
                }
            
        }

        
    new WooGC_yith_woocommerce_advanced_product_options_premium();

?>