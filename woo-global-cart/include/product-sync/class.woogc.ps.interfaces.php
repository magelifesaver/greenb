<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS_interfaces
        {
            
            var $PS;
            
            /**
             * __construct function.
             *
             * @access public
             * @return void
             */
            public function __construct() 
                {
                    $this->PS    =   new WooGC_PS();                   
                }
                
                
            public function register_actions()
                {
                    add_action('load-post.php',                             array($this, 'init'));
                    add_action('load-post-new.php',                         array($this, 'init'));
                    
                    //add the custom column
                    add_filter( 'manage_edit-product_columns',              array( $this, 'product_column' ) );
                    add_action( 'manage_product_posts_custom_column' ,      array( $this, 'product_column_content' ), 10, 2 );
                    
                    add_action( 'quick_edit_custom_box',                    array( $this, 'quick_edit_custom_box' ), 99, 2 );
                    add_action( 'bulk_edit_custom_box',                     array( $this, 'bulk_edit_custom_box' ), 99, 2 );
                    add_action( 'add_inline_data',                          array( $this, 'add_inline_data' ) );
                    add_action( 'admin_enqueue_scripts',                    array( $this, 'quick_bulk_admin_scripts' ) );
                    add_action( 'woocommerce_product_quick_edit_save',      array( $this, 'woocommerce_product_quick_edit_save' ), 99 );
                    //add_action( 'woocommerce_product_bulk_edit_save',       array( $this, 'woocommerce_product_bulk_edit_save' ), 99 );
                    add_action( 'bulk_edit_posts',                          array( $this, 'woocommerce_product_bulk_edit_save' ), 99 );
                    
                    add_action( 'before_delete_post',                       array( $this, 'before_delete_post' ), 10, 2 );
                }
            
            
            public function init()
                {
                         
                    $current_screen             =   get_current_screen();

                    if( is_object ( $current_screen )   &&  $current_screen->post_type  ==  'product' )
                        $this->load_interfaces();   
                    
                }
                
                
            private function load_interfaces()
                {
                    // Hooks
                    add_action( 'woocommerce_product_write_panel_tabs',     array( $this, 'product_write_panel_tab' ), -1 );
                    add_action( 'woocommerce_product_data_panels',          array( $this, 'product_write_panel' ) );
                    //add_filter( 'woocommerce_process_product_meta',         array( $this, 'product_save_data' ), 999 );
                    add_action( 'save_post',                                array( $this, 'product_save_data' ), 999, 2 );
                    
                    //enqueue css & js for product post type
                    add_action( 'admin_enqueue_scripts',                    array ( $this, 'interface_scripts' ) );
                    
                    add_filter( 'admin_notices',                            array( $this, 'admin_notices' ) );
                    
                }
                
            public function interface_scripts()
                {                   
                    $CSS_file = WOOGC_URL . '/css/woogc-ps-interfaces.css';
                    wp_register_style('woogc-ps-interfaces', $CSS_file );
                    wp_enqueue_style( 'woogc-ps-interfaces');
                    
                    $JS_file = WOOGC_URL . '/js/woogc-ps-interfaces.js';
                    wp_register_script('woogc-ps-interfaces', $JS_file );
                    wp_enqueue_script( 'woogc-ps-interfaces');        
                }
                
                
            

    
     
            /**
            * adds Licence tab to the product interface
            */
            public function product_write_panel_tab() 
                {
                    global $post;
                    
                    $product_sync_to_shops   =   (array)get_post_meta( $post->ID, '_woogc_ps_sync_to', TRUE);
                    
                    ?>
                        <li class="woogc_ps"><a href="#woogc_ps"><span><?php _e( 'Product Sync', 'woo-global-cart'); ?></span> <?php
                        
                            if ( ! $this->PS->is_child_product( $post->ID ) )
                                echo '<span class="dashicons dashicons-networking" style="font-size: 14px; vertical-align: middle;"></span>';
                                else
                                echo '<span class="dashicons dashicons-download" style="font-size: 14px; vertical-align: middle;"></span>';
                        
                        ?></a></li>
                    <?php
                }

                   
            /**
            * Add the appropiate panel
            * 
            */
            public function product_write_panel() 
                {
                    global $post;
                    
                    if ( ! $this->PS->is_child_product( $post->ID ) )
                        $this->product_write_panel_main();
                        else
                        $this->product_write_panel_child();
                    
                }
            
            
            
            function product_write_panel_main() 
                {
                    ?>
                        <div id="woogc_ps" class="panel woocommerce_options_panel">
                            <p><?php _e( "In this section, you can control the synchronisation shops and options.", 'woo-global-cart' ) ?></p>
                            
                            <div class="options_group">
                                
                                <?php
                                    
                                    global $blog_id, $WooGC, $post;
                                    
                                    $main_product  =   new WooGC_PS_main_product( $post->ID );
                                    
                                    $product_sync_to_shops          =   $main_product->get_children();
                                    $product_maintain_child         =   $main_product->get_maintained();
                                    $product_maintain_categories    =   $main_product->get_maintained_categories();
                                    $product_maintain_stock         =   $main_product->get_maintained_stock();
                                    
                                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                                    $sites  =   apply_filters( 'woogc/ps/interfaces/synchronize_to_sites', $sites );
                                    
                                    $count  =   1;
                                    
                                    foreach ( $sites    as  $site )
                                        {
                                            if ( $site->blog_id ==  $blog_id )
                                                continue;
                                            
                                            $blog_details = get_blog_details( $site->blog_id );
                                            
                                            $sync_to_shop           =   apply_filters ( 'woogc/ps/interfaces/sync_to_shop',         in_array ( $blog_details->blog_id, $product_sync_to_shops ), $blog_id, $post );
                                            $maintain_child         =   apply_filters ( 'woogc/ps/interfaces/maintain_child',       in_array ( $blog_details->blog_id, $product_maintain_child ), $blog_id, $post );
                                            $maintain_categories    =   apply_filters ( 'woogc/ps/interfaces/maintain_categories',  in_array ( $blog_details->blog_id, $product_maintain_categories ), $blog_id, $post );
                                            $maintain_stock         =   apply_filters ( 'woogc/ps/interfaces/maintain_stock',       in_array ( $blog_details->blog_id, $product_maintain_stock ), $blog_id, $post );
                                            
                                            ?>
                                            <div class="shop_ps">
                                                <table class="shop_ps_items">
                                                    <?php 
                                                    if ( $count < 2 ) {
                                                        $first  =   FALSE;
                                                    ?>
                                                    <thead>
                                                        <tr>
                            
                                                            <th class="shop_title"><?php _e( "Shop Title", 'woo-global-cart' ) ?></th>
                                                            <th><?php _e( "Enable Synchronization", 'woo-global-cart' ) ?></th>
                                                        </tr>            
                                                    </thead>
                                                    <?php } ?>
                                                    <tbody>
                                                        <tr class="<?php 
                                                        
                                                        if ( $count % 2 == 0) 
                                                            echo "even"; 
                                                            else 
                                                            echo "odd";
                                                        
                                                        ?>">
                                       
                                                            <td class="shop_title"><h4><?php echo  $blog_details->blogname ?></h4><small class="site-url"><?php echo  $blog_details->domain ?></small> 
                                                            <p class="site-product"><?php
                                                            
                                                            if ( ( $found_product_ID  =   $main_product->get_child_at_shop( $blog_details->blog_id ) ) !==  FALSE )
                                                                {
                                                                    ?><small><span class="dashicons dashicons-admin-generic"></span> <a target="_blank" href="<?php
                                                                    
                                                                        switch_to_blog( $blog_details->blog_id );
                                                                        echo get_edit_post_link( $found_product_ID );
                                                                        restore_current_blog();
                                                                    
                                                                    ?>"><?php _e( 'View Product' ) ?></a></small></p></td><?php
                                                                }
                                                            
                                                            ?>
                                                            <td class="holder">
                                                                <a class="woogc-input-toggle" href="#">
                                                                    <span class="woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                    
                                                                    if ( $sync_to_shop )
                                                                        echo 'enabled';
                                                                        else
                                                                        echo 'disabled';                                                                    
                                                                    
                                                                    ?>"></span>
                                                                </a> 
                                                                
                                                                <input type="hidden" class="toggle_input" name="_woogc_ps_sync_to[<?php echo $blog_details->blog_id ?>]" value="<?php
                                                                
                                                                if ( $sync_to_shop )
                                                                        echo 'yes';
                                                                        else
                                                                        echo 'no';                                                                
                                                                
                                                                ?>" />
                                                            </td>
                                                        </tr>
                                                           
                                                    </tbody>
                                                </table>
                                            
                                                <div class="details<?php if ( ! $sync_to_shop ) { echo ' hide';}  ?>">
                                                    
                                                    <table class="shop_ps_options">
                                                        <tbody>
                                                            <tr>
                                           
                                                                <td class="option_title"></td>
                                                                <td><a class="woogc-input-toggle" href="#">
                                                                        <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                        
                                                                        if ( $maintain_child )
                                                                            echo 'enabled';
                                                                            else
                                                                            echo 'disabled';                                                                    
                                                                        
                                                                        ?>"></span></a>
                                                                    
                                                                    <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_child[<?php echo $blog_details->blog_id ?>]" value="<?php
                                                                    
                                                                    if ( $maintain_child )
                                                                            echo 'yes';
                                                                            else
                                                                            echo 'no';                                                                
                                                                    
                                                                    ?>" />
                                                                <?php _e( "Maintain Child Product Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "When the current Product change, the Child Product get updated as well.", 'woo-global-cart' ) ?></small></td>
                                                            </tr>
                                                            <tr>
                                           
                                                                <td class="option_title"></td>
                                                                <td><a class="woogc-input-toggle" href="#">
                                                                        <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                        
                                                                        if ( $maintain_categories )
                                                                            echo 'enabled';
                                                                            else
                                                                            echo 'disabled';                                                                    
                                                                        
                                                                        ?>"></span></a>
                                                                    
                                                                    <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_categories[<?php echo $blog_details->blog_id ?>]" value="<?php
                                                                    
                                                                    if ( $maintain_categories )
                                                                            echo 'yes';
                                                                            else
                                                                            echo 'no';                                                                
                                                                    
                                                                    ?>" />
                                                                <?php _e( "Categories and Tags Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "Synchronize the product categories and tags. If the term is not found on the child store, it will be created.", 'woo-global-cart' ) ?></small></td>
                                                            </tr>
                                                            <tr>
                                           
                                                                <td class="option_title"></td>
                                                                <td><a class="woogc-input-toggle" href="#">
                                                                        <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                        
                                                                        if ( $maintain_stock )
                                                                            echo 'enabled';
                                                                            else
                                                                            echo 'disabled';                                                                    
                                                                        
                                                                        ?>"></span></a>
                                                                    
                                                                    <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_stock[<?php echo $blog_details->blog_id ?>]" value="<?php
                                                                    
                                                                    if ( $maintain_stock )
                                                                            echo 'yes';
                                                                            else
                                                                            echo 'no';                                                                
                                                                    
                                                                    ?>" /><?php _e( "Stock Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "Any stock changes on the products across the network using \"Stock Synchronization\", updates others stock too.", 'woo-global-cart' ) ?></small></td>
                                                            </tr>
                                                               
                                                        </tbody>
                                                    </table>
                           
                                                </div>
                                            </div>    
                                            <?php
                                            
                                            $count++;
                                        } 
                                
                                ?>
                               
                            </div>
                        </div>
                    
                    <?php   

                }

                
            function product_write_panel_child() 
                {
                    global $blog_id, $post;
                    
                    $child_product  =   new WooGC_PS_child_product( $post->ID );
                    
                    $main_product_data  =   $child_product->get_main_data();
                    
                    ?>
                        <div id="woogc_ps" class="panel woocommerce_options_panel">
                            <p><?php _e( "In this section, you can control the synchronisation options.", 'woo-global-cart' ) ?></p>
                            <p><b><span class="dashicons dashicons-download" style="font-size: 14px; vertical-align: middle;"></span> <?php _e( "This is a Child product.", 'woo-global-cart' ); ?></b></p>
                            <div class="options_group">
                                
                                <?php
             
                                    $blog_details = get_blog_details( $main_product_data['main_blog_id'] );
                                    
                                    ?>
                                    <div class="shop_ps">
                                        <table class="shop_ps_items">
                                    
                                            <thead>
                                                <tr>
                    
                                                    <th class="shop_title"><?php _e( "Origin Shop Title", 'woo-global-cart' ) ?></th>
                                                    <th><?php _e( "Enable Synchronization", 'woo-global-cart' ) ?></th>
                                                </tr>            
                                            </thead>

                                            <tbody>
                                                <tr class="even">
                               
                                                    <td class="shop_title"><h4><?php echo  $blog_details->blogname ?></h4><small><?php echo  $blog_details->domain ?></small> 
                                                        <p class="site-product"><small><span class="dashicons dashicons-admin-generic"></span> <a target="_blank" href="<?php
                                                            
                                                                switch_to_blog( $main_product_data['main_blog_id'] );
                                                                echo get_edit_post_link( $main_product_data['main_id'] );
                                                                restore_current_blog();
                                                            
                                                            ?>"><?php _e( 'View Origin Product' ) ?></a></small></p></td><?php
                                                       
                                                    
                                                    ?>
                                                    <td class="holder">
                                                        <a class="woogc-input-toggle" href="#">
                                                            <span class="woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                            
                                                            if ( $child_product->is_sync() )
                                                                echo 'enabled';
                                                                else
                                                                echo 'disabled';                                                                    
                                                            
                                                            ?>"></span>
                                                        </a> 
                                                        
                                                        <input type="hidden" class="toggle_input" name="_woogc_ps_sync_to[<?php echo $child_product->get_main_shop_id() ?>]" value="<?php
                                                        
                                                        if ( $child_product->is_sync() )
                                                                echo 'yes';
                                                                else
                                                                echo 'no';                                                                
                                                        
                                                        ?>" />
                                                    </td>
                                                </tr>
                                                   
                                            </tbody>
                                        </table>
                                    
                                        <div class="details">
                                            
                                            <table class="shop_ps_options">
                                                <tbody>
                                                    <tr>
                                   
                                                        <td class="option_title"></td>
                                                        <td><a class="woogc-input-toggle" href="#">
                                                                <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                
                                                                if ( $child_product->is_maintained_sync() )
                                                                    echo 'enabled';
                                                                    else
                                                                    echo 'disabled';                                                                    
                                                                
                                                                ?>"></span></a>
                                                            
                                                            <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_child[<?php echo $child_product->get_main_shop_id() ?>]" value="<?php
                                                            
                                                            if ( $child_product->is_maintained_sync() )
                                                                    echo 'yes';
                                                                    else
                                                                    echo 'no';                                                                
                                                            
                                                            ?>" /><?php _e( "Maintain Child Product Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "When the parent Product change, this Child Product get updated as well.", 'woo-global-cart' ) ?></small></td>
                                                    </tr>
                                                    <tr>
                                   
                                                        <td class="option_title"></td>
                                                        <td><a class="woogc-input-toggle" href="#">
                                                                <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                
                                                                if ( $child_product->is_categories_sync() )
                                                                    echo 'enabled';
                                                                    else
                                                                    echo 'disabled';                                                                    
                                                                
                                                                ?>"></span></a>
                                                            
                                                            <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_categories[<?php echo $child_product->get_main_shop_id() ?>]" value="<?php
                                                            
                                                            if ( $child_product->is_categories_sync() )
                                                                    echo 'yes';
                                                                    else
                                                                    echo 'no';                                                                
                                                            
                                                            ?>" /><?php _e( "Categories and Tags Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "Synchronize the parent product categories and tags. If the term is not found on the current store, it will be created.", 'woo-global-cart' ) ?></small></td>
                                                    </tr>
                                                    <tr>
                                   
                                                        <td class="option_title"></td>
                                                        <td><a class="woogc-input-toggle" href="#">
                                                                <span class="small woocommerce-input-toggle woocommerce-input-toggle--<?php
                                                                
                                                                if ( $child_product->is_stock_sync() )
                                                                    echo 'enabled';
                                                                    else
                                                                    echo 'disabled';                                                                    
                                                                
                                                                ?>"></span></a>
                                                            
                                                            <input type="hidden" class="toggle_input" name="_woogc_ps_maintain_stock[<?php echo $child_product->get_main_shop_id() ?>]" value="<?php
                                                            
                                                            if ( $child_product->is_stock_sync() )
                                                                    echo 'yes';
                                                                    else
                                                                    echo 'no';                                                                
                                                            
                                                            ?>" /><?php _e( "Stock Synchronization", 'woo-global-cart' ) ?><br /><small><?php _e( "Any stock changes on the products across the network using \"Stock Synchronization\", updates others stock too.", 'woo-global-cart' ) ?></small></td>
                                                    </tr>
                                                       
                                                </tbody>
                                            </table>
                   
                                        </div>
                                    </div>    
                                                                 
                            </div>
           
                        </div>
                    
                    <?php   

                }

                
            /**
             * Saves the data for the SL Tab product writepanel input boxes
             */
            public function product_save_data( $post_id, $post ) 
                {
                    $post_id = absint( $post_id );

                    // $post_id and $post are required
                    if ( empty( $post_id ) || empty( $post ) ) {
                        return;
                    }

                    // Dont' save meta boxes for revisions or autosaves.
                    if ( ( defined ( 'DOING_AUTOSAVE' )  &&  ( DOING_AUTOSAVE   === TRUE ) ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
                        return;
                    }

                    // Check the nonce.
                    if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        return;
                    }

                    // Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
                    if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
                        return;
                    }

                    // Check user has permission to edit.
                    if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return;
                    }
                    
                    $options    =   WooGC_Functions::get_options();
                    
                    if ( $options['product_synchronization_op_type']  ==  'cron_async' )
                        WooGC_PS_Async::async_start_product_save( $post_id, $_POST, 'product_save' );
                        else
                        {
                            if ( $this->PS->is_child_product( $post_id ) )
                                $this->product_save_child( $post_id );
                                else
                                $this->product_save_main( $post_id );   
                        }
                    
                }
                
            
            public function product_save_main( $product_id, $data = '' )
                {
                    if ( empty ( $data ) )
                        $data = $_POST;
                    
                    $main_product  =   new WooGC_PS_main_product( $product_id );
                    $main_product_sync_to_shops         =   $main_product->get_children();
                    $main_product_maintain_child        =   $main_product->get_maintained();
                    $main_product_maintain_categories   =   $main_product->get_maintained_categories();
                    $main_product_maintain_stock        =   $main_product->get_maintained_stock();
                        
                    $_woogc_ps_sync_to  =   FALSE;
                    if ( isset ( $data['_woogc_ps_sync_to'] )  &&  is_array ( $data['_woogc_ps_sync_to'] ) )
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
                    $interface_messages     =   $this->PS->synchronize_to( $product_id, $main_product_sync_to_shops, $args );
                    
                    if ( isset ( $data['return_interface_response'] )   &&  $data['return_interface_response']  === TRUE )
                        {
                            return $interface_messages;   
                        }
                    
                    else if ( isset ( $_POST['post_ID'] ) )
                        {
                            $current_user = wp_get_current_user();
                            update_user_meta( $current_user->ID, '_woogc/ps/interface/messages', $interface_messages );
                        }   
                }
                
                
            public function product_save_child( $product_id, $data = '' )
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
                    
                    $this->product_save_main( $main_product_data['main_id'], $args );
     
                    restore_current_blog();
              
                }
            
            
            
            function admin_notices()
                {
                    $current_user = wp_get_current_user();
                    $interface_messages =   get_user_meta( $current_user->ID, '_woogc/ps/interface/messages', TRUE ) ;
                    delete_user_meta( $current_user->ID, '_woogc/ps/interface/messages' );
                    
                    //only in the edit interface
                    if ( ! isset ( $_GET['action'] )    ||  $_GET['action'] !=  'edit' )
                        $interface_messages =   array();
                    
                    if ( ! is_array ( $interface_messages ) ||  count ( $interface_messages ) < 1 )
                        return;
                        
                    foreach ( $interface_messages   as  $interface_message )
                        {
                            ?>
                            <div class="notice notice-<?php echo $interface_message['type'] ?> is-dismissible">
                                <p><?php echo $interface_message['message'] ?></p>
                            </div>
                            <?php
                        }
                }
                
            function product_column( $columns )
                {
                   $columns =   array_slice( $columns, 0, 3, true) + array( 'ps-product' => __( 'Sync','woocommerce') ) + array_slice( $columns, 3, NULL, TRUE );
                  
                   return $columns;
                }
                
                
            function product_column_content( $column, $product_id )
                {
                    global $post;
                    
                    if ( $column != 'ps-product'  )
                        return;

                    if ( $this->PS->is_main_product( $post->ID ) )
                        echo '<span data-tip="Main Product" class="dashicons dashicons-networking tips" style="color:#959595;"></span>';
                    
                    else if ( $this->PS->is_child_product( $post->ID ) )
                        echo '<span data-tip="Synchronized/Child Product" class="dashicons dashicons-download tips" style="color:#959595; font-size: 18px;"></span>';
                    
                    else echo '-';
                    
                }
                
                
                
            function quick_edit_custom_box( $column_name, $post_type )
                {
                    if ( 'price' !== $column_name || 'product' !== $post_type  )
                        return;
        
                    include_once(WOOGC_PATH . '/include/product-sync/views/html-quick-edit-product.php');
                    
                }
                
            
            function bulk_edit_custom_box( $column_name, $post_type )
                {
                    if ( 'price' !== $column_name || 'product' !== $post_type  )
                        return;
                    
                    include_once(WOOGC_PATH . '/include/product-sync/views/html-bulk-edit-product.php');
                }
            
            
            function add_inline_data( $post )
                {
                               
                    if ( ! $this->PS->is_child_product( $post->ID ) )
                        {
                    
                            $main_product  =   new WooGC_PS_main_product( $post->ID );
                            
                            $product_sync_to_shops          =   $main_product->get_children();
                            $product_maintain_child         =   $main_product->get_maintained();
                            $product_maintain_categories    =   $main_product->get_maintained_categories();
                            $product_maintain_stock         =   $main_product->get_maintained_stock();
                            ?>
                            <div class="_woogc_ps_is_main_product">yes</div>
                            <div class="_woogc_ps_sync_to"><?php echo esc_html( implode ( "," , $product_sync_to_shops ) ); ?></div>
                            <div class="_woogc_ps_maintain_child"><?php echo esc_html( implode ( "," , $product_maintain_child ) ); ?></div>
                            <div class="_woogc_ps_maintain_categories"><?php echo esc_html( implode ( "," , $product_maintain_categories ) ); ?></div>
                            <div class="_woogc_ps_maintain_stock"><?php echo esc_html( implode ( "," , $product_maintain_stock ) ); ?></div>
                            <?php
                        }
                        else
                        {
                            $child_product  =   new WooGC_PS_child_product( $post->ID );
            
                            $main_product_data  =   $child_product->get_main_data();
      
                            $product_sync_to_shops          =   $child_product->is_sync()                   ? array ( $child_product->get_main_shop_id() )  :   array();
                            $product_maintain_child         =   $child_product->is_maintained_sync()        ? array( $child_product->get_main_shop_id() )   :   array();
                            $product_maintain_categories    =   $child_product->is_categories_sync()        ? array( $child_product->get_main_shop_id() )   :   array();
                            $product_maintain_stock         =   $child_product->is_stock_sync()             ? array( $child_product->get_main_shop_id() )   :   array();
                            ?>
                            <div class="_woogc_ps_is_main_product">no</div>
                            <div class="_woogc_ps_sync_to"><?php echo esc_html( implode ( "," , $product_sync_to_shops ) ); ?></div>
                            <div class="_woogc_ps_maintain_child"><?php echo esc_html( implode ( "," , $product_maintain_child ) ); ?></div>
                            <div class="_woogc_ps_maintain_categories"><?php echo esc_html( implode ( "," , $product_maintain_categories ) ); ?></div>
                            <div class="_woogc_ps_maintain_stock"><?php echo esc_html( implode ( "," , $product_maintain_stock ) ); ?></div>
                            
                            <div class="_woogc_ps_parent_bid"><?php echo intval( $child_product->get_main_shop_id() ) ?></div>
                            <?php
                            
                        }
               
                }
            
            function quick_bulk_admin_scripts()
                {
                    $screen       = get_current_screen();
                    $screen_id    = $screen ? $screen->id : '';
                        
                    if ( in_array( $screen_id, array( 'edit-product' ) ) ) 
                        {
                            wp_enqueue_script( 'woogc_quick-edit',      WOOGC_URL . '/js/woogc-quick-edit.js', array( 'jquery', 'woocommerce_admin' ) );
                            
                            wp_enqueue_style( 'woogc-ps-interfaces',    WOOGC_URL . '/css/woogc-ps-interfaces.css');
                            wp_enqueue_style( 'woogc-ps-quick-edit',    WOOGC_URL . '/css/woogc-quick-edit.css');
                            wp_enqueue_script( 'woogc-ps-interfaces',   WOOGC_URL . '/js/woogc-ps-interfaces.js');
                        }
                    
                }
            
                               
                
            /**
             * Quick edit.
             *
             * @param int        $post_id Post ID being saved.
             * @param WC_Product $product Product object.
             */
            function woocommerce_product_quick_edit_save( $product ) 
                {                    
                    $options    =   WooGC_Functions::get_options();
                    
                    if ( $options['product_synchronization_op_type']  ==  'cron_async' )
                        WooGC_PS_Async::async_start_product_save( $product->get_ID(), $_POST, 'product_save' );
                        else
                        {
                            if ( $this->PS->is_child_product( $post_id ) )
                                $this->product_save_child( $product->get_ID() );
                                else
                                $this->product_save_main( $product->get_ID() );   
                        }                    
                }

            /**
             * Bulk edit.
             *
             * @param int        $post_id Post ID being saved.
             * @param WC_Product $product Product object.
             */
            public function woocommerce_product_bulk_edit_save( $product_list ) 
                {
                    $options    =   WooGC_Functions::get_options();
                    
                    if ( $options['product_synchronization_op_type']  ==  'cron_async' &&   WooGC_PS_Async::$AsyncEnqueued    === FALSE )
                        {
                            WooGC_PS_Async::async_start_bulk_edit( $product_list, $_POST, $_GET );
                            return;
                        }
                        
                    global $blog_id;
                    
                    foreach ( $_GET['post']     as  $post_id )
                        {
                            foreach ( $_GET['_woogc_ps_sync_to']     as  $loop_shop_id   =>  $do_sync )
                                {
                                    
                                    if ( $do_sync === 'no' )
                                        continue;
                                    
                                    $switched_blog  =   FALSE;
                                    if ( $this->PS->is_child_product( $post_id ) )
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
                                    
                                    $sync_to    =   $_GET['_woogc_ps_sync_to'][ $shop_id ];
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
                                                            
                                                            break;   
                                        }
                                    
                                    $maintain_child    =   $_GET['_woogc_ps_maintain_child'][ $shop_id ];
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
                                    
                                    $maintain_categories    =   $_GET['_woogc_ps_maintain_categories'][ $shop_id ];
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
                                    
                                    $maintain_stock    =   $_GET['_woogc_ps_maintain_stock'][ $shop_id ];
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
                                    
                                    if ( ! $this->PS->is_child_product( $product_id ) )
                                        {
                                            $args   =   array  (
                                                                    'maintain_child'    =>  $product_maintain_child,
                                                                    'maintain_categories'    =>  $product_maintain_categories,
                                                                    'maintain_stock'    =>  $product_maintain_stock,
                                                                    );
                                            
                                            $interface_messages     =   $this->PS->synchronize_to( $product_id, array( $product_shop_id ), $args );
                                        }
                                    
                                    if ( $switched_blog )
                                        restore_current_blog();    
                                    
                                }
                            
                            
                        }
                }
                
                
            /**
            * Unassign the synchronized products
            *     
            * @param mixed $postid
            * @param mixed $post
            */
            function before_delete_post( $postid, $post )
                {
                    
                    //check if main product      
                    if ( ! $this->PS->is_main_product( $postid ) )
                        return;
                    
                    global $blog_id;
                    
                    $main_product                   =   new WooGC_PS_main_product( $postid );
                    
                    foreach ( $main_product->get_children() as  $main_product_child_shop_id )
                        {
                            $found_product_ID   =   $this->PS->get_product_synchronized_at_shop( $postid, $blog_id, $main_product_child_shop_id );
                            if ( $found_product_ID > 0 )
                                {
                                    switch_to_blog( $main_product_child_shop_id );
                                    
                                    delete_post_meta( $found_product_ID, '_woogc_ps_is_child_of_pid' );
                                    delete_post_meta( $found_product_ID, '_woogc_ps_is_child_of_bid' );
                                    
                                    restore_current_blog();
                                }
                        }
                    
                }
                 
        }
            
?>