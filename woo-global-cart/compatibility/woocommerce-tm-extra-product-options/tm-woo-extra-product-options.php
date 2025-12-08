<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:              WooCommerce TM Extra Product Options
    * Since :                   6.1.2
    * Last Update on Version    6.1.2
    */
    
    
    
    class WooGC_tm_woo_extra_product_options
        {
            
            function __construct( ) 
                {
                    
                    add_action ( 'wc_epo_cart_loaded_from_session_before_cart_item',    array ( $this, 'wc_epo_cart_loaded_from_session_before_cart_item') );
                    add_action ( 'wc_epo_cart_loaded_from_session_after_cart_item',     array ( $this, 'wc_epo_cart_loaded_from_session_after_cart_item') );
                      
                }
                
            function wc_epo_cart_loaded_from_session_before_cart_item( $cart_item )
                {
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                }         
                
            function wc_epo_cart_loaded_from_session_after_cart_item( $cart_item )
                {
                    do_action( 'woocommerce/cart_loop/end', $cart_item );
                }
            
        }

        
    new WooGC_tm_woo_extra_product_options();
   
    
?>