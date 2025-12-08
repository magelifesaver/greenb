<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Cart_Split_AJAX 
        {

            function __construct()
                {
                    add_action( 'wc_ajax_update_order_review',          array( $this, 'update_order_review' ), -1 );
                    add_action( 'wc_ajax_checkout',                     array( $this, 'checkout' ), -1 );
                }
                
                
            /**
            * AJAX update order review on checkout.
            */
            function update_order_review() 
                {
                    WC()->cart->cart_split->set_block();
                    
                    check_ajax_referer( 'update-order-review', 'security' );

                    wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

                    if ( WC()->cart->is_empty() && ! is_customize_preview() ) {
                        self::update_order_review_expired();
                    }

                    do_action( 'woocommerce_checkout_update_order_review', $_POST['post_data'] );

                    $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

                    if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) {
                        foreach ( $_POST['shipping_method'] as $i => $value ) {
                            $chosen_shipping_methods[ $i ] = wc_clean( $value );
                        }
                    }

                    WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
                    WC()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : $_POST['payment_method'] );
                    WC()->customer->set_props(
                        array(
                            'billing_country'   => isset( $_POST['country'] ) ? wp_unslash( $_POST['country'] ) : null,
                            'billing_state'     => isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : null,
                            'billing_postcode'  => isset( $_POST['postcode'] ) ? wp_unslash( $_POST['postcode'] ) : null,
                            'billing_city'      => isset( $_POST['city'] ) ? wp_unslash( $_POST['city'] ) : null,
                            'billing_address_1' => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : null,
                            'billing_address_2' => isset( $_POST['address_2'] ) ? wp_unslash( $_POST['address_2'] ) : null,
                        )
                    );

                    if ( wc_ship_to_billing_address_only() ) {
                        WC()->customer->set_props(
                            array(
                                'shipping_country'   => isset( $_POST['country'] ) ? wp_unslash( $_POST['country'] ) : null,
                                'shipping_state'     => isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : null,
                                'shipping_postcode'  => isset( $_POST['postcode'] ) ? wp_unslash( $_POST['postcode'] ) : null,
                                'shipping_city'      => isset( $_POST['city'] ) ? wp_unslash( $_POST['city'] ) : null,
                                'shipping_address_1' => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : null,
                                'shipping_address_2' => isset( $_POST['address_2'] ) ? wp_unslash( $_POST['address_2'] ) : null,
                            )
                        );
                    } else {
                        WC()->customer->set_props(
                            array(
                                'shipping_country'   => isset( $_POST['s_country'] ) ? wp_unslash( $_POST['s_country'] ) : null,
                                'shipping_state'     => isset( $_POST['s_state'] ) ? wp_unslash( $_POST['s_state'] ) : null,
                                'shipping_postcode'  => isset( $_POST['s_postcode'] ) ? wp_unslash( $_POST['s_postcode'] ) : null,
                                'shipping_city'      => isset( $_POST['s_city'] ) ? wp_unslash( $_POST['s_city'] ) : null,
                                'shipping_address_1' => isset( $_POST['s_address'] ) ? wp_unslash( $_POST['s_address'] ) : null,
                                'shipping_address_2' => isset( $_POST['s_address_2'] ) ? wp_unslash( $_POST['s_address_2'] ) : null,
                            )
                        );
                    }

                    if ( wc_string_to_bool( $_POST['has_full_address'] ) ) {
                        WC()->customer->set_calculated_shipping( true );
                    } else {
                        WC()->customer->set_calculated_shipping( false );
                    }

                    WC()->customer->save();
                    WC()->cart->calculate_totals();

                    // Get order review fragment
                    ob_start();
                    woocommerce_order_review();
                    $woocommerce_order_review = ob_get_clean();

                    // Get checkout payment fragment
                    ob_start();
                    woocommerce_checkout_payment();
                    $woocommerce_checkout_payment = ob_get_clean();

                    // Get messages if reload checkout is not true
                    $messages = '';
                    if ( ! isset( WC()->session->reload_checkout ) ) {
                        ob_start();
                        wc_print_notices();
                        $messages = ob_get_clean();
                    }

                    unset( WC()->session->refresh_totals, WC()->session->reload_checkout );

                    
                    //restore the cart
                    WC()->cart->cart_split->restore_cart();   
                    WC()->cart->calculate_totals();
                    
                    wp_send_json(
                        array(
                            'result'    => empty( $messages ) ? 'success' : 'failure',
                            'messages'  => $messages,
                            'reload'    => isset( WC()->session->reload_checkout ) ? 'true' : 'false',
                            'fragments' => apply_filters(
                                'woocommerce_update_order_review_fragments', array(
                                    '.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
                                    '.woocommerce-checkout-payment' => $woocommerce_checkout_payment,
                                )
                            ),
                        )
                    );
                    
                }
                
                
            function checkout()
                {
                    wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
                    WC()->checkout()->process_checkout();
                    wp_die( 0 );    
                    
                    
                }
   
        }
        
    new WooGC_Cart_Split_AJAX();
        
?>