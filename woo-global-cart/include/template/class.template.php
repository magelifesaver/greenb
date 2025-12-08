<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Template 
        {
            var $WooGC;
            
            function __construct()
                {

                    global $WooGC;
                    $this->WooGC    =   $WooGC;

                    $_WooGC_Disable_GlobalCart  =   apply_filters( 'woogc/disable_global_cart',     FALSE);
                    
                    if( $_WooGC_Disable_GlobalCart  === FALSE )
                        {
                            add_filter('post_type_link',                                array( $this,   'post_type_link'),  -1, 4);
                            
                            add_filter('woocommerce_get_checkout_url',                  array( $this,   'woocommerce_get_checkout_url'),  999);
                            
                            add_filter('woocommerce_order_item_permalink',              array( $this,   'woocommerce_order_item_permalink'),  999, 3);
                                                
                            add_filter('woocommerce_cart_item_thumbnail',               array( $this,   'on__woocommerce_cart_item_thumbnail'), 999, 3);
                            add_filter('woocommerce_cart_item_permalink',               array( $this,   'on__woocommerce_cart_item_permalink'), 999, 3);
                            add_filter('woocommerce_product_get_upsell_ids' ,           array( $this,   'on__woocommerce_product_upsell_ids'), 10, 2);
                            add_filter('woocommerce_product_get_review_count' ,         array( $this,   'on__woocommerce_product_review_count'), 10, 2);
                            add_filter('woocommerce_product_get_crosssell_ids' ,        array( $this,   'on__woocommerce_product_crosssell_ids'), 10, 2);
                            add_filter('woocommerce_is_virtual' ,                       array( $this,   'on__woocommerce_is_virtual'), 10, 2);                            
                        }
                                     
                    add_action( 'wp',                                       array( $this,   'check_checkout_location') );

                    add_filter('woocommerce_my_account_my_orders_query',                array( $this,   'woocommerce_my_account_my_orders_query'),  999);

                    add_filter('woocommerce_customer_get_downloadable_products',        array( $this,   'woocommerce_customer_get_downloadable_products'),  999);                                        
                    
                    add_filter('woocommerce_order_get_downloadable_items',              array( $this,   'woocommerce_order_get_downloadable_items'), 999, 2);
                    
                    $options    =   $this->WooGC->functions->get_options();
                    
                    if ( $options['show_product_attributes'] == 'yes' )
                        add_filter('woocommerce_get_item_data' ,                    array( $this,   'woocommerce_get_item_data'), 999, 2);
                    
                }
                
                
                
            function post_type_link( $post_link, $post, $leavename, $sample )
                {
                    
                    if ( $post->post_type   !=  'product' ) 
                        return $post_link;
                        
                    //ignore when WPML, TranslatePress
                    if  ( defined('ICL_SITEPRESS_VERSION') || defined ( 'TRP_PLUGIN_VERSION' ) )
                        return $post_link;
                        
                    global $wp_rewrite;
                        
                    $post_permastruct_link          =   $wp_rewrite->get_extra_permastruct( $post->post_type );
                    $local_wc_permalink             =   get_option('woocommerce_permalinks');
                    $local_post_permastruct_link    =   $local_wc_permalink['product_base'];
                    if ( strpos( $local_post_permastruct_link, '%product%' ) === FALSE )
                        $local_post_permastruct_link             .=  '/%product%';
                    
                    if  ( $post_permastruct_link == $local_post_permastruct_link )
                        return $post_link;
                        
                    $post_link          =   $local_post_permastruct_link;
                            
                    $slug = $post->post_name;

                    $draft_or_pending = get_post_status( $post ) && in_array( get_post_status( $post ), array( 'draft', 'pending', 'auto-draft', 'future' ) );

                    $post_type = get_post_type_object($post->post_type);

                    if (  !$draft_or_pending || $sample )  
                        {
                            if ( ! $leavename ) {
                                $post_link = str_replace("%$post->post_type%", $slug, $post_link);
                            }
                            $post_link = home_url( user_trailingslashit($post_link) );
                        } 
                    else 
                        {
                            if ( $post_type->query_var && ( isset($post->post_status) && !$draft_or_pending ) )
                                $post_link = add_query_arg($post_type->query_var, $slug, '');
                            else
                                $post_link = add_query_arg(array('post_type' => $post->post_type, 'p' => $post->ID), '');
                            $post_link = home_url($post_link);
                        }
                    
                    return $post_link;
                       
                }
            
            
            /**
            * Return orders for this user from all network
            * 
            */
            function woocommerce_my_account_my_orders_query( $orders )
                {
   
                    return $orders;
                    
                }
                
            
            function woocommerce_account_orders( $current_page )
                {
                   
                    $current_page    = empty( $current_page ) ? 1 : absint( $current_page );
                    
                    $network_orders =   new stdClass();
                    $network_orders->orders         =   array();
                    $network_orders->total          =   0;
                    $network_orders->max_num_pages  =   1;
                    
                    $posts_per_page =   get_option( 'posts_per_page' );
                    
                    //retrieve the customer ordsers from all blogs
                    global $WooGC, $blog_id;
                    
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                    foreach($sites  as  $site)
                        {
                            
                            switch_to_blog($site->blog_id);
                            
                            $customer_orders = wc_get_orders( apply_filters( 'woocommerce_my_account_my_orders_query', array( 'customer' => get_current_user_id(), 'page' => $current_page, 'paginate' => true , 'limit'    =>  -1) ) );
                            
                            //add the blog_id
                            foreach($customer_orders->orders    as  $customer_order)
                                {
                                    $customer_order->blog_id        =   $blog_id;
                                    
                                    $customer_order->order_number   =   $customer_order->get_order_number();
                                    
                                    $network_orders->orders[]       =   $customer_order;
                                    $network_orders->total++;
                                    
                                }
                                
                            restore_current_blog();
                            
                        }
                    
                    //sort this
                    usort($network_orders->orders, array($this, 'sort_orders_by_id'));
                        
                    $network_orders->max_num_pages  =   ceil( $network_orders->total / $posts_per_page );
                        
                    //slice the needed page
                    $current_page_orders    =   array_slice( $network_orders->orders, ( $current_page *  $posts_per_page ) - $posts_per_page, $posts_per_page );
                    $network_orders->orders =   $current_page_orders;
                    
                    wc_get_template(
                                    'myaccount/orders.php',
                                                            array(
                                                                'current_page' => absint( $current_page ),
                                                                'customer_orders' => $network_orders,
                                                                'has_orders' => 0 < $network_orders->total,
                                                                'wp_button_class' => function_exists ( 'wc_wp_theme_get_element_class_name' )   &&  wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '',
                                                            )
                                );    
                    
                }
                         
            
            function sort_orders_by_id( $a, $b )
                {
                    
                    if ($a->order_number == $b->order_number)
                        {
                            return 0;
                        }

                    //return ($a->order_number > $b->order_number) ? -1 : 1;
                    $a_date     =   $a->get_date_created();
                    if ( is_object ( $a_date)   && isset ( $a_date->date ) )
                        $a_date =   $a_date->date;
                    $b_date     =   $b->get_date_created();
                    if ( is_object ( $b_date)   && isset ( $b_date->date ) )
                        $b_date =   $b_date->date;
                    
                    if ( empty ($a_date )   ||  empty  ( $b_date  ))
                        {
                            return 0;   
                        }
                    return ( strtotime( $a_date ) > strtotime( $b_date ) )  ? -1 : 1;
                                           
                }
            
            
            function woocommerce_customer_get_downloadable_products( $downloads )
                {
                    
                    if ( ! is_user_logged_in() ) 
                        return $downloads;   
                    
                    
                    $customer_id    =   get_current_user_id();
                    
                    if ( ! is_array ( $downloads ) )
                        $downloads   = array();
                    
                    global $WooGC, $blog_id;
                    
                    
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                    foreach($sites  as  $site)
                        {
                            
                            switch_to_blog($site->blog_id);
                    
                            $_product    = null;
                            $order       = null;
                            $file_number = 0;

                            // Get results from valid orders only
                            $results = wc_get_customer_download_permissions( $customer_id );

                            if ( $results ) 
                                {
                                    foreach ( $results as $result ) 
                                        {
                                            if ( ! $order || $order->id != $result->order_id ) 
                                                {
                                                    // new order
                                                    $order    = wc_get_order( $result->order_id );
                                                    $_product = null;
                                                }

                                            // Make sure the order exists for this download
                                            if ( ! $order ) 
                                                {
                                                    continue;
                                                }

                                            // Downloads permitted?
                                            if ( ! $order->is_download_permitted() ) 
                                                {
                                                    continue;
                                                }

                                            $product_id = intval( $result->product_id );

                                            if ( ! $_product || $_product->id != $product_id ) 
                                                {
                                                    $product_blog_id  =   FALSE;    
                                                    foreach  ( $order->get_items() as  $order_item )
                                                        {
                                                            if ( $order_item->get_variation_id()    >   0   &&  $order_item->get_variation_id()  !=  $product_id )
                                                                continue;
                                                            else  if ( $order_item->get_variation_id()    <   1     &&  $order_item->get_product_id()  !=  $product_id )
                                                                continue;
                                                                
                                                            $product_blog_id    =   $order_item->get_meta( 'blog_id' );
                                                            
                                                            break;
                                                        }
                                                        
                                                    if ( $product_blog_id )
                                                        {
                                                            switch_to_blog( $product_blog_id );
                                                    
                                                            // new product
                                                            $file_number = 0;
                                                            $_product    = wc_get_product( $product_id );
                                                            
                                                            restore_current_blog();   
                                                            
                                                        }
                                                        else
                                                        {
                                                            // new product
                                                            $file_number = 0;
                                                            $_product    = wc_get_product( $product_id );
                                                        }
                                                    
                                                }

                                            // Check product exists and has the file
                                            if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) 
                                                {
                                                    continue;
                                                }

                                            $download_file = $_product->get_file( $result->download_id );

                                            // Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files
                                            $download_name = apply_filters(
                                                'woocommerce_downloadable_product_name',
                                                $download_file['name'],
                                                $_product,
                                                $result->download_id,
                                                $file_number
                                            );

                                            $download_url_args  =   array(
                                                                            'download_file' => $product_id,
                                                                            'order'         => $result->order_key,
                                                                            'email'         => $result->user_email,
                                                                            'key'           => $result->download_id
                                                                        );
                                            if ( $product_blog_id !==   FALSE )
                                                $download_url_args['sid']   =   $product_blog_id;
                                            
                                            $downloads[] = array(
                                                'download_url'        => add_query_arg( $download_url_args, home_url( '/' ) ),
                                                'download_id'         => $result->download_id,
                                                'product_id'          => $product_id,
                                                'blog_id'             => $site->blog_id,
                                                'product_name'        => $_product->get_name(),
                                                'product_url'         => $_product->is_visible() ? $_product->get_permalink() : '',
                                                'download_name'       => $download_name,
                                                'order_id'            => $order->id,
                                                'order_key'           => $order->order_key,
                                                'downloads_remaining' => $result->downloads_remaining,
                                                'access_expires'      => $result->access_expires,
                                                'file'                => array(
                                                                            'name' => $download_file->get_name(),
                                                                            'file' => $download_file->get_file(),
                                                                        ),
                                            );

                                            $file_number++;
                                        }
                                }
                    
                            restore_current_blog();
                            
                        }
                    
                    
                    return $downloads;
                    
                }
            
            
            /**
            * Return Checkout url deppending on use selection
            *     
            * @param mixed $checkout_url
            */
            public static function woocommerce_get_checkout_url( $checkout_url = '' )
                {
                    
                    global $blog_id, $woocommerce, $WooGC;
           
                    $options    =   $WooGC->functions->get_options();
                    
                    if( $options['cart_checkout_type']  ==  'single_checkout'  &&   ! empty($options['cart_checkout_location'])   &&  $options['cart_checkout_location']  !=  $blog_id    )
                        {
                            switch_to_blog( $options['cart_checkout_location'] );
                            
                            $checkout_url = wc_get_page_permalink( 'checkout' );
                            if ( $checkout_url ) 
                                {
                                    // Force SSL if needed
                                    if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) 
                                        {
                                            $checkout_url = str_replace( 'http:', 'https:', $checkout_url );
                                        }
                                }
                                
                            restore_current_blog();
                            
                            $checkout_url   =   apply_filters( 'woogc/get_checkout_url',     $checkout_url);
                        }
                        else if ( $options['cart_checkout_type']  ==  'each_store' )
                            {
                                if ( is_object ( $woocommerce->cart->cart_split ) )
                                    $checkout_url   =   $woocommerce->cart->cart_split->get_checkout_url();      
                            }
                    
                    return $checkout_url;    
                    
                }
                
           
           
            /**
            * check on the check-out page if correct, when using "Cart Checkout location
            * 
            */
            function check_checkout_location()
                {
                    
                    $options    =   $this->WooGC->functions->get_options();   
                    if ( $options['cart_checkout_type']  !=  'single_checkout' )
                        return;
                        
                    if ( empty ( $options['cart_checkout_location'] ) )
                        return;
                        
                    if ( ! is_checkout() )
                        return;
                        
                    global $blog_id;
                    
                    if ( $blog_id == $options['cart_checkout_location'] )
                        return;
                    
                    //check if the site is active in the global carts
                    if ( isset ( $options['use_global_cart_for_sites'][ $blog_id ] )    &&  $options['use_global_cart_for_sites'][ $blog_id ]   !=  'yes' )
                        return;
                    
                    $checkout_url = $this->woocommerce_get_checkout_url();
                    wp_redirect( $checkout_url );
                    
                    ob_end_flush();
                    
                }
                
            
            
            /**
            * Return order item permalink
            * 
            * @param mixed $link
            * @param mixed $item
            * @param mixed $order
            */
            function woocommerce_order_item_permalink( $permalink, $item, $order)
                {
                    //only if multisite set
                    if( !isset($item['blog_id']))
                        return $permalink;    
                    
                    switch_to_blog( $item['blog_id'] );
                    
                    $permalink  =   get_permalink( $item['product_id'] );
                    
                    restore_current_blog();
                    
                    return $permalink;
                        
                }
                
                
                
            /**
            * Return the order item product thumbnail
            * 
            * @param mixed $image
            * @param mixed $cart_item
            * @param mixed $cart_item_key
            */
            function on__woocommerce_cart_item_thumbnail( $image, $cart_item, $cart_item_key )
                {
                    
                    //only if multisite set
                    if( !isset($cart_item['blog_id']))
                        return $image;
                    
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $product    =   $cart_item['data'];   
                    $image      =   $product->get_image();
                    
                    restore_current_blog();
                    
                    return $image;
                    
                    
                    
                }
            
            
            
            /**
            * Return cart item permalink
            * 
            * @param mixed $permalink
            * @param mixed $cart_item
            * @param mixed $cart_item_key
            */
            function on__woocommerce_cart_item_permalink( $permalink, $cart_item, $cart_item_key )
                {
                    
                    //only if multisite set
                    if( !isset($cart_item['blog_id']))
                        return $permalink;
                    
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $product        =   $cart_item['data'];
                    
                    //reset visibility
                    unset($product->visibility);
                       
                    $permalink      =   $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
                    
                    restore_current_blog();
                    
                    return $permalink;   
                    
                }
            
            
            /**
            * Return product price
            * 
            * @param mixed $price
            * @param mixed $product
            */
            function on__woocommerce_get_price( $price, $product )
                {
                    
                    if( !isset($product->blog_id))
                        return $price;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product   = wc_get_product( $product->get_id() );
                    $price      =   $_product->get_price('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $price;
                    
                }
            
            
            
            /**
            * Return regular price
            * 
            * @param mixed $price
            * @param mixed $product
            */
            function on__woocommerce_get_regular_price( $price, $product )
                {
                    
                    if( !isset($product->blog_id) )
                        return $price;
                    
                    switch_to_blog( $product->blog_id );

                    $_product   = wc_get_product( $product->get_id() );
                    $price      =   $_product->get_regular_price('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $price;
               
                }
            
            
            
            /**
            * Return on sale price
            * 
            * @param mixed $sale_price
            * @param mixed $product
            */
            function on__woocommerce_get_sale_price( $sale_price, $product )
                {
                    return $sale_price;
                    if( !isset($product->blog_id) )
                        return $sale_price;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    $sale_price      =   $_product->get_sale_price('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $sale_price;    
                    
                }
            
            
            
            /**
            * Return product tax class
            * 
            * @param mixed $tax_class
            * @param mixed $product
            */
            function on__woocommerce_product_tax_class( $tax_class, $product )
                {
                    
                    if( !isset($product->blog_id) )
                        return $tax_class;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    $tax_class      =   $_product->get_tax_class('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $tax_class;   
                    
                }
            
            
            
            /**
            * Return product upsell ids
            * 
            * @param mixed $upsell_ids
            * @param mixed $product
            */
            function on__woocommerce_product_upsell_ids( $upsell_ids, $product )
                {
                    
                    if( !isset($product->blog_id) )
                        return $upsell_ids;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    $upsell_ids        =   $_product->get_upsell_ids('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $upsell_ids;   
                    
                }
            
            
            
            /**
            * Return product review count
            * 
            * @param mixed $count
            * @param mixed $product
            */
            function on__woocommerce_product_review_count( $count, $product )
                {
                    
                    if( !isset($product->blog_id) )
                        return $count;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    global $wpdb;

                    // No meta date? Do the calculation
                    if ( ! metadata_exists( 'post', $this->id, '_wc_review_count' ) ) {
                        $count = $wpdb->get_var( $wpdb->prepare("
                            SELECT COUNT(*) FROM $wpdb->comments
                            WHERE comment_parent = 0
                            AND comment_post_ID = %d
                            AND comment_approved = '1'
                        ", $this->id ) );

                        update_post_meta( $this->id, '_wc_review_count', $count );
                    } else {
                        $count = get_post_meta( $this->id, '_wc_review_count', true );
                    }
                    
                    restore_current_blog();
                        
                    return $count;
               
                }
            
            
            
            
            /**
            * Return product crossell ids
            * 
            * @param mixed $crosssell_ids
            * @param mixed $product
            */
            function on__woocommerce_product_crosssell_ids($crosssell_ids, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $crosssell_ids;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $crosssell_ids        =   $_product->get_crosssell_ids('woogc-filter');
                    
                    restore_current_blog();
                        
                    return $crosssell_ids;
                    
                }
            
                
            /**
            * Return if product is virtual
            * 
            * @param mixed $virtual
            * @param mixed $product
            */
            function on__woocommerce_is_virtual($virtual, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $virtual;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $virtual        =   $_product->is_virtual('woogc-filter') == 'yes' ? true : false;
                    
                    restore_current_blog();
                        
                    return $virtual;
                    
                }
                

            
            /**
            * Return product length
            * 
            * @param mixed $length
            * @param mixed $product
            */
            function on__woocommerce_product_length($length, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $length;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $length        =   '' === $_product->get_length('woogc-filter') ? '' : wc_format_decimal( $_product->get_length('woogc-filter') );
                    
                    restore_current_blog();
                        
                    return $length;
                    
                }
                

                
            /**
            * Return product width
            * 
            * @param mixed $width
            * @param mixed $product
            */
            function on__woocommerce_product_width($width, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $width;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $width        =   '' === $_product->get_width('woogc-filter') ? '' : wc_format_decimal( $_product->get_width('woogc-filter') );
                    
                    restore_current_blog();
                        
                    return $width;
                    
                }
                
            
            /**
            * Return product height
            * 
            * @param mixed $height
            * @param mixed $product
            */
            function on__woocommerce_product_height($height, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $height;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $height        =   '' === $_product->get_height('woogc-filter') ? '' : wc_format_decimal( $_product->get_height('woogc-filter') );
                    
                    restore_current_blog();
                        
                    return $height;
                    
                }
                

            
            /**
            * Return product weight
            * 
            * @param mixed $weight
            * @param mixed $product
            */
            function on__woocommerce_product_weight($weight, $product)
                {
                    
                    if( !isset($product->blog_id) )
                        return $weight;
                    
                    switch_to_blog( $product->blog_id );
                    
                    $_product = wc_get_product( $product->get_id() );
                    
                    $weight        =   '' === $_product->get_weight('woogc-filter') ? '' : wc_format_decimal( $_product->get_weight('woogc-filter') );
                    
                    restore_current_blog();
                        
                    return $weight;
                    
                }
                
                
            /**
            * Ensure the attributes for products from other shops are feetched correctlly.
            *     
            * @param mixed $item_data
            * @param mixed $cart_item
            */
            function woocommerce_get_item_data( $item_data, $cart_item )
                {
                    global $wp_taxonomies, $wc_product_attributes;
                    
                    if ( ! is_array ( $item_data ) )
                        $item_data = array();
                    
                    $_default_wc_product_attributes =   $wc_product_attributes;
                    
                    // Variation values are shown only if they are not found in the title as of 3.0.
                    // This is because variation titles display the attributes.
                    if ( $cart_item['data']->is_type( 'variation' ) && is_array( $cart_item['variation'] ) ) 
                        {
                            foreach ( $cart_item['variation'] as $name => $value ) 
                                {
                                    if ( isset ( $cart_item['blog_id'] ) )
                                        switch_to_blog( $cart_item['blog_id'] );
                                    
                                    $_wc_product_attributes = wc_get_attribute_taxonomies();
                                    $wc_product_attributes  =   array();
                                    foreach  ( $_wc_product_attributes   as  $data )
                                        $wc_product_attributes[ 'pa_' . $data->attribute_name]    =   $data;
                                    
                                    $taxonomy   =   ltrim( $name, 'attribute_');   
                                     
                                    // If this is a term slug, get the term's nice name
                                    if ( taxonomy_exists( $taxonomy ) ) 
                                        {
                                            $term = get_term_by( 'slug', $value, $taxonomy );
                                            if ( ! is_wp_error( $term ) && $term && $term->name ) 
                                                {
                                                    $value = $term->name;
                                                }
                                            $label = wc_attribute_label( $taxonomy );

                                        // If this is a custom option slug, get the options name.
                                        } 
                                    else 
                                        {
                                            $value = apply_filters( 'woocommerce_variation_option_name', $value );
                                            $label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
                                        }

                                    // Check the nicename against the title, ensure is not being used already in title
                                    if ( '' === $value || stristr( $cart_item['data']->get_name(), $value ) ) 
                                        {
                                            if ( isset ( $cart_item['blog_id'] ) )
                                                restore_current_blog();
                                                
                                            continue;
                                        }

                                    $item_data[] = array(
                                                            'key'   => $label,
                                                            'value' => $value,
                                                        );
                                    
                                    if ( isset ( $cart_item['blog_id'] ) )                    
                                        restore_current_blog();
                                    
                                }
                        }    
                    
                    
                    //restore the default
                    $wc_product_attributes  =   $_default_wc_product_attributes;
                    
                    return $item_data;
                        
                }
                
                
                
            /**
             * Get downloads from all line items for this order.
             *
             * @since  3.2.0
             * @return array
             */
            public function woocommerce_order_get_downloadable_items( $downloads, $order ) 
                {
                    global $blog_id;
                    
                    $downloads = array();

                    foreach ( $order->get_items() as $item ) 
                        {
                            if ( ! is_object( $item ) ) {
                                continue;
                            }
                            
                            
                            if ( $item->is_type( 'line_item' ) ) {
                                $item_downloads = $item->get_item_downloads();
                                
                                switch_to_blog( $item->get_meta('blog_id') );
                                
                                $product        = $item->get_product();
                                if ( $product && $item_downloads ) {
                                    foreach ( $item_downloads as $file ) {
                                        $downloads[] = array(
                                            'download_url'        => $file['download_url'] . '&sid=' . $item->get_meta('blog_id') ,
                                            'download_id'         => $file['id'],
                                            'product_id'          => $product->get_id(),
                                            'product_name'        => $product->get_name(),
                                            'product_url'         => $product->is_visible() ? $product->get_permalink() : '', // Since 3.3.0.
                                            'blog_id'             => ( $item->get_meta('blog_id') > 0 ) ?   $item->get_meta('blog_id')  :   $blog_id,    
                                            'download_name'       => $file['name'],
                                            'order_id'            => $order->get_id(),
                                            'order_key'           => $order->get_order_key(),
                                            'downloads_remaining' => $file['downloads_remaining'],
                                            'access_expires'      => $file['access_expires'],
                                            'file'                => array(
                                                'name' => $file['name'],
                                                'file' => $file['file'],
                                            ),
                                        );
                                    }
                                }
                                
                                restore_current_blog();
                            }
                        }

                    return $downloads;
                }
               
                 
        }

    new WooGC_Template();

?>