<?php


    class WooGC_WC_Dynamic_Pricing extends WC_Dynamic_Pricing
        {
            
            public function on_calculate_totals( $cart ) 
                {

                    $sorted_cart = array();
                    if ( sizeof( $cart->cart_contents ) > 0 ) {
                        foreach ( $cart->cart_contents as $cart_item_key => $values ) {
                            $sorted_cart[ $cart_item_key ] = $values;
                        }
                    }

                    //Sort the cart so that the lowest priced item is discounted when using block rules.
                    uasort( $sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price' );

                    $modules = apply_filters( 'wc_dynamic_pricing_load_modules', $this->modules );
                    foreach ( $modules as $module ) {
                        $module->adjust_cart( $sorted_cart );
                    }
                }   
            
        }
    

?>