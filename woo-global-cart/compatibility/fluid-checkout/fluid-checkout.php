<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:  Fluid Checkout
    * Since:        3.2.6
    */

    class WooGC_fluid_checkout
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_action( 'init', array( $this, 'late_hooks' ), 999 );
                      
                }
                
            function late_hooks()
                {
                    global $WooGC;
                    
                    //unregister the hook from original class                    
                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_billing', 'WOOGC_WC_Checkout', 'checkout_form_billing' );
                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_shipping', 'WOOGC_WC_Checkout', 'checkout_form_shipping' );
                }         
            
        }

        
    new WooGC_fluid_checkout();

?>