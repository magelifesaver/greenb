<?php
    
    defined( 'ABSPATH' ) || exit;
        
    /**
    * Name:          WooCommerce Subscriptions
    * Since:        2.2.1
    * Last Check:   4.0.1
    */
    
    class WooGC_woocommerce_subscriptions
        {
            
            function __construct()
                {
                    
                    add_action( 'init',                                 array( $this, 'on__init'), -1 );    
                    
                }
                        
            function on__init()
                {
                    
                    global $WooGC;
                    
                    //replace other hoock
                    $WooGC->functions->remove_class_filter ( 'woocommerce_grant_product_download_permissions', 'WCS_Download_Handler', 'save_downloadable_product_permissions' );
                    
                    add_action( 'woocommerce_grant_product_download_permissions', __CLASS__ . '::save_downloadable_product_permissions' );
                    
                    
                    //My Account -> Subscriptions list
                    add_action('wcs_get_users_subscriptions',   array ( $this, 'wcs_get_users_subscriptions' ), 999, 2 );
                    
                }
                
                
                /**
             * Save the download permissions on the individual subscriptions as well as the order. Hooked into
             * 'woocommerce_grant_product_download_permissions', which is strictly after the order received all the info
             * it needed, so we don't need to play with priorities.
             *
             * @param integer $order_id the ID of the order. At this point it is guaranteed that it has files in it and that it hasn't been granted permissions before
             */
            public static function save_downloadable_product_permissions( $order_id ) 
                {
                    global $wpdb;
                    $order = wc_get_order( $order_id );

                    if ( wcs_order_contains_subscription( $order, 'any' ) ) {
                        $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => array( 'any' ) ) );
                    } else {
                        return;
                    }

                    foreach ( $subscriptions as $subscription ) {
                        if ( sizeof( $subscription->get_items() ) > 0 ) {
                            foreach ( $subscription->get_items() as $item ) {
                                
                                do_action( 'woocommerce/cart_loop/start', $item );
                                
                                $_product = $item->get_product();

                                if ( $_product && $_product->exists() && $_product->is_downloadable() ) {
                                    $downloads  = wcs_get_objects_property( $_product, 'downloads' );
                                    $product_id = wcs_get_canonical_product_id( $item );

                                    foreach ( array_keys( $downloads ) as $download_id ) {
                                        // grant access on subscription if it does not already exist
                                        if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE `order_id` = %d AND `product_id` = %d AND `download_id` = '%s'", $subscription->get_id(), $product_id, $download_id ) ) ) {
                                            wc_downloadable_file_permission( $download_id, $product_id, $subscription, $item['qty'] );
                                        }
                                        WCS_Download_Handler::revoke_downloadable_file_permission( $product_id, $order_id, $order->get_user_id() );
                                    }
                                }
                                
                                do_action( 'woocommerce/cart_loop/end', $item );
                            }
                        }
                        update_post_meta( $subscription->get_id(), '_download_permissions_granted', 1 );
                    }
                }
            
            
            function wcs_get_users_subscriptions( $subscriptions, $user_id )
                {
                    if ( ! WooGC_Functions::check_backtrace_for_caller( array ( array ( 'get_my_subscriptions', 'WCS_Template_Loader') ) ) )
                        return $subscriptions;    
                    
                    if ( empty ( $user_id ) ||  $user_id < 1 )
                        return $subscriptions;    
                    
                    global $blog_id, $WooGC;
                    
                    $current_blog   =   $blog_id;
                    
                    $options    =   $WooGC->functions->get_options();
                            
                    $sites      =   $WooGC->functions->get_gc_sites( TRUE );
                    
                    $sites_ids  =   array();
                    foreach($sites  as  $site)
                        {
                            if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                continue;
                            
                            if ( $site->blog_id ==  $current_blog )
                                continue;
                            
                            switch_to_blog( $site->blog_id );
                            
                            if ( ! WooGC_Functions::is_plugin_active( 'woocommerce/woocommerce.php') )
                                {
                                    restore_current_blog();
                                    continue;   
                                }
                                
                            $subscription_ids = WCS_Customer_Store::instance()->get_users_subscription_ids( $user_id );

                            foreach ( $subscription_ids as $subscription_id ) 
                                {
                                    $subscription = wcs_get_subscription( $subscription_id );

                                    if ( $subscription ) 
                                        {
                                            $subscription->update_meta_data('blog_id', $blog_id );
                                            $subscriptions[ $subscription_id ] = $subscription;
                                        }
                                }
                                
                            restore_current_blog();
                        }
                    
                    krsort( $subscriptions );
                            
                    return $subscriptions;  
                }
                                       
        }


    new WooGC_woocommerce_subscriptions();    
    
?>