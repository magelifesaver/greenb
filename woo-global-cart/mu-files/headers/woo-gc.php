<?php
 
    defined( 'ABSPATH' ) || exit;
        
    define('WOOGC_MULOADER_VERSION',  '1.4');

    //check if multisite is active
    if ( ! defined('MULTISITE')     ||  MULTISITE   === FALSE )
        return;

    if( !file_exists(WP_PLUGIN_DIR . '/woo-global-cart/include/static-functions.php'))
        return;
        
    require_once(  WP_PLUGIN_DIR . '/woo-global-cart/include/static-functions.php' );


?>