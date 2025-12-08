<?php

    defined( 'ABSPATH' ) || exit;

    /**
     * WOOGC Coupons Engine class.
     *
     * @since 3.2.0
     */
    class WOOGC_Coupons_Engine 
        {

            var $WooGC;
                                  
            function __construct()
                {
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                    
                    $this->_init();
                }
                
                
                
            function _init()
                {
                    
                    add_filter( 'manage_shop_coupon_posts_columns' ,    array( $this, 'define_columns' ), 99 );
                    add_filter( 'manage_posts_custom_column' ,          array( $this, 'manage_posts_custom_column' ), 99, 2 );
                    
                    add_action( 'woocommerce_coupon_options',           array( $this, 'woocommerce_coupon_options' ), 99, 2 );
                    add_action( 'woocommerce_coupon_options_save',      array( $this, 'woocommerce_coupon_options_save' ), 99, 2 );
                    
                    
                    add_action( 'woocommerce_order_status_pending',     array( $this, 'wc_update_coupon_usage_counts' ), 99 );
                    add_action( 'woocommerce_order_status_completed',   array( $this, 'wc_update_coupon_usage_counts' ) );
                    add_action( 'woocommerce_order_status_processing',  array( $this, 'wc_update_coupon_usage_counts' ) );
                    add_action( 'woocommerce_order_status_on-hold',     array( $this, 'wc_update_coupon_usage_counts' ) );
                    add_action( 'woocommerce_order_status_cancelled',   array( $this, 'wc_update_coupon_usage_counts' ) );
                    
                }
                
                
                
            function define_columns( $columns )
                {
                    
                    $columns['global_coupon']  =   __( 'Global Coupon', 'woo-global-cart' );
                    
                    return $columns;   
                    
                }
            
            
            
            function manage_posts_custom_column ( $column_name, $post_ID )
                {
                    
                    if ( $column_name !=    'global_coupon' )
                        return;
                    
                    $global_coupon  =   get_post_meta ( $post_ID, 'global_coupon', TRUE );
                    if ( empty ( $global_coupon ) ||    $global_coupon  ==  'no' )
                        $global_coupon  =   __( 'No', 'woo-global-cart' );
                        else
                        $global_coupon  =   __( 'Yes', 'woo-global-cart' );
                        
                    echo $global_coupon;
                    
                }
                
                
                
            function woocommerce_coupon_options ( $coupon_ID, $coupon )
                {
                    
                    $global_coupon  =   get_post_meta ( $coupon_ID, 'global_coupon', TRUE );  
                    
                    $global_coupon  =   empty ( $global_coupon )    ?   'no'    :   $global_coupon;
                    
                    ?>
                    
                    <style>
                        p.form-field.global_coupon_field {background-color: #cc99c2;}
                        p.form-field.global_coupon_field label, p.form-field.global_coupon_field .woocommerce-help-tip {color:#FFF}
                    </style>
                    
                    <?php
                       
                    woocommerce_wp_select(
                                                array(
                                                    'id'                    => 'global_coupon',
                                                    'label'                 => __( 'Global Coupon', 'woo-global-cart' ),
                                                    'options'               => array ( 
                                                                                'no'    =>  __( 'No', 'woo-global-cart' ),
                                                                                'yes'   =>  __( 'Yes', 'woo-global-cart' )  
                                                                                ),
                                                    'value'                 => $global_coupon,
                                                    'description'           => __( 'If set to Yes, the coupon will be created and updated on all other WooCommerce shops.', 'woo-global-cart' ),
                                                    'desc_tip'              => true,
                                                )
                                            ); 
                                            
                    ?>
                    
                    <script type='text/javascript'>
                    /* <![CDATA[ */
                        jQuery('#global_coupon').on('change', function() {
                            var el_value    =   jQuery(this).val();
                            if ( el_value == 'yes' )
                                jQuery('#global_coupon_unlink').parent('.global_coupon_unlink_field').hide();
                                else
                                jQuery('#global_coupon_unlink').parent('.global_coupon_unlink_field').show();
                        })
                    /* ]]> */
                    </script>
                    
                    <?php
                    
                    woocommerce_wp_select(
                                                array(
                                                    'id'                    => 'global_coupon_unlink',
                                                    'label'                 => __( 'Global Coupon Unlink', 'woo-global-cart' ),
                                                    'options'               => array ( 
                                                                                'no'    =>  __( 'No', 'woo-global-cart' ),
                                                                                'yes'   =>  __( 'Yes', 'woo-global-cart' )  
                                                                                ),
                                                    'value'                 => 'no',
                                                    'description'           => __( 'If set to Yes, the coupon will stay on this shop but removed from all others.  If set to No, the coupon will be preserved at all shops but not maintained globally.', 'woo-global-cart' ),
                                                    'desc_tip'              => true,
                                                )
                                            );
                                            
                    ?>
                    <script type='text/javascript'>
                    /* <![CDATA[ */
                        var el_visibility   =   jQuery('#global_coupon_unlink').css('display');
                        if ( el_visibility )
                            jQuery('#global_coupon_unlink').parent('.global_coupon_unlink_field').hide();
                    /* ]]> */
                    </script>
                    <?php                        
                    
                }
                
                
            function woocommerce_coupon_options_save( $coupon_ID, $coupon )
                {
                    global $post;
                    
                    //previous setting
                    $previous_option_global_coupon  =   get_post_meta ( $coupon_ID, 'global_coupon', TRUE );
                    
                    $global_coupon      =   wc_clean( $_POST['global_coupon'] );
                    
                    update_post_meta ( $coupon_ID, 'global_coupon',  $global_coupon );
                    
                    //ensure there is a hash for the coupon to estabilish a global syncronisation, when changed to Yes/No
                    $coupon_hash    =   get_post_meta ( $coupon_ID, '_global_coupon_hash',  TRUE );
                    if ( empty ( $coupon_hash ) )
                        {
                            $coupon_hash    =   md5 ( time () . $coupon_ID );
                            update_post_meta ( $coupon_ID, '_global_coupon_hash',  $coupon_hash );
                        }
                    
                    //check for unlink
                    $global_coupon_unlink      =   wc_clean( $_POST['global_coupon_unlink'] );
                    if ( $previous_option_global_coupon ==  'yes' &&  $global_coupon    ==  'no' )
                        {
                            global $blog_id;
                            
                            $current_site   =   $blog_id;
                                                
                            //$coupon->save();
                            $sites  =   $this->WooGC->functions->get_gc_sites( TRUE, 'global_coupon/save' );        
                            foreach ( $sites    as  $site )
                                {
                                    if ( $site->blog_id ==  $current_site )
                                        continue;
                                    
                                    switch_to_blog( $site->blog_id );   
                                    
                                    $coupon_id  =   '';
                                    
                                    $args = array(
                                                   'post_type'  =>  'shop_coupon',
                                                   'meta_key'   => '_global_coupon_hash',
                                                   'meta_value' =>  $coupon_hash  
                                                );
                                    $founds = new WP_Query($args);
                                    if ( $founds->found_posts > 0 )
                                        {
                                            $founds->the_post();
                                            $coupon_id  =   $founds->post->ID;  
                                        }
                                    
                                    if ( ! empty ( $coupon_id ) )
                                        {
                                            if ( $global_coupon_unlink == 'yes' )
                                                wp_delete_post( $coupon_id, TRUE );
                                                else
                                                update_post_meta ( $coupon_id, 'global_coupon',  'no' );
                                        }
                                    
                                    
                                    restore_current_blog();
                                }   
                            
                        }
                        
                    //syncrnize with other shops
                    if ( $global_coupon    ==  'yes' )
                        {
                            global $blog_id;
                            
                            $current_site   =   $blog_id;
                            
                            $coupon_post_data    =  get_post ( $coupon_ID );
                            
                            $product_categories         = isset( $_POST['product_categories'] ) ? (array) $_POST['product_categories'] : array();
                            $exclude_product_categories = isset( $_POST['exclude_product_categories'] ) ? (array) $_POST['exclude_product_categories'] : array();
                            
                            $coupon_props = array(
                                                    'code'                        => $post->post_title,
                                                    'discount_type'               => wc_clean( $_POST['discount_type'] ),
                                                    'amount'                      => wc_format_decimal( $_POST['coupon_amount'] ),
                                                    'date_expires'                => wc_clean( $_POST['expiry_date'] ),
                                                    'individual_use'              => isset( $_POST['individual_use'] ),
                                                    'product_ids'                 => isset( $_POST['product_ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['product_ids'] ) ) : array(),
                                                    'excluded_product_ids'        => isset( $_POST['exclude_product_ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['exclude_product_ids'] ) ) : array(),
                                                    'usage_limit'                 => absint( $_POST['usage_limit'] ),
                                                    'usage_limit_per_user'        => absint( $_POST['usage_limit_per_user'] ),
                                                    'limit_usage_to_x_items'      => absint( $_POST['limit_usage_to_x_items'] ),
                                                    'free_shipping'               => isset( $_POST['free_shipping'] ),
                                                    'product_categories'          => array_filter( array_map( 'intval', $product_categories ) ),
                                                    'excluded_product_categories' => array_filter( array_map( 'intval', $exclude_product_categories ) ),
                                                    'exclude_sale_items'          => isset( $_POST['exclude_sale_items'] ),
                                                    'minimum_amount'              => wc_format_decimal( $_POST['minimum_amount'] ),
                                                    'maximum_amount'              => wc_format_decimal( $_POST['maximum_amount'] ),
                                                    'email_restrictions'          => array_filter( array_map( 'trim', explode( ',', wc_clean( $_POST['customer_email'] ) ) ) ),
                                                );
                            
                            $coupon_props   =   apply_filters( 'woogc/global_coupons/props', $coupon_props, $coupon_ID );
                            
                            $sites  =   $this->WooGC->functions->get_gc_sites( TRUE, 'global_coupon/save' );        
                            foreach ( $sites    as  $site )
                                {
                                    if ( $site->blog_id ==  $current_site )
                                        continue;
                                    
                                    switch_to_blog( $site->blog_id );   
                                    
                                    $args = array(
                                                   'post_type'  =>  'shop_coupon',
                                                   'meta_key'   => '_global_coupon_hash',
                                                   'meta_value' =>  $coupon_hash  
                                                );
                                    $founds = new WP_Query($args);
                                    if ( $founds->found_posts < 1 )
                                        {
                                            //no coupon found, creat it
                                            $new_coupon_post = (array)$coupon_post_data;
                                            unset ( $new_coupon_post['ID'] );
                                            unset ( $new_coupon_post['guid'] );
                                             
                                            // Insert the post into the database
                                            $coupon_id  =   wp_insert_post( $new_coupon_post );   
                                        }
                                        else
                                        {
                                            $founds->the_post();
                                            $coupon_id  =   $founds->post->ID;
                                            
                                            $new_coupon_post = (array)$coupon_post_data;
                                            $new_coupon_post['ID']  =   $coupon_id;
                                             
                                            // Insert the post into the database
                                            wp_update_post( $new_coupon_post );      
                                        }
                                        
                                    $coupon = new WC_Coupon( $coupon_id );
                                    $coupon->set_props( $coupon_props );
                                    $coupon->save();
                                    
                                    update_post_meta ( $coupon_id, 'global_coupon',  'yes' );
                                    update_post_meta ( $coupon_id, '_global_coupon_hash',  $coupon_hash );
                                    
                                    restore_current_blog();
                                }
                        }
                    
                }
            
            
            
            /**
             * Update used coupon amount for each coupon within an order.
             *
             * @since 3.0.0
             * @param int $order_id Order ID.
             */
            function wc_update_coupon_usage_counts( $order_id ) 
                {
                    $order = wc_get_order( $order_id );

                    if ( ! $order ) {
                        return;
                    }
                    
                    if ( count( $order->get_coupon_codes() ) < 1 ) {
                        return;
                    }

                    $has_recorded = $order->get_data_store()->get_recorded_coupon_usage_counts( $order );

                    if ( $order->has_status( 'cancelled' ) && $has_recorded ) 
                            {
                                $action = 'reduce';
                            } 
                        elseif ( ! $order->has_status( 'cancelled' ) && ! $has_recorded ) 
                            {
                                $action = 'increase';
                            } 
                        elseif ( $order->has_status( 'cancelled' ) ) 
                            {
                                return;
                            } 
                        else {
                            return;
                        }
                        
                    global $blog_id;
                            
                    $current_site   =   $blog_id;

                    foreach ( $order->get_coupon_codes() as $code ) 
                        {
                            if ( ! $code ) {
                                continue;
                            }

                            $coupon  = new WC_Coupon( $code );
                            
                            $coupon_hash    =   get_post_meta ( $coupon->get_ID(), '_global_coupon_hash',  TRUE );
                            if ( empty ( $coupon_hash ) )
                                continue;
                            
                            $used_by = $order->get_user_id();

                            if ( ! $used_by ) {
                                $used_by = $order->get_billing_email();
                            }
                            
                            
                            $sites  =   $this->WooGC->functions->get_gc_sites( TRUE, 'global_coupon/update_coupon_usage' );        
                            foreach ( $sites    as  $site )
                                {
                                    if ( $site->blog_id ==  $current_site )
                                        continue;
                                    
                                    switch_to_blog( $site->blog_id );   
                                    
                                    $args = array(
                                                   'post_type'  =>  'shop_coupon',
                                                   'meta_key'   => '_global_coupon_hash',
                                                   'meta_value' =>  $coupon_hash  
                                                );
                                    $founds = new WP_Query($args);
                                    if ( $founds->found_posts > 0 )
                                        {
                                            $founds->the_post();
                                            $code   =   $founds->post->post_title; 
                                            
                                            $coupon  = new WC_Coupon( $code );
                                            
                                            switch ( $action ) {
                                                case 'reduce':
                                                    $coupon->decrease_usage_count( $used_by );
                                                    break;
                                                case 'increase':
                                                    $coupon->increase_usage_count( $used_by );
                                                    break;
                                            } 
                                        }
                                    
                                    restore_current_blog();
                                }
                                

                            
                        }
                } 
            
        }
        
        
    new WOOGC_Coupons_Engine();
