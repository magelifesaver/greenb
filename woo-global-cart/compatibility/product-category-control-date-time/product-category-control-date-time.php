<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:     Preparation time Master PRO
    * Since:         1.1.6
    */

    class WooGC_WooCommerce_pccdt
        {
           
            function __construct() 
                {
                    
                    add_action ( 'wp_loaded', array( $this, 'init') , 100 );
                                  
                }
                
                
            function init()
                {
                    
                    global $WooGC;

                    $WooGC->functions->remove_anonymous_object_filter ( 'pisol_dtt_time_slot_filter' ,  'pisol_pccdt_available_time_range', 'slotFilter');
                    $WooGC->functions->remove_anonymous_object_filter ( 'pisol_dtt_time_range_filter' , 'pisol_pccdt_available_time_range', 'slotFilter');
                           
                    add_filter('pisol_dtt_time_slot_filter',        array($this, 'filterSlots'),1000,2);
                    add_filter('pisol_dtt_time_range_filter',       array($this, 'filterSlots'),1000,2);
                    
                }
                
                
            function filterSlots( $slots, $date )
                {
                    
                    if(isset(WC()->cart))
                        {
                            foreach( WC()->cart->get_cart() as $cart_item ) 
                                {
                                    switch_to_blog($cart_item['blog_id']);
                                    
                                    $product_id = $cart_item['product_id'];
                                    if(pisol_pccdt_product::productHasTimeRange($product_id, $date ))
                                        {
                                           $start_time = pisol_pccdt_product::productStartTime($product_id, $date );
                                           $end_time = pisol_pccdt_product::productEndTime($product_id, $date );
                                           if(!empty($start_time) && !empty( $end_time ))
                                               {
                                                   pisol_pccdt_available_time_range::removeSlots($slots, $start_time, $end_time);
                                               }
                                        }
                                        
                                    restore_current_blog();
                                }
                        }

                    return array_values($slots);
                }
            
        }

    new WooGC_WooCommerce_pccdt();

?>