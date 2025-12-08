<?php
 
    defined( 'ABSPATH' ) || exit;
        
    define('WOOGC_MULOADER_VERSION',  '1.4');

    //check if multisite is active
    if ( ! defined('MULTISITE')     ||  MULTISITE   === FALSE )
        return;

    if( !file_exists(WP_PLUGIN_DIR . '/woo-global-cart/include/static-functions.php'))
        return;
        
    require_once(  WP_PLUGIN_DIR . '/woo-global-cart/include/static-functions.php' );
        
    if ( is_subdomain_install() )
        {
            if ( !defined('COOKIE_DOMAIN') ) 
                {
                    global $blog_id;
                    
                    $blog_details   =   get_blog_details( $blog_id );
                    
                    $_domain =   WooGC_get_domain( $blog_details->domain );
                    if ( ! filter_var( $_domain, FILTER_VALIDATE_IP ) )
                        $_domain =   '.' . $_domain;
                        
                    define( 'COOKIE_DOMAIN', $_domain );
                }
                else
                {
                    global $WooGC__MU_Module;
                    
                    $WooGC__MU_Module   =   array();
                    
                    //we expect the cookie to be undefined   
                    $WooGC__MU_Module['issues'][]   =   'e01';
                }
        }


?>