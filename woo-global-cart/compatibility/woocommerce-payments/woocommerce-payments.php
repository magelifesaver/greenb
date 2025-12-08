<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:  WooCommerce Payments
    * Since:        5.4.0
    */

    class WooGC_woo_payments
        {            
            function __construct( $dependencies = array() ) 
                {
                    add_filter ( 'pre_option', array ( $this, 'pre_option'), 99, 3 );                      
                }
                
            function pre_option( $pre, $option, $default )
                {
                    global $_wp_switched_stack, $blog_id;

                    if ( isset ( $_wp_switched_stack[0] )   &&  $blog_id    !=  $_wp_switched_stack[0]  &&  strpos( $option, '_wcpay_' )    === 0  )
                        {
                            switch_to_blog( $_wp_switched_stack[0] );
                            
                            $option_value   =   get_option( $option );
                            $pre    =   $option_value;
                            
                            restore_current_blog();
                        }
                            
                    return $pre;
                }
            
        }

        
    new WooGC_woo_payments();

?>