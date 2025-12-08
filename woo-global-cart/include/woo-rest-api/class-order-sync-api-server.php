<?php
        
        class WooGC_Order_Sync_API
            {
                
                function __construct()
                    {
                        add_action('rest_api_init', array ( $this, 'create_order_endpoint' ) );
                    }
                
                function create_order_endpoint() 
                    {
                        register_rest_route('custom-api/v1', '/create-order', array(
                            'methods' => 'POST',
                            'callback' => array( $this, 'create_order'),
                            'permission_callback' => '__return_true',
                        ));
                        register_rest_route('custom-api/v1', '/update-order', array(
                            'methods' => 'POST',
                            'callback' => array( $this, 'update_order'),
                            'permission_callback' => '__return_true',
                        ));
                    }
                

                function create_order( WP_REST_Request $request ) 
                    { 
                        $is_valid_request   =   $this->request_check_if_valid( $request );
                        if ( $is_valid_request  !== TRUE )
                            return $is_valid_request;

                        // Proceed to create the order if the credentials are valid
                        $data = $request->get_json_params();
                        
                        if (empty($data)) {
                            return new WP_Error('no_data', 'No data provided', array('status' => 400));
                        }

                        $order = wc_create_order();

                        $ignore =   array (
                                            'line_items',
                                            'shipping_lines'
                                            );
                        foreach ( $data as $key => $data_item ) 
                            {
                                if ( is_array( $data_item ) ) 
                                    {
                                        foreach ( $data_item as $sub_key => $sub_value ) 
                                            {
                                                if ( method_exists( $order, 'set_' . $key . '_' . $sub_key ) )
                                                    $order->{'set_' . $key . '_' . $sub_key}( $sub_value );
                                            }
                                    } 
                                    elseif ( method_exists( $order, 'set_' . $key ) )
                                        $order->{'set_' . $key}( $data_item );
                            }
                        
                        
                        if (isset($data['line_items'])) 
                            {
                                foreach ($data['line_items'] as $item ) 
                                    {           
                                        switch_to_blog( $item['blog_id'] );
                                        $product    =   wc_get_product ( $item['variation_id'] ? $item['variation_id']   :   $item['product_id'] );
                                        
                                        if ( ! $product )
                                            {
                                                restore_current_blog();
                                                continue;        
                                            }
                                        
                                        restore_current_blog();
                                        
                                        $line_item = new WooGC_WC_Order_Item_Product();                            
                                        $line_item->update_meta_data( 'blog_id', $item['blog_id'] );
                                        
                                        $line_item->set_product( $product );
                                        $line_item->set_quantity( $item['quantity'] ? $item['quantity']   :   0 );
                                        
                                        $line_item->set_subtotal( $item['subtotal'] ? $item['subtotal']   :   0 );
                                        $line_item->set_subtotal_tax( $item['subtotal_tax'] ? $item['subtotal_tax']   :   0 );
                                        
                                        $line_item->set_total( $item['total'] ? $item['total']   :   0 );
                                        $line_item->set_total_tax( $item['total_tax'] ? $item['total_tax']   :   0 );
                                        
                                        $order->add_item ( $line_item );
                                    }
                            }
                        
                        if (isset($data['shipping_lines'])) 
                            {
                                foreach ($data['shipping_lines'] as $item ) 
                                    {           
                                        $shipping_item = new WC_Order_Item_Shipping();

                                        $shipping_item->set_method_title( $item['method_title'] );
                                        $shipping_item->set_total( $item['total'] );
                                        $order->add_item( $shipping_item );
                                    }
                            }
      
                        
                        if ( isset($data['meta_data']) ) {
                            foreach ( $data['meta_data'] as $meta_key   =>  $meta_value )
                                {
                                    $order->update_meta_data( $meta_key, $meta_value );   
                                }
                        }

                        $order->calculate_totals();
                        
                        do_action( 'woogc/api/order-sync/order_created', $order->get_id(), $data );

                        return [
                            'order_id' => $order->get_id(),
                            'status' => 'Order created successfully',
                        ];
                    } 
                    
                    
                function update_order( WP_REST_Request $request ) 
                    { 
                        $is_valid_request   =   $this->request_check_if_valid( $request );
                        if ( $is_valid_request  !== TRUE )
                            return $is_valid_request;                

                        // Proceed to create the order if the credentials are valid
                        $data = $request->get_json_params();
                        
                        if (empty($data)) {
                            return new WP_Error('no_data', 'No data provided', array('status' => 400));
                        }
                        
                        $order  =   FALSE;
                        
                        //retrieve the order
                        $args = array(
                                           'post_status'    =>  'any',
                                           
                                           'meta_query'     => array(
                                                                       'relation' => 'AND',
                                                                       array(
                                                                           'key'        => '_woogc_origin_order_id',
                                                                           'value'      => (int)$data['_woogc_origin_order_id'],
                                                                           'compare'    => '=',
                                                                       ),
                                                                       array(
                                                                           'key'        => '_woogc_origin_shop',
                                                                           'value'      => (int)$data['_woogc_origin_shop'],
                                                                           'compare'    => '=',
                                                                       )
                                                                   )
                                        );
                        
                        if ( WooGC_Functions::is_HPOS_active() )
                            {
                                $wc_query   =  new WC_Order_Query( $args );
                                $orders     =   $wc_query->get_orders();
                                
                                if ( $orders < 1 )
                                    return [
                                                'status' => 'Synchronised order not found',
                                            ];
                                
                                reset ( $orders );
                                $order = current ( $orders );       
                            }
                            else
                            {
                                $args['post_type']  =   'shop_order';
                                
                                $wc_query   =  new WP_Query( $args );
                                if ( $wc_query->post_count  <   1 )
                                    return [
                                                'status' => 'Synchronised order not found',
                                            ];
                                
                                $founds =   $wc_query->get_posts();
                                
                                reset ( $founds );
                                $current_post_data = current ( $founds );
                                
                                $order  =   new WC_Order ( $current_post_data->ID  );       
                            }
                            
                        if ( ! $order )
                            return [
                                                'status' => 'Synchronised order not found',
                                            ];
                                                
                        
                        $ignore =   array (
                                            'line_items',
                                            'new_line_items',
                                            'removed_line_items',
                                            'update_line_items',
                                            
                                            'shipping_lines'
                                            );
                                            
                        foreach ( $data as $key => $data_item ) 
                            {
                                if ( is_array( $data_item ) ) 
                                    {
                                        foreach ( $data_item as $sub_key => $sub_value ) 
                                            {
                                                if ( is_string ( $key ) &&  method_exists( $order, 'set_' . $key . '_' . $sub_key ) )
                                                    {
                                                        if ( $key   === 'total_tax' )
                                                            $key    =   'cart_tax';
                                                        $order->{'set_' . $key . '_' . $sub_key}( $sub_value );
                                                    }
                                            }
                                    } 
                                    elseif ( is_string ( $key ) &&  method_exists( $order, 'set_' . $key ) )
                                        {
                                            if ( $key   === 'total_tax' )
                                                $key    =   'cart_tax';
                                            $order->{'set_' . $key}( $data_item );
                                        }
                            }
                        

                        if (isset($data['line_items'])) {
                            foreach ($data['line_items'] as $item ) 
                                {           
                                    switch_to_blog( $item['blog_id'] );
                                    $product    =   wc_get_product ( $item['variation_id'] ? $item['variation_id']   :   $item['product_id'] );
                                    
                                    if ( ! $product )
                                        {
                                            restore_current_blog();
                                            continue;        
                                        }
                                    
                                    restore_current_blog();
                                    
                                    $line_item = new WooGC_WC_Order_Item_Product();                            
                                    $line_item->update_meta_data( 'blog_id', $item['blog_id'] );
                                    
                                    $line_item->set_product( $product );
                                    $line_item->set_quantity( $item['quantity'] ? $item['quantity']   :   0 );
                                    
                                    $line_item->set_subtotal( $item['subtotal'] ? $item['subtotal']   :   0 );
                                    $line_item->set_subtotal_tax( $item['subtotal_tax'] ? $item['subtotal_tax']   :   0 );
                                    
                                    $line_item->set_total( $item['total'] ? $item['total']   :   0 );
                                    $line_item->set_total_tax( $item['total_tax'] ? $item['total_tax']   :   0 );
                                    
                                    $order->add_item ( $line_item );
                                }
                        }
                        
                        if (isset($data['new_line_items'])) {
                            foreach ($data['new_line_items'] as $item ) 
                                {           
                                    switch_to_blog( $item['blog_id'] );
                                    $product    =   wc_get_product ( $item['variation_id'] ? $item['variation_id']   :   $item['product_id'] );
                                    
                                    if ( ! $product )
                                        {
                                            restore_current_blog();
                                            continue;        
                                        }
                                    
                                    restore_current_blog();
                                    
                                    $line_item = new WooGC_WC_Order_Item_Product();                            
                                    $line_item->update_meta_data( 'blog_id', $item['blog_id'] );
                                    
                                    $line_item->set_product( $product );
                                    $line_item->set_quantity( $item['quantity'] ? $item['quantity']   :   0 );
                                    
                                    $line_item->set_subtotal( $item['subtotal'] ? $item['subtotal']   :   0 );
                                    $line_item->set_subtotal_tax( $item['subtotal_tax'] ? $item['subtotal_tax']   :   0 );
                                    
                                    $line_item->set_total( $item['total'] ? $item['total']   :   0 );
                                    $line_item->set_total_tax( $item['total_tax'] ? $item['total_tax']   :   0 );
                                    
                                    $order->add_item ( $line_item );
                                }
                        }
                        
                        if (isset($data['removed_line_items'])) {
                            foreach ($data['removed_line_items'] as $item ) 
                                {           
                                    $current_order_items    =   $order->get_items();
                                    foreach ( $current_order_items  as  $item_key   =>  $item_data ) 
                                        {
                                            if ( $item_data->get_product_id()   ==  $item['product_id']  &&  $item_data->get_variation_id()  ==  $item['variation_id']    )
                                                $order->remove_item( $item_key );
                                        }
                                }
                        }
                        
                        if (isset($data['update_line_items'])) {
                            foreach ( $order->get_items() as $item_id => $item )
                                $order->remove_item( $item_id );
   
                            foreach ($data['update_line_items'] as $item ) 
                                {           
                                    switch_to_blog( $item['blog_id'] );
                                    $product    =   wc_get_product ( $item['variation_id'] ? $item['variation_id']   :   $item['product_id'] );
                                    
                                    if ( ! $product )
                                        {
                                            restore_current_blog();
                                            continue;        
                                        }
                                    
                                    restore_current_blog();
                                    
                                    $line_item = new WooGC_WC_Order_Item_Product();                            
                                    $line_item->update_meta_data( 'blog_id', $item['blog_id'] );
                                    
                                    $line_item->set_product( $product );
                                    $line_item->set_quantity( $item['quantity'] ? $item['quantity']   :   0 );
                                    
                                    $line_item->set_subtotal( $item['subtotal'] ? $item['subtotal']   :   0 );
                                    $line_item->set_subtotal_tax( $item['subtotal_tax'] ? $item['subtotal_tax']   :   0 );
                                    
                                    $line_item->set_total( $item['total'] ? $item['total']   :   0 );
                                    $line_item->set_total_tax( $item['total_tax'] ? $item['total_tax']   :   0 );
                                    
                                    $order->add_item ( $line_item );
                                }
                        }
                        
                        if (isset($data['shipping_lines'])) {
                            foreach ($data['shipping_lines'] as $item ) 
                                {           
                                    $shipping_item = new WC_Order_Item_Shipping();

                                    $shipping_item->set_method_title( $item['method_title'] );
                                    $shipping_item->set_total( $item['total'] );
                                    $order->add_item( $shipping_item );
                                }
                        }

                        if (isset($data['billing'])) {
                            $order->set_address($data['billing'], 'billing');
                        }
                        if (isset($data['shipping'])) {
                            $order->set_address($data['shipping'], 'shipping');
                        }

                        if (isset($data['payment_method'])) {
                            $order->set_payment_method($data['payment_method']);
                        }

                        $order->calculate_totals();
                        
                        do_action( 'woogc/api/order-sync/order_updated', $order->get_id(), $data );

                        return [
                            'order_id' => $order->get_id(),
                            'status' => 'Order updated successfully'
                        ];
                    }
                    
                    
                
                function request_check_if_valid( $request )
                    {
                        // Get API credentials from the Authorization header
                        $auth_header = $request->get_header('authorization');

                        if (!$auth_header) {
                            return new WP_Error('no_auth', 'Authorization header is missing.', array('status' => 401));
                        }

                        // Decode the base64-encoded credentials
                        if (strpos($auth_header, 'Basic ') === 0) {
                            $auth_header = substr($auth_header, 6); // Remove "Basic " prefix
                        }

                        $decoded_credentials = base64_decode($auth_header);
                        list($consumer_key, $consumer_secret) = explode(':', $decoded_credentials);

                        // Validate credentials
                        $user_id = $this->wc_api_check_credentials($consumer_key, $consumer_secret);

                        if (!$user_id) {
                            return new WP_Error('invalid_credentials', 'Invalid API credentials.', array('status' => 403));
                        }
                        
                        return TRUE;   
                    }
                

                /**
                * validate WooCommerce API credentials
                * 
                * @param mixed $consumer_key
                * @param mixed $consumer_secret
                */
                function wc_api_check_credentials( $consumer_key, $consumer_secret) 
                    {
                        global $wpdb;

                        // Query for the consumer key and secret in the WooCommerce database table
                        $query = $wpdb->prepare(
                            "SELECT user_id 
                             FROM {$wpdb->prefix}woocommerce_api_keys 
                             WHERE consumer_key = %s 
                               AND consumer_secret = %s 
                               AND permissions IN ('write', 'read_write')",
                            wc_api_hash ( $consumer_key ),
                            $consumer_secret
                        );

                        $user_id = $wpdb->get_var($query);

                        // Return user ID if valid, or false otherwise
                        return $user_id ?: false;
                    }
            
            }
            
        new WooGC_Order_Sync_API();    
