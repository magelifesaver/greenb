<?php
/**
 * Purchase Credit Features
 *
 * @author 		StoreApps
 * @since 		3.3.0
 * @version 	1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	/**
	 * Class for handling Purchase credit feature
	 */
	class WooGC_WC_SC_Purchase_Credit extends WC_SC_Purchase_Credit {
        
		/**
		 * Constructor
		 */
		public function __construct() 
        {

			if ( is_plugin_active( 'woocommerce-gateway-paypal-express/woocommerce-gateway-paypal-express.php' ) ) {
				add_action( 'woocommerce_ppe_checkout_order_review', array( $this, 'gift_certificate_receiver_detail_form' ) );
			}

			add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'gift_certificate_receiver_detail_form' ) );

		}
           
		       
       
        /**
         * Function to display form for entering details of the gift certificate's receiver
         */
        public function gift_certificate_receiver_detail_form() 
            {
                global $total_coupon_amount;

                $is_show = apply_filters( 'is_show_gift_certificate_receiver_detail_form', true, array() );

                if ( ! $is_show ) {
                    return;
                }

                if ( ! wp_style_is( 'smart-coupon' ) ) {
                    wp_enqueue_style( 'smart-coupon' );
                }

                if ( ! wp_doing_ajax() ) {
                    add_action( 'wp_footer', array( $this, 'receiver_detail_form_styles_and_scripts' ) );
                }

                $form_started = false;

                $all_discount_types = wc_get_coupon_types();

                $schedule_store_credit = get_option( 'smart_coupons_schedule_store_credit' );

                $coupon_receiver_details_session = $this->get_coupon_receiver_details_session();

                if ( ! is_null( $coupon_receiver_details_session ) ) {
                    $is_gift                     = ( ! empty( $coupon_receiver_details_session['is_gift'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['is_gift'] ) ) : '';
                    $sc_send_to                  = ( ! empty( $coupon_receiver_details_session['sc_send_to'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['sc_send_to'] ) ) : '';
                    $wc_sc_schedule_gift_sending = ( ! empty( $coupon_receiver_details_session['wc_sc_schedule_gift_sending'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['wc_sc_schedule_gift_sending'] ) ) : '';
                    $gift_receiver_email         = ( ! empty( $coupon_receiver_details_session['gift_receiver_email'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['gift_receiver_email'] ) ) : array();
                    $gift_sending_date_time      = ( ! empty( $coupon_receiver_details_session['gift_sending_date_time'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['gift_sending_date_time'] ) ) : array();
                    $gift_sending_timestamp      = ( ! empty( $coupon_receiver_details_session['gift_sending_timestamp'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['gift_sending_timestamp'] ) ) : array();
                    $gift_receiver_message       = ( ! empty( $coupon_receiver_details_session['gift_receiver_message'] ) ) ? wc_clean( wp_unslash( $coupon_receiver_details_session['gift_receiver_message'] ) ) : array();
                } else {
                    $is_gift                     = '';
                    $sc_send_to                  = '';
                    $wc_sc_schedule_gift_sending = '';
                    $gift_receiver_email         = array();
                    $gift_sending_date_time      = array();
                    $gift_sending_timestamp      = array();
                    $gift_receiver_message       = array();
                }

                $coupon_receiver_details_session = array(
                    'is_gift'                     => $is_gift,
                    'sc_send_to'                  => $sc_send_to,
                    'wc_sc_schedule_gift_sending' => $wc_sc_schedule_gift_sending,
                    'gift_receiver_email'         => $gift_receiver_email,
                    'gift_sending_date_time'      => $gift_sending_date_time,
                    'gift_sending_timestamp'      => $gift_sending_timestamp,
                    'gift_receiver_message'       => $gift_receiver_message,
                );

                foreach ( WC()->cart->cart_contents as $product ) {
                    
                    switch_to_blog( $product['blog_id'] );

                    if ( ! empty( $product['variation_id'] ) ) {
                        $_product = wc_get_product( $product['variation_id'] );
                    } elseif ( ! empty( $product['product_id'] ) ) {
                        $_product = wc_get_product( $product['product_id'] );
                    } else {
                        restore_current_blog();
                        continue;
                    }

                    $coupon_titles = $this->get_coupon_titles( array( 'product_object' => $_product ) );

                    $price = $_product->get_price();

                    if ( $coupon_titles ) {

                        foreach ( $coupon_titles as $coupon_title ) {

                            $coupon = new WC_Coupon( $coupon_title );
                            if ( $this->is_wc_gte_30() ) {
                                if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_id' ) ) ) {
                                    continue;
                                }
                                $coupon_id = $coupon->get_id();
                                if ( empty( $coupon_id ) ) {
                                    continue;
                                }
                                $discount_type = $coupon->get_discount_type();
                            } else {
                                $coupon_id     = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
                                $discount_type = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
                            }

                            $coupon_amount = $this->get_amount( $coupon, true );

                            $pick_price_of_prod                              = ( $this->is_callable( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'is_pick_price_of_product' ) : get_post_meta( $coupon_id, 'is_pick_price_of_product', true );
                            $smart_coupon_gift_certificate_form_page_text    = get_option( 'smart_coupon_gift_certificate_form_page_text' );
                            $smart_coupon_gift_certificate_form_page_text    = ( ! empty( $smart_coupon_gift_certificate_form_page_text ) ) ? $smart_coupon_gift_certificate_form_page_text : __( 'Send Coupons to...', 'woocommerce-smart-coupons' );
                            $smart_coupon_gift_certificate_form_details_text = get_option( 'smart_coupon_gift_certificate_form_details_text' );
                            $smart_coupon_gift_certificate_form_details_text = ( ! empty( $smart_coupon_gift_certificate_form_details_text ) ) ? $smart_coupon_gift_certificate_form_details_text : '';     // Enter email address and optional message for Gift Card receiver.

                            // MADE CHANGES IN THE CONDITION TO SHOW FORM.
                            if ( array_key_exists( $discount_type, $all_discount_types ) || ( 'yes' === $pick_price_of_prod && '' === $price ) || ( 'yes' === $pick_price_of_prod && '' !== $price && $coupon_amount > 0 ) ) {

                                if ( ! $form_started ) {
                                    $is_show_coupon_receiver_form = get_option( 'smart_coupons_display_coupon_receiver_details_form', 'yes' );
                                    if ( 'no' === $is_show_coupon_receiver_form ) {
                                        ?>
                                        <div class="gift-certificate sc_info_box">
                                            <p><?php echo esc_html__( 'Your order contains coupons. You will receive them after completion of this order.', 'woocommerce-smart-coupons' ); ?></p>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div class="gift-certificate sc_info_box" <?php echo ( 'no' === $is_show_coupon_receiver_form ) ? 'style="' . esc_attr( 'display: none;' ) . '"' : ''; ?>>
                                        <h3><?php echo esc_html( stripslashes( $smart_coupon_gift_certificate_form_page_text ) ); ?></h3>
                                            <?php if ( ! empty( $smart_coupon_gift_certificate_form_details_text ) ) { ?>
                                            <p><?php echo esc_html( stripslashes( $smart_coupon_gift_certificate_form_details_text ) ); ?></p>
                                            <?php } ?>
                                            <div class="gift-certificate-show-form">
                                                <p><?php echo esc_html__( 'Your order contains coupons. What would you like to do?', 'woocommerce-smart-coupons' ); ?></p>
                                                <ul class="show_hide_list" style="list-style-type: none;">
                                                    <li><input type="radio" id="hide_form" name="is_gift" value="no" <?php checked( in_array( $is_gift, array( 'no', '' ), true ) ); ?> /> <label for="hide_form"><?php echo esc_html__( 'Send to me', 'woocommerce-smart-coupons' ); ?></label></li>
                                                    <li>
                                                    <input type="radio" id="show_form" name="is_gift" value="yes" <?php checked( $is_gift, 'yes' ); ?> /> <label for="show_form"><?php echo esc_html__( 'Gift to someone else', 'woocommerce-smart-coupons' ); ?></label>
                                                    <ul class="single_multi_list" style="list-style-type: none;">
                                                    <li><input type="radio" id="send_to_one" name="sc_send_to" value="one" <?php checked( in_array( $sc_send_to, array( 'one', '' ), true ) ); ?> /> <label for="send_to_one"><?php echo esc_html__( 'Send to one person', 'woocommerce-smart-coupons' ); ?></label></li>
                                                    <li><input type="radio" id="send_to_many" name="sc_send_to" value="many" <?php checked( $sc_send_to, 'many' ); ?> /> <label for="send_to_many"><?php echo esc_html__( 'Send to different people', 'woocommerce-smart-coupons' ); ?></label></li>
                                                    </ul>
                                                    <?php if ( 'yes' === $schedule_store_credit ) { ?>
                                                        <li class="wc_sc_schedule_gift_sending_wrapper">
                                                            <?php echo esc_html__( 'Deliver coupon', 'woocommerce-smart-coupons' ); ?>
                                                            <label class="wc-sc-toggle-check">
                                                                <input type="checkbox" class="wc-sc-toggle-check-input" id="wc_sc_schedule_gift_sending" name="wc_sc_schedule_gift_sending" value="yes" <?php checked( $wc_sc_schedule_gift_sending, 'yes' ); ?> />
                                                                <span class="wc-sc-toggle-check-text"></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                    </li>
                                                </ul>
                                            </div>
                                    <div class="gift-certificate-receiver-detail-form">
                                    <div class="clear"></div>
                                    <div id="gift-certificate-receiver-form-multi">
                                    <?php

                                    $form_started = true;

                                }

                                $this->add_text_field_for_email( $coupon, $product, $coupon_receiver_details_session );

                            }
                        }
                    }
                    
                    restore_current_blog();
                }

                if ( $form_started ) {
                    ?>
                    </div>
                    <div id="gift-certificate-receiver-form-single">
                        <div class="form_table">
                            <div class="email_amount">
                                <div class="amount"></div>
                                <div class="email"><input class="gift_receiver_email" type="text" placeholder="<?php echo esc_attr__( 'Enter recipient e-mail address', 'woocommerce-smart-coupons' ); ?>..." name="gift_receiver_email[0][0]" value="<?php echo esc_attr( ( ! empty( $gift_receiver_email[0][0] ) ) ? $gift_receiver_email[0][0] : '' ); ?>" /></div>
                            </div>
                            <div class="email_sending_date_time_wrapper">
                                    <input class="gift_sending_date_time" type="text" placeholder="<?php echo esc_attr__( 'Pick a delivery date & time', 'woocommerce-smart-coupons' ); ?>..." name="gift_sending_date_time[0][0]" value="<?php echo esc_attr( ( ! empty( $gift_sending_date_time[0][0] ) ) ? $gift_sending_date_time[0][0] : '' ); ?>" autocomplete="off" style="position: relative; z-index: 99997;"/>
                                    <input class="gift_sending_timestamp" type="hidden" name="gift_sending_timestamp[0][0]" value="<?php echo esc_attr( ( ! empty( $gift_sending_timestamp[0][0] ) ) ? $gift_sending_timestamp[0][0] : '' ); ?>"/>
                            </div>
                            <div class="message_row">
                                <div class="message"><textarea placeholder="<?php echo esc_attr__( 'Write a message', 'woocommerce-smart-coupons' ); ?>..." class="gift_receiver_message" name="gift_receiver_message[0][0]" cols="50" rows="5"><?php echo esc_html( ( ! empty( $gift_receiver_message[0][0] ) ) ? $gift_receiver_message[0][0] : '' ); ?></textarea></div>
                            </div>
                        </div>
                    </div>
                    </div></div>
                    <?php
                    do_action( 'wc_sc_gift_certificate_form_shown' );
                }

            }
       
	}
