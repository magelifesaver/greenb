<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_woocommerce_pdf_invoices_packingslips
        {
           
            function __construct() 
                {

                    global $WooGC;
                    
                    //unregister the hook from original class
                    //$WooGC->functions->remove_class_filter( 'wp_ajax_generate_wpo_wcpdf', 'WPO\IPS\Main', 'generate_document_ajax' );
                    
                    //add_action( 'wp_ajax_generate_wpo_wcpdf', array($this, 'generate_document_ajax' ) );
              
                }
                
                
            public function generate_document_ajax() 
                {
                    
                    // Check the nonce
                    if( empty( $_GET['action'] ) ) {
                        wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips' ) );
                    }
                    
                    if( ! is_user_logged_in() ) {
                        wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips' ) );
                    }

                    // Check if all parameters are set
                    if ( empty( $_GET['document_type'] ) && !empty( $_GET['template_type'] ) ) {
                        $_GET['document_type'] = $_GET['template_type'];
                    }

                    if ( empty( $_GET['order_ids'] ) ) {
                        wp_die( __( "You haven't selected any orders", 'woocommerce-pdf-invoices-packing-slips' ) );
                    }

                    if( empty( $_GET['document_type'] ) ) {
                        wp_die( __( 'Some of the export parameters are missing.', 'woocommerce-pdf-invoices-packing-slips' ) );
                    }

                    // Generate the output
                    $document_type = sanitize_text_field( $_GET['document_type'] );

                    $order_ids = (array) array_map( 'absint', explode( 'x', $_GET['order_ids'] ) );
                    // Process oldest first: reverse $order_ids array
                    $order_ids = array_reverse( $order_ids );

                    // set default is allowed
                    $allowed = true;

                    // check if user is logged in
                    if ( ! is_user_logged_in() ) {
                        $allowed = false;
                    }

                    // Check the user privileges
                    if( !( current_user_can( 'manage_woocommerce_orders' ) || current_user_can( 'edit_shop_orders' ) ) && !isset( $_GET['my-account'] ) ) {
                        $allowed = false;
                    }

                    // User call from my-account page
                    if ( !current_user_can('manage_options') && isset( $_GET['my-account'] ) ) {
                        // Only for single orders!
                        if ( count( $order_ids ) > 1 ) {
                            $allowed = false;
                        }

                        // Check if current user is owner of order IMPORTANT!!!
                        if ( ! current_user_can( 'view_order', $order_ids[0] ) ) {
                            $allowed = false;
                        }
                    }

                    $allowed = apply_filters( 'wpo_wcpdf_check_privs', $allowed, $order_ids );

                    if ( ! $allowed ) {
                        wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips' ) );
                    }

                    // if we got here, we're safe to go!
                    try {
                        $document = wcpdf_get_document( $document_type, $order_ids, true );

                        if ( $document ) {
                            $output_format = WPO_WCPDF()->settings->get_output_format( $document_type );
                            switch ( $output_format ) {
                                case 'html':
                                    $document->output_html();
                                    break;
                                case 'pdf':
                                default:
                                    if ( has_action( 'wpo_wcpdf_created_manually' ) ) {
                                        do_action( 'wpo_wcpdf_created_manually', $document->get_pdf(), $document->get_filename() );
                                    }
                                    $output_mode = WPO_WCPDF()->settings->get_output_mode( $document_type );
                                    $document->output_pdf( $output_mode );
                                    break;
                            }
                        } else {
                            wp_die( sprintf( __( "Document of type '%s' for the selected order(s) could not be generated", 'woocommerce-pdf-invoices-packing-slips' ), $document_type ) );
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }

                    exit;
                }
          
            
            
        }

    new WooGC_woocommerce_pdf_invoices_packingslips();

?>