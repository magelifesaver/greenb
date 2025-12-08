<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Orders 
        {
            
            var $output =   '';
            
            function __construct()
                {
                    
                    add_filter('woocommerce_before_order_item_line_item_html',                      array( $this, 'woocommerce_before_order_item_line_item_html'),  1, 3 );
                    add_filter('woocommerce_order_item_line_item_html',                             array( $this, 'woocommerce_order_item_item_line_item_html'),  1, 3 );
                    
                    
                    add_action('woocommerce_checkout_create_order_line_item_object',                array($this, 'woocommerce_checkout_create_order_line_item_object'), 999,  4); 
                    
                    add_filter('woocommerce_order_item_get_formatted_meta_data',                    array($this, 'woocommerce_order_item_get_formatted_meta_data'), 999,  2);
                                        
                }
                
                
                     
            
            
            function woocommerce_checkout_create_order_line_item_object($item, $cart_item_key, $values, $order)
                {
                    if( !   isset($values['blog_id'])    )
                        return $item;
       
                    $item->set_props( array(
                                                'blog_id'     => $values['blog_id']
                                            ) );
       
                    //add the fields
                    $item->add_meta_data('blog_id', $values['blog_id']);    
                    
                    return $item;
                    
                }
         
        
            function woocommerce_before_order_item_line_item_html( $item_id, $item, $order )
                {
                    if( !isset($item['blog_id']) ) 
                        return;
                    
                    switch_to_blog( $item['blog_id'] );
                    
                }
                
                
            function woocommerce_order_item_item_line_item_html( $item_id, $item, $order )
                {
                    if( !isset($item['blog_id']) ) 
                        return;
                        
                    restore_current_blog();    
                    
                }
                
            
                
            function on_shutdown__clean_output()
                {
                    ob_clean();
                    
                    echo $this->output;    
                    
                }
                
                
            /**
            * Filter out the blog_id meta to not show on front side
            * 
            * @param mixed $formatted_meta
            * @param mixed $object
            */
            function woocommerce_order_item_get_formatted_meta_data ( $formatted_meta, $object )
                {
                    
                    if( !is_array($formatted_meta)  ||  count($formatted_meta)  <   1   )
                        return $formatted_meta;
                        
                    foreach($formatted_meta as  $key    =>  $data)    
                        {
                            
                            if( $data->key   ==  'blog_id' )
                                unset($formatted_meta[ $key ]);
                            
                        }
                       
                    
                    return $formatted_meta;
                       
                }
                
                
        }

    new WooGC_Orders();

?>