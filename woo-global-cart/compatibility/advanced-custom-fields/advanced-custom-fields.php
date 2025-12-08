<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Advanced Custom Fields  and Advanced Custom Fields PRO
    * Since:                6.0.7
    */

    class WooGC_acf
        {
            var $PS;
                              
            function __construct( $dependencies = array() ) 
                {
                    add_filter ( 'woogc/ps/synchronize_product/child_product', array ( $this, 'child_product_meta'), 10, 3 );                      
                }
                
            function child_product_meta( $child_product, $main_product_data, $origin_product_blog_ID )
                {
                    if ( ! isset ( $main_product_data['meta_data'] )    ||  count ( $main_product_data['meta_data'] ) < 1 )   
                        return $child_product;
                        
                    $this->PS    =   new WooGC_PS();
                    
                    global $blog_id, $wpdb;
                    
                    $child_blog_id  =   $blog_id;                    
                    $ACF_fields     =   array();
                    
                    switch_to_blog( $origin_product_blog_ID );
                    
                    //create a map of the fields
                    foreach ( $main_product_data['meta_data']    as $meta )
                        {
                            $meta_data  =   $meta->get_data();
                            
                            if ( ! is_string( $meta_data['value'] ) )
                                continue;
                                
                            if ( strpos( $meta_data['value'], 'field_' )    === 0   )
                                $ACF_fields[ $meta_data['value'] ]   =   ltrim( $meta_data['key'], '_' );
                        }
                    
                    if ( count ( $ACF_fields ) > 0 )
                        {
                            foreach ( $ACF_fields   as  $field_key  =>  $meta_name )
                                {
                                    $mysql_query    =   $wpdb->prepare ( "SELECT ID, post_content FROM " . $wpdb->posts . "
                                                                            WHERE post_name = %s AND post_type = 'acf-field'", $field_key );
                                    $acf_object     =   $wpdb->get_row( $mysql_query );
 
                                    if ( ! is_object ( $acf_object ) || ! isset ( $acf_object->post_content )   )
                                        continue;
                                        
                                    $field_settings =   maybe_unserialize ( $acf_object->post_content );
                                    
                                    if ( ! in_array ( $field_settings['type'], array ( 'image', 'gallery', 'post_object' ) ) )
                                        continue;
                                        
                                    switch ( $field_settings['type'] )
                                        {
                                            case 'image'    :
                                                                $origin_image_id   =   get_post_meta( $main_product_data['id'], $meta_name, TRUE );
                                                                if ( empty ( $origin_image_id ) )
                                                                    continue 2;
                                                                
                                                                switch_to_blog ( $child_blog_id ) ;
                                                                $image_id   =   WooGC_PS::synchronize_image( $origin_image_id, $origin_product_blog_ID );
                                                                if ( $image_id  >   0   )
                                                                    $child_product->update_meta_data( $meta_name, $image_id );
                                                                            
                                                                restore_current_blog();
                                                                break;   
                                            
                                            case 'gallery'  :
                                                                $origin_gallery   =   get_post_meta( $main_product_data['id'], $meta_name, TRUE );
                                                                if ( ! is_array ( $origin_gallery )     ||  count ( $origin_gallery ) <  1 )
                                                                    continue 2;
                                                                    
                                                                switch_to_blog ( $child_blog_id ) ;
                                                                
                                                                $gallery_ids    =   array();
                                                                
                                                                foreach ( $origin_gallery   as  $origin_image_id )
                                                                    {
                                                                        if ( empty ( $origin_image_id ) )
                                                                            continue 3;
                                                                            
                                                                        $image_id   =   WooGC_PS::synchronize_image( $origin_image_id, $origin_product_blog_ID );
                                                                        if ( $image_id  >   0   )
                                                                            $gallery_ids[]  =   $image_id;
                                                                    }
                                                                    
                                                                $child_product->update_meta_data( $meta_name, $gallery_ids );
                                                                            
                                                                restore_current_blog();
                                                                
                                                                
                                                                break;
                                                                
                                            case 'post_object'  :
                                                                $origin_posts_ids   =   get_post_meta( $main_product_data['id'], $meta_name, TRUE );
                                                                if ( ! is_array ( $origin_posts_ids )     ||  count ( $origin_posts_ids ) <  1 )
                                                                    continue 2;
                                                                
                                                                $origin_ids =   array();
                                                                
                                                                foreach ( $origin_posts_ids   as  $origin_post_id )
                                                                    {
                                                                        $origin_product_pid     =   '';
                                                                        $origin_product_bid     =   '';
                                                                        
                                                                        if ( $this->PS->is_child_product( $origin_post_id ) )
                                                                            {
                                                                                $origin_child_product   =   new WooGC_PS_child_product( $origin_post_id );
                                                                                $origin_product_pid     =   $origin_child_product->get_main_id();
                                                                                $origin_product_bid     =   $origin_child_product->get_main_shop_id();
                                                                            }
                                                                        else if ( $this->PS->is_main_product( $origin_post_id ) )
                                                                            {
                                                                                $origin_product_pid     =   $origin_post_id;
                                                                                $origin_product_bid     =   $origin_product_blog_ID;
                                                                            }
                                                                        else
                                                                            continue;    
                                                                            
                                                                        //check if the product is synchronized at the $child_blog_id
                                                                        switch_to_blog( $origin_product_bid );
                                                                        
                                                                        if ( $origin_product_bid    ==  $child_blog_id )
                                                                            $origin_main_product_child_at_shop   =   $origin_product_pid;    
                                                                            else
                                                                            {
                                                                                $origin_main_product                =   new WooGC_PS_main_product( $origin_product_pid );
                                                                                $origin_main_product_child_at_shop  =   $origin_main_product->get_child_at_shop( $child_blog_id );
                                                                            }
                                                                        
                                                                        if ( ! empty ( $origin_main_product_child_at_shop ) )
                                                                            $origin_ids[]   =   $origin_main_product_child_at_shop;
                                                                        
                                                                        restore_current_blog();
                                                                    }
                                                                
                                                                switch_to_blog ( $child_blog_id ) ;    
                                                                $child_product->update_meta_data( $meta_name, $origin_ids );
                                                                restore_current_blog();
                                                                
                                                                break;
                                            
                                        }
                                }
                        }
                    
                        
                    restore_current_blog();
                    
                    return $child_product;
                    
                }         
            
        }

        
    new WooGC_acf();

?>