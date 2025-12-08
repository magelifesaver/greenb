<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name   : TikTok
    * Since         : 1.2.5
    * Last Check    : 1.2.6  
    */

    class WooGC_tiktok
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'init', array ( $this, 'init') );
                      
                }
                
            function init( $sync_directory_url )
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_after_checkout_form',         'Tt4b_Pixel_Class', 'inject_initiate_checkout_event' );
                    
                    add_filter( 'woocommerce_after_checkout_form', array( $this, 'woocommerce_after_checkout_form' ) );
                }
                
                
            /**
             * Fires the start checkout event
             *
             * @return void
             */
            function woocommerce_after_checkout_form() {
                // do not fire without woocommerce
                if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
                    return;
                }

                if ( null === WC()->cart || WC()->cart->get_cart_contents_count() === 0 ) {
                    return;
                }

                $event  = 'InitiateCheckout';
                $logger = new Logger();
                $logger->log( __METHOD__, "hit $event" );
                $mapi = new Tt4b_Mapi_Class( $logger );
                // if registration required, and can't register in checkout and user not logged in, don't fire event.
                if ( ! WC()->checkout()->is_registration_enabled()
                     && WC()->checkout()->is_registration_required()
                     && ! is_user_logged_in()
                ) {
                    return;
                }
                $fields = Tt4b_Pixel_Class::pixel_event_tracking_field_track( __METHOD__ );
                if ( 0 === count( $fields ) ) {
                    return;
                }

                $event_contents = [];
                $value              = 0;
                $event_id           = Tt4b_Pixel_Class::get_event_id( '' );
                $content_type       = 'product';
                foreach ( WC()->cart->get_cart() as $cart_item ) {
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                    $product      = $cart_item['data'];
                    $quantity     = (int) $cart_item['quantity'];
                    $variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
                    $content      = Tt4b_Pixel_Class::get_properties_from_product( $product, $quantity, $variation_id, Method::STARTCHECKOUT );
                    $value      += $content['price'] * $content['quantity'];
                    array_push( $event_contents, $content );
                    do_action( 'woocommerce/cart_loop/end', $cart_item  );
                }

                $user         = Tt4b_Pixel_Class::get_user();
                $hashed_email = $user['email'];
                $hashed_phone = $user['phone'];

                $url = '';
                if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
                    $url = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
                }
                $referrer = wp_get_referer();
                $page = [
                    'url' => $url,
                ];
                if ( $referrer ) {
                    $page['referrer'] = $referrer;
                }

                $properties = [
                    'contents'             => $event_contents,
                    'content_type'         => $content_type,
                    'currency'             => get_woocommerce_currency(),
                    'value'                => $value,
                    'event_trigger_source' => 'WooCommerce',
                ];

                $data   = [
                    [
                        'event'      => $event,
                        'event_id'   => $event_id,
                        'event_time' => time(),
                        'user'       => $user,
                        'properties' => $properties,
                        'page'       => $page,
                    ],
                ];
                $params = [
                    'partner_name'    => 'WooCommerce',
                    'event_source'    => 'web',
                    'event_source_id' => $fields['pixel_code'],
                    'data'            => $data,
                ];

                // events API track
                $mapi->mapi_post( 'event/track/', $fields['access_token'], $params, 'v1.3' );

                // js pixel track
                Tt4b_Pixel_Class::enqueue_event( $event, $fields['pixel_code'], $properties, $hashed_email, $hashed_phone, $event_id, $user['first_name'], $user['last_name'], $user['city'], $user['state'], $user['country'], $user['zip_code'] );

            }
                         
            
        }
        
        
        

        
    new WooGC_tiktok();

?>