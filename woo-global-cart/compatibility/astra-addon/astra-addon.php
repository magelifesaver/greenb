<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:      Astra Pro 
    * Since:            4.8.0
    */

    class WooGC_astra_pro
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'astra_get_option_show-woo-grid-orders', array ( $this, 'astra_get_option'), 999, 3 );
                      
                }
                
            function astra_get_option ( $value, $option, $default )
                {
                    if ( $option != 'show-woo-grid-orders' )
                        return $value;
                        
                    if ( ! WooGC_Functions::check_backtrace_for_caller( array ( array ( 'modern_my_account_template', FALSE ) ) ) )
                        return $value;
                        
                    return FALSE;
                }         
            
        }

        
    new WooGC_astra_pro();

?>