<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name       :   YITH WooCommerce Composite Products
    * Since             :   2.2.1
    */

    class WooGC_yith_woocommerce_compopsite_products
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'woogc/get_cart_from_session/cart_item/values', array ( $this, 'get_cart_from_session_cart_item__values'), 999, 3 );
                      
                }
                
            function get_cart_from_session_cart_item__values( $values, $key, $cart )
                {
                    if ( ! isset ( $values['yith_wcp_child_component_data'] )   ||  ! isset ( $values['yith_wcp_child_component_data']['yith_wcp_component_parent_object'] ) )
                        return $values;
                        
                    $yith_wcp_component_key     =   $values['yith_wcp_child_component_data']['yith_wcp_component_key'];
                    $yith_wcp_cart_parent_key   =   $values['yith_wcp_child_component_data']['yith_wcp_cart_parent_key'];
                    
                    $cart_parent_component_data =   $cart[ $yith_wcp_cart_parent_key ];
                    $parent_product_id =   $cart_parent_component_data['variation_id'] > 0 ?     $cart_parent_component_data['variation_id']   :   $cart_parent_component_data['product_id'];
                    
                    switch_to_blog( $values['blog_id'] );
                    
                    $values['yith_wcp_child_component_data']['yith_wcp_component_parent_object']    =   new WC_Product_Yith_Composite ( $parent_product_id ); 
                    
                    restore_current_blog();
                    
                    return $values;
                }         
            
        }

        
    new WooGC_yith_woocommerce_compopsite_products();

?>