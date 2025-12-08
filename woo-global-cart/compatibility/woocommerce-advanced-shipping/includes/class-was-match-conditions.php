<?php
    
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    /**
     * Class WAS_Match_Conditions.
     *
     * The WAS Match Conditions class handles the matching rules for Shipping methods.
     *
     * @class		WAS_Match_Conditions
     * @author		Jeroen Sormani
     * @package 	WooCommerce Advanced Shipping
     * @version	1.0.0
     */
    class WooGC_WAS_Match_Conditions 
        {


	        /**
	         * Constructor.
	         *
	         * @since 1.0.0
	         */
	        public function __construct() 
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'was_match_condition_contains_shipping_class',  'WAS_Match_Conditions', 'was_match_condition_contains_shipping_class' );
                    $WooGC->functions->remove_class_filter( 'was_match_condition_stock',                    'WAS_Match_Conditions', 'was_match_condition_stock' );
                    
		            add_filter( 'was_match_condition_contains_shipping_class', array( $this, 'was_match_condition_contains_shipping_class' ), 10, 4 );
                    add_filter( 'was_match_condition_stock', array( $this, 'was_match_condition_stock' ), 10, 4 );


	            }

         
	        /**
	         * Shipping class.
	         *
	         * Matches if the condition value shipping class is in the cart.
	         *
	         * @since 1.0.1
	         *
	         * @param  bool   $match    Current match value.
	         * @param  string $operator Operator selected by the user in the condition row.
	         * @param  mixed  $value    Value given by the user in the condition row.
	         * @param  array  $package  List of shipping package details.
	         * @return BOOL             Matching result, TRUE if results match, otherwise FALSE.
	         */
	        public function was_match_condition_contains_shipping_class( $match, $operator, $value, $package ) 
                {

		            // True until proven false
                    if ( $operator == '!=' ) :
                        $match = true;
                    endif;

                    foreach ( $package['contents'] as $key => $product ) :
                        do_action( 'woocommerce/cart_loop/start', $product );
                        $id      = ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'];
                        $product = wc_get_product( $id );

                        if ( $operator == '==' ) :
                            if ( $product->get_shipping_class() == $value ) :
                                do_action( 'woocommerce/cart_loop/end', $product );
                                return true;
                            endif;
                        elseif ( $operator == '!=' ) :
                            if ( $product->get_shipping_class() == $value ) :
                                do_action( 'woocommerce/cart_loop/end', $product );
                                return false;
                            endif;
                        endif;
                        do_action( 'woocommerce/cart_loop/end', $product );
                    endforeach;

                    return $match;

	            }
                
                
            public function was_match_condition_stock( $match, $operator, $value, $package ) 
                {

                    // Get all product stocks
                    foreach ( $package['contents'] as $product ) :
                    
                        switch_to_blog( $product['blog_id'] );

                        if ( true == $product['data']->variation_has_stock ) :
                            $stock[] = ( get_post_meta( $product['data']->variation_id, '_stock', true ) );
                        else :
                            $stock[] = ( get_post_meta( $product['product_id'], '_stock', true ) );
                        endif;
                        
                        restore_current_blog();

                    endforeach;

                    // Get lowest value
                    $min_stock = min( $stock );

                    if ( '==' == $operator ) :
                        $match = ( $min_stock == $value );
                    elseif ( '!=' == $operator ) :
                        $match = ( $min_stock != $value );
                    elseif ( '>=' == $operator ) :
                        $match = ( $min_stock >= $value );
                    elseif ( '<=' == $operator ) :
                        $match = ( $min_stock <= $value );
                    endif;

                    return $match;

                }
        


        }
