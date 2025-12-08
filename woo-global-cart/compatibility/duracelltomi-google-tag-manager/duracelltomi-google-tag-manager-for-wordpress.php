<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for Plugin Name: Google Tag Manager for Wordpress
    * Compatibility checked on Version: 1.9.2
    */

    
    class WooGC_Compatibility_Google_Tag_Manager
        {
            
            function __construct()
                {
                    
                    add_filter('gtm4wp_eec_product_array', array( $this, 'gtm4wp_eec_product_array' ), 999, 2 );
                    
                }
                
                
            
            function gtm4wp_eec_product_array( $_temp_productdata, $location )
                {
                    
                    
                    return $_temp_productdata;
                }
                                                                   
        }


    new WooGC_Compatibility_Google_Tag_Manager();    
    
?>