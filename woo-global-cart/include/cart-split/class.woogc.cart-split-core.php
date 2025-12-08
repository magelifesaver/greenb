<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Cart_Split_Core
        {
            
            /**
            * Constructor
            * 
            */
            function __construct()
                {
                    add_action('plugins_loaded',                                array( $this, 'plugins_loaded'), 999  );
                    
                    add_action('init',                                          array( $this, 'init'), 999  ); 
                    
                    
                    add_filter ( 'rest_dispatch_request',           array ( $this, 'rest_dispatch_request' ), -1, 4 );
                    add_filter ( 'rest_request_after_callbacks',    array ( $this, 'rest_request_after_callbacks' ), 999, 3 );
    
                }
                     
            
            
            function plugins_loaded()
                {
                    
                    //remove_action( 'get_header',                              'wc_clear_cart_after_payment');
                    remove_action( 'template_redirect',                         'wc_clear_cart_after_payment', 20 ); 
                    add_action('template_redirect',                             array( $this,   'wc_clear_cart_after_payment' ), 20   );  
                    
                }
            
            /**
            * Trigger on Init action
            *     
            */
            function init()
                {
                    
                    add_action('woocommerce_cart_loaded_from_session',  array( $this, 'woocommerce_cart_loaded_from_session' ), 11);
                    
                    if  ( ! isset( $GLOBALS['woocommerce'] ) )
                        return;
                    
                    add_action( 'wp_enqueue_scripts',                   array( $this, 'wp_enqueue_scripts' ), 11 );
                        
                    //include custom clases
                    include_once( WOOGC_PATH . '/include/cart-split/class-wc-ajax.php');
                    
                    //remove the default output
                    remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
                    remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
                    
                    add_action( 'woocommerce_checkout_order_review',    array( $this,   'woocommerce_checkout_order_review' ));
                    add_action( 'woocommerce_after_checkout_form',      array( $this,   'woocommerce_after_checkout_form' ));

                    add_action('woocommerce_thankyou',  array( $this,'woocommerce_thankyou' ));
                    
                    //remove a defauulr shortocde and replace it with custom one
                    remove_shortcode("woocommerce_checkout");
                    add_shortcode("woocommerce_checkout", array( $this, 'shortcode_woocommerce_checkout'    ));
                    
                }
                
            
            /**
            * Adjust the cart content, before the REST route is processed
            * 
            * @param mixed $dispatch_request
            * @param mixed $request
            * @param mixed $route
            * @param mixed $handler
            */
            function rest_dispatch_request ( $dispatch_request, $request, $route, $handler )
                {
                    if ( 
                        ( $route  == '/wc/store/v1/cart' &&  WooGC_Functions::check_backtrace_for_caller( array ( array ( 'enqueue_data', 'Automattic\WooCommerce\Blocks\BlockTypes\Checkout') ) ) )
                        ||
                        $route == '/wc/store/v1/checkout'
                    )
                        {
                            
                            if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) 
                                {
                                    wc_load_cart();
                                    WC()->cart->calculate_totals();
                                }
                            
                            $status =   WC()->cart->cart_split->set_block();
                        }
                    return $dispatch_request;
                }
                
           
            /**
            * Put the items back to the cart
            * 
            * @param mixed $response
            * @param mixed $handler
            * @param mixed $request
            */
            function rest_request_after_callbacks ( $response, $handler, $request )
                {
                    $route  =   $request->get_route();
                    
                    if ( 
                        ( $route  == '/wc/store/v1/cart' &&  WooGC_Functions::check_backtrace_for_caller( array ( array ( 'enqueue_data', 'Automattic\WooCommerce\Blocks\BlockTypes\Checkout') ) ) )
                        ||
                        $route == '/wc/store/v1/checkout'
                    )
                        {
                            //restore original
                            WC()->cart->cart_split->restore_cart();   
                            WC()->cart->calculate_totals();
                        }
                    
                    return $response;
                    
                }
            
                
            function shortcode_woocommerce_checkout( $atts )
                {
                    include_once( WOOGC_PATH . '/include/shortcodes/class-wc-shortcode-checkout.php');
                    
                    return  WC_Shortcodes::shortcode_wrapper( array( 'WooGC_WC_Shortcode_Checkout', 'output' ), $atts );  
                }
            
            
            
            /**
            * Init the cart split class
            * 
            * @param mixed $cart
            */
            function woocommerce_cart_loaded_from_session( $cart )
                {
                    
                    include_once( WOOGC_PATH . '/include/cart-split/class.cart-split.php');
                    
                    $cart->cart_split   =   new WooGC_Cart_Split();
                    $cart->cart_split->init();

                }
                   
                
            function woocommerce_checkout_order_review()
                {
                    $status =   WC()->cart->cart_split->set_block();
                    if (    $status === FALSE ) 
                        {
                            //Wrong place, redirect to corect checkout 
                            $checkout_url   =   WC()->cart->cart_split->get_checkout_url();  
                            wp_redirect($checkout_url);
                            exit;
                        }
                    
                    $block_blog_id  =   WC()->cart->cart_split->get_current_block_blog_id(); 
                    $blog_details   =   get_blog_details( $block_blog_id );
                    
                    ?>
                    <h4><?php _e( 'Checkout for Shop', 'woo-global-cart' ); ?> - <b><?php echo apply_filters( 'woogc/checkout/split/order_review/shop_title', $blog_details->blogname, $block_blog_id ) ?></b></h4>
                    <?php
                    woocommerce_order_review();
                    woocommerce_checkout_payment();

                    
                }
                
                
            function woocommerce_after_checkout_form()
                {
                    
                    if  ( WC()->cart->cart_split->get_grouped_cart_count()    < 2 )
                        return;
                    
                    ?><div id="split-cart" class="checkout"><?php
                      
                    $current_key    =   WC()->cart->cart_split->current_key ;
                    foreach ( WC()->cart->cart_split->get_processed_content()  as  $key    =>  $data)
                        {
                            
                            if  ( $data['hash'] ==  $current_key )
                                continue;
                            
                            switch_to_blog( $data['blog_id'] );
                            
                            WC()->cart->cart_split->set_cart( $data['cart']);
                            
                            WC()->cart->calculate_totals();
                            
                            
                            $blog_details   =   get_blog_details( $data['blog_id'] );
                            
                            ?>
                                <div class="split-cart-item">
                                <h4><?php _e( 'Your order for', 'woo-global-cart' ); ?> <?php echo $blog_details->blogname ?></h4>
                            <?php
                            
                            woocommerce_order_review();
                            
                            ?>
                                </div>
                            <?php
                            
                            restore_current_blog();
                        }
                    
                    
                    ?><div class=""></div><?php
                        
                    //restore original
                    WC()->cart->cart_split->restore_cart();   
                    WC()->cart->calculate_totals();
                    
                }

                
            function wp_enqueue_scripts()
                {
                    
                    //de-register original file
                    wp_dequeue_script( 'wc-checkout' );
                    wp_deregister_script( 'wc-checkout' );

                    wp_enqueue_script( 'wc-checkout', WOOGC_URL . '/js/woogc-checkout.js', array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ), WC_VERSION);
                    wp_enqueue_style( 'split-cart', WOOGC_URL . '/css/split-cart.css');
                    
                }
                

            function wc_clear_cart_after_payment()
                {
                    global $wp;
                   
                    if ( ! empty( $wp->query_vars['order-received'] ) ) 
                        {

                            $order_id  = absint( $wp->query_vars['order-received'] );
                            $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.

                            if ( $order_id > 0 ) {
                                $order = wc_get_order( $order_id );

                                if ( $order && $order->get_order_key() === $order_key ) {
                                    WC()->cart->cart_split->exclude_processed_from_cart( $order );
                                }
                            }
                        }

                    if ( WC()->session->order_awaiting_payment > 0 ) 
                        {
                            $order = wc_get_order( WC()->session->order_awaiting_payment );

                            if ( $order && $order->get_id() > 0 ) {
                                // If the order has not failed, or is not pending, the order must have gone through.
                                if ( ! $order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
                                    WC()->cart->cart_split->exclude_processed_from_cart( $order );
                                }
                            }
                        }   
                }
                
                
                
            function woocommerce_thankyou()
                {
                    
                    if  ( WC()->cart->cart_split->get_grouped_cart_count()    < 1 )
                        return;
                    
                    ?>
                        <div id="split-cart" class="order-received">
                            <h2 class="woocommerce-column__split_cart_continue"><?php _e( 'Continue Checkout', 'woo-global-cart' ); ?></h2>
                            <p class="woocommerce-notice"><?php _e( 'There are other products in your cart which require Checkout', 'woo-global-cart' ); ?>.</p>
                            <a href="<?php echo wc_get_checkout_url(); ?>" class="button continue"><?php _e( 'Continue', 'woo-global-cart' ); ?></a>
                        </div>
                    
                    <?php
                }   
                           
        }
 
    new WooGC_Cart_Split_Core();
        
        
?>