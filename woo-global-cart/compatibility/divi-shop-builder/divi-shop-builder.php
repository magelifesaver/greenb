<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Divi Shop Builder
    * Since:        1.2.16
    */

    class WooGC_divi_shop_builder
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_action( 'woocommerce_checkout_init',                    array( $this, 'woocommerce_checkout_init' ), 10 );
                    add_action( 'woocommerce_checkout_before_order_review',     array( $this, 'woocommerce_checkout_before_order_review' ), 10 );                      
                }
                
            function woocommerce_checkout_init()
                {
                    remove_action( 'woocommerce_checkout_billing', array( WC()->checkout(), 'checkout_form_billing' ), 10 ); // remove wc default checkout billing
                    remove_action( 'woocommerce_checkout_shipping', array( WC()->checkout(), 'checkout_form_shipping' ), 10 ); // remove wc default checkout billing
                }
                
            function woocommerce_checkout_before_order_review()
                {
                    global $WooGC;    
                    
                    $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_checkout_order_review' , 'WooGC_Cart_Split_Core',   'woocommerce_checkout_order_review' );
                }         
            
        }

        
    new WooGC_divi_shop_builder();

?>