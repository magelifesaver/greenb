<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          CartFlows
    * Since Version:        1.5.2
    */

    class WooGC_Compatibility_Cartflow
        {
           
            function __construct()
                {

                    add_action( 'wp',   array( $this, '_wp'), 9999 );
                }
                
            
            function _wp()
                {
                    global $WooGC;
                    

                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_billing',     'WC_Checkout', 'checkout_form_billing', 10 );
                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_shipping',    'WC_Checkout', 'checkout_form_shipping', 10 );

                    
                }
            
            
        }

        
    new WooGC_Compatibility_Cartflow()



?>