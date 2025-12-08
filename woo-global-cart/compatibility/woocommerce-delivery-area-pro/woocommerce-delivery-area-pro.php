<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Delivery Area Pro
    * Since:                2.1.6
    */
    
    class WooGC_compatibility_woo_delivery_area_pro
        {
                
            public function __construct( ) 
                {
                    
                    add_filter( 'woocommerce_cart_item_class',  array( $this, 'wdap_woocommerce_cart_item_class_start' ),    9, 3 );
                    add_filter( 'woocommerce_cart_item_class',  array( $this, 'wdap_woocommerce_cart_item_class_end' ),      11, 3 );
                    
                    add_filter( 'init',                         array( $this, 'on_init' ) );
                      
                }
                
                
            
            function on_init()
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_after_checkout_billing_form', 'WDAP_Delivery_Area', 'wdap_custom_checkout_field' );
                   
                    add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'wdap_custom_checkout_field' ) );
                    
                }
                
            
            /**
            * Switch to product blog
            * 
            * @param mixed $cart_item
            * @param mixed $cart_item1
            * @param mixed $cart_item_key
            */
            function wdap_woocommerce_cart_item_class_start( $cart_item, $cart_item1, $cart_item_key )
                {
                    if ( isset ( $cart_item1['blog_id'] ) )
                        switch_to_blog( $cart_item1['blog_id'] );   
                    
                    return $cart_item;
                    
                }
                
                
            /**
            * Switch to product blog
            * 
            * @param mixed $cart_item
            * @param mixed $cart_item1
            * @param mixed $cart_item_key
            */
            function wdap_woocommerce_cart_item_class_end( $cart_item, $cart_item1, $cart_item_key )
                {
                    if ( isset ( $cart_item1['blog_id'] ) )
                        restore_current_blog();   
                        
                    return $cart_item;
                    
                }
                
                
            
            /**
             * Create Custom field on checkout page.
             */
            function wdap_custom_checkout_field( $checkout ) 
                {

                    global $woocommerce;
                    $cartdata = WC()->cart->get_cart();
                    $ids = array();
                    $is_all_viratual = array();
                    
                    $instance = new Child_WDAP_Delivery_Area();
                    
                    foreach ( $cartdata as $key => $item ) {
                        
                        if ( isset ( $item['blog_id']  ))
                            switch_to_blog($item['blog_id']);
                        
                        if($item['data']->is_virtual() ) { 
                            $is_all_viratual[] = 'yes';
                            
                            if ( isset ( $item['blog_id']  ))
                                restore_current_blog();
                            
                            continue; 
                        }

                        if(!empty($item['variation_id'])){
                            $variation_id = $item['variation_id'];
                            $v = new WC_Product_Variation($variation_id);
                            if(!empty($v->get_parent_id())){
                                $ids[] = $v->get_parent_id();
                            $is_all_viratual[] = 'no';
                            }else{

                            $ids[] = $item['product_id'];
                            $is_all_viratual[] = 'no';


                            }
                        }else{
                            $ids[] = $item['product_id'];
                            $is_all_viratual[] = 'no';
                        }
                        
                        if ( isset ( $item['blog_id']  ))
                            restore_current_blog();
                    }

                    if(!empty($is_all_viratual) && !(in_array('no', $is_all_viratual))){
                        $instance->is_all_products_viratual = true;
                    }

                     echo '<div id="wdap_custom_checkout_field">';
                           $instance->wdap_zip_search_markup( $ids, '' );
                     echo '</div>';
                }
               
        }

        
    new WooGC_compatibility_woo_delivery_area_pro();

    
    class Child_WDAP_Delivery_Area extends WDAP_Delivery_Area 
        {

          public function __construct() {
            //no parent::__construct($arg) call here
          }
        }
    

?>