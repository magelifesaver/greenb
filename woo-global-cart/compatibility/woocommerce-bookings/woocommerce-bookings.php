<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce Bookings
    * Since:                1.15.60
    */

    class WooGC_wp_hide
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_action( 'plugins_loaded',   array ( $this, 'plugins_loaded'), 11 );
                      
                }
                
            function plugins_loaded()
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_cart_loaded_from_session' , 'WC_Booking_Cart_Manager', 'cart_loaded_from_session');
                
                }       
            
        }

        
    new WooGC_wp_hide();

?>