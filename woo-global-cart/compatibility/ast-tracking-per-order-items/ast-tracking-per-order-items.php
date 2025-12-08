<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Tracking Per Item Add-on
    * Since Version:        1.3.3
    */

    class WooGC_compatibility_ast_tracking_per_order_items
        {
                
            public function __construct( ) 
                {
                    global $WooGC;
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_before_order_itemmeta', 'ast_woo_advanced_shipment_tracking_by_products', 'before_order_itemmeta' );
                        
                    add_action( 'woocommerce_before_order_itemmeta', array( $this, 'before_order_itemmeta'), 10, 3 );
                      
                }
                
    
            /**     
             * Function for show tracking info before order meta
             */
            public function before_order_itemmeta( $item_id, $item, $_product )
                {            
                    $order_id = $item->get_order_id();        
                    
                    if(!$_product){
                        return;
                    }
                    
                    $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();        
                    
                    $wast = WC_Advanced_Shipment_Tracking_Actions::get_instance();                
                    $tracking_items = $wast->get_tracking_items( $order_id );
                            
                    $show_products = array();
                    $product_list = array();
                    $show = false;
                    
                    global $blog_id;
                    
                    $restore_to =   $blog_id;
                    restore_current_blog();
                    
                    $order = wc_get_order( $order_id );
                    $items = $order->get_items();        
                    
                    switch_to_blog( $restore_to );
                    
                    foreach ( $items as $item ) {
                        
                        switch_to_blog( $item->get_meta( 'blog_id' ) );
                                
                        $products[] = (object) array (
                            'product' => $item->get_product_id(),
                            'qty' => $item->get_quantity(),
                        );
                        
                        restore_current_blog();                    
                    }                
                    
                    foreach($tracking_items as $t_item){
                        
                        if(isset($t_item['products_list'])){
                            $product_list[$t_item['tracking_id']] = $t_item['products_list'];
                        }
                    }                                
                    
                    foreach($tracking_items as $t_item){
                        if(isset($product_list[$t_item['tracking_id']])){
                            $array_check = ($product_list[$t_item['tracking_id']] == $products);                        
                            if(empty($t_item['products_list']) || $array_check == 1){
                                $show_products[$t_item['tracking_id']] = 0;
                            } else{
                                $show_products[$t_item['tracking_id']] = 1;
                            } 
                        }
                    }
                    
                    foreach($show_products as $key => $value){
                        if($value == 1){
                            $show = true;
                            break;
                        }
                    }
                    
                    if(!$show){
                        return;
                    }    
                    ?>
                    <style>
                    .before-meta-tracking-content {
                        background: #efefef none repeat scroll 0 0;
                        padding: 10px;
                        position: relative;
                        margin: 5px 0;
                    }
                    </style>    
                    <?php
                    echo '<div id="tracking-items">';
                        foreach($tracking_items as $tracking_item){
                            $formatted = $wast->get_formatted_tracking_item( $order_id, $tracking_item );                 
                            if(isset($tracking_item['products_list'])){
                                if(in_array($product_id, array_column($tracking_item['products_list'], 'product'))) { ?>
                                    <div class="before-meta-tracking-content">
                                        <div class="tracking-content-div">
                                            <strong><?php echo esc_html( $formatted['formatted_tracking_provider'] ); ?></strong>                        
                                            <?php if ( strlen( $formatted['formatted_tracking_link'] ) > 0 ) { ?>
                                                - <?php 
                                                $url = str_replace('%number%',$tracking_item['tracking_number'],$formatted['formatted_tracking_link']);
                                                echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Track Shipment', 'woo-advanced-shipment-tracking' ) ) . '">' . __( $tracking_item['tracking_number'] ) . '</a>', esc_url( $url ) ); ?>
                                            <?php } else{ ?>
                                                <span> - <?php echo $tracking_item['tracking_number']; ?></span>
                                            <?php } ?>
                                        </div>                    
                                        <?php 
                                        foreach($tracking_item['products_list'] as $products){
                                            if($products->product == $product_id){    
                                                $product = wc_get_product( $products->product );
                                                if($product){
                                                    $product_name = $product->get_name();
                                                    echo '<span class="tracking_product_list">'.$product_name.' x '.$products->qty.'</span>';
                                                }
                                            }
                                        } ?>
                                    </div>        
                                    <?php
                                } 
                            }    
                        }
                    echo '</div>';        
                }
             
               
        }

        
    new WooGC_compatibility_ast_tracking_per_order_items();



?>