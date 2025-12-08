<?php
/**
 * Order page.
 *
 * All the order functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Order page.
 *
 * All the order functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Order {
	/**
	 * Order page.
	 *
	 * @since 1.0.0
	 * @param object $order order object.
	 * @param int    $driver_id driver user id.
	 * @return html
	 */
	public function lddfw_order_page( $order, $driver_id ) {
		global $lddfw_order_id;

		// Driver permissions.
		$driver_prices_permission   = get_option( 'lddfw_driver_prices_permission', false );
		$driver_prices_permission   = false === $driver_prices_permission || '1' === $driver_prices_permission ? true : false;
		$driver_billing_permission  = get_option( 'lddfw_driver_billing_permission', false );
		$driver_billing_permission  = false === $driver_billing_permission || '1' === $driver_billing_permission ? true : false;
		$driver_whatsapp_permission = get_option( 'lddfw_driver_customer_whatsapp_permission', false );
		$driver_whatsapp_permission = false === $driver_whatsapp_permission || '1' === $driver_whatsapp_permission ? true : false;
		$driver_customer_permission = get_option( 'lddfw_driver_customer_permission', false );
		$driver_customer_permission = false === $driver_customer_permission || '1' === $driver_customer_permission ? true : false;

		$date_format        = lddfw_date_format( 'date' );
		$time_format        = lddfw_date_format( 'time' );
		$order_status       = $order->get_status();
		$email              = $order->get_billing_email();
		$order_status_name  = wc_get_order_status_name( $order_status );
		$date_created       = $order->get_date_created()->format( $date_format );
		$total              = $order->get_total();
		$refund             = $order->get_total_refunded();
		$customer_note      = $order->get_customer_note();
		$payment_method     = $order->get_payment_method_title();
		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name  = $order->get_billing_last_name();
		$billing_phone      = $order->get_billing_phone();

		$billing_country  = $order->get_billing_country();
		$shipping_country = $order->get_shipping_country();

		if ( version_compare( get_option( 'woocommerce_version' ), '5.6.0' ) < 0 ) {
			$shipping_phone          = '';
			$shipping_whatsapp_phone = '';
		} else {
			$shipping_phone          = $order->get_shipping_phone();
			$shipping_whatsapp_phone = preg_replace( '/[^0-9]/', '', lddfw_get_international_phone_number( $shipping_country, $shipping_phone ) );
		}

		// Whatsapp phone.
		$whatsapp_phone = preg_replace( '/[^0-9]/', '', lddfw_get_international_phone_number( $billing_country, $billing_phone ) );

		// Get and fromat billing address.
		$billing_array   = $this->lddfw_order_address( 'billing', $order, $lddfw_order_id );
		$billing_address = lddfw_format_address( 'address', $billing_array );

		// Get and fromat shipping address.
		$shipping_array   = $this->lddfw_order_address( 'shipping', $order, $lddfw_order_id );
		$shipping_address = lddfw_format_address( 'address', $shipping_array );

		// Navigation address.
		$shipping_direction_address = lddfw_format_address( 'map_address', $shipping_array );
		// Set address by coordinates.
		$coordinates = $this->lddfw_order_shipping_address_coordinates( $order );
		if ( '' !== $coordinates ) {
			$shipping_direction_address = $coordinates;
		}

		$store          = new LDDFW_Store();
		$failed_date    = $order->get_meta( 'lddfw_failed_attempt_date' );
		$delivered_date = $order->get_meta( 'lddfw_delivered_date' );

		$lddfw_google_api_key = get_option( 'lddfw_google_api_key', '' );
		$seller_id            = $store->lddfw_order_seller( $order );
		$store_phone          = $store->lddfw_store_phone( $order, $seller_id );

		$origin = $order->get_meta( 'lddfw_order_origin' );
		if ( '' === $origin ) {
			$origin = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
		}

		// Fix the address format.
		$origin                     = str_replace( '#', '%23', $origin );
		$shipping_direction_address = str_replace( '#', '%23', $shipping_direction_address );

		$html  = '<script>
			let driver_origin = "' . esc_js( $origin ) . '";
			let driver_destination ="' . esc_js( $shipping_direction_address ) . '";
			let driver_travel_mode = "' . esc_js( LDDFW_Driver::get_driver_driving_mode( $driver_id, '' ) ) . '";
			const lddfw_google_api_key = "' . esc_js( $lddfw_google_api_key ) . '";
			let lddfw_order_id = "' . esc_js( $lddfw_order_id ) . '";
			let tracking_timer;
			
			const FULL_DASH_ARRAY = 283;
			const WARNING_THRESHOLD = 10;
			const ALERT_THRESHOLD = 5;
			const COLOR_CODES = {
				info: {
					color: "green"
				},
				warning: {
					color: "orange",
					threshold: WARNING_THRESHOLD
				},
				alert: {
					color: "red",
					threshold: ALERT_THRESHOLD
				}
			};
			let countdown_started = 0;
			let TIME_LIMIT = 0;
			let timePassed = 0;
			let timerInterval = null;
			let remainingPathColor = COLOR_CODES.info.color;
			let tracking_min_text  = "<span class=tracking_min_text>' . esc_js( __( 'Minutes', 'lddfw' ) ) . ' ' . esc_js( __( 'until delivered', 'lddfw' ) ) . '</span>";
		</script>';
		$html .= '<div class="lddfw_page_content">';

		// Map.
		if ( '' !== $lddfw_google_api_key && get_option( 'lddfw_delivered_status', '' ) !== 'wc-' . $order_status ) {
			$html .= '<div id="google_map" style="height:350px;width:100%" class="container-fluid p-0"></div>';
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {
					$html .= '<div id="google_map_direction" style="display:none" class="container">
						<div class="row">
							<div class="col-2" style="max-width: 80px;padding-right: 0px;">
								<div id="lddfw_delivery_counter"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="clock" class="lddfw_tracking_svg svg-inline--fa fa-clock fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg></div>
							</div>
							<div class="col-10">
								<div id="lddfw_total_route"></div>
								<div id="lddfw_delivery_counter_text"></div>
							</div>
							<div id="lddfw_directions-panel-lightbox" class="lddfw_lightbox" style="display:none">
								<div class="lddfw_lightbox_wrap">
									<div class="container">
										<div class="row">
											<div class="col-12" >
											<a href="#" class="lddfw_lightbox_close">×</a>
												<h2>' . esc_html( __( 'Direction', 'lddfw' ) ) . '</h2>
												<div id="lddfw_directions-origin"></div>
												<div id="lddfw_directions-panel-direction"></div>
												<div id="lddfw_directions-destination"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				';
				}
			}
		}

		$html .= '
	<div class="container" id="lddfw_order">
		<div class="row">
			<div class="col-12">';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
 
				// Initialize note HTML.
				$lddfw_delivery_note_to_driver = $order->get_meta( '_lddfw_delivery_note_to_driver' );
				  
				// Get claim permission.
				$orders                  = new LDDFW_Orders();
				$driver_claim_permission = $orders->lddfw_claim_orders_permission__premium_only( $driver_id, $order );
				$unassigned_note = '';
				if ( $driver_claim_permission ) {
				
					// Retrieve unassigned driver information.
					$unassigned_data = $order->get_meta( 'lddfw_unassigned', true );

					if ( ! empty( $unassigned_data ) && is_array( $unassigned_data ) ) {
						$last_unassigned_date = null;

						foreach ( $unassigned_data as $unassigned ) {
							if ( intval( $unassigned['driver_id'] ) === intval( $driver_id ) ) {
								// Check if this unassigned date is the latest.
								if ( $last_unassigned_date === null || strtotime( $unassigned['date'] ) > strtotime( $last_unassigned_date ) ) {
									$last_unassigned_date = date( $date_format . ' ' . $time_format, strtotime( $unassigned['date'] ) ) ;
								}
							}
						}

						if ( $last_unassigned_date ) {
							$unassigned_note = '<p>' . sprintf(
								esc_html__( 'You previously unassigned this order on %s.', 'lddfw' ),
								esc_html( $last_unassigned_date )
							) . '</p>';
						}
					}
 
				}

				// Store Note to the Driver.
				if ( '' !== $lddfw_delivery_note_to_driver || '' !== $unassigned_note ) {
					$html .= '<div class="alert alert-info"><strong>' . esc_html( __( 'Store note:', 'lddfw' ) ) . '</strong><br>';
					if ( '' !== $lddfw_delivery_note_to_driver ) {
						$html .= '<p>' . esc_html( $lddfw_delivery_note_to_driver ) . '</p>';
					}
					if ( '' !== $unassigned_note ) {
						$html .= $unassigned_note;
					}
					$html .= '</div>';
				}
 
			}
		}

			$html .= '</div>
			<div class="col-12">';

		// Order info.
		$html .= '	<div class="lddfw_box">
					<h3 class="lddfw_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="info-circle" class="svg-inline--fa fa-info-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"></path></svg>
					' . esc_html( __( 'Info', 'lddfw' ) ) . '</h3>
					<div class="row">
					<div class="col-12">
						<p id="lddfw_order_date">' . esc_html( __( 'Date', 'lddfw' ) ) . ': ' . $date_created . '</p>
					</div>
					<div class="col-12">
						<p id="lddfw_order_status">' . esc_html( __( 'Status', 'lddfw' ) ) . ': ' . $order_status_name . '</p>
					</div>';

		if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $order_status && '' !== $failed_date ) {
			$html .= '<div class=\'col-12\'>
						<p id=\'lddfw_order_status_date\'>' . esc_html( __( 'Failed date', 'lddfw' ) ) . ': ' . date( $date_format . ' ' . $time_format, strtotime( $failed_date ) ) . '</p>
			 		  </div>';
		}

		if ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $order_status && '' !== $delivered_date ) {
			$html .= '<div class="col-12">
						<p id="lddfw_order_status_date">' . esc_html( __( 'Delivered date', 'lddfw' ) ) . ': ' . date( $date_format . ' ' . $time_format, strtotime( $delivered_date ) ) . '</p>
			 </div>';
		}

		if ( '' !== $payment_method ) {
			$html .= '<div class="col-12">
						<p id="lddfw_order_payment_method">'
				. esc_html( __( 'Payment method', 'lddfw' ) ) . ': ' . $payment_method .
				'</p>
					</div>';
		}

		$formatted_total = lddfw_price( $driver_prices_permission, wc_price( $total, array( 'currency' => $order->get_currency() ) ) );
		if ( '' !== $refund && '0' !== strval( $refund ) ) {
			$formatted_total  = '<strike>' . $formatted_total . '</strike>';
			$formatted_total .= ' ' . lddfw_price( $driver_prices_permission, wc_price( $total - $refund, array( 'currency' => $order->get_currency() ) ) );
		}

		if ( true === $driver_prices_permission ) {
			$html .= '<div class="col-12">
						<p id="lddfw_order_total">'
			. esc_html( __( 'Total', 'lddfw' ) ) . ': ' . $formatted_total .
			'</p>
					</div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( has_filter( 'lddfw_driver_order_page_info' ) ) {
					$html .= apply_filters( 'lddfw_driver_order_page_info', $order );
				}
			}
		}
		
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
								'terms'    => array( esc_html__( 'Order - Info', 'lddfw' ), 'Order - Info' ),
							),
						),
					)
				);
				// Print custom fields.
				$custom_fields = lddfw_order_custom_fields__premium_only( $lddfw_order_id, $custom_fields_array );
				if ( '' !== $custom_fields ) {
					$html .= '<div class = "col-12">' . $custom_fields . '</div>';
				}
			}
		}

		// Store phone number for the free plugin.
		if ( lddfw_is_free() ) {
			if ( '' !== $store_phone ) {
				$html .= '<div class="col-12 mt-2">
							<a class="btn btn-block btn-secondary"  href="tel:' . esc_attr( $store_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg> ' . esc_html( __( 'Call Dispatch', 'lddfw' ) ) . '</a>
						</div>';
			}
		}

		$html .= '</div>
			</div>';

		// Shipping address.
		$html .= '<div id="lddfw_shipping_address" class="lddfw_box">
					<h3 class="lddfw_title">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker-alt" class="svg-inline--fa fa-map-marker-alt fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path></svg> ' .
					esc_html( __( 'Shipping Address', 'lddfw' ) ) . '</h3>' . $shipping_address;

		if ( '' !== $shipping_phone ) {
			$html .= '<br><span class="lddfw_label">' . esc_html( __( 'Phone', 'lddfw' ) ) . ': ' . $shipping_phone . '</span>';
		}

		// Print coordinates.
		if ( '' !== $coordinates ) {
			$html .= '<br><span>' . __( 'Coordinates:', 'lddfw' ) . ' ' . esc_html( $coordinates ) . '</span>';
		}

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
								'terms'    => array( esc_html__( 'Order - Shipping Address', 'lddfw' ), 'Order - Shipping Address' ),
							),
						),
					)
				);
				// Print custom fields.
				$custom_fields = lddfw_order_custom_fields__premium_only( $lddfw_order_id, $custom_fields_array );
				if ( '' !== $custom_fields ) {
					$html .= '<br>' . $custom_fields;
				}
			}
		}

		if ( '' !== $shipping_phone ) {
			$shipping_phone_class = true === $driver_whatsapp_permission ? 'col-6 mt-2' : 'col-12';
			$html                .= '<div class="row" ><div class="' . esc_attr( $shipping_phone_class ) . '">
							<a class="btn shipping_phone btn-secondary btn-block" href="tel:' . esc_attr( $shipping_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg> ' . esc_html( __( 'Call', 'lddfw' ) ) . '</a>
						</div>';
			if ( true === $driver_whatsapp_permission ) {
				$html .= '<div class="' . esc_attr( $shipping_phone_class ) . '">
											<a class="btn whatsapp_phone btn-secondary btn-block" href="https://wa.me/' . esc_attr( $shipping_whatsapp_phone ) . '" target="_blank" ><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="whatsapp" class="svg-inline--fa fa-whatsapp fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg> ' . esc_html( __( 'WhatsApp', 'lddfw' ) ) . '</a>
										</div>
									';
			}
							$html .= '</div>';
		}

		// Map button.
		if ( '' === $lddfw_google_api_key ) {
			$html .= '<div class="row" id="lddfw_navigation_buttons">
										<div class="col-12  mt-2">
											<a class="btn btn-secondary btn-block" href="https://www.google.com/maps/search/?api=1&query=' . $shipping_direction_address . '">
											<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marked-alt" class="svg-inline--fa fa-map-marked-alt fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M288 0c-69.59 0-126 56.41-126 126 0 56.26 82.35 158.8 113.9 196.02 6.39 7.54 17.82 7.54 24.2 0C331.65 284.8 414 182.26 414 126 414 56.41 357.59 0 288 0zm0 168c-23.2 0-42-18.8-42-42s18.8-42 42-42 42 18.8 42 42-18.8 42-42 42zM20.12 215.95A32.006 32.006 0 0 0 0 245.66v250.32c0 11.32 11.43 19.06 21.94 14.86L160 448V214.92c-8.84-15.98-16.07-31.54-21.25-46.42L20.12 215.95zM288 359.67c-14.07 0-27.38-6.18-36.51-16.96-19.66-23.2-40.57-49.62-59.49-76.72v182l192 64V266c-18.92 27.09-39.82 53.52-59.49 76.72-9.13 10.77-22.44 16.95-36.51 16.95zm266.06-198.51L416 224v288l139.88-55.95A31.996 31.996 0 0 0 576 426.34V176.02c0-11.32-11.43-19.06-21.94-14.86z"></path></svg> ' . esc_html( __( 'Map', 'lddfw' ) ) . '</a>
										</div>
									</div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				$lddfw_driver_navigation_app = get_user_meta( $driver_id, 'lddfw_driver_navigation_app', true );
				$lddfw_navigation_app        = ( '' !== $lddfw_driver_navigation_app ) ? $lddfw_driver_navigation_app : get_option( 'lddfw_navigation_app', '' );

				// Navigation to customer button.
				$html .= '<div class="lddfw_map_buttons" >';

				if ( '' === $lddfw_navigation_app || 'wase' === $lddfw_navigation_app ) {
					$navigation_address = rawurlencode( $shipping_direction_address );
					$waze_link          = 'https://waze.com/ul?q=' . $navigation_address . '&nevigate=yes';
					if ( '' !== $coordinates ) {
						$waze_link = 'https://waze.com/ul?ll=' . $navigation_address . '&nevigate=yes';
					}
					$html .= '<a class="lddfw_navigation btn btn-secondary  btn-block" target="_blank" href="' . $waze_link . '"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="waze" class="svg-inline--fa fa-waze fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M502.17 201.67C516.69 287.53 471.23 369.59 389 409.8c13 34.1-12.4 70.2-48.32 70.2a51.68 51.68 0 0 1-51.57-49c-6.44.19-64.2 0-76.33-.64A51.69 51.69 0 0 1 159 479.92c-33.86-1.36-57.95-34.84-47-67.92-37.21-13.11-72.54-34.87-99.62-70.8-13-17.28-.48-41.8 20.84-41.8 46.31 0 32.22-54.17 43.15-110.26C94.8 95.2 193.12 32 288.09 32c102.48 0 197.15 70.67 214.08 169.67zM373.51 388.28c42-19.18 81.33-56.71 96.29-102.14 40.48-123.09-64.15-228-181.71-228-83.45 0-170.32 55.42-186.07 136-9.53 48.91 5 131.35-68.75 131.35C58.21 358.6 91.6 378.11 127 389.54c24.66-21.8 63.87-15.47 79.83 14.34 14.22 1 79.19 1.18 87.9.82a51.69 51.69 0 0 1 78.78-16.42zM205.12 187.13c0-34.74 50.84-34.75 50.84 0s-50.84 34.74-50.84 0zm116.57 0c0-34.74 50.86-34.75 50.86 0s-50.86 34.75-50.86 0zm-122.61 70.69c-3.44-16.94 22.18-22.18 25.62-5.21l.06.28c4.14 21.42 29.85 44 64.12 43.07 35.68-.94 59.25-22.21 64.11-42.77 4.46-16.05 28.6-10.36 25.47 6-5.23 22.18-31.21 62-91.46 62.9-42.55 0-80.88-27.84-87.9-64.25z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a>';
				}
				if ( 'apple_maps' === $lddfw_navigation_app ) {
					$navigation_address = $shipping_direction_address;
					$navigation_address = str_replace( ',', '%2C', $navigation_address );
					$navigation_address = str_replace( '|', '%7C', $navigation_address );
					$navigation_address = str_replace( ' ', '%20', $navigation_address );
					$html              .= '<a class="lddfw_navigation btn btn-secondary  btn-block" target="_blank" href="http://maps.apple.com/?daddr=' . $navigation_address . '&dirflg=d&t=h"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="apple" class="svg-inline--fa fa-apple fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a>';
				}
				if ( 'google_maps' === $lddfw_navigation_app ) {
					$navigation_address = $shipping_direction_address;
					$navigation_address = str_replace( ',', '%2C', $navigation_address );
					$navigation_address = str_replace( '|', '%7C', $navigation_address );
					$navigation_address = str_replace( ' ', '%20', $navigation_address );
					$html              .= '<a class="lddfw_navigation btn btn-secondary  btn-block" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=' . $navigation_address . '"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" class="svg-inline--fa fa-google fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><path fill="currentColor" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a>';
				}

					$html .= '
				</div>';

			}
		}
		$html .= '</div>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					// Get all order vendors.
					$order_vendors = $store->lddfw_order_seller( $order, true );

					// Pickup type.
					$pickup_type = $store->get_pickup_type( $order );

					// Pickup map address.
					$pickup_map_address = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );

					// Pickup phone.
					$pickup_phone = $store->get_pickup_phone( $order, $seller_id );

					$pickup_title = '<h3 class="lddfw_title">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="store" class="svg-inline--fa fa-store fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 616 512"><path fill="currentColor" d="M602 118.6L537.1 15C531.3 5.7 521 0 510 0H106C95 0 84.7 5.7 78.9 15L14 118.6c-33.5 53.5-3.8 127.9 58.8 136.4 4.5.6 9.1.9 13.7.9 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18.1 20.1 44.3 33.1 73.8 33.1 4.7 0 9.2-.3 13.7-.9 62.8-8.4 92.6-82.8 59-136.4zM529.5 288c-10 0-19.9-1.5-29.5-3.8V384H116v-99.8c-9.6 2.2-19.5 3.8-29.5 3.8-6 0-12.1-.4-18-1.2-5.6-.8-11.1-2.1-16.4-3.6V480c0 17.7 14.3 32 32 32h448c17.7 0 32-14.3 32-32V283.2c-5.4 1.6-10.8 2.9-16.4 3.6-6.1.8-12.1 1.2-18.2 1.2z"></path></svg> ' .
					esc_html( __( 'Pickup Address', 'lddfw' ) ) . '</h3>';

					$html .= '<div id="lddfw_pickup_address" class="lddfw_box">';

					$order_has_multi_vendors = false;
				if ( 'store' === $pickup_type ) {
					// Pickup location is store or vendor location.
					if ( ! empty( $order_vendors ) && is_array( $order_vendors ) && 0 < count( $order_vendors ) ) {
						// Order has multiple vendors.
						$order_has_multi_vendors = true;
						foreach ( $order_vendors as $vendor_id ) {
							$html .= $pickup_title;
							// Pickup address.
							$html .= $store->lddfw_store_name__premium_only( $order, $vendor_id ) . ' <br> ' . $store->lddfw_pickup_address( 'address', $order, $vendor_id );
							// Pickup phone.
							$pickup_phone = $store->get_pickup_phone( $order, $vendor_id );
							if ( '' !== $pickup_phone ) {
								$html .= '<br> ' . esc_html( __( 'Phone', 'lddfw' ) ) . ': ' . $pickup_phone . '<br>';
							}
							$html .= '<div class="row" style="margin-top:10px;margin-bottom:20px"">';
							if ( '' !== $pickup_phone ) {
								$html .= '<div class="col"><a class="btn col btn-block btn-secondary"  href="tel:' . esc_attr( $pickup_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg> ' . esc_html( __( 'Call', 'lddfw' ) ) . '</a></div>';
							}
							// Navigation to pickup address.
							$pickup_map_address = $store->lddfw_pickup_address( 'map_address', $order, $vendor_id );
							if ( '' === $lddfw_navigation_app || 'wase' === $lddfw_navigation_app ) {
								$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="https://waze.com/ul?q=' . $pickup_map_address . '&nevigate=yes"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="waze" class="svg-inline--fa fa-waze fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M502.17 201.67C516.69 287.53 471.23 369.59 389 409.8c13 34.1-12.4 70.2-48.32 70.2a51.68 51.68 0 0 1-51.57-49c-6.44.19-64.2 0-76.33-.64A51.69 51.69 0 0 1 159 479.92c-33.86-1.36-57.95-34.84-47-67.92-37.21-13.11-72.54-34.87-99.62-70.8-13-17.28-.48-41.8 20.84-41.8 46.31 0 32.22-54.17 43.15-110.26C94.8 95.2 193.12 32 288.09 32c102.48 0 197.15 70.67 214.08 169.67zM373.51 388.28c42-19.18 81.33-56.71 96.29-102.14 40.48-123.09-64.15-228-181.71-228-83.45 0-170.32 55.42-186.07 136-9.53 48.91 5 131.35-68.75 131.35C58.21 358.6 91.6 378.11 127 389.54c24.66-21.8 63.87-15.47 79.83 14.34 14.22 1 79.19 1.18 87.9.82a51.69 51.69 0 0 1 78.78-16.42zM205.12 187.13c0-34.74 50.84-34.75 50.84 0s-50.84 34.74-50.84 0zm116.57 0c0-34.74 50.86-34.75 50.86 0s-50.86 34.75-50.86 0zm-122.61 70.69c-3.44-16.94 22.18-22.18 25.62-5.21l.06.28c4.14 21.42 29.85 44 64.12 43.07 35.68-.94 59.25-22.21 64.11-42.77 4.46-16.05 28.6-10.36 25.47 6-5.23 22.18-31.21 62-91.46 62.9-42.55 0-80.88-27.84-87.9-64.25z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
							}
							if ( 'apple_maps' === $lddfw_navigation_app ) {
								$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="http://maps.apple.com/?daddr=' . $pickup_map_address . '&dirflg=d&t=h"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="apple" class="svg-inline--fa fa-apple fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
							}
							if ( 'google_maps' === $lddfw_navigation_app ) {
								$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="https://www.google.com/maps/dir/?api=1&destination=' . $pickup_map_address . '"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" class="svg-inline--fa fa-google fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><path fill="currentColor" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
							}
							$html .= '</div>';
						}
					} else {
						// Pickup location is the main store location or one vendor location.
						$html .= $pickup_title;
						$html .= $store->lddfw_store_name__premium_only( $order, $seller_id ) . ' <br> ' . $store->lddfw_pickup_address( 'address', $order, $seller_id );
					}
				} else {
					// Pickup location is not the store or the vendor location.
					$html .= $pickup_title;
					$html .= $store->lddfw_pickup_address( 'address', $order, $seller_id );
				}

				if ( ! $order_has_multi_vendors ) {

					if ( '' !== $pickup_phone ) {
						$html .= '<br> ' . esc_html( __( 'Phone', 'lddfw' ) ) . ': ' . $pickup_phone  ;
					}

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
									'terms'    => array( esc_html__( 'Order - Pickup', 'lddfw' ), 'Order - Pickup' ),
								),
							),
						)
					);

					// Print custom fields.
					$custom_fields = lddfw_order_custom_fields__premium_only( $lddfw_order_id, $custom_fields_array );
					if ( '' !== $custom_fields ) {
						$html .= '<br>' . $custom_fields;
					}

					$html .= '<div class="row" style="margin-top:10px">';
					if ( '' !== $pickup_phone ) {
						$html .= '<div class="col"><a class="btn col btn-block btn-secondary"  href="tel:' . esc_attr( $pickup_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg> ' . esc_html( __( 'Call', 'lddfw' ) ) . '</a></div>';
					}

					

					// Navigation to pickup address.
					if ( '' === $lddfw_navigation_app || 'wase' === $lddfw_navigation_app ) {
						$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="https://waze.com/ul?q=' . $pickup_map_address . '&nevigate=yes"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="waze" class="svg-inline--fa fa-waze fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M502.17 201.67C516.69 287.53 471.23 369.59 389 409.8c13 34.1-12.4 70.2-48.32 70.2a51.68 51.68 0 0 1-51.57-49c-6.44.19-64.2 0-76.33-.64A51.69 51.69 0 0 1 159 479.92c-33.86-1.36-57.95-34.84-47-67.92-37.21-13.11-72.54-34.87-99.62-70.8-13-17.28-.48-41.8 20.84-41.8 46.31 0 32.22-54.17 43.15-110.26C94.8 95.2 193.12 32 288.09 32c102.48 0 197.15 70.67 214.08 169.67zM373.51 388.28c42-19.18 81.33-56.71 96.29-102.14 40.48-123.09-64.15-228-181.71-228-83.45 0-170.32 55.42-186.07 136-9.53 48.91 5 131.35-68.75 131.35C58.21 358.6 91.6 378.11 127 389.54c24.66-21.8 63.87-15.47 79.83 14.34 14.22 1 79.19 1.18 87.9.82a51.69 51.69 0 0 1 78.78-16.42zM205.12 187.13c0-34.74 50.84-34.75 50.84 0s-50.84 34.74-50.84 0zm116.57 0c0-34.74 50.86-34.75 50.86 0s-50.86 34.75-50.86 0zm-122.61 70.69c-3.44-16.94 22.18-22.18 25.62-5.21l.06.28c4.14 21.42 29.85 44 64.12 43.07 35.68-.94 59.25-22.21 64.11-42.77 4.46-16.05 28.6-10.36 25.47 6-5.23 22.18-31.21 62-91.46 62.9-42.55 0-80.88-27.84-87.9-64.25z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
					}
					if ( 'apple_maps' === $lddfw_navigation_app ) {
						$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="http://maps.apple.com/?daddr=' . $pickup_map_address . '&dirflg=d&t=h"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="apple" class="svg-inline--fa fa-apple fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
					}
					if ( 'google_maps' === $lddfw_navigation_app ) {
						$html .= '<div class="col"><a target="_blank" class="lddfw_navigation col btn btn-secondary  btn-block" href="https://www.google.com/maps/dir/?api=1&destination=' . $pickup_map_address . '"><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" class="svg-inline--fa fa-google fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><path fill="currentColor" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path></svg> ' . esc_html( __( 'Navigate', 'lddfw' ) ) . '</a></div>';
					}

					$html .= '</div>';
				}
				$html .= '</div>';
			}
		}

		// Customer.
		if ( '' !== $billing_first_name && true === $driver_customer_permission ) {
			$html .= '<div class=" lddfw_box">
						<div class="row" id="lddfw_customer">
							<div class="col-12">
								<h3 class="lddfw_title">
								<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg> ' . esc_html( __( 'Customer', 'lddfw' ) ) . '</h3>'
				. $billing_first_name . ' ' . $billing_last_name . '
							</div>';

			if ( '' !== $billing_phone ) {
				$html .= '	<div class="col-12 mt-1">
								<span class="lddfw_label">' . esc_html( __( 'Phone', 'lddfw' ) ) . ': ' . $billing_phone . '</span>
							</div>';
			}
 
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
									'terms'    => array( esc_html__( 'Order - Customer', 'lddfw' ), 'Order - Customer' ),
								),
							),
						)
					);
					// Print custom fields.
					$custom_fields = lddfw_order_custom_fields__premium_only( $lddfw_order_id, $custom_fields_array );
					if ( '' !== $custom_fields ) {
						$html .= '<div class="col-12 mt-2">' . $custom_fields . '</div>';
					}
				}
			}
			
			if ( '' !== $billing_phone ) {
				$billing_phone_class = true === $driver_whatsapp_permission ? 'col-6 mt-2' : 'col-12';
				$html               .= '<div class="' . esc_attr( $billing_phone_class ) . '">
								<a class="btn billing_phone btn-secondary btn-block" href="tel:' . esc_attr( $billing_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg> ' . esc_html( __( 'Call', 'lddfw' ) ) . '</a>
							</div>';
				if ( true === $driver_whatsapp_permission ) {
					$html .= '<div class="' . esc_attr( $billing_phone_class ) . '">
								<a class="btn whatsapp_phone btn-secondary btn-block" href="https://wa.me/' . esc_attr( $whatsapp_phone ) . '" target="_blank" ><svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="whatsapp" class="svg-inline--fa fa-whatsapp fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg> ' . esc_html( __( 'WhatsApp', 'lddfw' ) ) . '</a>
							</div>
						  ';
				}
			}
			$html .= '</div>
					</div>';
		}

		// Note.
		if ( '' !== $customer_note ) {
			$html .= '<div class="alert alert-info"><span>
			<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sticky-note" class="svg-inline--fa fa-sticky-note fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M312 320h136V56c0-13.3-10.7-24-24-24H24C10.7 32 0 42.7 0 56v400c0 13.3 10.7 24 24 24h264V344c0-13.2 10.8-24 24-24zm129 55l-98 98c-4.5 4.5-10.6 7-17 7h-6V352h128v6.1c0 6.3-2.5 12.4-7 16.9z"></path></svg>' . esc_html( __( 'Note', 'lddfw' ) ) . '</span><p>' . $customer_note . '</p></div>';
		}

			// Billing address.
		if ( '' !== $billing_first_name && true === $driver_billing_permission ) {
			$html .= '<div class="lddfw_box">
						<h3 class="lddfw_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="address-card" class="svg-inline--fa fa-address-card fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M528 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm-352 96c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm112 236.8c0 10.6-10 19.2-22.4 19.2H86.4C74 384 64 375.4 64 364.8v-19.2c0-31.8 30.1-57.6 67.2-57.6h5c12.3 5.1 25.7 8 39.8 8s27.6-2.9 39.8-8h5c37.1 0 67.2 25.8 67.2 57.6v19.2zM512 312c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-64c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-64c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16z"></path></svg> ' . esc_html( __( 'Billing Address', 'lddfw' ) ) . '</h3>
						' . $billing_address;
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
									'terms'    => array( esc_html__( 'Order - Billing Address', 'lddfw' ), 'Order - Billing Address' ),
								),
							),
						)
					);
					// Print custom fields.
					$custom_fields = lddfw_order_custom_fields__premium_only( $lddfw_order_id, $custom_fields_array );
					if ( '' !== $custom_fields ) {
						$html .= '<br>' . $custom_fields;
					}
				}
			}
			$html .= '</div>';

		}

		// Items.
		$product_html = $this->lddfw_order_items( $order );
		$html        .= $product_html;

		// Driver.
		$html .= '<div class="lddfw_box">
			<h3 class="lddfw_title">
			<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"></path></svg>
			 ' . esc_html( __( 'Driver', 'lddfw' ) ) . '</h3>
			<div class="row">';

			$lddfw_driverid    = $order->get_meta( 'lddfw_driverid' );
			$user              = get_userdata( $lddfw_driverid );
			$lddfw_driver_name = ( ! empty( $user ) ) ? $user->display_name : '';
			$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );

			// Driver name.
		if ( '' !== $lddfw_driver_name ) {
			$html .= '<div class="col-12">
							<p>'
							. esc_html( __( 'Name', 'lddfw' ) ) . ': ' . esc_html( $lddfw_driver_name ) .
						'</p>
						</div>';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_order_delivery_start = $order->get_meta( '_lddfw_order_delivery_start' );
			}
		}

			// Driver note.
		if ( '' !== $lddfw_driver_note ) {
			$html .= '<div class="col-12">
				<p>
				<span class="lddfw_label">' . esc_html( __( 'Driver note', 'lddfw' ) ) . ': ' . esc_html( $lddfw_driver_note ) . '</span>
				</p>
			</div>';
		}
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				// Signature.
				$lddfw_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
				if ( '' !== $lddfw_order_signature ) {
					$html .= '<div class="col-12">
									<p>'
									. esc_html( __( 'Signature', 'lddfw' ) ) . '<br>' .
									'<img style="display:block;max-width:100%; margin:0 auto" src="' . esc_attr( $lddfw_order_signature ) . '">' .
								'</p>
								</div>';
				}

				// Display Delivery Photo(s).
				$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );

				if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
					$html .= '<div class="col-12"><p>';
				 	// Handle multiple or single images.
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

					$html .= '</p>';
					$html .= '</div>';
				}

			}
		}

			$html .= '</div>
			</div>';

		$html .= '</div>
        	</div>
       	</div></div> ';

		// Action screens.
		$html .= $this->lddfw_order_delivery_screen( $driver_id );
		$html .= $this->lddfw_order_thankyou_screen();

		// Action buttons.
		if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $order_status || get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {
			$html .= '<div class="lddfw_footer_buttons">
					<div class="container">
						<div class="row">';
							$order_status_buttons_style = '';

							// Start delivery button.
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {
					if ( '' === $lddfw_order_delivery_start ) {
						$html                      .= '<div class="col-12 lddfw_delivery_start_button">
																		<a href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" id="lddfw_start_delivery_btn" order_id="' . esc_attr( $lddfw_order_id ) . '" driver_id="' . esc_attr( $driver_id ) . '" class="btn btn-block btn-lg btn-primary">' . esc_html( __( 'Start Delivery', 'lddfw' ) ) . '</a>
																		<button style="display:none" id="lddfw_start_delivery_loading_btn" class="lddfw_loading_btn btn btn-block btn-lg btn-primary" type="button" disabled>
																			<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
																		</button>
																		</div>';
						$html                      .= '<div class="col-12 text-center" id="lddfw_start_delivery_notice" style="display:none">
																					<span id="lddfw_start_delivery_notice_duration"></span>
																					<span id="lddfw_start_delivery_notice_distance"></span>
																			</div>';
						$order_status_buttons_style = 'style="display:none"';
					}
				}
			}

							// Delivery buttons.
							$html .= '<div class="col lddfw_order_status_buttons"  ' . $order_status_buttons_style . ' ><a href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" id="lddfw_delivered_screen_btn" order_status="' . esc_attr( get_option( 'lddfw_delivered_status', '' ) ) . '" order_id="' . esc_attr( $lddfw_order_id ) . '" driver_id="' . esc_attr( $driver_id ) . '" class="btn btn-block btn-lg btn-success">' . esc_html( __( 'Delivered', 'lddfw' ) ) . '</a></div>
					 	 			  <div class="col lddfw_order_status_buttons"  ' . $order_status_buttons_style . '><a href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" id="lddfw_failed_delivered_screen_btn" order_status="' . esc_attr( get_option( 'lddfw_failed_attempt_status', '' ) ) . '" order_id="' . esc_attr( $lddfw_order_id ) . '" driver_id="' . esc_attr( $driver_id ) . '" class="btn  btn-lg  btn-block btn-danger">' . esc_html( __( 'Not Delivered', 'lddfw' ) ) . '</a></div>';

							$html .= '
			 			</div>
					</div>
				</div>
			';
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {

				// Assigned to drivers buttons.
				if ( get_option( 'lddfw_driver_assigned_status', '' ) === 'wc-' . $order_status && intval( $driver_id ) === intval( $lddfw_driverid ) ) {
					$html .= '<div class="lddfw_footer_buttons driver_assigned">
						<div class="container">
							<div class="row">
								<div class="col mb-2 mb-md-0">
									<a href="#" data="' . $lddfw_order_id . '" id="lddfw_order_unassign_button" class="btn btn-lg btn-block btn-secondary">' . esc_html( __( 'Unassign', 'lddfw' ) ) . '</a>
									<a href="#" style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-secondary"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></a>
								</div>
								<div class="col">
									<a href="#" data="' . $lddfw_order_id . '" id="lddfw_order_out_for_delivery_button" class="btn btn-lg btn-block btn-success"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="truck-loading" class="svg-inline--fa fa-truck-loading fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M50.2 375.6c2.3 8.5 11.1 13.6 19.6 11.3l216.4-58c8.5-2.3 13.6-11.1 11.3-19.6l-49.7-185.5c-2.3-8.5-11.1-13.6-19.6-11.3L151 133.3l24.8 92.7-61.8 16.5-24.8-92.7-77.3 20.7C3.4 172.8-1.7 181.6.6 190.1l49.6 185.5zM384 0c-17.7 0-32 14.3-32 32v323.6L5.9 450c-4.3 1.2-6.8 5.6-5.6 9.8l12.6 46.3c1.2 4.3 5.6 6.8 9.8 5.6l393.7-107.4C418.8 464.1 467.6 512 528 512c61.9 0 112-50.1 112-112V0H384zm144 448c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48z"></path></svg> ' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</a>
									<a href="#" style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-success"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></a>
								</div>
							</div>
						</div>
					</div>

					<div id="lddfw_order_out_for_delivery_alert" class="lddfw_lightbox lddfw_order_alert" style="display:none">
						<div class="lddfw_lightbox_wrap">
							<div class="container">
								<div class="row">
									<div class="col-12" id="lddfw_order_out_for_delivery_success" style="display:none" >
										<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
										<h2 class="lddfw_notice">' . esc_html( __( 'The order was successfully marked as out for delivery.', 'lddfw' ) ) . '</h2>
										<a class="btn btn-block btn-lg btn-primary" href="' . lddfw_drivers_page_url( 'lddfw_screen=assign_to_driver' ) . '">' . esc_html( __( 'Add more orders', 'lddfw' ) ) . '</a>
										<a class="btn btn-block btn-lg btn-secondary" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $lddfw_order_id ) . '">' . esc_html( __( 'View order', 'lddfw' ) ) . '</a>
									</div>
									<div class="col-12" id="lddfw_order_out_for_delivery_failed" style="display:none" >
										<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm101.8-262.2L295.6 256l62.2 62.2c4.7 4.7 4.7 12.3 0 17l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L256 295.6l-62.2 62.2c-4.7 4.7-12.3 4.7-17 0l-22.6-22.6c-4.7-4.7-4.7-12.3 0-17l62.2-62.2-62.2-62.2c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l62.2 62.2 62.2-62.2c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17z"></path></svg>
										<div id="lddfw_order_out_for_delivery_failed_alert" class="lddfw_notice"></div>
										<a class="btn btn-block btn-lg btn-secondary" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $lddfw_order_id ) . '">' . esc_html( __( 'Cancel', 'lddfw' ) ) . '</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="lddfw_unassign_alert" class="lddfw_lightbox lddfw_order_alert" style="display:none">
						<div class="lddfw_lightbox_wrap">
							<div class="container">
								<div class="row">
									<div class="col-12" id="lddfw_unassign_success" style="display:none" >
										<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
										<div class="lddfw_notice"></div>
										<a class="btn btn-block btn-lg btn-primary" href="' . lddfw_drivers_page_url( 'lddfw_screen=claim_orders' ) . '">' . esc_html( __( 'Close', 'lddfw' ) ) . '</a>
									</div>
									<div class="col-12" id="lddfw_unassign_failed" style="display:none" >
										<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm101.8-262.2L295.6 256l62.2 62.2c4.7 4.7 4.7 12.3 0 17l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L256 295.6l-62.2 62.2c-4.7 4.7-12.3 4.7-17 0l-22.6-22.6c-4.7-4.7-4.7-12.3 0-17l62.2-62.2-62.2-62.2c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l62.2 62.2 62.2-62.2c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17z"></path></svg>
										<div class="lddfw_notice"></div>
										<a class="btn btn-block btn-lg btn-secondary" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $lddfw_order_id ) . '">' . esc_html( __( 'Cancel', 'lddfw' ) ) . '</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					';
				}

				// Claim button.
				if ( $driver_claim_permission ) {
					$html .= '<div class="lddfw_footer_buttons">
									<div class="container">
										<div class="row">
										<div class="col">
										<a href="#" data="' . $lddfw_order_id . '" id="lddfw_claim_order_button" class="btn btn-lg btn-block btn-success"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="plus" class="svg-inline--fa fa-plus fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"></path></svg> ' . esc_html( __( 'Claim Order', 'lddfw' ) ) . '</a>
										<a href="#" id="lddfw_claim_order_button_loading"  style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-success"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></a>
										</div>
										</div>
									</div>
								</div>
								<div id="lddfw_order_claim_alert" class="lddfw_lightbox lddfw_order_alert" style="display:none">
									<div class="lddfw_lightbox_wrap">
										<div class="container">
											<div class="row">
												<div class="col-12" id="lddfw_order_claim_success" style="display:none" >
													<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
													<div class="lddfw_notice"><h2>' . esc_html( __( 'The Order has been assigned to you.', 'lddfw' ) ) . '</h2></div>
													<a class="btn btn-block btn-lg btn-primary" href="' . lddfw_drivers_page_url( 'lddfw_screen=claim_orders' ) . '">' . esc_html( __( 'Claim more orders', 'lddfw' ) ) . '</a>
													<a class="btn btn-block btn-lg btn-secondary" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $lddfw_order_id ) . '">' . esc_html( __( 'View order', 'lddfw' ) ) . '</a>
												</div>
												<div class="col-12" id="lddfw_order_claim_failed" style="display:none" >
													<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm101.8-262.2L295.6 256l62.2 62.2c4.7 4.7 4.7 12.3 0 17l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L256 295.6l-62.2 62.2c-4.7 4.7-12.3 4.7-17 0l-22.6-22.6c-4.7-4.7-4.7-12.3 0-17l62.2-62.2-62.2-62.2c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l62.2 62.2 62.2-62.2c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17z"></path></svg>
													<div class="lddfw_notice" id="lddfw_order_claim_failed_alert"></div>
													<a class="btn btn-block btn-lg btn-primary" href="' . lddfw_drivers_page_url( 'lddfw_screen=claim_orders' ) . '">' . esc_html( __( 'Claim more orders', 'lddfw' ) ) . '</a>
												</div>
											</div>
										</div>
									</div>
								</div>
					';
				}
			}
		}

		return $html;
	}


	/**
	 * Thank you page.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	private function lddfw_order_thankyou_screen() {
		$html = '<div id="lddfw_thankyou" class="lddfw_lightbox" style="display:none">
		<div class="lddfw_lightbox_wrap">
		<div class="container">
		<form>
			<div class="row">
			<div class="col-12">
			<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
			<h1>' . esc_html( __( 'Thank you!', 'lddfw' ) ) . '</h1>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
					$html .= '<div id="lddfw_next_delivery">
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						' . esc_html( __( 'Loading next delivery', 'lddfw' ) ) . '
						</div>';
			}
		}

			$html .= '<a class="btn btn-block btn-lg btn-secondary lddfw_loader lddfw_loader_fixed" href="' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '">' . esc_html( __( 'View deliveries', 'lddfw' ) ) . '</a>
			</div>
			</div>
			</form>
		</div>
		</div>
		</div>';
		return $html;
	}

	/**
	 * Delivery Photo.
	 *
	 * @since 1.3.0
	 * @return html
	 */
	private function lddfw_order_picture_screen() {
		global $lddfw_order_id;
		$html = '<div style="display:none;position:relative" id="lddfw_delivery_photo" class="lddfw_photo_wrap screen_wrap">';

		if ( lddfw_is_free() ) {
			$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Get proof of delivery.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Add a photo to order.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Customer and admin can view the photo at any time.', 'lddfw' ) );
			$html   .= lddfw_premium_feature_notice_content( $content );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				
				
				$html .= '
				<div class="delivery_header">
					<div class="container">
						<div class="row">
							<div class="col-5 lddfw_column"><h4>' . esc_html( __( 'Photos', 'lddfw' ) ) . '</h4></div>
							<div class="col-2 text-center lddfw_column"><a id="delivery-image-clear" href="#" ><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg></a>  </div>
							<div class="col-5 text-right lddfw_column">
								<div id="lddfw_driver_add_photo_btn" class="lddfw_btn input-group">
									<div class="custom-file">
										<input type="file" class="custom-file-input" id="upload_image" multiple>
										<label class="custom-file-label" for="upload_image">
										<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="plus" class="svg-inline--fa fa-plus fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"></path></svg>
										' . esc_html( __( 'Add', 'lddfw' ) ) . '</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					
					<div class="col-12" id="delivery_image_wrap">';

					
					$order = wc_get_order( $lddfw_order_id );
					$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
				$images_array = array();
				$imagges_json = '';
				if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
					 
				 	// Handle multiple or single images.
					if ( is_array( $lddfw_order_delivery_image_meta ) ) {
					 

						// Sort the meta data by ID in descending order (last added first).
						usort( $lddfw_order_delivery_image_meta, function( $a, $b ) {
							if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
								return 0;
							}
							return $b->id <=> $a->id; // <-- reversed here
						});

						// Multiple images stored as WC_Meta_Data objects.
						$counter = 0;
						foreach ( $lddfw_order_delivery_image_meta as $image ) {
							if ( is_a( $image, 'WC_Meta_Data' ) ) {
								$data = $image->get_data();
								if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
									$html .= '<div class="lddfw_image_item" data-url="'. esc_attr( $data['value'] ) .'" data-index="'.$counter.'"><span class="lddfw_helper"></span><img src='. esc_url( $data['value'] ) .'><span class="lddfw_delete_image" onclick="lddfw_delete_image(this)"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg></span></div>';
									$images_array[] =   $data['value']  ;
									$counter ++;
									if ( $counter > 2 ){
										break;
									}
								}
							}
						}
					} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
						// Single image URL as string.
						$images_array[] =   $lddfw_order_delivery_image_meta  ;
						 $html .= '<div class="lddfw_image_item" data-url="'. esc_attr( $lddfw_order_delivery_image_meta ) .'" data-index="0"><span class="lddfw_helper"></span><img src='. esc_url( $lddfw_order_delivery_image_meta ) .'><span class="lddfw_delete_image" onclick="lddfw_delete_image(this)"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg></span></div>';
					}

					 
					if ( ! empty( $images_array ) ) {
						$imagges_json = json_encode( $images_array );
					}
					
				 
			}
			$html .= '</div>
			<input type="hidden" id="delivery_image" name="delivery_image" value="'. esc_attr( $imagges_json ) .'">
			</div>
			';
		}
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Delivery Signature.
	 *
	 * @since 1.3.0
	 * @return html
	 */
	private function lddfw_order_signature_screen() {
				$html = '
				<div style="display:none" id="lddfw_delivery_signature" class="lddfw_signature_wrap screen_wrap">';

		if ( lddfw_is_free() ) {
			$content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Get proof of delivery.', 'lddfw' ) ) . '
							<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Add a signature to order.', 'lddfw' ) ) . '
							<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Customer and admin can view the signature at any time.', 'lddfw' ) );
			$html   .= lddfw_premium_feature_notice_content( $content );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$html .= '

						<div class="delivery_header">
							<div class="container">
								<div class="row">
									<div class="col-5 lddfw_column"><h4>' . esc_html( __( 'Signature', 'lddfw' ) ) . '</h4> </div>
									<div class="col-2 lddfw_column text-center">
										<a class="signature-clear" href="#" ><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg></a>
									</div>
									<div class="col-5 lddfw_column text-right">
										<a href="#" id="lddfw_driver_add_signature_btn" class="lddfw_btn btn-block btn btn-secondary">
										<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="plus" class="svg-inline--fa fa-plus fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"></path></svg>
										' . esc_html( __( 'Add', 'lddfw' ) ) . '</a>
									</div>
								</div>
							</div>
						</div>

							<div class="row">
								<div class="col-12">
									<p>' . esc_html( __( 'Write the signer\'s name and add a signature.', 'lddfw' ) ) . '</p>
								</div>
								<div class="col-12">
									<div class="form-group">
										<input type="text" class="signature_name form-control" placeholder="' . esc_html( __( 'Name', 'lddfw' ) ) . '" id="signature_name" name="signature_name" >
									</div>
								</div>
								<div class="col-12">
									<div class="signature-wrapper" style="display:none">
										<div id="signature-header" class="container">
											<div class="row">
												<div class="col-6">
												' . esc_html( __( 'Draw signature', 'lddfw' ) ) . '
												</div>
												<div class="col-2 text-right">
													<a class="signature-clear" href="#" ><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-alt" class="svg-inline--fa fa-trash-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"></path></svg></a>
												</div>
												<div class="col-4 text-right">
													<a id="signature-done" class="btn btn-primary" href="#" >' . esc_html( __( 'Done', 'lddfw' ) ) . '</a>
												</div>
											</div>
										</div>
										<canvas id="signature-pad" class="signature-pad"></canvas>
										<div id="signature-line">
											<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>
										</div>
									</div>
									<div id="signature-image" ></div>
								</div>
							</div>
								 ';
			}
		}

				$html .= '</div>
			   ';
		return $html;
	}

	/**
	 * Delivered page.
	 *
	 * @since 1.0.0
	 * @param int $driver_id user id.
	 * @return html
	 */
	private function lddfw_order_delivered_screen( $driver_id ) {

		$lddfw_delivery_dropoff_1 = '';
		$lddfw_delivery_dropoff_2 = '';
		$lddfw_delivery_dropoff_3 = '';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_delivery_dropoff_1 = get_option( 'lddfw_delivery_dropoff_1', '' );
				$lddfw_delivery_dropoff_2 = get_option( 'lddfw_delivery_dropoff_2', '' );
				$lddfw_delivery_dropoff_3 = get_option( 'lddfw_delivery_dropoff_3', '' );
			}
		}

		$html = '
		<form id="lddfw_delivered_form" class="lddfw_delivered_form lddfw_delivery_form screen_wrap">
		<div class="delivery_header">
			<div class="container">
				<div class="row">
					<div class="col-12 lddfw_column"><h4>' . esc_html( __( 'Note', 'lddfw' ) ) . '</h4> </div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
			<p>' . esc_html( __( 'Write your note for the customer.', 'lddfw' ) ) . '</p>';

		if ( '' !== $lddfw_delivery_dropoff_1 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio" checked class="custom-control-input" id="lddfw_delivery_dropoff_1" value="' . esc_attr( $lddfw_delivery_dropoff_1 ) . '" name="lddfw_delivery_dropoff_location">
				<label class="custom-control-label" for="lddfw_delivery_dropoff_1">' . $lddfw_delivery_dropoff_1 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_delivery_dropoff_2 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio" class="custom-control-input" id="lddfw_delivery_dropoff_2" value="' . esc_attr( $lddfw_delivery_dropoff_2 ) . '" name="lddfw_delivery_dropoff_location">
				<label class="custom-control-label" for="lddfw_delivery_dropoff_2">' . $lddfw_delivery_dropoff_2 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_delivery_dropoff_3 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio" class="custom-control-input" id="lddfw_delivery_dropoff_3" value="' . esc_attr( $lddfw_delivery_dropoff_3 ) . '" name="lddfw_delivery_dropoff_location">
				<label class="custom-control-label" for="lddfw_delivery_dropoff_3">' . $lddfw_delivery_dropoff_3 . '</label>
				</div>';
		}

			$html .= '<div class="custom-control custom-radio">
			<input type="radio" checked class="custom-control-input" id="lddfw_delivery_dropoff_other" name="lddfw_delivery_dropoff_location">
			<label class="custom-control-label" for="lddfw_delivery_dropoff_other">' . esc_html( __( 'Other', 'lddfw' ) ) . '</label>
			</div>
			<div id="lddfw_driver_delivered_note_wrap">
			<textarea class="form-control" id="lddfw_driver_delivered_note" placeholder="' . esc_attr( esc_html( __( 'Write your note', 'lddfw' ) ) ) . '" name="driver_note"></textarea>
			</div>
			</div>
			</div>
			</form>
		 ';
		return $html;
	}

	/**
	 * Confirmation page.
	 *
	 * @since 1.0.0
	 * @param int $id div id.
	 * @return html
	 */
	public function lddfw_confirmation_screen( $id ) {
		$html = '<div id="' . $id . '" style="display:none" class="lddfw_confirmation lddfw_lightbox">
			<div class="lddfw_lightbox_wrap">
				<a href="#" class="lddfw_lightbox_close">×</a>
				<div class="container">
					<div class="row">
						<div class="col-12"><h2>' . esc_html( __( 'Are you sure?', 'lddfw' ) ) . '</h2></div>
						<div class="col-6"><button class="lddfw_ok btn btn-lg btn-block btn-primary">' . esc_html( __( 'Ok', 'lddfw' ) ) . '</button></div>
						<div class="col-6"><button class="lddfw_cancel btn btn-lg btn-block btn btn-secondary">' . esc_html( __( 'Cancel', 'lddfw' ) ) . '</button></div>
					</div>
				</div>
			</div>
		</div>';
		return $html;
	}


	/**
	 * Alert page.
	 *
	 * @since 1.0.0
	 * @param int $id div id.
	 * @return html
	 */
	public function alert_screen( $id, $note, $icon ) {
		$html = '<div id="' . $id . '" style="display:none" class="lddfw_alert_screen lddfw_lightbox">
			<div class="lddfw_lightbox_wrap">
				<a href="#" class="lddfw_lightbox_close">×</a>
				<div class="container">
					<div class="row">';

		if ( '' !== $icon ) {
			$html .= '<div class="col-12 text-center">' . $icon . '</h2></div>';
		}
					$html .= '<div class="col-12 text-center"><h2>' . $note . '</h2></div>
						<div class="col-12 text-center"><button class="lddfw_ok btn btn-lg btn-block btn-primary">' . esc_html( __( 'Got it', 'lddfw' ) ) . '</button></div>
					</div>
				</div>
			</div>
		</div>';
		return $html;
	}

	/**
	 * Proof of Delivery bar
	 *
	 * @since 1.3.0
	 * @return html
	 */
	public function lddfw_order_delivery_proof_bar() {
		$html = '<div class="delivery_proof_bar">
			<div class="row">
				<div class="col-4">
					<a href="lddfw_notes_wrap" btn="lddfw_driver_complete_btn" class="active delivery_proof_notes">
					<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="clipboard" class="svg-inline--fa fa-clipboard fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M336 64h-80c0-35.3-28.7-64-64-64s-64 28.7-64 64H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM192 40c13.3 0 24 10.7 24 24s-10.7 24-24 24-24-10.7-24-24 10.7-24 24-24zm144 418c0 3.3-2.7 6-6 6H54c-3.3 0-6-2.7-6-6V118c0-3.3 2.7-6 6-6h42v36c0 6.6 5.4 12 12 12h168c6.6 0 12-5.4 12-12v-36h42c3.3 0 6 2.7 6 6z"></path></svg>
					<br>
					' . esc_html( __( 'Note', 'lddfw' ) ) . '
					</a>
				</div>
				<div class="col-4">
					<a href="lddfw_signature_wrap" btn="lddfw_driver_add_signature_btn" class=" delivery_proof_signature">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="signature" class="svg-inline--fa fa-signature fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M623.2 192c-51.8 3.5-125.7 54.7-163.1 71.5-29.1 13.1-54.2 24.4-76.1 24.4-22.6 0-26-16.2-21.3-51.9 1.1-8 11.7-79.2-42.7-76.1-25.1 1.5-64.3 24.8-169.5 126L192 182.2c30.4-75.9-53.2-151.5-129.7-102.8L7.4 116.3C0 121-2.2 130.9 2.5 138.4l17.2 27c4.7 7.5 14.6 9.7 22.1 4.9l58-38.9c18.4-11.7 40.7 7.2 32.7 27.1L34.3 404.1C27.5 421 37 448 64 448c8.3 0 16.5-3.2 22.6-9.4 42.2-42.2 154.7-150.7 211.2-195.8-2.2 28.5-2.1 58.9 20.6 83.8 15.3 16.8 37.3 25.3 65.5 25.3 35.6 0 68-14.6 102.3-30 33-14.8 99-62.6 138.4-65.8 8.5-.7 15.2-7.3 15.2-15.8v-32.1c.2-9.1-7.5-16.8-16.6-16.2z"></path></svg>
					<br>
					' . esc_html( __( 'Signature', 'lddfw' ) ) . '
					</a>
				</div>
				<div class="col-4">
					<a href="lddfw_photo_wrap" btn="lddfw_driver_add_photo_btn" class="delivery_proof_photo">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="camera" class="svg-inline--fa fa-camera fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 144v288c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V144c0-26.5 21.5-48 48-48h88l12.3-32.9c7-18.7 24.9-31.1 44.9-31.1h125.5c20 0 37.9 12.4 44.9 31.1L376 96h88c26.5 0 48 21.5 48 48zM376 288c0-66.2-53.8-120-120-120s-120 53.8-120 120 53.8 120 120 120 120-53.8 120-120zm-32 0c0 48.5-39.5 88-88 88s-88-39.5-88-88 39.5-88 88-88 88 39.5 88 88z"></path></svg>
					<br>
					' . esc_html( __( 'Photos', 'lddfw' ) ) . '
					</a>
				</div>
			</div>
		</div>';
		return $html;
	}




	/**
	 * Delivery page.
	 *
	 * @since 1.0.0
	 * @param int $driver_id user id.
	 * @return html
	 */
	private function lddfw_order_delivery_screen( $driver_id ) {
		global $lddfw_order_id;

		$html = '<div id="lddfw_delivery_screen" class="lddfw_lightbox" style="display:none">
		<div class="lddfw_lightbox_wrap">
		<a href="#" class="lddfw_lightbox_close">×</a>
		<div class="container">';

		$html .= $this->lddfw_order_signature_screen();
		$html .= $this->lddfw_order_picture_screen();
		$html .= $this->lddfw_order_failed_delivery_screen( $driver_id );
		$html .= $this->lddfw_order_delivered_screen( $driver_id );

		$html .= '</div><div class="lddfw_footer_buttons delivery_proof_buttons">
					<div class="container">
						<div class="row">
							<div class="col-12">
							' . $this->lddfw_order_delivery_proof_bar() . '
							</div>
							<div class="col-6"><a href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" delivery="" id="lddfw_driver_complete_btn" order_id="' . esc_attr( $lddfw_order_id ) . '" driver_id="' . esc_attr( $driver_id ) . '" pod="' . esc_attr( get_option( 'lddfw_proof_of_delivery_one_mandatory', '' ) ) . '"  signature="' . esc_attr( get_option( 'lddfw_proof_of_delivery_signature', '' ) ) . '" photo="' . esc_attr( get_option( 'lddfw_proof_of_delivery_photo', '' ) ) . '" failed_status="' . esc_attr( get_option( 'lddfw_failed_attempt_status', '' ) ) . '"  delivered_status="' . esc_attr( get_option( 'lddfw_delivered_status', '' ) ) . '" class="lddfw_btn btn btn-block btn-lg btn-primary">' . esc_html( __( 'Done', 'lddfw' ) ) . '</a></div>
							<div class="col-6"><a href="#" id="lddfw_driver_cancel_btn" class="lddfw_btn btn btn-block btn-lg btn-secondary">' . esc_html( __( 'Cancel', 'lddfw' ) ) . '</a></div>
			 			</div>
					</div>
				</div>
		</div>
		</div>';

		$html .= $this->lddfw_confirmation_screen( 'lddfw_failed_delivery_confirmation' );
		$html .= $this->lddfw_confirmation_screen( 'lddfw_delivered_confirmation' );

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$html .= $this->alert_screen( 'lddfw_alert_signature', esc_html( __( 'Please take the customer\'s signature as proof of delivery.', 'lddfw' ) ), '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="signature" class="svg-inline--fa fa-signature fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M623.2 192c-51.8 3.5-125.7 54.7-163.1 71.5-29.1 13.1-54.2 24.4-76.1 24.4-22.6 0-26-16.2-21.3-51.9 1.1-8 11.7-79.2-42.7-76.1-25.1 1.5-64.3 24.8-169.5 126L192 182.2c30.4-75.9-53.2-151.5-129.7-102.8L7.4 116.3C0 121-2.2 130.9 2.5 138.4l17.2 27c4.7 7.5 14.6 9.7 22.1 4.9l58-38.9c18.4-11.7 40.7 7.2 32.7 27.1L34.3 404.1C27.5 421 37 448 64 448c8.3 0 16.5-3.2 22.6-9.4 42.2-42.2 154.7-150.7 211.2-195.8-2.2 28.5-2.1 58.9 20.6 83.8 15.3 16.8 37.3 25.3 65.5 25.3 35.6 0 68-14.6 102.3-30 33-14.8 99-62.6 138.4-65.8 8.5-.7 15.2-7.3 15.2-15.8v-32.1c.2-9.1-7.5-16.8-16.6-16.2z"></path></svg>' );
				$html .= $this->alert_screen( 'lddfw_alert_photo', esc_html( __( 'Please take a photo as proof of delivery.', 'lddfw' ) ), '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="camera" class="svg-inline--fa fa-camera fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 144v288c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V144c0-26.5 21.5-48 48-48h88l12.3-32.9c7-18.7 24.9-31.1 44.9-31.1h125.5c20 0 37.9 12.4 44.9 31.1L376 96h88c26.5 0 48 21.5 48 48zM376 288c0-66.2-53.8-120-120-120s-120 53.8-120 120 53.8 120 120 120 120-53.8 120-120zm-32 0c0 48.5-39.5 88-88 88s-88-39.5-88-88 39.5-88 88-88 88 39.5 88 88z"></path></svg>' );
				$html .= $this->alert_screen( 'lddfw_alert_pod', esc_html( __( 'Please take a photo or signature as proof of delivery.', 'lddfw' ) ), '<svg style="margin-right:30px;" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="signature" class="svg-inline--fa fa-signature fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M623.2 192c-51.8 3.5-125.7 54.7-163.1 71.5-29.1 13.1-54.2 24.4-76.1 24.4-22.6 0-26-16.2-21.3-51.9 1.1-8 11.7-79.2-42.7-76.1-25.1 1.5-64.3 24.8-169.5 126L192 182.2c30.4-75.9-53.2-151.5-129.7-102.8L7.4 116.3C0 121-2.2 130.9 2.5 138.4l17.2 27c4.7 7.5 14.6 9.7 22.1 4.9l58-38.9c18.4-11.7 40.7 7.2 32.7 27.1L34.3 404.1C27.5 421 37 448 64 448c8.3 0 16.5-3.2 22.6-9.4 42.2-42.2 154.7-150.7 211.2-195.8-2.2 28.5-2.1 58.9 20.6 83.8 15.3 16.8 37.3 25.3 65.5 25.3 35.6 0 68-14.6 102.3-30 33-14.8 99-62.6 138.4-65.8 8.5-.7 15.2-7.3 15.2-15.8v-32.1c.2-9.1-7.5-16.8-16.6-16.2z"></path></svg>  <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="camera" class="svg-inline--fa fa-camera fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 144v288c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V144c0-26.5 21.5-48 48-48h88l12.3-32.9c7-18.7 24.9-31.1 44.9-31.1h125.5c20 0 37.9 12.4 44.9 31.1L376 96h88c26.5 0 48 21.5 48 48zM376 288c0-66.2-53.8-120-120-120s-120 53.8-120 120 53.8 120 120 120 120-53.8 120-120zm-32 0c0 48.5-39.5 88-88 88s-88-39.5-88-88 39.5-88 88-88 88 39.5 88 88z"></path></svg>' );

			}
		}

		return $html;
	}

	/**
	 * Failed delivery page.
	 *
	 * @since 1.0.0
	 * @param int $driver_id user id.
	 * @return html
	 */
	private function lddfw_order_failed_delivery_screen( $driver_id ) {

		$lddfw_failed_delivery_reason_1 = '';
		$lddfw_failed_delivery_reason_2 = '';
		$lddfw_failed_delivery_reason_3 = '';
		$lddfw_failed_delivery_reason_4 = '';
		$lddfw_failed_delivery_reason_5 = '';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_failed_delivery_reason_1 = get_option( 'lddfw_failed_delivery_reason_1', '' );
				$lddfw_failed_delivery_reason_2 = get_option( 'lddfw_failed_delivery_reason_2', '' );
				$lddfw_failed_delivery_reason_3 = get_option( 'lddfw_failed_delivery_reason_3', '' );
				$lddfw_failed_delivery_reason_4 = get_option( 'lddfw_failed_delivery_reason_4', '' );
				$lddfw_failed_delivery_reason_5 = get_option( 'lddfw_failed_delivery_reason_5', '' );
			}
		}

		$html = '<form id="lddfw_failed_delivery_form" class="lddfw_failed_delivery_form lddfw_delivery_form lddfw_notes_wrap screen_wrap">
		<div class="delivery_header">
			<div class="container">
				<div class="row">
					<div class="col-12 lddfw_column"><h4>' . esc_html( __( 'Note', 'lddfw' ) ) . '</h4> </div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
			<p>' . esc_html( __( 'Write your note for the customer.', 'lddfw' ) ) . '</p>';

		if ( '' !== $lddfw_failed_delivery_reason_1 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio"  class="custom-control-input" id="lddfw_delivery_failed_1" value="' . esc_attr( $lddfw_failed_delivery_reason_1 ) . '" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_1">' . $lddfw_failed_delivery_reason_1 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_failed_delivery_reason_2 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio"  class="custom-control-input" id="lddfw_delivery_failed_2" value="' . esc_attr( $lddfw_failed_delivery_reason_2 ) . '" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_2">' . $lddfw_failed_delivery_reason_2 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_failed_delivery_reason_3 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio"  class="custom-control-input" id="lddfw_delivery_failed_3" value="' . esc_attr( $lddfw_failed_delivery_reason_3 ) . '" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_3">' . $lddfw_failed_delivery_reason_3 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_failed_delivery_reason_4 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio"  class="custom-control-input" id="lddfw_delivery_failed_4" value="' . esc_attr( $lddfw_failed_delivery_reason_4 ) . '" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_4">' . $lddfw_failed_delivery_reason_4 . '</label>
				</div>';
		}

		if ( '' !== $lddfw_failed_delivery_reason_5 ) {
			$html .= '<div class="custom-control custom-radio">
				<input type="radio"  class="custom-control-input" id="lddfw_delivery_failed_5" value="' . esc_attr( $lddfw_failed_delivery_reason_5 ) . '" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_5">' . $lddfw_failed_delivery_reason_5 . '</label>
				</div>';
		}

			$html .= '<div class="custom-control custom-radio">
				<input type="radio" checked class="custom-control-input" id="lddfw_delivery_failed_6" name="lddfw_delivery_failed_reason">
				<label class="custom-control-label" for="lddfw_delivery_failed_6">' . esc_html( __( 'Other issues.', 'lddfw' ) ) . '</label>
			</div>
			<div id="lddfw_driver_note_wrap">
				<textarea class="form-control" id="lddfw_driver_note" placeholder="' . esc_attr( esc_html( __( 'Write your note', 'lddfw' ) ) ) . '" name="lddfw_driver_note"></textarea>
			</div>
			</div>
			</div>
			</form>';
		return $html;
	}

	/**
	 * Order items.
	 *
	 * @since 1.0.0
	 * @param object $order order data.
	 * @return html
	 */
	private function lddfw_order_items( $order ) {
		$driver_prices_permission   = get_option( 'lddfw_driver_prices_permission', false );
		$driver_prices_permission   = false === $driver_prices_permission || '1' === $driver_prices_permission ? true : false;
		$driver_products_permission = get_option( 'lddfw_driver_products_permission', false );
		$driver_products_permission = false === $driver_products_permission || '1' === $driver_products_permission ? true : false;

		$items                 = $order->get_items();
		$total                 = $order->get_total();
		$discount_total        = $order->get_discount_total();
		$product_html          = '';
		$weight                = 0;
		$hidden_order_itemmeta = array();

		if ( ! empty( $items ) ) {
			if ( true === $driver_products_permission ) {
				$product_html .= '<div class="lddfw_box">
			<h3 class="lddfw_title"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-cart" class="svg-inline--fa fa-shopping-cart fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M528.12 301.319l47.273-208C578.806 78.301 567.391 64 551.99 64H159.208l-9.166-44.81C147.758 8.021 137.93 0 126.529 0H24C10.745 0 0 10.745 0 24v16c0 13.255 10.745 24 24 24h69.883l70.248 343.435C147.325 417.1 136 435.222 136 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-15.674-6.447-29.835-16.824-40h209.647C430.447 426.165 424 440.326 424 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-22.172-12.888-41.332-31.579-50.405l5.517-24.276c3.413-15.018-8.002-29.319-23.403-29.319H218.117l-6.545-32h293.145c11.206 0 20.92-7.754 23.403-18.681z"></path></svg> ' . esc_html( __( 'Products', 'lddfw' ) ) . '</h3>
			<table class="table lddfw_order_products_tbl" >
			<tbody>
			<tr>
				<th align="center" >' . esc_html( __( 'Item', 'lddfw' ) ) . '</th>
				<td></td>
				<th align="center" class="lddfw_total_col" >';
				if ( true === $driver_prices_permission ) {
					$product_html .= esc_html( __( 'Total', 'lddfw' ) );
				}
				$product_html .= '</th>
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
						'_wc_cog_item_cost',
						'_wc_cog_item_total_cost',
						'_wcfmmp_order_item_processed',
						'method_slug',
						'vendor_id',
					)
				);
				$hidden_order_itemmeta = is_array( $hidden_order_itemmeta ) ? $hidden_order_itemmeta : array();

				foreach ( $items as $item_id  => $item_data ) {

					if ( has_filter( 'lddfw_skip_order_item' ) ) {
						// Allow developers to skip specific items.
						$skip_item = apply_filters( 'lddfw_skip_order_item', false, $item_id, $item_data, $order );
						if ( $skip_item ) {
							continue; // Skip this item if the filter returns true.
						}
					}
 
					$product_id               = $item_data['product_id'];
					$variation_id             = $item_data['variation_id'];
					$product_description      = '';
					$product_sort_description = '';
					$product                  = false;
					$product_image            = '';
					$product_sku              = '';
					if ( null !== $product_id && 0 !== $product_id ) {
						if ( 0 !== $variation_id ) {
							$product = wc_get_product( $variation_id );
							if ( false !== $product ) {
								$product_description      = $product->get_description();
								$product_image            = $product->get_image();
								$product_sku              = $product->get_sku();
								$product_sort_description = $product->get_short_description();
							}
						} else {
							$product = wc_get_product( $product_id );
							if ( false !== $product ) {
								$product_description      = $product->get_description();
								$product_sort_description = $product->get_short_description();
								$product_image            = $product->get_image();
								$product_sku              = $product->get_sku();
							}
						}
					}

					$item_name     = $item_data['name'];
					$item_quantity = wc_get_order_item_meta( $item_id, '_qty', true );
					$item_total    = wc_get_order_item_meta( $item_id, '_line_total', true );
					$item_subtotal = wc_get_order_item_meta( $item_id, '_line_subtotal', true );

					if ( false !== $product ) {
						if ( ! $product->is_virtual() ) {
							if ( is_numeric( $product->get_weight() ) && is_numeric( $item_quantity ) ) {
								$weight += $product->get_weight() * $item_quantity;
							}
						}
					}

					$discount = '';
					if ( $item_subtotal > $item_total ) {
						$discount = '<br>' . ( $item_subtotal - $item_total ) . ' discount';
					}
					$unit_price = $item_total / $item_quantity;

					$product_html .= '<tr class="lddfw_items">
				<td colspan="2">
					<div class="lddfw_product_line">';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->is_plan( 'premium', true ) ) {
							// Product image.
							$product_html .= $product_image;
							// Product SKU.
							if ( '' !== $product_sku ) {
								$product_sku = '<br>' . esc_html( __( 'SKU:', 'lddfw' ) ) . ' ' . $product_sku;
							}
						}
					}

					$formatted_item_subtotal = lddfw_price( $driver_prices_permission, wc_price( $item_subtotal, array( 'currency' => $order->get_currency() ) ) );

					$product_html .= $item_name . $product_sku . '<br>x ' . $item_quantity;
					// Add Meta data to product.
					$meta_data = $item_data->get_formatted_meta_data( '' );
					if ( ! empty( $meta_data ) ) {
						$product_html .= '<div class="item-variation">';
						foreach ( $meta_data as $meta_id => $meta ) {
							if ( in_array( $meta->key, $hidden_order_itemmeta, true ) || ( '' !== $meta->display_key && '_' === $meta->display_key[0] ) ) {
								continue;
							}
							$product_html .= wp_kses_post( $meta->display_key ) . ': ' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '<br>';
						}
						$product_html .= '</div>';
					}

					$product_html .= '</div>';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->is_plan( 'premium', true ) ) {

							$product_html .= '<div class="lddfw_lightbox lddfw_product_lightbox" style="display:none">
										<div class="lddfw_lightbox_wrap">
											<div class="container">
												<a href="#" class="lddfw_lightbox_close">×</a>
												<div class="row">
													<div class="col-12"><div class="lddfw_product_image">' . $product_image . '</div></div>
													<div class="col-12"><h2 class="lddfw_product_title">' . $item_name . '</h2></div>
													<div class="col-12"><p class="lddfw_product_sku">' . wp_strip_all_tags( $product_sku ) . '</p></div>
													<div class="col-12"><div class="lddfw_product_short_description">' . wc_format_content( wp_kses( force_balance_tags( $product_sort_description ), lddfw_allowed_html() ) ) . '</div></div>
													<div class="col-12"><div class="lddfw_product_description">' . wc_format_content( wp_kses( force_balance_tags( $product_description ), lddfw_allowed_html() ) ) . '</div></div>
												</div>
											</div>
										</div>
									</div>
								';

						}
					}

					$product_html .= '</td>
		<td class="lddfw_total_col">' . $formatted_item_subtotal . '</td>
		</tr>';
				}
				$product_html .= '</table></div>';
			}

			$product_html .= '<table class="table lddfw_order_total_tbl">';

			if ( $weight > 0 ) {
				$product_html .= '<tr><th colspan="2">' . esc_html( __( 'Weight', 'lddfw' ) ) . '</th> <td class="lddfw_total_col">' . $weight . esc_attr( get_option( 'woocommerce_weight_unit' ) ) . '</td>';
			}

			if ( '' !== $discount_total && true === $driver_prices_permission ) {
				$formatted_discount_total = lddfw_price( $driver_prices_permission, wc_price( $discount_total, array( 'currency' => $order->get_currency() ) ) );
				$product_html            .= '<tr><th colspan="2">' . esc_html( __( 'Discount', 'lddfw' ) ) . '</th> <td class="lddfw_total_col">-' . $formatted_discount_total . '</td>';
			}
			foreach ( $order->get_items( 'shipping' ) as $item_id => $line_item ) {
				// Get the data in an unprotected array.
				$shipping_data      = $line_item->get_data();
				$shipping_name      = $shipping_data['name'];
				$shipping_meta      = $shipping_data['meta_data'];
				$shipping_total     = $shipping_data['total'];
				$shipping_method_id = $shipping_data['method_id'];
				$shipping_meta_html = '';

				$local_pick_up = ( 'local_pickup' === $shipping_method_id || 'pickup_location' === $shipping_method_id ) ? '( ' . esc_html( __( 'Local pickup', 'lddfw' ) ) . ' )' : '';

				foreach ( $shipping_meta as $meta_id => $meta_line_item ) {
					// Hide item meta.
					if ( in_array( $meta_line_item->key, $hidden_order_itemmeta, true ) ) {
						continue;
					}
					$shipping_meta_html .= '<br>' . $meta_line_item->key . ': ';
					if ( is_array( $meta_line_item->value ) ) {
						foreach ( $meta_line_item->value as $shipping_meta_value ) {
							$shipping_meta_html .= $shipping_meta_value;
						}
					} else {
						$shipping_meta_html .= $meta_line_item->value;
					}
				}

				$formatted_shipping_total = lddfw_price( $driver_prices_permission, wc_price( $shipping_total, array( 'currency' => $order->get_currency() ) ) );

				$product_html .= '<tr class=\'lddfw_items\'>
		<th colspan=\'2\'>' . esc_html( __( 'Shipping', 'lddfw' ) ) . '<br><i>' . esc_html( __( 'via', 'lddfw' ) ) . ' ' . $shipping_name . ' ' . $local_pick_up . ' ' . $shipping_meta_html . '</i></th>
		<td class=\'lddfw_total_col\'>' . $formatted_shipping_total . '</td>
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
				$formatted_fee_total = lddfw_price( $driver_prices_permission, wc_price( $fee_total, array( 'currency' => $order->get_currency() ) ) );
				$product_html       .= '<tr class="lddfw_items">
		<th colspan="2">' . $fee_name . $feemeta_html . '</th>
		<td class="lddfw_total_col">' . $formatted_fee_total . '</td>
		</tr>';
			}

			if ( '' !== $total && true === $driver_prices_permission ) {

				$formatted_total = lddfw_price( $driver_prices_permission, wc_price( $total, array( 'currency' => $order->get_currency() ) ) );
				$product_html   .= '<tr> <th colspan="2">' . __( 'Total', 'lddfw' ) . '</th><td class="lddfw_total_col">' . $formatted_total . '</td>';

				$refund = $order->get_total_refunded();
				if ( '' !== $refund ) {

					$formatted_refund    = lddfw_price( $driver_prices_permission, wc_price( $refund, array( 'currency' => $order->get_currency() ) ) );
					$formatted_net_total = lddfw_price( $driver_prices_permission, wc_price( ( $total - $refund ), array( 'currency' => $order->get_currency() ) ) );

					$product_html .= '<tr style="color:#ca0303"> <th colspan="2">' . __( 'Refund', 'lddfw' ) . '</th><td class="lddfw_total_col">-' . $formatted_refund . '</td>';
					$product_html .= '<tr> <th colspan="2">' . __( 'Net Total', 'lddfw' ) . '</th><td class="lddfw_total_col">' . $formatted_net_total . '</td>';

				}
			}

			$product_html .= '</tbody></table>';
			return $product_html;
		}
	}



	/**
	 * Add image to order.
	 *
	 * @since 1.3.0
	 * @param string $image image data.
	 * @param string $type image type.
	 * @param int    $order_id order id.
	 * @return boolean
	 */
	public static function lddfw_add_image_to_order__premium_only( $image, $type, $order_id ) {
		 
		// Handle single image (original functionality)
		$image_url = self::upload_single_image__premium_only( $image, $type, $order_id );
		if ( $image_url ) {
			$order = wc_get_order( $order_id );
			if ( 'delivery_image' === $type ) {
				$order->add_meta_data( 'lddfw_order_last_' . $type, $image_url );
			
			} else {
				$order->update_meta_data( 'lddfw_order_last_' . $type, $image_url );
			
			}
			$order->add_meta_data( 'lddfw_order_' . $type, $image_url );
			$order->save();
			return $image_url ;
		}
		
		return false;
	}
	
	/**
	 * Helper function to upload a single image.
	 *
	 * @since 1.3.0
	 * @param string $image image data.
	 * @param string $type image type.
	 * @param int    $order_id order id.
	 * @return string|false URL of uploaded image or false on failure
	 */
	private static function upload_single_image__premium_only( $image, $type, $order_id ) {
		$upload_dir  = wp_upload_dir();
		$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

		$pos  = strpos( $image, ';' );
		if ( $pos === false ) {
			return false; // Invalid image data
		}
		
		$mime = explode( ':', substr( $image, 0, $pos ) )[1];
		$filename = '';

		if ( 'image/png' === $mime ) {
			$image    = str_replace( 'data:image/png;base64,', '', $image );
			$filename = $type . '.png';
		}
		if ( 'image/jpeg' === $mime ) {
			$image    = str_replace( 'data:image/jpeg;base64,', '', $image );
			$filename = $type . '.jpg';

		}
		if ( 'image/gif' === $mime ) {
			$image    = str_replace( 'data:image/gif;base64,', '', $image );
			$filename = $type . '.gif';
		}
		
		if ( empty( $filename ) ) {
			return false; // Unsupported image type
		}

		$image           = str_replace( ' ', '+', $image );
		$decoded         = base64_decode( $image );
		$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
		$file_path       = $upload_path . $hashed_filename;

		$image_upload = file_put_contents( $file_path, $decoded );
		
		if ( $image_upload === false ) {
			return false;
		}
		
		return $upload_dir['url'] . '/' . $hashed_filename;
	}

	/**
	 * Get order address.
	 *
	 * @since 1.7.4
	 * @param string $type address type.
	 * @param object $order order object.
	 * @param int    $orderid order number.
	 * @return string
	 */
	public function lddfw_order_address( $type, $order, $orderid ) {

		$billing_address_1  = $order->get_billing_address_1();
		$billing_address_2  = $order->get_billing_address_2();
		$billing_city       = $order->get_billing_city();
		$billing_postcode   = $order->get_billing_postcode();
		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name  = $order->get_billing_last_name();
		$billing_company    = $order->get_billing_company();
		$billing_country    = $order->get_billing_country();
		$billing_state      = self::lddfw_states( $billing_country, $order->get_billing_state() );
		if ( '' !== $billing_country ) {
			$billing_country = WC()->countries->countries[ $billing_country ];
		}

		if ( 'shipping' === $type ) {
			$shipping_first_name = $order->get_shipping_first_name();
			$shipping_last_name  = $order->get_shipping_last_name();
			$shipping_address_1  = $order->get_shipping_address_1();
			$shipping_address_2  = $order->get_shipping_address_2();
			$shipping_city       = $order->get_shipping_city();
			$shipping_postcode   = $order->get_shipping_postcode();
			$shipping_company    = $order->get_shipping_company();
			$shipping_country    = $order->get_shipping_country();
			$shipping_state      = self::lddfw_states( $shipping_country, $order->get_shipping_state() );
			if ( '' !== $shipping_country ) {
				$shipping_country = WC()->countries->countries[ $shipping_country ];
			}
		}

		// Add Extra checkout fields for brazil.
		if ( in_array( 'woocommerce-extra-checkout-fields-for-brazil', LDDFW_PLUGINS, true ) ) {

			if ( 'shipping' === $type ) {
				// Add shipping number to address.
				$shipping_number = $order->get_meta( '_shipping_number' );
				if ( '' !== $shipping_number && false !== $shipping_number ) {
					$shipping_address_1 .= ' ' . $shipping_number;
				}
			}

			// Add shipping number to address.
			$billing_number = $order->get_meta( '_billing_number' );
			if ( '' !== $billing_number && false !== $billing_number ) {
				$billing_address_1 .= ' ' . $billing_number;
			}
		}

		if ( 'shipping' === $type ) {
			/**
			 * If shipping info is missing if show the billing info.
			 */
			if ( '' === $shipping_first_name && '' === $shipping_address_1 ) {
				$shipping_first_name = $billing_first_name;
				$shipping_last_name  = $billing_last_name;
				$shipping_address_1  = $billing_address_1;
				$shipping_address_2  = $billing_address_2;
				$shipping_city       = $billing_city;
				$shipping_state      = $billing_state;
				$shipping_postcode   = $billing_postcode;
				$shipping_country    = $billing_country;
				$shipping_company    = $billing_company;
			}

			$array = array(
				'first_name' => $shipping_first_name,
				'last_name'  => $shipping_last_name,
				'company'    => $shipping_company,
				'street_1'   => $shipping_address_1,
				'street_2'   => $shipping_address_2,
				'city'       => $shipping_city,
				'zip'        => $shipping_postcode,
				'country'    => $shipping_country,
				'state'      => $shipping_state,
			);
		}

		if ( 'billing' === $type ) {
			$array = array(
				'first_name' => $billing_first_name,
				'last_name'  => $billing_last_name,
				'company'    => $billing_company,
				'street_1'   => $billing_address_1,
				'street_2'   => $billing_address_2,
				'city'       => $billing_city,
				'zip'        => $billing_postcode,
				'country'    => $billing_country,
				'state'      => $billing_state,
			);
		}
		return $array;
	}

	/**
	 * Get the state.
	 *
	 * @since 1.5.0
	 * @param string $country country code.
	 * @param string $state state name/code.
	 * @return string
	 */
	public static function lddfw_states( $country, $state ) {
		$result = '';
		// Show state for USA.
		if ( 'US' === $country || 'United States (US)' === $country ) {
			$result = $state;
		} elseif ( 'CL' === $country || 'Chile' === $country ) {
			if ( in_array( 'comunas-de-chile-para-woocommerce', LDDFW_PLUGINS, true ) ) {
				// Get chile states.
				if ( function_exists( 'comunas_de_chile' ) ) {
					$chile_states = comunas_de_chile( array() );
					if ( is_array( $chile_states ) ) {
						if ( array_key_exists( 'CL', $chile_states ) ) {
							if ( array_key_exists( $state, $chile_states['CL'] ) ) {
								$result = $chile_states['CL'][ $state ];
							}
						}
					}
				}
			}
		}
		return $result;
	}


	/**
	 * Get order shipping coordinates.
	 *
	 * @since 1.7.7
	 * @param object $order order object.
	 * @return string
	 */
	public function lddfw_order_shipping_address_coordinates( $order ) {
		$coordinates = '';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( has_filter( 'lddfw_order_shipping_address_coordinates' ) && '' === $coordinates ) {
					$coordinates = apply_filters( 'lddfw_order_shipping_address_coordinates', $order, $coordinates );
				}
			}
		}
		return $coordinates;
	}

	 
/**
 * Delete a specific delivery image from a WooCommerce order.
 *
 * This function removes an image URL from the order meta (typically used to track delivery photos),
 * deletes the corresponding file from the server, and returns whether any change occurred.
 *
 * @param string $image_url_to_delete The full URL of the image to be deleted.
 * @param string $type                The image type (optional, reserved for future use like 'signature', 'photo', etc).
 * @param int    $order_id            The ID of the WooCommerce order.
 *
 * @return bool True if the image meta or file was successfully deleted, false otherwise.
 */
public static function lddfw_delete_image_from_order__premium_only( $image_url_to_delete, $type, $order_id ) {
    // Validate required parameters
    if ( empty( $image_url_to_delete ) || empty( $order_id ) ) {
        return false;
    }

    // Get the WooCommerce order object
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }

    // Meta key where images are stored; optionally use $type to support multiple image types
    $meta_key = 'lddfw_order_last_delivery_image';

    // Get all image meta values (could be an array of WC_Meta_Data objects)
    $existing_images = $order->get_meta( $meta_key, false );
    $updated = false;

    // If it's an array (likely multiple images), remove the one matching the target URL
    if ( is_array( $existing_images ) ) {
        $new_images = array();

        foreach ( $existing_images as $meta ) {
            if ( is_a( $meta, 'WC_Meta_Data' ) ) {
                $data = $meta->get_data();

                // Keep only images that are not the one we're deleting
                if ( isset( $data['value'] ) && $data['value'] !== $image_url_to_delete ) {
                    $new_images[] = $data['value'];
                } else {
                    // Image matched and will be removed
                    $updated = true;
                }
            }
        }

        // Replace the meta with the cleaned image list
        $order->delete_meta_data( $meta_key );
        foreach ( $new_images as $url ) {
            $order->add_meta_data( $meta_key, $url );
        }
        $order->save();
    }

    // Prepare to delete the physical image file from the server
    $upload_dir     = wp_upload_dir();
    $upload_baseurl = $upload_dir['baseurl'];
    $upload_basedir = $upload_dir['basedir'];

    // Convert URL to relative path, then to absolute path on the filesystem
    if ( strpos( $image_url_to_delete, $upload_baseurl ) === 0 ) {
        $relative_path = str_replace( $upload_baseurl, '', $image_url_to_delete );
        $absolute_path = $upload_basedir . $relative_path;

        // Delete the file if it exists
        if ( file_exists( $absolute_path ) ) {
            if ( @unlink( $absolute_path ) ) {
                $updated = true;
            }
        }
    }

    // Return true if any update (meta or file delete) was made
    return $updated;
}


}
