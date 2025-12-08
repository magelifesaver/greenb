<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Points and Rewards for WooCommerce 
    * Since:                1.0.11
    */

    
    //NOT FULLY IMPLEMENTED!
    
    class WooGC_Compatibility_PRWOO
        {
           
            function __construct()
                {

                    //add_action( 'plugins_loaded',   array( $this, 'plugins_loaded') );
                }
                
            
            function plugins_loaded()
                {
                    global $WooGC;
                    

                    $WooGC->functions->remove_class_filter( 'woocommerce_before_calculate_totals',     'Points_Rewards_For_WooCommerce_Public', 'mwb_wpr_woocommerce_before_calculate_totals', 10 );

                    add_action( 'woocommerce_before_calculate_totals', array( $this, 'mwb_wpr_woocommerce_before_calculate_totals'), 10, 1 );
                }
                
                
            /**
            * This function will add discounted price in cart page.
            *
            * @name mwb_wpr_woocommerce_before_calculate_totals
            * @since 1.0.0
            * @author makewebbetter<ticket@makewebbetter.com>
            * @link https://www.makewebbetter.com/
            * @param array $cart array of the cart.
            */
            public function mwb_wpr_woocommerce_before_calculate_totals( $cart ) 
                {
                    // check allowed user for points features.
                    if ( apply_filters( 'mwb_wpr_allowed_user_roles_points_features', false ) ) {
                        return;
                    }
                    $woo_ver = WC()->version;
                    /*Get the current user id*/
                    $user_id = get_current_user_ID();
                    $new_price = '';
                    $today_date = date_i18n( 'Y-m-d' );
                    /*Get the current level of the user*/
                    $user_level = get_user_meta( $user_id, 'membership_level', true );
                    /*Expiration period of the membership*/
                    $mwb_wpr_mem_expr = get_user_meta( $user_id, 'membership_expiration', true );
                    /*Get the user id of the user*/
                    $get_points = (int) get_user_meta( $user_id, 'mwb_wpr_points', true );
                    $membership_settings_array = get_option( 'mwb_wpr_membership_settings', true );
                    /*Get the membership level*/
                    $mwb_wpr_membership_roles = isset( $membership_settings_array['membership_roles'] ) && ! empty( $membership_settings_array['membership_roles'] ) ? $membership_settings_array['membership_roles'] : array();
                    /*Get the current user*/
                    $user = wp_get_current_user();
                    $user_id = $user->ID;
                    /*Get the total points of the user*/
                    $get_points = (int) get_user_meta( $user_id, 'mwb_wpr_points', true );
                    foreach ( $cart->cart_contents as $key => $value ) {
                        $product_id = $value['product_id'];
                        $pro_quant = $value['quantity'];
                        $_product = wc_get_product( $product_id );
                        $product_is_variable = $this->mwb_wpr_check_whether_product_is_variable( $_product );
                        $reg_price = $_product->get_price();
                        if ( isset( $value['variation_id'] ) && ! empty( $value['variation_id'] ) ) {
                            $variation_id = $value['variation_id'];
                            $variable_product = wc_get_product( $variation_id );
                            $variable_price = $variable_product->get_price();
                        }
                        if ( isset( $mwb_wpr_mem_expr ) && ! empty( $mwb_wpr_mem_expr ) && $today_date <= $mwb_wpr_mem_expr ) {
                            if ( isset( $user_level ) && ! empty( $user_level ) ) {
                                foreach ( $mwb_wpr_membership_roles as $roles => $values ) {
                                    if ( $user_level == $roles ) {
                                        if ( is_array( $values['Product'] ) && ! empty( $values['Product'] ) ) {
                                            if ( in_array( $product_id, $values['Product'] ) && ! $this->check_exclude_sale_products( $_product ) ) {
                                                if ( ! $product_is_variable ) {
                                                    $new_price = $reg_price - ( $reg_price * $values['Discount'] ) / 100;
                                                    if ( $woo_ver < '3.0.0' ) {
                                                        $value['data']->price = $new_price;
                                                    } else {
                                                        $value['data']->set_price( $new_price );
                                                    }
                                                } elseif ( $product_is_variable ) {
                                                    $new_price = $variable_price - ( $variable_price * $values['Discount'] ) / 100;
                                                    if ( $woo_ver < '3.0.0' ) {
                                                        $value['data']->price = $new_price;
                                                    } else {
                                                        $value['data']->set_price( $new_price );
                                                    }
                                                }
                                            }
                                        } elseif ( ! $this->check_exclude_sale_products( $_product ) ) {
                                            $terms = get_the_terms( $product_id, 'product_cat' );
                                            if ( is_array( $terms ) && ! empty( $terms ) ) {
                                                foreach ( $terms as $term ) {
                                                    $cat_id = $term->term_id;
                                                    $parent_cat = $term->parent;
                                                    if ( in_array( $cat_id, $values['Prod_Categ'] ) || in_array( $parent_cat, $values['Prod_Categ'] ) ) {
                                                        if ( ! $product_is_variable ) {
                                                            $new_price = $reg_price - ( $reg_price * $values['Discount'] ) / 100;
                                                            if ( $woo_ver < '3.0.0' ) {
                                                                $value['data']->price = $new_price;
                                                            } else {
                                                                $value['data']->set_price( $new_price );
                                                            }
                                                        } elseif ( $product_is_variable ) {
                                                            $new_price = $variable_price - ( $variable_price * $values['Discount'] ) / 100;
                                                            if ( $woo_ver < '3.0.0' ) {
                                                                $value['data']->price = $new_price;
                                                            } else {
                                                                $value['data']->set_price( $new_price );
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            
            
        }

        
    new WooGC_Compatibility_PRWOO()



?>