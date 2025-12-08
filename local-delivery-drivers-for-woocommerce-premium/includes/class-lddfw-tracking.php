<?php
/**
 * Plugin Tracking.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Plugin Tracking.
 *
 * All the Tracking functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Tracking {

	/**
	 * Tracking page.
	 *
	 * @since 1.4.0
	 * @return html
	 */
	public function tracking_page() {

		$html = '';

		// Get order id by order key.
		$order_key = sanitize_text_field( wp_unslash( get_query_var( 'k' ) ) );
		$order_id  = wc_get_order_id_by_order_key( 'wc_order_' . $order_key );

		$order = wc_get_order( $order_id );
		if ( ! empty( $order ) ) {

			// Get the driver id.
			$driver_id = $order->get_meta( 'lddfw_driverid' );

			// Order Status.
			$order_status = $order->get_status();

			// Check if driver exist.
			if ( '' !== $driver_id ) {

				// Check if order is out for delivery.
				if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {

					// Get permissions to show the driver info.
					$lddfw_driver_photo_permission = get_option( 'lddfw_driver_photo_permission', false );
					$lddfw_driver_name_permission  = get_option( 'lddfw_driver_name_permission', false );
					$lddfw_driver_phone_permission = get_option( 'lddfw_driver_phone_permission', false );
					$photo_permission              = false === $lddfw_driver_photo_permission || '1' === $lddfw_driver_photo_permission ? true : false;
					$name_permission               = false === $lddfw_driver_name_permission || '1' === $lddfw_driver_name_permission ? true : false;
					$phone_permission              = false === $lddfw_driver_phone_permission || '1' === $lddfw_driver_phone_permission ? true : false;
					$driver                        = get_user_by( 'id', $driver_id );
					$driver_name                   = ( ! empty( $driver ) ) ? $driver->display_name : '';
					$driver_billing_phone          = get_user_meta( $driver_id, 'billing_phone', true );

					// ETA.
					$current_date        = date_i18n( 'Y-m-d H:i:s' );
					$delivery_start_date = $order->get_meta( '_lddfw_order_delivery_start' );
					$route               = $order->get_meta( 'lddfw_order_route' );

					$seconds_diff = -1;
					if ( ! empty( $route ) && '' !== $delivery_start_date ) {
						$route_duration_value = $route['duration_value'];
						if ( '' !== $route_duration_value ) {
							$route_date_created  = $route['date_created'];
							$route_duration_text = $route['duration_text'];
							$arrivel_date        = date( 'Y-m-d H:i:s', ( strtotime( date( $delivery_start_date ) ) + $route_duration_value ) );
							$seconds_diff        = strtotime( $arrivel_date ) - strtotime( $current_date );
						}
					}

					$driver = new LDDFW_Driver();
					$route  = new LDDFW_Route();

					$lddfw_google_api_key = get_option( 'lddfw_google_api_key', '' );

					// Get origin/pickup coordinates.
					$origin_coordinates = $route->get_order_pickup_geocode( $order );

					// Set order coordinates.
					$route->set_order_geocode( $order_id );

					// Get order coordinates.
					$geocode_array = $order->get_meta( '_lddfw_address_geocode' );

					$destination_coordinates = '';
					if ( ! empty( $geocode_array ) && is_array( $geocode_array ) ) {
						if ( 'ZERO_RESULTS' === $geocode_array[0] ) {
							// ZERO_RESULTS.
						}
						if ( ! empty( $geocode_array[1] ) ) {
							$destination_coordinates = $geocode_array[1];
						}
					}

					$html = '<div class="lddfw_tracking_content">';

					// Map.
					if ( '' !== $lddfw_google_api_key ) {
						$html .= '<div id="lddfw_map123" class="lddfw_map-main-outer"></div>';
					}

					// Counter.
					$html .= '<div id="lddfw_counter"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="clock" class="lddfw_tracking_svg svg-inline--fa fa-clock fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg></div>';

					// Order status note.
					$html .= '
			<div class="container">
				<div class="row">
					<div class="col-12">
						<div id="lddfw_tracking_order_status" class="text-center" >';
					if ( '' === $delivery_start_date ) {
						$html .= '<h2>' . esc_html( __( 'Your order is out for delivery', 'lddfw' ) ) . '</h2>';
					} else {
						$html .= '<h2>' . esc_html( __( 'Your order is on its way', 'lddfw' ) ) . '</h2>';
					}
						$html .= '</div>
					</div>
				</div>
			</div>';

					// Driver.
					$html .= '
	<div class="container-fluid" id="lddfw_tracking_driver">
		<div class="row">';

					$html .= '
			<div class="col-12">';

					// Driver.
					$html .= '<div class="lddfw_box">
						<div class="row" id="lddfw_driver">';

					// Driver info.
					$driver_info = '';
					if ( true === $name_permission ) {
						$driver_info .= '<div id="lddfw_tracking_driver_name">' . $driver_name . '</div>';
					}

					$lddfw_travel_mode          = get_user_meta( $driver_id, 'lddfw_driver_travel_mode', true );
					$lddfw_driver_vehicle       = get_user_meta( $driver_id, 'lddfw_driver_vehicle', true );
					$lddfw_driver_licence_plate = get_user_meta( $driver_id, 'lddfw_driver_licence_plate', true );
					if ( '' !== $lddfw_driver_vehicle || '' !== $lddfw_driver_licence_plate ) {

						$driver_info .= '<div id="lddfw_tracking_driver_vehicle">';
						$driver_info .= esc_html( $lddfw_driver_vehicle ) . '<br>';
						$driver_info .= esc_html( $lddfw_driver_licence_plate );
						$driver_info .= '</div>';

					}

					// Driver photo.
					$driver_image_url = plugins_url() . '/' . LDDFW_FOLDER . '/public/images/user.png?ver=' . LDDFW_VERSION;
					if ( true === $photo_permission ) {
						$image_id = get_user_meta( $driver_id, 'lddfw_driver_image', true );
						if ( intval( $image_id ) > 0 ) {
							$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
							if ( '' !== $image ) {
								$driver_image_url = $image;
							}
						}
					}

					$html .= '
				<div class="col-9" style="padding-right:0px">
				<div id="lddfw_tracking_driver_image" style="background-image:url(\'' . $driver_image_url . '\')"></div>
			 	' . $driver_info . '</div>';

					// Driver Phone and whatsapp.
					if ( '' !== $driver_billing_phone && true === $phone_permission ) {
						$html .= '<div class="col-3 text-center">
								<a class="btn lddfw_tracking_circle billing_phone btn-secondary btn-block" href="tel:' . esc_attr( $driver_billing_phone ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg></a>
							</div>

						';
					}

					$html .= '		</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		   <script>
		   		let lddfw_map;
		   		let driver_marker;

				let lddfw_nonce = {"nonce":"' . esc_js( wp_create_nonce( 'lddfw-nonce' ) ) . '"};
		   		let lddfw_google_api_key  =  "' . esc_js( $lddfw_google_api_key ) . '";
				const lddfw_map_language = "' . esc_js( lddfw_get_map_language() ) . '";
				let lddfw_order_id = "' . esc_js( $order_id ) . '";
		   		let lddfw_driver_id = "' . esc_js( $driver_id ) . '";
		   		let lddfw_ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
		   		let lddfw_drivers_tracking_timing = "' . esc_js( get_option( 'lddfw_drivers_tracking_timing', '' ) ) . '";
		   		let lddfw_tracking_status = "' . esc_js( get_user_meta( $driver_id, 'lddfw_tracking_status', true ) ) . '";
				let tracking_origin = "' . esc_js( $origin_coordinates ) . '";
				let tracking_destination = "' . esc_js( $destination_coordinates ) . '";
				let tracking_driving_mode = "' . esc_js( $driver->get_driver_driving_mode( $driver_id, '' ) ) . '";
				let tracking_min_text  = "<span class=tracking_min_text>' . esc_js( __( 'Minutes', 'lddfw' ) ) . '<br>' . esc_js( __( 'until delivered', 'lddfw' ) ) . '</span>";
				let lddfw_travel_mode = "' . esc_js( $lddfw_travel_mode ) . '";

				let tracking_timer;
				let tracking_milliseconds = "' . esc_js( $this->get_tracking_interval() ) . '";
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

				
		    </script>';

				} else {
					// Order is not on out for delivery status.
					wp_redirect( home_url() );
					exit;
				}
			} else {
				// No driver.
				wp_redirect( home_url() );
				exit;
			}
		} else {
			// Order not exist.
			wp_redirect( home_url() );
			exit;
		}
		return $html;
	}

	/**
	 * Drivers locations.
	 *
	 * @since 1.4.0
	 * @return json
	 */
	public function lddfw_drivers_locations() {
			$drivers             = LDDFW_Driver::lddfw_get_drivers();
			$json                = '';
			$last_lddfw_driverid = 0;
		if ( ! empty( $drivers ) ) {
			$json = '[';
			foreach ( $drivers as $driver ) {
				$lddfw_driverid = $driver->ID;

				// Get driver availability.
				$availability = get_user_meta( $lddfw_driverid, 'lddfw_driver_availability', true );

				// Get driver account.
				$driver_account = get_user_meta( $lddfw_driverid, 'lddfw_driver_account', true );

				// Get driver tracking status.
				$lddfw_tracking_status = get_user_meta( $lddfw_driverid, 'lddfw_tracking_status', true );

				if ( '1' === $driver_account && $last_lddfw_driverid !== $lddfw_driverid ) {

					// Get driver location from DB.
					$results = $this->lddfw_get_driver_location__premium_only( $lddfw_driverid );

					$lddfw_tracking_latitude  = '';
					$lddfw_tracking_longitude = '';
					$lddfw_tracking_speed     = '';

					if ( ! empty( $results ) ) {
						$lddfw_tracking_latitude  = $results[0]->latitude;
						$lddfw_tracking_longitude = $results[0]->longitude;
						$lddfw_tracking_speed     = $results[0]->speed;
					}

					if ( 0 !== $last_lddfw_driverid ) {
						$json .= ','; }
					$json               .= '{"driver":"' . $lddfw_driverid . '","tracking":"' . $lddfw_tracking_status . '","lat":"' . $lddfw_tracking_latitude . '","long":"' . $lddfw_tracking_longitude . '" , "speed" : "' . $lddfw_tracking_speed . '" }';
					$last_lddfw_driverid = $lddfw_driverid;
				}
				?>
				<?php
			}
				$json .= ']';
		}
		return $json;
	}

	/**
	 * Set driver tracking position.
	 *
	 * @param int    $driver_id driver user id.
	 * @param string $lddfw_latitude latitude.
	 * @param string $lddfw_longitude longitude.
	 * @param string $lddfw_speed speed.
	 * @since 1.4.0
	 * @return int
	 */
	public function lddfw_set_driver_tracking_position( $driver_id, $lddfw_latitude, $lddfw_longitude, $lddfw_speed ) {
		global $wpdb;
		$wpdb->insert(
			"{$wpdb->prefix}lddfw_tracking",
			array(
				'driver_id' => $driver_id,
				'latitude'  => $lddfw_latitude,
				'longitude' => $lddfw_longitude,
				'speed'     => $lddfw_speed,
				'date'      => date_i18n( 'Y-m-d H:i:s' ),
			)
		);
		return 1;
	}

	/**
	 * Set driver tracking status.
	 *
	 * @param int    $driver_id driver user id.
	 * @param string $tracking_status tracking status.
	 * @since 1.4.0
	 * @return int
	 */
	public function lddfw_driver_tracking_status( $driver_id, $tracking_status ) {
		update_user_meta( $driver_id, 'lddfw_tracking_status', $tracking_status );
		return 1;
	}

	/**
	 * Admin tracking screen.
	 *
	 * @since 1.4.0
	 */
	public function lddfw_drivers_panel_script() {
		global $lddfw_out_for_delivery_counter, $lddfw_driver_id;
		?>
		<script>
			jQuery( document ).ready(function() {
				<?php
				// Start watch position.
				if ( 0 < $lddfw_out_for_delivery_counter ) {
					?>
				if ( lddfw_tracking_status == "1" ) {
					<?php
						// Track the driver location if the last tracking time is more then interval.
						$results = $this->lddfw_get_driver_location__premium_only( $lddfw_driver_id );
					if ( ! empty( $results ) ) {
						$lddfw_tracking_date = $results[0]->date;
						$seconds_diff        = -1;
						if ( '' !== $lddfw_tracking_date ) {
							$current_date    = date_i18n( 'Y-m-d H:i:s' );
							$last_track_date = date( 'Y-m-d H:i:s', ( strtotime( date( $lddfw_tracking_date ) ) + (int) $this->get_tracking_interval() / 1000 ) );
							$seconds_diff    = strtotime( $last_track_date ) - strtotime( $current_date );
						}
						if ( $seconds_diff < -1 ) {
							?>
								lddfw_watch_position();
							<?php
						}
					}
					?>
					lddfw_watch_position_start();
				}
			<?php } ?>
});
		</script>
		<?php
	}

	 /**
	  * Get driver_location.
	  *
	  * @param int $driver_id driver user id.
	  * @return array
	  */
	public function lddfw_get_driver_location__premium_only( $driver_id ) {
		 global $wpdb;
		// Get driver location from DB.
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT latitude,longitude,speed,date FROM {$wpdb->prefix}lddfw_tracking WHERE driver_id=%d order by id desc limit 1", $driver_id )
		);
		return $results;
	}


	/**
	 * Delivery tracking.
	 *
	 * @param int $order_id order number.
	 * @return void
	 */
	public function lddfw_delivery_tracking__premium_only( $order_id ) {

		$order                    = wc_get_order( $order_id );
		$order_status             = '';
		$lddfw_tracking_latitude  = '';
		$lddfw_tracking_longitude = '';
		$lddfw_tracking_speed     = '';
		$note                     = '';
		if ( ! empty( $order ) ) {

			// Get the driver id.
			$driver_id = $order->get_meta( 'lddfw_driverid' );

			// Order Status.
			$order_status = $order->get_status();

			$tracking_status = 0;
			$seconds_diff    = -1;

			switch ( 'wc-' . $order_status ) {
				case get_option( 'lddfw_out_for_delivery_status', '' ):
					$note            = esc_attr( __( 'Your order is on its way', 'lddfw' ) );
					$tracking_status = 1;

					// ETA.
					$current_date        = date_i18n( 'Y-m-d H:i:s' );
					$delivery_start_date = $order->get_meta( '_lddfw_order_delivery_start' );
					$route               = $order->get_meta( 'lddfw_order_route' );

					if ( ! empty( $route ) && '' !== $delivery_start_date ) {
						$route_duration_value = $route['duration_value'];
						if ( '' !== $route_duration_value ) {
							$route_date_created  = $route['date_created'];
							$route_duration_text = $route['duration_text'];
							$arrivel_date        = date( 'Y-m-d H:i:s', ( strtotime( date( $delivery_start_date ) ) + $route_duration_value ) );
							$seconds_diff        = strtotime( $arrivel_date ) - strtotime( $current_date );
						}
					}

					break;
				case get_option( 'lddfw_failed_attempt_status', '' ):
					$note            = esc_attr( __( 'Your order is failed to deliver', 'lddfw' ) );
					$tracking_status = 2;
					break;
				case get_option( 'lddfw_delivered_status', '' ):
					$note            = esc_attr( __( 'Your order has been delivered', 'lddfw' ) );
					$tracking_status = 3;
					break;
				case get_option( 'lddfw_driver_assigned_status', '' ):
					$note            = esc_attr( __( 'Your order is now ready and being delivered', 'lddfw' ) );
					$tracking_status = 4;
					break;
			}

			// Check if driver exist.
			if ( '' !== $driver_id ) {
				// Check if order is out for delivery.
				if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {

					 $array = $this->lddfw_get_driver_location__premium_only( $driver_id );
					if ( ! empty( $array ) ) {
						$lddfw_tracking_latitude  = $array[0]->latitude;
						$lddfw_tracking_longitude = $array[0]->longitude;
						$lddfw_tracking_speed     = $array[0]->speed;
					}
				}
			}
		}

		$result = '{ "order" : "' . $order_id . '" , "tracking_status" : "' . $tracking_status . '", "driver_latitude" : "' . $lddfw_tracking_latitude . '", "driver_longitude" : "' . $lddfw_tracking_longitude . '", "driver_speed" : "' . $lddfw_tracking_speed . '", "note" : "' . $note . '" , "countdown_seconds" : "' . $seconds_diff . '" }';
		echo $result;
	}

	/**
	 * Get tracking interval.
	 *
	 * @return int
	 */
	public function get_tracking_interval() {
		$array             = $this->get_tracking_interval_array();
		$tracking_interval = get_option( 'lddfw_drivers_tracking_interval', '' );
		if ( ! in_array( $tracking_interval, $array ) ) {
			return '120000';
		} else {
			return $tracking_interval;
		}
	}

	/**
	 * Get tracking interval.
	 *
	 * @return array
	 */
	public static function get_tracking_interval_array() {
		return array( 120000, 90000, 60000, 30000, 15000 );
	}
}
