<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce - PDF Vouchers
    * Since Version:        4.3.6
    */

    class WooGC_woocommerce_pdf_vouchers
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter('woocommerce_customer_get_downloadable_products',        array( $this,   'woocommerce_customer_get_downloadable_products'),  1000 );
                      
                }
                
            function woocommerce_customer_get_downloadable_products( $downloads = array() )
                {
                    global $WooGC, $blog_id; 
                    $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                    foreach($sites  as  $site)
                        {
                            if ( $blog_id == $site->blog_id )
                                continue;
                                
                            switch_to_blog( $site->blog_id );
                            
                            $downloads  =   woo_vou_my_pdf_vouchers_download_link( $downloads );
                            
                            restore_current_blog();
                        }
                
                    return $downloads;
                } 
            
        }

        
    new WooGC_woocommerce_pdf_vouchers();

?>