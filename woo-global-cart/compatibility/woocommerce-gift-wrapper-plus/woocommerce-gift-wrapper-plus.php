<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Gift Wrapper Plus
    * Since:                5.2.2
    */

    class WooGC_wgwp
        {
            
            function __construct( $dependencies = array() ) 
                {
                    add_filter ( 'woocommerce_cart_item_class', array ( $this,  'woocommerce_cart_item_class__first' ), 10, 3 );
                    add_filter ( 'woocommerce_cart_item_class', array ( $this,  'woocommerce_cart_item_class__last' ), 12, 3 );
                }
                
            function woocommerce_cart_item_class__first( $class, $cart_item, $cart_item_key )
                {
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                    
                    return $class;
                }         
            
            function woocommerce_cart_item_class__last( $class, $cart_item, $cart_item_key )
                {
                    do_action( 'woocommerce/cart_loop/end', $cart_item );
                    
                    return $class;
                }
            
        }

        
    new WooGC_wgwp();

?>