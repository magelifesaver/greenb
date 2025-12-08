<?php
       
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Sequential_Order_Numbers 
        {
            
            function __construct() 
                {
                    
                    add_action( 'wp_insert_post',                                   'WooGC_Sequential_Order_Numbers::wp_insert_post' , 10, 2 );
                    add_filter( 'woocommerce_store_api_checkout_order_processed',   'WooGC_Sequential_Order_Numbers::woocommerce_store_api_checkout_order_processed', 10 );
                    add_filter( 'woogc/single_checkout/split_order/order_created',  'WooGC_Sequential_Order_Numbers::woocommerce_store_api_checkout_order_processed', 10 );
                    
                    add_action( 'woocommerce_process_shop_order_meta',              'WooGC_Sequential_Order_Numbers::woocommerce_process_shop_order_meta' , 10, 2 );
                                        
                    add_filter( 'woocommerce_order_number',                         'WooGC_Sequential_Order_Numbers::get_order_number' , 10, 2 );

                    add_filter( 'woocommerce_shortcode_order_tracking_order_id',    'WooGC_Sequential_Order_Numbers::woocommerce_shortcode_order_tracking_order_id' );   
                    
                }
                
                
            static function network_update_order_numbers()
                {
                    global $wpdb, $WooGC;
                    
                    $orders_to_process      =   200;
                    $highest_order_number   =   1;
                    
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                    foreach($sites as $site)
                        {
                            switch_to_blog( $site->blog_id );
                            
                            do {
                                    
                                    $mysql_query    =   "SELECT DISTINCT ID FROM "   .   $wpdb->posts    .   " as P
                                                            LEFT JOIN ". $wpdb->postmeta ." AS PM ON PM.post_id = P.ID
                                                            WHERE  P.post_type IN ( 'shop_order', 'shop_order_placehold' ) AND ID NOT IN 
                                                                        ( SELECT post_id FROM ". $wpdb->postmeta ."
                                                                               WHERE ". $wpdb->postmeta .".`meta_key` = '_order_number'
                                                                                AND ". $wpdb->postmeta .".`post_id` =   P.ID
                                                                        )
                                                            ORDER BY ID ASC
                                                            LIMIT ". $orders_to_process;
                                    
                                    $results    =   $wpdb->get_results($mysql_query);
                                    
                                    if(count($results)  >   0)
                                        {
                                            foreach( $results as $result ) 
                                                {
                                                    add_post_meta( $result->ID, '_order_number', $result->ID );
                                                }   
                                        }

                                } while ( count( $results ) >   0 ); 
                            
                            
                            $mysql_query    =   "SELECT MAX( CAST(`PM`.`meta_value` as SIGNED INTEGER) ) as highest FROM "   .   $wpdb->posts    .   " as P
                                                    JOIN ". $wpdb->postmeta ." AS PM ON PM.post_id = P.ID
                                                    WHERE  P.post_type IN ( 'shop_order', 'shop_order_placehold' ) AND PM.meta_key = '_order_number'";
                            
                            $highest    =   $wpdb->get_var($mysql_query);
                            
                            if ( $highest_order_number    <   $highest )
                                $highest_order_number   =   $highest; 
                            
                            restore_current_blog();
                                                        
                        }
                        
                    self::update_network_order_number($highest_order_number);
                                            
                }
            

            static function get_next_network_order_number()
                {
                    
                    $network_order_number   =   get_site_option('WooGC_current_network_order_number');
                    
                    $network_order_number++;
                    
                    return $network_order_number;
                    
                }
                

            static function update_network_order_number($order_number)
                {
                    
                    update_site_option('WooGC_current_network_order_number', $order_number);
                    
                }
            
                
            static function add_order_number( $order_id )
                {

                    $order_number = get_post_meta( $order_id, '_order_number', true );

                    if (    $order_number   >   0)
                        return $order_number;
     
     
                    global $WooGC;
                    
                    $have_lock  =   FALSE;
                    $_attempts  =   0;
                    while( $have_lock ===   FALSE )
                        {
                            if ( $WooGC->functions->create_lock( 'WooGC_Sequential_Order_Number', 10 ) )
                                $have_lock  =   TRUE;
                                else
                                sleep( 1 );
                                
                            $_attempts++;
                            
                            if ( $_attempts >   10 )
                                {
                                     
                                    return FALSE;
                                }
                        }
                    
                    $network_order_number   =   self::get_next_network_order_number();
                    
                    //Allow to format the order_number
                    $add_order_number   =   apply_filters ( 'woogc/sequential_order_number/format' , $network_order_number, $order_id );
                    
                    update_post_meta( $order_id, '_order_number', $add_order_number );
                    
                    self::update_network_order_number( $network_order_number );
                    
                    $WooGC->functions->release_lock( 'WooGC_Sequential_Order_Number' );
                    
                    return $network_order_number;
                    
                }
                
            
            static function woocommerce_process_shop_order_meta($post_id, $post )
                {
                    
                    if ( ! is_a( $post, 'WC_Order' ) ) 
                        {
                            if ( $post->post_type   !=  'shop_order')
                            return;
                        }
                    
                    if ( wp_is_post_revision( $post_id ) )
                        return;
  
                    self::add_order_number($post_id);    
                    
                }
            
            static function wp_insert_post($post_id, $post)
                {
                    
                    if (  !in_array ( $post->post_type, array ( 'shop_order', 'shop_order_placehold' ) ) )
                        return;
                    
                    if ( wp_is_post_revision( $post_id ) )
                        return;
  
                    self::add_order_number($post_id);
            
                }
                
                
            static function woocommerce_store_api_checkout_order_processed( $order ) 
                {
                    
                    self::add_order_number( $order->get_ID() );
                    
                }
                
            
            static function get_order_number($order_number, $order)
                {
                    if ( $order_number < 1 )
                        return $order_number;
                    
                    $_order_number  =   get_post_meta( $order_number, '_order_number', TRUE );
                    if ( $_order_number > 0 )
                        return $_order_number;
                    
                    remove_filter( 'woocommerce_order_number',                          'WooGC_Sequential_Order_Numbers::get_order_number' , 10, 2 );
                    $order_number   =   $order->get_order_number();
                    add_filter( 'woocommerce_order_number',                             'WooGC_Sequential_Order_Numbers::get_order_number' , 10, 2 );
                    
                    if ( !empty($order_number)) 
                        {
                            return $order_number;
                        }

                    
                    return $order_number;   
                    
                }
                   
             
        }


    new WooGC_Sequential_Order_Numbers();

?>