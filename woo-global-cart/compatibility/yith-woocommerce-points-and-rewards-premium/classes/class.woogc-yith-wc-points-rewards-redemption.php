<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}


	/**
	 * Class YITH_WC_Points_Rewards_Redemption
	 */
	class WooGC_YITH_WC_Points_Rewards_Redemption   extends YITH_WC_Points_Rewards_Redemption {
        
        
        protected static $instance;
        
        
        /**
         * Constructor
         *
         * Initialize plugin and registers actions and filters to be used
         *
         * @since  1.0.0
         * @author Emanuela Castorina
         */
        public function __construct() {

            global $WooGC;
            
            //unhook previous actions and filters from initial class
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_checkout_update_order_meta' , 'YITH_WC_Points_Rewards_Redemption', 'add_order_meta');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_checkout_order_processed' , 'YITH_WC_Points_Rewards_Redemption', 'deduce_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_checkout_update_order_meta' , 'YITH_WC_Points_Rewards_Redemption', 'deduce_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_status_failed' , 'YITH_WC_Points_Rewards_Redemption', 'remove_redeemed_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_removed_coupon' , 'YITH_WC_Points_Rewards_Redemption', 'clear_current_coupon');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_status_changed' , 'YITH_WC_Points_Rewards_Redemption', 'clear_ywpar_coupon_after_create_order');
            
            $WooGC->functions->remove_anonymous_object_filter ( 'wp_loaded' , 'YITH_WC_Points_Rewards_Redemption', 'apply_discount');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_cart_item_removed' , 'YITH_WC_Points_Rewards_Redemption', 'update_discount');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_after_cart_item_quantity_update' , 'YITH_WC_Points_Rewards_Redemption', 'update_discount');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_before_cart_item_quantity_zero' , 'YITH_WC_Points_Rewards_Redemption', 'update_discount');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_cart_loaded_from_session' , 'YITH_WC_Points_Rewards_Redemption', 'update_discount');
            
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_status_cancelled' , 'YITH_WC_Points_Rewards_Redemption', 'remove_redeemed_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_status_cancelled_to_completed' , 'YITH_WC_Points_Rewards_Redemption', 'add_redeemed_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_status_cancelled_to_processing' , 'YITH_WC_Points_Rewards_Redemption', 'add_redeemed_order_points');
            
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_order_fully_refunded' , 'YITH_WC_Points_Rewards_Redemption', 'remove_redeemed_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'wp_ajax_nopriv_woocommerce_delete_refund' , 'YITH_WC_Points_Rewards_Redemption', 'add_redeemed_order_points');
            $WooGC->functions->remove_anonymous_object_filter ( 'wp_ajax_woocommerce_delete_refund' , 'YITH_WC_Points_Rewards_Redemption', 'add_redeemed_order_points');
            
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_get_shop_coupon_data' , 'YITH_WC_Points_Rewards_Redemption', 'create_coupon_discount');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_coupon_message' , 'YITH_WC_Points_Rewards_Redemption', 'coupon_rewards_message');
            $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_cart_totals_coupon_label' , 'YITH_WC_Points_Rewards_Redemption', 'coupon_label');
            
            $WooGC->functions->remove_anonymous_object_filter ( 'wp_loaded' , 'YITH_WC_Points_Rewards_Redemption', 'ywpar_set_cron');
            $WooGC->functions->remove_anonymous_object_filter ( 'ywpar_clean_cron' , 'YITH_WC_Points_Rewards_Redemption', 'clear_coupons');
            
            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_order_meta' ), 10 );

            //remove points if are used in order
            if ( version_compare( WC()->version, '2.7', '<' ) ) {
                add_action( 'woocommerce_checkout_order_processed', array( $this, 'deduce_order_points' ) );
            } else {
                add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'deduce_order_points' ), 20 );
                add_action( 'woocommerce_order_status_failed', array( $this, 'remove_redeemed_order_points' ) );
                add_action( 'woocommerce_removed_coupon', array( $this, 'clear_current_coupon' ) );

                add_action( 'woocommerce_order_status_changed', array( $this, 'clear_ywpar_coupon_after_create_order' ), 10, 2 );
            }

            add_action( 'wp_loaded', array( $this, 'apply_discount' ), 30 );
            add_action( 'woocommerce_cart_item_removed', array( $this, 'update_discount' ) );
            add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'update_discount' ) );
            add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'update_discount' ) );
            add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'update_discount' ), 99 );

            //remove point when the order is cancelled
            if ( YITH_WC_Points_Rewards()->get_option( 'remove_point_order_deleted' ) == 'yes' ) {
                add_action( 'woocommerce_order_status_cancelled', array( $this, 'remove_redeemed_order_points' ) );
                add_action( 'woocommerce_order_status_cancelled_to_completed', array( $this, 'add_redeemed_order_points' ) );
                add_action( 'woocommerce_order_status_cancelled_to_processing', array( $this, 'add_redeemed_order_points' ) );
            }

            //remove point when the order is refunded
            if ( YITH_WC_Points_Rewards()->get_option( 'reassing_redeemed_points_refund_order' ) == 'yes' ) {
                add_action( 'woocommerce_order_fully_refunded', array( $this, 'remove_redeemed_order_points' ), 11, 2 );
                add_action( 'wp_ajax_nopriv_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
                add_action( 'wp_ajax_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
            }

            if ( is_user_logged_in() ) {
                if ( version_compare( WC()->version, '2.7', '<' ) ) {
                    add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'create_coupon_discount' ), 15, 2 );
                }
                add_filter( 'woocommerce_coupon_message', array( $this, 'coupon_rewards_message' ), 15, 3 );
                add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'coupon_label' ), 10, 2 );
            }

            add_action( 'wp_loaded', array( $this, 'ywpar_set_cron' ) );
            add_action( 'ywpar_clean_cron', array( $this, 'clear_coupons' ) );


        }
        
        
        
        /**
         * Returns single instance of the class
         *
         * @return \YITH_WC_Points_Rewards_Redemption
         * @since 1.0.0
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }
        
		        
        /**
         * Calculate the points of a product/variation for a single item
         *
         * @param float $discount_amount
         *
         * @return  int $points
         * @since   1.0.0
         * @author  Emanuela Castorina
         */
        public function calculate_rewards_discount( $discount_amount = 0.0 ) {

            $user_id       = get_current_user_id();
            $points_usable = get_user_meta( $user_id, '_ywpar_user_total_points', true );

            if ( $points_usable <= 0 ) {
                return false;
            }

            $items = WC()->cart->get_cart();

            $this->max_discount = 0;
            $this->max_points   = 0;

            if ( $this->get_conversion_method() == 'fixed' ) {
                $conversion = $this->get_conversion_rate_rewards();

                //get the items of cart
                foreach ( $items as $item => $values ) {
                    
                    switch_to_blog( $values['blog_id']);
                    
                    $product_id       = ( isset( $values['variation_id'] ) && $values['variation_id'] != 0 ) ? $values['variation_id'] : $values['product_id'];

                    $item_price       = apply_filters( 'ywpar_calculate_rewards_discount_item_price', ywpar_get_price( $values['data'] ), $values, $product_id );
                    $product_discount = $this->calculate_product_max_discounts( $product_id, $item_price );
                    if ( $product_discount != 0 ) {
                        $this->max_discount += $product_discount * $values['quantity'];
                    }
                    
                    restore_current_blog();
                }


                $general_max_discount = YITH_WC_Points_Rewards()->get_option( 'max_points_discount' );

                if ( apply_filters( 'ywpar_exclude_taxes_from_calculation', false ) ) {
                    $subtotal = ( (float) WC()->cart->get_subtotal() - (float) WC()->cart->get_discount_total() ) + $discount_amount;
                } else {
                    $subtotal = ( ( (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax() ) - ( (float) WC()->cart->get_discount_total() + (float) WC()->cart->get_discount_tax() ) ) + $discount_amount;
                }




                if ( $subtotal <= $this->max_discount ) {
                    $this->max_discount = $subtotal;
                }

                $this->max_discount = apply_filters( 'ywpar_set_max_discount_for_minor_subtotal', $this->max_discount, $subtotal );

                //check if there's a max discount amount
                if ( $general_max_discount != '' ) {
                    $is_percent = strpos( $general_max_discount, '%' );
                    if ( $is_percent === false ) {
                        $max_discount = ( $subtotal >= $general_max_discount ) ? $general_max_discount : $subtotal;
                    } else {
                        $general_max_discount = (float) str_replace( '%', '', $general_max_discount );
                        $max_discount         = $subtotal * $general_max_discount / 100;
                    }

                    if ( $max_discount < $this->max_discount ) {
                        $this->max_discount = $max_discount;
                    }
                }

                $this->max_discount = apply_filters( 'ywpar_calculate_rewards_discount_max_discount_fixed', $this->max_discount );
                $appfun             = apply_filters( 'ywpar_approx_function', 'ceil' );
                $this->max_points   = call_user_func( $appfun, $this->max_discount / $conversion['money'] * $conversion['points'] );

                if ( $this->max_points > $points_usable ) {
                    $this->max_points   = $points_usable;
                    $this->max_discount = $this->max_points / $conversion['points'] * $conversion['money'];
                }
            } elseif ( $this->get_conversion_method() == 'percentage' ) {
                $conversion = $this->get_conversion_percentual_rate_rewards();

                foreach ( $items as $item => $values ) {
                    
                    switch_to_blog( $values['blog_id']);
                    
                    $product_id       = ( isset( $values['variation_id'] ) && $values['variation_id'] != 0 ) ? $values['variation_id'] : $values['product_id'];
                    $item_price       = apply_filters( 'ywpar_calculate_rewards_discount_item_price', ywpar_get_price( $values['data'] ), $values );
                    $product_discount = $this->calculate_product_max_discounts_percentage( $product_id, $item_price );
                    if ( $product_discount != 0 ) {
                        $this->max_discount += $product_discount * $values['quantity'];
                    }
                    
                    restore_current_blog();
                }

                $subtotal_cart = ywpar_get_subtotal_cart();
                if ( $subtotal_cart != 0 ) {
                    $cart_discount_percentual = $this->max_discount / $subtotal_cart * 100;
                    $max_points               = round( $cart_discount_percentual / $conversion['discount'] ) * $conversion['points'];
                    $cart_discount_percentual = round( $max_points / $conversion['points'] ) * $conversion['discount'];

                    if ( $points_usable >= $max_points ) {
                        $this->max_points              = $max_points;
                        $this->max_percentual_discount = $cart_discount_percentual;
                        $this->max_discount            = ( $subtotal_cart * $this->max_percentual_discount ) / 100;
                    } else {
                        //must be floor because to calculate the right max points
                        $this->max_percentual_discount = floor( $points_usable / $conversion['points'] ) * $conversion['discount'];
                        $this->max_points              = round( $this->max_percentual_discount / $conversion['discount'] ) * $conversion['points'];
                        $this->max_discount            = ( $subtotal_cart * $this->max_percentual_discount ) / 100;
                    }
                }

                $this->max_discount = apply_filters( 'ywpar_calculate_rewards_discount_max_discount_percentual', $this->max_discount );
            }
            $this->max_discount = apply_filters( 'ywpar_calculate_rewards_discount_max_discount', $this->max_discount, $this, $conversion );
            $this->max_points   = apply_filters( 'ywpar_calculate_rewards_discount_max_points', $this->max_points, $this, $conversion );
            return $this->max_discount;
        }

		
	}
