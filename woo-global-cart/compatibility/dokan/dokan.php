<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Dokan
    * Since:                3.10.3
    */

    class WooGC_dokan
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'dokan_vendor_own_product_purchase_restriction', array ( $this, 'dokan_vendor_own_product_purchase_restriction'), 99, 2 );
                      
                }
                
            function dokan_vendor_own_product_purchase_restriction ( $is_purchasable, $product )
                {
                    remove_filter( 'woocommerce_is_purchasable', 'dokan_vendor_own_product_purchase_restriction', 10, 2 );
                    
                    $is_purchasable =   $product->is_purchasable();
                    
                    add_filter( 'woocommerce_is_purchasable', 'dokan_vendor_own_product_purchase_restriction', 10, 2 );
                    
                    return $is_purchasable;
                    
                }         
            
        }

        
    new WooGC_dokan();

?>