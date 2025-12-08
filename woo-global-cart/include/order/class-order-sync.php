<?php
    
    class WooGC_Order_Sync
        {
            var $WooGC_OrderSyncForBlogIDs      =   array();
            var $WooGC_SynchronizeToBlogIDs     =   array();
            
            var $API_consumer_key               =   '';
            var $API_consumer_secret            =   '';
            
            private $_previous_order_items      =   FALSE;
            private $_previous_order_data       =   FALSE;
            
            function __construct( )
                {
                    $options    =   WooGC_Functions::get_options();
                    
                    $this->WooGC_OrderSyncForBlogIDs        =   $this->get_order_sync_for_blogs_ids();
                    $this->WooGC_SynchronizeToBlogIDs[]     =   $options['order_synchronization_to_shop'];
                    
                    $this->API_consumer_key                 =   $options['order_synchronization_consumer_key'];
                    $this->API_consumer_secret              =   $options['order_synchronization_consumer_secret'];
                    
                    if ( empty ( $this->API_consumer_key ) ||   empty ( $this->API_consumer_secret ) )
                        return;
                    
                    add_action( 'woocommerce_new_order',                array ( $this, 'order_created_action' ),    9999, 2 );
                    
                    add_action( 'woocommerce_before_order_object_save', array ( $this, 'capture_order_before_save' ),   9999, 1 );
                    add_action( 'woocommerce_after_order_object_save',  array ( $this, 'capture_order_changes' ),       9999, 1 );
                    
                    add_action( 'woocommerce_before_save_order_items',  array ( $this, 'before_save_order_items' ), 9999, 2 );
                    add_action( 'woocommerce_saved_order_items',        array ( $this, 'saved_order_items' ), 9999, 2 );
                    
                    //!!! not triggering in ajx mode ( add a product to the order )
                    add_action( 'woocommerce_ajax_order_items_added',   array ( $this, 'order_items_added' ),   9999, 2 );
                    add_action( 'woocommerce_ajax_order_items_removed', array ( $this, 'order_items_removed' ), 9999, 4 );

                }
            
                
            function order_created_action( $order_id, $order )
                {
                    global $blog_id;
                    
                    //Allow synch only for the designated shop ids
                    if ( ! in_array ( $blog_id, $this->WooGC_OrderSyncForBlogIDs ) )
                        return;
                                
                    //$order =   new WC_Order ( $order_id );
                    
                    $order_data                         =   array();
                        
                    $order_data =   $order->get_data();
                    $unsets =   array(
                                        'id',
                                        'parent_id',
                                        'number',
                                        'total_tax',
                                        
                                        'meta_data',
                                        'line_items',
                                        'tax_lines',
                                        'shipping_lines',
                                        'fee_lines',
                                        'coupon_lines'
                                        );
                    foreach ( $unsets as $unset )
                        unset ( $order_data[ $unset ] );
                                                                    
                    foreach ( $order->get_items()                   as  $order_item_id  =>  $order_item_data )
                        {
                            $order_data["line_items"][]              =   array (
                                                                                "product_id"    => $order_item_data->get_product_id(),
                                                                                "variation_id"  => $order_item_data->get_variation_id(),
                                                                                "quantity"      => $order_item_data->get_quantity(),
                                                                                "subtotal"      => $order_item_data->get_subtotal(),
                                                                                "subtotal_tax"  => $order_item_data->get_subtotal_tax(),
                                                                                "total"         => $order_item_data->get_total(),
                                                                                "total_tax"     => $order_item_data->get_total_tax(),
                                                                                
                                                                                "blog_id"       => $order_item_data->get_meta( 'blog_id' ),
                                                                                );   
                        }
                        
                    foreach ( $order->get_items( 'shipping' )                   as  $order_item_id  =>  $order_item_data )
                        {
                            $order_data["shipping_lines"][]              =   array (
                                                                                "method_title"  =>  $order_item_data->get_name(),
                                                                                "total"         =>  $order_item_data->get_total()
                                                                                );   
                        }
                        
                        
                    //add meta data 
                    $order_data['meta_data']    =   array (
                                                            '_woogc_origin_shop'        =>  $blog_id,
                                                            '_woogc_origin_order_id'    =>  $order->get_ID(),
                                                            );
                    
                    $operation_type =   'new_order';
                    $woogc_synchronized_to  =   $this->submit_to_api( $order_data, $operation_type );
                        
                    $order->update_meta_data( '_woogc_synchronized_to', $woogc_synchronized_to );
                    //$order->save();
                        
                }
                
                
            function order_items_added( $added_items, $order )
                {
                    $new_line_items =   array();
                    
                    $this->update_order_items( $order->get_ID() );
 
                }
                
                
            function order_items_removed( $item_id, $order_item, $changed_stock, $order )
                {
                    $removed_line_items =   array();
                    
                    global $blog_id;
                    
                    if ( $order_item->get_type()    !== 'line_item' )
                        return;
                    
                    $removed_line_items[] =   array ( 
                                                    'product_id'    =>  $order_item->get_product_id(),
                                                    'variation_id'  =>  $order_item->get_variation_id(),
                                                    'blog_id'       =>  $blog_id
                                                    );
                    
                    $changes    =   array();    
                    $changes['_woogc_origin_order_id']  =   $order->get_ID();
                    $changes['_woogc_origin_shop']      =   $blog_id;
                    
                    $changes['removed_line_items']      =   $removed_line_items;
                    
                    $operation_type =   'order_update';
                    
                    $woogc_synchronized_to  =   $this->submit_to_api( $changes, $operation_type );       
                }
                
         
         
            private function submit_to_api( $order_data, $operation_type )
                {
                    // Combine the consumer key and secret
                    $auth = base64_encode( $this->API_consumer_key . ':' . $this->API_consumer_secret );

                    // Set up the request headers
                    $headers = array(
                        'Authorization' => 'Basic ' . $auth,
                        'Content-Type' => 'application/json'
                    );

                    // Prepare the data to send (empty object as example)
                    $body = json_encode( $order_data );

                    $woogc_synchronized_to  =   array();
                    
                    foreach ( $this->WooGC_SynchronizeToBlogIDs   as  $WooGC_SynchronizeToBlogID )
                        {
                            $api_url =  $this->get_shop_api_url( $WooGC_SynchronizeToBlogID, $operation_type );
                            
                            // Send the request
                            $response = wp_remote_post($api_url, array(
                                                                        'method'    => 'POST',
                                                                        'body'      => $body,
                                                                        'headers'   => $headers,
                                                                        'timeout'   => 10,
                                                                        
                                                                        'sslverify' => false
                                                                    ));

                            // Check for success or failure
                            if ( is_wp_error($response) )
                                error_log('Error: ' . $response->get_error_message());
                                else
                                {
                                    $response_body = (array)json_decode( wp_remote_retrieve_body( $response ) );
                                    
                                    if ( ! is_array ( $response_body ) ||   ! isset ( $response_body['order_id'] ) )
                                        continue;
                                    
                                    $woogc_synchronized_to[]    =   $WooGC_SynchronizeToBlogID;
                                }
                        }
                        
                    return $woogc_synchronized_to;      
                }
         
            
            private function get_shop_api_url( $WooGC_SynchronizeToBlogID, $operation_type )
                {
                    $blog_details   =   get_blog_details( $WooGC_SynchronizeToBlogID );
                    
                    switch ( $operation_type )
                        {
                            case 'new_order';
                                                $api_url =  trailingslashit ( $blog_details->siteurl )  .   'wp-json/custom-api/v1/create-order';
                                                break;
                            
                            case 'order_update';
                                                $api_url =  trailingslashit ( $blog_details->siteurl )  .   'wp-json/custom-api/v1/update-order';
                                                break;                
                        }
                    
                    return $api_url;
                }
                
                
            function capture_order_before_save( $order ) 
                {
                    if ( $order->get_id() < 1 )
                        {
                            $this->_previous_order_data =   FALSE;
                            return;
                        }    
                    
                    $this->_previous_order_data = $order->get_data();
                    
                    //adjust the meta data for later comparison
                    $this->_order_meta_to_array( $this->_previous_order_data );
                    
                }

            function capture_order_changes( $order ) {
                
                if ( $this->_previous_order_data ) {
                    // Get the current state of the order.
                    $current_order_data = $order->get_data();
                    $this->_order_meta_to_array( $current_order_data );

                    // Compare the two states to find changes.
                    $changes = $this->array_diff_assoc_recursive( $current_order_data, $this->_previous_order_data );

                    if ( empty ( $changes ) ||  ( count ( $changes )    === 1   &&  isset ( $changes['date_modified'] ) ) )
                        return;
                    
                    if ( ! empty( $changes ) ) 
                        {
                            global $blog_id;
                            
                            $changes['_woogc_origin_order_id'] =   $order->get_ID();
                            $changes['_woogc_origin_shop']  =   $blog_id;
                            
                            $operation_type =   'order_update';
                            $woogc_synchronized_to  =   $this->submit_to_api( $changes, $operation_type );
                        }
                }
            }


            private function array_diff_assoc_recursive( $array1, $array2 ) 
                {
                    $difference = [];

                    foreach ( $array1 as $key => $value ) 
                        {
                            if ( is_array( $value ) && isset( $array2[ $key ] ) && is_array( $array2[ $key ] ) ) 
                                {
                                    $new_diff = $this->array_diff_assoc_recursive( $value, $array2[ $key ] );
                                    if ( ! empty( $new_diff ) )
                                        $difference[ $key ] = $new_diff;
                                } 
                                elseif ( ! array_key_exists( $key, $array2 ) || $value !== $array2[ $key ] ) 
                                    $difference[ $key ] = $value;
                        }

                    return $difference;
                }
                
                
            private function _order_meta_to_array( &$order )
                {
                    if ( isset ( $order['meta_data'] )    &&  count ( $order['meta_data'] )   >   0 )
                        {
                            $previous_meta_data =   array();
                            
                            foreach ( $order['meta_data'] as  $meta_data )
                                {
                                    $data   =   $meta_data->get_data();
                                    $previous_meta_data[]   =   array (
                                                                        'key'       =>  $data['key'],
                                                                        'value'     =>  $data['value'],
                                                                        );
                                }
                                
                            $order['meta_data']    =   $previous_meta_data;
                        }   
                }
        
            
            function before_save_order_items( $order_id, $items )
                {
                    $order =   new WC_Order ( $order_id );
                    
                    $order_items                         =   array();
                                                                     
                    foreach ( $order->get_items()                   as  $order_item_id  =>  $order_item_data )
                        {
                            $order_items[]              =   array (
                                                                                        "product_id"    => $order_item_data->get_product_id(),
                                                                                        "variation_id"  => $order_item_data->get_variation_id(),
                                                                                        "quantity"      => $order_item_data->get_quantity(),
                                                                                        "subtotal"      => $order_item_data->get_subtotal(),
                                                                                        "subtotal_tax"  => $order_item_data->get_subtotal_tax(),
                                                                                        "total"         => $order_item_data->get_total(),
                                                                                        "total_tax"     => $order_item_data->get_total_tax(),
                                                                                        
                                                                                        "blog_id"       => $order_item_data->get_meta( 'blog_id' ),
                                                                                        );   
                        }
                        
                    $this->_previous_order_items    =   $order_items;
                }
            
            function saved_order_items( $order_id, $items )
                {
                    if ( $this->_previous_order_items   === FALSE )   
                        return;
                    
                    $order_items    =   array();
                    
                    $order =   new WC_Order ( $order_id );
                    foreach ( $order->get_items()                   as  $order_item_id  =>  $order_item_data )
                        {
                            $order_items[]              =   array (
                                                                                        "product_id"    => $order_item_data->get_product_id(),
                                                                                        "variation_id"  => $order_item_data->get_variation_id(),
                                                                                        "quantity"      => $order_item_data->get_quantity(),
                                                                                        "subtotal"      => $order_item_data->get_subtotal(),
                                                                                        "subtotal_tax"  => $order_item_data->get_subtotal_tax(),
                                                                                        "total"         => $order_item_data->get_total(),
                                                                                        "total_tax"     => $order_item_data->get_total_tax(),
                                                                                        
                                                                                        "blog_id"       => $order_item_data->get_meta( 'blog_id' ),
                                                                                        );   
                        }
                    
                    if ( json_encode( $this->_previous_order_items )    === json_encode ( $order_items ) )
                        return;    
                    
                    $this->_previous_order_items   = FALSE;
                    
                    $this->update_order_items( $order_id );
                    
                    
                }
                
                
            private function update_order_items( $order_id )
                {
                    $order_items    =   array();
                    
                    $order =   new WC_Order ( $order_id );
                    foreach ( $order->get_items()                   as  $order_item_id  =>  $order_item_data )
                        {
                            $order_items[]              =   array (
                                                                                        "product_id"    => $order_item_data->get_product_id(),
                                                                                        "variation_id"  => $order_item_data->get_variation_id(),
                                                                                        "quantity"      => $order_item_data->get_quantity(),
                                                                                        "subtotal"      => $order_item_data->get_subtotal(),
                                                                                        "subtotal_tax"  => $order_item_data->get_subtotal_tax(),
                                                                                        "total"         => $order_item_data->get_total(),
                                                                                        "total_tax"     => $order_item_data->get_total_tax(),
                                                                                        
                                                                                        "blog_id"       => $order_item_data->get_meta( 'blog_id' ),
                                                                                        );   
                        }
                       
                    global $blog_id;
                    
                    $changes    =   array();        
                    $changes['_woogc_origin_order_id']  =   $order->get_ID();
                    $changes['_woogc_origin_shop']      =   $blog_id;
                    $changes['update_line_items']       =   $order_items;
                    
                    $operation_type =   'order_update';
                    $woogc_synchronized_to  =   $this->submit_to_api( $changes, $operation_type );   
                }
                
            
            /**
            * Return an array with the Blogs IDs where the orders will be synchronyzed to WooGC_SynchronizeToBlogIDs
            *     
            */
            public static function get_order_sync_for_blogs_ids()
                {
                    $WooGC_OrderSyncForBlogIDs          =   array();
                    
                    $options    =   WooGC_Functions::get_options();
                    
                    $options_OrderSyncForBlogIDs        =   $options['order_synchronization_for_shops'];
                    
                    if  ( ! is_array ( $options_OrderSyncForBlogIDs ) )
                        return $WooGC_OrderSyncForBlogIDs;
                        
                    foreach ( $options_OrderSyncForBlogIDs    as  $blog_id    =>  $status )
                        {
                            if ( $status !==    'yes' )
                                continue;
                                
                            $WooGC_OrderSyncForBlogIDs[]    =   $blog_id;
                        }
                    
                    return $WooGC_OrderSyncForBlogIDs;
                }
            
        }
        
    new WooGC_Order_Sync( );
    


    

    
       
    