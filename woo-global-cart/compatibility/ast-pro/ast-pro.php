<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name   :        Advanced Shipment Tracking Pro
    * Since         :        2.1
    */

    class WooGC_ast_pro
        {
            
            function __construct( $dependencies = array() ) 
                {
                    global $WooGC;
                    $WooGC->functions->remove_class_filter( 'woocommerce_before_order_itemmeta', 'AST_Tpi', 'before_order_itemmeta' );
                    
                    add_action( 'woocommerce_before_order_itemmeta', array( $this, 'before_order_itemmeta' ), 10, 3 );
                      
                }
                
      
            /**     
             * Function for show tracking info before order meta
             */
            public function before_order_itemmeta( $item_id, $item, $_product ) {            
                
                if ( !$_product ) {
                    return;
                }    
                
                $order_id = $item->get_order_id();
                
                global $blog_id;
                $_rstore_to_blog    =   $blog_id;
                restore_current_blog();
                
                $order = wc_get_order( $order_id );
                
                switch_to_blog( $_rstore_to_blog );
                
                $item_quantity  = $item->get_quantity();
                
                $ast = AST_Pro_Actions::get_instance();                
                $tracking_items = $ast->get_tracking_items( $order_id );                
                                
                $show = AST_tpi::get_instance()->check_if_tpi_order( $tracking_items, $order );
                
                if ( !$show ) {
                    return;
                }    
                
                $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();                    
                
                echo '<div id="tracking-items">';
                foreach ( $tracking_items as $tracking_item ) {
                    $formatted = $ast->get_formatted_tracking_item( $order_id, $tracking_item );
                    if ( isset( $tracking_item[ 'products_list' ] ) && is_array( $tracking_item[ 'products_list' ] ) ) {                    
                        if ( in_array( $product_id, array_column( $tracking_item[ 'products_list' ], 'product' ) ) ) {
                            foreach ( $tracking_item[ 'products_list' ] as $products ) {
                                
                                if ( isset( $products->item_id ) && $products->item_id == $item_id ) {                        
                                    ?>
                                    <div class="wc-order-item-sku">
                                        <strong><?php esc_html_e( 'Shipped with:', 'ast-pro' ); ?></strong>
                                        <strong><?php echo esc_html( $formatted['formatted_tracking_provider'] ); ?></strong>
                                        <?php if ( strlen( $formatted['ast_tracking_link'] ) > 0 ) { ?>
                                        - 
                                        <?php 
                                        echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Track Shipment', 'ast-pro' ) ) . '">' . esc_html( $tracking_item['tracking_number'] ) . '</a>', esc_url( $formatted['ast_tracking_link'] ) ); 
                                        } else {
                                            ?>
                                            <span> - <?php esc_html_e( $tracking_item['tracking_number'] ); ?></span>
                                        <?php 
                                        } 
                                        echo '<span class="tracking_product_list"> x ' . esc_html( $products->qty ) . '</span>';                                                                    
                                        ?>
                                    </div>
                                    <?php
                                } elseif ( !isset( $products->item_id ) && $products->product == $product_id ) {
                                    echo 'product_id';    
                                    ?>
                                    <div class="wc-order-item-sku">
                                        <strong><?php esc_html_e( 'Shipped with:', 'ast-pro' ); ?></strong>
                                        <strong><?php echo esc_html( $formatted['formatted_tracking_provider'] ); ?></strong>
                                        <?php if ( strlen( $formatted['ast_tracking_link'] ) > 0 ) { ?>
                                        - 
                                        <?php 
                                        echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Track Shipment', 'ast-pro' ) ) . '">' . esc_html( $tracking_item['tracking_number'] ) . '</a>', esc_url( $formatted['ast_tracking_link'] ) ); 
                                        } else {
                                            ?>
                                            <span> - <?php esc_html_e( $tracking_item['tracking_number'] ); ?></span>
                                        <?php 
                                        } 
                                        echo '<span class="tracking_product_list"> x ' . esc_html( $products->qty ) . '</span>';                                                                    
                                        ?>
                                    </div>                                                
                                    <?php
                                }
                            }    
                        } 
                    }    
                }
                echo '</div>';        
            }
            
        }

        
    new WooGC_ast_pro();

?>