<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:      WooCommerce Smart Coupons
    * Since:            
    * Last checked:     9.19.1
    */
    
    class WooGC_woocommerce_smart_coupons
        {
            
            function __construct()
                {
                    
                    add_action ( 'plugins_loaded',                              array ( $this, 'plugins_loaded' ), 999 );                
                }
                
            
            function plugins_loaded()
                {
                    global $WooGC;
                     
                    if ( is_plugin_active( 'woocommerce-gateway-paypal-express/woocommerce-gateway-paypal-express.php' ) ) 
                        {
                            $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_ppe_checkout_order_review', 'WC_SC_Purchase_Credit', 'gift_certificate_receiver_detail_form' );
                        }
                        
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_checkout_after_customer_details', 'WC_SC_Purchase_Credit', 'gift_certificate_receiver_detail_form' );
                    
                    
                    add_filter( 'woocommerce_update_order_review_fragments',    array( $this, 'run_filter' ), 99 );
                    add_action( 'wcopc_before_display_checkout',                array( $this, 'run' ), 99 );
                }
                
                
            function run()
                {
                    
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-smart-coupons/includes/class-sc-purchase-credit.php');
                    
                    $WooGC_WC_SC_Purchase_Credit    =   new WooGC_WC_SC_Purchase_Credit();
                    
                }
                
            function run_filter( $fragments = array() )
                {
                    
                    $this->run();
                    
                    return $fragments;
                    
                }
                                                                   
        }


    new WooGC_woocommerce_smart_coupons();    
    
?>