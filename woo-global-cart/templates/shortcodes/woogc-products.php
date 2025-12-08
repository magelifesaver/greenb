<?php


        defined( 'ABSPATH' ) || exit;

        if ( count  ( $products ) < 1 )
            return;
        
        global $product, $post;
        
        $columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
            
        ?>
        <div class="woocommerce">
            <ul id="globl_products" class="products columns-<?php echo $columns ?>">
            <?php
            foreach ( $products as $post )
                {
                    
                    switch_to_blog( $post->blog_id );    
                                    
                    $product    =   wc_get_product ( $post );
                    $product->_context  =   'woogc_shortcode';
                    
                    wc_get_template_part( 'content', 'product' );
                    
                    restore_current_blog();
                    
                }
        
            ?>
            </ul>
        
            <?php   
                
                if ( $shortcode_atts['_found_posts'] >  $shortcode_atts['posts_per_page'] &&    $shortcode_atts['disable_pagination']   ===   FALSE )
                    {
                        wc_get_template( 'shortcodes/woogc-products-pagination.php',   array(
                                                                                            'products'          =>  $products,
                                                                                            'shortcode_atts'    =>  $shortcode_atts
                                                                                        )
                            ); 
                    }
                    
            ?>
            
        </div>
        <?php

        wp_reset_postdata()
?>