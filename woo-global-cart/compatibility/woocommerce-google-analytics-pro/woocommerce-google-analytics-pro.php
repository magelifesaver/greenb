<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:     WooCommerce Google Analytics Pro
    * Since:         1.7.1
    */

    class WooGC_woocommerce_google_analytics_pro
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                    add_filter( 'wc_google_analytics_pro_event_classes_to_load', array ( $this, 'wc_google_analytics_pro_event_classes_to_load' ) );                    
                }

                
            function wc_google_analytics_pro_event_classes_to_load( $event_classes )
                {
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-google-analytics-pro/includes/Removed_From_Cart_Event.php');
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-google-analytics-pro/includes/Changed_Cart_Quantity_Event.php');
                    
                    foreach ( $event_classes    as  $key    =>  $event_name )
                        {
                            if ( $event_name    ==  'SkyVerge\WooCommerce\Google_Analytics_Pro\Tracking\Events\Universal_Analytics\Removed_From_Cart_Event' )
                                $event_classes[ $key ]  =   'SkyVerge\WooCommerce\Google_Analytics_Pro\Tracking\Events\Universal_Analytics\WooGC_Removed_From_Cart_Event';
                            if ( $event_name    ==  'SkyVerge\WooCommerce\Google_Analytics_Pro\Tracking\Events\Universal_Analytics\Changed_Cart_Quantity_Event' )
                                $event_classes[ $key ]  =   'SkyVerge\WooCommerce\Google_Analytics_Pro\Tracking\Events\Universal_Analytics\WooGC_Changed_Cart_Quantity_Event';
                        }
                    
                    return $event_classes;
                    
                }                
        }

    new WooGC_woocommerce_google_analytics_pro();

?>