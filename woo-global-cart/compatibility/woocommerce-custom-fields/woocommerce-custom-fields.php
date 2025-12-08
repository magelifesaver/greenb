<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for Plugin Name: WooCommerce Custom Fields
    * Compatibility checked on: 2.3.4 
    */

    class WooGC_woocf
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'init', array ( $this, 'on_init') );
                      
                }
                
            function on_init()
                {
                    global $WooGC;
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_hidden_order_itemmeta', 'WCCF_WC_Order_Item', 'hidden_order_item_meta' );
                        
                    add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hidden_order_item_meta'));
                }
                
                
            /**
             * Hide order item meta (raw values and meta for internal use)
             *
             * @access public
             * @param array $hidden_keys
             * @return array
             */
            function hidden_order_item_meta( $hidden_keys )
                {
                    // Check if order id can be determined
                    if ($order_id = RightPress_Help::get_wc_order_id()) {

                        $hidden_order_item_meta_key_cache[$order_id]    =   array();
    
                        // Load order object
                        global $blog_id;
                        
                        $current_blog   =   $blog_id;
                        
                        restore_current_blog();
                        $order = wc_get_order($order_id);
                        
                        if ( $order )
                        {
                            // Iterate over order items
                        foreach ($order->get_items() as $order_item_key => $order_item) {
                            do_action( 'woocommerce/cart_loop/start', $order_item );
                            // Iterate over order item meta
                            foreach ($order_item->get_meta_data() as $meta) {

                                // Check if this is our internal meta key; also match data stored with 1.x versions of this extension
                                if (preg_match('/^_wccf_/i', $meta->key) || preg_match('/^wccf_/i', $meta->key)) {

                                    // Check if it already exists in hidden keys array
                                    if (!in_array($meta->key, $hidden_order_item_meta_key_cache[$order_id])) {

                                        // Add key to hidden keys array
                                        $hidden_order_item_meta_key_cache[$order_id][] = $meta->key;
                                    }
                                }
                            }
                            do_action( 'woocommerce/cart_loop/end', $order_item );
                        }
                        }
            
                        switch_to_blog( $current_blog );
                        
                        // Add our hidden keys to the main hidden keys array
                        $hidden_keys = array_merge($hidden_keys, $hidden_order_item_meta_key_cache[$order_id]);
                    }

                    return $hidden_keys;
                }         
            
        }

        
    new WooGC_woocf();

?>