<?php
    
    defined( 'ABSPATH' ) || exit;

    class WOOGC_WC_Cart extends WC_Cart {
        
        public function __construct( $reload_session_data = FALSE ) 
            {
                global $WooGC;
                
                //remove the other hooks for WC_Cart_Session
                $WooGC->functions->remove_class_action('wp_loaded', 'WC_Cart_Session', 'get_cart_from_session');
                $WooGC->functions->remove_class_action('woocommerce_cart_emptied', 'WC_Cart_Session', 'destroy_cart_session');
                $WooGC->functions->remove_class_action('wp', 'WC_Cart_Session', 'maybe_set_cart_cookies', 99);
                $WooGC->functions->remove_class_action('shutdown', 'WC_Cart_Session', 'maybe_set_cart_cookies', 0);
                $WooGC->functions->remove_class_action('woocommerce_add_to_cart', 'WC_Cart_Session', 'maybe_set_cart_cookies');
                $WooGC->functions->remove_class_action('woocommerce_after_calculate_totals', 'WC_Cart_Session', 'set_session');
                $WooGC->functions->remove_class_action('woocommerce_cart_loaded_from_session', 'WC_Cart_Session', 'set_session');
                $WooGC->functions->remove_class_action('woocommerce_removed_coupon', 'WC_Cart_Session', 'set_session');
                $WooGC->functions->remove_class_action('woocommerce_cart_updated', 'WC_Cart_Session', 'persistent_cart_update');
                
                //remove parent class actions
                $WooGC->functions->remove_class_action('woocommerce_add_to_cart',       'WC_Cart', 'calculate_totals', 20);
                $WooGC->functions->remove_class_action('woocommerce_applied_coupon',    'WC_Cart', 'calculate_totals', 20);
                $WooGC->functions->remove_class_action('woocommerce_removed_coupon',    'WC_Cart', 'calculate_totals', 20 );
                $WooGC->functions->remove_class_action('woocommerce_cart_item_removed', 'WC_Cart', 'calculate_totals', 20);
                $WooGC->functions->remove_class_action('woocommerce_cart_item_restored','WC_Cart', 'calculate_totals', 20);
                $WooGC->functions->remove_class_action('woocommerce_check_cart_items',  'WC_Cart', 'check_cart_items', 1);
                $WooGC->functions->remove_class_action('woocommerce_check_cart_items',  'WC_Cart', 'check_cart_coupons', 1);
                $WooGC->functions->remove_class_action('woocommerce_after_checkout_validation', 'WC_Cart', 'check_customer_coupons', 1);
                
                $this->session          = new WOOGC_WC_Cart_Session( $this );
                $this->fees_api         = new WC_Cart_Fees( );
                $this->tax_display_cart = get_option( 'woocommerce_tax_display_cart' );

                // Register hooks for the objects.
                $this->session->init( $reload_session_data );

                add_action( 'woocommerce_add_to_cart',                  array( $this, 'calculate_totals' ), 20, 0 );
                add_action( 'woocommerce_applied_coupon',               array( $this, 'calculate_totals' ), 20, 0 );
                add_action( 'woocommerce_removed_coupon',               array( $this, 'calculate_totals' ), 20, 0 );
                add_action( 'woocommerce_cart_item_removed',            array( $this, 'calculate_totals' ), 20, 0 );
                add_action( 'woocommerce_cart_item_restored',           array( $this, 'calculate_totals' ), 20, 0 );
                add_action( 'woocommerce_check_cart_items',             array( $this, 'check_cart_items' ), 1 );
                add_action( 'woocommerce_check_cart_items',             array( $this, 'check_cart_coupons' ), 1 );
                add_action( 'woocommerce_after_checkout_validation',    array( $this, 'check_customer_coupons' ), 1 );
                                
            }
        
        /**
         * When cloning, ensure object properties are handled.
         *
         * These properties store a reference to the cart, so we use new instead of clone.
         */
        public function __clone() {
            $this->session  = clone $this->session;
            $this->fees_api = clone $this->fees_api;

            $this->session->set_cart( $this );
        }
        
            
        /**
             * Calculate totals for the items in the cart.
             *
             * @uses WC_Cart_Totals
             */
        public function calculate_totals() {
                $this->reset_totals();

                if ( $this->is_empty() ) {
                    $this->session->set_session();
                    return;
                }

                do_action( 'woocommerce_before_calculate_totals', $this );

                new WooGC_WC_Cart_Totals( $this ); 

                do_action( 'woocommerce_after_calculate_totals', $this );
            }
            
            /**
             * Reset cart totals to the defaults. Useful before running calculations.
             */
            private function reset_totals() {
                $this->totals = $this->default_totals;
                $this->fees_api->remove_all_fees();
                do_action( 'woocommerce_cart_reset', $this, false );
            }
            
            
            
            /**
             * Uses the shipping class to calculate shipping then gets the totals when its finished.
             */
            public function calculate_shipping() {
                
                if ( ! defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) )
                    {
                        return parent::calculate_shipping();   
                    }
                
                $this->shipping_methods = $this->needs_shipping() ? $this->get_chosen_shipping_methods( WC()->shipping()->calculate_shipping( $this->get_shipping_packages() ) ) : array();
                
                $shipping_taxes =   array();
                $tax_total      =   array();
                foreach ( $this->shipping_methods as $package   => $taxes )
                    {
                        foreach ( $taxes as $item )
                            {
                                if ( ! empty ( $item->get_taxes() ) )
                                    {
                                        $item_total_tax  =  $item->get_taxes();
                                        
                                        foreach ( $item_total_tax as $tax_id    =>  $value )
                                            {
                                                if ( isset ( $tax_total[ $tax_id ] ) )
                                                    $tax_total[ $tax_id ]   +=  $value;
                                                    else
                                                    $tax_total[ $tax_id ]   =  $value;   
                                            }
                                    }
                            }
                            
                        $shipping_taxes[ $package ] =   $tax_total;
                    }
                $merged_taxes   = $tax_total;
                /*
                foreach ( $shipping_taxes as $taxes ) {
                    foreach ( $taxes as $tax_id => $tax_amount ) {
                        if ( ! isset( $merged_taxes[ $tax_id ] ) ) {
                            $merged_taxes[ $tax_id ] = 0;
                        }
                        $merged_taxes[ $tax_id ] += $tax_amount;
                    }
                }
                */

                $shipping_total =   0;
                foreach ( $this->shipping_methods as $package   => $taxes )
                    {
                        foreach ( $taxes as $item )
                            {
                                $shipping_total   +=  $item->get_cost();
                            }
                    }

                $this->set_shipping_total( $shipping_total );
                $this->set_shipping_tax( array_sum( $merged_taxes ) );
                $this->set_shipping_taxes( $merged_taxes );

                //combine the data for further code compatibility
                $shipping_methods   =   $this->shipping_methods;
                foreach ( $this->shipping_methods as $package   => $taxes )
                    {
                        $shipping_item  =   FALSE;
                        $sum_costs   =   0;
                        $sum_taxes   =   array();
                        foreach ( $taxes as $item )
                            {
                                if ( ! $shipping_item ) 
                                    $shipping_item  =   clone ( $item );
                                
                                $sum_costs  +=   $item->get_cost();
                                foreach ( $item->get_taxes() as $key => $item_tax_line )
                                    {
                                        if ( ! isset ( $sum_taxes [ $key ] ) )
                                            $sum_taxes[ $key ] =   0;
                                        $sum_taxes[ $key ]  +=   $item_tax_line;
                                    }
                                
                            }
                            
                        $shipping_item->set_cost( $sum_costs );
                        $shipping_item->set_taxes( $sum_taxes );
                            
                        $shipping_methods[$package]    =   $shipping_item;
                    }
                
                return $shipping_methods;
            }
            
            
            
            /**
             * Looks through the cart to see if shipping is actually required.
             *
             * @return bool whether or not the cart needs shipping
             */
            public function needs_shipping() {
                
                if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' )  && WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP === TRUE )
                    return TRUE;
                
                if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
                    return false;
                }
                $needs_shipping = false;

                foreach ( $this->get_cart_contents() as $cart_item_key => $values ) {
                    if ( $values['data']->needs_shipping() ) {
                        $needs_shipping = true;
                        break;
                    }
                }

                return apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
            }
            
            
            
            /**
             * Sees if the customer has entered enough data to calc the shipping yet.
             *
             * @return bool
             */
            public function show_shipping() {
                
                if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' )  && WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP === TRUE )
                    return TRUE;
                
                if ( ! wc_shipping_enabled() || ! $this->get_cart_contents() ) {
                    return false;
                }

                if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) ) {
                    $country = $this->get_customer()->get_shipping_country();
                    if ( ! $country ) {
                        return false;
                    }
                    $country_fields = WC()->countries->get_address_fields( $country, 'shipping_' );
                    if ( isset( $country_fields['shipping_state'] ) && $country_fields['shipping_state']['required'] && ! $this->get_customer()->get_shipping_state() ) {
                        return false;
                    }
                    if ( isset( $country_fields['shipping_postcode'] ) && $country_fields['shipping_postcode']['required'] && ! $this->get_customer()->get_shipping_postcode() ) {
                        return false;
                    }
                }

                return apply_filters( 'woocommerce_cart_ready_to_calc_shipping', true );
            }
            
            
            /**
             * Given a set of packages with rates, get the chosen ones only.
             *
             * @since 3.2.0
             * @param array $calculated_shipping_packages Array of packages.
             * @return array
             */
            protected function get_chosen_shipping_methods( $calculated_shipping_packages = array() ) {
                
                if ( ! defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) )
                    {
                        return parent::get_chosen_shipping_methods( $calculated_shipping_packages );   
                    }
                
                $chosen_methods = array();
                // Get chosen methods for each package to get our totals.
                foreach ( $calculated_shipping_packages as $key => $package ) 
                    {
                        $content_products_map  =   array();
                        foreach ( $package['contents']   as  $data) 
                            $content_products_map[ $data['blog_id']]  =   TRUE;
                        
                        foreach ( $content_products_map as $_blog_id => $found )
                            {
                                $chosen_method = $this->wc_get_chosen_shipping_method_for_package( $key, $package, $_blog_id );
                                if ( $chosen_method ) {
                                    $chosen_methods[ $key ][ $_blog_id ] = $package['rates'][ $chosen_method ];
                                }
                            }
                    }
                return $chosen_methods;
            }
            
            
            /**
             * Get chosen method for package from session.
             *
             * @since  3.2.0
             * @param  int   $key Key of package.
             * @param  array $package Package data array.
             * @return string|bool
             */
            function wc_get_chosen_shipping_method_for_package( $key, $package, $blog_id ) {

                /**
                * 
                * Variable format:      
                * $chosen_methods [ $key ]    =   array (
                *                                                   $blog_id    =>  WC_Shipping_Rate
                *                                                   )
                */
                $chosen_methods = (array)WC()->session->get( 'chosen_shipping_methods' );
                if ( ! empty ( $chosen_methods[ $key ] )    &&  ! is_array ( $chosen_methods[ $key ] ) )
                    $chosen_methods[ $key ] =   (array)$chosen_methods[ $key ];              
                $chosen_method_list  = isset( $chosen_methods[ $key ] ) ? (array)$chosen_methods[ $key ] : false;
                
                $chosen_method  =   '';
                if ( is_array( $chosen_method_list ) && count ( $chosen_method_list ) > 0 )
                    {
                        foreach ( $chosen_method_list as $method )
                            {
                                if ( strpos( $method, $blog_id . "|" ) === 0 )
                                    {
                                        $chosen_method  =   $method;   
                                        break;
                                    }
                            }
                    }
                
                $changed        = wc_shipping_methods_have_changed( $key, $package );

                // This is deprecated but here for BW compat. TODO: Remove in 4.0.0.
                $method_counts = WC()->session->get( 'shipping_method_counts' );

                if ( ! empty( $method_counts[ $key ] ) ) {
                    $method_count = absint( $method_counts[ $key ] );
                } else {
                    $method_count = 0;
                }
                                
                // If not set, not available, or available methods have changed, set to the DEFAULT option.
                if ( ! $chosen_method || $changed || ! isset( $package['rates'][ $chosen_method ] ) || count( $package['rates'] ) !== $method_count ) {
                    $chosen_method          = $this->wc_get_default_shipping_method_for_package( $key, $package, $chosen_method, $blog_id );
                    
                    if ( ! isset ( $chosen_methods[ $key ] ) )
                        $chosen_methods[ $key ] =   array ();
                    

                    if ( $chosen_method )
                        {
                            if ( array_search ( $chosen_method, $chosen_methods[ $key ] ) ===  FALSE )
                                $chosen_methods[ $key ][] =  $chosen_method;
                            
                            $method_counts[ $key ]  = count( $package['rates'] );

                            WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
                            WC()->session->set( 'shipping_method_counts', $method_counts );

                            do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
                        }

                }
                return $chosen_method;
            }
            
            
            /**
             * Choose the default method for a package.
             *
             * @since  3.2.0
             * @param  int    $key Key of package.
             * @param  array  $package Package data array.
             * @param  string $chosen_method Chosen method id.
             * @return string
             */
            function wc_get_default_shipping_method_for_package( $key, $package, $chosen_method, $blog_id ) {
                $rate_keys = array_keys( $package['rates'] );
                
                foreach  ( $rate_keys as $key   =>  $rate_key )
                    {
                        if ( strpos( $rate_key, $blog_id . "|" ) !== 0 )
                            unset  ( $rate_keys[$key] );       
                    }
                
                $default   = current( $rate_keys );
                $coupons   = WC()->cart->get_coupons();
                foreach ( $coupons as $coupon ) {
                    if ( $coupon->get_free_shipping() ) {
                        foreach ( $rate_keys as $rate_key ) {
                            if ( 0 === stripos( $rate_key, $blog_id . '|free_shipping' ) ) {
                                $default = $rate_key;
                                break;
                            }
                        }
                        break;
                    }
                }
                return apply_filters( 'woocommerce_shipping_chosen_method', $default, $package['rates'], $chosen_method );
            }
            
       

    }


?>