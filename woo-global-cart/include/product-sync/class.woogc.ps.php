<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS
        {
            
            private $_triggered_messages_for_id  =   array();
               
            public function init()
                {
                    add_action ( 'woocommerce_variation_set_stock',     array ( $this, 'woocommerce_variation_set_stock' ) );    
                    add_action ( 'woocommerce_product_set_stock',       array ( $this, 'woocommerce_product_set_stock'  ) );
                }
            
              
            /**
            * Check if a product is a Main product
            * 
            * @param mixed $product_ID
            */
            public function is_main_product( $product_ID )
                {
                    if ( $this->is_child_product ( $product_ID ) )
                        return FALSE;
                    
                    $_woogc_ps_is_main_product   =   get_post_meta( $product_ID, '_woogc_ps_is_main_product', TRUE);
                    if ( $_woogc_ps_is_main_product != 'yes' )
                        return FALSE;
                        
                    return TRUE;
                }
                
            
            /**
            * Check if a product is a Child ( synchronized ) product 
            * 
            * @param mixed $product_ID
            */
            public function is_child_product( $product_ID )
                {
                    $_woogc_ps_is_child_of_pid   =   get_post_meta( $product_ID, '_woogc_ps_is_child_of_pid', TRUE);
                    if ( $_woogc_ps_is_child_of_pid > 0 )
                        return TRUE;
                        
                    return FALSE;
                }
                
            
            /**
            * Check if main product have child at the shop
            *     
            * @param mixed $product_ID
            * @param mixed $shop_id
            */
            public function main_have_child_at_shop ( $product_ID, $shop_id )
                {
                    global $blog_id; 
                    
                    if (  $this->get_product_synchronized_at_shop ( $product_ID, $blog_id, $shop_id ) !== FALSE )
                        return TRUE;
                    
                    return FALSE;
                }
  
            
            /**
            * Return the shops IDs where syncronyze
            * 
            * @param mixed $product_ID
            */
            public function main_get_children( $product_ID )
                {
                    $_woogc_ps_sync_to  =   get_post_meta( $product_ID, '_woogc_ps_sync_to', TRUE);
                    $_woogc_ps_sync_to  =   explode ( ",", $_woogc_ps_sync_to );
                    $_woogc_ps_sync_to  =   array_filter( $_woogc_ps_sync_to );
                        
                    return $_woogc_ps_sync_to;   
                    
                }
                
                
            /**
            * Return the maintained Child ( synchronized ) products
            * 
            * @param mixed $product_ID
            */
            public function main_get_maintain_children( $product_ID )
                {
                    $_woogc_ps_maintain_child  =   get_post_meta( $product_ID, '_woogc_ps_maintain_child', TRUE);
                    $_woogc_ps_maintain_child  =   explode ( ",", $_woogc_ps_maintain_child );
                    $_woogc_ps_maintain_child  =   array_filter( $_woogc_ps_maintain_child );
                        
                    return $_woogc_ps_maintain_child;   
                    
                }
                
            
            /**
            * Return the maintained categories for Child ( synchronized ) products
            * 
            * @param mixed $product_ID
            */
            public function main_get_maintain_categories( $product_ID )
                {
                    $_woogc_ps_maintain_categories  =   get_post_meta( $product_ID, '_woogc_ps_maintain_categories', TRUE);
                    $_woogc_ps_maintain_categories  =   explode ( ",", $_woogc_ps_maintain_categories );
                    $_woogc_ps_maintain_categories  =   array_filter( $_woogc_ps_maintain_categories );
                        
                    return $_woogc_ps_maintain_categories;   
                    
                }
                
            /**
            * Return the maintained stock for Child ( synchronized ) products
            * 
            * @param mixed $product_ID
            */
            public function main_get_maintain_stock( $product_ID )
                {
                    $_woogc_ps_maintain_stock  =   get_post_meta( $product_ID, '_woogc_ps_maintain_stock', TRUE);
                    $_woogc_ps_maintain_stock  =   explode ( ",", $_woogc_ps_maintain_stock );
                    $_woogc_ps_maintain_stock  =   array_filter( $_woogc_ps_maintain_stock );
                        
                    return $_woogc_ps_maintain_stock;   
                    
                }
                
            
            /**
            * Check if a product is maintained at the shop ID
            * 
            * @param mixed $product_ID
            * @param mixed $shop_ID
            */
            private function is_product_synchronized_at_shop ( $product_ID, $product_blog_ID, $check_at_shop_id )
                {
                    if ( $this->get_product_synchronized_at_shop ( $product_ID, $product_blog_ID, $check_at_shop_id ) )
                        return TRUE;
                        else
                        return FALSE;
                }
                
            
            /**
            * Check if a product is maintained at the shop ID
            * 
            * @param mixed $product_ID
            * @param mixed $shop_ID
            */
            public function get_product_synchronized_at_shop ( $product_ID, $product_blog_ID, $check_at_shop_id )
                {
                    global $wpdb;
                    
                    switch_to_blog( $check_at_shop_id );
                    
                    $mysql_query    =   $wpdb->prepare ( "SELECT pm.post_id FROM " . $wpdb->postmeta . " as pm
                                                            JOIN  " . $wpdb->postmeta . " as pm1 ON pm.post_id  =   pm1.post_id
                                                            WHERE pm.meta_key = '_woogc_ps_is_child_of_pid' AND pm.meta_value = %d
                                                                    AND pm1.meta_key = '_woogc_ps_is_child_of_bid' AND pm1.meta_value = %d", $product_ID, $product_blog_ID );
                    $found_product_ID  =   $wpdb->get_var( $mysql_query );
                    
                    restore_current_blog();
                    
                    if ( $found_product_ID  > 0 )
                        return $found_product_ID;
                        else
                        return FALSE;
                }
            
            
            /**
            * Get origin Main Product ID and the origin shop ID
            * 
            * @param mixed $product_ID
            */
            public function child_get_main ( $product_ID )
                {
                    $origin_product_id  =   get_post_meta( $product_ID, '_woogc_ps_is_child_of_pid', TRUE );
                    $origin_blog_id     =   get_post_meta( $product_ID, '_woogc_ps_is_child_of_bid', TRUE );
                         
                    return array ( $origin_product_id, $origin_blog_id );
                }
            
            
            /**
            * Synchronize the stock for a product if required 
            * 
            * @param mixed $product
            */
            public function woocommerce_product_set_stock( $product )
                {
                    if ( ! $this->is_main_product( $product->get_ID() ) &&    ! $this->is_child_product( $product->get_ID() ) )
                        return;
                    
                    global $wpdb;
                    
                    $current_stock          =   $product->get_stock_quantity();
                    
                    $main_product_ID        =   '';
                    $started_from_child     =   FALSE;
                    $started_from_child_ID  =   FALSE;
                    
                    if ( $this->is_main_product( $product->get_ID() ) )
                        $main_product_ID    =   $product->get_ID();
                        else
                        {
                            $child_product      =   new WooGC_PS_child_product( $product->get_ID() );
                            
                            if ( $child_product->is_stock_sync()    === FALSE )
                                return;
                            
                            $main_product_ID    =   $child_product->get_main_id();
                            $main_shop_ID       =   $child_product->get_main_shop_id();
                            
                            if ( empty ( $main_product_ID ) ||   empty ( $main_shop_ID ) )
                                return;
                            
                            switch_to_blog( $main_shop_ID );
                            
                            $started_from_child     =   TRUE;
                            $started_from_child_ID  =   $product->get_ID();
                        }
                    
                    $main_product               =   new WooGC_PS_main_product( $main_product_ID );
                    $product_maintain_stock     =   $main_product->get_maintained_stock();
                    
                    if ( $started_from_child    === TRUE )
                        {
                            $mysql_query    =   $wpdb->prepare ( "UPDATE " . $wpdb->postmeta ." 
                                                                        SET meta_value  =   %f
                                                                        WHERE post_id   =   %d  AND meta_key    =   '_stock'", $current_stock, $main_product_ID );
                            $wpdb->query( $mysql_query );
                        }
                    
                    foreach ( $product_maintain_stock   as  $product_shop )
                        {
                            $child_product_id   =   $main_product->get_child_at_shop( $product_shop );   
                            if ( empty ( $child_product_id ) )
                                continue;
                            
                            if ( $started_from_child    === TRUE    &&  $started_from_child_ID  ==  $child_product_id )
                                continue;
                            
                            switch_to_blog( $product_shop );
                            
                            $mysql_query    =   $wpdb->prepare ( "UPDATE " . $wpdb->postmeta ." 
                                                                        SET meta_value  =   %f
                                                                        WHERE post_id   =   %d  AND meta_key    =   '_stock'", $current_stock, $child_product_id );
                            $wpdb->query( $mysql_query );
                            
                            
                            restore_current_blog();
                        }
                    
                    if ( $started_from_child    === TRUE )
                        restore_current_blog();
                    
                }
            
            /**
            * Synchronize the stock for a product variation if required 
            * 
            * @param mixed $product
            */
            public function woocommerce_variation_set_stock( $product )
                {

                    $parent_ID  =   $product->get_parent_id();
                    
                    if ( ! $this->is_main_product( $parent_ID ) &&    ! $this->is_child_product( $parent_ID ) )
                        return;
                    
                    global $wpdb, $blog_id;
                    
                    $current_stock              =   $product->get_stock_quantity();
                    
                    $main_product_ID            =   '';
                    $main_product_variation_ID  =   '';
                    $started_from_child         =   FALSE;
                    $started_from_child_ID      =   FALSE;
                    
                    if ( $this->is_main_product( $parent_ID ) )
                        {
                            $main_product_ID    =   $parent_ID;
                            $main_product_variation_ID  =   $product->get_ID();
                        }
                        else
                        {
                            $child_product      =   new WooGC_PS_child_product( $parent_ID );
                            
                            if ( $child_product->is_stock_sync()    === FALSE )
                                return;
                            
                            $main_product_ID    =   $child_product->get_main_id();
                            $main_shop_ID       =   $child_product->get_main_shop_id();
                            
                            $main_product_variation_ID  =   get_post_meta( $product->get_ID(), '_woogc_ps_is_child_of_pid', TRUE);
                            
                            if ( empty ( $main_product_ID ) ||   empty ( $main_shop_ID ) || empty ( $main_product_variation_ID ) )
                                return;
                            
                            switch_to_blog( $main_shop_ID );
                            
                            $started_from_child     =   TRUE;
                            $started_from_child_ID  =   $product->get_ID();
                        }
                    
                    $main_product               =   new WooGC_PS_main_product( $main_product_ID );
                    $product_maintain_stock     =   $main_product->get_maintained_stock();
                    
                    if ( $started_from_child    === TRUE )
                        {
                            $mysql_query    =   $wpdb->prepare ( "UPDATE " . $wpdb->postmeta ." 
                                                                        SET meta_value  =   %s
                                                                        WHERE post_id   =   %d  AND meta_key    =   '_stock'", $current_stock, $main_product_variation_ID );
                            $wpdb->query( $mysql_query );
                        }
                    
                    foreach ( $product_maintain_stock   as  $product_shop )
                        {
                            $child_product_variation_id   =   $found_product_ID   =   $this->get_product_synchronized_at_shop( $main_product_variation_ID, $blog_id, $product_shop );
                            if ( empty ( $child_product_variation_id ) )
                                continue;
                            
                            if ( $started_from_child    === TRUE    &&  $started_from_child_ID  ==  $child_product_variation_id )
                                continue;
                            
                            switch_to_blog( $product_shop );
                            
                            $mysql_query    =   $wpdb->prepare ( "UPDATE " . $wpdb->postmeta ." 
                                                                        SET meta_value  =   %s
                                                                        WHERE post_id   =   %d  AND meta_key    =   '_stock'", $current_stock, $child_product_variation_id );
                            $wpdb->query( $mysql_query );
                            
                            
                            restore_current_blog();
                        }
                    
                    if ( $started_from_child    === TRUE )
                        restore_current_blog();
                    
                }

            
            
            
            
                
                
            /**
            * Synchronize the product ID to specified shops
            * 
            * @param mixed $product_ID
            * @param mixed $sync_to
            * @param mixed $args
            */
            function synchronize_to ( $product_ID, $sync_to, $args )
                {
                    
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
                                            
                    foreach ( $sync_to as  $shop_id )
                        {
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
                            
                            $synchronized_product_ID    =   $this->synchronize_product( $product, $shop_id, $sync_args );
                            
                            $attributes =   $product->get_attributes();
                            if ( count ( $attributes )  >   0 )
                                {
                                    $terms_ids  =   $this->synchronize_attributes( $attributes, $shop_id );
                                    
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
                                                    $found_product_ID   =   $this->get_product_synchronized_at_shop( $group_product_id, $blog_id, $shop_id );
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
                                                    $variation_sync_product_ID    =   $this->synchronize_product( $main_product_variation, $shop_id, $sync_args );
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
                    
                    
                    $this->clean_internal_messages();
                    
                    $interface_messages[]   =   array ( 
                                                        'type'      =>  'success',
                                                        'message'   =>  __( 'Product successfully synchronized.', 'woo-global-cart')
                                                        );
                    
                    return $interface_messages;
                    
                    
                }
            
            
            
            /**
            * Synchronize a Product to a specified Shop
            * 
            * @param mixed $parent_product
            * @param mixed $shop_id
            * @param mixed $sync_args
            */
            public function synchronize_product ( $main_product, $shop_id, $sync_args )
                {
                    global $blog_id;
                    
                    $origin_product_blog_ID    =   $blog_id;
                    
                    $main_product_data  =   $main_product->get_data();
                    $origin_author_id   =   get_post_field( 'post_author', $main_product->get_ID() );
                        
                    switch_to_blog( $shop_id );
                       
                    $switched_type  =   FALSE;
                    
                    //check if the product already synchronized
                    $synchronized_product_ID    =   $this->get_product_synchronized_at_shop ( $main_product->get_ID(), $origin_product_blog_ID, $shop_id );
                    if ( $synchronized_product_ID   === FALSE  )
                        {
                            $child_product  =   clone ( $main_product );
                            $child_product->set_ID( 0 );
                            
                            if ( $main_product->get_type() == 'variation' )
                                $child_product->set_parent_id( $sync_args['synchronized_product_id'] );    
                            
                            //remove specific data
                            $child_product->set_category_ids( array() );
                            $child_product->set_tag_ids( array() );
                            
                            //remove all _woogc meta data
                            foreach ( $child_product->get_meta_data()   as  $meta )
                                {
                                    $meta_data  =   $meta->get_data();
                                    $child_product->delete_meta_data( $meta_data['key'] );         
                                }
                            
                            $synchronized_product_ID    =   $child_product->save();
                            
                            //set the origin author id
                            wp_update_post( array( 'ID' => $synchronized_product_ID, 'post_author' => $origin_author_id ) );
                            
                            $child_product  =   $this->synchronize_product_data ( $child_product, $main_product_data, $origin_product_blog_ID, $switched_type, $sync_args );
                            $child_product->save();                                    
                        }
                        else
                        {
                            $child_product  =   wc_get_product( $synchronized_product_ID );
                                                        
                            if ( $child_product->get_type() !=  $main_product->get_type() )
                                {
                                    $child_product  =   clone ( $main_product );
                                    $child_product->set_ID( $synchronized_product_ID );
                                    
                                    //remove all metadata
                                    foreach ( $child_product->get_meta_data()   as  $meta )
                                        {
                                            $meta_data  =   $meta->get_data();                                               
                                            $child_product->delete_meta_data( $meta_data['key'] );
                                        }
                                             
                                    //$switched_type  =   TRUE;
                                }
                            
                            $child_product  =   $this->synchronize_product_data ( $child_product, $main_product_data, $origin_product_blog_ID, $switched_type, $sync_args );
                            
                            $child_product->save();
                            
                            //set the origin author id
                            wp_update_post( array( 'ID' => $synchronized_product_ID, 'post_author' => $origin_author_id ) );
                        }
                    
                    update_post_meta( $synchronized_product_ID, '_woogc_ps_is_child_of_pid', $main_product->get_ID() );
                    update_post_meta( $synchronized_product_ID, '_woogc_ps_is_child_of_bid', $origin_product_blog_ID );
                  
                    restore_current_blog();
                    
                    do_action( 'woogc/ps/synchronize_product/completed', $synchronized_product_ID, $main_product->get_ID(), $shop_id );
                
                    return  $synchronized_product_ID;
                
                }
                
            
            /**
            * Synchronize the product data
            * 
            * @param mixed $child_product
            * @param mixed $main_product_data
            * @param mixed $origin_product_blog_ID
            */
            public function synchronize_product_data( $child_product, $main_product_data, $origin_product_blog_ID, $switched_type   =   FALSE, $sync_args = array() )
                {
                    global $interface_messages, $blog_id;
                    
                    //compare the changes
                    foreach ( $main_product_data    as $prop_title  =>  $prop_value )
                        {
                            do_action ( 'woogc/ps/synchronize_product/synchronize_product_data', $prop_title, $prop_value, $child_product, $main_product_data, $origin_product_blog_ID );
                            
                            if ( apply_filters( 'woogc/ps/synchronize_product/ignore_meta_key', FALSE, $prop_title, $prop_value, $child_product, $main_product_data, $origin_product_blog_ID ) )
                                continue;
                            
                               
                            switch ( $prop_title )
                                {
                                    case 'attributes'    :
                                                                $child_product->set_attributes( $prop_value );
                                                                break;
                                                                
                                    case 'id'           :
                                                                break;
                                    
                                    case 'category_ids' :
                                                                if ( isset ( $sync_args['maintain_categories'] )    &&  $sync_args['maintain_categories']   === TRUE )
                                                                    {
                                                                        $this->synchronize_taxonomy( $prop_value, $child_product->get_ID(), $origin_product_blog_ID, 'product_cat' );
                                                                    }
                                                                break;
                                    
                                    case 'children' :
                                                                break;
                                                            
                                    case 'catalog_visibility' :
                                                                if ( $child_product->{'get_' . $prop_title}() !=    $prop_value )
                                                                    $child_product->{'set_' . $prop_title}( $prop_value );
                                                                break;
                                                                
                                    case 'cogs_value' :
                                                                                                                                    
                                                                break;
                                                                
                                    case 'cross_sell_ids' :
                                                                $cross_sell_ids =   $this->synchronize_cross_sell_ids( $prop_value, $origin_product_blog_ID );
                                                                $child_product->set_cross_sell_ids( array_values ( $cross_sell_ids ) );
                                                                
                                                                break;
                                                                
                                    case 'image_id' :
                                                                if ( $prop_value < 1 )
                                                                    {
                                                                        $child_product->set_image_id( $prop_value );
                                                                        continue 2;
                                                                    }
                                                                    
                                                                $image_id   =   $this->synchronize_image( $prop_value, $origin_product_blog_ID );
                                                                if ( $image_id  >   0 )
                                                                    $child_product->set_image_id( $image_id );
                                                                break;

                                    case 'gallery_image_ids' :
                                                                if ( ! is_array ( $prop_value ) ||  count ( $prop_value ) < 1 )
                                                                    {
                                                                        $child_product->set_gallery_image_ids( $prop_value );
                                                                        continue 2;
                                                                    }
                                                                
                                                                $gallery    =   array();
                                                                
                                                                foreach ( $prop_value   as  $origin_image_id )
                                                                    {
                                                                        $image_id   =   $this->synchronize_image( $origin_image_id, $origin_product_blog_ID );
                                                                        if ( $image_id  >   0   )
                                                                            $gallery[]  =   $image_id;
                                                                    }
                                                                $child_product->set_gallery_image_ids( $gallery );
                                                                break;
                                    
                                    case 'parent_id' :                            
                                                                break;
                                    
                                    
                                    case 'meta_data'    :
                                                                foreach ( $prop_value   as  $meta )
                                                                    {
                                                                        $meta_data  =   $meta->get_data();
                                                                        if ( strpos( $meta_data['key'], '_woogc' ) === 0 || apply_filters( 'woogc/ps/synchronize_product/ignore_meta_key', FALSE, $meta_data['key'], $meta_data, $child_product, $main_product_data, $origin_product_blog_ID ) )
                                                                            continue;
                                                                        
                                                                        $meta_value =   apply_filters ( 'woogc/ps/synchronize_product/meta_data/value', $meta_data['value'], $meta_data, $child_product, $main_product_data, $origin_product_blog_ID );
                                                                            
                                                                        $child_product->update_meta_data( $meta_data['key'], $meta_data['value'] );         
                                                                    }
                                                                break;
                                                                
                                    case 'shipping_class_id' :
                                                                $this->synchronize_taxonomy( $prop_value, $child_product->get_ID(), $origin_product_blog_ID, 'product_shipping_class' );
                                                                break;
                                    
                                    case 'sku' :
                                                                if ( $switched_type )
                                                                    {
                                                                        if ( is_string( $prop_value ))
                                                                            $child_product->{'set_' . $prop_title}( '' );
                                                                        else if ( is_array ( $prop_value ) )
                                                                            $child_product->{'set_' . $prop_title}( array() );
                                                                    }
                                                                
                                                                if ( $child_product->{'get_' . $prop_title}() !==    $prop_value )
                                                                    {
                                                                        try {
                                                                            $child_product->{'set_' . $prop_title}( $prop_value );
                                                                        } catch (Exception $e) {
                                                                            $blog_details = get_blog_details( $blog_id ); 
                                                                            $interface_messages[]   =   array ( 
                                                                                                                    'type'      =>  'error',
                                                                                                                    'message'   =>  __( 'Unable to set SKU metadata with value of ', 'woo-global-cart') . '<b>' . $prop_value . '</b>. ' . __( 'Ensure the value is not assigned to a different products on the target shop ', 'woo-global-cart') . '<b>' . $blog_details->blogname . '</b>'
                                                                                                                    );
                                                                        }
                                                                        
                                                                        
                                                                    }
                                                                    
                                                                break;
                                    
                                    case 'stock_status' :
                                                                if ( $child_product->{'get_' . $prop_title}() !=    $prop_value )
                                                                    $child_product->{'set_' . $prop_title}( $prop_value );
                                                                break;
                                    
                                    case 'tag_ids' :
                                                                if ( isset ( $sync_args['maintain_categories'] )    &&  $sync_args['maintain_categories']   === TRUE )
                                                                    {
                                                                        $this->synchronize_taxonomy( $prop_value, $child_product->get_ID(), $origin_product_blog_ID, 'product_tag' );
                                                                    }
                                                                break;
                                    
                                    case 'tax_class' :
                                                                if ( empty ( $prop_value ) )
                                                                    continue 2;
                                                                
                                                                $woocommerce_calc_taxes =   get_option('woocommerce_calc_taxes');
                                                                if ( $woocommerce_calc_taxes    !=  'yes')
                                                                    continue 2;
                                                                
                                                                if ( $prop_value    ==  'parent' )
                                                                    {
                                                                        $child_product->set_tax_class( $prop_value );   
                                                                    }
                                                                    else
                                                                    {
                                                                        $tax_classes = WC_Tax::get_tax_class_slugs();
                                                                        
                                                                        if ( array_search ( $prop_value, $tax_classes ) === FALSE )
                                                                            {
                                                                                $interface_messages[]   =   array ( 
                                                                                                                        'type'      =>  'error',
                                                                                                                        'message'   =>  __( 'Unable to set Tax Class ', 'woo-global-cart') . '<b>' . $prop_value . '</b>' . __( ' on shop ID ', 'woo-global-cart') . '<b>' . $blog_id . '</b>' .__( ' as the class does not exists. Create it then Update.', 'woo-global-cart')
                                                                                                                        );   
                                                                                
                                                                            }
                                                                            else
                                                                            $child_product->set_tax_class( $prop_value );
                                                                    }
                                                                
                                                                break;
                                                                
                                    case 'tax_status' :
                                                                if ( $child_product->{'get_' . $prop_title}() !=    $prop_value )
                                                                    $child_product->{'set_' . $prop_title}( $prop_value );
                                                                break;
                                                                
                                    case 'total_sales' :
                                                                break;
                                                                
                                    case 'upsell_ids' :
                                                                $upsell_ids =   $this->synchronize_upsell_ids( $prop_value, $origin_product_blog_ID );
                                                                $child_product->set_upsell_ids( array_values ( $upsell_ids ) );
                                                                
                                                                break;
                                    
                                    default:
                                                                if ( $switched_type )
                                                                    {
                                                                        if ( is_string( $prop_value ))
                                                                            $child_product->{'set_' . $prop_title}( '' );
                                                                        else if ( is_array ( $prop_value ) )
                                                                            $child_product->{'set_' . $prop_title}( array() );
                                                                    }
                                                                
                                                                if ( $child_product->{'get_' . $prop_title}() !==    $prop_value )
                                                                    {
                                                                        try {
                                                                            $child_product->{'set_' . $prop_title}( $prop_value );
                                                                        } catch (Exception $e) {
                                                                             $interface_messages[]   =   array ( 
                                                                                                                    'type'      =>  'error',
                                                                                                                    'message'   =>  __( 'Unable to set ', 'woo-global-cart') . '<b>' . $prop_title . '</b>' . __( ' metadata with value ', 'woo-global-cart') . '<b>' . $prop_value . '</b>'
                                                                                                                    );
                                                                        }
                                                                        
                                                                        
                                                                    }
                                    
                                }
                        }   
                    
                    
                    $child_product  =   apply_filters ( 'woogc/ps/synchronize_product/child_product', $child_product, $main_product_data, $origin_product_blog_ID );
                                        
                    return $child_product;    
                }
            
                
            
            /**
            * Synchronize image to child product
            * 
            * @param mixed $prop_value
            * @param mixed $origin_product_blog_ID
            * @param mixed $synchronized_product_ID
            */
            static public function synchronize_image( $prop_value, $origin_product_blog_ID )
                {
                    //Check if the current image ID has been previously synchronized tothe  current shop
                    global $wpdb;
                    
                    $mysql_query    =   $wpdb->prepare( "SELECT meta_value FROM ". $wpdb->postmeta . " WHERE meta_key = '_woogc_image_sync_%d_%d'", $origin_product_blog_ID, $prop_value );
                    $local_image_id     =   $wpdb->get_var( $mysql_query );
                
                    if ( ! empty ( $local_image_id ) )
                        {                            
                            //check if the image still exists
                            $image_data =   get_post( $local_image_id );
                            
                            if ( is_object ( $image_data ) &&   $image_data->post_type  ==  'attachment' )
                                return $local_image_id;
                        }
                    
                    switch_to_blog( $origin_product_blog_ID );
                    
                    $image_data =   wp_get_attachment_image_src( $prop_value, 'full' );
                    
                    restore_current_blog();
                        
                    $local_image_id =   media_sideload_image ( $image_data[0], 0, null, 'id' );
                    
                    if ( is_int ( $local_image_id )  &&  $local_image_id > 0 )
                        {
                            update_post_meta( $local_image_id, '_woogc_image_sync_' . $origin_product_blog_ID . '_' . $prop_value,  $local_image_id );
                            
                            return $local_image_id;    
                        }
                    
                    return FALSE;
                
                }
                
            
            /**
            * Synchronyze categories
            * 
            * @param mixed $prop_value
            * @param mixed $synchronized_product_ID
            * @param mixed $origin_product_blog_ID
            */
            public function synchronize_categories( $prop_value, $synchronized_product_ID, $origin_product_blog_ID )
                {
                    global $wpdb, $blog_id;    
                    
                    $terms   =   array();
                    
                    switch_to_blog( $origin_product_blog_ID );
                    if ( is_array ( $prop_value ) &&  count ( $prop_value ) > 0 )
                        {
                            foreach ( $prop_value   as  $origin_term_id )
                                {
                                    $term_data  =   get_term_by ( 'term_id', $origin_term_id, 'product_cat' );
                                    $terms[]    =   $term_data;   
                                }
                        }
                    restore_current_blog();
                    
                    $remote_term_ids    =   array();
                    
                    //check if exists locally
                    foreach ( $terms    as  $term_data )
                        {
                            $remote_term_data  =   get_term_by ( 'name', $term_data->name, 'product_cat' );
                            if ( ! $remote_term_data  )
                                {
                                    $insert_term    =   wp_insert_term ( $term_data->name, 'product_cat' );
                                    $remote_term_data   =   get_term_by ( 'term_id', $insert_term['term_id'], 'product_cat' );
                                }
                                
                            $remote_term_ids[]  =   $remote_term_data->term_id;
                        }
                    
                    wp_set_post_terms( $synchronized_product_ID, $remote_term_ids, 'product_cat', FALSE );

                    
                }
            
            
            /**
            * Synchronyze taxonomy
            *     
            * @param mixed $prop_value
            * @param mixed $synchronized_product_ID
            * @param mixed $origin_product_blog_ID
            * @param mixed $taxonomy_title
            */
            public function synchronize_taxonomy( $prop_value, $synchronized_product_ID, $origin_product_blog_ID, $taxonomy_title )
                {
                    global $wpdb, $blog_id;    
                    
                    $terms   =   array();
                    
                    if ( ! is_array ( $prop_value ) && ! empty ( trim ( $prop_value ) ) )
                        {
                            $prop_value =   array ( $prop_value );   
                        }
                    
                    if ( ! is_array ( $prop_value ) )
                        $prop_value =   array();
                    
                    switch_to_blog( $origin_product_blog_ID );
                    if ( is_array ( $prop_value ) &&  count ( $prop_value ) > 0 )
                        {
                            foreach ( $prop_value   as  $origin_term_id )
                                {
                                    $term_data  =   get_term_by ( 'term_id', $origin_term_id, $taxonomy_title );
                                    $terms[]    =   $term_data;   
                                }
                        }
                    restore_current_blog();
                    
                    $remote_term_ids    =   array();
                    
                    //check if exists locally
                    foreach ( $terms    as  $term_data )
                        {
                            $remote_term_data  =   get_term_by ( 'name', $term_data->name, $taxonomy_title );
                            if ( ! $remote_term_data  )
                                {
                                    $insert_term        =   wp_insert_term ( $term_data->name, $taxonomy_title, array ( 'description'   =>  $term_data->description ) );
                                    $remote_term_data   =   get_term_by ( 'term_id', $insert_term['term_id'], $taxonomy_title );
                                    
                                    $remote_term_ids[]  =   $remote_term_data->term_id;
                                    
                                    $parent_id = $term_data->parent;
                                    
                                    while ( $parent_id != 0 ) 
                                        {
                                            // Get the parent term data from the remote source
                                            switch_to_blog( $origin_product_blog_ID );
                                            $parent_term_data = get_term_by( 'term_id', $parent_id, $taxonomy_title );
                                            restore_current_blog();
                                            
                                            if ( $parent_term_data ) 
                                                {
                                                    // Check if the parent term exists locally
                                                    $remote_parent_term = get_term_by('name', $parent_term_data->name, $taxonomy_title);
                                                    
                                                    if ( !$remote_parent_term ) 
                                                        {
                                                            // Insert the parent term locally if it doesn't exist
                                                            $inserted_remote_parent = wp_insert_term($parent_term_data->name, $taxonomy_title, array(
                                                                                                                                                'description' => $parent_term_data->description
                                                                                                                                            ));
                                                            
                                                            $remote_parent_term = get_term_by('term_id', $inserted_remote_parent['term_id'], $taxonomy_title);
                                                        }
                                                    
                                                    //update the parent of the remote term    
                                                    $updated_term = wp_update_term( $remote_term_data->term_id, $taxonomy_title, array(
                                                                                                                                            'parent' => $remote_parent_term->term_id,
                                                                                                                                        ));
                                                    $remote_term_data  =   $remote_parent_term;
                                                    
                                                    // Move up the hierarchy to the next parent
                                                    $parent_id = $parent_term_data->parent;
                                                }
                                                else
                                                $parent_id      =   0;
                                        }   
                                }
                                else
                                $remote_term_ids[]  =   $remote_term_data->term_id;
                        }
                    
                    wp_set_post_terms( $synchronized_product_ID, $remote_term_ids, $taxonomy_title, FALSE );

                    
                }
            
            
            /**
            * Synchronyze attributes
            *     
            * @param mixed $attributes
            * @param mixed $shop_id
            * @return WP_Error
            */
            public function synchronize_attributes( $attributes, $shop_id )
                {
                    global $wpdb, $blog_id;
                    
                    $origin_blog_id =   $blog_id;
                    
                    $term_ids   =   array();
                    
                    foreach ( $attributes   as  $attribute_name =>  $attribute )
                        {
                            $origin_attribute_data =   wc_get_attribute( wc_attribute_taxonomy_id_by_name( $attribute->get_name() ) );
                            
                            if ( ! is_object ( $origin_attribute_data ) )
                                continue;
                            
                            $origin_terms  =   $attribute->get_terms();
                            
                            switch_to_blog( $shop_id );
                    
                            if ( wc_attribute_taxonomy_id_by_name( $attribute->get_name() ) < 1 )
                                {
                                    $attribute_data = array(
                                                    'attribute_label'   => $origin_attribute_data->name,
                                                    'attribute_name'    => preg_replace( '/^pa\_/', '', $origin_attribute_data->slug ),
                                                    'attribute_type'    => $origin_attribute_data->type,
                                                    'attribute_orderby' => $origin_attribute_data->order_by,
                                                    'attribute_public'  => isset( $origin_attribute_data->has_archives ) ? (int) $origin_attribute_data->has_archives : 0,
                                                );
                                    $format = array( '%s', '%s', '%s', '%s', '%d' );
                                                                
                                    $results = $wpdb->insert(
                                                                $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                                                                $attribute_data,
                                                                $format
                                                            );

                                    if ( is_wp_error( $results ) ) {
                                        return new WP_Error( 'cannot_create_attribute', $results->get_error_message(), array( 'status' => 400 ) );
                                    }

                                    $id = $wpdb->insert_id;

                                    /**
                                     * Attribute added.
                                     *
                                     * @param int   $id   Added attribute ID.
                                     * @param array $data Attribute data.
                                     */
                                    do_action( 'woocommerce_attribute_added', $id, $attribute_data );
                               

                                    // Clear cache and flush rewrite rules.
                                    wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
                                    delete_transient( 'wc_attribute_taxonomies' );
                                    WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
                                }
                                
                            //add the terms
                            if ( is_array ( $origin_terms ) )
                                {
                                    foreach ( $origin_terms as  $origin_term )
                                        {
                                            //check if already mapped to any term
                                            $mysql_query    =   $wpdb->prepare( "SELECT meta_value FROM ". $wpdb->termmeta . " WHERE meta_key = '_woogc_term_sync_%d_%d'", $origin_blog_id, $origin_term->term_id );
                                            $term_id        =   $wpdb->get_var( $mysql_query );
                                        
                                            if ( $term_id    <   1 )
                                                {
                                                    //check if already exists
                                                    $find_term  =   get_term_by ( 'name', $origin_term->name, $attribute_name );
                                                    
                                                    if ( $find_term === FALSE )
                                                        {
                                                            $term_data    =   wp_insert_term( $origin_term->name,  $attribute_name );
                                                            if ( ! is_wp_error( $term_data )    &&  isset ( $term_data['term_id'] ) )
                                                                {
                                                                    $term_id    =   $term_data['term_id'];
                                                                    update_term_meta( $term_data['term_id'], '_woogc_term_sync_'. $origin_blog_id .'_' . $origin_term->term_id, $term_data['term_id'] );
                                                                }
                                                                else
                                                                continue;
                                                        }
                                                        else
                                                        {
                                                            $term_id    =   $find_term->term_id;
                                                            update_term_meta( $find_term->term_id, '_woogc_term_sync_'. $origin_blog_id .'_' . $origin_term->term_id, $find_term->term_id );
                                                        }
                                                }
                                                
                                            $term_ids[ $term_id ] =   $attribute_name;
                                        }
                                }
                            
                            restore_current_blog();
                                
                        }
                    
                    return $term_ids;
                }
                
            
            public function synchronize_cross_sell_ids( $prop_value, $origin_product_blog_ID )
                {
                    
                    $found_local_products   =   array();
                
                    if ( ! is_array ( $prop_value ) ||  count ( $prop_value ) < 1 )
                        return $found_local_products;
                    
                    global $blog_id, $interface_messages;
                        
                    foreach ( $prop_value   as  $product_id )
                        {
                            $local_product  =   $this->get_product_synchronized_at_shop( $product_id, $origin_product_blog_ID, $blog_id );
                            if ( $local_product !== FALSE )
                                $found_local_products[ $product_id ]    =   $local_product;
                                else
                                {
                                    if ( array_search ( $product_id, $this->_triggered_messages_for_id )    === FALSE ) 
                                        {
                                            $this->_triggered_messages_for_id[] =   $product_id;
                                            
                                            switch_to_blog ( $origin_product_blog_ID );
                                            $product_post   =   get_post ( $product_id );
                                            restore_current_blog();
                                            
                                            $interface_messages[]   =   array ( 
                                                                'type'      =>  'error',
                                                                'message'   =>  __( 'Unable to set cross sell id for product ', 'woo-global-cart') . '<b>' . $product_post->post_title . ' ( ID '. $product_id . ' ).</b> ' . __( 'Make sure the product is synchronised at the child shop, then Update.', 'woo-global-cart') 
                                                                );   
                                        }
                                }
                        }
                    
                    return $found_local_products;
                }
                
            public function synchronize_upsell_ids( $prop_value, $origin_product_blog_ID )
                {
                    
                    $found_local_products   =   array();
                
                    if ( ! is_array ( $prop_value ) ||  count ( $prop_value ) < 1 )
                        return $found_local_products;
                    
                    global $blog_id, $interface_messages;
                        
                    foreach ( $prop_value   as  $product_id )
                        {
                            $local_product  =   $this->get_product_synchronized_at_shop( $product_id, $origin_product_blog_ID, $blog_id );
                            if ( $local_product !== FALSE )
                                $found_local_products[ $product_id ]    =   $local_product;
                                else
                                {
                                    if ( array_search ( $product_id, $this->_triggered_messages_for_id )    === FALSE ) 
                                        {
                                            $this->_triggered_messages_for_id[] =   $product_id;
                                            
                                            switch_to_blog ( $origin_product_blog_ID );
                                            $product_post   =   get_post ( $product_id );
                                            restore_current_blog();
                                            
                                            $interface_messages[]   =   array ( 
                                                                'type'      =>  'error',
                                                                'message'   =>  __( 'Unable to set upsell id for product ', 'woo-global-cart') . '<b>' . $product_post->post_title . ' ( ID '. $product_id . ' ).</b> ' . __( 'Make sure the product is synchronised at the child shop, then Update.', 'woo-global-cart') 
                                                                );
                                        }
                                }
                        }
                    
                    return $found_local_products;
                }
                
                
            function clean_internal_messages()
                {
                    $this->_triggered_messages_for_id   =   array();
                }    
             
            
        }
        
    $WooGC_PS   =   new WooGC_PS();
    $WooGC_PS->init();
        
?>