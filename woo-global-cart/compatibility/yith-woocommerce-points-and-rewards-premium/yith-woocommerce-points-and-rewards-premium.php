<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:     YITH WooCommerce Points and Rewards Premium
    * Version:         1.5.8
    */

    class WooGC_yith_woocommerce_points_and_rewards_premium
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_filter( 'ywpar_wc_points_rewards_redemption_instance' , array( $this, 'YITH_WC_Points_Rewards_Redemption' ) );
                    
                }
                
                
            
            function YITH_WC_Points_Rewards_Redemption()
                {
                    include_once ( WOOGC_PATH . '/compatibility/yith-woocommerce-points-and-rewards-premium/classes/class.woogc-yith-wc-points-rewards-redemption.php');
                    
                    return WooGC_YITH_WC_Points_Rewards_Redemption::get_instance();
                }
            
        }

    new WooGC_yith_woocommerce_points_and_rewards_premium();

?>