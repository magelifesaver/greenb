<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_WooCommerce_AeliaCurencySwitcher
        {
           
            public function __construct() 
                {
                    
                    add_action('woocommerce_init', array($this, 'woocommerce_loaded'), 999);
              
                }
  
  
            function woocommerce_loaded()
                {
                    global $WooGC;
                    
                    $order_status_events = array(
                                                    'woocommerce_order_status_pending_to_processing',
                                                    'woocommerce_order_status_pending_to_completed',
                                                    'woocommerce_order_status_pending_to_cancelled',
                                                    'woocommerce_order_status_pending_to_on-hold',
                                                    'woocommerce_order_status_failed_to_processing',
                                                    'woocommerce_order_status_failed_to_completed',
                                                    'woocommerce_order_status_on-hold_to_processing',
                                                    'woocommerce_order_status_on-hold_to_cancelled',
                                                    'woocommerce_order_status_completed',
                                                    'woocommerce_order_fully_refunded',
                                                    'woocommerce_order_partially_refunded',
                                                );
                                                
                    foreach($order_status_events as $hook) 
                        {
                            $WooGC->functions->remove_anonymous_object_filter ( $hook . '_notification' , 'WC_Aelia_CurrencySwitcher', 'track_order_notification');
                        }
                }
  
        }

    new WooGC_WooCommerce_AeliaCurencySwitcher()

?>