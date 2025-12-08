<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_general_filters 
        {
            
            var $functions;
               
            function __construct()   
                {
                    global $WooGC;
                    
                    $this->functions    =   $WooGC->functions;
                    
                    $options    =   WooGC_Functions::get_options();
                    
                    add_filter( 'woocommerce_cart_item_product',                array($this, 'woocommerce_cart_item_product'), 99, 3 );
                                        
                    //exclude blog_id when retreiving formated order product meta 
                    add_filter('woocommerce_order_items_meta_get_formatted',    array($this, 'woocommerce_order_items_meta_get_formatted'), 999, 2);
                    
                    
                    add_filter('wp_loaded',                                     array ( $this, 'wp_loaded'));  
                    
                    
                    add_filter('woocommerce_cart_subtotal',                     array ( $this, 'woocommerce_cart_subtotal' )  , 999, 3 );
                    add_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                    
                    add_filter('woocommerce_product_add_to_cart_url',           array ( $this, 'woocommerce_product_add_to_cart_url'), 99, 2 );
                    
                    //check on Replace the Cart Products with origin version
                    add_filter ( 'woogc/get_cart_from_session/cart_item/values', array ( $this, 'replace_cart_product_with_origin_version' ) , 10, 2 );
                    
                    //check on Replace the Cart Products with local version
                    add_filter ( 'woogc/get_cart_from_session/cart_item/values', array ( $this, 'replace_cart_product_with_local_version' ) , 10, 2 );
                    
                    //There is no filter to disable the Store Rest API stock reserve method
                    if( defined( 'WOOGC_SINGLE_CHECKOUT' )  &&  WOOGC_SINGLE_CHECKOUT   === TRUE  )
                        {
                            add_filter( 'option_woocommerce_schema_version',            array ( $this, 'store_rest_api_reservestock_option_woocommerce_schema_version' ) );
                            add_filter( 'woocommerce_variation_is_purchasable',         array ( $this, 'store_rest_api_woocommerce_variation_is_purchasable' ), 99, 2 );
                            add_filter( 'woocommerce_variation_is_visible',             array ( $this, 'store_rest_api_woocommerce_variation_is_visible' ), 99, 4 );
                        }
                        
                        
                    add_action( 'init',                                     array( $this, 'on_action__init') );
                    add_filter('woocommerce_add_cart_item',                 array( $this, 'woocommerce_add_cart_item'),             10, 2 );
                    add_filter('woocommerce_cart_id',                       array( $this, 'woocommerce_cart_id'),  999, 5 );
                    
                    //ensure the order search is checking the _order_number meta field
                    add_filter( 'woocommerce_shop_order_search_fields',      array( $this, 'woocommerce_shop_order_search_fields') );
                    
                    global $blog_id; 
                    
                    //Show the Sync Order in name
                    if ( $options['enable_order_synchronization']   === 'yes'   &&  $options['order_synchronization_to_shop'] > 0  &&  $options['order_synchronization_to_shop']   ==  $blog_id )
                        add_filter( 'woocommerce_admin_order_buyer_name',      array( $this, 'order_sync_admin_order_buyer_name'), 10, 2 );
                        
                    
                    //avoid the duplicated checkout filters (e.g. woocommerce_checkout_shipping )
                    add_filter( 'woocommerce_before_checkout_form', array( $this, 'woocommerce_before_checkout_form'), -1 );
                    
                }
                       
            
            function wp_loaded()
                {
                    add_action('switch_blog',                                   array( $this, 'switch_blog'), 999, 2 );
                }
                
            function woocommerce_cart_item_product ( $cart_item_data, $cart_item, $cart_item_key )
                {
                    
                    if (    !isset($cart_item['blog_id'])    ||  $cart_item['blog_id']   < 1   )
                        return $cart_item_data;
                    
                    global $blog_id;
                    
                    if ( $cart_item['blog_id'] ==   $blog_id )
                        return $cart_item_data;
                    
                    $product_id     =   $cart_item_data->get_ID();
                        
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $cart_item_data =   wc_get_product( $product_id ) ;
                    
                    restore_current_blog();
                       
                    return $cart_item_data;   
                }
                
                            
                
            function woocommerce_order_items_meta_get_formatted( $formatted_meta, $WC_Order_Item_Meta_Object )
                {
                    
                    foreach ( $formatted_meta   as  $key    =>  $formatted_item_meta )
                        {
                            
                            if ( $formatted_item_meta['key']    ==  'blog_id' )
                                unset( $formatted_meta[$key] );
                            
                        }
                    
                    return $formatted_meta;
                       
                }
                
                
            /**
            * Attempt to populate the txonomies with appropiate data for current site.
            *     
            * @param mixed $new_blog
            * @param mixed $prev_blog_id
            */
            function switch_blog( $new_blog, $prev_blog_id )  
                {
                    global $wp_taxonomies, $wp_switch_taxonomies_stack;
                    
                    if  ( ! is_array ( $wp_switch_taxonomies_stack ) )
                        $wp_switch_taxonomies_stack =   array(); 
                    
                    if  ( ! isset( $wp_switch_taxonomies_stack[$prev_blog_id] ) )
                        $wp_switch_taxonomies_stack[$prev_blog_id]  =   $wp_taxonomies;
                        
                    if ( isset( $wp_switch_taxonomies_stack[$new_blog] ) )
                        {
                            $wp_taxonomies  =   $wp_switch_taxonomies_stack[$new_blog];
                            return;   
                        }
                        
                    //attempt to create a list of taxonomies
                    global $wpdb;
                    $mysql_query    =   "SELECT taxonomy FROM " . $wpdb->term_taxonomy . " GROUP BY taxonomy";
                    $results        =   $wpdb->get_results( $mysql_query );
                    
                    foreach ( $results  as  $result )
                        {
                            if  ( isset ( $wp_taxonomies[ $result->taxonomy ] ))
                                continue;
                            
                            $name   =   $result->taxonomy;
                            $taxonomy_data  = array();
                            
                            if ( strpos($result->taxonomy, 'pa_' ) === 0 )
                                {
                                    $label  =   str_replace("pa_", "", $result->taxonomy);
                                    $label  =   ucfirst( $label );
                                    
                                    $taxonomy_data  = array(
                                                                'hierarchical'          => false,
                                                                'update_count_callback' => '_update_post_term_count',
                                                                'labels'                => array(
                                                                    /* translators: %s: attribute name */
                                                                    'name'              => sprintf( _x( 'Product %s', 'Product Attribute', 'woocommerce' ), $label ),
                                                                    'singular_name'     => $label,
                                                                    /* translators: %s: attribute name */
                                                                    'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'add_new_item'      => sprintf( __( 'Add new %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'not_found'         => sprintf( __( 'No &quot;%s&quot; found', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'back_to_items'     => sprintf( __( '&larr; Back to "%s" attributes', 'woocommerce' ), $label ),
                                                                ),
                                                                'show_ui'               => true,
                                                                'show_in_quick_edit'    => false,
                                                                'show_in_menu'          => false,
                                                                'meta_box_cb'           => false,
                                                                'query_var'             => false,
                                                                'rewrite'               => false,
                                                                'sort'                  => false,
                                                                'public'                => false,
                                                                'show_in_nav_menus'     => false,
                                                                'capabilities'          => array(
                                                                    'manage_terms' => 'manage_product_terms',
                                                                    'edit_terms'   => 'edit_product_terms',
                                                                    'delete_terms' => 'delete_product_terms',
                                                                    'assign_terms' => 'assign_product_terms',
                                                                ),
                                                            );
                                }
                            
                            //presume is being used by product post type    
                            $new_taxonomy = new WP_Taxonomy( $name, 'product', $taxonomy_data );
                            
                            $wp_taxonomies[ $name ]   =   $new_taxonomy;
                            
                        }   
                    
                    $wp_switch_taxonomies_stack[$new_blog]  =   $wp_taxonomies;
                }
                
                
            
            /**
            * Group/format the SubTotal price, if the shops use different curencies
            * 
            * @param mixed $cart_subtotal
            * @param mixed $compound
            * @param mixed $cart
            */
            function woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart )
                {
                    $options    =   $this->functions->get_options();   
                    if( $options['cart_checkout_type']  !=  'each_store' )
                        return $cart_subtotal;
                    
                    //check what curencies each of the sites with a product in the cart, uses 
                    $currency_map   =   array();
                    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            if ( ! isset( $currency_map[ $shop_currency ] ))
                                $currency_map[ $shop_currency ]     =   array( $cart_item['blog_id'] );
                                else
                                $currency_map[ $shop_currency ][]     =   $cart_item['blog_id']; 
                            restore_current_blog();
                        }

                    if ( count  ( $currency_map )  === 1  )
                        {
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            reset ( $currency_map );
                            
                            $cart_item_currency = key ( $currency_map );
                            
                            if ( $cart_item_currency    ==  $shop_currency )
                                return $cart_subtotal;
                        }
                    
                    $prices =   array();
                    
                    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            $item_subtotal  =   0;
                            if ( $compound ) {
                                $item_subtotal =    $cart_item['line_total'] + $cart->get_shipping_total() + $cart_item['line_tax'];

                            } elseif ( $cart->display_prices_including_tax() ) {
                                $item_subtotal =    $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];

                            } else {
                                $item_subtotal =    $cart_item['line_subtotal'];
                            }        
                    
                            if ( isset ( $prices[ $shop_currency ] ) )
                                $prices[ $shop_currency ]   +=  $item_subtotal;
                                else
                                $prices[ $shop_currency ]   =  $item_subtotal;
                    
                            restore_current_blog();
                        }
                    
                    $cart_subtotal  =   '';
                    
                    foreach  ( $prices  as  $currency => $price )
                        {
                            if ( ! empty ( $cart_subtotal ) )
                                $cart_subtotal  .=  ' &#43; ';
                            $cart_subtotal  .=   wc_price ( $price , array ( 'currency'           => $currency ) );
                        }
                        
                    return $cart_subtotal;
                       
                }
                
            
            
            /**
            * Group/format the SubTotal price, if the shops use different curencies
            *     
            * @param mixed $value
            */
            function woocommerce_cart_totals_order_total_html( $total_value )
                {
                    $options    =   $this->functions->get_options();   
                    if( $options['cart_checkout_type']  !=  'each_store' )
                        return $total_value;
                        
                    //check what curencies each of the sites with a product in the cart, uses 
                    $currency_map   =   array();
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            if ( ! isset( $currency_map[ $shop_currency ] ))
                                $currency_map[ $shop_currency ]     =   array( $cart_item['blog_id'] );
                                else
                                $currency_map[ $shop_currency ][]     =   $cart_item['blog_id']; 
                            restore_current_blog();
                        }
                    
                    if ( count  ( $currency_map )  === 1  )
                        {
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            reset ( $currency_map );
                            
                            $cart_item_currency = key ( $currency_map );
                            
                            if ( $cart_item_currency    ==  $shop_currency )
                                return $total_value;
                        }
                               
                    $prices =   array();
                    
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                    
                            if ( isset ( $prices[ $shop_currency ] ) )
                                $prices[ $shop_currency ]   +=  $cart_item['line_total'];
                                else
                                $prices[ $shop_currency ]   =  $cart_item['line_total'];
                                
                            if ( wc_tax_enabled() )
                                {
                                    $prices[ $shop_currency ]   +=  $cart_item['line_tax'];
                                }
                    
                            restore_current_blog();
                        }
                    
                    $local_total    =   FALSE;
                    $shop_currency  =   get_option('woocommerce_currency');
                    if ( count  ( $currency_map )  > 1  )
                        {
                            //check if there's any curency in the map
                            if ( isset( $currency_map[ $shop_currency ] ))
                                {
                                    $include_shops  =   $currency_map[ $shop_currency ];
                                    $include_shops  =   array_unique($include_shops);
                                    
                                    $default_cart_contents  =   WC()->cart->cart_contents;
                    
                                    foreach ( WC()->cart->cart_contents as  $cart_item_hash =>  $cart_item )
                                        {
                                            if ( ! in_array( $cart_item['blog_id'], $include_shops))
                                                unset( WC()->cart->cart_contents[$cart_item_hash] );
                                        }
                                    WC()->cart->calculate_totals();
                                    
                                    remove_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                                    
                                    ob_start();
                                    wc_cart_totals_order_total_html();
                                    $local_total =    ob_get_contents();
                                    ob_clean();
                                    add_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                                    
                                    //restore
                                    WC()->cart->cart_contents   =   $default_cart_contents;
                                    WC()->cart->calculate_totals();
                                }
                        }
                    
                    
                    $total_value  =   '';
                    
                    foreach  ( $prices  as  $currency => $price )
                        {
                                
                            if ( empty ( $total_value ) )
                                $total_value  =   '<strong>';
                                else
                                $total_value  .=  ' &#43; ';
                                
                            if  ( $currency == $shop_currency   &&  $local_total    !== FALSE )
                                {
                                    $total_value  .=    $local_total;
                                    continue;
                                }
                                
                            $total_value  .=   wc_price ( $price , array ( 'currency'           => $currency ) );
                        }
                    
                    //if the local currency not in the $prices, add the other fees to total
                    if ( ! isset ( $prices[ $shop_currency ] ))
                        {    
                            $cart_totals = WC()->cart->get_totals( );
                            $total_value  .= ' &#43; ' . wc_price ( (float)$cart_totals['total'] - (float)$cart_totals['cart_contents_total'], array ( 'currency'           => $shop_currency ) );
                        }
                    
                    $total_value  .=   '</strong>';
                        
                    return $total_value;   
                    
                    
                }
                
                
            /**
            * Get the add to url used mainly in loops.
            *     
            * @param mixed $url
            * @param mixed $product
            */
            function woocommerce_product_add_to_cart_url( $url, $product )
                {
                    
                    if ( isset ( $product->_context ) &&  $product->_context === 'woogc_shortcode' )
                        {
                            switch ( $product->get_type() )
                                {
                                    case 'external' :
                                                        $url    =   $product->get_permalink();
                                                        break;
                                                        
                                    case 'variable' :
                                                        $url    =   $product->get_permalink();
                                                        break;
                                    
                                    case 'simple' : 
                                                        $url = $product->is_purchasable() && $product->is_in_stock() ? remove_query_arg(
                                                                'added-to-cart',
                                                                add_query_arg(
                                                                    array(
                                                                        'add-to-cart' => $product->get_id(),
                                                                    ),
                                                                    $product->get_permalink()
                                                                )
                                                            ) : $product->get_permalink();
                                                        break;
                                    
                                }
                        }
                        else
                        {
                            //attempt to add the absolute url instead the query argument of the url.
                            if ( strpos( $url, '?' ) === 0 )
                                {
                                    $product_url    =   $product->get_permalink();                                    
                                    $url            =   $product_url    .   $url;   
                                }
                        }
                    
                    return $url;    
                }
            
            
            /**
            * Check for Replace the Cart Products with origin version
            *     
            * @param mixed $values
            * @param mixed $key
            */
            function replace_cart_product_with_origin_version( $values, $key )
                {
                    global $WooGC, $blog_id;
                    $options    =   $WooGC->functions->get_options();
                    
                    if ( $options['replace_cart_product_with_origin_version']    !=  'yes' )
                        return $values;
                    
                    $cart_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
                    
                    if ( ! is_object( $cart_product ) )
                        return $values;
                        
                    $_woogc_ps_is_child_of_pid  =   $cart_product->get_meta( '_woogc_ps_is_child_of_pid' );
                    $_woogc_ps_is_child_of_bid  =   $cart_product->get_meta( '_woogc_ps_is_child_of_bid' );
                    
                    if ( empty ( $_woogc_ps_is_child_of_pid )   ||  empty ( $_woogc_ps_is_child_of_bid ) )
                        return $values;
                    
                    switch_to_blog( $_woogc_ps_is_child_of_bid );
                        
                    $origin_product =   wc_get_product( $_woogc_ps_is_child_of_pid );
                    if ( is_object( $origin_product ) )   
                        {   
                            $values['blog_id']          =   $_woogc_ps_is_child_of_bid;
                            $values['product_id']       =   $_woogc_ps_is_child_of_pid;
                            $values['line_subtotal']    =   $origin_product->get_price()  *  $values['quantity'] ;
                            $values['line_total']       =   $origin_product->get_price()  *  $values['quantity'] ;    
                        }
                    
                    restore_current_blog();
                                            
                    return $values;
                    
                }
                
            
            /**
            * Check for Replace the Cart Products with local version
            *     
            * @param mixed $values
            * @param mixed $key
            */
            function replace_cart_product_with_local_version( $values, $key )
                {
                    global $WooGC, $blog_id;
                    $options    =   $WooGC->functions->get_options();
                    
                    if ( ! isset ( $values['blog_id'] ) )
                        return $values;    
                    
                    if ( $options['replace_cart_product_with_local_version']    !=  'yes'   ||  $blog_id    ==  $values['blog_id'] )
                        return $values;
                    
                    $product_sku    =   '';
                    
                    if ( isset ( $values['blog_id'] ) )
                        switch_to_blog( $values['blog_id'] );
                    
                    $cart_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
                    if ( is_object( $cart_product ) )
                        $product_sku    =   $cart_product->get_sku();
                    
                    if ( isset ( $values['blog_id'] ) )
                        restore_current_blog();
                    
                    if ( empty ( $product_sku ) )
                        return $values;
                    
                    $local_product_id   =   wc_get_product_id_by_sku( $product_sku );
                    if ( empty ( $local_product_id ) )
                        return $values;
                    
                    $local_product      =   wc_get_product( $local_product_id );
                    if ( ! is_object ( $local_product ) )
                        return $values;
                    
                    if ( $local_product->get_type() !=  $cart_product->get_type() )
                        return $values;
                        
                    $values['blog_id']          =   $blog_id;
                    
                    if ( $local_product->get_type() === 'variation' )
                        {
                            $values['product_id']       =   $local_product->get_parent_id();
                            $values['variation_id']     =   $local_product_id;   
                        }
                        else
                        {
                            $values['product_id']       =   $local_product_id;
                            $values['variation_id']     =   0;
                        }
                    
                    $values['line_subtotal']    =   $local_product->get_price()  *  $values['quantity'] ;
                    $values['line_total']       =   $local_product->get_price()  *  $values['quantity'] ;
                        
                    return $values;
                    
                }
                
            
            function store_rest_api_reservestock_option_woocommerce_schema_version( $value )
                {
                    if (  ! WooGC_Functions::check_backtrace_for_caller( array ( array ( '__construct', 'Automattic\WooCommerce\Checkout\Helpers\ReserveStock') ) ) )
                        return $value;
                    
                    return 1;   
                }
                
                
            function store_rest_api_woocommerce_variation_is_purchasable( $is_purchasable, $product ) 
                {
                    
                    
                    return $is_purchasable;
                }                                                              
                
            function store_rest_api_woocommerce_variation_is_visible( $is_visible, $product_id, $product_parent_id, $product ) 
                {
                    if ( $is_visible )   
                        return $is_visible;
                    
                    global $blog_id; 
                    
                    $product_blog_id    =   $product->get_meta('blog_id', TRUE);
                    if ( $product_blog_id > 0   &&  $product_blog_id    !=  $blog_id )
                        {
                            switch_to_blog( $product_blog_id );
                            
                            $is_visible =   'publish' === get_post_status( $product->get_id() ) && '' !== $product->get_price();
                            
                            restore_current_blog();
                        }
                    
                    return $is_visible;
                }
                
                
                
                
            /**
            * Trigger on WordPress Init action
            * 
            */
            function on_action__init( )
                {
                    
                    //custom handler for undo_item
                    add_action('woocommerce_restore_cart_item',             array( $this, 'woocommerce_restore_cart_item' ), 999, 2 );
        
                }
                       
            
            
            function woocommerce_restore_cart_item( $cart_item_key, $cart )
                {
                    
                    $cart->cart_contents[ $cart_item_key ] = $cart->removed_cart_contents[ $cart_item_key ];
                    
                    $cart_item  =   $cart->cart_contents[ $cart_item_key ];
                    
                    if(!isset($cart_item['blog_id']))
                        return;
                        
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $cart->cart_contents[ $cart_item_key ]['data'] = wc_get_product( $cart->cart_contents[ $cart_item_key ]['variation_id'] ? $cart->cart_contents[ $cart_item_key ]['variation_id'] : $cart->cart_contents[ $cart_item_key ]['product_id'] );                   
                    
                    restore_current_blog();   
                    
                }
            
            
            function woocommerce_add_cart_item( $cart_item_data, $cart_item_key )
                {
                    
                    global $blog_id;
            
                    $cart_item_data['blog_id']          =   absint($blog_id);
                    //$cart_item_data['data']->site_id    =   absint($blog_id);
                    
                    return $cart_item_data;   
                    
                }

            
                
            function woocommerce_cart_id( $cart_item_id, $product_id, $variation_id, $variation, $cart_item_data)
                {
                    
                    global $blog_id;
                    
                    $id_parts = array( $product_id );

                    if ( $variation_id && 0 != $variation_id ) 
                        {
                            $id_parts[] = $variation_id;
                        }

                    if ( is_array( $variation ) && ! empty( $variation ) ) 
                        {
                            $variation_key = '';
                            foreach ( $variation as $key => $value ) 
                                {
                                    $variation_key .= trim( $key ) . trim( $value );
                                }
                            $id_parts[] = $variation_key;
                        }

                    if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) 
                        {
                            $cart_item_data_key = '';
                            foreach ( $cart_item_data as $key => $value ) 
                                {
                                    if ( is_array( $value ) || is_object( $value ) ) 
                                        {
                                            $value = http_build_query( $value );
                                        }
                                    $cart_item_data_key .= trim( $key ) . trim( $value );

                                }
                            $id_parts[] = $cart_item_data_key;
                        }
                        
                    $id_parts[] =   $blog_id;
                    
                    $cart_item_id   =   md5( implode( '_', $id_parts ) );
                    
                    return $cart_item_id;
                       
                }
                
            
            /**
            * Add additional meta field sin the search query when looking up for orders
            *     
            * @param mixed $search_fields
            */
            function woocommerce_shop_order_search_fields( $search_fields )
                {
                    $search_fields[] = '_order_number';

                    return $search_fields;   
                }
                
                
            
            /**
            * Shot the Ordr Sync notice in the order title, if apply
            * 
            * @param mixed $title
            * @param mixed $order
            */
            function order_sync_admin_order_buyer_name ( $title, $order )
                {
                    
                    $shop_id_origin     =   $order->get_meta ( '_woogc_origin_shop' );
                    $order_id_origin    =   $order->get_meta ( '_woogc_origin_order_id' );
                    
                    if ( empty ( $shop_id_origin )  ||  empty ( $order_id_origin ) )
                        return $title;
                    
                    $title  .=  ' ( origin Shop#'. $shop_id_origin .' Order#'. $order_id_origin .' )';
                    
                    return $title;
                }
                
                
            function woocommerce_before_checkout_form()
                {
                    global $wp_filter;

                    $hook_names =   array ( 
                                            'checkout_form_shipping'    =>  'woocommerce_checkout_shipping',
                                            'checkout_form_billing'     =>  'woocommerce_checkout_billing'
                                            );

                    foreach ( $hook_names   as  $hook_function_name  =>  $hook_name ) 
                        {
                            $_default_filter    =   FALSE;
                            $_extend_filter     =   FALSE;
                            
                            if ( isset( $wp_filter[ $hook_name ] ) ) 
                                {
                                    foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) 
                                        {
                                            foreach ( $callbacks as $callback ) 
                                                {
                                                    if ( is_array( $callback['function'] )  &&  is_object ( $callback['function'][0] ) ) {
                                                        $function_name = get_class( $callback['function'][0] ) . '::' . $callback['function'][1];
                                                    } elseif ( is_string( $callback['function'] ) ) {
                                                        $function_name = $callback['function'];
                                                    } else {
                                                        $function_name = 'Unknown callback type';
                                                    }

                                                    if ( $function_name === 'WC_Checkout::' . $hook_function_name )
                                                        $_default_filter    =   TRUE;
                                                    if ( $function_name === 'WOOGC_WC_Checkout::' . $hook_function_name )
                                                        $_extend_filter =   TRUE;
                                                }
                                        }
                                        
                                    if ( $_default_filter   &&  $_extend_filter )
                                        {
                                            WooGC_Functions::remove_anonymous_object_filter( $hook_name, 'WC_Checkout', $hook_function_name );
                                        }
                                }
                        }
                }
            
        }


    new WooGC_general_filters();
        
?>