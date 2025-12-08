<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_wc_dynamic_pricing_and_discounts 
        {
            public function __construct() 
                {
                    
                    $this->init();
                    
                    add_filter('RP_WCDPD_Controller/get_item_internal',             array($this, 'get_item_internal'), 999, 3 );
                    
                    //multiple curencies
                    add_filter('rp_wcdpd_promotion_total_saved_formatted_amount',   array( $this, 'rp_wcdpd_promotion_total_saved_formatted_amount' ), 999, 3 );
              
                }
  
  
            public function init( )
                {
                    
                    include_once ( WOOGC_PATH . '/compatibility/wc-dynamic-pricing-and-discounts/classes/conditions/rp-wcdpd-condition-product-category.class.php');
                  
                        
                }    
            
            function get_item_internal( $item, $key, $context )
                {
                    
                    if ( $key   !=  'category' )    
                        return $item;
                        
                    $item   =   RP_WCDPD_Condition_Product_Category_WooGC::get_instance();
                    
                    return $item;
                }
                
                
                
            function rp_wcdpd_promotion_total_saved_formatted_amount ( $formated_amount, $amount, $hook )
                {
                    
                    //check what curencies each of the sites with a product in the cart, uses 
                    $currency_map   =   array();
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            if ( ! isset( $currency_map[ $shop_currency ] ))
                                $currency_map[ $shop_currency ]     =   array( $cart_item['blog_id'] );
                                else
                                $currency_map[ $shop_currency ][]     =   $cart_item['blog_id']; 
                            restore_current_blog();
                        }
                        
                    if ( count  ( $currency_map )  === 1  )
                        {
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            reset ( $currency_map );
                            
                            $cart_item_currency = key ( $currency_map );
                            
                            if ( $cart_item_currency    ==  $shop_currency )
                                return $formated_amount;
                        }
                    
                    $prices =   array();
                    
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            $amount = 0.0;
                            
                            // Get line subtotal
                            $final_subtotal = (float) $cart_item['line_subtotal'];

                            // Get line subtotal tax
                            if ($include_tax) {
                                $final_subtotal += (float) $cart_item['line_subtotal_tax'];
                            }

                            // Get cart item price changes
                            $price_changes = RightPress_Product_Price_Cart::get_cart_item_price_changes($cart_item_key);

                            // Get full product price from our data
                            if (!empty($price_changes)) {

                                // Get highest prices subtotal from cart item price changes
                                $initial_subtotal = RightPress_Product_Price_Changes::get_highest_prices_subtotal_from_cart_item_price_changes($price_changes);
                            }
                            // Get full product price from product
                            else {

                                // Load new product object
                                $product = wc_get_product($cart_item['data']->get_id());

                                // Get full price from product
                                $full_price = $product->get_regular_price('edit');
                                $full_price = RightPress_Help::get_amount_in_currency($full_price);

                                // Calculate subtotal based on initial price
                                $initial_subtotal = (float) ($full_price * $cart_item['quantity']);
                            }

                            // Tax adjustment
                            if ($include_tax) {
                                $initial_subtotal = wc_get_price_including_tax($cart_item['data'], array('qty' => 1, 'price' => $initial_subtotal));
                            }
                            else {
                                $initial_subtotal = wc_get_price_excluding_tax($cart_item['data'], array('qty' => 1, 'price' => $initial_subtotal));
                            }

                            // Convert currency
                            $initial_subtotal = RightPress_Help::get_amount_in_currency_realmag777($initial_subtotal);


                            // Check if cart item was discounted
                            if (RightPress_Product_Price::price_is_smaller_than($final_subtotal, $initial_subtotal)) {

                                // Add the difference to total discount amount
                                $amount = (RightPress_Product_Price::round($initial_subtotal) - RightPress_Product_Price::round($final_subtotal));
                            }
                            
                    
                            if ( isset ( $prices[ $shop_currency ] ) )
                                $prices[ $shop_currency ]   +=  $amount;
                                else
                                $prices[ $shop_currency ]   =  $amount;
                                  
                            restore_current_blog();
                        }
                    
                    $formated_amount  =   '';
                    
                    foreach  ( $prices  as  $currency => $price )
                        {
                            if ( !empty ( $formated_amount ) )
                                $formated_amount  .=  ' &#43; ';
                                
                            $formated_amount  .=   wc_price ( $price , array ( 'currency'           => $currency ) );
                        }
                        
                    return $formated_amount;    
                    
                    
                }
            
            
        }

    new WooGC_wc_dynamic_pricing_and_discounts();

?>