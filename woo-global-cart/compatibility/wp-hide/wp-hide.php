<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WP Hide & Security Enhancer PRO
    * Since:        2.2.1
    */

    class WooGC_wp_hide
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'woogc/sync_directory_url', array ( $this, 'sync_directory_url') );
                      
                }
                
            function sync_directory_url( $sync_directory_url )
                {
                    global $wph;
                    
                    $sync_directory    =   $wph->functions->content_urls_replacement( WOOGC_URL,  $wph->functions->get_replacement_list() );
                    $sync_directory    .=   '/sync';
                    
                    $site_home  =   site_url();
                    $site_home  =   str_replace(array('http://', 'https://'), "", $site_home);
                    $site_home  =   trim($site_home, '/');
                      
                    $sync_directory_url     =   str_replace(array('http://', 'https://'), "", $sync_directory);
                    $sync_directory_url     =   str_replace($site_home, "", $sync_directory_url);
                
                    return $sync_directory_url;
                }         
            
        }

        
    new WooGC_wp_hide();

?>