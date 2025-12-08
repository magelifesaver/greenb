<?php
/**
 * File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-order-creator.php
 * Purpose: Finalizes WooCommerce order from Order Creator preview.
 * Handles: customer matching, file uploads, billing/shipping, delivery info,
 *          products, discounts, coupons, payment, profiles, notifications, logging.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Order_Creator {

    /**
     * Create WooCommerce order from preview form data.
     *
     * @param array $post_data   Form data from Order Creator.
     * @param array $parsed_data Parsed data from HTML (optional).
     * @return int|null Order ID on success, null on failure.
     */
    public static function create_order_from_preview( $post_data, $parsed_data = [] ) {
        AAA_V4_Logger::log( '=== Process Start: Order Creation ===' );

        if ( empty( $post_data['products'] ) ) {
            echo '<p style="color:red;">No products found. Cannot create order.</p>';
            AAA_V4_Logger::log( 'Aborting: No products in payload.' );
            AAA_V4_Logger::log( '=== Process End: Order Creation ===' );
            return null;
        }

        // ───────────────────────────────────────────────
        // Sanitize fields
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Sanitizing input' );

        $external_order_number = sanitize_text_field( $post_data['external_order_number'] ?? '' );
        $first_name            = sanitize_text_field( $post_data['customer_first_name'] ?? '' );
        $last_name             = sanitize_text_field( $post_data['customer_last_name'] ?? '' );
        $customer_phone        = AAA_V4_Customer_Handler::normalize_phone_number( sanitize_text_field( $post_data['customer_phone'] ?? '' ) );
        $customer_email        = sanitize_email( $post_data['customer_email'] ?? '' );

        $billing_address_1     = sanitize_text_field( $post_data['billing_address_1'] ?? '' );
        $billing_address_2     = sanitize_text_field( $post_data['billing_address_2'] ?? '' );
        $billing_city          = sanitize_text_field( $post_data['billing_city'] ?? '' );
        $billing_state         = sanitize_text_field( $post_data['billing_state'] ?? '' );
        $billing_postcode      = sanitize_text_field( $post_data['billing_postcode'] ?? '' );
        $billing_country       = sanitize_text_field( $post_data['billing_country'] ?? '' );

        $raw_pm                = sanitize_text_field( $post_data['payment_method'] ?? '' );
        $shipping_method       = sanitize_text_field( $post_data['shipping_method'] ?? '' );
        $coupon_code           = sanitize_text_field( $post_data['coupon_code'] ?? '' );

        // ───────────────────────────────────────────────
        // Match or create customer
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Matching or creating customer' );

        $is_new_customer = false;
        $match           = AAA_V4_Customer_Handler::find_existing_customer( $customer_email, $customer_phone );
        $customer_id     = $match['user_id'] ?? 0;

        if ( ! $customer_id ) {
            $customer_id = AAA_V4_Customer_Handler::create_customer(
                $customer_email,
                "{$first_name} {$last_name}",
                $customer_phone,
                $billing_address_1
            );
            $is_new_customer = true;
        }

        if ( ! $customer_id ) {
            echo '<p style="color:red;">Could not match or create customer.</p>';
            AAA_V4_Logger::log( 'Aborting: No customer ID.' );
            AAA_V4_Logger::log( '=== Process End: Order Creation ===' );
            return null;
        }

        // ────────────────────────────────────────────────
        // Save DL / Expiration / Birthday if provided
        // ────────────────────────────────────────────────
        if ( isset( $post_data['afreg_additional_4532'] ) ) {
            $dln_value = sanitize_text_field( $post_data['afreg_additional_4532'] );
            $old_dln   = get_user_meta( $customer_id, 'afreg_additional_4532', true );
            if ( $dln_value !== $old_dln ) {
                update_user_meta( $customer_id, 'afreg_additional_4532', $dln_value );
            }
        }

        if ( isset( $post_data['afreg_additional_4623'] ) ) {
            $dl_exp_value = sanitize_text_field( $post_data['afreg_additional_4623'] );
            $old_exp      = get_user_meta( $customer_id, 'afreg_additional_4623', true );
            if ( $dl_exp_value !== $old_exp ) {
                update_user_meta( $customer_id, 'afreg_additional_4623', $dl_exp_value );
            }
        }

        if ( isset( $post_data['afreg_additional_4625'] ) ) {
            $birthday_value = sanitize_text_field( $post_data['afreg_additional_4625'] );
            $old_bday       = get_user_meta( $customer_id, 'afreg_additional_4625', true );
            if ( $birthday_value !== $old_bday ) {
                update_user_meta( $customer_id, 'afreg_additional_4625', $birthday_value );
            }
        }

        // ───────────────────────────────────────────────
        // File uploads (ID, Selfie, Medical Record)
        // ───────────────────────────────────────────────
        $uploads   = wp_upload_dir();
        $basedir   = $uploads['basedir'];
        $subfolder = 'addify_registration_uploads';
        $custom_dir = trailingslashit( $basedir ) . $subfolder;

        if ( ! file_exists( $custom_dir ) ) {
            wp_mkdir_p( $custom_dir );
        }

        $handle_file_upload = function( $input_name, $meta_key ) use ( $custom_dir, $customer_id ) {
            if ( empty( $_FILES[ $input_name ]['name'] ) ) { return; }
            $file_array = $_FILES[ $input_name ];
            if ( $file_array['error'] !== UPLOAD_ERR_OK ) { return; }

            $orig_name   = sanitize_file_name( $file_array['name'] );
            $timestamped = time() . '_' . $orig_name;
            $destination = trailingslashit( $custom_dir ) . $timestamped;

            if ( move_uploaded_file( $file_array['tmp_name'], $destination ) ) {
                update_user_meta( $customer_id, $meta_key, $timestamped );
            }
        };

        if ( ! empty( $_FILES['afreg_additional_4626']['name'] ) ) {
            $handle_file_upload( 'afreg_additional_4626', 'afreg_additional_4626' );
        }
        if ( ! empty( $_FILES['afreg_additional_4627']['name'] ) ) {
            $handle_file_upload( 'afreg_additional_4627', 'afreg_additional_4627' );
        }
        if ( ! empty( $_FILES['afreg_additional_4630']['name'] ) ) {
            $handle_file_upload( 'afreg_additional_4630', 'afreg_additional_4630' );
        }

        // ───────────────────────────────────────────────
        // Create WooCommerce order
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Creating WooCommerce order' );
        $order = wc_create_order( [ 'customer_id' => (int) $customer_id ] );

        if ( is_wp_error( $order ) ) {
            echo '<p style="color:red;">Failed to create WooCommerce order.</p>';
            AAA_V4_Logger::log( 'Aborting: wc_create_order returned error.' );
            AAA_V4_Logger::log( '=== Process End: Order Creation ===' );
            return null;
        }

        // Save external order number
        if ( ! empty( $parsed_data['external_order_number'] ) ) {
            $order->update_meta_data( '_external_order_number', sanitize_text_field( $parsed_data['external_order_number'] ) );
        }

        // Internal private note
        if ( ! empty( $post_data['internal_order_note'] ) ) {
            $note = sanitize_textarea_field( $post_data['internal_order_note'] );
            $order->add_order_note( $note, false );
            AAA_V4_Logger::log( "Internal note added: {$note}" );
        }

        // ───────────────────────────────────────────────
        // Order source
        // ───────────────────────────────────────────────
        $order_source = sanitize_text_field( $post_data['order_source_type'] ?? 'phone' );
	$allowed_sources = [ 'phone', 'weedmaps', 'checkout', 'employee', 'weedmaps_ftp', 'phone_ftp', 'web_ftp' ];
	if ( ! in_array( $order_source, $allowed_sources, true ) ) {
            $order_source = 'phone';
        }
        $order->set_created_via( $order_source );

        // ───────────────────────────────────────────────
        // Shipping & Billing Addresses
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Setting shipping & billing details' );

        $shipping_address_1 = sanitize_text_field( $post_data['shipping_address_1'] ?? '' );
        $shipping_address_2 = sanitize_text_field( $post_data['shipping_address_2'] ?? '' );
        $shipping_city      = sanitize_text_field( $post_data['shipping_city'] ?? '' );
        $shipping_state     = sanitize_text_field( $post_data['shipping_state'] ?? '' );
        $shipping_postcode  = sanitize_text_field( $post_data['shipping_postcode'] ?? '' );
        $shipping_country   = sanitize_text_field( $post_data['shipping_country'] ?? 'US' );

        // Save shipping into order
        $order->set_shipping_first_name( $first_name );
        $order->set_shipping_last_name( $last_name );
        $order->set_shipping_address_1( $shipping_address_1 );
        $order->set_shipping_address_2( $shipping_address_2 );
        $order->set_shipping_city( $shipping_city );
        $order->set_shipping_state( $shipping_state );
        $order->set_shipping_postcode( $shipping_postcode );
        $order->set_shipping_country( $shipping_country );

        // Load billing from user profile (canonical)
	$billing_first_name   = get_user_meta( $customer_id, 'billing_first_name', true );
	$billing_last_name    = get_user_meta( $customer_id, 'billing_last_name',  true );
	$billing_phone_um     = get_user_meta( $customer_id, 'billing_phone',      true );
	$billing_email_um     = get_user_meta( $customer_id, 'billing_email',      true );
	$billing_address_1_um = get_user_meta( $customer_id, 'billing_address_1',  true );
	$billing_address_2_um = get_user_meta( $customer_id, 'billing_address_2',  true );
	$billing_city_um      = get_user_meta( $customer_id, 'billing_city',       true );
	$billing_state_um     = get_user_meta( $customer_id, 'billing_state',      true );
	$billing_postcode_um  = get_user_meta( $customer_id, 'billing_postcode',   true );
	$billing_country_um   = get_user_meta( $customer_id, 'billing_country',    true );

	// Determine if customer already has a billing address on file
        $has_billing_on_file = ( ! empty( $billing_address_1_um ) || ! empty( $billing_city_um ) || ! empty( $billing_postcode_um ) );
	$needs_billing_copy = $is_new_customer || empty( get_user_meta( $customer_id, 'billing_address_1', true ) );


	// 3) Decide billing source:
	//    - If billing exists on file → use it for the order.
	//    - If billing is missing (or brand-new customer) → copy SHIPPING into BILLING for the order.
	if ( $has_billing_on_file && ! $is_new_customer ) {
	    // Use billing from user meta for this order
	    $order->set_billing_first_name( $billing_first_name ?: $first_name );
	    $order->set_billing_last_name(  $billing_last_name  ?: $last_name  );
	    $order->set_billing_phone(      $billing_phone_um   ?: $customer_phone );
	    $order->set_billing_email(      $billing_email_um   ?: $customer_email );

	    $order->set_billing_address_1( $billing_address_1_um );
	    $order->set_billing_address_2( $billing_address_2_um );
	    $order->set_billing_city(      $billing_city_um );
	    $order->set_billing_state(     $billing_state_um );
	    $order->set_billing_postcode(  $billing_postcode_um );
	    $order->set_billing_country(   $billing_country_um ?: 'US' );

	    AAA_V4_Logger::log( 'Billing loaded from user profile.' );
	} else {
	    $order->set_billing_first_name( $first_name );
	    $order->set_billing_last_name(  $last_name );
	    $order->set_billing_phone(      $customer_phone );
	    $order->set_billing_email(      $customer_email );

	    // Copy billing from shipping if new customer or no billing address exists
	    if ( $needs_billing_copy ) {
	        $order->set_billing_address_1( $shipping_address_1 );
	        $order->set_billing_address_2( $shipping_address_2 );
	        $order->set_billing_city(      $shipping_city );
	        $order->set_billing_state(     $shipping_state );
	        $order->set_billing_postcode(  $shipping_postcode );
	        $order->set_billing_country(   $shipping_country );
	        AAA_V4_Logger::log( 'Billing address copied from shipping (new customer or no billing on file).' );
	    } else {
	        $order->set_billing_address_1( $billing_address_1 );
	        $order->set_billing_address_2( $billing_address_2 );
	        $order->set_billing_city(      $billing_city );
	        $order->set_billing_state(     $billing_state );
	        $order->set_billing_postcode(  $billing_postcode );
	        $order->set_billing_country(   $billing_country );
	    }
        }

        // ───────────────────────────────────────────────
        // Coords
        // ───────────────────────────────────────────────
        $lat  = sanitize_text_field( $post_data['aaa_oc_latitude'] ?? '' );
        $lng  = sanitize_text_field( $post_data['aaa_oc_longitude'] ?? '' );
        $flag = sanitize_text_field( $post_data['aaa_oc_coords_verified'] ?? 'no' );

        // 🔴 Enforce verified address requirement
        if ( strtolower($flag) !== 'yes' || empty($lat) || empty($lng) ) {
            echo '<p style="color:red;">Order creation blocked: Address must be verified before placing an order.</p>';
            AAA_V4_Logger::log("Aborting: Address not verified. lat={$lat}, lng={$lng}, flag={$flag}");
            return null;
        }
        // 🔴 End enforcement

        if ( $lat && $lng ) {
            // Save to custom keys
            $order->update_meta_data( 'aaa_oc_latitude', $lat );
            $order->update_meta_data( 'aaa_oc_longitude', $lng );
            $order->update_meta_data( 'aaa_oc_coords_verified', $flag );

            // Save to Delivery Blocks plugin keys
            $order->update_meta_data( '_wc_shipping/aaa-delivery-blocks/latitude', $lat );
            $order->update_meta_data( '_wc_shipping/aaa-delivery-blocks/longitude', $lng );
            $order->update_meta_data( '_wc_shipping/aaa-delivery-blocks/coords-verified', $flag );

            if ( $needs_billing_copy ) {
                $order->update_meta_data( '_wc_billing/aaa-delivery-blocks/latitude', $lat );
                $order->update_meta_data( '_wc_billing/aaa-delivery-blocks/longitude', $lng );
                $order->update_meta_data( '_wc_billing/aaa-delivery-blocks/coords-verified', $flag );
            }

            AAA_V4_Logger::log( "Coords saved: lat={$lat}, lng={$lng}, verified={$flag}" );
        }

	// ───────────────────────────────────────────────
	// Delivery date/time
	// ───────────────────────────────────────────────
	AAA_V4_Logger::log( 'Step: Saving delivery date/time' );

	$delivery_date_input = sanitize_text_field( $post_data['aaa_delivery_date'] ?? '' );
	$time_from_input     = sanitize_text_field( $post_data['aaa_delivery_time_from'] ?? '' );
	$time_to_input       = sanitize_text_field( $post_data['aaa_delivery_time_to'] ?? '' );

	if ( $delivery_date_input ) {
	    $order->update_meta_data( '_aaa_delivery_date', $delivery_date_input );
	}

	if ( $time_from_input ) {
	    $order->update_meta_data( '_aaa_delivery_time_from', $time_from_input );
	}

	if ( $time_to_input ) {
	    $order->update_meta_data( '_aaa_delivery_time_to', $time_to_input );
	}

	// Add a visible order note for staff
	if ( $delivery_date_input || $time_from_input || $time_to_input ) {
	    $note = trim(
	        sprintf(
	            'Delivery scheduled: %s %s%s',
	            $delivery_date_input ?: '[No Date]',
	            $time_from_input     ?: '',
	            $time_to_input       ? ' – ' . $time_to_input : ''
	        )
	    );
	    $order->add_order_note( $note );
	    AAA_V4_Logger::log( "Delivery note added: {$note}" );
}

        // ───────────────────────────────────────────────
        // Payment method
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( "Step: Setting payment method (raw='{$raw_pm}')" );

        $method_key = strtolower( $raw_pm );
        if ( in_array( $method_key, [ 'cod', 'cash on delivery', 'cash_on_delivery' ], true ) ) {
            $method_key = 'cod';
        }

        $available = WC()->payment_gateways()->payment_gateways();
        if ( $method_key && isset( $available[ $method_key ] ) ) {
            $gateway = $available[ $method_key ];
            $order->set_payment_method( $gateway->id );
            $order->set_payment_method_title( $gateway->get_title() );
            AAA_V4_Logger::log( "Payment method set: {$gateway->id}" );

            $order_id = $order->get_id();
            update_post_meta( $order_id, 'aaa_oc_payment_status', $gateway->id );
        } else {
            AAA_V4_Logger::log( "Unknown payment method: '{$method_key}'" );
        }

	// ───────────────────────────────────────────────
        // Products
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Adding line items' );

        $subtotal = 0;
        foreach ( $post_data['products'] as $product_info ) {
            $product_id    = (int) ( $product_info['product_id'] ?? 0 );
            $quantity      = (int) ( $product_info['quantity'] ?? 1 );
            $special_price = isset( $product_info['special_price'] ) ? floatval( $product_info['special_price'] ) : null;

            if ( $product_id <= 0 || $quantity <= 0 ) { continue; }

            $product = wc_get_product( $product_id );
            if ( ! $product ) { continue; }

            $price      = ( $special_price && $special_price > 0 ) ? $special_price : $product->get_price();
            $line_total = $price * $quantity;
            $subtotal  += $line_total;

            $item = new WC_Order_Item_Product();
            $item->set_product( $product );
            $item->set_quantity( $quantity );
            $item->set_subtotal( $line_total );
            $item->set_total( $line_total );
            $order->add_item( $item );
            AAA_V4_Logger::log( "Added product {$product_id}, qty {$quantity}, price {$price}" );
        }

        // ───────────────────────────────────────────────
        // Cart discounts
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Computing discount' );

        $percent_val   = floatval( $post_data['cart_discount_percent'] ?? 0 );
        $fixed_val     = floatval( $post_data['cart_discount_fixed'] ?? 0 );
        $cart_discount = 0;

        if ( $percent_val > 0 && $subtotal > 0 ) {
            $cart_discount = $subtotal * ( $percent_val / 100 );
        } elseif ( $fixed_val > 0 ) {
            $cart_discount = $fixed_val;
        }

        AAA_V4_Logger::log( "Cart discount computed: {$cart_discount}" );

        if ( $cart_discount > 0 ) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name( 'Cart Discount' );
            $fee->set_amount( -$cart_discount );
            $fee->set_total( -$cart_discount );
            $order->add_item( $fee );
            AAA_V4_Logger::log( "Applied discount fee: -{$cart_discount}" );
        }

        // ───────────────────────────────────────────────
        // Coupon as WC_Order_Item_Coupon
        // ───────────────────────────────────────────────
        if ( $coupon_code ) {
            $coupon = new WC_Coupon( $coupon_code );
            if ( $coupon && $coupon->get_id() ) {
                try {
                    $item = new WC_Order_Item_Coupon();
                    $item->set_props( [
                        'code'         => $coupon->get_code(),
                        'discount'     => 0,
                        'discount_tax' => 0,
                    ] );
                    $order->add_item( $item );
                    AAA_V4_Logger::log( "Coupon added as order item: {$coupon_code}" );
                } catch ( Exception $e ) {
                    AAA_V4_Logger::log( "❌ Coupon add failed: {$e->getMessage()}" );
                }
            } else {
                AAA_V4_Logger::log( "❌ Invalid coupon code: {$coupon_code}" );
            }
        }

        // ───────────────────────────────────────────────
	// Shipping (from POST or fallback to settings)
        // ───────────────────────────────────────────────
	AAA_V4_Logger::log( 'Step: Adding shipping method' );

	$settings         = AAA_V4_Settings::get_settings();
	$default_shipping = $settings['default_shipping_method'] ?? '';
	$chosen_method    = $shipping_method ?: $default_shipping;

	if ( $chosen_method ) {
	    $methods = WC()->shipping()->get_shipping_methods();
	    $method_id = explode( ':', $chosen_method )[0];

	    if ( isset( $methods[ $method_id ] ) ) {
	        $ship_item = new WC_Order_Item_Shipping();
	        $ship_item->set_method_title( $methods[ $method_id ]->get_method_title() );
	        $ship_item->set_method_id( $chosen_method );
	        $ship_item->set_total( 0 );
	        $order->add_item( $ship_item );
	        AAA_V4_Logger::log("Shipping method applied: {$chosen_method}");
	    } else {
	        AAA_V4_Logger::log("❌ Unknown shipping method: {$chosen_method}");
	    }
	} else {
	    AAA_V4_Logger::log('ℹ️ No shipping method selected or configured.');
	}

        // ───────────────────────────────────────────────
        // Finalize
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Finalizing order' );

        $order->calculate_totals();
        $order->update_status( 'pending', 'Order created via Order Creator V4.' );

        if ( ! empty( $post_data['order_notes'] ) ) {
            $order->set_customer_note( sanitize_textarea_field( $post_data['order_notes'] ) );
        }

        wc_reduce_stock_levels( $order->get_id() );
        $order->save();
	

        // ───────────────────────────────────────────────
        // Save profiles
        // ───────────────────────────────────────────────
        AAA_V4_Logger::log( 'Step: Saving profiles & notifications' );
        update_user_meta( $customer_id, 'shipping_first_name', $first_name );
        update_user_meta( $customer_id, 'shipping_last_name', $last_name );
        update_user_meta( $customer_id, 'shipping_address_1', $shipping_address_1 );
        update_user_meta( $customer_id, 'shipping_address_2', $shipping_address_2 );
        update_user_meta( $customer_id, 'shipping_city', $shipping_city );
        update_user_meta( $customer_id, 'shipping_state', $shipping_state );
        update_user_meta( $customer_id, 'shipping_postcode', $shipping_postcode );
        update_user_meta( $customer_id, 'shipping_country', $shipping_country );

        if ( $is_new_customer ) {
            update_user_meta( $customer_id, 'billing_first_name', $first_name );
            update_user_meta( $customer_id, 'billing_last_name', $last_name );
            update_user_meta( $customer_id, 'billing_phone', $customer_phone );
            update_user_meta( $customer_id, 'billing_email', $customer_email );
            update_user_meta( $customer_id, 'billing_address_1', $billing_address_1 );
            update_user_meta( $customer_id, 'billing_address_2', $billing_address_2 );
            update_user_meta( $customer_id, 'billing_city', $billing_city );
            update_user_meta( $customer_id, 'billing_state', $billing_state );
            update_user_meta( $customer_id, 'billing_postcode', $billing_postcode );
            update_user_meta( $customer_id, 'billing_country', $billing_country );
        }

        // ───────────────────────────────────────────────
        // Notifications
        // ───────────────────────────────────────────────
        if ( ! empty( $post_data['send_order_confirmation'] ) ) {
            WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger( $order->get_id() );
        }

        if ( ! empty( $post_data['send_account_email'] ) && $is_new_customer ) {
            wp_new_user_notification( $customer_id, null, 'user' );
        }

        return $order->get_id();
    }
}
