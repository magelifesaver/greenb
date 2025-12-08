<?php
    
    defined( 'ABSPATH' ) || exit;
     
    class WooGC_AJAX 
        {
            public function __construct() 
                {
                    
                    //customer filter
                    add_action('wp_ajax_woogc_json_search_customers'             , array($this, 'json_search_customers'));
                    
                    
                    add_action('plugins_loaded',                                array($this, 'plugins_loaded'));
                    
                    if (  defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) )
                        add_action( 'wc_ajax_update_shipping_method',       array( $this, 'update_shipping_method' ), -1 );
                    
                }
                
                
            function plugins_loaded()
                {
                    //replace default 
                    remove_action( 'wp_ajax_woocommerce_increase_order_item_stock', array( 'WC_AJAX', 'increase_order_item_stock' ) );
                    remove_action( 'wp_ajax_woocommerce_reduce_order_item_stock',   array( 'WC_AJAX', 'reduce_order_item_stock' ) );
                        
                    //handle AJAX stock reduce
                    add_filter('wp_ajax_woocommerce_reduce_order_item_stock',       array( $this, 'woocommerce_reduce_order_stock'),  999);
                    //handle AJAX stock increase                                    
                    add_filter('wp_ajax_woocommerce_increase_order_item_stock',     array( $this, 'woocommerce_increase_order_stock'),  999);
                    
                }  
           
           
           
            /**
             * AJAX update shipping method on cart page.
             */
            public static function update_shipping_method() {
                check_ajax_referer( 'update-shipping-method', 'security' );

                wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

                $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
                $posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

                if ( is_array( $posted_shipping_methods ) ) {
                    foreach ( $posted_shipping_methods as $i => $value ) {
                        if ( ! is_array( $value ) ) {
                            continue;
                        }
                        $chosen_shipping_methods[ $i ] = $value;
                    }
                }

                WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

                WC_AJAX::get_cart_totals();
            }
           
                          
            /**
             * Search for customers and return json.
             */
            function json_search_customers() 
                {
                    ob_start();

                    check_ajax_referer( 'search-customers', 'security' );

                    if ( ! current_user_can( 'edit_shop_orders' ) ) {
                        wp_die( -1 );
                    }

                    $term    = wc_clean( stripslashes( $_GET['term'] ) );
                    $exclude = array();

                    if ( empty( $term ) ) {
                        wp_die();
                    }

                    // Stop if it is not numeric and smaller than 3 characters.
                    if ( ! is_numeric( $term ) && 2 >= strlen( $term ) ) {
                        wp_die();
                    }

                    // Search by ID.
                    if ( is_numeric( $term ) ) {
                        $customer = new WC_Customer( intval( $term ) );

                        // Customer does not exists.
                        if ( 0 === $customer->get_id() ) {
                            wp_die();
                        }

                        $ids = array( $customer->get_id() );
                    } else {
                        $data_store = WC_Data_Store::load( 'customer' );
                        $ids        = $data_store->search_customers( $term );
                        
                        global $wpdb;
                        
                        $mysql_query    =   $wpdb->prepare("SELECT ID FROM " . $wpdb->users . " AS u
                                                    JOIN " . $wpdb->usermeta . " AS um ON um.user_id = u.ID
                                                    WHERE " 
                                                    //um.meta_key REGEXP '^" . $wpdb->prefix . "[0-9]+_capabilities$' AND um.meta_value LIKE '%%customer%%' AND 
                                                     ."u.user_login LIKE '%%%s%%'", $term);
                        $results        =           $wpdb->get_results( $mysql_query );
                        
                        $ids    =   array();
                        foreach( $results as $result)
                            {
                                $ids[]  =   $result->ID;    
                            }
                        
                    }

                    $found_customers = array();

                    if ( ! empty( $_GET['exclude'] ) ) {
                        $ids = array_diff( $ids, (array) $_GET['exclude'] );
                    }

                    foreach ( $ids as $id ) {
                        $user = new WP_User( $id );
                        /* translators: 1: user display name 2: user ID 3: user email */
                        $found_customers[ $id ] = sprintf(
                            esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
                            get_user_meta($id, 'first_name', TRUE) . ' ' . get_user_meta($id, 'last_name', TRUE),
                            $id,
                            $user->data->user_email
                        );
                    }

                    wp_send_json( apply_filters( 'woocommerce_json_search_found_customers', $found_customers ) );
                }
            

                
            function woocommerce_reduce_order_stock( $order )
                {
      
                    check_ajax_referer( 'order-item', 'security' );
                    if ( ! current_user_can( 'edit_shop_orders' ) ) {
                        wp_die( -1 );
                    }
                    $order_id       = absint( $_POST['order_id'] );
                    $order_item_ids = isset( $_POST['order_item_ids'] ) ? $_POST['order_item_ids'] : array();
                    $order_item_qty = isset( $_POST['order_item_qty'] ) ? $_POST['order_item_qty'] : array();
                    $order          = wc_get_order( $order_id );
                    $order_items    = $order->get_items();
                    $return         = array();
                    if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) 
                        {
                            foreach ( $order_items as $item_id => $order_item ) 
                                {
                                    // Only reduce checked items
                                    if ( ! in_array( $item_id, $order_item_ids ) ) 
                                        {
                                            continue;
                                        }
                                        
                                    if( !empty($order_item->get_meta('blog_id')) )
                                        switch_to_blog( $order_item->get_meta('blog_id') );
                                        
                                    $_product = $order_item->get_product();
                                    if ( $_product && $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) 
                                        {
                                            $stock_change = apply_filters( 'woocommerce_reduce_order_stock_quantity', $order_item_qty[ $item_id ], $item_id );
                                            $new_stock    = wc_update_product_stock( $_product, $stock_change, 'decrease' );
                                            $item_name    = $_product->get_sku() ? $_product->get_sku() : $_product->get_id();
                                            $note         = sprintf( __( 'Item %1$s stock reduced from %2$s to %3$s.', 'woocommerce' ), $item_name, $new_stock + $stock_change, $new_stock );
                                            $return[]     = $note;
                                            $order->add_order_note( $note );
                                        }
                                        
                                    if( !empty($order_item->get_meta('blog_id')) )
                                        restore_current_blog();
                                        
                                }
                                
                            do_action( 'woocommerce_reduce_order_stock', $order );
                            if ( empty( $return ) ) 
                                {
                                    $return[] = __( 'No products had their stock reduced - they may not have stock management enabled.', 'woocommerce' );
                                }
                            echo wp_kses_post( implode( ', ', $return ) );
                        }
                    wp_die();
                    
                }
                
                
            function woocommerce_increase_order_stock( $order )
                {
                             
                    check_ajax_referer( 'order-item', 'security' );
                    if ( ! current_user_can( 'edit_shop_orders' ) ) {
                        wp_die( -1 );
                    }
                    $order_id       = absint( $_POST['order_id'] );
                    $order_item_ids = isset( $_POST['order_item_ids'] ) ? $_POST['order_item_ids'] : array();
                    $order_item_qty = isset( $_POST['order_item_qty'] ) ? $_POST['order_item_qty'] : array();
                    $order          = wc_get_order( $order_id );
                    $order_items    = $order->get_items();
                    $return         = array();
                    if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) 
                        {
                            foreach ( $order_items as $item_id => $order_item ) 
                                {
                                    // Only reduce checked items
                                    if ( ! in_array( $item_id, $order_item_ids ) ) 
                                        {
                                            continue;
                                        }
                                        
                                    if( !empty($order_item->get_meta('blog_id')) )
                                        switch_to_blog( $order_item->get_meta('blog_id') );
                                        
                                    $_product = $order_item->get_product();
                                    if ( $_product && $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) 
                                        {
                                            $old_stock    = $_product->get_stock_quantity();
                                            $stock_change = apply_filters( 'woocommerce_restore_order_stock_quantity', $order_item_qty[ $item_id ], $item_id );
                                            $new_quantity = wc_update_product_stock( $_product, $stock_change, 'increase' );
                                            $item_name    = $_product->get_sku() ? $_product->get_sku() : $_product->get_id();
                                            $note         = sprintf( __( 'Item %1$s stock increased from %2$s to %3$s.', 'woocommerce' ), $item_name, $old_stock, $new_quantity );
                                            $return[]     = $note;
                                            $order->add_order_note( $note );
                                        }
                                        
                                    if( !empty($order_item->get_meta('blog_id')) )
                                        restore_current_blog();
                                        
                                }
                            do_action( 'woocommerce_restore_order_stock', $order );
                            if ( empty( $return ) ) 
                                {
                                    $return[] = __( 'No products had their stock increased - they may not have stock management enabled.', 'woocommerce' );
                                }
                            echo wp_kses_post( implode( ', ', $return ) );
                        }
                    wp_die();
                    
                }
                
                
        }
        
?>