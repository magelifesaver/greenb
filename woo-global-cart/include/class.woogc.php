<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC 
        {
            
            var $functions;
            
            var $licence;
            
            var $user_login_logout_action       =   FALSE;
            var $user_login_sso_hash            =   FALSE;
            
            var $cache  =   array();
            
            var $ps_interfaces;
            
            function __construct()
                {
                    
                    $this->functions    =   new WooGC_Functions();
                
                    $this->licence      =   new WooGC_licence();

                    add_filter( 'woogc/disable_global_cart',     array ( $this , 'disable_global_cart' ), 10, 2 );
                    
                    $this->functions->check_required_structure();
                
                }    
            
            function init()
                {
                    //Admin
                    if(is_admin())
                        {
                            //options interface
                            include_once(WOOGC_PATH . '/include/class.woogc.options.php');
                            
                            //plugin core updater check
                            include_once(WOOGC_PATH . '/include/class.woogc.updater.php');
                        }
                        
                    if(!$this->licence->licence_key_verify())
                        return FALSE;
                        
                    global $blog_id;
                    
                    /**
                    * Check for specific features / functionality disable
                    */
                    $_WooGC_Disable_GlobalCart  =   apply_filters( 'woogc/disable_global_cart',     FALSE);
                        
                    // Check if WooCommerce is enabled
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php' ) )
                        {
                            if ( ! $_WooGC_Disable_GlobalCart )
                                {
                                    add_action( 'admin_notices',                array( $this, 'WC_disabled_notice' ));
                                    add_action( 'network_admin_notices',        array( $this, 'WC_disabled_notice' ));
                                }
        
                            return FALSE;
                        }
                    
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                        {
                            //return;
                            $_WooGC_Disable_GlobalCart  =   TRUE;
                        }
                    
                    $options    =   $this->functions->get_options();
                    
                    if( $_WooGC_Disable_GlobalCart  === FALSE )
                        {
                            if( $options['cart_checkout_type']  ==  'single_checkout' )
                                define( 'WOOGC_SINGLE_CHECKOUT', TRUE );
                            if( $options['cart_checkout_type']  ==  'each_store' )
                                define( 'WOOGC_EACH_STORE_CHECKOUT', TRUE );
                            
                            if( defined ( 'WOOGC/SHIPPING/COSTS_EACH_SHOP' ) && $options['calculate_shipping_costs_for_each_shops']  ==  'yes' )
                                {
                                    define( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP', TRUE );
                                    
                                    if ( $options['calculate_shipping_costs_for_each_shops__site_base_tax']  > 0 )
                                        define ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX', $options['calculate_shipping_costs_for_each_shops__site_base_tax'] );
                                }
                            
                            //general filters
                            include_once(WOOGC_PATH . '/include/class.woogc.general-filters.php');
                                                        
                            add_action( 'woocommerce_init',                      array($this, 'woocommerce_init'));
                            
                            //replace default session manager
                            add_filter( 'woocommerce_session_handler',           array( $this, 'woocommerce_session_handler' ), 999 ); 
                  
                            //if( defined ( 'DOING_AJAX' ) )
                                {
                                    //AJAX calls 
                                    include(WOOGC_PATH . '/include/class.woogc.ajax.php');
                                    new WooGC_AJAX();
                                }
                            
                            if ( $options['synchronization_type']  ==  'screen' )
                                include_once ( WOOGC_PATH . '/include/class.woogc.synchronization-screen.php');
                            
                            //include dependencies
                            include_once(WOOGC_PATH . '/include/class.woogc.form-handler.php');
                            
                        }
                        
                    include_once ( WOOGC_PATH . '/include/class.woogc.compatibility.php');

                    if( is_admin() )
                        {                            
                            //include internal update procedures on update
                            include_once(WOOGC_PATH . '/include/class.woogc.on-update.php');
                            
                            //admin notices
                            add_action( 'admin_notices',                array(&$this, 'on__admin_notices'));
                            add_action( 'network_admin_notices',        array(&$this, 'on__admin_notices'));
                            
                        }
                        
                    //Product Syncronization
                    if( file_exists ( WOOGC_PATH . '/include/product-sync/class.woogc.ps.php' )     &&  $options['enable_product_synchronization']  ==  'yes' )
                        {
                            include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.php');
                            include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.ajax.php');
                            include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.main-product.php');
                            include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.child-product.php');
                            
                            if ( $options['product_synchronization_op_type']  ==  'cron_async' )
                                {
                                    include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.async.php');
                                    WooGC_PS_Async::register_actions();
                                }
                                
                            include_once(WOOGC_PATH . '/include/product-sync/class.woogc.ps.interfaces.php');
                            if ( is_admin() )
                                {
                                    $this->ps_interfaces    =   new WooGC_PS_interfaces();
                                    $this->ps_interfaces->register_actions();
                                }
                        }
                                    
                    if( is_admin() &&   ! is_network_admin() )
                        {
                            if ( $_WooGC_Disable_GlobalCart  === FALSE )
                                include_once ( WOOGC_PATH . '/include/admin/class.admin.php');
                                
                            include_once(WOOGC_PATH . '/include/class.woogc.admin-menus.php');
                            new WooGC_admin_menus();
                        }
                        
                    //network stuff
                    if( is_network_admin() )
                        {
                            include_once(WOOGC_PATH . '/include/class.woogc.admin-menus.php');
                            new WooGC_admin_menus();
                        }

                    
                    if( $_WooGC_Disable_GlobalCart  === FALSE )
                        { 
                            add_action( 'plugins_loaded',                       array( $this, 'on_plugins_loaded') );

                            add_action( 'init',                                 array( $this, 'gc_on_action_init') );
                            
                            //replicate the cart session to other blogs
                            add_action( 'shutdown',                             array( $this, 'on_action_shutdown_save__session_data' ), 9999 );
                            
                            add_filter ( 'woocommerce_get_order_item_classname', array( $this, 'woocommerce_get_order_item_classname' ), 999, 3 );
                            
                            //load the cart when REST API
                            add_filter( 'rest_authentication_errors', array( $this, 'maybe_init_cart_session' ), 10, 2 );
                            
                            //shiping
                            include_once ( WOOGC_PATH . '/include/shipping/class.shipping.php');
                            
                            //stock
                            include_once ( WOOGC_PATH . '/include/stock/class.stock.php');
                            
                            //Order
                            include_once ( WOOGC_PATH . '/include/order/class.order.php');
                                                            
                            //cart split                            
                            if( defined( 'WOOGC_SINGLE_CHECKOUT' )  &&  WOOGC_SINGLE_CHECKOUT   === TRUE  )
                                include_once ( WOOGC_PATH . '/include/checkout/class.single_checkout.php');
                            if( defined ( 'WOOGC_EACH_STORE_CHECKOUT' ) &&  WOOGC_EACH_STORE_CHECKOUT   === TRUE )
                                include_once ( WOOGC_PATH . '/include/cart-split/class.woogc.cart-split-core.php');
                                
                            if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) && defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX' ) )
                                include_once ( WOOGC_PATH . '/include/tax/class.tax.php');
                                
                                
                            //Order Synchronize feature
                            if ( $options['enable_order_synchronization']   === 'yes'    &&  is_array ( $options['order_synchronization_for_shops'] )  &&  isset ( $options['order_synchronization_for_shops'][ $blog_id ] )   &&   $options['order_synchronization_for_shops'][ $blog_id ] === 'yes'   
                                //&&  ! WooGC_Functions::is_rest_request() 
                                )
                                {
                                    include_once ( WOOGC_PATH . '/include/order/class-order-sync.php');
                                }
                        }
                        
                    //Templates filters
                    include_once ( WOOGC_PATH . '/include/template/class.template.php');
                    
                    add_action( 'init',                                 array( $this, 'on_action_init') );    
   
                    //Global Coupons
                    include_once ( WOOGC_PATH . '/include/coupons/class-coupons.php');
                    
                    //Shortcodes
                    include_once ( WOOGC_PATH . '/include/shortcodes/class.woogc.shortcodes.php');
                    
                    //Rest filters
                    if ( ! is_admin() )
                        {
                            include_once ( WOOGC_PATH . '/include/woo-rest-api/class-wp-rest-server.php');

                            if ( $options['enable_order_synchronization']   === 'yes'   &&  $options['order_synchronization_to_shop'] > 0  &&  $options['order_synchronization_to_shop']   ==  $blog_id )
                                include_once ( WOOGC_PATH . '/include/woo-rest-api/class-order-sync-api-server.php');
                        }
                        
                }
            
            
            /**
            * On woocommerce_init
            * 
            */
            function woocommerce_init()
                {
                    
                    //replace the default cart with an extended WC_Cart instance
                    include_once ( WOOGC_PATH . '/include/cart/class.wc-cart-extend.php');
                    include_once ( WOOGC_PATH . '/include/cart/class-wc-cart-totals.php');
                    include_once ( WOOGC_PATH . '/include/session/class-wc-cart-session-extend.php');
                    if(! is_null($GLOBALS['woocommerce']->cart))
                        {
                            $GLOBALS['woocommerce']->cart   =   new WOOGC_WC_Cart( );
                        }
                    
                    include_once(WOOGC_PATH . '/include/class-woogc-wc-product-factory.php');
                    WC()->product_factory =   new WooGC_WC_Product_Factory();
                        
                    add_action( 'woocommerce_checkout_init',            array( 'WOOGC_WC_Checkout', 'instance' ), 999 );

                    //replace the default checkout with an extended WC_Checkout instance
                    include_once ( WOOGC_PATH . '/include/checkout/class.wc-checkout-extend.php');
                    
                    include_once ( WOOGC_PATH . '/include/order/class-wc-order-item-product.php');
                    
                    //remove the default stock reservation
                    remove_action( 'woocommerce_checkout_order_created', 'wc_reserve_stock_for_order' );
                    
                }
            
            function maybe_init_cart_session( $return, $request = false )
                {
                    // Pass through other errors.
                    if ( ! empty( $error ) ) 
                        {
                            return $error;
                        }    
                    
                    if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) 
                        {
                            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
                            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
                            wc_load_cart();
                        }
                    
                    if( ! is_null($GLOBALS['woocommerce']->cart ) )
                        {
                            //$GLOBALS['woocommerce']->cart   =   new WOOGC_WC_Cart( TRUE );
                            $GLOBALS['woocommerce']->cart   =   new WOOGC_WC_Cart( );
                        }
                    
                    
                    return $return;
                    
                }
                  
            
            function on_plugins_loaded()
                {
                        
                    //turn on buffering
                    ob_start( array ( $this, 'on__shutdown' ) );
                    
                    include_once ( WOOGC_PATH . '/include/class-woogc-download-handler.php');
                        
                }
            
            
            /**
            * On WordPress shutdown
            * Change any checkout links to plugin option
            * 
            */
            function on__shutdown( $HTML )
                {
                    global $blog_id, $woocommerce;
                    
                    if( ! is_object( $woocommerce ) ||  !is_object( $woocommerce->cart ) )
                        return $HTML;
                       
                    $options    =   $this->functions->get_options();
                    $blog_details   =   get_blog_details( $blog_id );
         
                    //replace any checkout links
                    if( $options['cart_checkout_type']  ==  'single_checkout'  &&  !   empty($options['cart_checkout_location'])   &&  $options['cart_checkout_location']  !=  $blog_id)
                        {
                            $checkout_url   =   wc_get_checkout_url();
                            $checkout_url   =   str_replace(array('http:', 'https:'), "", $checkout_url);
                            $checkout_url   =   trailingslashit($checkout_url);
                            
                            $HTML   =   str_replace( "//"   .   $blog_details->domain .  untrailingslashit($blog_details->path) . "/checkout/", $checkout_url, $HTML);
                        
                        }
                        else if ( $options['cart_checkout_type']  ==  'each_store'  &&  isset ( $woocommerce->cart->cart_split ) )
                                {
                                    $checkout_url   =   $woocommerce->cart->cart_split->get_checkout_url();
                                    $checkout_url   =   str_replace(array('http:', 'https:'), "", $checkout_url);
                                    $checkout_url   =   trailingslashit($checkout_url);
                                    
                                    $HTML   =   str_replace( "//"   .   $blog_details->domain .  untrailingslashit($blog_details->path) . "/checkout/", $checkout_url, $HTML);
                                }
                    
                    return $HTML;
                }
            
            
            /**
            * Trigger on WordPress Init action
            * 
            */
            function gc_on_action_init( )
                {
                    
                    //unregistre certain WooCommerce filters and use custom
                    remove_action( 'wp_loaded',                 array( 'WC_Form_Handler', 'order_again' ), 20 );
                    remove_action( 'wp_loaded',                 array( 'WC_Form_Handler', 'update_cart_action' ), 20 );
                    remove_action( 'woocommerce_payment_complete', 'wc_maybe_reduce_stock_levels' );
                    
                    //register a custom one
                    add_action( 'wp_loaded',                    array( 'WooGC_Form_Handler', 'order_again' ), 20 );
                    add_action( 'wp_loaded',                    array( 'WooGC_Form_Handler', 'update_cart_action' ), 20 );

                }
                
            /**
            * Trigger on WordPress Init action
            * 
            */
            function on_action_init( )
                {
                                         
                    $options    =   $this->functions->get_options();
                    if($options['use_sequential_order_numbers'] ==  'yes')
                        include_once( WOOGC_PATH . '/include/class.woogc.sequential-order-numbers.php');
                    
                }
                  
            
            function on_action_shutdown_save__session_data( )
                {
                    
                    if(is_admin()   &&  ( ! defined('DOING_AJAX') ||  (defined('DOING_AJAX') &&  DOING_AJAX  === FALSE )))
                        return;
                    
                    global $wpdb, $blog_id, $woocommerce;
                    
                    $session_key    =   '';
                    
                    if( is_object( $woocommerce )    &&  is_object( $woocommerce->session ) )
                        $session_key        =   $woocommerce->session->get_customer_id();
                    
                    if ( empty ( $session_key ))
                        return;
                    
                    //check if there's a session saved
                    //retrieve the current session data
                    $mysql_query    =   $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . "woocommerce_sessions WHERE session_key = %s", $session_key );
                    $session_data   =   $wpdb->get_row( $mysql_query );

                    //if empty no need to continue
                    if ( !isset($session_data->session_id)   ||  empty($session_data->session_id) )
                        return;
                    
                    $options    =   $this->functions->get_options();
                                 
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                                        
                    $sites  =   $this->functions->get_gc_sites( TRUE );
                    foreach( $sites  as  $site )
                        {
                            if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                continue;
                                
                            if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) !== FALSE )
                                continue;
                                    
                            //no need to update current blog
                            if ( $blog_id    ==  $site->blog_id ) 
                                continue;
                             
                            switch_to_blog( $site->blog_id );
                            
                            //check if woocommerce is active for this site
                            if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php' ) )
                                {
                                    restore_current_blog();
                                    continue;
                                }
                            
                            //check if the table exists
                            $mysql_query    =   "SHOW tables LIKE '" . $wpdb->prefix . "woocommerce_sessions'";
                            $found_table    =   $wpdb->get_var( $mysql_query );
                            if ( empty ( $found_table ) )
                                {
                                    restore_current_blog();
                                    continue;
                                }
                                                                
                            $mysql_query    =   $wpdb->prepare( "SELECT session_id FROM ". $wpdb->prefix . "woocommerce_sessions WHERE session_key = %s", $session_key );
                            $session_id     =   $wpdb->get_var( $mysql_query );
                            
                            if( empty($session_id) )
                                {
                                    //add new entry    
                                    $mysql_query    =   $wpdb->prepare ( "INSERT INTO ". $wpdb->prefix . "woocommerce_sessions 
                                                            (`session_id`, `session_key`, `session_value`, `session_expiry`) 
                                                            VALUES (NULL, %s, %s, %s)", $session_key, $session_data->session_value, $session_data->session_expiry );
                                    $results        =   $wpdb->get_results( $mysql_query );
                                }
                                else
                                {
                                    //update the row   
                                    $mysql_query    =   $wpdb->prepare ( "UPDATE ". $wpdb->prefix . "woocommerce_sessions 
                                                                SET `session_value` =   %s, `session_expiry`    =   %s
                                                                WHERE session_id = %s", $session_data->session_value, $session_data->session_expiry, $session_id );
                                    $results        =   $wpdb->get_results( $mysql_query );
                                }
                                
                            restore_current_blog();
                            
                        }
                      
                    
                }

                
            function woocommerce_session_handler()
                {
                    $options    =   $this->functions->get_options();
                    
                    if ( $options['synchronization_type']  ==  'screen' )
                        include_once(WOOGC_PATH . '/include/class.woogc.wc-session-handler-screen.php');
                    else if ( $options['synchronization_type']  ==  'headers' )
                        include_once(WOOGC_PATH . '/include/class.woogc.wc-session-handler-headers.php');
                        
                    return 'WooGC_WC_Session_Handler';    
                    
                }
                
                
            function on__admin_notices()
                {
                    
                    if(! $this->functions->check_mu_files())
                        {
                            echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('Unable to copy woo-gc.php to mu-plugins folder. Is this directory writable?', 'woo-global-cart')  ."</p></div>";
                        }
                        
                    //check for MU module starter issues
                    global $WooGC__MU_Module;
                    
                    if  ( ! is_array($WooGC__MU_Module)  )
                        $WooGC__MU_Module   =   array();
                    
                    if(isset($WooGC__MU_Module['issues'])   &&  count( $WooGC__MU_Module['issues'] )   >   0 )
                        {
                            foreach($WooGC__MU_Module['issues'] as  $issue_code)
                                {
                                    switch($issue_code)
                                        {
                                            case 'e01'      :
                                                                echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('COOKIE_DOMAIN constant already defined. The Global Cart feature possibly not fully functional.', 'woo-global-cart')  ."</p></div>";
                                                                break;   
                                            
                                        }
                                }
                        }
                    
                    
                    //When using the Single Checkout type with Split, Ensure the HPOS has the same set-up everywhere
                    $options    =   $this->functions->get_options();
                    if ( $options['cart_checkout_type']  ==  'single_checkout' &&    $options['cart_checkout_split_orders']  ==  'yes' )
                        {
                            $current_HPOS_state =   '';
                            $wrong_setup        =   FALSE;
                            
                            $sites  =   $this->functions->get_gc_sites( TRUE );
                            foreach( $sites  as  $site )
                                {
                                    if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                        continue;
                                        
                                    if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) !== FALSE )
                                        continue; 
                                        
                                    switch_to_blog( $site->blog_id );
                                    
                                    if ( empty ( $current_HPOS_state ) )
                                        $current_HPOS_state =   $this->functions->is_HPOS_active();
                                        else if ( $this->functions->is_HPOS_active() !== $current_HPOS_state )
                                            {
                                                $wrong_setup    =   TRUE;
                                                restore_current_blog();
                                                break;    
                                            }
                                    
                                    restore_current_blog();
                                }
                                
                            if ( $wrong_setup )  
                                {
                                    echo "<div class='error is-dismissible notice-info'><p><strong>WooCommerce Global Cart:</strong> ". __('All shops utilizing the Global Cart must employ the same HPOS setup; currently, there is a mix, with some using it while others do not.', 'woo-global-cart')  ."</p></div>";   
                                }

                        } 
                    
                }
                
                
            function WC_disabled_notice()
                {
                    global $blog_id;
                    
                    $options    =   $this->functions->get_options();
                     
                    if ( $blog_id   >   1   &&  isset ( $options['use_global_cart_for_sites'][ $blog_id ] )    &&  $options['use_global_cart_for_sites'][ $blog_id ] == 'no' )
                        return;
                        
                    echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('WooCommerce plugin is required to be active.', 'woo-global-cart')  ."</p></div>";
                }

                
            function woocommerce_get_order_item_classname( $classname, $item_type, $id  )
                {
                    
                    switch ( $item_type ) 
                        {
                            case 'line_item' :
                            case 'product' :
                                $classname = 'WooGC_WC_Order_Item_Product';
                            break;
                 
                        }
                        
                    return $classname;
                       
                }
            
            
            function disable_global_cart( $status, $_blog_id = '' )
                {
                    global $blog_id;
             
                    if ( empty ( $_blog_id ) )
                        $_blog_id    =   $blog_id;
                                        
                    $options    =   $this->functions->get_options();
                     
                    if ( isset ( $options['use_global_cart_for_sites'][ $_blog_id ] )    &&  $options['use_global_cart_for_sites'][ $_blog_id ] == 'no' )
                        return TRUE;   
                         
                    return $status;   
                    
                    
                }
                       
        }
        
?>