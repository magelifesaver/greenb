<?php

    class WooGC_WC_Dynamic_Pricing_Counter extends WC_Dynamic_Pricing_Counter 
        {
            
            public function reset_counter( $cart ) 
                {

                    $this->product_counts     = array();
                    $this->variation_counts   = array();
                    $this->category_counts    = array();
                    $this->categories_in_cart = array();
                    $this->taxonomies_in_cart = array();
                    $this->taxonomy_counts    = array();

                    if ( sizeof( $cart->cart_contents ) > 0 ) 
                        {
                            foreach ( $cart->cart_contents as $cart_item_key => $values ) 
                                {
                                    
                                    if(isset($values['blog_id']))
                                        switch_to_blog( $values['blog_id'] );
                                    
                                    $quantity = isset( $values['quantity'] ) ? (int) $values['quantity'] : 0;

                                    $product_id   = $values['product_id'];
                                    $variation_id = isset( $values['variation_id'] ) ? $values['variation_id'] : false;

                                    //Store product counts
                                    $this->product_counts[ $product_id ] = isset( $this->product_counts[ $product_id ] ) ? $this->product_counts[ $product_id ] + $quantity : $quantity;

                                    //Gather product variation id counts
                                    if ( ! empty( $variation_id ) ) 
                                        {
                                            $this->variation_counts[ $variation_id ] = isset( $this->variation_counts[ $variation_id ] ) ?
                                                $this->variation_counts[ $variation_id ] + $quantity : $quantity;
                                        }

                                    //Gather product category counts
                                    $product            = wc_get_product( $product_id );
                                    $product_categories = WC_Dynamic_Pricing_Compatibility::get_product_category_ids($product);

                                    foreach ( $product_categories as $category ) 
                                        {
                                            $this->category_counts[ $category ] = isset( $this->category_counts[$category] ) ?
                                                $this->category_counts[ $category ] + $quantity : $quantity;

                                            $this->categories_in_cart[] = $category;
                                        }

                                    $additional_taxonomies = apply_filters( 'wc_dynamic_pricing_get_discount_taxonomies', array() );
                                    //Gather additional taxonomy counts.

                                    foreach ( $additional_taxonomies as $additional_taxonomy ) 
                                        {
                                            if (!taxonomy_exists($additional_taxonomy))
                                                {
                                                    continue;
                                                }
                                            $this->taxonomy_counts[ $additional_taxonomy ]    = array();
                                            $this->taxonomies_in_cart[ $additional_taxonomy ] = array();
                                            $product_categories                               = wp_get_post_terms( $product_id, $additional_taxonomy );
                                            foreach ( $product_categories as $category ) 
                                                {
                                                    $this->taxonomy_counts[ $additional_taxonomy ][ $category->term_id ] = isset( $this->taxonomy_counts[ $additional_taxonomy ][ $category->term_id ] ) ?
                                                        $this->taxonomy_counts[ $additional_taxonomy ][ $category->term_id ] + $quantity : $quantity;

                                                    $this->taxonomies_in_cart[ $additional_taxonomy ][] = $category->term_id;
                                                }
                                        }

                                    if(isset($values['blog_id']))
                                        restore_current_blog();

                                }
                        }

                    do_action( 'wc_dynamic_pricing_counter_updated' );
                }

        }
        
        
?>
