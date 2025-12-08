<?php
    
    namespace WeDevs\DokanPro\VendorDiscount;
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Dokan PRO
    * Since:                3.12.5
    */

    class WooGC_dokan_pro
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_filter ( 'init', array ( $this, 'init'), 999 );
                      
                }
                
                
            function init ()
                {
                    global $WooGC;
                    
                    $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_check_cart_items' , 'WeDevs\DokanPro\VendorDiscount\Hooks', 'generate_and_apply_coupon_for_discount');
                    $WooGC->functions->remove_anonymous_object_filter ( 'woocommerce_before_checkout_form' , 'WeDevs\DokanPro\VendorDiscount\Hooks', 'generate_and_apply_coupon_for_discount');
                    
                    add_action( 'woocommerce_check_cart_items',     [ $this, 'generate_and_apply_coupon_for_discount' ] );
                    add_action( 'woocommerce_before_checkout_form', [ $this, 'generate_and_apply_coupon_for_discount' ] );
                }
                
            /**
             * Generate and apply coupon for order and product quantity discount.
             *
             * @since 3.9.4
             *
             * @return void
             */
            public function generate_and_apply_coupon_for_discount(): void 
                {
                    $this->apply_order_discounts();
                    $this->apply_product_discounts();
                }
                
                
            
            /**
             * Apply product quantity discount.
             *
             * @since 3.9.4
             *
             * @return void
             */
            public function apply_product_discounts(): void {
                if ( ! WC()->cart ) {
                    return;
                }

                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                    
                    $product_discount = ( new ProductDiscount() )
                        ->set_cart_item_key( $cart_item_key )
                        ->set_product_id( $cart_item['product_id'] )
                        ->set_quantity( $cart_item['quantity'] );

                    if ( ! $product_discount->is_already_applied() && $product_discount->is_applicable() ) {
                        $product_discount->apply();
                    } elseif ( $product_discount->is_already_applied() && ! $product_discount->is_applicable() ) {
                        $product_discount->remove()->delete_coupon();
                    }
                    
                    do_action( 'woocommerce/cart_loop/end', $cart_item );
                    
                }
            }

            /**
             * Apply order total discount.
             *
             * @since 3.9.4
             *
             * @return void
             */
            public function apply_order_discounts(): void {
                if ( ! WC()->cart ) {
                    return;
                }

                $cart_data            = [];
                $applied_coupons      = [];
                $cart_applied_coupons = WC()->cart->applied_coupons;
                WC()->cart->calculate_totals();

                /**
                 * Here we are extracting the vendor and his order totals so that we can apply the discounts based on the vendor settings.
                 */
                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    
                    do_action( 'woocommerce/cart_loop/start', $cart_item );
                    
                    $product_id = $cart_item['product_id'];
                    $seller     = dokan_get_vendor_by_product( $product_id );

                    if ( ! $seller ) {
                        do_action( 'woocommerce/cart_loop/end', $cart_item );
                        continue;
                    }

                    if ( ! array_key_exists( $seller->get_id(), $cart_data ) ) {
                        $cart_data[ $seller->get_id() ]['total_amount'] = 0;
                        $cart_data[ $seller->get_id() ]['vendor']       = $seller;
                        $cart_data[ $seller->get_id() ]['product_ids']  = [];
                    }

                    $cart_data[ $seller->get_id() ]['total_amount']  = $cart_data[ $seller->get_id() ]['total_amount'] + (float) $cart_item['line_subtotal'];
                    $cart_data[ $seller->get_id() ]['product_ids'][] = $product_id;

                    // we need this to remove the coupon from the cart, $cart_item_key is unique and won't change even if the quantity is changed
                    $cart_data[ $seller->get_id() ]['cart_item_keys'][] = $cart_item_key;
                    
                    do_action( 'woocommerce/cart_loop/end', $cart_item );
                    
                }

                /**
                 * Here we are applying the discounts based on the vendor settings.
                 */
                foreach ( $cart_data as $data ) {
                    $vendor        = $data['vendor'];
                    $total_amount  = $data['total_amount'];
                    $product_ids   = $data['product_ids'];
                    $cart_item_key = md5( wp_json_encode( $data['cart_item_keys'] ) );

                    $order_discount = ( new OrderDiscount() )
                        ->set_vendor( $vendor )
                        ->set_cart_item_key( $cart_item_key )
                        ->set_total_amount( $total_amount )
                        ->set_product_ids( $product_ids );

                    if ( ! $order_discount->is_applicable() ) {
                        $order_discount->remove()->delete_coupon();
                        continue;
                    }

                    if ( ! $order_discount->is_already_applied() ) {
                        $order_discount->apply();
                    }

                    $applied_coupons[] = $order_discount->get_coupon_code();
                }

                foreach ( $cart_applied_coupons as $coupon_code ) {
                    $coupon = new WC_Coupon( $coupon_code );
                    if ( Helper::is_vendor_order_discount_coupon( $coupon ) && ! in_array( $coupon_code, $applied_coupons, true ) ) {
                        WC()->cart->remove_coupon( $coupon_code );
                        $coupon->delete( true );
                    }
                }
            }         
            
        }

        
    new WooGC_dokan_pro();

?>