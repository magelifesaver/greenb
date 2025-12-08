<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:      YITH WooCommerce Minimum Maximum Quantity Premium
    * Start:            1.4.7
    * Last Update for:  1.16.1
    */

    class WooGC_yith_woocommerce_minimum_maximum_quantity_premium
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {                    
                    remove_action('ywmmq_init',     'ywmmq_init');
                    
                    if ( function_exists( 'WC' ) )
                        add_action('ywmmq_init',        array ( $this, 'ywmmq_init') );
                        
                }
                
            function ywmmq_init()
                {
                    require_once YWMMQ_DIR . 'class-yith-wc-min-max-qty.php';
                    
                    include_once ( WOOGC_PATH . '/compatibility/yith-woocommerce-minimum-maximum-quantity-premium/includes/class.extend.php');
                    
                    return WooGC_YITH_WC_Min_Max_Qty::get_instance();    
                    
                }
        }

    new WooGC_yith_woocommerce_minimum_maximum_quantity_premium();

?>