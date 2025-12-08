<?php


    class WooGC_WAF_Cost_Option_Cost_Per_Shipping_Class extends WAF_Cost_Option_Cost_Per_Shipping_Class 
        {
            
            /**
            * Get related products.
            *
            * Get the related products from the cart where the cost should be applied to.
            *
            * @since 1.1.8
            *
            * @param string $value Set value for the advanced option.
            * @return array List of related products
            */
            public function get_related_products( $value = null ) 
                {

                    $related_products = array();

                    $cart_items = WC()->cart->get_cart();
                    foreach ( $cart_items as $cart_key => $item )  :

                        if ( $item['blog_id']   > 0 )
                            switch_to_blog( $item['blog_id'] );

                        $product_id        = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
                        $product        = wc_get_product( $product_id );
                        $shipping_class = $product->get_shipping_class();

                        if ( $value == $shipping_class ) :
                            $related_products[ $cart_key ] = $item;
                        endif;
                        
                        if ( $item['blog_id']   > 0 )
                            restore_current_blog();

                    endforeach;

                    return $related_products;

                }    
            
            
            
        }

?>