<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce Cost of Goods
    * Since:        2.12.0
    */

    class WooGC_woo_cg
        {
            var $switched   =   FALSE;
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_action( 'woocommerce_admin_order_item_values', array( $this, 'switch_to_order_shop' ), 9 );
                    add_action( 'woocommerce_admin_order_item_values', array( $this, 'restore_shop' ), 11 );
                      
                }
                
            function switch_to_order_shop( )
                {
                    global $_wp_switched_stack, $blog_id;
                    
                    if ( isset ( $_wp_switched_stack[0] )   &&  $blog_id    !=  $_wp_switched_stack[0]  )
                        {
                            $this->switched    =   TRUE;
                            switch_to_blog( $_wp_switched_stack[0] );
                        }
                }         
                
            
            function restore_shop()
                {
                    if ( $this->switched )
                        {
                            $this->switched    =   FALSE;
                            restore_current_blog();
                        }
                }
            
        }

        
    new WooGC_woo_cg();

?>