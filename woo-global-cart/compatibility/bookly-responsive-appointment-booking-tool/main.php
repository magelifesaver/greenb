<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for Plugin Name: Bookly
    * Compatibility checked on Version: 17.6
    */

    use Bookly\Lib;
    use Bookly\Frontend\Modules\Booking\Proxy;
    
    class WooGC_Compatibility_Bookly
        {
            
            function __construct()
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_cart_item_product',     'WooGC_general_filters',    'woocommerce_cart_item_product' );
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_get_item_data',         'WooGC_Template',           'woocommerce_get_item_data' );
                       
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_check_cart_items',      'BooklyPro\Frontend\Modules\WooCommerce\Ajax', 'checkAvailableTimeForCart', 10, 0 );
                    add_action( 'woocommerce_check_cart_items',         array( 'WooGC_Compatibility_Bookly', 'checkAvailableTimeForCart' ), 10, 0 );
                    
                    $WooGC->functions->remove_anonymous_object_filter( 'woocommerce_before_calculate_totals','BooklyPro\Frontend\Modules\WooCommerce\Ajax', 'beforeCalculateTotals', 10, 1 );
                    add_action( 'woocommerce_before_calculate_totals',  array( 'WooGC_Compatibility_Bookly', 'beforeCalculateTotals' ), 10, 1 );
                    
                }
                
                
                
            /**
            * Verifies the availability of all appointments that are in the cart
            */
            public static function checkAvailableTimeForCart()
                {
                    $recalculate_totals = false;
                    foreach ( wc()->cart->get_cart() as $wc_key => $wc_item ) {
                        
                        if ( array_key_exists( 'bookly', $wc_item ) ) {
                            
                            switch_to_blog( $wc_item['blog_id']  );
                            
                            if ( self::_migration( $wc_key, $wc_item ) === false ) {
                                // Removed item from cart.
                                continue;
                            }
                            $userData = new Lib\UserBookingData( null );
                            $userData->fillData( $wc_item['bookly'] );
                            $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                            if ( $wc_item['quantity'] > 1 ) {
                                foreach ( $userData->cart->getItems() as $cart_item ) {
                                    // Equal appointments increase quantity
                                    $cart_item->setNumberOfPersons( $cart_item->getNumberOfPersons() * $wc_item['quantity'] );
                                }
                            }
                            // Check if appointment's time is still available
                            $failed_cart_key = $userData->cart->getFailedKey();
                            if ( $failed_cart_key !== null ) {
                                $cart_item = $userData->cart->get( $failed_cart_key );
                                $slot = $cart_item->getSlots();
                                wc_add_notice( strtr( __( 'Sorry, the time slot %date_time% for %service% has been already occupied.', 'bookly' ),
                                    array(
                                        '%service%'   => '<strong>' . $cart_item->getService()->getTranslatedTitle() . '</strong>',
                                        '%date_time%' => Lib\Utils\DateTime::formatDateTime( $slot[0][2] ),
                                    ) ), 'error' );
                                wc()->cart->set_quantity( $wc_key, 0, false );
                                $recalculate_totals = true;
                            }
                            
                            restore_current_blog();
                        }
                    }
                    if ( $recalculate_totals ) {
                        wc()->cart->calculate_totals();
                    }
                }
                
                
            /**
             * Change item price in cart.
             *
             * @param \WC_Cart $cart_object
             */
            public static function beforeCalculateTotals( $cart_object )
            {
                foreach ( $cart_object->cart_contents as $wc_key => &$wc_item ) {
                    if ( isset ( $wc_item['bookly'] ) ) {
                        
                        switch_to_blog( $wc_item['blog_id']  );
                        
                        $userData = new Lib\UserBookingData( null );
                        $userData->fillData( $wc_item['bookly'] );
                        $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                        $cart_info = $userData->cart->getInfo();
                        /** @var \WC_Product $product */
                        $product   = $wc_item['data'];
                        if ( $product->is_taxable() && Lib\Config::taxesActive() && ! wc_prices_include_tax() ) {
                            $product->set_price( $cart_info->getPayNow() - $cart_info->getPayTax() );
                        } else {
                            $product->set_price( $cart_info->getPayNow() );
                        }
                        
                        restore_current_blog();
                    }
                }
            }
            
            
            
            /**
             * Migration deprecated cart items data.
             *
             * @param string $wc_key
             * @param array  $wc_item
             * @return bool
             */
            protected static function _migration( $wc_key, &$wc_item )
            {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    // The current implementation only remove cart items with deprecated format.
                    wc()->cart->set_quantity( $wc_key, 0, false );
                    wc()->cart->calculate_totals();

                    return false;
                } else {
                    // Version event data structure.
                    $version = $wc_item['bookly']['version'];
                    if ( $version == BooklyPro\Frontend\Modules\WooCommerce\Controller::VERSION ) {
                        return true;
                    }

                    if ( $version == '1.0' ) {
                        // add new billing data
                        $wc_item['bookly']['wc_checkout'] = array(
                            'billing_first_name' => $wc_item['bookly']['first_name'],
                            'billing_last_name'  => $wc_item['bookly']['last_name'],
                            'billing_email'      => $wc_item['bookly']['email'],
                            'billing_phone'      => $wc_item['bookly']['phone'],
                            'billing_country'    => null,
                            'billing_address_1'  => null,
                            'billing_address_2'  => null,
                            'billing_city'       => null,
                            'billing_state'      => null,
                            'billing_postcode'   => null,
                        );
                    }

                    $wc_item['bookly']['version'] = self::VERSION;

                    // Update client cart session.
                    wc()->cart->set_session();
                }

                return true;
            }
                                                                   
        }


    new WooGC_Compatibility_Bookly();    
    
?>