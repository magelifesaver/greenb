<?php


    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:      WooCommerce Bulk Discount
    * Since:            3.0
    */


    class WooGC_Woo_Bulk_Discount_Plugin_t4m extends Woo_Bulk_Discount_Plugin_t4m
        {
           
            function __construct() 
                {

                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_loaded', 'Woo_Bulk_Discount_Plugin_t4m', 'woocommerce_loaded' );
                    
                    $this->woocommerce_loaded();
              
                }
                
                
            /**
            * Main processing hooks
            */
            public function woocommerce_loaded() 
                {

                    add_action( 'woocommerce_before_calculate_totals', array( $this, 'action_before_calculate' ), 10, 1 );
                    add_action( 'woocommerce_calculate_totals', array( $this, 'action_after_calculate' ), 10, 1 );
                    add_action( 'woocommerce_before_cart_table', array( $this, 'before_cart_table' ) );
                    add_action( 'woocommerce_single_product_summary', array( $this, 'single_product_summary' ), 45 );
                    add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'filter_subtotal_price' ), 10, 2 );
                    add_filter( 'woocommerce_checkout_item_subtotal', array( $this, 'filter_subtotal_price' ), 10, 2 );
                    add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'filter_subtotal_order_price' ), 10, 3 );
                    add_filter( 'woocommerce_product_write_panel_tabs', array( $this, 'action_product_write_panel_tabs' ) );
                    
                    add_filter( 'woocommerce_product_data_panels', array( $this, 'action_product_write_panels' ) );
                    
                    add_action( 'woocommerce_process_product_meta', array( $this, 'action_process_meta' ) );
                    add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'filter_cart_product_subtotal' ), 10, 3 );
                    add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_update_meta' ) );

                    add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_item_price' ), 10, 2 );
                    add_filter( 'woocommerce_update_cart_validation', array( $this, 'filter_before_calculate' ), 10, 1 );

                }
                
                
            /**
            * Gather discount information to the array $this->discount_coefs
            */
            protected function gather_discount_coeffs() {

                global $woocommerce;

                $cart = $woocommerce->cart;
                $this->discount_coeffs = array();

                if ( sizeof( $cart->cart_contents ) > 0 ) {
                    foreach ( $cart->cart_contents as $cart_item_key => $values ) {
                        $_product = $values['data'];
                        
                        if ( isset ( $values['blog_id'] ) ) switch_to_blog( $values['blog_id'] );
                        
                        $quantity = 0;
                        if ( get_option( 'woocommerce_t4m_variations_separate', 'yes' ) == 'no' && $_product instanceof WC_Product_Variation && $this->get_parent($_product) ) {
                            $parent = $this->get_parent($_product);
                            foreach ( $cart->cart_contents as $valuesInner ) {
                                $p = $valuesInner['data'];
                                if ( $p instanceof WC_Product_Variation && $this->get_parent($p) && $this->get_product_id($this->get_parent($p)) == $this->get_product_id($parent) ) {
                                    $quantity += $valuesInner['quantity'];
                                    $this->discount_coeffs[$this->get_variation_id($_product)]['quantity'] = $quantity;
                                }
                            }
                        } else {
                            $quantity = $values['quantity'];
                        }
                        $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] = $this->get_discounted_coeff( $this->get_product_id($_product), $quantity );
                        $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] = $_product->get_price();
                        $this->discount_coeffs[$this->get_actual_id( $_product )]['quantity'] = $quantity;
                        
                        if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                    }
                }

            }
            
            
            
            /**
             * Hook to woocommerce_before_calculate_totals action.
             *
             * @param WC_Cart $cart
             */
            public function action_before_calculate( WC_Cart $cart ) {

                if ( $this->coupon_check() ) {
                    return;
                }

                if ( $this->bulk_discount_calculated ) {
                    return;
                }

                $this->gather_discount_coeffs();

                if ( sizeof( $cart->cart_contents ) > 0 ) {

                    foreach ( $cart->cart_contents as $cart_item_key => $values ) {
                        $_product = $values['data'];
                        
                        if ( isset ( $values['blog_id'] ) ) switch_to_blog( $values['blog_id'] );
                        
                        if ( get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) != '' && get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) !== 'yes' ) {
                            if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                            continue;
                        }
                        if ( ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) ) {
                            $row_base_price = max( 0, $_product->get_price() - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] / $values['quantity'] ) );
                        } else if ( ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'fixed' ) ) {
                            $row_base_price = max( 0, $_product->get_price() - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] / $values['quantity'] ) );
                        } else {
                            $row_base_price = $_product->get_price() * $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
                        }

                        $values['data']->set_price( $row_base_price );
                        
                        if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                    }

                    $this->bulk_discount_calculated = true;

                }

            }
            
            
            
            public function filter_before_calculate( $res ) {

                global $woocommerce;

                if ( $this->bulk_discount_calculated ) {
                    return $res;
                }

                $cart = $woocommerce->cart;

                if ( $this->coupon_check() ) {
                    return $res;
                }

                $this->gather_discount_coeffs();

                if ( sizeof( $cart->cart_contents ) > 0 ) {

                    foreach ( $cart->cart_contents as $cart_item_key => $values ) {
                        $_product = $values['data'];
                        
                        if ( isset ( $values['blog_id'] ) ) switch_to_blog( $values['blog_id'] );
                        
                        if ( get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) != '' && get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) !== 'yes' ) {
                            if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                            continue;
                        }
                        if ( ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'flat' ) ) {
                            $row_base_price = max( 0, $_product->get_price() - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] / $values['quantity'] ) );
                        } else if ( ( get_option( 'woocommerce_t4m_discount_type', '' ) == 'fixed' ) ) {
                            $row_base_price = max( 0, $_product->get_price() - ( $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'] / $values['quantity'] ) );
                        } else {
                            $row_base_price = $_product->get_price() * $this->discount_coeffs[$this->get_actual_id( $_product )]['coeff'];
                        }

                        $values['data']->set_price( $row_base_price );
                        
                        if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                    }

                    $this->bulk_discount_calculated = true;

                }

                return $res;

            }
            
            
            
            /**
             * Hook to woocommerce_calculate_totals.
             *
             * @param WC_Cart $cart
             */
            public function action_after_calculate( WC_Cart $cart ) {

                if ( $this->coupon_check() ) {
                    return;
                }

                if ( !$this->bulk_discount_calculated ) {
                    return;
                }

                if ( sizeof( $cart->cart_contents ) > 0 ) {
                    foreach ( $cart->cart_contents as $cart_item_key => $values ) {
                        $_product = $values['data'];
                        
                        if ( isset ( $values['blog_id'] ) ) switch_to_blog( $values['blog_id'] );
                        
                        if ( get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) != '' && get_post_meta( $this->get_product_id($_product), "_bulkdiscount_enabled", true ) !== 'yes' ) {
                            if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                            continue;
                        }
                        $values['data']->set_price( $this->discount_coeffs[$this->get_actual_id( $_product )]['orig_price'] );
                        
                        if ( isset ( $values['blog_id'] ) ) restore_current_blog();
                    }
                    $this->bulk_discount_calculated = false;
                }

            }
          
            
            
        }

    new WooGC_Woo_Bulk_Discount_Plugin_t4m();

?>