<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:      Mollie Payments for WooCommerce
    * Since:            5.5.1
    * Last Check        7.3.4
    */

    class WooGC_mollie_payments_for_woocommerce
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_action( 'mollie-payments-for-woocommerce_orderlines_process_items_before_getting_product_id',   array( $this, '_orderlines_process_items_before_getting_product_id'), -1);
                    add_action( 'mollie-payments-for-woocommerce_orderlines_process_items_after_processing_item',       array( $this, '_orderlines_process_items_after_processing_item'), -1);
                    
                    //add_action( 'template_redirect' , array ( $this, '_on_template_redirect' ), 99 );
                                        
                }
                
            
            function _orderlines_process_items_before_getting_product_id( $cart_item )
                {
                    if( isset($cart_item['blog_id']))
                        switch_to_blog( $cart_item['blog_id'] );
                }
                
                
            function _orderlines_process_items_after_processing_item( $cart_item )
                {
                    if( isset($cart_item['blog_id']))
                        restore_current_blog();
                }
                
                
            function _on_template_redirect( )
                {
                    global $WooGC;
                    
                    $options    =   $WooGC->functions->get_options();   
                    if( $options['cart_checkout_type']  ==  'each_store' )
                        {
                            global $wp_filter;
                            foreach  ( $wp_filter as   $key    =>  $data )
                                {
                                    if ( preg_match('/woocommerce_thankyou_mollie_wc_gateway_/i', $key )    > 0 )
                                        {
                                            $callbacks  =   $data->callbacks;
                                            reset ( $callbacks );
                                            
                                            $caller_data   =   current( $callbacks );
                                            reset ( $caller_data );
                                            
                                            $caller_data_function   =   current ( $caller_data );
                                            
                                            $class_name =   get_class ( $caller_data_function['function'][0] );
                                            $WooGC->functions->remove_anonymous_object_filter( $key,     $class_name,    'thankyou_page' );
                                            
                                            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
                                        }
                                }

                        }    
                }
                    
        }

    new WooGC_mollie_payments_for_woocommerce();

?>