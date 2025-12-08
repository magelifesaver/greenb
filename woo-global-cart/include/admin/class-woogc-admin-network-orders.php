<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Network_Admin_Orders_Table_List extends WP_List_Table 
        {

            public $dashboard_url;

            private $is_trash;
            
            private $post_status;

            /**
             * Current level for output.
             *
             * @since 4.3.0
             * @access protected
             * @var int
             */
            protected $current_level = 0;

            /**
             * Constructor.
             *
             * @since 3.1.0
             * @access public
             *
             * @see WP_List_Table::__construct() for more information on default arguments.
             *
             * @global WP_Post_Type $post_type_object
             * @global wpdb         $wpdb
             *
             * @param array $args An associative array of arguments.
             */
            public function __construct( $args = array() ) 
                {
                    global $post_type_object, $wpdb;
                    
                    $args = wp_parse_args( $args, array(
                                                        'plural'        => '',
                                                        'singular'      => '',
                                                        'ajax'          => false,
                                                        'screen'        => null,
                                                    ) );
                    
                    if(is_network_admin())
                        $this->dashboard_url    =   network_admin_url( 'admin.php?page=woogc-woocommerce-orders' );
                        else
                        $this->dashboard_url    =   admin_url( 'admin.php?page=woogc-woocommerce-orders' );
                    
                    $post_type          = $post_type_object->name;
                    
                    $screen             =   get_current_screen();
                    $screen->post_type  =   $post_type;
                    
                    $this->screen       = convert_to_screen( $screen );
                    
                    
                    
                    if ( !$args['plural'] )
                        $args['plural'] = $this->screen->base;

                    $args['plural'] = sanitize_key( $args['plural'] );
                    $args['singular'] = sanitize_key( $args['singular'] );
                    
                    $this->_args = $args;
                    
                    $this->post_status  =   isset($_GET['post_status']) ? $_GET['post_status']  :   '';
                    
                    if ( $this->post_status     ==  'trash')
                        $this->is_trash =   TRUE;
                      
                }
                
            
            public static function output()
                {
                    
                    global $post_type, $post_type_object, $typenow;
                    
                    $post_type  =   'shop_order';
                         
                    $post_type_object = get_post_type_object( $post_type );
                    
                    $wp_list_table = new self();
                    $pagenum = $wp_list_table->get_pagenum();    
                    
                    $doaction = $wp_list_table->current_action();
                    
                    if ( $doaction ) 
                        {
                            check_admin_referer('bulk-' . $wp_list_table->_args['plural']);

                            $sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'mark_processing', 'mark_on-hold', 'mark_completed', 'locked', 'ids'), wp_get_referer() );
                            if ( ! $sendback )
                                $sendback = $wp_list_table->dashboard_url;
                                
                            $sendback = add_query_arg( 'paged', $pagenum, $sendback );
                  
                            $query_posts    =   (array)$_GET['post'];
                            
                            switch ( $doaction ) 
                                {
                                    
                                    case 'trash'    :
                                                        $trashed = $locked = 0;
                                                        
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                if ( !current_user_can( 'delete_post', $post_id) )
                                                                    wp_die( __('Sorry, you are not allowed to move this item to the Trash.') );

                                                                if ( wp_check_post_lock( $post_id ) ) 
                                                                    {
                                                                        $locked++;
                                                                        restore_current_blog();
                                                                        continue;
                                                                    }

                                                                $order  =   new WC_Order ( $post_id );
                                                                if ( ! $order->update_status( 'trash' ) )
                                                                    wp_die( __('Error in moving to Trash.') );

                                                                $trashed++;
                                                                                                                      
                                                                restore_current_blog();
                                                                
                                                            }
                                                        
                                                        $sendback = add_query_arg( array('trashed' => $trashed, 'ids' => join(',', $query_posts), 'locked' => $locked ), $sendback );
                                                                                    
                                                        break;
                                
                                    case 'untrash'  :
                                                        $untrashed = 0;
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                if ( !current_user_can( 'delete_post', $post_id) )
                                                                    wp_die( __('Sorry, you are not allowed to restore this item from the Trash.') );

                                                                $order  =   new WC_Order ( $post_id );
                                                                if ( ! $order->untrash() )
                                                                    wp_die( __('Error in restoring from Trash.') );

                                                                $untrashed++;
                                                                
                                                                //restore original blog
                                                                restore_current_blog();
                                                                
                                                            }
                                                        
                                                        $sendback = add_query_arg('untrashed', $untrashed, $sendback);
                                                        
                                                        break;
                                                        
                                                        
                                    case 'delete'       :
                                                        
                                                        $deleted = 0;
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                $post_del = get_post($post_id);

                                                                if ( !current_user_can( 'delete_post', $post_id ) )
                                                                    wp_die( __('Sorry, you are not allowed to delete this item.') );

                                                                $order  =   new WC_Order ( $post_id );
                                                                if ( ! $order->delete( TRUE ) )
                                                                    wp_die( __('Error in deleting.') );
                                                                                                                                    
                                                                $deleted++;
                                                                
                                                                //restore original blog
                                                                restore_current_blog();
                                                            }
                                                            
                                                                                                                
                                                        $sendback = add_query_arg('deleted', $deleted, $sendback);                                                        
                                                        
                                                        break;
                                                        
                                    case 'mark_processing':
                                                        
                                                        $mark_processing = 0;
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                $order = new WC_Order($post_id);
                                                                $order->update_status('processing');
                                                                
                                                                $mark_processing++;
                                                                
                                                                //restore original blog
                                                                restore_current_blog();
                                                            }
                                                        
                                                        $sendback = add_query_arg('mark_processing', $mark_processing, $sendback);
                                    
                                                        break;
                                
                                    case 'mark_on-hold':

                                                        $mark_on_hold = 0;
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                $order = new WC_Order($post_id);
                                                                $order->update_status('on-hold');
                                                                
                                                                $mark_on_hold++;
                                                                
                                                                //restore original blog
                                                                restore_current_blog();
                                                                
                                                            }
                                                                                                                
                                                        $sendback = add_query_arg('mark_on-hold', $mark_on_hold, $sendback);
                                    
                                                        break;
                                                        
                                    case 'mark_completed':

                                                        $mark_completed = 0;
                                                        foreach($query_posts as  $post_data)
                                                            {
                                                                list($blog_id, $post_id)    =   explode("_", $post_data);
                                                                
                                                                switch_to_blog( $blog_id );
                                                                
                                                                $order = new WC_Order($post_id);
                                                                $order->update_status('completed');
                                                                
                                                                $mark_completed++;
                                                                
                                                                //restore original blog
                                                                restore_current_blog();
                                                                
                                                            }
                                                            
                                                        $sendback = add_query_arg('mark_completed', $mark_completed, $sendback);
                                    
                                                        break;
                             
                            }

                            $sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view'), $sendback );

                            wp_redirect($sendback);
                            exit();
                            
                        } 
                        elseif ( ! empty($_REQUEST['_wp_http_referer']) ) 
                        {
                             wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI']) ) );
                             exit;
                        }
                    
                    $wp_list_table->prepare_items();
                    
                    get_current_screen()->set_screen_reader_content( array(
                        'heading_views'      => $post_type_object->labels->filter_items_list,
                        'heading_pagination' => $post_type_object->labels->items_list_navigation,
                        'heading_list'       => $post_type_object->labels->items_list,
                    ) );

                    $bulk_counts = array(
                        'updated'   => isset( $_REQUEST['updated'] )   ? absint( $_REQUEST['updated'] )   : 0,
                        'locked'    => isset( $_REQUEST['locked'] )    ? absint( $_REQUEST['locked'] )    : 0,
                        'deleted'   => isset( $_REQUEST['deleted'] )   ? absint( $_REQUEST['deleted'] )   : 0,
                        'trashed'   => isset( $_REQUEST['trashed'] )   ? absint( $_REQUEST['trashed'] )   : 0,
                        'untrashed' => isset( $_REQUEST['untrashed'] ) ? absint( $_REQUEST['untrashed'] ) : 0,
                    );
                    
                    $bulk_messages = array();
                    $bulk_messages['shop_order'] = array(
                        'updated'   => _n( '%s post updated.', '%s posts updated.', $bulk_counts['updated'] ),
                        'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 post not updated, somebody is editing it.' ) :
                                           _n( '%s post not updated, somebody is editing it.', '%s posts not updated, somebody is editing them.', $bulk_counts['locked'] ),
                        'deleted'   => _n( '%s post permanently deleted.', '%s posts permanently deleted.', $bulk_counts['deleted'] ),
                        'trashed'   => _n( '%s post moved to the Trash.', '%s posts moved to the Trash.', $bulk_counts['trashed'] ),
                        'untrashed' => _n( '%s post restored from the Trash.', '%s posts restored from the Trash.', $bulk_counts['untrashed'] ),
                    );

                    $bulk_counts = array_filter( $bulk_counts );
                    
                    ?>
                        <div class="wrap">
                            <h1 class="wp-heading-inline"><?php echo esc_html( $post_type_object->labels->name ); ?></h1>

                            <?php
                
                                if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) 
                                    {
                                        /* translators: %s: search keywords */
                                        printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', get_search_query() );
                                    }
                            ?>

                            <hr class="wp-header-end">

                            <?php
                            // If we have a bulk message to issue:
                            $messages = array();
                            foreach ( $bulk_counts as $message => $count ) 
                                {
                                    if ( isset( $bulk_messages[ $post_type ][ $message ] ) )
                                        $messages[] = sprintf( $bulk_messages[ $post_type ][ $message ], number_format_i18n( $count ) );
                                    elseif ( isset( $bulk_messages['post'][ $message ] ) )
                                        $messages[] = sprintf( $bulk_messages['post'][ $message ], number_format_i18n( $count ) );
                       
                                }

                            if ( $messages )
                                echo '<div id="message" class="updated notice is-dismissible"><p>' . join( ' ', $messages ) . '</p></div>';
                            unset( $messages );

                            $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed', 'mark_processing', 'mark_on-hold', 'mark_completed' ), $_SERVER['REQUEST_URI'] );
                            
                            ?>

                            <?php $wp_list_table->views(); ?>

                            <form id="posts-filter" method="get">

                                <?php $wp_list_table->search_box( $post_type_object->labels->search_items, 'post' ); ?>
                                
                                <input type="hidden" name="page" class="page" value="woogc-woocommerce-orders" />
                                <input type="hidden" name="post_status" class="post_status_page" value="<?php echo !empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : 'all'; ?>" />
                                                         
                                <?php $wp_list_table->display(); ?>

                            </form>

                            <?php
                            if ( $wp_list_table->has_items() )
                                $wp_list_table->inline_edit();
                            ?>

                            <div id="ajax-response"></div>
                            <br class="clear" />
                        </div>
                    <?php   
                
                }    
            
     
            /**
            * Prepare order items
            * 
            */
            public function prepare_items() 
                {
                    global $avail_post_stati, $wp_query, $per_page, $mode;
                    
                    // is going to call wp()
                    $avail_post_stati   = wp_edit_posts_query();
                    
                    $post_type          = $this->screen->post_type;
                    $per_page           = $this->get_items_per_page( 'woogc_orders_per_page' );
                    $filter_post_status =   ( $this->post_status == 'all' ) ?   ''  :   $this->post_status;
                    $filter_month       =   isset( $_GET['m'] ) ? (int) $_GET['m'] : '';
                    $filter_search      =   isset( $_GET['s'] ) ? $_GET['s'] : '';
                    $blog_id            =   isset( $_GET['blog_id'] ) ? (int) $_GET['blog_id'] : '';
                    $_customer_user     =   isset( $_GET['_customer_user'] ) ? (int) $_GET['_customer_user'] : '';
                    
                    $orderby            =   isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';
                    $order              =   isset( $_GET['order'] ) ? $_GET['order'] : '';

                    $args   =   array(
                                        'per_page'          =>  $per_page,
                                        'paged'             =>  $this->get_pagenum(),
                                        'post_status'       =>  $filter_post_status,
                                        'filter_month'      =>  $filter_month,
                                        '_customer_user'    =>  $_customer_user,
                                        'filter_search'     =>  $filter_search,
                                        'blog_id'           =>  $blog_id ,
                                        
                                        'orderby'           =>  $orderby,
                                        'order'             =>  $order
                                        );
                    
                    $order_posts_data   =   $this->get_all_sites_orders( $args );

                    $this->set_pagination_args( array(
                                                        'total_items'   => $order_posts_data['total_records'],
                                                        'per_page'      => $per_page
                                                    ) );
                                                    
                    $this->items                   =   $order_posts_data['results'];
                       
                }

            /**
             *
             * @return bool
             */
            public function has_items() 
                {
                    return count( $this->items );
                }

            /**
             * @access public
             */
            public function no_items() 
                {
                    if ( isset( $_REQUEST['post_status'] ) && 'trash' === $_REQUEST['post_status'] )
                        echo get_post_type_object( $this->screen->post_type )->labels->not_found_in_trash;
                    else
                        echo get_post_type_object( $this->screen->post_type )->labels->not_found;
                }

            /**
             * Determine if the current view is the "All" view.
             *
             * @since 4.2.0
             *
             * @return bool Whether the current view is the "All" view.
             */
            protected function is_base_request() 
                {
                    $vars = $_GET;
                    unset( $vars['paged'] );

                    if ( empty( $vars ) ) 
                        {
                            return true;
                        } 
                    elseif ( 1 >= count( $vars ) || (2 >= count( $vars ) && ! empty( $vars['post_type'] ) ) )
                        {
                            if (! empty($vars['post_type'] ))
                                return $this->screen->post_type === $vars['post_type'];
                                else
                                return TRUE;
                        }

                    return 2 >= count( $vars ) && ! empty( $vars['mode'] );
                }

            /**
             * Helper to create links to edit.php with params.
             *
             * @since 4.4.0
             * @access protected
             *
             * @param array  $args  URL parameters for the link.
             * @param string $label Link text.
             * @param string $class Optional. Class attribute. Default empty string.
             * @return string The formatted link string.
             */
            protected function get_edit_link( $args, $label, $class = '' ) 
                {
                    
                    $url = add_query_arg( $args, $this->dashboard_url );

                    $class_html = '';
                    if ( ! empty( $class ) ) {
                         $class_html = sprintf(
                            ' class="%s"',
                            esc_attr( $class )
                        );
                    }

                    return sprintf(
                        '<a href="%s"%s>%s</a>',
                        esc_url( $url ),
                        $class_html,
                        $label
                    );
                }

            /**
             *
             * @global array $locked_post_status This seems to be deprecated.
             * @global array $avail_post_stati
             * @return array
             */
            protected function get_views() 
                {
                    global $locked_post_status, $avail_post_stati;

                    $post_type = $this->screen->post_type;

                    if ( !empty($locked_post_status) )
                        return array();

                    $status_links   =   array();
                    $num_posts      =   new stdClass();
                    
                    $orders_statuses    =   $this->get_all_sites_orders_statuses();
                    foreach($avail_post_stati as $status)
                        {
                            $num_posts->$status =   isset($orders_statuses[$status])    ?   $orders_statuses[$status]   :   0;   
                        }
                    
                    $total_posts = array_sum( (array) $num_posts );
                    $class = '';

                    $current_user_id = get_current_user_id();
                    $all_args = array(  );
                    $mine = '';

                    // Subtract post types that are not included in the admin all list.
                    foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) 
                        {
                            $total_posts -= $num_posts->$state;
                        }
      
                    if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_posts'] ) ) ) 
                        {
                            $class = 'current';
                        }

                    $all_inner_html = sprintf(
                                                    _nx(
                                                        'All <span class="count">(%s)</span>',
                                                        'All <span class="count">(%s)</span>',
                                                        $total_posts,
                                                        'posts'
                                                    ),
                                                    number_format_i18n( $total_posts )
                                                );

                    $status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );
                    if ( $mine ) 
                        {
                            $status_links['mine'] = $mine;
                        }

                    foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status ) 
                        {
                            $class = '';

                            $status_name = $status->name;

                            if ( ! in_array( $status_name, $avail_post_stati ) || empty( $num_posts->$status_name ) ) 
                                {
                                    continue;
                                }

                            if ( isset($_REQUEST['post_status']) && $status_name === $_REQUEST['post_status'] ) 
                                {
                                    $class = 'current';
                                }

                            $status_args = array(
                                'post_status' => $status_name,
                               // 'post_type' => $post_type,
                            );

                            $status_label = sprintf(
                                translate_nooped_plural( $status->label_count, $num_posts->$status_name ),
                                number_format_i18n( $num_posts->$status_name )
                            );

                            $status_links[ $status_name ] = $this->get_edit_link( $status_args, $status_label, $class );
                        }
               
                    return $status_links;
                }

               
            /**
             *
             * @return array
             */
            protected function get_bulk_actions() 
                {
                    $actions = array();
                    $post_type_obj = get_post_type_object( $this->screen->post_type );

                    if ( current_user_can( $post_type_obj->cap->edit_posts ) ) 
                        {
                            if ( $this->is_trash ) 
                                {
                                    $actions['untrash'] = __( 'Restore' );
                                    $actions['delete'] = __( 'Delete Permanently' );
                                } 
                            else 
                                {
                                    $actions['trash'] = __( 'Move to Trash' );
                                }
                
                            $actions['mark_processing'] = __( 'Mark processing' );
                            $actions['mark_on-hold']    = __( 'Mark on-hold' );
                            $actions['mark_completed']  = __( 'Mark complete' );
                
                        }
             
                    return $actions;
                }
      
      
            /**
            * Blog filter
            * 
            */
            function blog_filter()
                {
                    global $WooGC;
                    
                    $blog_id = isset( $_GET['blog_id'] ) ? (int) $_GET['blog_id'] : '';
                    
                    ?>
                            <label for="filter-by-blog" class="screen-reader-text"><?php _e( 'Filter by shop' ); ?></label>
                            <select name="blog_id" id="filter-by-blog">
                                <option<?php selected( $blog_id, '' ); ?> value=""><?php _e( 'All Shops' ); ?></option>
                    <?php
                            
                            $network_sites  =   get_sites(array('limit'  =>  999));
                            foreach($network_sites as $network_site)
                                {
                                    
                                    switch_to_blog( $network_site->blog_id );                                                
                                                        
                                    if (! $WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                                        {
                                            restore_current_blog();
                                            continue;   
                                        }

                                    restore_current_blog();
                                    
                                    $blog_details   =   get_blog_details($network_site->blog_id);
                     
                                    printf( "<option %s value='%s'>%s</option>\n",
                                        selected( $blog_id, $network_site->blog_id, false ),
                                        esc_attr( $network_site->blog_id ),
                                        $network_site->blogname
                                    );
                                }
                    ?>
                            </select>
                    <?php    
                    
                    
                }
                
                
            
            /**
            * Display a monthly dropdown for filtering items
            *
            *
            * @global wpdb      $wpdb
            * @global WP_Locale $wp_locale
            *
            * @param string $post_type
            */
            protected function months_dropdown( $post_type ) 
                {
                    global $wpdb, $wp_locale, $WooGC;


                    $extra_checks = "AND post_status != 'auto-draft'";
                    if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) {
                        $extra_checks .= " AND post_status != 'trash'";
                    } elseif ( isset( $_GET['post_status'] ) ) {
                        $extra_checks = $wpdb->prepare( ' AND post_status = %s', $_GET['post_status'] );
                    }

                    $network_sites  =   $WooGC->functions->get_gc_sites( TRUE, 'global_orders' );
                    
                    $main_query =   "SELECT * FROM ( ";
                    $query  =   array();
                    foreach ( $network_sites as  $site )
                        {
                            switch_to_blog( $site->blog_id );
                            
                            $query[]    =   $wpdb->prepare("SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
                                                FROM $wpdb->posts
                                                WHERE post_type = %s
                                                $extra_checks ", $post_type);
                            
                            restore_current_blog();                            
                        }
                    
                    
                    
                    $main_query .=  implode( " UNION ALL ", $query ) . ") as q
                                                GROUP BY YEAR, MONTH
                                                ORDER BY YEAR DESC, MONTH DESC";
                    
                    $months = $wpdb->get_results( $main_query );

                    $months = apply_filters( 'months_dropdown_results', $months, $post_type );

                    $month_count = count( $months );

                    if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
                        return;

                    $m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
                    ?>
                            <label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
                            <select name="m" id="filter-by-date">
                                <option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
                    <?php
                            foreach ( $months as $arc_row ) {
                                if ( 0 == $arc_row->year )
                                    continue;

                                $month = zeroise( $arc_row->month, 2 );
                                $year = $arc_row->year;

                                printf( "<option %s value='%s'>%s</option>\n",
                                    selected( $m, $year . $month, false ),
                                    esc_attr( $arc_row->year . $month ),
                                    /* translators: 1: month name, 2: 4-digit year */
                                    sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
                                );
                            }
                    ?>
                            </select>
                    <?php
                }
                

            /**
             * @param string $which
             */
            protected function extra_tablenav( $which ) 
                { 
                    ?>
                            <div class="alignleft actions">
                    <?php
                            if ( 'top' === $which && !is_singular() ) {
                                ob_start();

                                $this->blog_filter( );
                                
                                $this->months_dropdown( $this->screen->post_type );

                                /**
                                 * Fires before the Filter button on the Posts and Pages list tables.
                                 *
                                 * The Filter button allows sorting by date and/or category on the
                                 * Posts list table, and sorting by date on the Pages list table.
                                 *
                                 * @since 2.1.0
                                 * @since 4.4.0 The `$post_type` parameter was added.
                                 * @since 4.6.0 The `$which` parameter was added.
                                 *
                                 * @param string $post_type The post type slug.
                                 * @param string $which     The location of the extra table nav markup:
                                 *                          'top' or 'bottom'.
                                 */
                                do_action( 'restrict_manage_posts', $this->screen->post_type, $which );

                                $output = ob_get_clean();

                                if ( ! empty( $output ) ) 
                                    {
                                        echo $output;
                                        submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
                                    }
                            }
               
                    ?>
                            </div>
                    <?php
                    /**
                     * Fires immediately following the closing "actions" div in the tablenav for the posts
                     * list table.
                     *
                     * @since 4.4.0
                     *
                     * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
                     */
                    do_action( 'manage_posts_extra_tablenav', $which );
                }

            /**
             *
             * @return string
             */
            public function current_action() {
                if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) )
                    return 'delete_all';

                return parent::current_action();
            }

            /**
             *
             * @return array
             */
            protected function get_table_classes() {
                return array( 'widefat', 'fixed', 'striped', is_post_type_hierarchical( $this->screen->post_type ) ? 'pages' : 'posts' );
            }

            
            function get_column_info()
                {
                    
                    // $_column_headers is already set / cached
                    if ( isset( $this->_column_headers ) && is_array( $this->_column_headers ) ) {
                        // Back-compat for list tables that have been manually setting $_column_headers for horse reasons.
                        // In 4.3, we added a fourth argument for primary column.
                        $column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
                        foreach ( $this->_column_headers as $key => $value ) {
                            $column_headers[ $key ] = $value;
                        }

                        return $column_headers;
                    }

                    $columns = $this->get_columns();
                    $hidden = get_hidden_columns( $this->screen );

                    $sortable_columns = $this->get_sortable_columns();
                    /**
                     * Filters the list table sortable columns for a specific screen.
                     *
                     * The dynamic portion of the hook name, `$this->screen->id`, refers
                     * to the ID of the current screen, usually a string.
                     *
                     * @since 3.5.0
                     *
                     * @param array $sortable_columns An array of sortable columns.
                     */
                    $_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

                    $sortable = array();
                    foreach ( $_sortable as $id => $data ) {
                        if ( empty( $data ) )
                            continue;

                        $data = (array) $data;
                        if ( !isset( $data[1] ) )
                            $data[1] = false;

                        $sortable[$id] = $data;
                    }

                    $primary = $this->get_primary_column_name();
                    $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

                    return $this->_column_headers;   
                    
                    
                }
                
                
            function get_primary_column_name()
                {
                    
                    $columns = $this->get_columns();
                    $default = $this->get_default_primary_column_name();

                    // If the primary column doesn't exist fall back to the
                    // first non-checkbox column.
                    if ( ! isset( $columns[ $default ] ) ) {
                        $default = WP_List_Table::get_default_primary_column_name();
                    }

                    /**
                     * Filters the name of the primary column for the current list table.
                     *
                     * @since 4.3.0
                     *
                     * @param string $default Column name default for the specific list table, e.g. 'name'.
                     * @param string $context Screen ID for specific list table, e.g. 'plugins'.
                     */
                    $column  = apply_filters( 'list_table_primary_column', $default, $this->screen->id );

                    if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
                        $column = $default;
                    }

                    return $column;   
                    
                    
                }
                
                
            
            /**
             *
             * @return array
             */
            public function get_columns() 
                {
                    $columns                     = array();
                    $columns['cb']               = '<input type="checkbox" />';
                    $columns['shop_title']       = __( 'Shop', 'woocommerce' );
                    $columns['order_status']     = __( 'Status', 'woocommerce' );
                    $columns['order_title']      = __( 'Order', 'woocommerce' );
                    $columns['billing_address']  = __( 'Billing', 'woocommerce' );
                    $columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
                    $columns['customer_message'] = '<span class="notes_head tips" data-tip="' . esc_attr__( 'Customer message', 'woocommerce' ) . '">' . esc_attr__( 'Customer message', 'woocommerce' ) . '</span>';
                    $columns['order_notes']      = '<span class="order-notes_head tips" data-tip="' . esc_attr__( 'Order notes', 'woocommerce' ) . '">' . esc_attr__( 'Order notes', 'woocommerce' ) . '</span>';
                    $columns['order_date']       = __( 'Date', 'woocommerce' );
                    $columns['order_total']      = __( 'Total', 'woocommerce' );
                    $columns['order_actions']    = __( 'Actions', 'woocommerce' );

                    $columns    =   apply_filters( 'wogc/admin/manage_shop_order_columns', $columns );
                    
                    return $columns;
                }

            /**
             *
             * @return array
             */
            protected function get_sortable_columns() {
                return array(
                    'order_date'    => 'order_date',
                    'shop_title'    => 'shop_title'
                );
            }

            /**
             * @global WP_Query $wp_query
             * @global int $per_page
             * @param array $posts
             * @param int $level
             */
            public function display_rows( $posts = array(), $level = 0 ) 
                {
                    global $per_page;

                    if ( empty( $posts ) )
                        $posts = $this->items;
                    
                    $this->_display_rows( $posts, $level );
                  
                }

            /**
             * @param array $posts
             * @param int $level
             */
            private function _display_rows( $posts, $level = 0 ) 
                {
               
                    foreach ( $posts as $post )
                        {
                            switch_to_blog( $post->blog_id );
                            
                            $this->single_row( $post, $level );
                            
                            restore_current_blog();
                        }
                }
          

            /**
             * Handles the checkbox column output.
             *
             * @since 4.3.0
             * @access public
             *
             * @param WP_Post $post The current WP_Post object.
             */
            public function column_cb( $post ) 
                {
                    
                    global $blog_id;
                    
                    if ( current_user_can( 'edit_post', $post->ID ) ): ?>
                        <label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>"><?php
                            printf( __( 'Select %s' ), _draft_or_post_title() );
                        ?></label>
                        <input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="post[]" value="<?php echo $blog_id; ?>_<?php the_ID(); ?>" />
                        <div class="locked-indicator">
                            <span class="locked-indicator-icon" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php
                            printf(
                                /* translators: %s: post title */
                                __( '&#8220;%s&#8221; is locked' ),
                                _draft_or_post_title()
                            );
                            ?></span>
                        </div>
                    <?php endif;
                }
            
            /**
             * Handles the post date column output.
             *
             * @since 4.3.0
             * @access public
             *
             * @global string $mode
             *
             * @param WP_Post $post The current WP_Post object.
             */
            public function column_date( $post ) {
                global $mode;

                if ( '0000-00-00 00:00:00' === $post->post_date ) {
                    $t_time = $h_time = __( 'Unpublished' );
                    $time_diff = 0;
                } else {
                    $t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
                    $m_time = $post->post_date;
                    $time = get_post_time( 'G', true, $post );

                    $time_diff = time() - $time;

                    if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
                        $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                    } else {
                        $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
                    }
                }

                if ( 'publish' === $post->post_status ) {
                    _e( 'Published' );
                } elseif ( 'future' === $post->post_status ) {
                    if ( $time_diff > 0 ) {
                        echo '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
                    } else {
                        _e( 'Scheduled' );
                    }
                } else {
                    _e( 'Last Modified' );
                }
                echo '<br />';
                if ( 'excerpt' === $mode ) {
                    /**
                     * Filters the published time of the post.
                     *
                     * If `$mode` equals 'excerpt', the published time and date are both displayed.
                     * If `$mode` equals 'list' (default), the publish date is displayed, with the
                     * time and date together available as an abbreviation definition.
                     *
                     * @since 2.5.1
                     *
                     * @param string  $t_time      The published time.
                     * @param WP_Post $post        Post object.
                     * @param string  $column_name The column name.
                     * @param string  $mode        The list display mode ('excerpt' or 'list').
                     */
                    echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
                } else {

                    /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
                    echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
                }
            }

            /**
             * Handles the default column output.
             *
             * @since 4.3.0
             * @access public
             *
             * @param WP_Post $post        The current WP_Post object.
             * @param string  $column_name The current column name.
             */
            public function column_default( $post, $column_name ) 
                {
                    global $post, $the_order, $blog_id;

                    if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) 
                        {
                            $the_order = new WC_Order( $post->ID );
                        }

                    switch ( $column_name ) 
                        {
                            case 'shop_title' :
                                $blog_details   =   get_blog_details( $blog_id );
                                printf( '<b>%s</b>', esc_html( $blog_details->blogname ) );
                            break;
                            case 'order_status' :
                                printf( '<mark class="order-status status-%s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( $the_order->get_status() ) ), esc_attr( wc_get_order_status_name( $the_order->get_status() ) ), esc_html( wc_get_order_status_name( $the_order->get_status() ) ) );
                            break;
                            case 'order_date' :
                                $order_date = $the_order->get_date_created();
                                if  ( $order_date )
                                    {
                                        printf( '<time datetime="%s">%s</time>', esc_attr( $order_date->date( 'c' ) ), esc_html( $order_date->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'Y-m-d', 'woocommerce' ) ) ) ) );
                                    }
                            break;
                            case 'customer_message' :
                                if ( $the_order->get_customer_note() ) {
                                    echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $the_order->get_customer_note() ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
                                } else {
                                    echo '<span class="na">&ndash;</span>';
                                }

                            break;
                            case 'billing_address' :

                                if ( $address = $the_order->get_formatted_billing_address() ) {
                                    echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
                                } else {
                                    echo '&ndash;';
                                }

                                if ( $the_order->get_billing_phone() ) {
                                    echo '<small class="meta">' . __( 'Phone:', 'woocommerce' ) . ' ' . esc_html( $the_order->get_billing_phone() ) . '</small>';
                                }

                            break;
                            case 'shipping_address' :

                                if ( $address = $the_order->get_formatted_shipping_address() ) {
                                    echo '<a target="_blank" href="' . esc_url( $the_order->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
                                } else {
                                    echo '&ndash;';
                                }

                                if ( $the_order->get_shipping_method() ) {
                                    echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
                                }

                            break;
                            case 'order_notes' :

                                if ( $post->comment_count ) 
                                    {
                                        
                                        // check the status of the post
                                        $status = ( 'trash' !== $post->post_status ) ? '' : 'post-trashed';

                                        $latest_notes = get_comments( array(
                                            'post_id'   => $post->ID,
                                            'number'    => 1,
                                            'status'    => $status,
                                            'post_type' => array('shop_order','shop_order_refund')
                                        ) );

                                        $latest_note = current( $latest_notes );

                                        if ( isset( $latest_note->comment_content ) && 1 == $post->comment_count ) {
                                            echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
                                        } elseif ( isset( $latest_note->comment_content ) ) {
                                            /* translators: %d: notes count */
                                            echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content . '<br/><small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), $post->comment_count - 1 ) . '</small>' ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
                                        } else {
                                            /* translators: %d: notes count */
                                            echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $post->comment_count, 'woocommerce' ), $post->comment_count ) ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
                                        }
                                    } 
                                    else 
                                    {
                                        echo '<span class="na">&ndash;</span>';
                                    }

                            break;
                            case 'order_total' :
                                echo $the_order->get_formatted_order_total();

                                if ( $the_order->get_payment_method_title() ) 
                                    {
                                        echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_payment_method_title() ) . '</small>';
                                    }
                            break;
                            case 'order_title' :
                                                         
                                $buyer = '';

                                if ( $the_order->get_billing_first_name() || $the_order->get_billing_last_name() ) {
                                    /* translators: 1: first name 2: last name */
                                    $buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $the_order->get_billing_first_name(), $the_order->get_billing_last_name() ) );
                                } elseif ( $the_order->get_billing_company() ) {
                                    $buyer = trim( $the_order->get_billing_company() );
                                } elseif ( $the_order->get_customer_id() ) {
                                    $user  = get_user_by( 'id', $the_order->get_customer_id() );
                                    $buyer = ucwords( $user->display_name );
                                }

                                /**
                                 * Filter buyer name in list table orders.
                                 *
                                 * @since 3.7.0
                                 * @param string   $buyer Buyer name.
                                 * @param WC_Order $order Order data.
                                 */
                                $buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $the_order );
                                
                                if ( $the_order->get_status() === 'trash' ) {
                                    echo '<strong>#' . esc_attr( $the_order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
                                } else {
                                    echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $the_order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $the_order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
                                }

                            break;
                            case 'order_actions' :

                                ?><p class="column-wc_actions">
                                    <?php
                                        do_action( 'woocommerce_admin_order_actions_start', $the_order );

                                        $actions = array();
                                        
                                        $screen = get_current_screen();

                                        if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) 
                                            {
                                                $actions['processing'] = array(
                                                    'url'       => wp_nonce_url( $this->dashboard_url . '&action=mark_processing&post=' . $blog_id . '_' . $post->ID , 'bulk-' . $screen->id  ),
                                                    'name'      => __( 'Processing', 'woocommerce' ),
                                                    'action'    => "processing",
                                                );
                                            }

                                        if ( $the_order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) 
                                            {
                                                $actions['complete'] = array(
                                                    'url'       => wp_nonce_url( $this->dashboard_url . '&action=mark_completed&post=' . $blog_id . '_' . $post->ID , 'bulk-' . $screen->id  ),
                                                    'name'      => __( 'Complete', 'woocommerce' ),
                                                    'action'    => "complete",
                                                );
                                            }

                                        if ( ! $the_order->has_status( array( 'trash' ) ) ) 
                                            {
                                                $actions['view'] = array(
                                                    'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
                                                    'name'      => __( 'View', 'woocommerce' ),
                                                    'action'    => "view",
                                                );
                                            }

                                        $actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );

                                        foreach ( $actions as $action ) 
                                            {
                                                printf( '<a class="button tips %s" href="%s" title="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
                                            }

                                        do_action( 'woocommerce_admin_order_actions_end', $the_order );
                                    ?>
                                </p><?php

                            break;
                        }
                
                    do_action( 'wogc/admin/manage_shop_order_column_data', $post, $column_name );
                    
                }

            /**
             * @global WP_Post $post
             *
             * @param int|WP_Post $post
             * @param int         $level
             */
            public function single_row( $post, $level = 0 ) 
                {
                    $global_post = get_post();

                    $post = get_post( $post->ID );
                    $this->current_level = $level;

                    $GLOBALS['post'] = $post;
                    setup_postdata( $post );

                    $classes = 'iedit author-' . ( get_current_user_id() == $post->post_author ? 'self' : 'other' );

                    $lock_holder = wp_check_post_lock( $post->ID );
                    if ( $lock_holder ) {
                        $classes .= ' wp-locked';
                    }

                    if ( $post->post_parent ) {
                        $count = count( get_post_ancestors( $post->ID ) );
                        $classes .= ' level-'. $count;
                    } else {
                        $classes .= ' level-0';
                    }
                    ?>
                        <tr id="post-<?php echo $post->ID; ?>" class="<?php echo implode( ' ', get_post_class( $classes, $post->ID ) ); ?>">
                            <?php $this->single_row_columns( $post ); ?>
                        </tr>
                    <?php
                    $GLOBALS['post'] = $global_post;
                }

            /**
             * Gets the name of the default primary column.
             *
             * @since 4.3.0
             * @access protected
             *
             * @return string Name of the default primary column, in this case, 'title'.
             */
            protected function get_default_primary_column_name() 
                {
                    return 'order_title';
                }

            /**
             * Generates and displays row action links.
             *
             * @since 4.3.0
             * @access protected
             *
             * @param object $post        Post being acted upon.
             * @param string $column_name Current column name.
             * @param string $primary     Primary column name.
             * @return string Row actions output for posts.
             */
            protected function handle_row_actions( $post, $column_name, $primary ) 
                {
                    if ( $primary !== $column_name ) {
                        return '';
                    }

                    $post_type_object = get_post_type_object( $post->post_type );
                    $can_edit_post = current_user_can( 'edit_post', $post->ID );
                    $actions = array();
                    $title = _draft_or_post_title();

                    if ( $can_edit_post && 'trash' != $post->post_status ) {
                        $actions['edit'] = sprintf(
                            '<a href="%s" aria-label="%s">%s</a>',
                            get_edit_post_link( $post->ID ),
                            /* translators: %s: post title */
                            esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
                            __( 'Edit' )
                        );
                
                    }

                    if ( current_user_can( 'delete_post', $post->ID ) ) {
                        if ( 'trash' === $post->post_status ) {
                            $actions['untrash'] = sprintf(
                                '<a href="%s" aria-label="%s">%s</a>',
                                wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
                                /* translators: %s: post title */
                                esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
                                __( 'Restore' )
                            );
                        } 
                    }

                    if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) 
                        {
                            if ( $can_edit_post ) 
                                {
                                    $preview_link = get_preview_post_link( $post );
                                    $actions['view'] = sprintf(
                                        '<a href="%s" rel="permalink" aria-label="%s">%s</a>',
                                        esc_url( $preview_link ),
                                        /* translators: %s: post title */
                                        esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ),
                                        __( 'Preview' )
                                    );
                                }
                        } 
                        elseif ( 'trash' != $post->post_status ) 
                            {
                                $actions['view'] = sprintf(
                                    '<a href="%s" rel="permalink" aria-label="%s">%s</a>',
                                    get_edit_post_link( $post->ID ),
                                    /* translators: %s: post title */
                                    esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ),
                                    __( 'View' )
                                );
                            }
             
                    return $this->row_actions( $actions );
                }
            
            /**
            * Return orders statuses across all shops
            * 
            */
            function get_all_sites_orders_statuses()
                {
                    global $wpdb, $WooGC;
                    
                    $mysql_query    =   '
                    
                        SELECT post_status, count FROM (
                        ';
                    
                    $network_sites  =   get_sites(array('limit'  =>  999));
                    foreach($network_sites as $network_site)
                        {
                            
                            switch_to_blog( $network_site->blog_id );                                                
                                                
                            if (! $WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                                {
                                    restore_current_blog();
                                    continue;   
                                }
                            
                            if ( $WooGC->functions->is_HPOS_active() )
                                $table_name =   $wpdb->prefix . 'wc_orders';
                                else
                                $table_name =   $wpdb->prefix . 'posts';
                                                        
                            if ( $WooGC->functions->is_HPOS_active() )
                                {                            
                                    if($network_site->blog_id > 1)
                                        $mysql_query    .=   '
                                                                UNION ALL
                                                                ';    
                                                                                            
                                    $mysql_query    .=          "(SELECT " . $WooGC->functions->get_collated_column_name( 'status', $table_name, 'post_status' ) .", COUNT(*) as count FROM ". $table_name ;
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query/SELECT', $mysql_query, $network_site->blog_id );
                                    
                                    $mysql_query    .=          " WHERE 1 = 1";
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query/WHERE', $mysql_query, $network_site->blog_id );
                                    
                                    $mysql_query    .=          " GROUP BY post_status
                                                                        )";
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query', $mysql_query, $network_site->blog_id );
                                }
                                else
                                {
                                    if( $network_site->blog_id > 1)
                                        $mysql_query    .=   '
                                                                UNION ALL
                                                                ';    
                                                                                            
                                    $mysql_query    .=          "(SELECT " . $WooGC->functions->get_collated_column_name('post_status', $table_name ) .", COUNT(*) as count FROM ". $table_name ;
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query/SELECT', $mysql_query, $network_site->blog_id );
                                    
                                    $mysql_query    .=          " WHERE post_type = 'shop_order'";
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query/WHERE', $mysql_query, $network_site->blog_id );
                                    
                                    $mysql_query    .=          " GROUP BY post_status
                                                                        )";
                                                                        
                                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders_statuses/mysql_query', $mysql_query, $network_site->blog_id );
                                }
                            
                            restore_current_blog();
                        }
                    
                    $mysql_query    .=   ') results 
                                    ' ;
                    
                    $results        =   $wpdb->get_results($mysql_query);
                    
                    $statuses   =   array();
                    foreach ($results   as  $result)
                        {
                            if(!isset($statuses[$result->post_status]))
                                $statuses[$result->post_status] =   0;
                                
                            $statuses[$result->post_status] +=  $result->count;
                        }
         
                    return $statuses;
                }
            
            /**
            * Return a list of orders from all sites
            * 
            * @param mixed $per_page
            * @param mixed $paged
            * @param mixed $post_status
            */
            function get_all_sites_orders( $args )
                {
                    global $wpdb, $WooGC;
                    
                    $defaults   = array (
                                            'per_page'          =>  10,
                                            'paged'             =>  1,
                                            'post_status'       =>  '',
                                            '_customer_user'    =>  '',
                                            'filter_search'     =>  '',
                                            'blog_id'           =>  '',
                                            
                                            'orderby'           =>  '',
                                            'order'             =>  ''
                                        );
                                        
                    // Parse incoming $args into an array and merge it with $defaults
                    $args   =   wp_parse_args( $args, $defaults );
                    
                    $mysql_query    =   '
                    
                        SELECT SQL_CALC_FOUND_ROWS * FROM (
                        ';
                    
                    $items = 0;
                    $network_sites  =   get_sites(array('limit'  =>  999));
                    foreach($network_sites as $network_site)
                        {
                            
                            if( ! empty ( $args['blog_id'] )    &&  $args['blog_id']    !=  $network_site->blog_id)
                                continue;
                             
                            switch_to_blog( $network_site->blog_id );                                                
                                                
                            if (! $WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                                {
                                    restore_current_blog();
                                    continue;   
                                }

                            $items++;
                                                        
                            $blog_details   =   get_blog_details( $network_site->blog_id );
                                       
                            if($items > 1)
                                $mysql_query    .=   '
                                                        UNION ALL
                                                        ';    
                            
                            if ( $WooGC->functions->is_HPOS_active() )
                                $mysql_query    .=          "(SELECT ID, date_created_gmt as post_date, '". $blog_details->blog_id ."' as blog_id, '". $blog_details->blogname ."' as blog_name FROM ". $wpdb->prefix . "wc_orders  ";
                                else
                                $mysql_query    .=          "(SELECT ID, post_date, '". $blog_details->blog_id ."' as blog_id, '". $blog_details->blogname ."' as blog_name FROM ". $wpdb->prefix . "posts  ";
                            
                            $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/SELECT', $mysql_query, $network_site->blog_id );
                            
                            /**
                            * JOIN
                            */
                            
                            //custoer
                            if( ! empty ( $args['_customer_user'] ) )
                                {
                                    $blog_table_prefix =    $blog_details->blog_id  >   1   ?   $blog_details->blog_id . "_"  :   '';            
                                    $mysql_query    .=          "LEFT JOIN ". $wpdb->postmeta . " AS pm ON ID = pm.post_id";           
                                }
                            
                            //search all meta for order
                            if( ! empty ( $args['filter_search'] ) )
                                {
                                    $blog_table_prefix =    $blog_details->blog_id  >   1   ?   $blog_details->blog_id . "_"  :   '';            
                                    $mysql_query    .=          "LEFT JOIN ". $wpdb->postmeta . " AS pm_s ON ID = pm_s.post_id";
                                }
                            
                            
                            $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/JOIN', $mysql_query, $network_site->blog_id );
                            
                            
                            /**
                            * WHERE
                            *                             
                            * @var mixed
                            */
                            if ( $WooGC->functions->is_HPOS_active() )
                                $mysql_query    .=          " WHERE 1 = 1";
                                else
                                $mysql_query    .=          " WHERE post_type = 'shop_order'";
                            
                            if(!empty($args['post_status']))
                                {
                                    if ( $WooGC->functions->is_HPOS_active() )
                                        $mysql_query    .=  " AND status   =   '". $args['post_status'] ."'";
                                        else
                                        $mysql_query    .=  " AND post_status   =   '". $args['post_status'] ."'";
                                }
                            
                            if($args['post_status'] !=  'trash')    
                                {
                                    if ( $WooGC->functions->is_HPOS_active() )
                                        $mysql_query    .=  " AND status NOT IN('trash')";
                                        else
                                        $mysql_query    .=  " AND post_status NOT IN('trash')";
                                }
                            
                            if( ! empty ( $args['filter_month'] ) )
                                {
                                    $filter_month       =   $args['filter_month'];
                                    $filter_year        =   substr($filter_month, 0, 4);
                                    $filter_month       =   substr($filter_month, 4, 2);
                                    
                                    $mysql_query        .=  " AND post_date LIKE '" . $filter_year . "-" . $filter_month ."%'";           
                                }
                                
                            //custoer
                            if( ! empty ( $args['_customer_user'] ) )
                                {
                                               
                                    $mysql_query        .=  " AND pm.meta_key = '_customer_user'    AND pm.meta_value   =   '".$args['_customer_user']."'";           
                                }
                            
                            //search    
                            if( ! empty ( $args['filter_search'] ) )
                                {
                                    if ( $WooGC->functions->is_HPOS_active() )           
                                        $mysql_query        .=  " AND ( id LIKE '%". esc_sql($args['filter_search']) ."%' OR pm_s.meta_value LIKE '%". esc_sql($args['filter_search']) ."%' ";           
                                        else
                                        $mysql_query        .=  " AND (post_title LIKE '%". esc_sql($args['filter_search']) ."%' OR post_content LIKE '%". esc_sql($args['filter_search']) ."%' OR pm_s.meta_value LIKE '%". esc_sql($args['filter_search']) ."%'  ";
                                        
                                    $mysql_query    .=   " OR ID LIKE '%". esc_sql($args['filter_search']) ."%' )";
                                }
                            
                            $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/WHERE', $mysql_query, $network_site->blog_id );
                            
                            
                            /**
                            * Group BY
                            * 
                            * @var mixed
                            */
                            if( ! empty ( $args['filter_search'] ) )
                                {
                                               
                                    $mysql_query    .=              " GROUP BY ID ";
                                }
                            
                            
                            $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/GROUP_BY', $mysql_query, $network_site->blog_id );
 
                            $mysql_query    .=              " )";
                           
                            restore_current_blog();
                            
                        }
                    
                    $mysql_query    .=   ') results
                        ORDER BY ';
                    
                    
                    $options    =   $WooGC->functions->get_options();
                                            
                    switch ($args['orderby'])
                        {
                            case "order_date"    :
                                                    $mysql_query    .= 'post_date';
                                                    break;
                            case "shop_title"    :
                                                    $mysql_query    .= 'blog_name';
                                                    break;
                            default :
                                                    $mysql_query    .= 'post_date';
                                                    break;
                        }
                        
                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/ORDER_BY', $mysql_query, $network_site->blog_id );
                    
                    $mysql_query    .= ' ';
                        
                    switch (strtolower($args['order']))
                        {
                            case "asc"    :
                                                    $mysql_query    .= 'ASC';
                                                    break;
                            case "desc"    :
                                                    $mysql_query    .= 'DESC';
                                                    break;
                            default :
                                                    $mysql_query    .= 'DESC';
                                                    break;   
                               
                        }
                        
                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query/ORDER', $mysql_query, $network_site->blog_id );
                        
                    $mysql_query    .= '   LIMIT ' . ( $args['per_page'] * ($args['paged'] - 1) ) . ', '. $args['per_page'] ;
                    
                    
                    $mysql_query    =   apply_filters('woogc/network_orders/get_orders/mysql_query', $mysql_query, $network_site->blog_id );

                    
                    $results        =   $wpdb->get_results($mysql_query);
                    $total_records  =   $wpdb->get_var("SELECT FOUND_ROWS()");
                    
                    $data = array(  
                                    'results'       =>  $results,
                                    'total_records' =>  $total_records
                                    );
                    
                    return $data;
                }
            
        }
    
        
?>