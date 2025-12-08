<?php


    defined( 'ABSPATH' ) || exit;
    
    
    class WooGC_shortcodes 
        {
            
            function __construct()
                {
                    add_shortcode( 'woogc_products',                            array( $this, 'do__products' ) );            
                
                    add_action( 'woocommerce_locate_template',                  array( $this,  'woocommerce_locate_template' ), 99, 3 );
                
                }    
            
            function do__products ( $atts )
                {
                    $shortcode_atts =   shortcode_atts( 
                                                        array(
                                                        'sites_id'              =>      array(),
                                                        'categories'            =>      array(),
                                                        'categories_exclude'    =>      array(),
                                                        
                                                        'search'                =>      '',
                                                        
                                                        'min_price'             =>      '',
                                                        'max_price'             =>      '',
                                                        
                                                        'orderby'               =>      array(),
                                                        'order'                 =>      array(),
                                                        'status'                =>      array(),
                                                        'meta_key'              =>      '',
                                                        
                                                        
                                                        'posts_per_page'        =>      get_option( 'posts_per_page' ),
                                                        'page'                  =>      '1',
                                                        
                                                        'disable_pagination'    =>      FALSE
                                                    ), $atts );    
                    
                    if ( ! is_array ( $shortcode_atts['sites_id'] ) )
                        {
                            $shortcode_atts['sites_id'] =   explode (",", $shortcode_atts['sites_id'] );
                            $shortcode_atts['sites_id'] =   array_map('trim', $shortcode_atts['sites_id'] );
                            $shortcode_atts['sites_id'] =   array_filter ( $shortcode_atts['sites_id'] );
                            $shortcode_atts['sites_id'] =   array_map('intval', $shortcode_atts['sites_id'] );
                        }
                        
                    if ( ! is_array ( $shortcode_atts['categories'] ) )
                        {
                            $shortcode_atts['categories'] =   explode (",", $shortcode_atts['categories'] );
                            $shortcode_atts['categories'] =   array_map('trim', $shortcode_atts['categories'] );
                            $shortcode_atts['categories'] =   array_filter ( $shortcode_atts['categories'] );
                            $shortcode_atts['categories'] =   preg_replace("/[^a-zA-Z0-9-_]/i", "", $shortcode_atts['categories'] );
                        }
                    
                    if ( ! is_array ( $shortcode_atts['categories_exclude'] ) )
                        {
                            $shortcode_atts['categories_exclude'] =   explode (",", $shortcode_atts['categories_exclude'] );
                            $shortcode_atts['categories_exclude'] =   array_map('trim', $shortcode_atts['categories_exclude'] );
                            $shortcode_atts['categories_exclude'] =   array_filter ( $shortcode_atts['categories_exclude'] );
                            $shortcode_atts['categories_exclude'] =   preg_replace("/[^a-zA-Z0-9-_]/i", "", $shortcode_atts['categories_exclude'] );
                        }
                        
                    if ( ! is_array ( $shortcode_atts['status'] ) )
                        {
                            $shortcode_atts['status']       =   explode (",", $shortcode_atts['status'] );
                            $shortcode_atts['status']       =   array_map('trim', $shortcode_atts['status'] );
                            $shortcode_atts['status']       =   array_map('strtolower', $shortcode_atts['status'] );
                            $shortcode_atts['status']       =   array_filter ( $shortcode_atts['status'] );
                            $shortcode_atts['status']       =   preg_replace("/[^a-zA-Z0-9-_]/i", "", $shortcode_atts['status'] );
                        }
                    if ( empty ( $shortcode_atts['status'] ) )
                        $shortcode_atts['status'][]    =   'publish';
                        
                    if ( ! empty  ( $shortcode_atts['min_price'] ) )
                        $shortcode_atts['min_price']    =   trim ( intval( $shortcode_atts['min_price'] ) );
                    if ( ! empty  ( $shortcode_atts['max_price'] ) )
                        $shortcode_atts['max_price']    =   trim ( intval( $shortcode_atts['max_price'] ) );
                        
                    $shortcode_atts['orderby']  =   preg_replace("/[^a-zA-Z0-9-_, ]/i", "", $shortcode_atts['orderby'] );
                    if ( ! is_array ( $shortcode_atts['orderby'] ) )
                        {
                            $shortcode_atts['orderby'] =   explode (",", $shortcode_atts['orderby'] );
                            $shortcode_atts['orderby'] =   array_map('trim', $shortcode_atts['orderby'] );
                            $shortcode_atts['orderby'] =   array_filter ( $shortcode_atts['orderby'] );
                            $shortcode_atts['orderby'] =   preg_replace("/[^a-zA-Z0-9-_]/i", "", $shortcode_atts['orderby'] );
                        }
                    if ( count ( $shortcode_atts['orderby'] ) < 1 )
                        $shortcode_atts['orderby'][]  =   'post_title';
                        
                    $shortcode_atts['order']    =   preg_replace("/[^a-zA-Z0-9-_, ]/i", "", $shortcode_atts['order'] );
                    if ( ! is_array ( $shortcode_atts['order'] ) )
                        {
                            $shortcode_atts['order'] =   explode (",", $shortcode_atts['order'] );
                            $shortcode_atts['order'] =   array_map('trim', $shortcode_atts['order'] );
                            $shortcode_atts['order'] =   array_filter ( $shortcode_atts['order'] );
                            $shortcode_atts['order'] =   preg_replace("/[^a-zA-Z0-9-_]/i", "", $shortcode_atts['order'] );
                        }
                    if ( count ( $shortcode_atts['order'] ) < 1 )
                        $shortcode_atts['order'][]  =   'ASC';
                    
                    if ( ! empty  ( $shortcode_atts['meta_key'] ) )
                        $shortcode_atts['meta_key']    =   trim ( preg_replace("/[^a-zA-Z0-9-_ ]/i", "", $shortcode_atts['meta_key'] ) );
                    
                    $shortcode_atts['posts_per_page']   =   trim ( intval( $shortcode_atts['posts_per_page'] ) );
                    
                    if ( ! empty  ( $shortcode_atts['disable_pagination'] ) && ( $shortcode_atts['disable_pagination'] == '1' ||    $shortcode_atts['disable_pagination'] == 'true' ) )
                        {
                            $shortcode_atts['disable_pagination']   =   TRUE;
                        }
                    
                    $shortcode_atts['woogc_shortcode_hash'] =   substr(  md5 ( json_encode ( $shortcode_atts ) ), 0 , 9 );
                                    
                    $shortcode_atts['page']             =   trim ( intval( $shortcode_atts['page'] ) );
                    if ( isset ( $_GET['woogc_shortcode'] ) &&  $_GET['woogc_shortcode'] == $shortcode_atts['woogc_shortcode_hash'] &&  isset ( $_GET['pagination'] ) )
                        {
                            $shortcode_atts['page'] =   trim ( intval( $_GET['pagination'] ) );   
                        }
                    
                                        
                    global $wpdb, $WooGC, $blog_id;
                    
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );

                    $mysql_query    =   'SELECT SQL_CALC_FOUND_ROWS *';
                    $mysql_query    .=  ' FROM (';
                        
                    $first_union    =   TRUE;
                    
                    foreach ( $sites as  $shop )
                        {
                            
                            if ( count ( $shortcode_atts['sites_id'] ) > 0  &&  ! in_array( $shop->blog_id, $shortcode_atts['sites_id'] ) )
                                continue;
                            
                            switch_to_blog( $shop->blog_id );
                            
                            $categories =   array ();
                            if ( is_array ( $shortcode_atts['categories'] ) &&  count ( $shortcode_atts['categories'] ) > 0  )
                                {                                    
                                    foreach ( $shortcode_atts['categories'] as $category_slug )
                                        {
                                            $category = get_term_by('slug', $category_slug, 'product_cat');
                                            if ( is_object( $category ) &&  isset ( $category->term_id ) )
                                                $categories[]   =   $category->term_id;
                                        }
                                        
                                    if ( count ( $categories ) < 1 )
                                        {
                                            restore_current_blog();
                                            continue;  
                                        }
                                }
                                
                            $categories_exclude =   array ();
                            if ( is_array ( $shortcode_atts['categories_exclude'] ) &&  count ( $shortcode_atts['categories_exclude'] ) > 0  )
                                {                                   
                                    foreach ( $shortcode_atts['categories_exclude'] as $category_slug )
                                        {
                                            $category = get_term_by('slug', $category_slug, 'product_cat');
                                            if ( is_object( $category ) &&  isset ( $category->term_id ) )
                                                $categories_exclude[]   =   $category->term_id;
                                        }
                                }
                            
                            $product_visibility_terms  = wc_get_product_visibility_term_ids();
                            $product_visibility_not_in = array( $product_visibility_terms['exclude-from-catalog'] );
                            
                            // Hide out of stock products.
                            if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
                                $product_visibility_not_in[] = $product_visibility_terms['outofstock'];
                            }
                                                      
                            if  ( $first_union ) 
                                $first_union    =   FALSE;
                                else
                                $mysql_query    .=   ' UNION ALL ';
                            
                            $mysql_query    .=   " (SELECT
                                                            ". $wpdb->posts .".*,
                                                            ". $shop->blog_id ." AS blog_id";
                            
                            if ( ! empty ( $shortcode_atts['meta_key'] ) )
                                $mysql_query    .=   ", s.meta_field_value AS mf" ;
                                      
                            $mysql_query    .=   "
                                                        FROM
                                                            ". $wpdb->posts ;
                            
                            if ( ! empty ( $shortcode_atts['meta_key'] ) )
                                $mysql_query    .=   $wpdb->prepare ( "
                                                        LEFT JOIN(
                                                                    SELECT
                                                                        ID,
                                                                        pm.meta_value AS meta_field_value
                                                                    FROM
                                                                        " . $wpdb->posts ."
                                                                    JOIN " . $wpdb->postmeta ." AS pm
                                                                    ON
                                                                        pm.post_id = " . $wpdb->posts .".ID
                                                                    WHERE
                                                                        pm.meta_key = %s
                                                                ) s ON  s.ID = " . $wpdb->posts .".ID
                                                                ", $shortcode_atts['meta_key'] );
                                                        
                            if ( ! empty ( $shortcode_atts['min_price'] ) )
                                $mysql_query    .=   " JOIN " . $wpdb->postmeta ." AS pm1 ON " . $wpdb->posts .".ID = pm1.post_id";
                            if ( ! empty ( $shortcode_atts['max_price'] ) )
                                $mysql_query    .=   " JOIN " . $wpdb->postmeta ." AS pm2 ON " . $wpdb->posts .".ID = pm2.post_id";
                            
                            $mysql_query    .=   "      WHERE
                                                            1 = 1 ";
                             
                            if ( count ( $product_visibility_not_in ) > 0 )
                                {                                
                                    $mysql_query    .=  " AND  ( 
                                                                                      ". $wpdb->posts.".ID NOT IN (
                                                                                                    SELECT object_id
                                                                                                    FROM ". $wpdb->term_relationships."
                                                                                                    WHERE term_taxonomy_id IN ( ". implode ( ", ", $product_visibility_not_in )  .")
                                                                                                )
                                                                                    ) ";
                                }
                                                                            
                            if ( count ( $categories ) > 0 )
                                {    
                                    $mysql_query    .=  " AND  ( 
                                                                              ". $wpdb->posts.".ID IN (
                                                                                            SELECT object_id FROM ". $wpdb->term_relationships." as tr
                                                                                            JOIN ". $wpdb->term_taxonomy." as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                                                                                            WHERE tt.term_id IN ( ". implode ( ", ", $categories )  .")
                                                                                        )
                                                                            ) ";
                                }
                                
                            if ( count ( $categories_exclude ) > 0 )
                                {    
                                    $mysql_query    .=  " AND  ( 
                                                                              ". $wpdb->posts.".ID NOT IN (
                                                                                            SELECT object_id FROM ". $wpdb->term_relationships." as tr
                                                                                            JOIN ". $wpdb->term_taxonomy." as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                                                                                            WHERE tt.term_id IN ( ". implode ( ", ", $categories_exclude )  .")
                                                                                        )
                                                                            ) ";
                                }
                                
                            if ( ! empty ( $shortcode_atts['search'] ) )
                                {
                                    $search_phrase  =   '%' . $wpdb->esc_like( $shortcode_atts['search'] ) . '%';
                                    $mysql_query    .=  " AND  ( ". $wpdb->posts . '.post_title LIKE "' .  $search_phrase .'"  OR '. $wpdb->posts.'.post_content LIKE "' .  $search_phrase .'" ) ';
                                    
                                }
                                
                            if ( ! empty ( $shortcode_atts['min_price'] ) )
                                {
                                    $mysql_query    .=   $wpdb->prepare(" AND ( pm1.meta_key = '_regular_price' AND pm1.meta_value >= %d)", $shortcode_atts['min_price'] );   
                                }
                            if ( ! empty ( $shortcode_atts['max_price'] ) )
                                {
                                    $mysql_query    .=   $wpdb->prepare(" AND ( pm2.meta_key = '_regular_price' AND pm2.meta_value <= %d)", $shortcode_atts['max_price'] );   
                                }
                                                                            
                            $mysql_query    .=   " AND  ". $wpdb->posts.".post_password = '' AND ". $wpdb->posts.".post_type = 'product' AND ". $wpdb->posts.".post_status IN ('" . implode( "', '", array_map( 'esc_sql', $shortcode_atts['status'] ) ) . "')
                                                        GROUP BY
                                                            ". $wpdb->posts.".ID)";
                            
                            restore_current_blog();
                        }
                        
                        
                    $mysql_query    .=   ' ) results'; 
                    
                    //add the order
                    $order_arguments    =   array ();
                    foreach ( $shortcode_atts['orderby']    as  $key    =>  $value )
                        {
                            $order  =   isset ( $shortcode_atts['order'][$key] ) ?  $shortcode_atts['order'][$key]  :   'ASC'; 
                            
                            switch  ( $value )
                                {
                                    case 'id':
                                        $order_arguments[]    =   ' ID ' . $order;
                                        break;
                                    case 'menu_order':
                                        $order_arguments[]    =   '  menu_order, post_title ' . $order;
                                        break;
                                    case 'title':
                                        $order_arguments[]    =   ' post_title ' . $order;
                                        break;
                                    case 'rand':
                                        $order_arguments[]    =   ' rand()';
                                        break;
                                    case 'date':
                                        $order_arguments[]    =   ' post_date ' . $order;
                                        break;
                                        
                                    case 'meta_value':
                                        $order_arguments[]    =   ' mf IS NULL ASC,  mf ' . $order;
                                        break;
                                    case 'meta_value_num':
                                        $order_arguments[]    =   ' mf IS NULL ASC,  CAST( mf AS UNSIGNED) ' . $order;
                                        break;
                                                                                  
                                    default:
                                        $order_arguments[]    =   ' post_title ' . $order;
                                        break;                                          
                                }
                        }
                    $mysql_query    .=  ' ORDER BY ' . implode ( ",", $order_arguments );
                    
                    //Add limit argument to the query                        
                    $mysql_query    .=   ' LIMIT ' . $shortcode_atts['posts_per_page'] . " OFFSET " . ( ( $shortcode_atts['posts_per_page'] * $shortcode_atts['page'] ) - $shortcode_atts['posts_per_page'] );
                    
                    $shortcode_posts        =   $wpdb->get_results ( $mysql_query );
                    $shortcode_posts_count  =   $wpdb->get_var('SELECT FOUND_ROWS()');
                    
                    ob_start();
                    
                    $shortcode_atts['_found_posts'] =   $shortcode_posts_count;
                    
                    if ( $shortcode_posts_count > 0 )
                        {
                            wc_get_template(
                                'shortcodes/woogc-products.php',
                                                                    array(
                                                                        'products'          =>  $shortcode_posts,
                                                                        'shortcode_atts'    =>  $shortcode_atts
                                                                    )
                            );
                        }
                    
                    
                    $html = ob_get_clean();
                    
                    return $html;
                    
                }
                
                
                
            function woocommerce_locate_template( $template_file, $template, $template_base )
                {
                    $specific_templates =   array(
                                                    'shortcodes/woogc-products.php',
                                                    'shortcodes/woogc-products-pagination.php'
                                                    );
                        
                    if( !in_array($template, $specific_templates ) )
                        return $template_file;
                        
                    //check if returned $template_file is pointing to theme file
                    if ( strpos( $template_file, STYLESHEETPATH ) !== FALSE   ||  strpos( $template_file, TEMPLATEPATH ) !== FALSE) 
                        return $template_file;
                    
                    $template_file  =   WOOGC_PATH .   'templates/' . $template;
                       
                    return $template_file;    

                }
                
                
            
            
            /**
            * Create a navigational link for the shortocde
            * 
            * @param mixed $args
            */
            static public function paginate_links( $args = '' ) 
                {
                    
                    $total   = ceil ( $args['_found_posts'] / $args['posts_per_page'] );
                    
                    // Setting up default values based on the current URL.
                    $pagenum_link = html_entity_decode( get_pagenum_link() );
                    $url_parts    = explode( '?', $pagenum_link );

                    // Append the format placeholder to the base URL.
                    $pagenum_link = trailingslashit( $url_parts[0] );
                    
                    $defaults = array(
                        'base'               => $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below).
                        'total'              => $total,
                        'current'            => 1,
                        'aria_current'       => 'page',
                        'show_all'           => false,
                        'prev_next'          => true,
                        'prev_text'          => __( '&laquo; Previous' ),
                        'next_text'          => __( 'Next &raquo;' ),
                        'end_size'           => 1,
                        'mid_size'           => 2,
                        'add_args'           => array(), // Array of query args to add.
                        'add_fragment'       => '',
                        'before_page_number' => '',
                        'after_page_number'  => '',
                    );

                    $args = wp_parse_args( $args, $defaults );
                    
                    if ( isset ( $url_parts[1] ) )
                        {
                            $_add_args;
                            $add_args   =   explode ( "&", $url_parts[1] );
                            foreach ( $add_args as  $key    =>  $pair )
                                {
                                    list( $key, $value )    =   explode ( "=", $pair );
                                    $_add_args[$key]            =   $value;
                                }
                                
                            $add_args   =   $_add_args;
                        }
                        else
                        $add_args   =   array ();
                        
                    $add_args['woogc_shortcode'] =   $args['woogc_shortcode_hash'];
                    
                    if ( ( isset ( $_GET['woogc_shortcode'] ) && $add_args['woogc_shortcode']   ==  $_GET['woogc_shortcode'] ) && isset( $_GET['pagination'] ) )
                        {
                            $current = isset( $_GET['pagination'] ) ?   intval ( $_GET['pagination'] ) :    1;
                            $args['current']    =   $current;
                        }
                    
                    // Who knows what else people pass in $args.
                    $total = (int) $args['total'];
                    if ( $total < 2 ) {
                        return;
                    }
                    $current  = (int) $args['current'];
                    $end_size = (int) $args['end_size']; // Out of bounds? Make it the default.
                    if ( $end_size < 1 ) {
                        $end_size = 1;
                    }
                    $mid_size = (int) $args['mid_size'];
                    if ( $mid_size < 0 ) {
                        $mid_size = 2;
                    }

                    $r          = '';
                    $page_links = array();
                    $dots       = false;

                    if ( $args['prev_next'] && $current && 1 < $current ) :
                        $link = $args['base'];
                        if ( $add_args ) {
                            $_add_args   =   $add_args;
                            $_add_args['pagination']    =   $current - 1;   
                            $link = add_query_arg( $_add_args, $link );
                        }
                        $link .= $args['add_fragment'];

                        $page_links[] = sprintf(
                            '<a class="prev page-numbers" href="%s">%s</a>',
                            /**
                             * Filters the paginated links for the given archive pages.
                             *
                             * @since 3.0.0
                             *
                             * @param string $link The paginated link URL.
                             */
                            esc_url( apply_filters( 'paginate_links', $link ) ),
                            $args['prev_text']
                        );
                    endif;

                    for ( $n = 1; $n <= $total; $n++ ) :
                        if ( $n == $current ) :
                            $page_links[] = sprintf(
                                '<span aria-current="%s" class="page-numbers current">%s</span>',
                                esc_attr( $args['aria_current'] ),
                                $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']
                            );

                            $dots = true;
                        else :
                            if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
                                $link = $args['base'];
                                if ( $add_args ) {
                                    $_add_args   =   $add_args;
                                    $_add_args['pagination']    =   $n;
                                    $link = add_query_arg( $_add_args, $link );
                                }
                                $link .= $args['add_fragment'];

                                $page_links[] = sprintf(
                                    '<a class="page-numbers" href="%s">%s</a>',
                                    /** This filter is documented in wp-includes/general-template.php */
                                    esc_url( apply_filters( 'paginate_links', $link ) ),
                                    $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']
                                );

                                $dots = true;
                            elseif ( $dots && ! $args['show_all'] ) :
                                $page_links[] = '<span class="page-numbers dots">' . __( '&hellip;' ) . '</span>';

                                $dots = false;
                            endif;
                        endif;
                    endfor;

                    if ( $args['prev_next'] && $current && $current < $total ) :
                        $link = $args['base'];
                        if ( $add_args ) {
                            $_add_args   =   $add_args;
                            $_add_args['pagination']    =   $current + 1;
                            $link = add_query_arg( $_add_args, $link );
                        }
                        $link .= $args['add_fragment'];

                        $page_links[] = sprintf(
                            '<a class="next page-numbers" href="%s">%s</a>',
                            /** This filter is documented in wp-includes/general-template.php */
                            esc_url( apply_filters( 'paginate_links', $link ) ),
                            $args['next_text']
                        );
                    endif;

                    $r = implode( "\n", $page_links );

                    echo $r;
                }
            
            
        }
    
    
    new WooGC_shortcodes();

    
?>