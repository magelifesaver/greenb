<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Woo Tabbed Category Product Listing - Pro
    * Since Version:        9.4.1
    */

    class WooGC_Compatibility_woo_tabbed_category
        {
           
            function __construct()
                {

                    add_action( 'woocommerce_checkout_order_review',   array( $this, 'woocommerce_checkout_order_review'), 1 );
                }
                
            
            function woocommerce_checkout_order_review()
                {
                    //remove the split filter if plugin active and ajax cart content
                    if ( isset ( $_GET['wc-ajax'] ) &&  $_GET['wc-ajax']    ==  'get_refreshed_fragments' )
                        {
                    
                            global $WooGC;
                    
                            $WooGC->functions->remove_class_filter( 'woocommerce_checkout_order_review',     'WooGC_Cart_Split_Core', 'woocommerce_checkout_order_review', 10 );
                        }
                }
            
            
        }

        
    new WooGC_Compatibility_woo_tabbed_category()



?>