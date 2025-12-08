<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Compatibility for Theme Name: Astra
    * Compatibility checked on Version: 1.4.9
    */

    class WooGC_Compatibility_Theme_Astra
        {
            
            function __construct()
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter ( 'wp' , 'Astra_Woocommerce', 'woocommerce_checkout');                    
                }
                                                                   
        }


    new WooGC_Compatibility_Theme_Astra();


?>