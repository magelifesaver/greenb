<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Polylang
    * Since:                3.7.3
    */

    class WooGC_polylang
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter  ( 'init',           [ $this, 'init' ] );
                      
                }
                
                
            function init( )
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_class_filter( 'woocommerce_cart_item_permalink', 'WooGC_Template', 'on__woocommerce_cart_item_permalink' );
                }
                
                  
            
        }

        
    new WooGC_polylang();

?>