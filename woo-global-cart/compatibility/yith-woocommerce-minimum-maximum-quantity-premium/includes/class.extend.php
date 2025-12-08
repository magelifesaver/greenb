<?php


if ( ! defined( 'ABSPATH' ) ) {  exit; }


    class WooGC_YITH_WC_Min_Max_Qty extends YITH_WC_Min_Max_Qty
        {
            
            
            /**
             * Returns single instance of the class
             *
             * @return YITH_WC_Min_Max_Qty
             * @since 1.0.0
             */
            public static function get_instance() {

                if ( is_null( self::$instance ) ) {
                    self::$instance = new self();
                }

                return self::$instance;

            }
     
            function __construct() {

                parent::__construct();
            
            }
     
            
            
            /**
             * Validates cart and checkout on page load.
             *
             * @return void
             * @since  1.0.0
             * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
             */
            public function cart_validation() 
                {

                    if ( apply_filters( 'ywmmq_exclude_role_from_rules', false ) || isset( $_POST['woocommerce-login-nonce'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
                        return;
                    }

                    $on_cart_page     = is_page( wc_get_page_id( 'cart' ) );
                    $on_quote_page    = function_exists( 'YITH_Request_Quote' ) ? is_page( YITH_Request_Quote()->get_raq_page_id() ) : false;
                    $on_checkout_page = is_checkout() && ! is_checkout_pay_page() && ! is_order_received_page();

                    if ( get_option( 'ywmmq_enable_rules_on_quotes' ) === 'yes' && $on_quote_page && function_exists( 'YITH_Request_Quote' ) ) {
                        $this->contents_type        = 'quote';
                        $this->contents_to_validate = YITH_Request_Quote()->get_raq_return();
                    } else {
                        $this->contents_type        = 'cart';
                        $this->contents_to_validate = WC()->cart->cart_contents;
                    }
                           
                    if ( $on_cart_page || $on_checkout_page || $on_quote_page ) {

                        if ( function_exists( 'YITH_Request_Quote' ) ) {
                            if ( YITH_YWRAQ_Order_Request()->get_current_order_id() ) {
                                return;
                            }
                        }

                        $cart_update_notice = esc_html__( 'Cart updated.', 'woocommerce' );
                        $cart_update        = wc_has_notice( $cart_update_notice );
                        $errors             = array();
                        wc_clear_notices();

                        if ( $this->contents_to_validate ) {

                            if ( $on_cart_page || $on_quote_page ) {
                                $current_page = 'cart';
                            } else {
                                $current_page = '';
                            }

                            
                            global $blog_id;
                            
                            $contents_to_validate   =   array ( );
                            foreach ( $this->contents_to_validate   as  $key    => $data )
                                {
                                    if ( ! isset ( $data['blog_id'] ) )
                                        continue;
                                        
                                    $contents_to_validate[ $data['blog_id'] ][$key] =   $data;
                                }
                                
                            foreach ( $contents_to_validate as $_blog_id    =>  $data )
                                {
                                    switch_to_blog( $_blog_id );
                                    
                                    $blog_details   =   get_blog_details ( $_blog_id );
                                    
                                    $this->contents_to_validate =   $data;
                            
                                    $errors = $this->validate( $current_page, ( $on_cart_page || $on_quote_page ) );

                                    if ( $errors ) {

                                        ob_start();

                                        ?>

                                        <ul>
                                            <?php foreach ( $errors as $error ) : ?>
                                                <li><?php 
                                                echo esc_html( $blog_details->blogname ) . ":<br />";
                                                echo $error; //phpcs:ignore ?></li>
                                            <?php endforeach; ?>
                                            <?php echo apply_filters( 'ywmmq_additional_notification', '' ); //phpcs:ignore ?>
                                        </ul>

                                        <?php

                                        $error_list = ob_get_clean();

                                        wc_add_notice( $error_list, 'error' );

                                    }
                                    
                                    restore_current_blog();
                                }

                            if ( $cart_update && empty( $errors ) ) {
                                wc_add_notice( $cart_update_notice );
                            }
                        }
                    }

                }
                
            
            
                
            /**
             * Return the total value of all excluded items in the cart
             *
             * @return int
             * @since  1.0.0
             * @author Alberto Ruggiero <alberto.ruggiero@yithemes.com>
             */
            public function cart_total_excluded_value() {

                $total_value = 0;

                if ( $this->contents_to_validate ) {

                    foreach ( $this->contents_to_validate as $item_id => $item ) {

                        if ( ! isset( $item['product_id'] ) || 'cart' === $item_id ) {
                            continue;
                        }
                        
                        do_action( 'woocommerce/cart_loop/start', $item );

                        if ( apply_filters( 'ywmmq_check_exclusion', false, $item_id, $item['product_id'] ) ) {

                            if ( 'cart' === $this->contents_type ) {
                                $total_value += wc_format_decimal( $item['line_total'] + $item['line_tax'] );
                            } else {
                                $_product = wc_get_product( $item['product_id'] );

                                $total_value += wc_format_decimal( wc_get_price_including_tax( $_product, array( 'qty' => $item['quantity'] ) ) );
                            }
                        }
                        
                        do_action( 'woocommerce/cart_loop/end', $item );
                    }
                }

                return $total_value;

            }        
    
            
        }


?>