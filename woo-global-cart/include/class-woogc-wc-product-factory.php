<?php
/**
 * Product Factory
 *
 * The WooCommerce product factory creating the right product object.
 *
 * @package WooCommerce\Classes
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product factory class.
 */
class WooGC_WC_Product_Factory extends WC_Product_Factory {

        /**
         * Get a product.
         *
         * @param mixed $product_id WC_Product|WP_Post|int|bool $product Product instance, post instance, numeric or false to use global $post.
         * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
         * @return WC_Product|bool Product object or false if the product cannot be loaded.
         */
        public function get_product( $product_id = false, $deprecated = array() ) {
            $product_id = $this->get_product_id( $product_id );

            if ( ! $product_id ) {
                return false;
            }

            $product_type = $this->get_product_type( $product_id );

            // Backwards compatibility.
            if ( ! empty( $deprecated ) ) {
                wc_deprecated_argument( 'args', '3.0', 'Passing args to the product factory is deprecated. If you need to force a type, construct the product class directly.' );

                if ( isset( $deprecated['product_type'] ) ) {
                    $product_type = $this->get_classname_from_product_type( $deprecated['product_type'] );
                }
            }

            $classname = $this->get_product_classname( $product_id, $product_type );
            
            try {
                return new $classname( $product_id, $deprecated );
            } catch ( Exception $e ) 
                {
                
                    //Check the cart if the products belongs to another shop
                    $cart = WC()->cart ?? null;
                    if ( ! $cart || $cart->is_empty() )
                        return false;
                    
                    $product    =   FALSE;
                        
                    $cart_content   =   WC()->cart->get_cart_contents();
                    foreach ( $cart_content as  $item_key   =>  $item )   
                        {
                            
                            if ( ! isset ( $item['blog_id'] ) )
                                continue;    
                            
                            if ( $item['product_id']    ==  $product_id ) 
                                {
                                    switch_to_blog ( $item['blog_id'] ) ;
                                    
                                    $product_type = $this->get_product_type( $product_id );
                                    
                                    $classname = $this->get_product_classname( $product_id, $product_type );
             
                                    try {
                                            $product    =   new $classname( $product_id, $deprecated );
                                            $product->get_meta('');
                                        } catch ( Exception $e ) {
                                            $product    =   FALSE;
                                        }
                                    
                                    restore_current_blog();            
                                }
                        }
                }
                
            return $product;
        }
        
        
        /**
         * Get the product ID depending on what was passed.
         *
         * @since  3.0.0
         * @param  WC_Product|WP_Post|int|bool $product Product instance, post instance, numeric or false to use global $post.
         * @return int|bool false on failure
         */
        private function get_product_id( $product ) {
            global $post;

            if ( false === $product && isset( $post, $post->ID ) && 'product' === get_post_type( $post->ID ) ) {
                return absint( $post->ID );
            } elseif ( is_numeric( $product ) ) {
                return $product;
            } elseif ( $product instanceof WC_Product ) {
                return $product->get_id();
            } elseif ( ! empty( $product->ID ) ) {
                return $product->ID;
            } else {
                return false;
            }
        }

}
