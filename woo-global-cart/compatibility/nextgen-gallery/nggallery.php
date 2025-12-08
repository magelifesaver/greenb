<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:     NextGEN Gallery
    * Implemented on Version:         2.2.16
    */

    class WooGC_nextgen
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                    
                    add_filter('run_ngg_resource_manager', array ( $this, 'run_ngg_resource_manager') );
                    
                }
                
            
            function run_ngg_resource_manager()
                {
                    
                    return FALSE;
                       
                }
            
        }

    new WooGC_nextgen();

?>