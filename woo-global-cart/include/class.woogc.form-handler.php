<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
     * Handle frontend forms.
     *
     * @class 		WC_Form_Handler
     * @version		2.2.0
     * @package		WooCommerce/Classes/
     * @category	Class
     * @author 		WooThemes
     */
    class WooGC_Form_Handler 
        {

	        /**
	         * Remove from cart/update.
	         */
	        public static function update_cart_action() 
                {
                    
                    if ( ! ( isset( $_REQUEST['apply_coupon'] ) || isset( $_REQUEST['remove_coupon'] ) || isset( $_REQUEST['remove_item'] ) || isset( $_REQUEST['undo_item'] ) || isset( $_REQUEST['update_cart'] ) || isset( $_REQUEST['proceed'] ) ) ) 
                        {
                            return;
                        }
                    
                    wc_nocache_headers();
                    
                    $nonce_value = wc_get_var( $_REQUEST['woocommerce-cart-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) );
                    
		            // Add Discount
		            if ( ! empty( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) ) 
                        {
			                WC()->cart->add_discount( sanitize_text_field(  wp_unslash ( $_POST['coupon_code'] ) ) );
		                }
		            // Remove Coupon Codes
		            elseif ( isset( $_GET['remove_coupon'] ) ) 
                        {
			                WC()->cart->remove_coupon( wc_clean( wp_unslash( $_GET['remove_coupon'] ) ) );
		                }
		            // Remove from cart
		            elseif ( ! empty( $_GET['remove_item'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) 
                        {
			                $cart_item_key = sanitize_text_field( wp_unslash( $_GET['remove_item'] ) );
                            $cart_item     = WC()->cart->get_cart_item( $cart_item_key );

			                if ( $cart_item  ) {
				                WC()->cart->remove_cart_item( $cart_item_key );

                                if (isset($cart_item['blog_id']))
                                    switch_to_blog( $cart_item['blog_id'] );
                                    
				                $product = wc_get_product( $cart_item['product_id'] );
                                
                                if (isset($cart_item['blog_id']))
                                    restore_current_blog();

				                $item_removed_title = apply_filters( 'woocommerce_cart_item_removed_title', $product ? sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), $product->get_name() ) : __( 'Item', 'woocommerce' ), $cart_item );

				                // Don't show undo link if removed item is out of stock.
				                if ( $product->is_in_stock() && $product->has_enough_stock( $cart_item['quantity'] ) ) {
					                $removed_notice  = sprintf( __( '%s removed.', 'woocommerce' ), $item_removed_title );
					                $removed_notice .= ' <a href="' . esc_url( wc_get_cart_undo_url( $cart_item_key ) ) . '" class="restore-item">' . __( 'Undo?', 'woocommerce' ) . '</a>';
				                } else {
					                $removed_notice = sprintf( __( '%s removed.', 'woocommerce' ), $item_removed_title );
				                }

				                wc_add_notice( $removed_notice );
			                }

			                $referer  = wp_get_referer() ? remove_query_arg( array( 'remove_item', 'add-to-cart', 'added-to-cart' ), add_query_arg( 'removed_item', '1', wp_get_referer() ) ) : wc_get_cart_url();
                            wp_safe_redirect( $referer );
			                exit;
		                }
		            // Undo Cart Item
		            elseif ( ! empty( $_GET['undo_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) )  
                        {
			                $cart_item_key = sanitize_text_field( $_GET['undo_item'] );

			                WC()->cart->restore_cart_item( $cart_item_key );

			                $referer  = wp_get_referer() ? remove_query_arg( array( 'undo_item', '_wpnonce' ), wp_get_referer() ) : wc_get_cart_url();
			                wp_safe_redirect( $referer );
			                exit;
		                }

                        
                        
                        
		            // Update Cart - checks apply_coupon too because they are in the same form
		            if ( ( ! empty( $_POST['apply_coupon'] ) || ! empty( $_POST['update_cart'] ) || ! empty( $_POST['proceed'] ) ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) 
                        {

			                $cart_updated = false;
			                $cart_totals  = isset( $_POST['cart'] ) ? wp_unslash( $_POST['cart'] ) : '';

			                if ( ! WC()->cart->is_empty() && is_array( $cart_totals ) ) 
                                {
				                    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) 
                                        {

					                        $_product = $values['data'];

					                        // Skip product if no updated quantity was posted
					                        if ( ! isset( $cart_totals[ $cart_item_key ] ) || ! isset( $cart_totals[ $cart_item_key ]['qty'] ) ) 
                                                {
						                            continue;
					                            }

					                        // Sanitize
					                        $quantity = apply_filters( 'woocommerce_stock_amount_cart_item', wc_stock_amount( preg_replace( "/[^0-9\.]/", '', $cart_totals[ $cart_item_key ]['qty'] ) ), $cart_item_key );

					                        if ( '' === $quantity || $quantity == $values['quantity'] )
						                        continue;

					                        // Update cart validation
					                        $passed_validation 	= apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $values, $quantity );

					                        // is_sold_individually
					                        if ( $_product->is_sold_individually() && $quantity > 1 ) 
                                                {
						                            wc_add_notice( sprintf( __( 'You can only have 1 %s in your cart.', 'woocommerce' ), $_product->get_title() ), 'error' );
						                            $passed_validation = false;
					                            }

					                        if ( $passed_validation ) 
                                                {
						                            WC()->cart->set_quantity( $cart_item_key, $quantity, false );
						                            $cart_updated = true;
					                            }

				                        }
			                    }

			                // Trigger action - let 3rd parties update the cart if they need to and update the $cart_updated variable
			                $cart_updated = apply_filters( 'woocommerce_update_cart_action_cart_updated', $cart_updated );

			                if ( $cart_updated ) 
                                {
				                    // Recalc our totals
				                    WC()->cart->calculate_totals();
			                    }

			                if ( ! empty( $_POST['proceed'] ) ) 
                                {
				                    wp_safe_redirect( wc_get_checkout_url() );
				                    exit;
			                    } 
                            elseif ( $cart_updated ) 
                                {
				                    wc_add_notice( __( 'Cart updated.', 'woocommerce' ) );
				                    $referer = remove_query_arg( 'remove_coupon', ( wp_get_referer() ? wp_get_referer() : wc_get_cart_url() ) );
				                    wp_safe_redirect( $referer );
				                    exit;
			                    }
		                }
	            }
                
                
                
            /**
            * Place a previous order again.
            */
            public static function order_again() 
                {
                    // Nothing to do
                    if ( ! isset( $_GET['order_again'] ) || ! is_user_logged_in() || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-order_again' ) ) {
                        return;
                    }

                    wc_nocache_headers();

                    if ( apply_filters( 'woocommerce_empty_cart_when_order_again', true ) ) {
                        WC()->cart->empty_cart();
                    }

                    // Load the previous order - Stop if the order does not exist
                    $order = wc_get_order( absint( $_GET['order_again'] ) );

                    if ( ! $order->get_id() ) {
                        return;
                    }

                    if ( ! $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ) ) ) {
                        return;
                    }

                    // Make sure the user is allowed to order again. By default it check if the
                    // previous order belonged to the current user.
                    if ( ! current_user_can( 'order_again', $order->get_id() ) ) {
                        return;
                    }

                    // Copy products from the order to the cart
                    $order_items = $order->get_items();
                    foreach ( $order_items as $item ) {
                        // Load all product info including variation data
                        $product_id   = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item->get_product_id() );
                        $quantity     = $item->get_quantity();
                        $variation_id = $item->get_variation_id();
                        $variations   = array();
                        $cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

                        foreach ( $item->get_meta_data() as $meta ) {
                            if ( taxonomy_is_product_attribute( $meta->key ) ) {
                                $term = get_term_by( 'slug', $meta->value, $meta->key );
                                $variations[ $meta->key ] = $term ? $term->name : $meta->value;
                            } elseif ( meta_is_product_attribute( $meta->key, $meta->value, $product_id ) ) {
                                $variations[ $meta->key ] = $meta->value;
                            }
                        }

                        // Prevent reordering variable products if no selected variation.
                        if ( ! $variation_id && ( $product = $item->get_product() ) && $product->is_type( 'variable' ) ) {
                            continue;
                        }

                        // Add to cart validation
                        if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
                            continue;
                        }

                        $item_blog_id    =   $item->get_meta('blog_id', TRUE);
                        switch_to_blog( $item_blog_id );
                        
                        WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
                        
                        restore_current_blog();
                        
                    }

                    do_action( 'woocommerce_ordered_again', $order->get_id() );

                    $num_items_in_cart = count( WC()->cart->get_cart() );
                    $num_items_in_original_order = count( $order_items );

                    if ( $num_items_in_original_order > $num_items_in_cart ) {
                        wc_add_notice(
                            sprintf( _n(
                                '%d item from your previous order is currently unavailable and could not be added to your cart.',
                                '%d items from your previous order are currently unavailable and could not be added to your cart.',
                                $num_items_in_original_order - $num_items_in_cart,
                                'woocommerce'
                            ), $num_items_in_original_order - $num_items_in_cart ),
                            'error'
                        );
                    }

                    if ( $num_items_in_cart > 0 ) {
                        wc_add_notice( __( 'The cart has been filled with the items from your previous order.', 'woocommerce' ) );
                    }

                    // Redirect to cart
                    wp_safe_redirect( wc_get_cart_url() );
                    exit;
                }
             
        }


?>