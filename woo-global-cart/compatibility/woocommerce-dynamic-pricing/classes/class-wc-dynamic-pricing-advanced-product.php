<?php

    class WooGC_WC_Dynamic_Pricing_Advanced_Product extends WC_Dynamic_Pricing_Advanced_Product 
        {

	        public function adjust_cart( $temp_cart ) 
                {
		            foreach ( $temp_cart as $cart_item_key => $values ) 
                        {
			                $temp_cart[ $cart_item_key ]                       = $values;
			                $temp_cart[ $cart_item_key ]['available_quantity'] = $values['quantity'];
			                $temp_cart[ $cart_item_key ]['available_quantity'] = $values['quantity'];
		                }


		            foreach ( $temp_cart as $cart_item_key => $cart_item ) 
                        {
			                
                            if(isset($cart_item['blog_id']))
                                switch_to_blog( $cart_item['blog_id'] );
                            
                            $process_discounts = apply_filters( 'woocommerce_dynamic_pricing_process_product_discounts', true, $cart_item['data'], 'advanced_product', $this, $cart_item );
			                if ( ! $process_discounts ) 
                                {
				                    if(isset($cart_item['blog_id']))
                                        restore_current_blog();
                                    
                                    continue;
			                    }

			                if ( ! $this->is_cumulative( $cart_item, $cart_item_key ) ) 
                                {
				                    if ( $this->is_item_discounted( $cart_item, $cart_item_key ) ) 
                                        {
					                        if(isset($cart_item['blog_id']))
                                                restore_current_blog();
                                            
                                            continue;
				                        }
			                    }

			                $product_adjustment_sets = $this->get_pricing_rule_sets( $cart_item );
			                if ( $product_adjustment_sets && count( $product_adjustment_sets ) ) 
                                {

				                    foreach ( $product_adjustment_sets as $set_id => $set ) 
                                        {

					                        if ( $set->target_variations && isset( $cart_item['variation_id'] ) && ! in_array( $cart_item['variation_id'], $set->target_variations ) ) 
                                                {
						                            if(isset($cart_item['blog_id']))
                                                        restore_current_blog();
                                                        
                                                    continue;
					                            }

					                        //check if this set is valid for the current user;
					                        $is_valid_for_user = $set->is_valid_for_user();

					                        if ( ! ( $is_valid_for_user ) ) 
                                                {
						                            if(isset($cart_item['blog_id']))
                                                        restore_current_blog();
                                                        
                                                    continue;
					                            }

					                        $original_price = $this->get_price_to_discount( $cart_item, $cart_item_key );
					                        if ( $original_price ) 
                                                {
						                            $price_adjusted = false;
						                            if ( $set->mode == 'block' ) 
                                                        {
							                                $price_adjusted = $this->get_block_adjusted_price( $set, $original_price, $cart_item );
						                                } 
                                                    elseif ( $set->mode == 'bulk' ) 
                                                        {
							                                $price_adjusted = $this->get_adjusted_price( $set, $original_price, $cart_item );
						                                }

						                            if ( $price_adjusted !== false && floatval( $original_price ) != floatval( $price_adjusted ) ) 
                                                        {
							                                WC_Dynamic_Pricing::apply_cart_item_adjustment( $cart_item_key, $original_price, $price_adjusted, 'advanced_product', $set_id );
							                                break;
						                                }
					                            }
				                        }
			                    }
                                
                            if(isset($cart_item['blog_id']))
                                restore_current_blog();
		                }
	            }

        }