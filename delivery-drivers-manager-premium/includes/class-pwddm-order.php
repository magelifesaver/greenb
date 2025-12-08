<?php
/**
 * Order page.
 *
 * All the order functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Order page.
 *
 * All the order functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Order {


	/**
	 * Update order.
	 *
	 * @since 1.0.0
	 * @param object $order order object.
	 * @param int    $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_order_update( $pwddm_orderid, $pwddm_manager_id, $pwddm_orders_status, $pwddm_driver_id ) {
		$result = 0;
		$order  = wc_get_order( $pwddm_orderid );
		$store  = new LDDFW_Store();
		// Update order status.
		if ( '' !== $pwddm_orders_status ) {
			$order->update_status( $pwddm_orders_status, __( 'Order status has been changed by the manager.', 'pwddm' ) );
		}
		// Assign driver to order.
			$driver_manager = get_user_meta( $pwddm_driver_id, 'pwddm_manager', true );
		if ( strval( $driver_manager ) === strval( $pwddm_manager_id ) ) {
			$driver = new LDDFW_Driver();
			$driver->assign_delivery_driver( $pwddm_orderid, $pwddm_driver_id, 'store' );
		}
			$result = '1';
			$error  = __( 'Order successfully updated.', 'pwddm' );
			echo "{\"result\":\"$result\",\"error\":\"$error\"}";
	}



	/**
	 * Order_form_post
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_order_form_post( $order, $manager_id ) {

		if ( isset( $_POST['pwddm_wpnonce'] ) && isset( $_GET['pwddm_orderid'] ) ) {
				$order_id = sanitize_text_field( wp_unslash( $_GET['pwddm_orderid'] ) );
				$nonce    = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				$pwddm_action = ( isset( $_POST['pwddm_action'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_action'] ) ) : '';
				if ( '' != $pwddm_action ) {
					$order = wc_get_order( $order_id );

					// Update order Status.
					if ( 'mark_driver_assigned' === $pwddm_action ) {
						$order->update_status( get_option( 'lddfw_driver_assigned_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
					} elseif ( 'mark_out_for_delivery' === $pwddm_action ) {
						$order->update_status( get_option( 'lddfw_out_for_delivery_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
					} elseif ( 'mark_failed' === $pwddm_action ) {
						$order->update_status( get_option( 'lddfw_failed_attempt_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
					} elseif ( 'mark_delivered' === $pwddm_action ) {
						$order->update_status( get_option( 'lddfw_delivered_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
					} elseif ( 'mark_processing' === $pwddm_action ) {
						$order->update_status( get_option( 'lddfw_processing_status', '' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
					}
				}

				// Assign drivers to order.
				$driver               = new LDDFW_Driver();
				$pwddm_assign_drivers = ( isset( $_POST['pwddm_assign_drivers'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_assign_drivers'] ) ) : '';
				if ( '-1' === $pwddm_assign_drivers || '' === $pwddm_assign_drivers ) {
					// Delete drivers.
					$order->delete_meta_data( 'lddfw_driverid' );
					lddfw_update_sync_order( $order_id, 'lddfw_driverid', '0' );
					$order->save();
				} elseif ( '' !== $pwddm_assign_drivers ) {
					// Assign a driver to order.
					$driver->assign_delivery_driver( $order_id, $pwddm_assign_drivers, 'store' );
				}
			}
				header( 'Location: ' . pwddm_manager_page_url( 'pwddm_screen=order&pwddm_orderid=' . $order_id ) );
		}
	}



	/**
	 * Order page.
	 *
	 * @since 1.0.0
	 * @param object $order order object.
	 * @param int    $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_order_page( $order, $manager_id ) {

		 // Handle orders form post.
		$this->pwddm_order_form_post( $order, $manager_id );

		global $pwddm_order_id;
		$date_format = lddfw_date_format( 'date' );
		$time_format = lddfw_date_format( 'time' );

		$order_status      = $order->get_status();
		$order_status_name = wc_get_order_status_name( $order_status );
		$date_created      = $order->get_date_created()->format( $date_format );
		$discount_total    = $order->get_discount_total();
		$shipping_total    = $order->get_shipping_total();
		$total             = $order->get_total();

		$shipping_address_map_url = $order->get_shipping_address_map_url();
		$billing_first_name       = $order->get_billing_first_name();
		$billing_last_name        = $order->get_billing_last_name();
		$billing_company          = $order->get_billing_company();
		$billing_address_1        = $order->get_billing_address_1();
		$billing_address_2        = $order->get_billing_address_2();
		$billing_city             = $order->get_billing_city();

		$billing_country = $order->get_billing_country();
		$billing_state   = LDDFW_Order::lddfw_states( $billing_country, $order->get_billing_state() );
		if ( '' !== $billing_country ) {
			$billing_country = WC()->countries->countries[ $billing_country ];
		}
		$billing_postcode = $order->get_billing_postcode();

		$billing_phone       = $order->get_billing_phone();
		$shipping_first_name = $order->get_shipping_first_name();
		$shipping_last_name  = $order->get_shipping_last_name();
		$shipping_company    = $order->get_shipping_company();
		$shipping_address_1  = $order->get_shipping_address_1();
		$shipping_address_2  = $order->get_shipping_address_2();
		$shipping_city       = $order->get_shipping_city();

		$shipping_postcode = $order->get_shipping_postcode();
		$shipping_country  = $order->get_shipping_country();
		$shipping_state    = LDDFW_Order::lddfw_states( $shipping_country, $order->get_shipping_state() );
		if ( '' !== $shipping_country ) {
			$shipping_country = WC()->countries->countries[ $shipping_country ];
		}

		$customer_note  = $order->get_customer_note();
		$payment_method = $order->get_payment_method();

		// Format billing address.
		$billing_full_name = $billing_first_name . ' ' . $billing_last_name . '<br>';
		if ( '' !== $billing_company ) {
			$billing_full_name .= $billing_company . '<br>';
		}

		$billing_address = $billing_address_1 . ' ';
		if ( '' !== $billing_address_2 ) {
			$billing_address .= ', ' . $billing_address_2 . ' ';
		}
		$billing_address .= '<br>' . $billing_city . ' ';
		if ( '' !== $billing_state ) {
			$billing_address .= $billing_state . ' ';
		}
		if ( '' !== $billing_postcode ) {
			$billing_address .= $billing_postcode . ' ';
		}
		if ( '' !== $billing_country ) {
			$billing_address .= '<br>' . $billing_country;
		}

		if ( isset( $shipping_address_1 ) && '' !== $shipping_address_1 ) {
			// Format shipping address.
			$shipping_full_name = $shipping_first_name . ' ' . $shipping_last_name . ' <br>';
			if ( '' !== $shipping_company ) {
				$shipping_full_name .= $shipping_company . ' <br>';
			}

			$shipping_address = $shipping_address_1 . ' ';
			if ( '' !== $shipping_address_2 ) {
				$shipping_address .= ',' . $shipping_address_2 . ' ';
			}
			$shipping_address .= '<br>' . $shipping_city . ' ';
			if ( '' !== $shipping_state ) {
				$shipping_address .= $shipping_state . ' ';
			}
			if ( '' !== $shipping_postcode ) {
				$shipping_address .= $shipping_postcode . ' ';
			}
			if ( '' !== $shipping_country ) {
				$shipping_address .= '<br>' . $shipping_country;
			}
		} else {
			$shipping_full_name = $billing_full_name;
			$shipping_address   = $billing_address;
		}

		// Waze buttons.
		$shipping_direction_address = str_replace( '<br>', '', $shipping_address );
		$shipping_direction_address = str_replace( ',', '', $shipping_direction_address );
		$navigation_address         = rawurlencode( $shipping_direction_address );
		$shipping_direction_address = str_replace( '  ', ' ', $shipping_direction_address );
		$shipping_direction_address = str_replace( ' ', '+', $shipping_direction_address );

		$store         = new LDDFW_Store();
		$store_address = $store->lddfw_store_address( 'map_address' );
		$origin        = $order->get_meta( 'pwddm_order_origin' );

		$failed_date    = $order->get_meta( 'pwddm_failed_attempt_date' );
		$delivered_date = $order->get_meta( 'pwddm_delivered_date' );

		$html = '<div class="pwddm_page_content">';

		if ( '' === $origin ) {
			$origin = $store_address;
		}

		$pwddm_dispatch_phone_number = get_option( 'lddfw_dispatch_phone_number', '' );

		$pwddm_driverid    = $order->get_meta( 'lddfw_driverid' );
		$user              = get_userdata( $pwddm_driverid );
		$pwddm_driver_name = ( ! empty( $user ) ) ? $user->display_name : '';
		$pwddm_driver_note = $order->get_meta( 'lddfw_driver_note' );

		$driver_manager_id = '';
		if ( '' != $pwddm_driverid && '-1' != $pwddm_driverid ) {
			$driver_manager_id = get_user_meta( $pwddm_driverid, 'pwddm_manager', true );
		}
		$pwddm_manager_drivers = get_user_meta( $manager_id, 'pwddm_manager_drivers', true );

		if ( ( '' === $pwddm_driverid || '-1' === $pwddm_driverid )
		||
		(
				( ( '0' === $pwddm_manager_drivers || '' === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
				||
				( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
				||
				( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
			)
		 ) {

			$html .= '
	<div class="container" id="pwddm_order">
		<div class="row">
		<div class="col-12"><div class="pwddm_alert_wrap"></div></div>
		<div class="col-12 col-md-8 order-2 order-md-1">';
			// Orders info.
			$html .= ' <div class="pwddm_box">
					<h3 class="pwddm_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="info-circle" class="svg-inline--fa fa-info-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path></svg> 
					' . esc_html( __( 'Info', 'pwddm' ) ) . '</h3>
					<div class="row">
					<div class="col-12">
						<p id="pwddm_order_date">' . esc_html( __( 'Date', 'pwddm' ) ) . ': ' . $date_created . '</p>
					</div> 
					<div class="col-12">
						<p id="pwddm_order_status">' . esc_html( __( 'Status', 'pwddm' ) ) . ': ' . $order_status_name . '</p>
					</div>';

			if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $order_status && '' !== $failed_date ) {
				$html .= '<div class=\'col-12\'>
						<p id=\'pwddm_order_status_date\'>' . esc_html( __( 'Failed date', 'pwddm' ) ) . ': ' . date( $date_format . ' ' . $time_format, strtotime( $failed_date ) ) . '</p>
			 		  </div>';
			}

			if ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $order_status && '' !== $delivered_date ) {
				$html .= '<div class="col-12">
						<p id="pwddm_order_status_date">' . esc_html( __( 'Delivered date', 'pwddm' ) ) . ': ' . date( $date_format . ' ' . $time_format, strtotime( $delivered_date ) ) . '</p>
			 </div>';
			}

			if ( '' !== $payment_method ) {
				$html .= '<div class="col-12">
						<p id="pwddm_order_payment_method">'
				. esc_html( __( 'Payment method', 'pwddm' ) ) . ': ' . $payment_method .
				'</p>
					</div>';
			}
			$html .= '<div class="col-12">
						<p id="pwddm_order_total">'
			. esc_html( __( 'Total', 'pwddm' ) ) . ': ' . wc_price( $total, array( 'currency' => $order->get_currency() ) ) .
			'</p>
					</div>';

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {
					/**
					 * Get custom fields query
					 */
					$custom_fields_array = get_posts(
						array(
							'numberposts' => -1,
							'post_type'   => 'lddfw_custom_fields',
							'post_status' => 'publish',
							'tax_query'   => array(
								array(
									'taxonomy' => 'lddfw_custom_fields_sections',
									'field'    => 'name',
									'terms'    => array( esc_html__( 'Order - Info', 'pwddm' ), 'Order - Info' ),
								),
							),
						)
					);
					// Print custom fields.
					$custom_fields = pwddm_order_custom_fields__premium_only( $pwddm_order_id, $custom_fields_array );
					if ( '' !== $custom_fields ) {
						$html .= '<div class = "col-12">' . $custom_fields . '</div>';
					}
				}
			}

			$html .= '</div>
			</div>';

			// Shipping address.
			$html .= '<div id="pwddm_shipping_address" class="pwddm_box">
					<h3 class="pwddm_title">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker-alt" class="svg-inline--fa fa-map-marker-alt fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path></svg> ' .
					esc_html( __( 'Shipping Address', 'pwddm' ) ) . '</h3>' . $shipping_full_name . ' ' . $shipping_address;

			if ( pwddm_fs()->is__premium_only() ) {
				if ( pwddm_fs()->is_plan( 'premium', true ) ) {
					/**
					 * Get custom fields query
					 */
					$custom_fields_array = get_posts(
						array(
							'numberposts' => -1,
							'post_type'   => 'pwddm_custom_fields',
							'post_status' => 'publish',
							'tax_query'   => array(
								array(
									'taxonomy' => 'pwddm_custom_fields_sections',
									'field'    => 'name',
									'terms'    => array( esc_html__( 'Order - Shipping Address', 'pwddm' ), 'Order - Shipping Address' ),
								),
							),
						)
					);
					// Print custom fields.
					$custom_fields = pwddm_order_custom_fields__premium_only( $pwddm_order_id, $custom_fields_array );
					if ( '' !== $custom_fields ) {
						$html .= '<br>' . $custom_fields;
					}
				}
			}

			$html .= '</div>';

			// Customer.
			if ( '' !== $billing_first_name ) {
				$html .= '<div class=" pwddm_box">
						<div class="row" id="pwddm_customer">
							<div class="col-12">
								<h3 class="pwddm_title">
								<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg> ' . esc_html( __( 'Customer', 'pwddm' ) ) . '</h3>'
				. $billing_first_name . ' ' . $billing_last_name . '
							</div>';

				if ( '' !== $billing_phone ) {
					$html .= '	<div class="col-12 mt-1">
								<span class="pwddm_label">' . esc_html( __( 'Phone', 'pwddm' ) ) . ': ' . $billing_phone . '</span>
							</div>';
				}
				if ( pwddm_fs()->is__premium_only() ) {
					if ( pwddm_fs()->is_plan( 'premium', true ) ) {
						/**
						 * Get custom fields query
						 */
						$custom_fields_array = get_posts(
							array(
								'numberposts' => -1,
								'post_type'   => 'pwddm_custom_fields',
								'post_status' => 'publish',
								'tax_query'   => array(
									array(
										'taxonomy' => 'pwddm_custom_fields_sections',
										'field'    => 'name',
										'terms'    => array( esc_html__( 'Order - Customer', 'pwddm' ), 'Order - Customer' ),
									),
								),
							)
						);
						// Print custom fields.
						$custom_fields = pwddm_order_custom_fields__premium_only( $pwddm_order_id, $custom_fields_array );
						if ( '' !== $custom_fields ) {
							$html .= '<div class="col-12 mt-2">' . $custom_fields . '</div>';
						}
					}
				}

				$html .= '</div>
					</div>';
			}

			// Note.
			if ( '' !== $customer_note ) {
				$html .= '<div class="alert alert-info"><span>
			<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sticky-note" class="svg-inline--fa fa-sticky-note fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M312 320h136V56c0-13.3-10.7-24-24-24H24C10.7 32 0 42.7 0 56v400c0 13.3 10.7 24 24 24h264V344c0-13.2 10.8-24 24-24zm129 55l-98 98c-4.5 4.5-10.6 7-17 7h-6V352h128v6.1c0 6.3-2.5 12.4-7 16.9z"></path></svg> Note</span><p>' . $customer_note . '</p></div>';
			}

			// Items.
			$product_html = $this->pwddm_order_items( $order );
			$html        .= $product_html;

			// Billing address.
			if ( '' !== $billing_first_name ) {
				$html .= '<div class="pwddm_box">
					<h3 class="pwddm_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="address-card" class="svg-inline--fa fa-address-card fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M528 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm-352 96c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm112 236.8c0 10.6-10 19.2-22.4 19.2H86.4C74 384 64 375.4 64 364.8v-19.2c0-31.8 30.1-57.6 67.2-57.6h5c12.3 5.1 25.7 8 39.8 8s27.6-2.9 39.8-8h5c37.1 0 67.2 25.8 67.2 57.6v19.2zM512 312c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-64c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-64c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16z"></path></svg> ' . esc_html( __( 'Billing Address', 'pwddm' ) ) . '</h3>
					' . $billing_full_name . $billing_address;
				if ( pwddm_fs()->is__premium_only() ) {
					if ( pwddm_fs()->is_plan( 'premium', true ) ) {
						/**
					 * Get custom fields query
					 */
						$custom_fields_array = get_posts(
							array(
								'numberposts' => -1,
								'post_type'   => 'pwddm_custom_fields',
								'post_status' => 'publish',
								'tax_query'   => array(
									array(
										'taxonomy' => 'pwddm_custom_fields_sections',
										'field'    => 'name',
										'terms'    => array( esc_html__( 'Order - Billing Address', 'pwddm' ), 'Order - Billing Address' ),
									),
								),
							)
						);
						// Print custom fields.
						$custom_fields = pwddm_order_custom_fields__premium_only( $pwddm_order_id, $custom_fields_array );
						if ( '' !== $custom_fields ) {
							$html .= '<br>' . $custom_fields;
						}
					}
				}
				$html .= '</div>';

			}

			$user_query = PWDDM_Driver::pwddm_get_drivers( $manager_id, '' );
			$drivers    = $user_query->get_results();

			/**
			 * Set current status names
			 */
			$pwddm_driver_assigned_status_name  = esc_html( __( 'Driver assigned', 'pwddm' ) );
			$pwddm_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'pwddm' ) );
			$pwddm_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'pwddm' ) );
			if ( function_exists( 'wc_get_order_statuses' ) ) {
				$result = wc_get_order_statuses();
				if ( ! empty( $result ) ) {
					foreach ( $result as $key => $status ) {
						switch ( $key ) {
							case get_option( 'lddfw_out_for_delivery_status' ):
								if ( $status !== $pwddm_out_for_delivery_status_name ) {
									$pwddm_out_for_delivery_status_name = $status;
								}
								break;
							case get_option( 'lddfw_failed_attempt_status' ):
								if ( esc_html( __( 'Failed Delivery Attempt', 'pwddm' ) ) !== $status ) {
									$pwddm_failed_attempt_status_name = $status;
								}
								break;
							case get_option( 'lddfw_driver_assigned_status' ):
								if ( $status !== $pwddm_driver_assigned_status_name ) {
									$pwddm_driver_assigned_status_name = $status;
								}
								break;
						}
					}
				}
			}

			$html     .= '</div>
		<div class="col-12 col-md-4 order-1">
		<div class="pwddm_box"> 

		<form method="POST" name="pwddm_order_form" id="pwddm_order_form" action="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=order&pwddm_orderid=' . $pwddm_order_id ) ) . '">
		<input type="hidden" name="pwddm_wpnonce" value="' . wp_create_nonce( 'pwddm-nonce' ) . '">
		

		<div class="row"> 
		<div class="col-12 mb-2">
				<select name="pwddm_action" class="form-select">
				<option value="">' . esc_html( __( 'Choose action', 'pwddm' ) ) . '</option>
				<option value="mark_processing">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Processing', 'pwddm' ) ) . '</option>
				<option value="mark_driver_assigned">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_driver_assigned_status_name . '</option>
				<option value="mark_out_for_delivery">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_out_for_delivery_status_name . ' </option>
				<option value="mark_failed">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_failed_attempt_status_name . '</option>
				<option value="mark_delivered">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Delivered', 'pwddm' ) ) . '</option>
			 ';
				$html .= '
			</select>
		</div>';

			$html .= '<div class="col-12 mb-2">
				<select name="pwddm_assign_drivers" id="pwddm_assign_drivers" class="form-select">
				<option value="">' . __( 'Assign a driver', 'pwddm' ) . '</option>
				<option value="-1">' . __( 'Unassign driver', 'pwddm' ) . '</option>
				';

				$last_availability = '';
			foreach ( $drivers as $driver ) {

				$driver_manager_id = get_user_meta( $driver->ID, 'pwddm_manager', true );
				if ( ( '0' === $pwddm_manager_drivers || '' === strval( $pwddm_manager_drivers ) ) && ( '' === strval( $driver_manager_id ) )
					||
					( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
					||
					( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
					) {

					$driver_name    = $driver->display_name;
					$availability   = get_user_meta( $driver->ID, 'lddfw_driver_availability', true );
					$driver_account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
					$availability   = '1' === $availability ? 'Available' : 'Unavailable';
					$selected       = '';
					if ( intval( $pwddm_driverid ) === $driver->ID ) {
						$selected = 'selected';
					}
					if ( $last_availability !== $availability ) {
						if ( '' !== $last_availability ) {
							$html .= '</optgroup>';
						}
						$html             .= '<optgroup label="' . esc_attr( $availability . ' ' . __( 'drivers', 'pwddm' ) ) . '">';
						$last_availability = $availability;
					}
					if ( '1' === $driver_account || ( '1' != $driver_account && intval( $pwddm_driverid ) === $driver->ID ) ) {
						$html .= '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
					}
				}
			}
				$html .= '</optgroup>';

				$html .= '
			</select>
			</div>
			<div class="col-12">
			<div class="d-grid gap-2 col-12 col-md-6 mx-auto">
			<button type="submit"  class="btn-lg btn btn-block btn-primary">' . esc_attr( __( 'Update', 'pwddm' ) ) . '</button>	
			
				<button style="display: none;margin:0px;" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled="">
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								Loading
								</button>
								</div>
			</div>
			</div>
			</form>
		</div>
		';
			$html     .= '<div class="pwddm_box">
		<h3 class="pwddm_title">
		<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"></path></svg>
		 ' . esc_html( __( 'Driver', 'pwddm' ) ) . '</h3>
		<div class="row">';

			// Driver name.
			if ( '' !== $pwddm_driver_name ) {
				$html .= '<div class="col-12">
						<p>'
							. esc_html( __( 'Driver', 'pwddm' ) ) . ': ' . esc_html( $pwddm_driver_name ) .
						'</p>
					</div>';
			}

			// Driver note.
			if ( '' !== $pwddm_driver_note ) {
				$html .= '<div class="col-12">
			<p>
			<span class="pwddm_label">' . esc_html( __( 'Driver note', 'pwddm' ) ) . ': ' . esc_html( $pwddm_driver_note ) . '</span>
			</p>
		</div>';
			}
			if ( pwddm_fs()->is__premium_only() ) {
				if ( pwddm_fs()->is_plan( 'premium', true ) ) {

					// Signature.
					$pwddm_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
					if ( '' !== $pwddm_order_signature ) {
						$html .= '<div class="col-12">
								<p>'
									. esc_html( __( 'Signature', 'pwddm' ) ) . '<br>' .
									'<img style="display:block;max-width:100%; margin:0 auto" src="' . esc_attr( $pwddm_order_signature ) . '">' .
								'</p>
							</div>';
					}

					// Photo.
					$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
					if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
						$html .= '<div class="col-12">';
						if ( is_array( $lddfw_order_delivery_image_meta ) ) {
							$html .= esc_html__( 'Photos', 'lddfw' ) . '<br>';
	
							// Sort the meta data by ID in descending order (last added first).
							usort( $lddfw_order_delivery_image_meta, function( $a, $b ) {
								if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
									return 0;
								}
								return $b->id <=> $a->id; // <-- reversed here
							});
	
							// Multiple images stored as WC_Meta_Data objects.
							foreach ( $lddfw_order_delivery_image_meta as $image ) {
								if ( is_a( $image, 'WC_Meta_Data' ) ) {
									$data = $image->get_data();
									if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
										$html .= '<img style="display:block;max-width:100%;margin:0 auto 10px auto" src="' . esc_url( $data['value'] ) . '" alt="' . esc_attr__( 'Delivery Image', 'lddfw' ) . '">';
									}
								}
							}
						} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
							// Single image URL as string.
							$html .=  esc_html__( 'Photo', 'lddfw' ) . '<br>';
							$html .= '<img style="display:block;max-width:100%;margin:0 auto" src="' . esc_url( $lddfw_order_delivery_image_meta ) . '" alt="' . esc_attr__( 'Delivery Image', 'lddfw' ) . '">';
						}

								 
						$html .='</div>';
					}
				}
			}

			$html .= '</div>
		</div> 
		</div>
        	</div>
       	</div>';
		} else {
			$html .= '<div class="container"><div class="alert alert-danger">' . esc_html( __( 'You don\'t have access to this order.', 'pwddm' ) ) . '</div></div>';
		}
		   $html .= '</div> ';

		return $html;
	}



	/**
	 * Order items.
	 *
	 * @since 1.0.0
	 * @param object $order order data.
	 * @return html
	 */
	private function pwddm_order_items( $order ) {
		$items          = $order->get_items();
		$total          = $order->get_total();
		$discount_total = $order->get_discount_total();

		if ( ! empty( $items ) ) {

			$product_html = '<div class="pwddm_box">
	<h3 class="pwddm_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-cart" class="svg-inline--fa fa-shopping-cart fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M528.12 301.319l47.273-208C578.806 78.301 567.391 64 551.99 64H159.208l-9.166-44.81C147.758 8.021 137.93 0 126.529 0H24C10.745 0 0 10.745 0 24v16c0 13.255 10.745 24 24 24h69.883l70.248 343.435C147.325 417.1 136 435.222 136 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-15.674-6.447-29.835-16.824-40h209.647C430.447 426.165 424 440.326 424 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-22.172-12.888-41.332-31.579-50.405l5.517-24.276c3.413-15.018-8.002-29.319-23.403-29.319H218.117l-6.545-32h293.145c11.206 0 20.92-7.754 23.403-18.681z"></path></svg> ' . esc_html( __( 'Products', 'pwddm' ) ) . '</h3>
	<div class="table-responsive">
	<table class="table pwddm_order_products_tbl" >
	<tbody>
	<tr>
	<th align="center" >' . esc_html( __( 'Item', 'pwddm' ) ) . '</th>
	<td></td>
	<th align="center" class="pwddm_total_col" >' . esc_html( __( 'Total', 'pwddm' ) ) . '</th>
	</tr>';

			$hidden_order_itemmeta = apply_filters(
				'woocommerce_hidden_order_itemmeta',
				array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'method_id',
					'cost',
					'_reduced_stock',
				)
			);
			foreach ( $items as $item_id  => $item_data ) {

				$product_id          = $item_data['product_id'];
				$variation_id        = $item_data['variation_id'];
				$product_description = '';
				$product             = false;
				$product_image       = '';
				if ( null !== $product_id && 0 !== $product_id ) {
					if ( 0 !== $variation_id ) {
						$product             = wc_get_product( $variation_id );
						$product_description = $product->get_description();
						$product_image       = $product->get_image();
					} else {
						$product             = wc_get_product( $product_id );
						$product_description = $product->get_short_description();
						$product_image       = $product->get_image();
					}
				}

				$item_name     = $item_data['name'];
				$item_quantity = wc_get_order_item_meta( $item_id, '_qty', true );
				$item_total    = wc_get_order_item_meta( $item_id, '_line_total', true );
				$item_subtotal = wc_get_order_item_meta( $item_id, '_line_subtotal', true );

				$discount = '';
				if ( $item_subtotal > $item_total ) {
					$discount = '<br>' . ( $item_subtotal - $item_total ) . ' discount';
				}
				$unit_price = $item_total / $item_quantity;

				$product_html .= '<tr class="pwddm_items"><td colspan="2">';
				if ( pwddm_fs()->is__premium_only() ) {
					if ( pwddm_fs()->is_plan( 'premium', true ) ) {
						$product_html .= $product_image;
					}
				}

				$product_html .= $item_name . '<br>X ' . $item_quantity;
				// Add Meta Date to product.
				if ( $meta_data = $item_data->get_formatted_meta_data( '' ) ) {
					$product_html .= '<div class="item-variation">';
					foreach ( $meta_data as $meta_id => $meta ) {
						if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
							continue;
						}
						$product_html .= wp_kses_post( $meta->display_key ) . ': ' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '<br>';
					}
					$product_html .= '</div>';
				}

				$product_html .= '</td>
		<td class="pwddm_total_col">' . wc_price( $item_subtotal, array( 'currency' => $order->get_currency() ) ) . '</td>
		</tr>';
			}
			$product_html .= '</table></div></div><div class="table-responsive"><table class="table pwddm_order_total_tbl">';
			if ( '' !== $discount_total ) {
				$product_html .= '<tr><th colspan="2">' . esc_html( __( 'Discount', 'pwddm' ) ) . '</th> <td class="pwddm_total_col">-' . wc_price( $discount_total, array( 'currency' => $order->get_currency() ) ) . '</td>';
			}
			foreach ( $order->get_items( 'shipping' ) as $item_id => $line_item ) {
				// Get the data in an unprotected array.
				$shipping_data      = $line_item->get_data();
				$shipping_name      = $shipping_data['name'];
				$shipping_meta      = $shipping_data['meta_data'];
				$shipping_total     = $shipping_data['total'];
				$shipping_meta_html = '';
				foreach ( $shipping_meta as $meta_id => $line_item ) {
					$shipping_meta_html .= '<br>' . $line_item->key . ': ' . $line_item->value;
				}

				$product_html .= '<tr class=\'pwddm_items\'>
		<th colspan=\'2\'>' . esc_html( __( 'Shipping', 'pwddm' ) ) . '<br><i>' . esc_html( __( 'via', 'pwddm' ) ) . ' ' . $shipping_name . $shipping_meta_html . '</i></th>
		<td class=\'pwddm_total_col\'>' . wc_price( $shipping_total, array( 'currency' => $order->get_currency() ) ) . '</td>
		</tr>';
			}

			foreach ( $order->get_items( 'fee' ) as $item_id => $line_item ) {
				$fee_data  = $line_item->get_data();
				$fee_meta  = $fee_data['meta_data'];
				$fee_total = $fee_data['total'];
				$fee_name  = $fee_data['name'];

				$feemeta_html = '';
				foreach ( $fee_meta as $meta_id => $meta_lineitem ) {
					$feemeta_html .= '<br>' . $meta_lineitem->key . ': ' . $meta_lineitem->value;
				}

				$product_html .= '<tr class="pwddm_items">
		<th colspan="2">' . $fee_name . $feemeta_html . '</th>
		<td class="pwddm_total_col">' . wc_price( $fee_total, array( 'currency' => $order->get_currency() ) ) . '</td>
		</tr>';
			}

			if ( '' !== $total ) {
				$product_html .= '<tr> <th colspan="2">' . __( 'Total', 'pwddm' ) . '</th><td class="pwddm_total_col">' . wc_price( $total, array( 'currency' => $order->get_currency() ) ) . '</td>';

				$refund = $order->get_total_refunded();
				if ( '' != $refund ) {
					$product_html .= '<tr style="color:#ca0303"> <th colspan="2">' . __( 'Refund', 'pwddm' ) . '</th><td class="pwddm_total_col">-' . wc_price( $refund, array( 'currency' => $order->get_currency() ) ) . '</td>';
					$product_html .= '<tr> <th colspan="2">' . __( 'Net Total', 'pwddm' ) . '</th><td class="pwddm_total_col">' . wc_price( ( $total - $refund ), array( 'currency' => $order->get_currency() ) ) . '</td>';

				}
			}

			$product_html .= '</tbody></table></div>';
			return $product_html;
		}
	}



}
