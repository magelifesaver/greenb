<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS_Async
        {
            
            public $ServerMaxExecutionTime   =   FALSE;
            public $StartingExecutionTime    =   FALSE;
            public $ExpectedTimeTimeout      =   FALSE;
            
            public static $OperationType            =   FALSE;
            
            public static $AsyncEnqueued            =   FALSE;
                            
            /**
             * __construct function.
             *
             * @access public
             * @return void
             */
            static function register_actions() 
                {                    
                    add_action( 'admin_notices',                            array( 'WooGC_PS_Async', 'async_admin_notices_html' ));
                    
                    add_action( 'woogc_async_action_product_save',          array ( 'WooGC_PS_Async', 'handle_woogc_async_action_product_save' ) , 10, 3 );
                    add_action( 'woogc_async_action_bulk_edit',             array ( 'WooGC_PS_Async', 'handle_woogc_async_action_bulk_edit' ) , 10, 3 );
                       
                }
            
            
            /**
            * Setup the exeution timing, depending on the server environment.
            * 
            */
            public function setup_execution_time()
                {
                    if ( $this->ExpectedTimeTimeout > 0 )
                        return;
                                            
                    $this->ServerMaxExecutionTime   =   ini_get('max_execution_time');
                    
                    //possible this is set to 0 (zero)
                    if ( $this->ServerMaxExecutionTime < 10 )
                        $this->ServerMaxExecutionTime   =   30;
                        
                    //make it up to 120 sec
                    if ( $this->ServerMaxExecutionTime > 60 )
                        $this->ServerMaxExecutionTime   =   60;
                                            
                    $this->StartingExecutionTime    =   time();
                    
                    $this->ExpectedTimeTimeout      =   $this->StartingExecutionTime    +   $this->ServerMaxExecutionTime;
                    
                    //Ensure the expected timeout is not exceeded
                    $this->ExpectedTimeTimeout      =   $this->ExpectedTimeTimeout  -   5;
                }
                
            
            /**
            * Check if the the code should continue the excution or exit
            * 
            */
            public function can_continue_code_execution()
                {
                    
                    if ( time() > $this->ExpectedTimeTimeout )
                        return FALSE;
                        
                    return TRUE;
                } 
            
            
            static function cleanup_async_action_data()
                {
                    $action_data = get_option('woogc_async_action_data');
                    if ( empty ( $action_data ) ||  ! is_array ( $action_data ) ||  count ( $action_data ) < 1 )
                        return;
                    
                    $data_hash    =   md5 ( json_encode ( $action_data ) );
                        
                    foreach ( $action_data  as  $key    =>   $action_data_item ) 
                        {
                            if ( ! is_array ( $action_data_item ) )
                                continue;
                            
                            $action_id          =   $action_data_item['action_id'];
                            $action_messagge    =   $action_data_item['message'];
                            
                            if ( $action_id )
                                {
                                    $status = self::get_async_action_status($action_id);
                                    if ( strtolower ( $status ) === 'action not found' || strtolower ( $status ) === 'completed' )
                                        unset ( $action_data[ $key ] );
                                }
                        }
                        
                    if ( md5 ( json_encode ( $action_data ) ) != $data_hash )
                        update_option( 'woogc_async_action_data', $action_data );
                }
                      
                
            static function async_admin_notices_html()
                {
                    self::cleanup_async_action_data();
                    
                    //check if already in the Scheduled Actions interface
                    if ( isset ( $_GET['page'] )    &&  $_GET['page']   ==  'wc-status' &&  isset ( $_GET['tab'] )    &&  $_GET['tab']   ==  'action-scheduler' )
                        return;
                                        
                    $action_data = get_option('woogc_async_action_data');
                    if ( empty ( $action_data ) ||  ! is_array ( $action_data ) ||  count ( $action_data ) < 1 )
                        return;
                    
                    $found_action   =   FALSE;
                        
                    foreach ( $action_data  as  $action_data_item ) 
                        {
                            if ( ! is_array ( $action_data_item ) )
                                continue;
                            
                            $action_id          =   $action_data_item['action_id'];
                            $action_messagge    =   $action_data_item['message'];
                            
                            if ( $action_id )
                                {
                                    $status = self::get_async_action_status($action_id);
                                    if ( strtolower ( $status ) === 'completed' )
                                        continue;
                                        
                                    $found_action   =   TRUE;
                                }
                        }
                        
                    if ( ! $found_action )
                        return;
                    
                    ?>
                    
                    <div class="notice notice-success is-dismissible">
                        <h2 data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-1h1361s e19lxcc00"><?php _e( 'Product Synchronisation is in progress.', 'woo-global-cart'); ?></h2>
                        <p><?php _e( 'Product Synchronization is in progress. Your products are being updated to ensure all information is consistent and up-to-date across the selected shops. Please wait while the system completes this process. The duration may vary depending on the number of products and the required operations.', 'woo-global-cart'); ?></p>
                        <p>
                        <?php
                            
                            foreach ( $action_data  as  $action_data_item ) 
                                {
                                    $action_id          =   $action_data_item['action_id'];
                                    $action_messagge    =   $action_data_item['message'];
                            
                                    if ( $action_id ) {
                                        echo $action_messagge ."<br />";
                                        $status = self::get_async_action_status($action_id);
                                        echo "The status of the async action " . $action_id . " is: $status";
                                    } else {
                                        echo "No async action ID found.";
                                    }
                                    
                                    
                                ?>
                                </p>
                                <?php
                                    if ( strtolower ( $status ) == 'pending'  )
                                        {
                                ?>
                                <p><a href="<?php echo get_admin_url() ?>admin.php?page=wc-status&amp;tab=action-scheduler&amp;s=woogc_async_action&amp;status=pending" class="components-button is-secondary">View progress </a></p>
                                <?php } ?>
                                <?php
                                    if ( strtolower ( $status ) == 'running'  )
                                        {
                                ?>
                                <p><a href="<?php echo get_admin_url() ?>admin.php?page=wc-status&amp;tab=action-scheduler&amp;s=woogc_async_action&amp;status=in-progress" class="components-button is-secondary">View progress </a></p>
                                <?php } 
                                
                                if ( strtolower ( $status ) == 'failed'  )
                                        {
                                ?>
                                <p><a href="<?php echo get_admin_url() ?>admin.php?page=wc-status&amp;tab=action-scheduler&amp;s=woogc_async_action&amp;status=failed" class="components-button is-secondary">View</a></p>
                                <?php }
                        
                                }
                        ?>
                    </div>
       
                    <?php 
                }
                
                
                
            static function get_async_action_status( $action_id ) 
                {
                    // Get the action scheduler store
                    $store = \ActionScheduler::store();

                    // Fetch the action
                    try {
                        $action = $store->fetch_action($action_id);
                        // Get the status of the action
                        $status = $store->get_status($action_id);
                    } catch (Exception $e) {
                        return 'Action not found';
                    }

                    

                    switch ($status) {
                        case \ActionScheduler_Store::STATUS_PENDING:
                            return 'Pending';
                        case \ActionScheduler_Store::STATUS_RUNNING:
                            return 'Running';
                        case \ActionScheduler_Store::STATUS_COMPLETE:
                            return 'Completed';
                        case \ActionScheduler_Store::STATUS_FAILED:
                            return 'Failed';
                        default:
                            return 'Unknown status';
                    }
                }
                
                
            static function async_start_product_save( $post_id, $_post_data, $operation_type )  
                {
                    
                    if ( ! isset ( $_post_data[ 'async_post_id' ] ) )
                        $_post_data[ 'async_post_id' ]  =   $post_id;
                    
                    $params = array( $post_id, array( 'post_data'   =>  $_post_data ) );
                    
                    //$action_id = as_enqueue_async_action('woogc_async_action_product_save', $params);
                    $action_id = as_schedule_single_action( time() + 10, 'woogc_async_action_product_save', $params );
                    
                    $new_action_data    =   array (
                                                    'action_id' =>  $action_id,
                                                    'message'   =>  isset ( $_post_data['asyng_sync_to'] )  ?   __( 'Continue the synchronization batch.', 'woo-global-cart' )   :  __( 'Start the synchronization batch.', 'woo-global-cart' )   
                                                    );
                    
                    // Store the action ID for later use
                    $action_data = get_option('woogc_async_action_data');
                    if ( ! is_array ( $action_data ) )
                        $action_data    =   array();
                    
                    $action_data[]              =   $new_action_data;
                    update_option( 'woogc_async_action_data', $action_data );
                }
                
            
            
            static function async_start_bulk_edit( $post_id, $_post_data, $_get_data )  
                {
                    self::$AsyncEnqueued    =   TRUE;
                       
                    $params = array( $post_id, array( 'post_data'   =>  $_post_data, 'get_data' =>  $_get_data ) );
                    
                    //$action_id = as_enqueue_async_action('woogc_async_action_bulk_edit', $params);
                    //$action_id = as_schedule_single_action( time() + 10 * 60 * 60, 'woogc_async_action_bulk_edit', $params );
                    $action_id = as_schedule_single_action( time() + 10, 'woogc_async_action_bulk_edit', $params );
                    
                    $new_action_data    =   array (
                                                    'action_id' =>  $action_id,
                                                    'message'   =>  isset ( $_post_data['asyng_sync_to'] )  ?   __( 'Continue the synchronization batch.', 'woo-global-cart' )   :  __( 'Start the synchronization batch.', 'woo-global-cart' )   
                                                    );
                    
                    // Store the action ID for later use
                    $action_data = get_option('woogc_async_action_data');
                    if ( ! is_array ( $action_data ) )
                        $action_data    =   array();
                    
                    $action_data[]              =   $new_action_data;
                    update_option( 'woogc_async_action_data', $action_data );
                }
                
                
            static function handle_woogc_async_action_product_save( $post_id, $post_data ) 
                {
                    self::$OperationType    =   'product_save';
                        
                    $PS             =   new WooGC_PS();
                    
                    $_post_data =   $post_data['post_data'];
                                                                        
                    if ( $PS->is_child_product( $post_id ) )
                        self::product_save_child( $post_id, $_post_data );
                        else
                        self::product_save_main( $post_id, $_post_data );
                }
                
                
            static function handle_woogc_async_action_bulk_edit( $post_id, $post_data ) 
                {
                    self::$OperationType    =   'bulk_edit';
                        
                    $PS             =   new WooGC_PS();
                    
                    $_post_data =   $post_data['post_data'];
                    $_get_data  =   $post_data['get_data'];
                    
                    //self::woocommerce_product_bulk_edit_save( $_post_data, $_get_data );
                    $WooGC_PS_Async =   new WooGC_PS_Async();
                    $WooGC_PS_Async->woocommerce_product_bulk_edit_save( $_post_data, $_get_data );

                }
            
            
            
            
            static function product_save_main( $product_id, $data = '' )
                {
                    if ( empty ( $data ) )
                        $data = $_POST;
                    
                    $main_product  =   new WooGC_PS_main_product( $product_id );
                    
                    if ( isset ( $data['asyng_sync_to'] ) )
                        $main_product_sync_to_shops =   $data['asyng_sync_to'];
                        else
                        $main_product_sync_to_shops         =   $main_product->get_children();
                    
                    
                    $main_product_maintain_child        =   $main_product->get_maintained();
                    $main_product_maintain_categories   =   $main_product->get_maintained_categories();
                    $main_product_maintain_stock        =   $main_product->get_maintained_stock();
                        
                    $_woogc_ps_sync_to  =   FALSE;
                    if ( isset ( $data['_woogc_ps_sync_to'] )  &&  is_array ( $data['_woogc_ps_sync_to'] )  &&  ! isset ( $data['asyng_sync_to'] ) )
                        {
                            foreach ( $data['_woogc_ps_sync_to']   as   $shop_id   =>  $do_sync )
                                {
                                    $shop_id    =   intval( $shop_id );
                                    if ( $do_sync   ==  'yes' )
                                        $_woogc_ps_sync_to  =   TRUE;
                                        else
                                        $_woogc_ps_sync_to  =   FALSE;    
                                    
                                    if ( $_woogc_ps_sync_to === TRUE    &&  array_search ( $shop_id, $main_product_sync_to_shops )    === FALSE )
                                        $main_product_sync_to_shops[]   =   $shop_id;
                                    if ( $_woogc_ps_sync_to === FALSE    &&  array_search ( $shop_id, $main_product_sync_to_shops )    !== FALSE )
                                        unset ( $main_product_sync_to_shops[ array_search ( $shop_id, $main_product_sync_to_shops ) ] );
                                }
                            
                            $main_product->set_sync_to( $main_product_sync_to_shops );
                        }
                                            
                    $_woogc_ps_maintain_child  =   FALSE;
                    if ( isset ( $data['_woogc_ps_maintain_child'] )  &&  is_array ( $data['_woogc_ps_maintain_child'] ) )
                        {
                            foreach ( $data['_woogc_ps_maintain_child']   as   $shop_id   =>  $do_sync )
                                {
                                    $shop_id    =   intval( $shop_id );
                                    if ( $do_sync   ==  'yes' )
                                        $_woogc_ps_maintain_child  =   TRUE;
                                        else
                                        $_woogc_ps_maintain_child  =   FALSE;    
                                    
                                    if ( $_woogc_ps_maintain_child === TRUE    &&  array_search ( $shop_id, $main_product_maintain_child )    === FALSE )
                                        $main_product_maintain_child[]   =   $shop_id;
                                    if ( $_woogc_ps_maintain_child === FALSE    &&  array_search ( $shop_id, $main_product_maintain_child )    !== FALSE )
                                        unset ( $main_product_maintain_child[ array_search ( $shop_id, $main_product_maintain_child ) ] );
                                }
                            $main_product->set_mintained( $main_product_maintain_child );
                        }
                    
                    $_woogc_ps_maintain_categories  =   FALSE;
                    if ( isset ( $data['_woogc_ps_maintain_categories'] )  &&  is_array ( $data['_woogc_ps_maintain_categories'] ) )
                        {
                            foreach ( $data['_woogc_ps_maintain_categories']   as   $shop_id   =>  $do_sync )
                                {
                                    $shop_id    =   intval( $shop_id );
                                    if ( $do_sync   ==  'yes' )
                                        $_woogc_ps_maintain_categories  =   TRUE;
                                        else
                                        $_woogc_ps_maintain_categories  =   FALSE;    
                                    
                                    if ( $_woogc_ps_maintain_categories === TRUE    &&  array_search ( $shop_id, $main_product_maintain_categories )    === FALSE )
                                        $main_product_maintain_categories[]   =   $shop_id;
                                    if ( $_woogc_ps_maintain_categories === FALSE    &&  array_search ( $shop_id, $main_product_maintain_categories )    !== FALSE )
                                        unset ( $main_product_maintain_categories[ array_search ( $shop_id, $main_product_maintain_categories ) ] );
                                }
                            $main_product->set_mintained_categories( $main_product_maintain_categories );
                        }
                        
                    $_woogc_ps_maintain_stock  =   FALSE;
                    if ( isset ( $data['_woogc_ps_maintain_stock'] )  &&  is_array ( $data['_woogc_ps_maintain_stock'] ) )
                        {
                            foreach ( $data['_woogc_ps_maintain_stock']   as   $shop_id   =>  $do_sync )
                                {
                                    $shop_id    =   intval( $shop_id );
                                    if ( $do_sync   ==  'yes' )
                                        $_woogc_ps_maintain_stock  =   TRUE;
                                        else
                                        $_woogc_ps_maintain_stock  =   FALSE;    
                                    
                                    if ( $_woogc_ps_maintain_stock === TRUE    &&  array_search ( $shop_id, $main_product_maintain_stock )    === FALSE )
                                        $main_product_maintain_stock[]   =   $shop_id;
                                    if ( $_woogc_ps_maintain_stock === FALSE    &&  array_search ( $shop_id, $main_product_maintain_stock )    !== FALSE )
                                        unset ( $main_product_maintain_stock[ array_search ( $shop_id, $main_product_maintain_stock ) ] );
                                }
                            $main_product->set_mintained_stock( $main_product_maintain_stock );
                        }

                    if ( empty ( $main_product_sync_to_shops )   ||  ( isset ( $data['child_update'] )   &&  $data['child_update']   =   TRUE ))
                        return;
                    
                    $main_product->set_as_main_product();
                    
                    $args   =   array  (
                                            'maintain_child'        =>  $main_product_maintain_child,
                                            'maintain_categories'   =>  $main_product_maintain_categories,
                                            'maintain_stock'        =>  $main_product_maintain_stock,
                                            );
                    $interface_messages     =   self::async_synchronize_to( $product_id, $main_product_sync_to_shops, $args, $data );
                
                }
                
                
            static function product_save_child( $product_id, $data = '' )
                {
                    global $blog_id;
                    
                    if ( empty ( $data ) )
                        $data = $_POST;
                    
                    $child_product      =   new WooGC_PS_child_product( $product_id );
                    $child_blog_id      =   $blog_id;
                    $main_product_data  =   $child_product->get_main_data();
                    
                    switch_to_blog( $main_product_data['main_blog_id'] );
                    
                    $args   =   array ();
                    $args['_woogc_ps_sync_to'][$child_blog_id]              =   $data['_woogc_ps_sync_to'][$blog_id];
                    $args['_woogc_ps_maintain_child'][$child_blog_id]       =   $data['_woogc_ps_maintain_child'][$blog_id];
                    $args['_woogc_ps_maintain_categories'][$child_blog_id]  =   $data['_woogc_ps_maintain_categories'][$blog_id];
                    $args['_woogc_ps_maintain_stock'][$child_blog_id]       =   $data['_woogc_ps_maintain_stock'][$blog_id];
                    
                    $args['child_update']   =   TRUE;
                    
                    self::product_save_main( $main_product_data['main_id'], $args );
     
                    restore_current_blog();
              
                }
            
            
            
                           
                
            /**
            * Synchronize the product ID to specified shops
            * 
            * @param mixed $product_ID
            * @param mixed $sync_to
            * @param mixed $args
            */
            static function async_synchronize_to ( $product_ID, $sync_to, $args, $_post_data = array() )
                {
                    
                    $PS             =   new WooGC_PS();
                    
                    if ( ! is_array ( $sync_to )   ||  count ( $sync_to ) < 1 )
                        return;
                    
                    global $WooGC;                   
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'wp_after_insert_post', 'WC_Products_Tracking', 'track_product_published' );

                    
                    global $blog_id;
                                        
                    $product_type       = WC_Product_Factory::get_product_type( $product_ID );
                    $classname          = WC_Product_Factory::get_product_classname( $product_ID, $product_type ? $product_type : 'simple' );
                    $product            = new $classname( $product_ID );
                    
                    $main_product   =   new WooGC_PS_main_product( $product_ID );
                    
                    if ( $product->get_type() == 'variable' )
                        $product->get_children();
                    
                    global $interface_messages;
                    $interface_messages =   array();
                    
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                    foreach ( $sites as $key    =>  $site )
                        {
                            if ( array_search ( $site->blog_id, $sync_to )  === FALSE )
                                unset ( $sites[ $key ] );
                        }
                        
                    //allow to disable programatially certain shops
                    $sites  =   apply_filters( 'woogc/ps/interfaces/synchronize_to_sites', $sites );
                    
                    $sync_to = array_values ( array_map( function($obj) { return $obj->blog_id; }, $sites ) );
                                            
                    foreach ( $sync_to as  $key =>  $shop_id )
                        {
                                 
                            //Unlink as processed
                            unset ( $sync_to[ $key ] );
                            
                            //check if the $shop_id still exists
                            $site_details = get_blog_details( $shop_id );
                            if ( $site_details  === FALSE )
                                continue;
                            
                            $sync_args  =   array ( );    
                            if ( array_search ( $shop_id, $args['maintain_child'] ) !== FALSE )
                                $sync_args['maintain_child']    =   TRUE;
                                else
                                $sync_args['maintain_child']    =   FALSE;
                            if ( array_search ( $shop_id, $args['maintain_categories'] ) !== FALSE )
                                $sync_args['maintain_categories']    =   TRUE;
                                else
                                $sync_args['maintain_categories']    =   FALSE;
                            if ( array_search ( $shop_id, $args['maintain_stock'] ) !== FALSE )
                                $sync_args['maintain_stock']    =   TRUE;
                                else
                                $sync_args['maintain_stock']    =   FALSE;
                            
                            if ( $main_product->get_child_at_shop( $shop_id )    >   0  &&  $sync_args['maintain_child']   === FALSE )
                                continue;    
                            
                            $synchronized_product_ID    =   $PS->synchronize_product( $product, $shop_id, $sync_args );
                            
                            $attributes =   $product->get_attributes();
                            if ( count ( $attributes )  >   0 )
                                {
                                    $terms_ids  =   $PS->synchronize_attributes( $attributes, $shop_id );
                                    
                                    switch_to_blog( $shop_id );
                                    foreach ( $terms_ids    as  $terms_id =>    $taxonomy_name )
                                        {
                                            wp_set_post_terms( $synchronized_product_ID, array ( intval ( $terms_id ) ), $taxonomy_name, TRUE );
                                        }
                                    restore_current_blog();
                                }

                            //check for grouped type
                            if ( $product->get_type() == 'grouped' )
                                {
                                    $found_sync_products    =   array();
                                    if ( count ( $product->get_children() ) > 0 )
                                        {
                                            foreach ( $product->get_children() as  $group_product_id )
                                                {
                                                    $found_product_ID   =   $PS->get_product_synchronized_at_shop( $group_product_id, $blog_id, $shop_id );
                                                    if ( $found_product_ID > 0 )
                                                        $found_sync_products[ $group_product_id ]   =   $found_product_ID; 
                                                }
                                            
                                            switch_to_blog( $shop_id ); 
                                               
                                            $product_type       = WC_Product_Factory::get_product_type( $synchronized_product_ID );
                                            $classname          = WC_Product_Factory::get_product_classname( $synchronized_product_ID, $product_type ? $product_type : 'simple' );
                                            $sync_product       = new $classname( $synchronized_product_ID );
                                            
                                            $sync_product->set_children( array_values( $found_sync_products ) );
                                            $sync_product->save();
                                            
                                            restore_current_blog();
                                        }      
                                    
                                }
                            
                            //check for variations
                            if ( $product->get_type() == 'variable' )
                                {
                                    
                                    $sync_args['synchronized_product_id']   =   $synchronized_product_ID;
                                    
                                    $found_sync_products    =   array();
                                    if ( count ( $product->get_children() ) > 0 )
                                        {
                                            foreach ( $product->get_children() as  $variation_id )
                                                {
                                                    //check if the variation is syncronized on local shop
                                                    $main_product_variation =   new WC_Product_Variation( $variation_id );
                                                    $variation_sync_product_ID    =   $PS->synchronize_product( $main_product_variation, $shop_id, $sync_args );
                                                    $found_sync_products[]  =   $variation_sync_product_ID;
                                                }
                                        }
                                        
                                    //ensure the synchronized product variations are the same as main
                                    switch_to_blog( $shop_id );
                                    
                                    $child_product  =   wc_get_product( $synchronized_product_ID );
                                    $child_product_variations   =   $child_product->get_children();
                                    
                                    $diff   =   array_diff ( $child_product_variations, $found_sync_products );
                                    if  ( count ( $diff ) > 0 )
                                        {
                                            foreach ( $diff  as $variation_id )
                                                {
                                                    wp_delete_post( $variation_id );
                                                }
                                        }
                                    
                                    restore_current_blog();
                                    
                                    
                                    
                                }
                            
                
                        }   
                    
                    
                    $PS->clean_internal_messages();
                    
                    return TRUE;
                    
                    
                }
                
                
                
            
            /**
             * Bulk edit.
             *
             * @param int        $post_id Post ID being saved.
             * @param WC_Product $product Product object.
             */
            public function woocommerce_product_bulk_edit_save( $_post_data, $_get_data ) 
                {
                    $this->setup_execution_time();
                    
                    global $blog_id;
                    
                    $PS             =   new WooGC_PS();
                    
                    foreach ( $_get_data['post']     as  $key    =>  $post_id )
                        {
                            
                            //create a new batch if the timout is almost reached
                            if ( ! $this->can_continue_code_execution() )
                                {                                    
                                    self::async_start_bulk_edit( $post_id, $_post_data, $_get_data );
                                    
                                    return FALSE;
                                }
                            
                            foreach ( $_get_data['_woogc_ps_sync_to']     as  $loop_shop_id   =>  $do_sync )
                                {
                                    if ( $do_sync === 'no' )
                                        continue;
                                    
                                    $switched_blog  =   FALSE;
                                    if ( $PS->is_child_product( $post_id ) )
                                        {
                                            $child_product      =   new WooGC_PS_child_product( $post_id );
                                            
                                            if ( $child_product->get_main_shop_id() !=  $loop_shop_id )
                                                continue;
                                            
                                            //$child_blog_id      =   $blog_id;
                                            
                                            $product_id         =   $child_product->get_main_id();
                                            $shop_id            =   $child_product->get_main_shop_id();
                                            $product_shop_id    =   $blog_id;
                                               
                                            switch_to_blog( $child_product->get_main_shop_id() );
                                            $switched_blog  =   TRUE;
                              
                                        }
                                        else
                                        {
                                            $product_id         =   $post_id;
                                            $shop_id            =   $loop_shop_id;
                                            $product_shop_id    =   $loop_shop_id;   
                                        }
                                    
                                    $main_product                   =   new WooGC_PS_main_product( $product_id );
                                    
                                    //main product
                                    $product_sync_to_shops          =   $main_product->get_children();
                                    $product_maintain_child         =   $main_product->get_maintained();
                                    $product_maintain_categories    =   $main_product->get_maintained_categories();
                                    $product_maintain_stock         =   $main_product->get_maintained_stock();
                                    
                                    $sync_to    =   $_get_data['_woogc_ps_sync_to'][ $shop_id ];
                                    switch ( $sync_to )
                                        {
                                            case 'yes'  :
                                                            if ( array_search ( $product_shop_id, $product_sync_to_shops )    === FALSE )
                                                                $product_sync_to_shops[]    =   $product_shop_id;
                                                            break;
                                            case 'no'   :
                                                            if ( array_search ( $product_shop_id, $product_sync_to_shops )    !== FALSE )
                                                                unset ( $product_sync_to_shops[ array_search ( $product_shop_id, $product_sync_to_shops ) ] );
                                                            break;
                                                            
                                            default:
                                                            if ( array_search ( $product_shop_id, $product_sync_to_shops )    === FALSE )
                                                                continue 2;    
                                        }
                                    
                                    $maintain_child    =   $_get_data['_woogc_ps_maintain_child'][ $shop_id ];
                                    switch ( $maintain_child )
                                        {
                                            case 'yes'  :
                                                            if ( array_search ( $product_shop_id, $product_maintain_child )    === FALSE )
                                                                $product_maintain_child[]    =   $product_shop_id;
                                                            break;
                                            case 'no'   :
                                                            if ( array_search ( $product_shop_id, $product_maintain_child )    !== FALSE )
                                                                unset ( $product_maintain_child[ array_search ( $product_shop_id, $product_maintain_child ) ] );
                                                            break;   
                                        }
                                    
                                    $maintain_categories    =   $_get_data['_woogc_ps_maintain_categories'][ $shop_id ];
                                    switch ( $maintain_categories )
                                        {
                                            case 'yes'  :
                                                            if ( array_search ( $product_shop_id, $product_maintain_categories )    === FALSE )
                                                                $product_maintain_categories[]    =   $product_shop_id;
                                                            break;
                                            case 'no'   :
                                                            if ( array_search ( $product_shop_id, $product_maintain_categories )    !== FALSE )
                                                                unset ( $product_maintain_categories[ array_search ( $product_shop_id, $product_maintain_categories ) ] );
                                                            break;   
                                        }
                                    
                                    $maintain_stock    =   $_get_data['_woogc_ps_maintain_stock'][ $shop_id ];
                                    switch ( $maintain_stock )
                                        {
                                            case 'yes'  :
                                                            if ( array_search ( $product_shop_id, $product_maintain_stock )    === FALSE )
                                                                $product_maintain_stock[]    =   $product_shop_id;
                                                            break;
                                            case 'no'   :
                                                            if ( array_search ( $product_shop_id, $product_maintain_stock )    !== FALSE )
                                                                unset ( $product_maintain_stock[ array_search ( $product_shop_id, $product_maintain_stock ) ] );
                                                            break;   
                                        }
                                        
                                    $main_product->set_as_main_product();
                                    $main_product->set_sync_to( $product_sync_to_shops );
                                    $main_product->set_mintained( $product_maintain_child );
                                    $main_product->set_mintained_categories( $product_maintain_categories );
                                    $main_product->set_mintained_stock( $product_maintain_stock );
                                    
                                    if ( ! $PS->is_child_product( $product_id ) )
                                        {
                                            $args   =   array  (
                                                                    'maintain_child'        =>  $product_maintain_child,
                                                                    'maintain_categories'   =>  $product_maintain_categories,
                                                                    'maintain_stock'        =>  $product_maintain_stock,
                                                                    );
                                            $interface_messages     =   self::async_synchronize_to( $product_id, array( $product_shop_id ), $args, $_post_data );
                                        }
                                    
                                    if ( $switched_blog )
                                        restore_current_blog();    
                                    
                                }
                            
                            unset ( $_get_data['post'][ $key ] );
                            
                        }
                }
                
                
        }
        
        
    