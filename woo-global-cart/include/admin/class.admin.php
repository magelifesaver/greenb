<?php

    use Automattic\WooCommerce\Utilities\ArrayUtil;

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Admin 
        {
            var $WooGC;
            
            function __construct()
                {
                    
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                    
                    add_action( 'init',                                 array( $this, 'on_init'),999 );
                    
                    //Return Network Products when creating an Order through admin
                    add_filter( 'woocommerce_json_search_found_products' ,  array ( $this, 'woocommerce_json_search_found_products' ) );
                    add_action( 'wp_ajax_woocommerce_add_order_item',       array( $this, 'wp_ajax_woocommerce_add_order_item' ) );
                                        
                }
                       
            
            function on_init()
                {
                    
                    //backward compatibility with 3.0 and down
                    global $woocommerce;
                    if( version_compare( $woocommerce->version, '3.0', "<" ) ) 
                        {
                            $this->WooGC->functions->remove_class_action('manage_shop_order_posts_custom_column', 'WC_Admin_Post_Types', 'render_shop_order_columns', 2);
        
                            include_once ( WOOGC_PATH . '/include/admin/class-woogc-admin-post-types.php');
                            $WooGC_Admin_Post_Types =   $this->WooGC->functions->createInstanceWithoutConstructor( 'WooGC_Admin_Post_Types' );
                            add_action      ( 'manage_shop_order_posts_custom_column', array( $WooGC_Admin_Post_Types, 'render_shop_order_columns' ), 2 );        
                        }
                    
                }
                
                
            function woocommerce_json_search_found_products ( $products )
                {
                    global $blog_id;
                    
                    $blog_details   =   get_blog_details( $blog_id );
                    
                    //adjust the indexesof $product array to include a .blog_id
                    
                    $processed_products_array = [];
                    foreach ( $products as $key => $value) 
                        {
                            $processed_products_array [ $key . '.' . $blog_id ] = $blog_details->blogname . ' - ' . $value;
                        }
                    
                    $options    =   $this->WooGC->functions->get_options();
                            
                    $sites      =   $this->WooGC->functions->get_gc_sites( TRUE );
                    
                    $sites_ids  =   array();
                    foreach( $sites  as  $site )
                        {
                            if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                continue;
                            
                            if ( $site->blog_id   ==  $blog_id )
                                continue;
                                
                            //check if the globalcart is disabled for site
                            if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) === FALSE )
                                $sites_ids[]    =   $site->blog_id;   
                        }
                        
                    if ( count ( $sites_ids ) < 1 )
                        return $processed_products_array;

                    $term = (string) wc_clean( wp_unslash( $_GET['term'] ) );

                    if ( ! empty( $_GET['limit'] ) ) {
                        $limit = absint( $_GET['limit'] );
                    } else {
                        $limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
                    }

                    $include_ids = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['include'] ) ) : array();
                    $exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : array();

                    $exclude_types = array();
                    if ( ! empty( $_GET['exclude_type'] ) ) {
                        // Support both comma-delimited and array format inputs.
                        $exclude_types = wp_unslash( $_GET['exclude_type'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        if ( ! is_array( $exclude_types ) ) {
                            $exclude_types = explode( ',', $exclude_types );
                        }

                        // Sanitize the excluded types against valid product types.
                        foreach ( $exclude_types as &$exclude_type ) {
                            $exclude_type = strtolower( trim( $exclude_type ) );
                        }
                        $exclude_types = array_intersect(
                            array_merge( array( 'variation' ), array_keys( wc_get_product_types() ) ),
                            $exclude_types
                        );
                    }    
                    
                    
                    foreach ( $sites_ids    as  $site_id )
                        {
                                   
                            switch_to_blog( $site_id );
                            
                            $blog_details   =   get_blog_details( $site_id );
                            
                            $data_store = WC_Data_Store::load( 'product' );
                            $ids        = $data_store->search_products( $term, '', false, false, $limit, $include_ids, $exclude_ids );

                            foreach ( $ids as $id ) 
                                {
                                    $product_object = wc_get_product( $id );

                                    if ( ! wc_products_array_filter_readable( $product_object ) ) {
                                        continue;
                                    }

                                    $formatted_name = $product_object->get_formatted_name();
                                    $managing_stock = $product_object->managing_stock();

                                    if ( in_array( $product_object->get_type(), $exclude_types, true ) ) {
                                        continue;
                                    }

                                    if ( $managing_stock && ! empty( $_GET['display_stock'] ) ) {
                                        $stock_amount = $product_object->get_stock_quantity();
                                        /* Translators: %d stock amount */
                                        $formatted_name .= ' &ndash; ' . sprintf( __( 'Stock: %d', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product_object ) );
                                    }

                                    $processed_products_array[ $product_object->get_id() . '.' . $blog_id ] = $blog_details->blogname . ' - ' . rawurldecode( wp_strip_all_tags( $formatted_name ) );
                                }
                            
                            restore_current_blog();
                        }
                    
                    asort ( $processed_products_array );
                        
                    return $processed_products_array;
                    
                }
                
                
            function wp_ajax_woocommerce_add_order_item()
                {
                    check_ajax_referer( 'order-item', 'security' );

                    if ( ! current_user_can( 'edit_shop_orders' ) ) {
                        wp_die( -1 );
                    }

                    if ( ! isset( $_POST['order_id'] ) ) {
                        throw new Exception( __( 'Invalid order', 'woocommerce' ) );
                    }
                    $order_id = absint( wp_unslash( $_POST['order_id'] ) );

                    // If we passed through items it means we need to save first before adding a new one.
                    $items = ( ! empty( $_POST['items'] ) ) ? wp_unslash( $_POST['items'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                    $items_to_add = isset( $_POST['data'] ) ? array_filter( wp_unslash( (array) $_POST['data'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                    try {
                        $response = self::maybe_add_order_item( $order_id, $items, $items_to_add );
                        wp_send_json_success( $response );
                    } catch ( Exception $e ) {
                        wp_send_json_error( array( 'error' => $e->getMessage() ) );
                    }
                }
                
            /**
             * Add order item via AJAX. This is refactored for better unit testing.
             *
             * @param int          $order_id     ID of order to add items to.
             * @param string|array $items        Existing items in order. Empty string if no items to add.
             * @param array        $items_to_add Array of items to add.
             *
             * @return array     Fragments to render and notes HTML.
             * @throws Exception When unable to add item.
             */
            private static function maybe_add_order_item( $order_id, $items, $items_to_add ) 
                {
                    try {
                        $order = wc_get_order( $order_id );

                        if ( ! $order ) {
                            throw new Exception( __( 'Invalid order', 'woocommerce' ) );
                        }

                        if ( ! empty( $items ) ) {
                            $save_items = array();
                            parse_str( $items, $save_items );
                            wc_save_order_items( $order->get_id(), $save_items );
                        }

                        // Add items to order.
                        $order_notes = array();
                        $added_items = array();

                        foreach ( $items_to_add as $item ) {
                            if ( ! isset( $item['id'], $item['qty'] ) || empty( $item['id'] ) ) {
                                continue;
                            }
                            
                            $product_id_blog    =   explode ( '.',  $item['id'] );
                            $product_id     = absint( $product_id_blog[0] );
                            $product_blog_id = absint( $product_id_blog[1] );
                            
                            $qty        = wc_stock_amount( $item['qty'] );
                            
                            switch_to_blog( $product_blog_id );
                            
                            $product    = wc_get_product( $product_id );
                            $product->update_meta_data ( 'blog_id', $product_blog_id );
                            
                            restore_current_blog();

                            if ( ! $product ) {
                                throw new Exception( __( 'Invalid product ID', 'woocommerce' ) . ' ' . $product_id );
                            }
                            if ( 'variable' === $product->get_type() ) {
                                /* translators: %s product name */
                                throw new Exception( sprintf( __( '%s is a variable product parent and cannot be added.', 'woocommerce' ), $product->get_name() ) );
                            }
                            $validation_error = new WP_Error();
                            $validation_error = apply_filters( 'woocommerce_ajax_add_order_item_validation', $validation_error, $product, $order, $qty );

                            if ( $validation_error->get_error_code() ) {
                                /* translators: %s: error message */
                                throw new Exception( sprintf( __( 'Error: %s', 'woocommerce' ), $validation_error->get_error_message() ) );
                            }
                            
                            
                            $args   =   array( 'order' => $order );
                            $order = ArrayUtil::get_value_or_default( $args, 'order' );
                            $total = wc_get_price_excluding_tax(
                                $product,
                                array(
                                    'qty'   => $qty,
                                    'order' => $order,
                                )
                            );

                            $default_args = array(
                                'name'         => $product->get_name(),
                                'tax_class'    => $product->get_tax_class(),
                                'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
                                'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
                                'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
                                'subtotal'     => $total,
                                'total'        => $total,
                                'quantity'     => $qty,
                            );
                     

                            $args = wp_parse_args( $args, $default_args );

                            // BW compatibility with old args.
                            if ( isset( $args['totals'] ) ) {
                                foreach ( $args['totals'] as $key => $value ) {
                                    if ( 'tax' === $key ) {
                                        $args['total_tax'] = $value;
                                    } elseif ( 'tax_data' === $key ) {
                                        $args['taxes'] = $value;
                                    } else {
                                        $args[ $key ] = $value;
                                    }
                                }
                            }

                            $item = new WooGC_WC_Order_Item_Product();
                            $item->add_meta_data( 'blog_id', $product_blog_id );
                            $item->set_props( $args );
                            $item->set_backorder_meta();
                            $item->set_order_id( $order->get_id() );
                            $item->save();
                            
                            $order->add_item( $item );
                            wc_do_deprecated_action( 'woocommerce_order_add_product', array( $order->get_id(), $item->get_id(), $product, $qty, $args ), '3.0', 'woocommerce_new_order_item action instead' );
                            delete_transient( 'wc_order_' . $order->get_id() . '_needs_processing' );

                            $item_id    =   $item->get_id();

                            $item                    = apply_filters( 'woocommerce_ajax_order_item', $order->get_item( $item_id ), $item_id, $order, $product );
                            $added_items[ $item_id ] = $item;
                            $order_notes[ $item_id ] = $product->get_formatted_name();

                            // We do not perform any stock operations here because they will be handled when order is moved to a status where stock operations are applied (like processing, completed etc).

                            do_action( 'woocommerce_ajax_add_order_item_meta', $item_id, $item, $order );
                        }

                        /* translators: %s item name. */
                        $order->add_order_note( sprintf( __( 'Added line items: %s', 'woocommerce' ), implode( ', ', $order_notes ) ), false, true );

                        do_action( 'woocommerce_ajax_order_items_added', $added_items, $order );

                        $data = get_post_meta( $order_id );

                        // Get HTML to return.
                        ob_start();
                        include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-order-items.php';
                        $items_html = ob_get_clean();

                        ob_start();
                        $notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
                        include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-order-notes.php';
                        $notes_html = ob_get_clean();

                        return array(
                            'html'       => $items_html,
                            'notes_html' => $notes_html,
                        );
                    } catch ( Exception $e ) {
                        throw $e; // Forward exception to caller.
                    }
                }
                
        }

    new WooGC_Admin();

?>