<?php
/*
* Plugin Name: WP Global Cart
* Plugin URI: https://wpglobalcart.com/
* Description: Set a Global Cart for WooCommerce under a WordPress MultiSite environment
* Author: Nsp Code
* Author URI: https://wpglobalcart.com/ 
* Version: 5.0.8
* Text Domain: woo-global-cart
* Domain Path: /languages/
* Network: true
* WC requires at least: 3.2.0
* WC tested up to: 10.1.2
* PHP checked up to: 8.2.4
* Requires Plugins: woocommerce
*/

    defined( 'ABSPATH' ) || exit;
    
    define('WOOGC_VERSION',             '5.0.8');
    define('WOOGC_PATH',                plugin_dir_path(__FILE__));
    define('WOOGC_URL',                 plugins_url('', __FILE__));
    
    define('WOOGC_PRODUCT_ID',          'WooGC');
    define('WOOGC_INSTANCE',            preg_replace('/:[0-9]+/', '', str_replace(array ("https://" , "http://"), "", trim(network_site_url(), '/'))));
    define('WOOGC_UPDATE_API_URL',      'https://api.wpglobalcart.com/index.php');
            
    include_once(WOOGC_PATH . '/include/class.woogc.php');
    include_once(WOOGC_PATH . '/include/class.woogc.functions.php');
    include_once(WOOGC_PATH . '/include/class.woogc.licence.php');
    require_once(WOOGC_PATH . '/include/static-functions.php');
              
    //load language files
    add_action( 'plugins_loaded', 'WOOGC_load_textdomain'); 
    function WOOGC_load_textdomain() 
        {
            load_plugin_textdomain('woo-global-cart', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages');
            $locale             =   get_locale();
            $plugin_textdomain  =   'woo-global-cart';

            // Check if the specific translation file exists
            if (file_exists( WOOGC_PATH . "/languages/$plugin_textdomain-$locale.mo")) {
                load_textdomain( $plugin_textdomain, WOOGC_PATH . "/languages/$plugin_textdomain-$locale.mo" );
            } else {
                $general_locale = substr($locale, 0, 2);
                $general_mofile = WOOGC_PATH . "/languages/$plugin_textdomain-$general_locale.mo";
                
                if (file_exists($general_mofile))
                    load_textdomain( $plugin_textdomain, $general_mofile );
            } 
        }
    
    
    register_activation_hook(   __FILE__, 'WOOGC_activated');
    register_deactivation_hook( __FILE__, 'WOOGC_deactivated');

    function WOOGC_activated($network_wide) 
        {
            global $WooGC;
            $WooGC->functions->create_tables();
            $WooGC->functions->wp_config_add();
        }

    function WOOGC_deactivated() 
        {
            //unlink MU files
            global $WooGC;
            $WooGC->functions->remove_mu_files();
            $WooGC->functions->remove_tables();
            $WooGC->functions->wp_config_clean();
        }
        
    global $WooGC;
    
    $WooGC  =   new WOOGC();   
    $WooGC->init();
    
    
    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );
        
?>