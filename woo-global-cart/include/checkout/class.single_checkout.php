<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Cart_Single_Checkout
        {
            
            var $WooGC;
            
            /**
            * Constructor
            * 
            */
            function __construct()
                {
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                       
                    $options    =   $this->WooGC->functions->get_options();
                     
                    if( $options['cart_checkout_split_orders']  ==  'yes' )
                        {
                            if ( ! defined( 'WOOGC_SINGLE_CHECKOUT_USE_SPLIT_EACH_ITEM_SEPARATE_ORDER' ) )
                                define ( 'WOOGC_SINGLE_CHECKOUT_USE_SPLIT_EACH_ITEM_SEPARATE_ORDER', FALSE );
                            
                            add_action ( 'woocommerce_checkout_order_created' ,                 array ( $this, 'custom_woocommerce_checkout_order_created' ) );
                            add_action ( 'woocommerce_store_api_checkout_order_processed' ,     array ( $this, 'custom_woocommerce_checkout_order_created' ) );
                            
                            add_action ( 'woocommerce_order_status_changed',                     array ( $this, 'custom_woocommerce_order_status_changed' ) ,    99, 3);
                                                
                            add_filter ( 'woocommerce_order_data_store_cpt_get_orders_query',   array ( $this, 'custom_woocommerce_order_data_store_cpt_get_orders_query' ), 99, 3 );
                            
                            //allow emails filtering
                            add_filter('woocommerce_email_enabled_new_order',                   array ( $this, 'woocommerce_email_enabled' ), 99, 3 );
                            add_filter('woocommerce_email_enabled_customer_on_hold_order',      array ( $this, 'woocommerce_email_enabled' ), 99, 3 );
                            add_filter('woocommerce_email_enabled_customer_completed_order',    array ( $this, 'woocommerce_email_enabled' ), 99, 3 );    
                        }
                    
                    
                    add_filter( 'woocommerce_admin_order_buyer_name',                   array ( $this, 'custom_woocommerce_admin_order_buyer_name'),   99, 2 );    
                    add_filter( 'woocommerce_admin_order_totals_after_shipping',        array ( $this, 'woocommerce_admin_order_totals_after_shipping'),   99, 1 ); 
                        
                }
                      
            
            function custom_woocommerce_checkout_order_created ( $order ) 
                {
                    global $blog_id;
                    
                    //allow date ordering to apply
                    sleep (2);
                    
                    //re-create the order objets to entusre also the changes applied after the initial order creation are included
                    $main_order     =   new wc_order ( $order->get_ID() ); 
                    
                    $default_order  =   clone $main_order;
                    
                    $order_blog_id  =   $blog_id;
                    
                    $order_items    =   $default_order->get_items();
                    $order_status   =   $default_order->get_status();
                    
                    $items_by_shop  =   array();
                    
                    if ( count ( $order_items ) < 1 )
                        return;
                        
                    //mark the order as being splitted
                    $order->update_meta_data( 'order_splitted', 'true' );
                    $order->save();
                        
                    foreach ( $order_items as $order_item )
                        {
                            $_blog_id   =   $order_item->get_meta('blog_id');
                                    
                            $items_by_shop[ $_blog_id ][]   =   $order_item;    
                            
                        }
                        
                    //check if the are only items from the check-out site
                    if ( count ( $items_by_shop ) === 1 &&  isset ( $items_by_shop[$blog_id] ))
                        return;
                    
                    $blog_currency  =   get_woocommerce_currency();
                    $woocommerce_prices_include_tax =   get_option( 'woocommerce_prices_include_tax' );    
                    
                    //Create split orders for the shops in $items_by_shop
                    foreach  ( $items_by_shop   as  $i_blog_id    =>  $shop_order_items )
                        {
                            //deprecated filter
                            if ( ! apply_filters( 'woogc/single_checkout/split_order/blog_id', TRUE, $i_blog_id, $order ) )
                                continue;
                            
                            if ( ! apply_filters( 'woogc/checkout/single/split/blog_id', TRUE, $i_blog_id, $order ) )
                                continue;
                                
                            $args   =   array ( 
                                                            'default_order'     =>  $default_order,
                                                            'order_blog_id'     =>  $order_blog_id,
                                                            'order_status'      =>  $order_status,
                                                            
                                                            'blog_currency'     =>  $blog_currency,
                                                            'woocommerce_prices_include_tax'    =>  $woocommerce_prices_include_tax,
                                                            
                                                            'i_blog_id'         =>  $i_blog_id
                                                                );
                            
                            if ( WOOGC_SINGLE_CHECKOUT_USE_SPLIT_EACH_ITEM_SEPARATE_ORDER === TRUE )
                                    {
                                        foreach ( $shop_order_items     as  $shop_order_item )
                                            {
                                                $args['shop_order_items']   =   array( $shop_order_item );    
                                                $this->_custom_create_order_and_add_items ( $args );        
                                            }                                
                                    }
                                else
                                    {
                                        $args['shop_order_items']   =   $shop_order_items;
                                        $this->_custom_create_order_and_add_items ( $args );
                                    }
                            
                          
                            
                        }
                    
                }
        
        
            function _custom_create_order_and_add_items ( $args )
                {
                    extract( $args );    
                    
                    $default_order_data = $default_order->get_data();
                    $default_order_ID   =   $default_order_data['id'];
                    unset ( $default_order_data['id'] );
                    
                    switch_to_blog( $i_blog_id );
                            
                    $new_order  =   new WC_Order();
                    
                    $fields_prefix = array(
                        'shipping' => true,
                        'billing'  => true,
                    );

                    $shipping_fields = array(
                        'shipping_method' => true,
                        'shipping_total'  => true,
                        'shipping_tax'    => true,
                    );
                    
                    restore_current_blog();
                    $new_order->set_parent_id( $default_order->get_id() );
                    switch_to_blog( $i_blog_id );
                    
                    $new_order->set_status( $order_status );
                    
                    $new_order->update_meta_data( 'checkout_blog_id', $order_blog_id );
                    $new_order->update_meta_data( 'checkout_order_id', $default_order->get_id() );
                    
                    foreach ( $default_order_data as $key => $value ) 
                        {
                            if ( $key   ==  'meta_data' ) 
                                {                                    
                                    foreach ( $value as $meta_key   =>  $meta_data)   
                                        {
                                            $current_meta   =   $meta_data->get_data();
                                            if ( isset ( $current_meta['id'] ) )
                                                unset ( $current_meta['id'] );
                                                
                                            $new_order->add_meta_data( $current_meta['key'], $current_meta['value'], TRUE );  
                                        }  
                                }
                            else if ( is_callable( array( $new_order, "set_{$key}" ) ) ) 
                                {
                                    $new_order->{"set_{$key}"}( $value );
                                    // Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
                                } 
                                elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) 
                                {
                                    if ( ! isset( $shipping_fields[ $key ] ) ) 
                                        {
                                            foreach ( $value    as  $a_key  =>  $a_value)
                                                {
                                                    if ( is_callable( array( $new_order, "set_" . current( explode( '_', $key ) ) . "_" . $a_key ) ) ) 
                                                        {
                                                            $new_order->{"set_" . current( explode( '_', $key ) ) . "_" . $a_key}( $a_value );
                                                        }   
                                                }
                                        }
                            
                                }
                        }
                    
                    if ( isset ( $default_order_data['billing_email'] ) )
                        $new_order->hold_applied_coupons( $default_order_data['billing_email'] );
                    $new_order->set_created_via( 'checkout' );
                    $new_order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ) );
                    $new_order->set_currency( $blog_currency );
                    $new_order->set_prices_include_tax( 'yes' === $woocommerce_prices_include_tax );
                    $new_order->set_customer_ip_address( $default_order->get_customer_ip_address() );
                    $new_order->set_customer_user_agent( $default_order->get_customer_user_agent() );
                    $new_order->set_customer_note( isset( $default_order_data['order_comments'] ) ? $default_order_data['order_comments'] : '' );
                    $new_order->set_payment_method( isset( $available_gateways[ $default_order_data['payment_method'] ] ) ? $available_gateways[ $default_order_data['payment_method'] ] : $default_order_data['payment_method'] );
                    
                    $order_total    =   0;
                    
                    foreach ( $shop_order_items  as  $order_item )
                        {
                            $values =   $order_item->get_data();   
                            
                            $cart_item_key  =   '';
                                 
                            $item                       = apply_filters( 'woocommerce_checkout_create_order_line_item_object', new WC_Order_Item_Product(), $cart_item_key, $values, $new_order );

                            //$item->legacy_values        = $values; // @deprecated For legacy actions.
                            //$item->legacy_cart_item_key = $cart_item_key; // @deprecated For legacy actions.
                            $item->set_props(
                                array(
                                    'name'         => $values['name'],
                                    'tax_class'    => $values['tax_class'],
                                    'product_id'   => $values['product_id'],
                                    'variation_id' => $values['variation_id'],
                                    'quantity'     => $values['quantity'],
                                    'subtotal'     => $values['subtotal'],
                                    'total'        => $values['total'],
                                    'subtotal_tax' => $values['subtotal_tax'],
                                    'total_tax'    => $values['total_tax'],
                                    'taxes'        => $values['taxes'],
                                )
                            );
                            
                            $order_total    +=  $values['total'];
                            
                            if ( isset ( $values['variation'] ) )
                                $item->set_props( 
                                    array (
                                            'variation'    => $values['variation'] 
                                            )
                                    );
             
                            $values['data'] =   wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );;
             
                            $meta_data  =   $order_item->get_meta_data();
                            foreach ( $meta_data    as  $meta_item )
                                {
                                    $meta_item_data =   $meta_item->get_data();
                                    $item->add_meta_data( $meta_item_data['key'], $meta_item_data['value'], TRUE );  
                                }
             
                            $item->set_backorder_meta();

                            /**
                             * Action hook to adjust item before save.
                             *
                             * @since 3.0.0
                             */
                            do_action( 'woocommerce_checkout_create_order_line_item', $item, $cart_item_key, $values, $new_order );

                            // Add item to order and save.
                            $new_order->add_item( $item );                            
                            
                        }
                        
                    //set shipping
                    $shipping_items =   $default_order->get_items( 'shipping' );
                    if ( count ( $shipping_items ) > 0 )
                        {
                            foreach ( $shipping_items as $key   =>  $shipping_item )
                                {
                                    //$shipping_item_blog_id  =   $shipping_item->get_meta( 'blog_id' );
                                    //if ( empty ( $shipping_item_blog_id ) ||  $shipping_item_blog_id  != $i_blog_id )
                                        //continue;
                                
                                    $item                       = new WC_Order_Item_Shipping();
                                    $shipping_item_data         = $shipping_item->get_data();
                                    $item->set_props(
                                        array(
                                            'method_title' => $shipping_item_data['method_title'],
                                            'method_id'    => $shipping_item_data['method_id'],
                                            'instance_id'  => $shipping_item_data['instance_id'],
                                            'total'        => $shipping_item_data['total'],
                                            'total_tax'    => $shipping_item_data['total_tax'],
                                            'taxes'        => $shipping_item_data['taxes'],
                                        )
                                    );
                    
                                    $item->add_meta_data( 'Items' , $shipping_item->get_meta( 'Items' ) ); 
                                    $item->add_meta_data( 'blog_id', $i_blog_id, true );

                                    // Add item to order and save.
                                    $new_order->add_item( $item );
                                    
                                    //update the order shipping_total
                                    $new_order->set_shipping_total( $shipping_item_data['total'] );
                                    //$order_total    +=  $shipping_item_data['total'];
                                }
                        }
                    
                    $new_order->set_total ( $order_total );
                    $order_id = $new_order->save();
                    
                    do_action( 'woogc/single_checkout/split_order/order_created', $new_order );
                    
                    switch_to_blog($args['order_blog_id']);
                    $note = __("This is a Split of", 'woo-global-cart') . ' <a href="' . get_edit_post_link($default_order_ID) .'">Order</a>';
                    restore_current_blog();
                    
                    wc_downloadable_product_permissions( $new_order->get_ID(), TRUE );
                    
                    $new_order->add_order_note( $note );  
                                    
                    restore_current_blog();
                    
                }
        
            
            function custom_woocommerce_order_status_changed( $order_id, $old_status, $new_status )
                {
                    
                    remove_action( 'woocommerce_order_status_changed', array( $this, 'custom_woocommerce_order_status_changed' ), 99, 3 );
                    
                    $order  =   new WC_Order( $order_id );
                    
                    //on master order
                    $order_splitted     =   $order->get_meta( 'order_splitted' );
                    if (  ! empty ( $order_splitted ) )
                        {
                            //update the status for child orders
                            $order_items    =   $order->get_items();
                            
                            $items_by_shop  =   array();
                            
                            if ( count ( $order_items ) > 0 )
                                {
                                
                                    foreach ( $order_items as $order_item )
                                        {
                                            $_blog_id   =   $order_item->get_meta('blog_id');
                                                    
                                            $items_by_shop[ $_blog_id ][]   =   $order_item;    
                                            
                                        }
                                        
                                    foreach ( $items_by_shop    as  $shop_id    =>  $items )
                                        {
                                            switch_to_blog( $shop_id );
                                            
                                            $updated_wc_emails_recipients   =   FALSE;
                                            
                                            /*
                                            $args = array(
                                                               'post_type'      =>  'shop_order',
                                                               'status'    =>  'any',
                                                               
                                                               'meta_query' => array(
                                                                   array(
                                                                       'key'        => 'checkout_order_id',
                                                                       'value'      => $order->get_ID(),
                                                                       'compare'    => '=',
                                                                   )
                                                               )
                                                            );
                                            */
                                            remove_filter ( 'woocommerce_order_data_store_cpt_get_orders_query',   array ( $this, 'custom_woocommerce_order_data_store_cpt_get_orders_query' ), 99, 3 );
                                                            
                                            $args = array(
                                                               'post_type'      =>  'shop_order',
                                                               'status'         =>  'any',                
                                                               'meta_key'       =>  'checkout_order_id',
                                                               'meta_value'     =>  $order->get_ID(),
                                                               'meta_compare'   =>  '=',
                                                               );
                                                            
                                                                                                        
                                            $wc_query   =  new WC_Order_Query( $args );
                                            $orders     =   $wc_query->get_orders();
                                            
                                            add_filter ( 'woocommerce_order_data_store_cpt_get_orders_query',   array ( $this, 'custom_woocommerce_order_data_store_cpt_get_orders_query' ), 99, 3 );
                                            
                                            if ( $orders > 0 )
                                                {
                                                    $updated_wc_emails_recipients   =   TRUE;
                                                    $this->update_wc_emails_recipients();
                                                    
                                                    foreach ( $orders as  $order_object )
                                                        {
                                                            $order_old_status   =   $order_object->get_status();
                                                            
                                                            if ( $new_status == $order_old_status )
                                                                continue;
                                                            
                                                            $order_object->update_status( $new_status );
                                                            
                                                            do_action( 'woocommerce_order_status_' . $order_old_status . '_to_' . $new_status, $order_object->get_ID(), $order_object );
                                                            do_action( 'woocommerce_order_status_changed', $order_object->get_ID(), $order_old_status, $new_status, $order_object );
                                                            
                                                        }  
                                                }
                                                                                    
                                            restore_current_blog();
                                            
                                            if ( $updated_wc_emails_recipients )
                                                $this->update_wc_emails_recipients();
                                        }    
                                    
                                }
                            
                        }
                    
                    //on splitted order   
                    $checkout_blog_id   =   $order->get_meta( 'checkout_blog_id' );
                    if (  ! empty ( $checkout_blog_id ) )
                        {
                            //If completed status, check all other split orders if the same and mark the main slso as Completed
                            if ( $new_status == 'completed' )
                                {
                                    //check all other orders    
                                    
                                    $checkout_order_id   =   $order->get_meta( 'checkout_order_id' );
                                                                
                                    switch_to_blog( $checkout_blog_id );
                                    
                                    $main_order  =   new WC_Order( $checkout_order_id );
                                    $order_items    =   $main_order->get_items();
                                    
                                    $items_by_shop  =   array();
                                    $order_status_by_shop   =   array();
                                    
                                    if ( count ( $order_items ) > 0 )
                                        {
                                        
                                            foreach ( $order_items as $order_item )
                                                {
                                                    $_blog_id   =   $order_item->get_meta('blog_id');
                                                            
                                                    $items_by_shop[ $_blog_id ][]   =   $order_item;    
                                                    
                                                }
                                                
                                            foreach ( $items_by_shop    as  $shop_id    =>  $items )
                                                {
                                                    switch_to_blog( $shop_id );
                                                    
                                                    $args = array(
                                                                       'post_status'    =>  'any',
                                                                       
                                                                       'meta_query'     => array(
                                                                                                   'relation' => 'AND',
                                                                                                   array(
                                                                                                       'key'        => 'checkout_blog_id',
                                                                                                       'value'      => $checkout_blog_id,
                                                                                                       'compare'    => '=',
                                                                                                   ),
                                                                                                   array(
                                                                                                       'key'        => 'checkout_order_id',
                                                                                                       'value'      => $main_order->get_ID(),
                                                                                                       'compare'    => '=',
                                                                                                   )
                                                                                               )
                                                                    );
                                                    $wc_query   =  new WC_Order_Query( $args );
                                                    $orders     =   $wc_query->get_orders();
                                                    
                                                    if ( $orders > 0 )
                                                        {
                                                            foreach ( $orders as  $order_object )
                                                                {                                                            
                                                                    if ( ! isset ( $order_status_by_shop[$shop_id ] ) )
                                                                        $order_status_by_shop[$shop_id ]    =   array();
                                                                    
                                                                    $order_status_by_shop[$shop_id ][]    =   $order_object->get_status();
                                                                }
                                                        }
                                                                       
                                                    restore_current_blog();
                                                    
                                                }    
                                            
                                        }

                                    $unique_statuses    =   array();
                                    
                                    foreach ( $order_status_by_shop as  $shop_id    =>  $statuses )
                                        {
                                            $unique_statuses   =  array_merge ( $statuses, $unique_statuses );
                                        }
                                        
                                    $unique_statuses    =   array_unique ( $unique_statuses );
                                    
                                    if ( count ( $unique_statuses ) < 2 )
                                        {
                                            $unique_statuses    =   array_values( $unique_statuses );
                                            
                                            //update the status of the master order
                                            $order_object    =   wc_get_order( $checkout_order_id );
                                            
                                            if ( ! in_array ( $order_object->get_status() , array ( 'refunded', 'cancelled') ))
                                                $order_object->update_status( $unique_statuses[0] );
                                        }
                                    
                                    restore_current_blog();
                                    
                                }
                            
                        }
                        
                    add_action ( 'woocommerce_order_status_changed',                     array ( $this, 'custom_woocommerce_order_status_changed' ) ,    99, 3);    
                    
                }
            
            function custom_woocommerce_admin_order_buyer_name( $buyer, $object )
                {
                    $checkout_blog_id   =   $object->get_meta( 'checkout_blog_id' );
                    $checkout_order_id  =   $object->get_meta( 'checkout_order_id' );
                    
                    if ( empty ( $checkout_blog_id ) )
                        return $buyer;
                    
                    $buyer   =   "SPLIT: Shop#" . $checkout_blog_id .", Order ID" . $checkout_order_id . " - " . $buyer;
                       
                    return $buyer;   
                }
                
                
            function woocommerce_admin_order_totals_after_shipping( $orde_id )
                {
                    $order          =   new WC_Order ( $orde_id );
                    $shipping_items =   $order->get_items( 'shipping' );
                    
                    //check if collected on another shop
                    $collected_outide   =   FALSE;
                    foreach ( $shipping_items   as  $shipping_item )
                        {
                            if ( $shipping_item->get_meta( 'blog_id', TRUE ) > 0 )
                                $collected_outide   =   TRUE;
                        }
                        
                    if ( $collected_outide )
                        echo '<tr><td colspan="100%"><script type="text/javascript">
                            
                            var element =   jQuery(".wc-order-totals-items table td.label:contains(\"'. esc_html__( 'Shipping:', 'woocommerce' ) .'\")");
                            
                            jQuery(element).html( "'. esc_html__( 'Shipping', 'woocommerce' ) .' ( '. esc_html__( 'Collected on Check-out', 'woocommerce' ) .' ) :");
                        
                        </script></td></tr>';
                }
                
                
            function custom_woocommerce_order_data_store_cpt_get_orders_query( $wp_query_args, $query_vars, $object )
                {
                    if ( ! isset ( $wp_query_args['meta_query'] ) )
                        $wp_query_args['meta_query'] =   array();
                        
                    $wp_query_args['meta_query'][]     =   array(
                                                        array(
                                                            'key'     => 'checkout_blog_id',
                                                            'compare' => 'NOT EXISTS',
                                                        ),
                                                    ); 
                    
                    return $wp_query_args;
                }
                
                
            function update_wc_emails_recipients()
                {
                    if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_New_Order' ] ) )
                        {
                            $option_data    =   get_option( 'woocommerce_new_order_settings', array() );
                            $recipient      =   '';
                            if ( ! is_array ( $option_data )    ||  empty ( $option_data['recipient'] ) )
                                $recipient  =   '';
                                else
                                $recipient  =   $option_data['recipient'];
                            
                            if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_New_Order' ]->recipient ))        
                                WC_Emails::instance()->emails[ 'WC_Email_New_Order' ]->recipient =   $recipient;    
                        }
                    if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_Cancelled_Order' ] ) )
                        {
                            $option_data    =   get_option( 'woocommerce_cancelled_order_settings' );
                            $recipient      =   '';
                            if ( ! is_array ( $option_data )    ||  empty ( $option_data['recipient'] ) )
                                $recipient  =   '';
                                else
                                $recipient  =   $option_data['recipient'];
                            
                            if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_Cancelled_Order' ]->recipient ) )
                                WC_Emails::instance()->emails[ 'WC_Email_Cancelled_Order' ]->recipient =   $recipient;    
                        }    
                    if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_Failed_Order' ] ) )
                        {
                            $option_data    =   get_option( 'woocommerce_failed_order_settings' );
                            $recipient      =   '';
                            if ( ! is_array ( $option_data )    ||  empty ( $option_data['recipient'] ) )
                                $recipient  =   '';
                                else
                                $recipient  =   $option_data['recipient'];
                            
                            if ( isset ( WC_Emails::instance()->emails[ 'WC_Email_Failed_Order' ]->recipient ) )
                                WC_Emails::instance()->emails[ 'WC_Email_Failed_Order' ]->recipient =   $recipient;    
                        }
                }
                
                
            function woocommerce_email_enabled( $status, $order_object, $email_object )
                {
                    if ( is_admin() )
                        return $status;
                        
                    $status =   $this->_check_email_enabled_filter( $status, $order_object,  $email_object->id, $email_object );
                        
                    return $status;   
                }
                
            private function _check_email_enabled_filter( $status, $order_object, $type, $email_object )
                {
                    $is_split_order =   $order_object->get_meta( 'checkout_order_id' )  >   0   ?   TRUE    :   FALSE;
                    
                    $args   =   array(
                                        'type'              =>  $type,
                                        'email_object'     =>  $email_object,
                                        'order'             =>  $order_object,
                                        'is_split_order'    =>  $is_split_order
                                        );
                                        
                    if ( $is_split_order )
                        {
                            $args['checkout_blog_id']       =   $order_object->get_meta( 'checkout_blog_id' );
                            $args['checkout_order_id']      =   $order_object->get_meta( 'checkout_order_id' );
                        }
                    
                    $status =   apply_filters( 'woogc/checkout/single/split/trigger_email', $status, $args );
                        
                    return $status;    
                    
                }
                           
        }
        
 
 
    new WooGC_Cart_Single_Checkout();
        
        
?>