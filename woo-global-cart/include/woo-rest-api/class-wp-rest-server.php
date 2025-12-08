<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_WP_REST_Server 
        {
            
            function __construct()   
                {
                    add_filter( 'rest_request_after_callbacks',                       array( $this, 'rest_request_after_callbacks'), -1, 3 );
                    add_filter( 'woocommerce_hydration_request_after_callbacks',      array( $this, 'rest_request_after_callbacks'), -1, 3 );
                }
                
            
            function rest_request_after_callbacks ( $response, $handler, $request )
                {
                    if ( ! in_array ( $request->get_route(), array ( '/wc/store/v1/cart', '/wc/store/v1/cart/update-customer', '/wc/store/v1/cart/update-item', '/wc/store/v1/cart/remove-item' ) ) )
                        return $response;
                    
                    //adjust the items images
                    $cart_content   =   WC()->cart->get_cart();
                    if ( count ( $cart_content ) < 1 )
                        return $response;   
                    
                    if ( count ( $response->data['items' ] )    >   0 )
                        {
                            foreach ( $response->data['items' ] as  $key    =>  $item_data )
                                {
                                    if ( ! isset ( $cart_content[ $item_data['key'] ] ) )
                                        continue;
                                    
                                    if ( isset ( $cart_content[ $item_data['key'] ]['blog_id'] ) )
                                        switch_to_blog( $cart_content[ $item_data['key'] ]['blog_id'] );
                                    
                                    $product    =   $cart_content[ $item_data['key'] ]['data'];
                                    
                                    $images =   array();
                                       
                                    $attachment_ids = array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() );
                                    
                                    if ( count ( $attachment_ids ) < 1 )
                                        {
                                            restore_current_blog();
                                            continue;
                                        }
                                        
                                    foreach ( $attachment_ids   as  $attachment_id )
                                        {
                                            $attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
                                            
                                            if ( ! $attachment )
                                                continue;

                                            $thumbnail = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

                                            $images[]   = (object) [
                                                'id'        => (int) $attachment_id,
                                                'src'       => current( $attachment ),
                                                'thumbnail' => current( $thumbnail ),
                                                'srcset'    => (string) wp_get_attachment_image_srcset( $attachment_id, 'full' ),
                                                'sizes'     => (string) wp_get_attachment_image_sizes( $attachment_id, 'full' ),
                                                'name'      => get_the_title( $attachment_id ),
                                                'alt'       => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
                                            ];   
                                        }
                                    
                                    if ( isset ( $cart_content[ $item_data['key'] ]['blog_id'] ) )
                                        restore_current_blog();
                                        
                                    if ( count ( $images ) > 0 )
                                        $response->data['items' ][ $key ]['images']   =   $images;
                                    
                                }
                        }
                    
                        
                    return $response;
                } 
            
        }


    new WooGC_WP_REST_Server();
        
?>