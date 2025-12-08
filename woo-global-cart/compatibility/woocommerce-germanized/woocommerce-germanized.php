<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for Plugin Name: Germanized for WooCommerce
    * Compatibility checked on Version: 3.1.9
    */
    
    class WooGC_Compatibility_WC_Germanized
        {
            
            function __construct()
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_get_item_data',         'WC_GZD_Product_Attribute_Helper',    'cart_item_data_filter' );
                    
                    add_filter( 'woocommerce_get_item_data', array( $this, 'cart_item_data_filter' ), 150, 2 );
                }
                
            
            
            public function cart_item_data_filter( $item_data, $cart_item ) 
                {
                    $cart_product = $cart_item['data'];

                    if ( ! $cart_product ) {
                        return $item_data;
                    }

                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $item_data_product = wc_gzd_get_gzd_product( $cart_product )->get_checkout_attributes( $item_data, isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() );

                    restore_current_blog();
                    
                    if ( $item_data !== $item_data_product ) {
                        $item_data = array_replace_recursive( $item_data, $item_data_product );
                    }

                    return $item_data;
                }
                                                                   
        }


    new WooGC_Compatibility_WC_Germanized();    
    
?>