<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          FooEvents for WooCommerce
    * Since Version:        1.11.10
    */
    
    return;
    
    class WooGC_fooevents
        {

            public function __construct( $dependencies = array() ) 
                {

                    global $WooGC;
                    
                    $options    =   $WooGC->functions->get_options();   
                    if( $options['cart_checkout_type']  ==  'each_store' )
                        add_action('init',          array($this, 'plugin_init'), 99);

                }
    

            function plugin_init()
                {
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_after_order_notes',            'FooEvents_Checkout_Helper',     'attendee_checkout' );
                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_process',             'FooEvents_Checkout_Helper',     'attendee_checkout_process' );
                    $WooGC->functions->remove_class_filter( 'woocommerce_checkout_update_order_meta',   'FooEvents_Checkout_Helper',     'woocommerce_events_process' );
                    require_once WOOGC_PATH . '/compatibility/fooevents/classes/checkouthelper.php';
                    new WooGC_FooEvents_Checkout_Helper();
                    
                    
                    $WooGC->functions->remove_class_filter( 'woocommerce_order_status_completed',   'FooEvents_Woo_Helper',     'process_order_tickets', 20 );
                    require_once WOOGC_PATH . '/compatibility/fooevents/classes/woohelper.php';
                    new WooGC_FooEvents_Woo_Helper();   


                }

        }

    new WooGC_fooevents();


?>