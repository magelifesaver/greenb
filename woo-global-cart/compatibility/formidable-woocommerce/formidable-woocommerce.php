<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for     : Formidable WooCommerce
    * Last checkedon        : 1.11
    */
    
    class WooGC_Compatibility_WC_Formidable
        {
            
            function __construct()
                {
                    
                    remove_action( 'plugins_loaded', array( 'WC_Formidable', 'get_instance' ) );
                    
                    include_once ( WOOGC_PATH . '/compatibility/formidable-woocommerce/woogc_wc_formidable.class.php');
                    WooGC_WC_Formidable::get_instance();
                    
                }
                                                                   
        }


    new WooGC_Compatibility_WC_Formidable();    
    
?>