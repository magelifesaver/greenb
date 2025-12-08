<?php
    
    defined( 'ABSPATH' ) || exit;

    class WooGC_WC_Order_Item_Product extends WC_Order_Item_Product 
        {
            
            /**
            * Get the associated product.
            *
            * @return WC_Product|bool
            */
            public function get_product() 
                {
                    
                    if ( $this->get_meta('blog_id') > 0 )
                        switch_to_blog( $this->get_meta('blog_id') );
                    
                    if ( $this->get_variation_id() ) 
                        {
                            $product = wc_get_product( $this->get_variation_id() );
                        } 
                        else 
                        {
                            $product = wc_get_product( $this->get_product_id() );
                        }

                    if ( ! is_object( $product ) )
                        {
                            if ( $this->get_meta('blog_id') > 0 )
                                restore_current_blog(); 
                            
                            return FALSE;
                        }
                        
                    //ensure we retrieve the meta data on this product for later usage
                    if ( $product->meta_exists( 'dummy' ) ) { }
                        
                    // Backwards compatible filter from WC_Order::get_product_from_item()
                    if ( has_filter( 'woocommerce_get_product_from_item' ) ) 
                        {
                            $product = apply_filters( 'woocommerce_get_product_from_item', $product, $this, $this->get_order() );
                        }

                    if ( $this->get_meta('blog_id') > 0 )
                        restore_current_blog();    
                    
                    return apply_filters( 'woocommerce_order_item_product', $product, $this );
                }
            
            
            /**
            * Set Product ID
            *
            * @param int $value
            * @throws WC_Data_Exception
            */
            public function set_product_id( $value ) 
                {
                
                    if ( $this->get_meta('blog_id') > 0 )
                        switch_to_blog( $this->get_meta('blog_id') );
                    
                    if ( $value > 0 && 'product' !== get_post_type( absint( $value ) ) ) 
                        {
                            if ( $this->get_meta('blog_id') > 0 )
                                restore_current_blog();
                                
                            $this->error( 'order_item_product_invalid_product_id', __( 'Invalid product ID', 'woocommerce' ) );
                        }
                        
                    if ( $this->get_meta('blog_id') > 0 )
                        restore_current_blog();
                    
                    $this->set_prop( 'product_id', absint( $value ) );
                }
                
            
            /**
            * Set variation ID.
            *
            * @param int $value
            * @throws WC_Data_Exception
            */
            public function set_variation_id( $value ) 
                {
                    
                    if ( $this->get_meta('blog_id') > 0 )
                        switch_to_blog( $this->get_meta('blog_id') );
                    
                    if ( $value > 0 && 'product_variation' !== get_post_type( $value ) ) 
                        {
                            if ( $this->get_meta('blog_id') > 0 )
                                restore_current_blog();
                                
                            $this->error( 'order_item_product_invalid_variation_id', __( 'Invalid variation ID', 'woocommerce' ) );
                        }
                        
                    if ( $this->get_meta('blog_id') > 0 )
                        restore_current_blog();
                        
                    $this->set_prop( 'variation_id', absint( $value ) );
                }
               
        }
