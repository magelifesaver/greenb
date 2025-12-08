<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce Product Bundles
    * Since Version:        5.13.0
    */

    class WooGC_Compatibility_WooCommerce_Product_Bundles
        {
            
            public function __construct( ) 
                {
                    
                     
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_check_cart_items', 'WC_PB_Cart', 'check_cart_items', 15 );
                    
                    // Validate bundle configuration in cart.
                    add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 15 );
                    
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woogc/get_cart_from_session/cart_item/values', 'WooGC_general_filters', 'replace_cart_product_with_local_version', 10 );
                    //check on Replace the Cart Products with local version
                    add_filter ( 'woogc/get_cart_from_session/cart_item/values', array ( $this, 'replace_cart_product_with_local_version' ) , 10, 2 );
                      
                }
                
                
            /**
             * Check bundle cart item configurations on cart load.
             */
            public function check_cart_items() 
                {

                    foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) 
                        {

                            if ( isset ( $cart_item['blog_id']  ) )
                                switch_to_blog ( $cart_item['blog_id'] ) ;
                            
                            if ( wc_pb_is_bundle_container_cart_item( $cart_item ) ) {

                                $configuration = isset( $cart_item[ 'stamp' ] ) ? $cart_item[ 'stamp' ] : WC_PB()->cart->get_posted_bundle_configuration( $cart_item[ 'data' ] );

                                WC_PB()->cart->validate_bundle_configuration( $cart_item[ 'data' ], $cart_item[ 'quantity' ], $configuration, 'cart' );
                            }
                            
                            if ( isset ( $cart_item['blog_id']  ) )
                                restore_current_blog();
                        }
                }
                
                
                
            
            /**
            * Check for Replace the Cart Products with local version
            *     
            * @param mixed $values
            * @param mixed $key
            */
            function replace_cart_product_with_local_version( $values, $key )
                {
                    global $WooGC, $blog_id;
                    $options    =   $WooGC->functions->get_options();
                    
                    if ( $options['replace_cart_product_with_local_version']    !=  'yes'   ||  $blog_id    ==  $values['blog_id'] )
                        return $values;
                    
                    $product_sku    =   '';
                    
                    if ( isset ( $values['blog_id'] ) )
                        switch_to_blog( $values['blog_id'] );
                    
                    $cart_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
                    if ( is_object( $cart_product ) )
                        $product_sku    =   $cart_product->get_sku();
                    
                    if ( isset ( $values['blog_id'] ) )
                        restore_current_blog();
                    
                    if ( empty ( $product_sku ) )
                        return $values;
                    
                    $local_product_id   =   wc_get_product_id_by_sku( $product_sku );
                    if ( empty ( $local_product_id ) )
                        return $values;
                    
                    $local_product      =   wc_get_product( $local_product_id );
                    if ( ! is_object ( $local_product ) )
                        return $values;
                    
                    if ( $local_product->get_type() !=  $cart_product->get_type() )
                        return $values;
                    
                    $origin_blog_id             =   $values['blog_id'];
                        
                    $values['blog_id']          =   $blog_id;
                    $values['product_id']       =   $local_product_id;
                    $values['line_subtotal']    =   $local_product->get_price()  *  $values['quantity'] ;
                    $values['line_total']       =   $local_product->get_price()  *  $values['quantity'] ;
                    
                    //update the stamp
                    if ( isset ( $values['stamp'] )     &&  isset ( $values['blog_id'] ) )
                        {
                            foreach ( $values['stamp']  as  $key    =>  $data )
                                {
                                    switch_to_blog( $origin_blog_id );
                                    
                                    $remote_product      =   wc_get_product( $data['product_id'] );
                                    if ( ! is_object ( $remote_product ) )
                                        continue;
                                    
                                    $product_sku    =   $remote_product->get_sku();
                                    
                                    restore_current_blog();
                                    
                                    if ( empty ( $product_sku ) )
                                        continue;
                                        
                                    $local_product_id   =   wc_get_product_id_by_sku( $product_sku );
                                    if ( empty ( $local_product_id ) )
                                        continue;
                                    
                                    $values['stamp'][ $key ]['product_id']  =   $local_product_id;
                                }
                        }
                        
                    return $values;
                    
                }
                
        
            
            
        }

        
    new WooGC_Compatibility_WooCommerce_Product_Bundles();



?>