<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	class WooGC_WPC_Contains_Shipping_Class_Condition extends WPC_Contains_Shipping_Class_Condition {


		public function match( $match, $operator, $value ) {

			foreach ( WC()->cart->get_cart() as $cart_product ) {

                if ( $cart_product['blog_id']   > 0 )
                    switch_to_blog( $cart_product['blog_id'] );
                
				$id      = ! empty( $cart_product['variation_id'] ) ? $cart_product['variation_id'] : $cart_product['product_id'];
				$product = wc_get_product( $id );

				if ( $operator == '==' ) {
					if ( $product->get_shipping_class() == $value ) {
						
                        if ( $cart_product['blog_id']   > 0 )
                            restore_current_blog();
                        
                        return true;
					}
				} elseif ( $operator == '!=' ) {
					$match = true;
					if ( $product->get_shipping_class() == $value ) {
						
                        if ( $cart_product['blog_id']   > 0 )
                            restore_current_blog();
                        
                        return false;
					}
				}
                
                if ( $cart_product['blog_id']   > 0 )
                    restore_current_blog();

			}

			return $match;

		}

		public function get_available_operators() {

			$operators = parent::get_available_operators();

			unset( $operators['>='] );
			unset( $operators['<='] );

			return $operators;

		}

		public function get_value_field_args() {

			$shipping_classes = get_terms( 'product_shipping_class', array( 'hide_empty' => false ) );
			$shipping_classes = array_merge(
				array( '-1' => __( 'No shipping class', 'woocommerce' ) ),
				wp_list_pluck( $shipping_classes, 'name', 'slug' )
			);

			$field_args = array(
				'type' => 'select',
				'options' => $shipping_classes,
				'class' => array( 'wpc-value', 'wc-enhanced-select' ),
			);

			return $field_args;

		}

	}

    
?>