<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_woocommerce_dynamic_pricing
        {
            
            function __construct()
                {
                    
                    add_action( 'init',                                 array( $this, 'on__init') );    
                    
                }
                        
            function on__init()
                {
                    
                    global $WooGC;
                    
                    if ( ! is_admin() || defined( 'DOING_AJAX' ) ) 
                        {
                            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_before_calculate_totals' , 'WC_Dynamic_Pricing_Counter', 'reset_counter');
                            include ( WOOGC_PATH . '/compatibility/woocommerce-dynamic-pricing/classes/class-wc-dynamic-pricing-counter.php');
                            $WooGC_WC_Dynamic_Pricing_Counter =   $WooGC->functions->createInstanceWithoutConstructor( 'WooGC_WC_Dynamic_Pricing_Counter' );
                            add_action( 'woocommerce_before_calculate_totals', array( $WooGC_WC_Dynamic_Pricing_Counter, 'reset_counter' ), 80, 1 );
                            
                            
                            /*
                            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_before_calculate_totals' , 'WC_Dynamic_Pricing', 'reset_counter');
                            include ( WOOGC_PATH . '/compatibility/woocommerce-dynamic-pricing/classes/class-woocommerce-dynamic-pricing.php');
                            $WooGC_WC_Dynamic_Pricing =   $WooGC->functions->createInstanceWithoutConstructor( 'WooGC_WC_Dynamic_Pricing' );
                            add_action( 'woocommerce_before_calculate_totals', array( $WooGC_WC_Dynamic_Pricing, 'on_calculate_totals' ), 98, 1 );
                            */
                            
                            add_filter( 'wc_dynamic_pricing_load_modules', array( $this, 'wc_dynamic_pricing_load_modules' ) ); 
                        }
                    
                }
                
            function wc_dynamic_pricing_load_modules( $modules )
                {
                    
                    //extend few modules
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-dynamic-pricing/classes/class-wc-dynamic-pricing-advanced-product.php');
                    
                    if(isset($modules['advanced_product']))
                        $modules['advanced_product']    =   new WooGC_WC_Dynamic_Pricing_Advanced_Product( 'advanced_product' );
                        
                    return $modules;
                    
                } 
                
                                                                   
        }


    new WooGC_woocommerce_dynamic_pricing();    
    
?>