<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          TranslatePress - Multilingual
    * Since:                2.4.4
    */

    class WooGC_translatepress_multilingual
        {
            
            function __construct( $dependencies = array() ) 
                {
                    add_filter ('woogc/on_shutdown/ob_buferring_output', array ( $this, 'woogc_on_shutdown_ob_buferring_output' ) );
                }
                
            function woogc_on_shutdown_ob_buferring_output( $status )
                {
                    return TRUE;
                }         
            
        }

        
    new WooGC_translatepress_multilingual();

?>