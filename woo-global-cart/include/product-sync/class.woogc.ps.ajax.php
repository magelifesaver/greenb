<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS_AJAX
        {
            
            private $PS;
             
            /**
            * On construct
            * 
            */
            function __construct()
                {
                    $this->PS    =   new WooGC_PS();
                    
                    add_action( 'woocommerce_ajax_save_product_variations',     array( $this, 'woocommerce_ajax_save_product_variations' ) );
                } 
            
            
            function woocommerce_product_quick_edit_save( $product )
                {
                    
                    if ( ! $this->PS->is_main_product( $product->get_ID() ) )
                        return;
                    
                    $main_product  =   new WooGC_PS_main_product( $product->get_ID() );
                    
                    $_woogc_ps_sync_to  =   $main_product->get_children();
                    if ( count ( $_woogc_ps_sync_to ) < 1 )
                        return;
                                        
                    $args   =   array  (
                                            'maintain_child'        =>  $main_product->get_maintained(),
                                            'maintain_categories'   =>  $main_product->get_maintained_categories(),
                                            'maintain_stock'        =>  $main_product->get_maintained_stock(),
                                            );
                    $interface_messages     =   $this->PS->synchronize_to( $product->get_ID(), $_woogc_ps_sync_to, $args );
                    
                }
                
                
            function woocommerce_ajax_save_product_variations( $product_id )
                {
                    if ( ! $this->PS->is_main_product( $product_id ) )
                        return;
                    
                    $main_product  =   new WooGC_PS_main_product( $product_id );  
                    
                    $_woogc_ps_sync_to  =   $main_product->get_children();
                    if ( count ( $_woogc_ps_sync_to ) < 1 )
                        return;
                                        
                    $args   =   array  (
                                            'maintain_child'        =>  $main_product->get_maintained(),
                                            'maintain_categories'   =>  $main_product->get_maintained_categories(),
                                            'maintain_stock'        =>  $main_product->get_maintained_stock(),
                                            );
                    $interface_messages     =   $this->PS->synchronize_to( $product_id, $_woogc_ps_sync_to, $args );
                    
                    foreach ( $interface_messages   as  $interface_message )
                        {
                            echo '<div class="'. $interface_message['type'] .' notice is-dismissible">';
                            echo '<p>' . wp_kses_post( $interface_message['message'] ) . '</p>';
                            echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'woo-global-cart' ) . '</span></button>';
                            echo '</div>';   
                        }
                }
            
        }
        
        
    new WooGC_PS_AJAX();
        
?>