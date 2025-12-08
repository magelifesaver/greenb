<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Shipping 
        {
            
            function __construct()
                {
                    
                    //add_filter('woocommerce_cart_shipping_packages',    array( $this, 'woocommerce_cart_shipping_packages'), 999 ); 
                    
                    add_filter ( 'woocommerce_product_variation_get_shipping_class_id',     array( $this, 'get_shipping_class_id'), 99, 2 );
                    add_filter ( 'woocommerce_product_get_shipping_class_id',               array( $this, 'get_shipping_class_id'), 99, 2 );
                    
                    if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' )  && WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP === TRUE )
                        {
                            add_action( 'wp_enqueue_scripts',                   array ( $this, 'wp_enqueue_scripts' ) , 11 );
                            add_filter( 'woocommerce_shipping_packages',        array ( $this, 'woocommerce_shipping_packages' ) );
                            add_action( 'wc_ajax_update_order_review',          array ( $this, 'update_order_review' ) );
                            add_action( 'wc_ajax_nopriv_update_order_review',   array ( $this, 'update_order_review' ) );
                            add_action( 'woocommerce_after_order_itemmeta',     array ( $this, 'woocommerce_after_order_itemmeta' ), 10, 2 );
                            add_filter( 'woocommerce_get_shipping_classes' ,    array ( $this, 'woocommerce_get_shipping_classes' ) );
                            
                            add_filter ( 'woocommerce_apply_base_tax_for_local_pickup', array ( $this, 'woocommerce_apply_base_tax_for_local_pickup' ) );
                            add_filter ( 'woocommerce_customer_taxable_address',        array ( $this, 'woocommerce_customer_taxable_address' ) );
                            
                            add_action ( 'woocommerce_blocks_loaded',                   array ( $this, 'woocommerce_blocks_loaded' ) );
                        }  
                    
                }
            
            /**
            * Map the shipping class id to the current shop
            * 
            * @param mixed $shipping_class_id
            * @param mixed $product_object
            * @return mixed
            */
            function get_shipping_class_id( $shipping_class_id, $product_object )
                {
                    if ( empty ( $shipping_class_id ) )
                        return;
                    
                    global $blog_id;
                    
                    $product_origin_blog_id    =   $product_object->get_meta( 'blog_id' );
                    
                    if ( empty ( $product_origin_blog_id ) )
                        return $shipping_class_id;
                    
                    if ( $blog_id   ==  $product_origin_blog_id )
                        return $shipping_class_id;
                    
                    switch_to_blog( $product_origin_blog_id );
                    $shipping_class_term    =   get_term_by ( 'term_id', $shipping_class_id, 'product_shipping_class' );
                    restore_current_blog();
                    
                    $local_shipping_term    =   get_term_by ( 'slug', $shipping_class_term->slug , 'product_shipping_class' );
                                
                    //60
                    if ( is_object ( $local_shipping_term ) )
                        $shipping_class_id  =   $local_shipping_term->term_id;
                    
                    return $shipping_class_id;
                }
                
            
            /**
            * Apply the dimmensions to every product
            * 
            * @param mixed $packages
            */
            function woocommerce_cart_shipping_packages( $packages )
                {
                    if ( is_array ( $packages )  &&  isset ( $packages[0] ) )
                        {
                            foreach($packages[0]['contents']    as  $key    =>  $data)
                                {
                                    $packages[0]['contents'][$key]['data']->weight  =   $packages[0]['contents'][$key]['data']->get_weight();
                                    $packages[0]['contents'][$key]['data']->length  =   $packages[0]['contents'][$key]['data']->get_length();
                                    $packages[0]['contents'][$key]['data']->height  =   $packages[0]['contents'][$key]['data']->get_height();
                                    $packages[0]['contents'][$key]['data']->width   =   $packages[0]['contents'][$key]['data']->get_width();
                                } 
                        }
                    
                    return $packages;
                        
                }
                
                
                
            function wp_enqueue_scripts()
                {
                    
                    wp_enqueue_script( 'woogc-cart', WOOGC_URL . '/js/woogc-cart.js', array(), array(), TRUE);
                    wp_enqueue_style( 'split-cart', WOOGC_URL . '/css/split-cart.css');
                    
                    if ( ! is_checkout() )
                        return;
                        
                    //de-recister original file
                    wp_dequeue_script( 'wc-checkout' );
                    wp_deregister_script( 'wc-checkout' );
                    
                    wp_enqueue_script( 'wc-checkout', WOOGC_URL . '/js/woogc-checkout.js', array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ), WC_VERSION);
                        
                }
                
                
            function woocommerce_shipping_packages( $_packages )
                {
                    $rates  =   array ();
                        
                    foreach ( $_packages as $package_key => $package ) 
                        {
                            $saved_content   =   $package['contents'];   
            
                            $content_products_map  =   array();
                            foreach ( $package['contents']   as  $key    =>  $data) 
                                $content_products_map[ $data['blog_id']][]  =   $key;
                                
                            foreach ( $content_products_map as  $cart_blog_id   =>  $items )
                                {
                                    $packages   =   array();
                                    
                                    $package['contents']   =   $saved_content;
                                    
                                    foreach ( $package['contents'] as $key =>  $data )
                                        {
                                            if ( !in_array ( $key, $items ) )
                                                unset ( $package['contents'][ $key ] );
                                        }
                                        
                                    switch_to_blog( $cart_blog_id );
                                    
                                    $packages[ $package_key ] = WC()->shipping()->calculate_shipping_for_package( $package, $package_key );
                                    
                                    restore_current_blog();
                                    
                                    foreach  ( $packages[ $package_key ]['rates'] as $rate_key   =>  $data )
                                        $rates[ $cart_blog_id . "|" . $rate_key ] = $data;
                                }
                                
                            $_packages[$package_key]['rates']  =   $rates;
                        }    
                                    
                    return $_packages;   
                }
                
            
            function update_order_review()
                {
                    check_ajax_referer( 'update-order-review', 'security' );

                    wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

                    if ( WC()->cart->is_empty() && ! is_customize_preview() && apply_filters( 'woocommerce_checkout_update_order_review_expired', true ) ) {
                        update_order_review_expired();
                    }

                    do_action( 'woocommerce_checkout_update_order_review', isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                    $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
                    $posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

                    if ( is_array( $posted_shipping_methods ) ) {
                        foreach ( $posted_shipping_methods as $i => $value ) {
                            $chosen_shipping_methods[ $i ] = $value;
                        }
                    }

                    WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
                    WC()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : wc_clean( wp_unslash( $_POST['payment_method'] ) ) );
                    WC()->customer->set_props(
                        array(
                            'billing_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
                            'billing_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
                            'billing_postcode'  => isset( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : null,
                            'billing_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
                            'billing_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
                            'billing_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
                        )
                    );

                    if ( wc_ship_to_billing_address_only() ) {
                        WC()->customer->set_props(
                            array(
                                'shipping_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
                                'shipping_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
                                'shipping_postcode'  => isset( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : null,
                                'shipping_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
                                'shipping_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
                                'shipping_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
                            )
                        );
                    } else {
                        WC()->customer->set_props(
                            array(
                                'shipping_country'   => isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null,
                                'shipping_state'     => isset( $_POST['s_state'] ) ? wc_clean( wp_unslash( $_POST['s_state'] ) ) : null,
                                'shipping_postcode'  => isset( $_POST['s_postcode'] ) ? wc_clean( wp_unslash( $_POST['s_postcode'] ) ) : null,
                                'shipping_city'      => isset( $_POST['s_city'] ) ? wc_clean( wp_unslash( $_POST['s_city'] ) ) : null,
                                'shipping_address_1' => isset( $_POST['s_address'] ) ? wc_clean( wp_unslash( $_POST['s_address'] ) ) : null,
                                'shipping_address_2' => isset( $_POST['s_address_2'] ) ? wc_clean( wp_unslash( $_POST['s_address_2'] ) ) : null,
                            )
                        );
                    }

                    if ( isset( $_POST['has_full_address'] ) && wc_string_to_bool( wc_clean( wp_unslash( $_POST['has_full_address'] ) ) ) ) {
                        WC()->customer->set_calculated_shipping( true );
                    } else {
                        WC()->customer->set_calculated_shipping( false );
                    }

                    WC()->customer->save();

                    // Calculate shipping before totals. This will ensure any shipping methods that affect things like taxes are chosen prior to final totals being calculated. Ref: #22708.
                    WC()->cart->calculate_shipping();
                    WC()->cart->calculate_totals();

                    // Get order review fragment.
                    ob_start();
                    woocommerce_order_review();
                    $woocommerce_order_review = ob_get_clean();

                    // Get checkout payment fragment.
                    ob_start();
                    woocommerce_checkout_payment();
                    $woocommerce_checkout_payment = ob_get_clean();

                    // Get messages if reload checkout is not true.
                    $reload_checkout = isset( WC()->session->reload_checkout );
                    if ( ! $reload_checkout ) {
                        $messages = wc_print_notices( true );
                    } else {
                        $messages = '';
                    }

                    unset( WC()->session->refresh_totals, WC()->session->reload_checkout );

                    wp_send_json(
                        array(
                            'result'    => empty( $messages ) ? 'success' : 'failure',
                            'messages'  => $messages,
                            'reload'    => $reload_checkout,
                            'fragments' => apply_filters(
                                'woocommerce_update_order_review_fragments',
                                array(
                                    '.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
                                    '.woocommerce-checkout-payment' => $woocommerce_checkout_payment,
                                )
                            ),
                        )
                    );   
                    
                    
                }
                
            
            /**
            * Output the shipping blog id
            *     
            * @param mixed $item_id
            * @param mixed $item
            */
            function woocommerce_after_order_itemmeta( $item_id, $item )
                {
                    $blog_id    =   $item->get_meta( 'blog_id' ); 
                    if ( ! empty ( $blog_id ) )
                        {
                            ?>
                            <table cellspacing="0" class="display_meta">
                                <tr>
                                    <th>Blog ID:</th>
                                    <td><?php echo $blog_id; ?></td>
                                </tr>
                            </table>
                            <?php
                        }
                }
                
                
            /**
            * Ensure it returns the correct shipping classes for current shop, instead the cached ones. 
            *     
            * @param mixed $shipping_classes
            */
            function woocommerce_get_shipping_classes( $shipping_classes )
                {
                    $classes                =       get_terms(  'product_shipping_class',
                                                                                            array(
                                                                                                'hide_empty' => '0',
                                                                                                'orderby'    => 'name',
                                                                                            )
                                                                                        );
                    return $classes;    
                    
                }
                
                
            function woocommerce_apply_base_tax_for_local_pickup( $status )
                {
                    
                    return FALSE;  
                }
                
                
            function woocommerce_customer_taxable_address ( $data )
                {
                    $tax_based_on = get_option( 'woocommerce_tax_based_on' );

                    // Check shipping method at this point to see if we need special handling.
                    if ( count( array_intersect( $this->wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
                        $tax_based_on = 'base';
                    }

                    if ( 'base' === $tax_based_on ) {
                        $country  = WC()->countries->get_base_country();
                        $state    = WC()->countries->get_base_state();
                        $postcode = WC()->countries->get_base_postcode();
                        $city     = WC()->countries->get_base_city();
                    } elseif ( 'billing' === $tax_based_on ) {
                        $country  = WC()->customer->get_billing_country();
                        $state    = WC()->customer->get_billing_state();
                        $postcode = WC()->customer->get_billing_postcode();
                        $city     = WC()->customer->get_billing_city();
                    } else {
                        $country  = WC()->customer->get_shipping_country();
                        $state    = WC()->customer->get_shipping_state();
                        $postcode = WC()->customer->get_shipping_postcode();
                        $city     = WC()->customer->get_shipping_city();
                    }
                        
                    return $data;   
                }
                
                
            /**
             * Gets chosen shipping method IDs from chosen_shipping_methods session, without instance IDs.
             *
             * @since  2.6.2
             * @return string[]
             */
            function wc_get_chosen_shipping_method_ids() {
                $method_ids     = array();
                $chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
                foreach ( $chosen_methods as $block_chosen_method ) 
                {
                    if ( is_array ( $block_chosen_method ) )
                        {
                            foreach ( $block_chosen_method as $chosen_method )
                                {
                                    $chosen_method = explode( ':', $chosen_method );
                                    $method_ids[]  = current( $chosen_method );
                                }
                        }
                }
                return $method_ids;
            }
            
            
            
            
            
            
            function woocommerce_blocks_loaded()
                {
                    global $WooGC;
                            
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_customer_taxable_address', 'Automattic\WooCommerce\Blocks\Shipping\ShippingController',   'filter_taxable_address' );
                }
 
        }

    new WooGC_Shipping();

?>