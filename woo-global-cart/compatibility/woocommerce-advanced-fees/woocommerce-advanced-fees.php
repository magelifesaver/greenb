<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_WooCommerce_Advanced_Fees
        {
           
            public function __construct() 
                {
                    
                    add_filter( 'woocommerce_advanced_fees_registered_cost_options', array( $this, 'woocommerce_advanced_fees_registered_cost_options'), 99 );
              
                    //add custom_conditionals
                    add_filter( 'wpc_get_condition_class_name' ,  array( $this, 'wpc_get_condition_class_name'), 99, 2 );
              
                }
  
  
            public function woocommerce_advanced_fees_registered_cost_options( $registered_cost_options )
                {
                    
                    require_once WOOGC_PATH . '/compatibility/woocommerce-advanced-fees/includes/class-waf-cost-option-cost-per-category.php';
                    require_once WOOGC_PATH . '/compatibility/woocommerce-advanced-fees/includes/class-waf-cost-option-cost-per-shipping-class.php';
                    require_once WOOGC_PATH . '/compatibility/woocommerce-advanced-fees/includes/class-waf-cost-option-cost-per-product.php';
                    
                    $registered_cost_options['cost_per_shipping_class'] =   new WooGC_WAF_Cost_Option_Cost_Per_Shipping_Class();
                    $registered_cost_options['cost_per_category']       =   new WooGC_WAF_Cost_Option_Cost_Per_Category();
                    $registered_cost_options['cost_per_product']        =   new WooGC_WAF_Cost_Option_Cost_Per_Product();
                    
                    return $registered_cost_options;
                        
                }
                
                
            function wpc_get_condition_class_name( $class_name, $condition )
                {
                    
                    switch (    $class_name )
                        {
                            case 'WPC_Contains_Shipping_Class_Condition'   :
                                                                require_once WOOGC_PATH . '/compatibility/woocommerce-advanced-fees/includes/conditions/wpc-contains-shipping-class-condition.php';
                                                                
                                                                $class_name =   'WooGC_WPC_Contains_Shipping_Class_Condition';
                                                                break;
                            
                        }
                    
                    return $class_name;
                        
                }
  
        }

    new WooGC_WooCommerce_Advanced_Fees()

?>