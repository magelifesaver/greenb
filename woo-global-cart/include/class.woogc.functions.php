<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Functions 
        {
                            
            /**
            * Return Options
            * 
            */
            static public function get_options()
                {
                    
                    $_options   =   get_site_option('woogc_options');
                    
                    if ( ( ! isset ( $_options['synchronization_type'] )  ||  empty ( $_options['synchronization_type'] ) ) &&  defined( 'WOOGC_LOADER' ) )
                        $_options['synchronization_type']   =   'headers';
                    
                    $defaults = array (
                                             'version'                                  =>  '1.0',
                                             'db_version'                               =>  '1.0',
                                             
                                             'cart_checkout_type'                       =>  'single_checkout',
                                             'cart_checkout_location'                   =>  '',
                                             'cart_checkout_split_orders'               =>  'no',
                                             
                                             'calculate_shipping_costs_for_each_shops'  =>  'no',
                                             'calculate_shipping_costs_for_each_shops__site_base_tax'  =>  'no',
                                             
                                             'use_sequential_order_numbers'             =>  'no',
                                             
                                             'enable_order_synchronization'             =>  'no',
                                             'order_synchronization_consumer_key'       =>  '',
                                             'order_synchronization_consumer_secret'    =>  '',
                                             'order_synchronization_for_shops'          =>  array(),
                                             'order_synchronization_to_shop'            =>  '',
                                             
                                             'enable_product_synchronization'           =>  'no',
                                             'product_synchronization_op_type'          =>  'on_update',
                                             'replace_cart_product_with_origin_version' =>  'no',
                                             
                                             'replace_cart_product_with_local_version'  =>  'no',
                                             'show_product_attributes'                  =>  'no',
                                             
                                             'use_global_cart_for_sites'                =>  array(),
                                             
                                             'synchronization_type'                     =>  'screen'
                                             
                                       );
                    
                    $options = wp_parse_args( $_options, $defaults );
                          
                    return $options;  
                    
                }
            
            /**
            * Update Options
            *     
            * @param mixed $options
            */
            static public function update_options($options)
                {
                    
                    update_site_option('woogc_options', $options);
                    
                    
                }
            
                  
            /**
            * Return current url
            * 
            */
            function current_url()
                {
                    
                    $current_url    =   'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    
                    return $current_url;
                    
                }
            
            
            
            /**
            * Return a list of blogs to be used along with the plugin
            * 
            * @param mixed $WooCommerce_Active
            * @param mixed $_usage_area
            */
            function get_gc_sites( $WooCommerce_Active = FALSE, $context = 'view' )
                {
                    
                    $args   =   array(
                                        'number'    =>  9999,
                                        'public'    =>  1,
                                        'archived'  =>  0
                                        );
                    $sites  =   get_sites( $args );
                    
                    if($WooCommerce_Active  === FALSE)
                        return $sites;
                        
                    foreach ($sites as  $key    =>  $site)
                        {
                            switch_to_blog($site->blog_id);
                            
                            if (! $this->is_plugin_active( 'woocommerce/woocommerce.php') )
                                {
                                    unset($sites[$key]);
                                }
                                
                            restore_current_blog();
                               
                        }
                        
                    $sites  =   array_values($sites);
                    
                    $sites  =   apply_filters( 'woogc/get_gc_sites' , $sites, $context );
                    
                    return $sites;   
                    
                }
            
                       
            
            /**
            * Remove Class Filter Without Access to Class Object
            *
            * In order to use the core WordPress remove_filter() on a filter added with the callback
            * to a class, you either have to have access to that class object, or it has to be a call
            * to a static method.  This method allows you to remove filters with a callback to a class
            * you don't have access to.
            *
            * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
            * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
            *
            * @param string $tag         Filter to remove
            * @param string $class_name  Class name for the filter's callback
            * @param string $method_name Method name for the filter's callback
            * @param int    $priority    Priority of the filter (default 10)
            *
            * @return bool Whether the function is removed.
            */
            function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = '' ) 
                {
                    
                    global $wp_filter;
                    
                    // Check that filter actually exists first
                    if ( ! isset( $wp_filter[ $tag ] ) ) 
                        return FALSE;
                        
                    /**
                    * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
                    * a simple array, rather it is an object that implements the ArrayAccess interface.
                    *
                    * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
                    *
                    * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
                    */
                    if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) 
                        {
                            // Create $fob object from filter tag, to use below
                            $fob = $wp_filter[ $tag ];
                            $callbacks = &$wp_filter[ $tag ]->callbacks;
                        } 
                        else 
                        {
                            $callbacks = &$wp_filter[ $tag ];
                        }
                        
                    // Exit if there aren't any callbacks for specified priority
                    if ( ! empty ( $priority ) && ( ! isset ( $callbacks[ $priority ] ) || empty ( $callbacks[ $priority ] ) ) )
                        return FALSE;
                    
                    foreach ( (array) $callbacks    as  $callbacks_priority =>  $group )
                        {
                            if ( ! empty ( $priority) &&    $priority   !=  $callbacks_priority )
                                continue;
                                
                            // Loop through each filter for the specified priority, looking for our class & method
                            foreach( (array) $callbacks[ $callbacks_priority ] as $filter_id => $filter ) 
                                {
                                    // Filter should always be an array - array( $this, 'method' ), if not goto next
                                    if ( ! isset( $filter[ 'function' ]  ) ) 
                                        continue;
                                    
                                    //remove static    
                                    if ( ! is_array( $filter[ 'function' ] ) )
                                        {
                                            if( $filter[ 'function' ]   ==  $class_name . '::'  .  $method_name)
                                                {
                                                    unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                                    return TRUE;   
                                                }
                                            continue;   
                                        }
                                        
                                    // If first value in array is not an object, it can't be a class
                                    if ( ! is_object( $filter[ 'function' ][ 0 ] ) &&   empty ( $filter[ 'function' ][ 0 ] ) ) 
                                        continue;
                                        
                                    // Method doesn't match the one we're looking for, goto next
                                    if ( $filter[ 'function' ][ 1 ] !== $method_name ) 
                                        continue;
                                        
                                    // Method matched, now let's check the Class
                                    if ( is_object( $filter[ 'function' ][ 0 ] ) &&  get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) 
                                        {
                                            // WordPress 4.7+ use core remove_filter() since we found the class object
                                            if( isset( $fob ) )
                                                {
                                                    // Handles removing filter, reseting callback priority keys mid-iteration, etc.
                                                    $fob->remove_filter( $tag, $filter['function'], $callbacks_priority );
                                                } 
                                            else 
                                                {
                                                    // Use legacy removal process (pre 4.7)
                                                    unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                                    
                                                    // and if it was the only filter in that priority, unset that priority
                                                    if ( empty( $callbacks[ $callbacks_priority ] ) ) 
                                                        {
                                                            unset( $callbacks[ $callbacks_priority ] );
                                                        }
                                                        
                                                    // and if the only filter for that tag, set the tag to an empty array
                                                    if ( empty( $callbacks ) ) 
                                                        {
                                                            $callbacks = array();
                                                        }

                                                    
                                                }
                                                
                                            return TRUE;
                                            
                                        }
                                        else
                                        {
                                            // Use legacy removal process (pre 4.7)
                                            unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                            
                                            // and if it was the only filter in that priority, unset that priority
                                            if ( empty( $callbacks[ $callbacks_priority ] ) ) 
                                                {
                                                    unset( $callbacks[ $callbacks_priority ] );
                                                }
                                                
                                            // and if the only filter for that tag, set the tag to an empty array
                                            if ( empty( $callbacks ) ) 
                                                {
                                                    $callbacks = array();
                                                }
                                            
                                        }
                                }
                        }
                        
                    return FALSE;
                    
                }
            
            
            /**
            * Remove Class Action Without Access to Class Object
            *
            * In order to use the core WordPress remove_action() on an action added with the callback
            * to a class, you either have to have access to that class object, or it has to be a call
            * to a static method.  This method allows you to remove actions with a callback to a class
            * you don't have access to.
            *
            * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
            *
            * @param string $tag         Action to remove
            * @param string $class_name  Class name for the action's callback
            * @param string $method_name Method name for the action's callback
            * @param int    $priority    Priority of the action (default 10)
            *
            * @return bool               Whether the function is removed.
            */
            function remove_class_action( $tag, $class_name = '', $method_name = '', $priority = '' ) 
                {
                    
                    $this->remove_class_filter( $tag, $class_name, $method_name, $priority );
                    
                }
                
            
            /**
            * Replace a filter / action from anonymous object
            * 
            * @param mixed $tag
            * @param mixed $class
            * @param mixed $method
            * @param mixed $priority
            */
            static public function remove_anonymous_object_filter( $tag, $class, $method, $priority = '' ) 
                {
                    $filters = false;

                    if ( isset( $GLOBALS['wp_filter'][$tag] ) )
                        $filters = $GLOBALS['wp_filter'][$tag];

                    if ( $filters )
                    foreach ( $filters as $filter_priority => $filter ) 
                        {
                            if ( ! empty ( $priority )  &&   $priority != $filter_priority )
                                continue;
                                
                            foreach ( $filter as $identifier => $function ) 
                                {                                   
                                    if ( ! isset ( $function['function'] ) || ! is_array ( $function['function'] ) )
                                        continue;
                                    
                                    if ( is_string( $function['function'][0] )  &&  $function['function'][0]    == $class   &&  $function['function'][1]    ==  $method )
                                        remove_filter($tag, array( $function['function'][0], $method ), $filter_priority );
                                    else if ( is_object( $function['function'][0] )  &&  get_class( $function['function'][0] )    == $class   &&  $function['function'][1]    ==  $method ) 
                                        remove_filter($tag, array( $function['function'][0], $method ), $filter_priority );
                                }
                        }
                }
                
                
            static public function createInstanceWithoutConstructor($class)
                {
                    
                    $reflector  = new ReflectionClass($class);
                    $properties = $reflector->getProperties();
                    $defaults   = $reflector->getDefaultProperties();
                           
                    $serealized = "O:" . strlen($class) . ":\"$class\":".count($properties) .':{';
                    foreach ($properties as $property)
                        {
                            $name = $property->getName();
                            if($property->isProtected())
                                {
                                    $name = chr(0) . '*' .chr(0) .$name;
                                } 
                            elseif($property->isPrivate())
                                {
                                    $name = chr(0)  . $class.  chr(0).$name;
                                }
                            
                            $serealized .= serialize($name);
                            
                            if(array_key_exists($property->getName(),$defaults) )
                                {
                                    $serealized .= serialize($defaults[$property->getName()]);
                                } 
                            else 
                                {
                                    $serealized .= serialize(null);
                                }
                        }
                        
                    $serealized .="}";
                    
                    return unserialize($serealized);
                    
                }
                
                
            /* Check if the groups are in the stack of callers
            * 
            * e.g.
            * array ( 
            *           array ( 'create_attachment', 'WP_Job_Manager_Form_Submit_Job') , 
            *           array ('validate_fields', 'WP_Job_Manager_Form_Submit_Job') 
            * )
            * 
            * @param mixed $groups
            */
            static public function check_backtrace_for_caller( $groups )
                {
                    $backtrace  =   debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    foreach ( $groups   as $group )
                        {
                            $function_name      =   $group[0]; 
                            $class_name         =   isset ( $group[1] ) ?   $group[1]   :   FALSE;
                            
                            foreach ( $backtrace as  $block )
                                {
                                    if ( $block['function']    ===  $function_name )
                                        {
                                            if ( $class_name    ===  FALSE )
                                                return TRUE;
                                            
                                            if ( $class_name    !=  FALSE   &&  !isset( $block['class'] ) )
                                                return FALSE;
                                                
                                            if ( $block['class']    ==  $class_name )
                                                return TRUE;
                                            
                                            return FALSE;
                                            
                                        }
                                
                                }
                        }
                        
                    return FALSE;
                }
            
            
            static public function is_rest_request() 
                {
                    if (defined('REST_REQUEST') && REST_REQUEST) {
                        return true;
                    }
                    if (isset($_SERVER['REQUEST_URI'])) {
                        return strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false;
                    }
                    return false;
                }
                
                
                
            static public function is_plugin_active( $plugin_slug )
                {
                    
                    include_once ( ABSPATH.'wp-admin/includes/plugin.php' );
                    
                    $found_plugin   =   is_plugin_active($plugin_slug);   
                    
                    if ( $found_plugin &&  ! file_exists( trailingslashit ( WP_PLUGIN_DIR ) . $plugin_slug ) )
                        $found_plugin   =   FALSE;
                    
                    return $found_plugin;
                    
                }
                
            
            static public function is_HPOS_active()
                {
                    $woocommerce_custom_orders_table_enabled    =   get_option( 'woocommerce_custom_orders_table_enabled' ) == 'yes' ?    TRUE :  FALSE;
                    
                    return $woocommerce_custom_orders_table_enabled;
                }
            
            
            
            /**
            * Check different requires
            * 
            */
            public function check_required_structure()
                {
                    $options    =   $this->get_options();
                    
                    //check if the mu files exists
                    if( ! $this->check_mu_files())
                        $this->copy_mu_files( FALSE );
                        
                    //check if outdated
                    if ( ! defined('WOOGC_MULOADER_VERSION')    ||  version_compare( WOOGC_MULOADER_VERSION, '1.4', '<' ) )
                        $this->copy_mu_files( TRUE );
                        
                    add_action('admin_notices',         array($this, 'admin_environment_notices'));
                    add_action('network_admin_notices', array($this, 'admin_environment_notices'));
                }
                
            
            /**
            * Output possible environment issues
            * 
            */
            function admin_environment_notices()
                {
                    //check for WOOGC_MULOADER_VERSION
                    
                    $options    =   $this->get_options();
                    
                    if ( $options['synchronization_type']  ==  'screen' )
                        {
                            if ( ! defined( 'WOOGC_MULOADER_VERSION' ) )
                                {
                                    ?><div class="error fade"><p><?php _e( "<b>WooCommerce Global Cart</b> - unable to copy over to /wp-content/mu-plugins/ and launch the MU module file..", 'woo-global-cart' ) ?></p></div><?php
                                }
                            
                        }
                        
                    
                    if ( $options['synchronization_type']  ==  'headers' )
                        {
                            if ( ! defined( 'WOOGC_LOADER' ) )
                                {
                                    ?><div class="error fade"><p><?php _e( "<b>WooCommerce Global Cart</b> - unable to insert the loader to /wp-config.php. Ensure the file is writable.", 'woo-global-cart' ) ?></p></div><?php
                                } 
                                
                            if ( ! defined( 'WOOGC_MULOADER_VERSION' ) )
                                {
                                    ?><div class="error fade"><p><?php _e( "<b>WooCommerce Global Cart</b> - unable to copy over to /wp-content/mu-plugins/ and launch the MU module file..", 'woo-global-cart' ) ?></p></div><?php
                                }                            
                        }
                }
                
                
            
            /**
            * Check if MU files exists
            * 
            */
            public function check_mu_files()
                {
                    
                    if( file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                        return TRUE;
                        
                    return FALSE;
                    
                }
            
                
                
            /**
            * Attempt to copy the mu files to mu-plugins folder
            * 
            */
            public function copy_mu_files( $force_overwrite    =   FALSE )
                {
                    
                    //check if mu-plugins folder exists
                    if(! is_dir( WPMU_PLUGIN_DIR ))
                        {
                            if (! wp_mkdir_p( WPMU_PLUGIN_DIR ) )
                                return;
                        }
                    
                    //check if file actually exists already
                    if( !   $force_overwrite    )
                        {
                            if( file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                                return;
                        }
                    
                    $options    =   $this->get_options();
                        
                    //attempt to copy the file
                    if ( $options['synchronization_type']  ==  'screen' )
                        @copy( WP_PLUGIN_DIR . '/woo-global-cart/mu-files/screen/woo-gc.php', WPMU_PLUGIN_DIR . '/woo-gc.php' );
                    if ( $options['synchronization_type']  ==  'headers' )
                        @copy( WP_PLUGIN_DIR . '/woo-global-cart/mu-files/headers/woo-gc.php', WPMU_PLUGIN_DIR . '/woo-gc.php' );
                }
                
                
            
            /**
            * Remove MU plugin files
            * 
            */
            public function remove_mu_files()
                {
                    
                    //check if file actually exists already
                    if( !file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                        return;
                        
                    //attempt to copy the file
                    @unlink ( WPMU_PLUGIN_DIR . '/woo-gc.php' );    
                    
                }
                
                
                
            /**
            * Add the required code to wp-config.php file
            * 
            */
            function wp_config_add()
                {
                    
                    $options    =   $this->get_options();
                    if ( $options['synchronization_type']   !=  'headers' )
                        return;
                    
                    $config_data    =   $this->get_config_data();
                    
                    if ( is_array( $config_data ) )
                        return;
                    
                    //check if exists 
                    if ( stripos( $config_data, $this->wp_config_addon() )   !== FALSE )
                        return;
                    
                    if(is_array($config_data))
                        return ($config_data);   
  
                    $config_data_array    =   preg_split("/\r\n|\n|\r/", $config_data );
                    $founds = preg_grep ('/define(\s+)?\((\s+)?\'ABSPATH\'/i', array_map("trim", $config_data_array ) );
                    
                    if( count ( $founds ) < 1 )
                        {
                            $return =   array(
                                                'status'            =>  'fail',
                                                'error_messages'    =>  array(
                                                                                __( 'Unexpected wp-config.php file content', 'woo-global-cart' )
                                                                                )
                                                );
                                                
                            return $return;    
                        }
                        
                    $found_insert_position  =   FALSE;
                    reset ( $founds );
                    $check_at   =   key ( $founds );
                    while ( $check_at > 0 )
                        {
                            if ( empty ( $config_data_array[$check_at] ) )
                                {
                                    $found_insert_position  =   $check_at;
                                    break;
                                }
                                //In case there are no gaps in the code, add above the first comment which is the /* That's all, stop editing! Happy publishing. */
                                else if ( strpos( $config_data_array[$check_at], '/* ' ) !== FALSE )
                                    {
                                        $found_insert_position  =   $check_at;
                                        break;   
                                    }
                            
                            $check_at--;
                        }
                    
                    if ( $found_insert_position !== FALSE )
                        {
                            array_splice( $config_data_array, $found_insert_position, 0, $this->wp_config_addon() );
                            $new_config_data    =   implode( PHP_EOL, $config_data_array );
                        }
                        else
                        {
                            $new_config_data    = $this->wp_config_remove_from_content($config_data);
                            $position           = strpos($new_config_data, "/* That's all, stop editing! Happy blogging. */");   
                            $new_config_data    = substr_replace($new_config_data,  $this->wp_config_addon(), $position, 0);        
                        }
                    
                    if ( ! empty ( $new_config_data ) )
                        $response = $this->update_config_data($new_config_data);
                    
                    return $response; 
                }
            
            
            /**
            * Remove the custom code from wp-config.php
            * 
            */    
            function wp_config_clean()
                {
  
                    $config_data    =   $this->get_config_data();
                    if ( is_array( $config_data ) )
                        return ($config_data);                    
                    
                    $new_config_data = $this->wp_config_remove_from_content($config_data);
                    
                    if ($new_config_data != $config_data) 
                        {
                            $response = $this->update_config_data($new_config_data);
                            return $response;
                        }
                        
                    $response =   array(
                                    'status'            =>  'success',
                                    'messages'    =>  array(
                                                                    ''
                                                                    )
                                    );
                    return $response;    
                    
                }
                
            function get_config_data()
                {
                    $config_path = $this->get_wp_config_path();

                    if($config_path ===  FALSE)
                        {
                            $return =   array(
                                                'status'            =>  'fail',
                                                'error_messages'    =>  array(
                                                                                __('Unable to determinate the wp-config.php file path', 'woo-global-cart')
                                                                                )
                                                );
                            return $return;
                        }
                    
                    //check if is writable
                    if(!is_writable($config_path))
                        {
                            $return =   array(
                                                'status'            =>  'fail',
                                                'error_messages'    =>  array(
                                                                                __('The wp-config.php file is not writable', 'woo-global-cart')
                                                                                )
                                                );
                            return $return;
                        }
                    
                    $config_data = @file_get_contents($config_path);
                    if ($config_data === false)
                        {
                            $return =   array(
                                                'status'            =>  'fail',
                                                'error_messages'    =>  array(
                                                                                __('Unable to read the wp-config.php file content', 'woo-global-cart')
                                                                                )
                                                );
                                                
                            return $return;
                        
                        }
                        
                    return $config_data;
                    
                }
                
            function update_config_data($new_config_data)
                {
                    $config_path = $this->get_wp_config_path();
                    
                    try 
                        {
                            $fh = fopen($config_path, 'w') or die("can't open file");
                            fwrite($fh, $new_config_data);
                            fclose($fh);
                            
                            $return =   array(
                                            'status'            =>  'success',
                                            'messages'    =>  array(
                                                                            __('Cache Module status updated', 'woo-global-cart')
                                                                            )
                                            );
                                            
                            return $return;
                        } 
                    catch (Exception $e) 
                        {
                            $return =   array(
                                            'status'            =>  'fail',
                                            'error_messages'    =>  array(
                                                                            __('Unable to update the wp-config.php file', 'woo-global-cart')
                                                                            )
                                            );
                                            
                            return $return;
                        }    
                    
                }
                
            
            function get_wp_config_path() 
                {
                    $search = array(
                                        ABSPATH . 'wp-config.php',
                                        dirname(ABSPATH) . '/wp-config.php'
                                    );

                    foreach ($search as $path) 
                        {
                            if (file_exists($path)) 
                                {
                                    return $path;
                                }
                        }
                    
                    return false;
                }
                
            function wp_config_remove_from_content($config_data) 
                {
                     
                    $config_data    =   str_replace($this->wp_config_addon(), '', $config_data);

                    return $config_data;
                }
                
            function wp_config_addon()
                {
                    return "/* Start WP Global Cart Synchronization module */" . PHP_EOL
                        .   "@include_once( ( defined('WP_PLUGIN_DIR')    ?     WP_PLUGIN_DIR   .   '/woo-global-cart/'    :      ( defined( 'WP_CONTENT_DIR') ? WP_CONTENT_DIR  :   dirname( __FILE__ ) . '/' . 'wp-content' )  . '/plugins/woo-global-cart' ) . '/sync/loader.php');" . PHP_EOL
                        .   "/* End WP Global Cart Synchronization module */";
                }
                
                
                
            
            /**
            * Create required tables
            * 
            */
            public function create_tables()
                {
                    //esnure the tables do not exists
                    $this->remove_tables();
                    
                    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                    
                    global $wpdb;
                    
                    $collate = $wpdb->get_charset_collate();
                    
                    $options    =   $this->get_options();
                    
                    if ( $options['synchronization_type']   ==  'screen' )
                        {
                            $query = "CREATE TABLE IF NOT EXISTS `". $wpdb->base_prefix ."woocommerce_woogc_sessions` (
                                      `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      `session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `woogc_session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `session_expiry` bigint(20) UNSIGNED NOT NULL,
                                      `trigger_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `trigger_key_expiry` bigint(20) UNSIGNED NOT NULL,
                                      `trigger_user_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                        PRIMARY KEY (`session_id`),
                                        UNIQUE KEY `woogc_session_key` (`woogc_session_key`) USING BTREE,
                                        KEY `trigger_key` (`trigger_key`)
                                    ) " . $collate;
                            dbDelta( $query );
                               
                        }
                        
                    if ( $options['synchronization_type']   ==  'headers' )                           
                        {
                            $query = "CREATE TABLE `". $wpdb->base_prefix ."woocommerce_woogc_sessions` (
                                      `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                      `session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `woogc_session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `session_expiry` bigint(20) UNSIGNED NOT NULL,
                                      `user_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                        PRIMARY KEY (`session_id`),
                                        UNIQUE KEY `woogc_session_key` (`woogc_session_key`) USING BTREE
                                    ) " . $collate;
                            dbDelta( $query );
                            
                            
                            $query = "CREATE TABLE `". $wpdb->base_prefix ."woocommerce_woogc_sessions_triggers` (
                                          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                                          `woogc_session_key` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                          `trigger_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                          `trigger_key_expiry` bigint(20) UNSIGNED NOT NULL,
                                          `domain` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
                                          `user_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                            PRIMARY KEY  (`id`)
                                        ) " . $collate;
                            dbDelta( $query );
                        }
                    
                }
                
                
            /**
            * Remove tables
            * 
            */
            public function remove_tables()
                {
                                        
                    global $wpdb;
                                        
                    $query = "DROP TABLE IF EXISTS `". $wpdb->base_prefix ."woocommerce_woogc_sessions`";
                    $wpdb->query( $query );
                    $query = "DROP TABLE IF EXISTS `". $wpdb->base_prefix ."woocommerce_woogc_sessions_triggers`";
                    $wpdb->query( $query );
                    
                }
            
            
            /**
            * Check if filter / action exists for anonymous object
            * 
            * @param mixed $tag
            * @param mixed $class
            * @param mixed $method
            */
            function anonymous_object_filter_exists($tag, $class, $method)
                {
                    if ( !  isset( $GLOBALS['wp_filter'][$tag] ) )
                        return FALSE;
                    
                    $filters = $GLOBALS['wp_filter'][$tag];
                    
                    if ( !  $filters )
                        return FALSE;
                        
                    foreach ( $filters as $priority => $filter ) 
                        {
                            foreach ( $filter as $identifier => $function ) 
                                {
                                    if ( ! is_array( $function ) )
                                        continue;
                                    
                                    if ( ! $function['function'][0] instanceof $class )
                                        continue;
                                    
                                    if ( $method == $function['function'][1] ) 
                                        {
                                            return TRUE;
                                        }
                                }
                        }
                        
                    return FALSE;
                }
                
                
            
            /**
            * Cretae a field collation to unify across database
            * 
            */
            function get_collated_column_name( $field_name, $table_name, $as_field_name =   '' )
                {
                        
                    global $wpdb, $WooGC;
                    
                    //try a cached
                    if( ! isset($WooGC->cache['database'])   ||  ! isset($WooGC->cache['database']['table_collation']) )
                        {
                            //attempt to get all tables collation
                            $mysql_query    =   "SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.`TABLES` 
                                                    WHERE TABLE_SCHEMA = '" .  DB_NAME  ."'";
                            $results        =   $wpdb->get_results( $mysql_query );
                            
                            if ( count ( $results ) >   0 )
                                {
                                    $WooGC->cache['database']['table_collation']    =   array();
                                    
                                    foreach ( $results  as  $result )
                                        {
                                            $WooGC->cache['database']['table_collation'][ $result->TABLE_NAME ] =   $result->TABLE_COLLATION;
                                        }
                                    
                                }
                                else
                                    {
                                        //something went wrong
                                        $WooGC->cache['database']['table_collation']    =   FALSE;
                                    }
                        }
                    
                    if ( empty ( $as_field_name ) )
                        $as_field_name  =   $field_name;
                    
                    //try the cache
                    if ( $WooGC->cache['database']['table_collation']   !== FALSE   &&  isset ( $WooGC->cache['database']['table_collation'][$table_name] ))
                        {
                            
                            $table_collation    =   explode( "_", $WooGC->cache['database']['table_collation'][$table_name]);
                            $charset            =   $table_collation[0];
                            
                            $collation          =   explode( "_", $wpdb->collate );
                            $collation[0]       =   $charset;
                            $use_collation      =   implode("_", $collation);
                            
                            return $field_name . " COLLATE " . $use_collation . " AS " . $as_field_name;
                        }
                        else
                        {
                            //regular approach
                            $db_collation =   $wpdb->collate;
                            
                            if(empty($db_collation))
                                return $field_name;
                                
                            return $field_name . " COLLATE " . $db_collation . " AS " . $as_field_name; 
                        }                   
                    
                }
                
                
                
            /**
            * Create a Lock functionality using the MySql 
            * 
            * @param mixed $lock_name
            * @param mixed $release_timeout
            * 
            * @return bool False if a lock couldn't be created or if the lock is still valid. True otherwise.
            */
            function create_lock( $lock_name, $release_timeout = null ) 
                {
                    
                    global $wpdb, $blog_id;
                    
                    if ( ! $release_timeout ) {
                        $release_timeout = 10;
                    }
                    $lock_option = $lock_name . '.lock';
                                     
                    // Try to lock.
                    $lock_result = $wpdb->query( $wpdb->prepare( "INSERT INTO `". $wpdb->sitemeta ."` (`site_id`, `meta_key`, `meta_value`) 
                                                                    SELECT %s, %s, %s FROM DUAL
                                                                    WHERE NOT EXISTS (SELECT * FROM `". $wpdb->sitemeta ."` 
                                                                          WHERE `meta_key` = %s AND `meta_value` != '') 
                                                                    LIMIT 1", $blog_id, $lock_option, time(), $lock_option) );
                                        
                    if ( ! $lock_result ) 
                        {
                            $lock_result    =   $this->get_lock( $lock_option );

                            // If a lock couldn't be created, and there isn't a lock, bail.
                            if ( ! $lock_result ) {
                                return false;
                            }

                            // Check to see if the lock is still valid. If it is, bail.
                            if ( $lock_result > ( time() - $release_timeout ) ) {
                                return false;
                            }

                            // There must exist an expired lock, clear it and re-gain it.
                            $this->release_lock( $lock_name );

                            return $this->create_lock( $lock_name, $release_timeout );
                        }

                    // Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
                    $this->update_lock( $lock_option, time() );

                    return true;
                    
                }

            
            /**
            * Retrieve a lock value
            * 
            * @param mixed $lock_name
            * @param mixed $return_full_row
            */
            private function get_lock( $lock_name, $return_full_row =   FALSE )
                {
                    
                    global $wpdb;
                    
                    $mysq_query =   $wpdb->get_row( $wpdb->prepare("SELECT `site_id`, `meta_key`, `meta_value` FROM  `". $wpdb->sitemeta ."`
                                                                    WHERE `meta_key`    =   %s", $lock_name ) );
                    
                    
                    if ( $return_full_row   === TRUE )
                        return $mysq_query;
                        
                    if ( is_object($mysq_query) && isset ( $mysq_query->meta_value ) )
                        return $mysq_query->meta_value;
                        
                    return FALSE;
                    
                }
                
                
            /**
            * Update lock value
            *     
            * @param mixed $lock_name
            * @param mixed $lock_value
            */
            private function update_lock( $lock_name, $lock_value )
                {
                    
                    global $wpdb;
                    
                    $mysq_query =   $wpdb->query( $wpdb->prepare("UPDATE `". $wpdb->sitemeta ."` 
                                                                    SET meta_value = %s
                                                                    WHERE meta_key = %s", $lock_value, $lock_name) );
                    
                    
                    return $mysq_query;
                    
                }
                
            
            /**
            * Releases an upgrader lock.
            *
            * @param string $lock_name The name of this unique lock.
            * @return bool True if the lock was successfully released. False on failure.
            */
            function release_lock( $lock_name ) 
                {
                    
                    global $wpdb;
                    
                    $lock_option = $lock_name . '.lock';
                    
                    $mysq_query =   $wpdb->query( $wpdb->prepare( "DELETE FROM `". $wpdb->sitemeta ."` 
                                                                    WHERE meta_key = %s", $lock_option ) );
                    
                    return $mysq_query;
                    
                }
                
                
                
            /**
            * Save a message log to a debug file
            *     
            * @param mixed $text
            */
            static function log_save ( $text )
                {
                    
                    $myfile     = fopen( WOOGC_PATH . "/debug.txt", "a") or die("Unable to open file!");
                    $txt        =  $text   .   "\n";
                    fwrite($myfile, $txt);
                    fclose($myfile);   
                    
                }
                               
                
        }


?>