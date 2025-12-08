<?php

    use function WCML\functions\getSitePress;

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:     WooCommerce Multilingual
    * Version:         5.2
    */
    
    class WooGC_wcml
        {
           
            function __construct() 
                {
                    
                    add_filter('init',                                      array( $this, 'load'), 9999 );
                    
                    //add_filter( 'woocommerce_cart_item_name',               array( $this, '_start_woocommerce_cart_item_name'), -1, 3 );
                    //add_filter( 'woocommerce_cart_item_name',               array( $this, '_stop_woocommerce_cart_item_name'), 9999, 3 );
                
                }
                
                
            function load()
                {
                    $sitepress = getSitePress();
                    
                    if  (  $sitepress   === NULL  )
                        return;
                    
                    if ( ! class_exists( 'WCML_Orders' ) )  
                        return;
                    
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-multilingual/inc/class-wcml-orders.php');   
                    
                    global $woocommerce_wpml;
                    
                    new WOOGC_WCML_Orders( $woocommerce_wpml, $sitepress );
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_cart_item_product', 'WCML_Cart', 'adjust_cart_item_product_name' );
                    add_filter( 'woocommerce_cart_item_product', array ( $this, 'adjust_cart_item_product_name' ), 10, 3 );
                                        
                    $WooGC->functions->remove_class_filter( 'woocommerce_before_calculate_totals', 'WCML_Cart', 'woocommerce_calculate_totals' );
                    
                }
                
                
            function _start_woocommerce_cart_item_name( $title, $cart_item, $cart_item_key  )   
                {
                    switch_to_blog( $cart_item['blog_id'] );
                        
                    return $title;    
                }
            
            function _stop_woocommerce_cart_item_name( $title, $cart_item, $cart_item_key  )   
                {
                    restore_current_blog();
                    
                    return $title;    
                }
                
                
            /**
             * @param WC_Product $product
             *
             * @return WC_Product
             */
            public function adjust_cart_item_product_name( $product, $cart_item, $cart_item_key ) {

                switch_to_blog( $cart_item['blog_id'] );
                
                $product_id = $product->get_id();
                
                $current_product_id = wpml_object_id_filter( $product_id, get_post_type( $product_id ) );

                if ( $current_product_id ) {
                    $product->set_name( wc_get_product( $current_product_id )->get_name() );
                }
                
                restore_current_blog();

                return $product;
            }
            
            
 
     
        }

    new WooGC_wcml();



?>