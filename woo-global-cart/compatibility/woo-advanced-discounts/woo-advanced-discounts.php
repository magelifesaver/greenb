<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_woo_advanced_discounts
        {
            
            function __construct()
                {
                    
                    add_action( 'init',                                 array( $this, 'on_init'));
                    
                }
                        
            function on_init()
                {
                    
                    global $WooGC;
                                        
                    //replace other hoock
                    $WooGC->functions->remove_class_filter ( 'woocommerce_cart_subtotal', 'WAD_Discount', 'get_cart_subtotal', 90);
                    
                    include_once ( WOOGC_PATH . '/compatibility/woo-advanced-discounts/classes/class-wad-discount.php');
                    
                    $discount   =   new WooGC_WAD_Discount(false);
                                        
                    add_filter( 'woocommerce_cart_subtotal', array($discount, 'get_cart_subtotal'),90, 3 );
                    
                }
                                                                   
        }


    new WooGC_woo_advanced_discounts();  


?>