<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:     WPML Multilingual CMS
    * Since:         4.2.7.1
    */
    
    
    class WooGC_wpml
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_filter( 'woogc/on_shutdown/ob_buferring_output',   array( $this, '_on_shutdown_ob_buferring_output') );
                    
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_json_search_found_products' , 'WooGC_Admin', 'woocommerce_json_search_found_products');   
                                      
                }
                
                
            function _on_shutdown_ob_buferring_output( $continue )
                {
                    return FALSE;   
                }
            
            

     
     
        }

    new WooGC_wpml();



?>