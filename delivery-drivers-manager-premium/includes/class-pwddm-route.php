<?php
/**
 * Route page.
 *
 * All the route functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Route page.
 *
 * All the route functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Route {

	/**
	 * Google_api_key variable.
	 *
	 * @var string
	 */
	private $pwddm_google_api_key;
	/**
	 * Google_api_key variable.
	 *
	 * @var string
	 */
	private $pwddm_google_api_key_server;

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->pwddm_google_api_key        = get_option( 'lddfw_google_api_key', '' );
		$this->pwddm_google_api_key_server = get_option( 'lddfw_google_api_key_server', '' );
	}

	/**
	 * Get unit system by country.
	 *
	 * @return string
	 */
	public function pwddm_country_unit_system__premium_only() {
		$store_raw_country = get_option( 'woocommerce_default_country', '' );
		$split_country     = explode( ':', $store_raw_country );
		if ( false === strpos( $store_raw_country, ':' ) ) {
			$store_country = $split_country[0];
		} else {
			$store_country = $split_country[0];
		}
			$array = array( 'gb', 'us', 'lr', 'mm' );
		if ( in_array( strtolower( $store_country ), $array, true ) ) {
			return 'imperial';
		} else {
			return 'metric';
		}
	}

	 /**
	  * Plain route.
	  *
	  * @param int   $driver_id driver user id.
	  * @param array $origin origin.
	  * @param array $destination destination.
	  * @return json
	  */
	public function pwddm_plain_route__premium_only( $driver_id, $origin, $destination ) {

		$alert = '';
		// Get google API key.
		$pwddm_google_api_key = $this->pwddm_google_api_key_server;
		if ( '' === $pwddm_google_api_key ) {
			$pwddm_google_api_key = $this->pwddm_google_api_key;
		}

		if ( '' !== $pwddm_google_api_key ) {

			$store       = new LDDFW_Store();
			$lddfw_order = new LDDFW_Order();
			$unit_system = $this->pwddm_country_unit_system__premium_only();

			$store_address     = '';
			$wc_query          = $this->pwddm_driver_route_query__premium_only( $driver_id );
			$order_destination = '';
			$counter           = 0;
			$orders_array      = array();
			$travel_mode       = LDDFW_Driver::get_driver_driving_mode( $driver_id, 'lowercase' );
			if ( ! empty( $wc_query ) ) {
				foreach ( $wc_query as $result ) {

					$orderid = $result->ID;
					$order   = wc_get_order( $orderid );

					// Get start pick-up address.
					$seller_id = $store->lddfw_order_seller( $order );
					if ( '' === $store_address ) {
						$store_address = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
					}

					// Get and fromat shipping address.
					$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
					$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );

					// Set address by coordinates.
					$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
					if ( '' !== $coordinates ) {
						$shipping_map_address = $coordinates;
					}

					if ( $counter > 0 ) {
						$order_destination .= '|';
					}
					$order_destination .= rawurlencode( $shipping_map_address );
					$orders_array[]     = array(
						'orderid'          => $orderid,
						'shipping_address' => $shipping_map_address,
					);
					$counter++;
				}
			}

			// Set origin.
			$origin        = ( '' !== $origin ) ? $origin : $store_address;
			$origin_encode = rawurlencode( $origin );

			// Get farest address from origin.
			if ( '' !== $order_destination ) {
				$url      = 'https://maps.googleapis.com/maps/api/distancematrix/json?mode=' . $travel_mode . '&origins=' . $origin_encode . '&destinations=' . $order_destination . '&key=' . $pwddm_google_api_key . '&units=' . $unit_system;
				$response = wp_remote_get( $url );
				if ( is_wp_error( $response ) ) {
					return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . __( 'An unexpected error has occurred, please try again.', 'pwddm' ) . '</div>';
				} else {
					$body = wp_remote_retrieve_body( $response );
					$obj  = json_decode( $body );
				}

				$farthest_distance = 0;
				$farthest_index    = 0;
				$counter           = 0;
				$array             = array();

				if ( 'OK' === $obj->status ) {

					$row = $obj->rows[0];
					foreach ( $row->elements  as $element ) {

						$distance_text  = '';
						$distance_value = '';
						$duration_text  = '';
						$duration_value = '';

						if ( 'ZERO_RESULTS' === $element->status ) {
							if ( ! empty( $orders_array[ $counter ]['orderid'] ) ) {
								$alert .= '<div>' . __( 'Order #', 'pwddm' ) . $orders_array[ $counter ]['orderid'] . ': ' . __( 'no route could be found between the origin and address.', 'pwddm' ) . '</div>';
							}
						}

						if ( 'NOT_FOUND' === $element->status ) {
							if ( ! empty( $orders_array[ $counter ]['orderid'] ) ) {
								$alert .= '<div>' . __( 'Order #', 'pwddm' ) . $orders_array[ $counter ]['orderid'] . ': ' . __( 'origin and/or address could not be geocoded.', 'pwddm' ) . '</div>';
							}
						}

						if ( 'OK' === $element->status ) {

							$distance_text  = ( ! empty( $element->distance->text ) ) ? $element->distance->text : '';
							$distance_value = ( ! empty( $element->distance->value ) ) ? $element->distance->value : '';
							$duration_text  = ( ! empty( $element->duration->text ) ) ? $element->duration->text : '';
							$duration_value = ( ! empty( $element->duration->value ) ) ? $element->duration->value : '';

							// Get farthest order.
							if ( $distance_value > $farthest_distance ) {
								$farthest_distance = $distance_value;
								$farthest_index    = $counter;
							}
						}

						// Set array of the results.
						$array[] = array(
							'index'          => $counter,
							'distance_text'  => $distance_text,
							'distance_value' => $distance_value,
							'duration_text'  => $duration_text,
							'duration_value' => $duration_value,
							'date_created'   => date_i18n( 'Y-m-d H:i:s' ),
						);
						$counter++;
					}

					/**
					 * Sort route by distance.
					 *
					 * @param array $a distance_value.
					 * @param array $b distance_value.
					 * @return html
					 */
					function sort_count( $a, $b ) {
						if ( $a['distance_value'] === $b['distance_value'] ) {
							return 0;
						} else {
							return ( $a['distance_value'] > $b['distance_value'] ? 1 : -1 );
						}
					}
					$sorted_array = uasort( $array, 'sort_count' );
					$counter      = 0;

					// Save route info in each order.
					foreach ( $array as $value ) {
						$order_count = 0;
						foreach ( $orders_array as $order ) {
							if ( $order_count === $value['index'] ) {
								$order = wc_get_order( $order['orderid'] );
								$order->update_meta_data( 'lddfw_order_route', $value );
								$order->update_meta_data( '_lddfw_origin_distance', $value );
								$order->save();
								break;
							}
							$order_count++;
						}
						$counter++;
					}

					// Set waypoint and destination.
					$waypoint             = '';
					$counter              = 0;
					$destination_order_id = 0;

					if ( '' === $destination || 'last_address_on_route' === $destination ) {
						// Get last order address as destination.
						$destination_index    = end( $array )['index'];
						$destination          = $orders_array[ $destination_index ]['shipping_address'];
						$destination_order_id = $orders_array[ $destination_index ]['orderid'];
					}
					$destination = rawurlencode( $destination );

					$waypoint_array = array();
					foreach ( $orders_array as $order ) {
						if ( $order['orderid'] !== $destination_order_id ) {
							if ( $counter > 0 ) {
								$waypoint .= '|';
							}
							$waypoint_array[] = array(
								'orderid' => $order['orderid'],
							);
							$waypoint        .= rawurlencode( $order['shipping_address'] );
							$counter++;
						}
					}

					// Save orders sort index by direction waypoints.
					$url      = 'https://maps.googleapis.com/maps/api/directions/json?mode=' . $travel_mode . '&origin=' . $origin_encode . '&destination=' . $destination . '&waypoints=optimize:true|' . $waypoint . '&key=' . $pwddm_google_api_key;
					$response = wp_remote_get( $url );
					if ( is_wp_error( $response ) ) {
						return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . __( 'An unexpected error has occurred, please try again.', 'pwddm' ) . '</div>';
					} else {
						$body = wp_remote_retrieve_body( $response );
						$obj  = json_decode( $body );
					}

					if ( 'OK' === $obj->status ) {
						$array   = $obj->routes[0]->waypoint_order;
						$counter = 0;
						foreach ( $array as $value ) {
							$waypoint_count = 0;
							foreach ( $waypoint_array as $waypoint ) {
								if ( $waypoint_count === $value ) {

									$order_id = $waypoint['orderid'];
									$order    = wc_get_order( $order_id );
									$order->update_meta_data( 'lddfw_order_sort', $counter );
									lddfw_update_sync_order( $order_id, 'lddfw_order_sort', $counter );
									$order->save();

								}
								$waypoint_count++;
							}
							$counter++;
						}

						if ( $destination_order_id > 0 ) {
							// Set last order sort number if destination is order address.

							$order = wc_get_order( $destination_order_id );
							$order->update_meta_data( 'lddfw_order_sort', $counter );
							lddfw_update_sync_order( $destination_order_id, 'lddfw_order_sort', $counter );
							$order->save();

						}
					}

					// Set delivery origin for each order.
					$this->pwddm_set_delivery_origin__premium_only( $driver_id, $origin );
				} else {
					$alert .= $obj->status . ' ' . $obj->error_message;
				}
			}
		}
		$alert = ( '' !== $alert ) ? '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $alert . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>' : '1';
		return $alert;
	}

	/**
	 * Route alerts
	 *
	 * @return html
	 */
	public function pwddm_route_alerts__premium_only() {
		$html = '';
		if ( '' !== $this->pwddm_google_api_key ) {
			$plain_route_note_info = __( 'The route has been optimized by distance, if you want to make changes you can drag and drop orders manually.' );
			$plain_route_note_wait = __( 'Optimize route, please wait...' );
		} else {
			$plain_route_note_info = __( 'The route is ready for optimization, you can make changes by drag and drop orders manually.' );
			$plain_route_note_wait = __( 'Please wait...' );
		}

		$html .= '
			<div class="pwddm_plain_route_wrap">
					<div class="row" id="pwddm_plain_route_row">
					<div class="col-12"><a id="pwddm_plainroute_btn" data_start =\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="edit" class="svg-inline--fa fa-edit fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.6l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8 0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2 0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8 0l35.4-35.4c15.2-15.3 15.2-40 0-55.2zM384 346.2V448H64V128h229.8c3.2 0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z"></path></svg> ' . esc_attr( __( 'Plan your route', 'pwddm' ) ) . '\' data_finish =\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="lock" class="svg-inline--fa fa-lock fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg> ' . esc_attr( __( 'Finish planning route', 'pwddm' ) ) . '\' class=" btn btn-secondary  btn-block" href="#">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="edit" class="svg-inline--fa fa-edit fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.6l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8 0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2 0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8 0l35.4-35.4c15.2-15.3 15.2-40 0-55.2zM384 346.2V448H64V128h229.8c3.2 0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z"></path></svg> ' . esc_html( __( 'Plan your route', 'pwddm' ) ) . '</a></div>
					</div>
					<div id="pwddm_plain_route_note_wait" style="display:none;margin-top:17px">
						<div class="alert alert-primary">' . $plain_route_note_wait . '</div>
					</div>
					<div id="pwddm_plain_route_note_info" style="display:none;margin-top:17px">
						<div class="alert alert-primary" id="pwddm_plain_route_note_alert">' . $plain_route_note_info . ' <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>
					</div>
			</div>';
		return $html;
	}

	/**
	 * All Routes query.
	 *
	 * @param int $manager_id manager id.
	 * @return object
	 */
	public function pwddm_all_routes_query__premium_only( $manager_id ) {

		global $wpdb;
		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wo.ID FROM ' . $wpdb->prefix . 'wc_orders wo 
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON wo.id = o.order_id
					WHERE
					wo.type = \'shop_order\'
					AND wo.status = %s
					AND o.driver_id > 0
					GROUP BY wo.id
					ORDER BY o.driver_id, o.order_sort, o.order_shipping_city',
					array( get_option( 'lddfw_out_for_delivery_status', '' ) )
				)
			); // db call ok; no-cache ok.
		} else {
			// Original query for non-HPOS environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT p.ID FROM ' . $wpdb->prefix . 'posts p 
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND driver_id > 0
					GROUP BY p.ID
					ORDER BY driver_id,order_sort,order_shipping_city',
					array( get_option( 'lddfw_out_for_delivery_status', '' ) )
				)
			);
		}
		return $query;

	}



	/**
	 * Route query.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return object
	 */
	public function pwddm_driver_route_query__premium_only( $driver_id ) {

		global $wpdb;
		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wo.ID FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE
					wo.type = \'shop_order\'
					AND wo.status = %s
					AND o.driver_id = %d
					GROUP BY wo.id
					ORDER BY o.order_sort, o.order_shipping_city',
					array( get_option( 'lddfw_out_for_delivery_status', '' ), $driver_id )
				)
			); // db call ok; no-cache ok.
		} else {
			// Original query for non-HPOS environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT p.ID FROM ' . $wpdb->prefix . 'posts p 
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND driver_id = %d
					GROUP BY p.ID
					ORDER BY order_sort,order_shipping_city',
					array( get_option( 'lddfw_out_for_delivery_status', '' ), $driver_id )
				)
			);
		}
		return $query;

	}


	/**
	 * Save route.
	 *
	 * @param int    $driver_id driver id.
	 * @param string $orders_list orders list.
	 * @param array  $route_array route.
	 * @return statement
	 */
	public function pwddm_save_route__premium_only( $driver_id, $orders_list, $route_array ) {
		$result = 0;
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {

				// Sort orders.
			if ( '' !== $orders_list ) {
				$orders_list_array = explode( ',', $orders_list );
				$counter           = 1;
				foreach ( $orders_list_array  as $order_id ) {
					if ( '' !== $order_id ) {

						$order = wc_get_order( $order_id );
						$order->update_meta_data( 'lddfw_order_sort', $counter );
						lddfw_update_sync_order( $order_id, 'lddfw_order_sort', $counter );
						$order->save();

						++$counter;

					}
				}
			}

				update_user_meta( $driver_id, 'lddfw_route', $route_array );
				$this->pwddm_set_delivery_origin__premium_only( $driver_id, $route_array['origin_map_address'] );
		}

		return $result;
	}


	/**
	 * Set delivery origin.
	 *
	 * @since 1.0.0
	 * @param int   $driver_id driver user id.
	 * @param array $origin origin.
	 */
	public function pwddm_set_delivery_origin__premium_only( $driver_id, $origin ) {
		$wc_query    = $this->pwddm_driver_route_query__premium_only( $driver_id );
		$store       = new LDDFW_Store();
		$lddfw_order = new LDDFW_Order();

		if ( ! empty( $wc_query ) ) {
			foreach ( $wc_query as $result ) {
				$orderid   = $result->ID;
				$order     = wc_get_order( $orderid );
				$seller_id = $store->lddfw_order_seller( $order );

				if ( '' === $origin ) {
					$origin = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
				}
				// Get and fromat shipping address.
				$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
				$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );

				// Set address by coordinates.
				$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
				if ( '' !== $coordinates ) {
					$shipping_map_address = $coordinates;
				}

				// set delivery origin.
				$order->update_meta_data( 'lddfw_order_origin', $origin );
				$order->save();
				$origin = $shipping_map_address;
			}
		}
	}


	/**
	 * Route script.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_route_script__premium_only() {
		$html = '';
		if ( '' !== $this->pwddm_google_api_key ) {
			$store         = new LDDFW_Store();
			$store_address = $store->lddfw_store_address( 'map_address' );

			$html .= '
			<script>
				var pwddm_optimizeWaypoints_flag = false;
				var pwddm_google_api_key         =  "' . $this->pwddm_google_api_key . '";
				var pwddm_google_api_origin 	 =  "' . esc_attr( $store_address ) . '";
			</script>
			';
		}
		return $html;
	}

	/**
	 * Route button.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_route_button__premium_only() {
		$html = '';
		if ( '' !== $this->pwddm_google_api_key ) {
			$html .= '
			<div class="pwddm_footer_buttons">
				<div class="container">
					<div class="row">
						<div class="col-12"><a href="#" id="pwddm_route_btn" class="btn btn-lg btn-block btn-success">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marked-alt" class="svg-inline--fa fa-map-marked-alt fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M288 0c-69.59 0-126 56.41-126 126 0 56.26 82.35 158.8 113.9 196.02 6.39 7.54 17.82 7.54 24.2 0C331.65 284.8 414 182.26 414 126 414 56.41 357.59 0 288 0zm0 168c-23.2 0-42-18.8-42-42s18.8-42 42-42 42 18.8 42 42-18.8 42-42 42zM20.12 215.95A32.006 32.006 0 0 0 0 245.66v250.32c0 11.32 11.43 19.06 21.94 14.86L160 448V214.92c-8.84-15.98-16.07-31.54-21.25-46.42L20.12 215.95zM288 359.67c-14.07 0-27.38-6.18-36.51-16.96-19.66-23.2-40.57-49.62-59.49-76.72v182l192 64V266c-18.92 27.09-39.82 53.52-59.49 76.72-9.13 10.77-22.44 16.95-36.51 16.95zm266.06-198.51L416 224v288l139.88-55.95A31.996 31.996 0 0 0 576 426.34V176.02c0-11.32-11.43-19.06-21.94-14.86z"></path></svg></i> ' . esc_html( __( 'View Route', 'pwddm' ) ) . '</a></div>
					</div>
				</div>
			</div>';
		}
		return $html;
	}





	/**
	 * Drivers routes
	 *
	 * @param int $pwddm_manager_id manager id.
	 * @return statement
	 */
	public function pwddm_drivers_routes__premium_only( $pwddm_manager_id ) {
		$pwddm_manager_drivers    = get_user_meta( $pwddm_manager_id, 'pwddm_manager_drivers', true );
		$wc_query                 = $this->pwddm_all_routes_query__premium_only( $pwddm_manager_id );
		$route_array              = '';
		$store                    = new LDDFW_Store();
		$lddfw_order              = new LDDFW_Order();
		$driver                   = new PWDDM_Driver();
		$store_address            = $store->lddfw_store_address( 'map_address' );
		$drivers_counter          = 0;
		$array                    = $driver->pwddm_get_drivers( $pwddm_manager_id, 'all' );
		$drivers_array            = $array->get_results();
		$drivers_with_route_array = array();
		$last_pwddm_driverid      = 0;

		/**
		 * Random color.
		 *
		 * @return string
		 */
		function pwddm_random_color_part() {
			return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
		}
		/**
		 * Random color.
		 *
		 * @return string
		 */
		function pwddm_random_color() {
			return pwddm_random_color_part() . pwddm_random_color_part() . pwddm_random_color_part();
		}

		$drivers_colors = array(
			'#00b8b0', // Tiffany Blue.
			'#f68026', // Princeton Orange.
			'#722887', // Eminence.
			'#63be5f', // Mantis.
			'#ee2f7f', // Electric Pink.
			'#01abe8', // Vivid Cerulean.
			'#000080', // Navy Blue.
			'#bd9b33', // Satin Sheen Gold.
			'#808080', // Gray.
			'#3377aa', // Steel Blue.
			'#9440ed', // Lavender Indigo.
			'#800000', // Maroon.
			'#008000', // Ao (English).
			'#FF00FF', // Fuchsia.
		);
		$route_array    = '{ "data": [{"route": [';
		if ( ! empty( $wc_query ) ) {

			foreach ( $wc_query as $result ) {

				$orderid         = $result->ID;
				$order           = wc_get_order( $orderid );
				$order_number    = $order->get_order_number();
				$pwddm_driverid  = $order->get_meta( 'lddfw_driverid' );
				$order_seller_id = $store->lddfw_order_seller( $order );
				$driver_manager  = get_user_meta( $pwddm_driverid, 'pwddm_manager', true );

				if ( ( '0' === $pwddm_manager_drivers && ( '' === $driver_manager || false === $driver_manager ) ) || ( '1' === $pwddm_manager_drivers || '' === $pwddm_manager_drivers ) || ( '2' === $pwddm_manager_drivers && ( strval( $driver_manager ) === strval( $pwddm_manager_id ) ) ) ) {

						// Get and fromat shipping address.
						$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
						$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );
						$shipping_full_name   = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();

						// Set address by coordinates.
					if ( method_exists( $lddfw_order, 'lddfw_order_shipping_address_coordinates' ) ) {
						$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
						if ( '' !== $coordinates ) {
							$shipping_map_address = $coordinates;
						}
					}
						// route array.
					if ( $last_pwddm_driverid !== $pwddm_driverid ) {

						$drivers_with_route_array[] = $pwddm_driverid;
						// Set default route and destination.
						$route_destination = 'last_address_on_route';
						$route_origin      = '';

						// Get saved route origin and destination.
						$driver_route = get_user_meta( $pwddm_driverid, 'lddfw_route', true );
						if ( ! empty( $driver_route ) ) {
							$driver_route_date_created = $driver_route['date_created'];
							if ( date_i18n( 'Y-m-d' ) === date_i18n( 'Y-m-d', strtotime( $driver_route_date_created ) ) ) {
								if ( '' !== $driver_route['destination_map_address'] ) {
									$route_destination = $driver_route['destination_map_address'];
									$route_origin      = $driver_route['origin_map_address'];
								}
							}
						}

						// get last order shipping address as origin.
						if ( '' === $route_origin ) {
							// get route saved origin.
							$route_origin = $order->get_meta( 'lddfw_order_origin' );
						}

						if ( '' === $route_origin ) {
							// get pickup address as origin.
							$route_origin = $store->lddfw_pickup_address( 'map_address', $order, $order_seller_id );
						}
						// Driver home address.
						$driver_address = $driver->get_driver_address( $pwddm_driverid );
						if ( ! empty( $driver_address ) ) {
							$driver_address = $driver_address[0];
						}

						// set driver address coordinates if not exist.
						$this->set_driver_geocode( $pwddm_driverid );

						// get driver address coordinates.
						$driver_geocode_coordinates = '';
						$coordinates                = $this->get_driver_geocode( $pwddm_driverid );
						if ( false !== $coordinates && is_array( $coordinates ) ) {
							$driver_geocode_coordinates = $coordinates[0] . ',' . $coordinates[1];
						}

						// Close json.
						if ( $drivers_counter > 0 ) {
							$route_array  = substr( $route_array, 0, -1 );
							$route_array .= ']},';
						};

						$user              = get_userdata( $pwddm_driverid );
						$lddfw_driver_name = ( ! empty( $user ) ) ? $user->display_name : '';
						$image_id          = get_user_meta( $pwddm_driverid, 'lddfw_driver_image', true );

						// Driver travel mode.
						$driver_travel_mode = LDDFW_Driver::get_driver_driving_mode( $pwddm_driverid, '' );

						// Driver image.
						$image_id = get_user_meta( $pwddm_driverid, 'lddfw_driver_image', true );
						$image    = '';
						if ( intval( $image_id ) > 0 ) {
							$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
						}

						// Vehicle.
						$driver_vehicle = get_user_meta( $pwddm_driverid, 'lddfw_driver_vehicle', true );

						// License Plate.
						$driver_licence_plate = get_user_meta( $pwddm_driverid, 'lddfw_driver_licence_plate', true );

						// Driver availability.
						$driver_availability = get_user_meta( $pwddm_driverid, 'lddfw_driver_availability', true );

						// Driver phone.
						$phone          = get_user_meta( $pwddm_driverid, 'billing_phone', true );
						$country_code   = get_user_meta( $pwddm_driverid, 'billing_country', true );
						$whatsapp_phone = preg_replace( '/[^0-9]/', '', lddfw_get_international_phone_number( $country_code, $phone ) );

						$driver_color = '#' . pwddm_random_color();
						if ( $drivers_counter < 14 ) {
							$driver_color = $drivers_colors[ $drivers_counter ];
						}
						$route_array .= '{"driver": [{"id": "' . esc_attr( $pwddm_driverid ) . '", "whatsapp_phone":"' . $whatsapp_phone . '", "phone" : "' . esc_attr( $phone ) . '", "availability" : "' . esc_attr( $driver_availability ) . '", "licence_plate" : "' . esc_attr( $driver_licence_plate ) . '" , "vehicle" : "' . esc_attr( $driver_vehicle ) . '" , "address_coordinates":"' . esc_attr( $driver_geocode_coordinates ) . '" ,"address" : "' . esc_attr( $driver_address ) . '","name": "' . esc_attr( $lddfw_driver_name ) . '","image": "' . esc_attr( $image ) . '","color": "' . esc_attr( $driver_color ) . '", "travel_mode":"' . esc_attr( $driver_travel_mode ) . '"}], "destination":"' . esc_attr( $route_destination ) . '","origin": "' . esc_attr( $route_origin ) . '","waypoints": [';
						++$drivers_counter;
						$last_pwddm_driverid = $pwddm_driverid;
					}
						$pickup       = $store->lddfw_pickup_address( 'map_address', $order, $order_seller_id );
						$route_array .= '{"order_number": "' . $order_number . '","order": "' . $orderid . '", "pickup":"' . esc_attr( $pickup ) . '", "address": "' . esc_attr( $shipping_map_address ) . '","status": "waiting","color": "#800000" , "shipping_name": "' . esc_attr( $shipping_full_name ) . '" },';
					?>
					 <?php

				}
				?>
				<?php

			}

			// Close json.
			if ( $drivers_counter > 0 ) {
				$route_array  = substr( $route_array, 0, -1 );
				$route_array .= ']},';
			}
		}

		if ( ! empty( $drivers_array ) ) {
			$available_drivers = false;

			foreach ( $drivers_array as $user ) {
				$pwddm_driverid = $user->ID;

				$driver_manager_id = get_user_meta( $pwddm_driverid, 'pwddm_manager', true );
				if (
					( ( '0' === $pwddm_manager_drivers || false === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
					||
					( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $pwddm_manager_id ) )
					||
					( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $pwddm_manager_id ) || '' === strval( $driver_manager_id ) ) )
				) {

					if ( ! in_array( $pwddm_driverid, $drivers_with_route_array ) ) {
						$availability = get_user_meta( $pwddm_driverid, 'lddfw_driver_availability', true );
						if ( '1' === $availability ) {

							// Driver home address.
							$driver_address = $driver->get_driver_address( $pwddm_driverid );
							if ( ! empty( $driver_address ) ) {
								$driver_address = $driver_address[0];
							}

							// set driver address coordinates if not exist.
							$this->set_driver_geocode( $pwddm_driverid );

							// get driver address coordinates.
							$driver_geocode_coordinates = '';
							$coordinates                = $this->get_driver_geocode( $pwddm_driverid );
							if ( false !== $coordinates && is_array( $coordinates ) ) {
								$driver_geocode_coordinates = $coordinates[0] . ',' . $coordinates[1];
							}

							// Driver travel mode.
							$driver_travel_mode = LDDFW_Driver::get_driver_driving_mode( $pwddm_driverid, '' );

							$user              = get_userdata( $pwddm_driverid );
							$lddfw_driver_name = ( ! empty( $user ) ) ? $user->display_name : '';

							// Driver image.
							$image_id = get_user_meta( $pwddm_driverid, 'lddfw_driver_image', true );
							$image    = '';
							if ( intval( $image_id ) > 0 ) {
								$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
							}

							// Vehicle.
							$driver_vehicle = get_user_meta( $pwddm_driverid, 'lddfw_driver_vehicle', true );

							// License Plate.
							$driver_licence_plate = get_user_meta( $pwddm_driverid, 'lddfw_driver_licence_plate', true );

							// Driver availability.
							$driver_availability = get_user_meta( $pwddm_driverid, 'lddfw_driver_availability', true );

							// Driver phone.
							$phone          = get_user_meta( $pwddm_driverid, 'billing_phone', true );
							$country_code   = get_user_meta( $pwddm_driverid, 'billing_country', true );
							$whatsapp_phone = preg_replace( '/[^0-9]/', '', lddfw_get_international_phone_number( $country_code, $phone ) );

							$driver_color = '#' . pwddm_random_color();
							if ( $drivers_counter < 14 ) {
								$driver_color = $drivers_colors[ $drivers_counter ];
							}
							if ( $drivers_counter > 0 && $available_drivers = false ) {
								$route_array .= ',';
							}

							$route_array .= '{"driver": [{"id": "' . esc_attr( $pwddm_driverid ) . '", "whatsapp_phone":"' . $whatsapp_phone . '", "phone" : "' . esc_attr( $phone ) . '", "availability" : "1", "licence_plate" : "' . esc_attr( $driver_licence_plate ) . '" , "vehicle" : "' . esc_attr( $driver_vehicle ) . '" , "address_coordinates":"' . esc_attr( $driver_geocode_coordinates ) . '" ,"address" : "' . esc_attr( $driver_address ) . '","name": "' . esc_attr( $lddfw_driver_name ) . '","image": "' . esc_attr( $image ) . '","color": "' . esc_attr( $driver_color ) . '", "travel_mode":"' . esc_attr( $driver_travel_mode ) . '"}], "destination":"","origin": "","waypoints": ""},';
							$drivers_counter++;
							$available_drivers = true;
						}
					}
				}
			}
		}
		if ( $drivers_counter > 0 ) {
			$route_array = substr( $route_array, 0, -1 );

		}

		$route_array .= ']}]}';

		return $route_array;
	}


	/**
	 * Admin routes screen.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function pwddm_routes_screen() {

		global $pwddm_manager_id,$pwddm_manager_drivers;
		$drivers_array = PWDDM_Driver::pwddm_get_drivers( $pwddm_manager_id, 'all' );
		$drivers       = $drivers_array->get_results();
		/**
		 * Set current status names
		 */
		$pwddm_driver_assigned_status_name  = esc_html( __( 'Driver assigned', 'pwddm' ) );
		$pwddm_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'pwddm' ) );
		$pwddm_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'pwddm' ) );
		$pwddm_processing_status_name       = esc_html( __( 'Processing', 'pwddm' ) );

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

		echo '<div class="wrap container-fluid">';
		echo '<div id="pwddm_routes_notice" style="display:none">' . esc_html( __( 'There are no routes for drivers.', 'pwddm' ) ) . '</div>';
		echo '
	<div id="pwddm_routes"  >
		<div class="row">
			<div class="col-12 col-lg-7 col-xl-8 order-1 mb-3 mb-lg-0">
				<div id="pwddm_map123"></div>
			</div>
			<div class="col-12 col-lg order-2 order-3 order-lg-2 ">
				<div id="driver-panel" style="display:none" ></div>
				<div id="orders-panel-wrap"  style="display:none" >
					<form method="POST" name="pwddm_orders_form" id="pwddm_orders_form" action="#">
					<input type="hidden" name="pwddm_wpnonce" value="' . wp_create_nonce( 'pwddm-nonce' ) . '">
					<div id="orders-tools" >
						<div class="row">
							<div class="col-12">
								<!-- Nav tabs -->
								<ul class="nav nav-tabs" id="myTab" role="tablist">
									<li class="nav-item" role="presentation">
										<a class="nav-link active" id="filter-tab" data-bs-toggle="tab" data-bs-target="#filter" type="button" role="tab" aria-controls="filter" aria-selected="true">' . esc_html( __( 'Filter', 'pwddm' ) ) . '</a>
									</li>
									<li class="nav-item" role="presentation">
										<a class="nav-link" id="filter-tab" data-bs-toggle="tab" data-bs-target="#bulk-action" type="button" role="tab" aria-controls="bulk-action" aria-selected="true">' . pwddm_premium_feature( '' ) . esc_html( __( 'Bulk actions', 'pwddm' ) ) . '</a>
									</li>
								</ul>
							</div>
							<!-- Tab panes -->
								<div class="tab-content" style="margin-top:10px;">
									<div class="tab-pane " id="bulk-action" role="tabpanel" aria-labelledby="bulk-action-tab">
										<div class="row">
											<div class="col-12 col-sm mb-2 mb-sm-0"  >
												<select name="pwddm_action" id="pwddm_action" class="form-select">
													<option value="">' . esc_html( __( 'Bulk actions', 'pwddm' ) ) . '</option>
													<option value="mark_processing">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Processing', 'pwddm' ) ) . '</option>
													<option value="mark_driver_assigned">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_driver_assigned_status_name . '</option>
													<option value="mark_out_for_delivery">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_out_for_delivery_status_name . ' </option>
													<option value="mark_failed">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_failed_attempt_status_name . '</option>
													<option value="mark_delivered">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Delivered', 'pwddm' ) ) . '</option>
													<option value="remove_location_status">' . esc_html( __( 'Remove order location status', 'pwddm' ) ) . '</option>
													<option value="-1">' . esc_html( __( 'Unassign driver', 'pwddm' ) ) . '</option>';
													$last_availability = '';
		foreach ( $drivers as $driver ) {

			$driver_manager_id = get_user_meta( $driver->ID, 'pwddm_manager', true );
			if (
				( ( '0' === $pwddm_manager_drivers || false === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
				||
				( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $pwddm_manager_id ) )
				||
				( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $pwddm_manager_id ) || '' === strval( $driver_manager_id ) ) )
			) {

				$driver_name    = $driver->display_name;
				$availability   = get_user_meta( $driver->ID, 'lddfw_driver_availability', true );
				$driver_account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
				$availability   = '1' === $availability ? 'Available' : 'Unavailable';
				if ( $last_availability !== $availability ) {
					if ( '' !== $last_availability ) {
						echo '</optgroup>';
					}
					echo '<optgroup label="' . esc_attr( $availability . ' ' . __( 'drivers', 'pwddm' ) ) . '">';
					$last_availability = $availability;
				}
				if ( '1' === $driver_account ) {
					echo '<option value="' . esc_attr( $driver->ID ) . '">' . esc_html( __( 'Assign to', 'pwddm' ) ) . ' ' . esc_html( $driver_name ) . '</option>';
				}
			}
		}
													echo '</optgroup>';
													echo '
												</select>
											</div>
											<div class="col-12 col-sm-2 mb-2 mb-sm-0 text-center d-grid gap-2"  >
												<button class="btn btn-secondary btn-block" type="button" id="pwddm_orders_bulk_btn" data="' . esc_attr( __( 'Apply', 'pwddm' ) ) . '"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="edit" class="svg-inline--fa fa-edit fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg></button>
											</div>
										</div>
									</div>
									<div class="tab-pane active" id="filter" role="tabpanel" aria-labelledby="filter-tab">
										<div class="row">
											<div class="col-12 col-sm mb-2 mb-sm-0"  >
												<select class="form-select" id="pwddm_orders_status" name="pwddm_orders_status">
													<option value="' . get_option( 'lddfw_processing_status', '' ) . '">' . $pwddm_processing_status_name . '</option>
													<option value="' . get_option( 'lddfw_driver_assigned_status', '' ) . '">' . $pwddm_driver_assigned_status_name . '</option>
													<option selected value="' . get_option( 'lddfw_out_for_delivery_status', '' ) . '"> ' . $pwddm_out_for_delivery_status_name . ' </option>
													<option value="' . get_option( 'lddfw_failed_attempt_status', '' ) . '">' . $pwddm_failed_attempt_status_name . '</option>
													<option value="' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '">' . esc_html( __( 'Delivered', 'pwddm' ) ) . '</option>
												</select>
											</div>
											<div class="col-12 col-sm mb-2 mb-sm-0 pwddm_dates_range_col" style="display:none; ">
												<select class="form-select" id="pwddm_dates_range" name="pwddm_dates_range" >
													<option value="">' . esc_attr( __( 'All Dates', 'pwddm' ) ) . '</option>
													<option value="' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) . '">' . esc_html( __( 'Today', 'pwddm' ) ) . '</option>
													<option value="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '">' . esc_html( __( 'Yesterday', 'pwddm' ) ) . '</option>
													<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '">' . esc_html( __( 'This month', 'pwddm' ) ) . '</option>
													<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '">' . esc_html( __( 'Last month', 'pwddm' ) ) . '</option>
												</select>
											</div>
											<div class="col-12 col-sm mb-2 mb-sm-0" >
												<select name="pwddm_orders_filter"  id="pwddm_orders_filter" class="form-select">
													<option selected value="">' . __( 'Filter By', 'pwddm' ) . '</option>
													<option value="-1" >' . __( 'With drivers', 'pwddm' ) . '</option>
													<option value="-2" >' . __( 'Without drivers', 'pwddm' ) . '</option>
													<optgroup label="' . esc_attr( __( 'Drivers', 'pwddm' ) ) . '"></optgroup>';
		foreach ( $drivers as $driver ) {
			$driver_name = $driver->display_name;
			echo '<option value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
		}
													echo '
												</select>
											</div>
											<div class="col-12 col-sm-2 text-center d-grid gap-2">
												<button class="btn btn-block btn-secondary" name="pwddm_orders_filter_btn" id="pwddm_orders_filter_btn" type="button" data="' . esc_attr( __( 'Filter', 'pwddm' ) ) . '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="search" class="svg-inline--fa fa-search fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"></path></svg></button>
											</div>
										</div>
									</div>
							</div>
						</div>
					</div>
					<div class="d-grid gap-2 col-12 col-sm-6 col-md-6 col-lg-12 mx-auto">
						<button type="button" class="btn btn-block btn-primary" id="pwddm_show_on_map" >' . esc_attr( __( 'Show orders on map', 'pwddm' ) ) . '</button>
						<button style="display:none" id="pwddm_show_on_map_loading" class="pwddm_loading_btn btn btn-block btn-primary" type="button" disabled>
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						' . esc_html( __( 'Loading', 'pwddm' ) ) . '
						</button>
						<button type="button" class="btn btn-block btn-primary" id="pwddm_hide_on_map" style="display:none;" >' . esc_attr( __( 'Hide orders on map', 'pwddm' ) ) . '</button>
					</div>
					<div id="orders-panel"></div>
					</div>
					</form>
			</div>
			<div class="col-md order-2 order-lg-3" id="pwddm_drivers_icons">

			</div>
		</div>
	</div>
</div>




';
		$pwddm_google_api_key          = get_option( 'lddfw_google_api_key', '' );
		$lddfw_drivers_tracking_timing = get_option( 'lddfw_drivers_tracking_timing' );
		?>

		<script>

			let show_orders_on_map = false;
			var geocoder;
			let infowindow;
			var driverMarker = [];
			var driver_home_marker = [];
			let orderMarker = [];
			let pickup_marker = [];
			let orderMarker_icon = [];
			var pwddm_waypts_array = [];
			let pwddm_map;
			let pwddm_directionsService;
			let pwddm_directionsRenderer;
			let pwddm_route_array = [];
			let pwddm_marker_green = {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="43" height="43"><defs><path d="M155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93C133.95 229 142.03 256.69 155.41 282.22Z" id="e1tjFi6Pp"></path><path d="M155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93C133.95 229 142.03 256.69 155.41 282.22Z" id="a5U0aaXGF4"></path><path d="M491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64Z" id="c4rN26gzGj"></path><path d="M491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64Z" id="cGs9LQreb"></path><path d="M406.78 143.67C407.96 138.16 404.03 132.92 398.72 132.92C389.7 132.92 344.61 132.92 263.44 132.92C261.54 123.07 260.49 117.59 260.28 116.49C259.49 112.4 256.11 109.46 252.18 109.46C248.65 109.46 220.4 109.46 216.86 109.46C212.3 109.46 208.6 113.4 208.6 118.26C208.6 118.84 208.6 123.54 208.6 124.12C208.6 128.98 212.3 132.92 216.86 132.92C218.47 132.92 226.49 132.92 240.94 132.92C255.45 208.46 263.52 250.43 265.13 258.82C259.34 262.36 255.44 269 255.44 276.62C255.44 287.96 264.08 297.15 274.73 297.15C285.38 297.15 294.02 287.96 294.02 276.62C294.02 270.88 291.8 265.68 288.22 261.96C295.44 261.96 353.21 261.96 360.43 261.96C356.86 265.68 354.64 270.88 354.64 276.62C354.64 287.96 363.27 297.15 373.93 297.15C384.58 297.15 393.22 287.96 393.22 276.62C393.22 268.49 388.78 261.47 382.34 258.14C382.53 257.25 384.05 250.13 384.24 249.24C385.41 243.74 381.48 238.5 376.18 238.5C370.01 238.5 339.2 238.5 283.73 238.5L281.47 226.77C342.05 226.77 375.71 226.77 382.44 226.77C386.3 226.77 389.65 223.92 390.5 219.92C393.76 204.67 405.16 151.29 406.78 143.67Z" id="a6edtsLup8"></path></defs><g><g><g><use xlink:href="#e1tjFi6Pp" opacity="1" fill="#5cb323" fill-opacity="1"></use><g><use xlink:href="#e1tjFi6Pp" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#a5U0aaXGF4" opacity="1" fill="#6de039" fill-opacity="0"></use><g><use xlink:href="#a5U0aaXGF4" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#c4rN26gzGj" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#c4rN26gzGj" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#cGs9LQreb" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#cGs9LQreb" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#a6edtsLup8" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#a6edtsLup8" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>' ) };
			let pwddm_spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

			<?php
			echo '
				var pwddm_ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
				var pwddm_hour_text = "' . esc_js( __( 'hour', 'pwddm' ) ) . '";
				var pwddm_hours_text = "' . esc_js( __( 'hours', 'pwddm' ) ) . '";
				var pwddm_mins_text = "' . esc_js( __( 'mins', 'pwddm' ) ) . '";
				'
			?>
		</script>

		<?php
		$route = new PWDDM_Route();
		echo $route->pwddm_route_script__premium_only();

		?>
				<script>
				var pwddm_json='';
				function pwddm_get_routes_json()
				{
					return jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "json",
							 data: {
								action: 'pwddm_ajax',
								pwddm_service: 'pwddm_drivers_routes',
								pwddm_wpnonce: pwddm_nonce,
								pwddm_data_type: 'json',
								pwddm_manager_id: '<?php echo $pwddm_manager_id; ?>'
							},
							success:function(data){
								pwddm_json = data;
							},
							error: function(request, status, error) {
								console.log(error);
							}
						})
				}

				// Hide orders markers
				function pwddm_hide_orders_markers(){
					if ( orderMarker.length ) {

						orderMarker.forEach(function(marker) {
							marker.setMap(null);
						});
					}
					if ( pickup_marker.length ) {

						pickup_marker.forEach(function(marker) {
							marker.setMap(null);
						});
					}
				}

				// Delete orders markers
				function pwddm_delete_orders_markers() {
					pwddm_hide_orders_markers();
					if ( orderMarker.length ) {
						orderMarker = [];
					}
					if ( pickup_marker.length ) {
						pickup_marker = [];
					}
				}

				// Load orders.
				function pwddm_orders(){
					  jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "html",
							 data: {
								action: 'pwddm_ajax',
								pwddm_service: 'pwddm_orders',
								pwddm_wpnonce: pwddm_nonce,
								pwddm_data_type: 'html',
								pwddm_manager_id: '<?php echo $pwddm_manager_id; ?>'
							},
							success:function(data){
								jQuery("#orders-panel").html(data);
							},
							error: function(request, status, error) {
								console.log(error);
							}
						})
				}


				jQuery("body").on("click" , "#pwddm_orders_icon" , function() {
					jQuery("#pwddm_drivers_icons a").each(function(){
						jQuery(this).removeClass("current");
					})
					jQuery(this).addClass("current");
					jQuery("#orders-panel-wrap").show();
					jQuery("#driver-panel").hide();
					return false;
				});
				jQuery("body").on("click" , ".pwddm_driver_icon" , function() {
					var driver_box_id = jQuery(this).attr("data");
					jQuery("#pwddm_drivers_icons a").each(function(){
						jQuery(this).removeClass("current");
					})
					jQuery(this).addClass("current");
					jQuery("#orders-panel-wrap").hide();
					jQuery("#driver-panel").show();
					jQuery(".pwddm_driver_box").hide();
					jQuery("#driver_" + driver_box_id).show();
					return false;
				});

				// Click on driver home icon
				jQuery("body").on("click", ".pwddm_driver_box .pwddm_driver_home_icon a", function(){
					 var pwddm_driverid = jQuery(this).attr("data");
					 if ( typeof driver_home_marker[pwddm_driverid] === 'object' && driver_home_marker[pwddm_driverid] !== null ){
						//pwddm_map.setZoom(14);
						pwddm_map.panTo(driver_home_marker[pwddm_driverid].position);
					 }
					 return false;
				});

				//Multi checkbox.
				jQuery("body").on("click" , "#pwddm_multi_checkbox" , function() {
					if ( jQuery(this).is(":checked") )
						{
							jQuery("#orders-panel .order_checkbox").each( function(){
								jQuery( this ).prop( "checked", true );
								if ( orderMarker.length ) {
									var order_id = jQuery(this).val();
									orderMarker[order_id].setIcon(pwddm_marker_green);
								}
							});
						}
					else
						{
							jQuery("#orders-panel .order_checkbox").each( function(){
								jQuery( this ).prop( "checked", false );
								if ( orderMarker.length ) {
									var order_id = jQuery(this).val();
									orderMarker[order_id].setIcon(orderMarker_icon[order_id]);
								}
							});
						}
					});

					//Checkbox handle map marker.
					jQuery("body").on("click" , "#orders-panel .order_checkbox",function(){
							var checkbox = jQuery(this);
							if ( orderMarker.length ) {
							var order_id = checkbox.val();
							if ( checkbox.is(":checked") ) {
								orderMarker[order_id].setIcon(pwddm_marker_green);
								pwddm_map.panTo(orderMarker[order_id].position);
							} else {
								orderMarker[order_id].setIcon(orderMarker_icon[order_id]);
							}
						}
						if ( checkbox.is(":checked") ) {
							checkbox.parents("tr").addClass("active");
						}else {
							checkbox.parents("tr").removeClass("active");
						}


					});

				// Show orders on map.
				function pwddm_show_orders_on_map()
				{

					jQuery("#pwddm_show_on_map").hide();
					jQuery("#pwddm_show_on_map_loading").hide();
					jQuery("#pwddm_hide_on_map").show();

					orderMarker = [];
					pickup_marker = [];
					var move_to_first_position = false;
					jQuery(".order_checkbox").each(function(){

						var $this = jQuery(this);
						var order_coordinations  = $this.attr("coordinates");
						var order_address 		 = $this.attr("formatted_address");
						var order_id  			 = $this.val();
						var order_color 		 = $this.attr("color");
						var pickup_coordinates   = $this.attr("pickup_coordinates");
						var pickup_id            = $this.attr("pickup_id");
						var pickup_name  		 = $this.attr("pickup_name");
						var pickup_address 	     = $this.attr("pickup_address");
						var order_number 		 = $this.attr("order_number");
					 
						// Sellers markers
						if ( pickup_coordinates != "" ) {
							var pickup_coordinates_array = pickup_coordinates.split(",");
							var LatLng = { lat: parseFloat(pickup_coordinates_array[0]), lng: parseFloat(pickup_coordinates_array[1]) };

							if ( !(pickup_id in pickup_marker) ) {
								
								pickup_marker[pickup_id] =new google.maps.Marker({
									position: LatLng,
									map: pwddm_map,
									icon : {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="55" height="55"><defs><path d="M133.95 200.93C133.95 229 142.03 256.69 155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93Z" id="a8icuLH8v"></path><path d="M513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93Z" id="d1BN9HSsyo"></path><path d="M402.85 111.5C400.75 108.09 397.03 106 393.05 106C378.44 106 261.56 106 246.95 106C242.97 106 239.25 108.09 237.15 111.5C234.8 115.3 216.03 145.68 213.68 149.48C201.57 169.09 212.31 196.36 234.94 199.48C236.57 199.7 238.23 199.81 239.9 199.81C250.6 199.81 260.08 195.05 266.59 187.68C273.1 195.05 282.61 199.81 293.27 199.81C303.98 199.81 313.45 195.05 319.96 187.68C326.47 195.05 335.98 199.81 346.65 199.81C357.35 199.81 366.83 195.05 373.34 187.68C379.88 195.05 389.36 199.81 400.03 199.81C401.73 199.81 403.35 199.7 404.98 199.48C427.69 196.4 438.47 169.13 426.32 149.48C421.62 141.88 405.19 115.3 402.85 111.5ZM389.43 210.18C389.43 212.62 389.43 224.82 389.43 246.77L250.57 246.77C250.57 224.82 250.57 212.62 250.57 210.18C247.09 210.99 243.51 211.58 239.9 211.58C237.73 211.58 235.52 211.43 233.39 211.14C231.36 210.84 229.37 210.37 227.46 209.82C227.46 217.03 227.46 274.75 227.46 281.96C227.46 288.45 232.63 293.69 239.03 293.69C255.23 293.69 384.84 293.69 401.04 293.69C407.44 293.69 412.61 288.45 412.61 281.96C412.61 274.75 412.61 217.03 412.61 209.82C410.66 210.4 408.71 210.88 406.68 211.14C404.47 211.43 402.3 211.58 400.1 211.58C396.48 211.58 392.9 211.03 389.43 210.18Z" id="ebvQlA14I"></path></defs><g><g><g><use xlink:href="#a8icuLH8v" opacity="1" fill="' + order_color + '" fill-opacity="1"></use><g><use xlink:href="#a8icuLH8v" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#d1BN9HSsyo" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#d1BN9HSsyo" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#ebvQlA14I" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#ebvQlA14I" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>' ) },
								});

								pickup_marker[pickup_id].addListener( 'mouseover', function () {
								infowindow.setContent( "<div style='margin:5px'><strong><?php echo esc_html( __( 'Pickup from', 'pwddm' ) ); ?> " + pickup_name + "</strong> <br>  " + pickup_address + "</div>" );
								infowindow.open(pwddm_map, this);
									})
								pickup_marker[pickup_id].addListener( 'mouseout', function () {
										infowindow.close();
								})

							}
						}

						// Orders markers
						if ( order_coordinations != "" ) {
							var order_coordinations_array = order_coordinations.split(",");
							var LatLng = { lat: parseFloat(order_coordinations_array[0]), lng: parseFloat(order_coordinations_array[1]) };
							orderMarker_icon[order_id] = {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="43" height="43"><defs><path d="M155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93C133.95 229 142.03 256.69 155.41 282.22Z" id="e1tjFi6Pp"></path><path d="M155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93C133.95 229 142.03 256.69 155.41 282.22Z" id="a5U0aaXGF4"></path><path d="M491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64Z" id="c4rN26gzGj"></path><path d="M491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64Z" id="cGs9LQreb"></path><path d="M406.78 143.67C407.96 138.16 404.03 132.92 398.72 132.92C389.7 132.92 344.61 132.92 263.44 132.92C261.54 123.07 260.49 117.59 260.28 116.49C259.49 112.4 256.11 109.46 252.18 109.46C248.65 109.46 220.4 109.46 216.86 109.46C212.3 109.46 208.6 113.4 208.6 118.26C208.6 118.84 208.6 123.54 208.6 124.12C208.6 128.98 212.3 132.92 216.86 132.92C218.47 132.92 226.49 132.92 240.94 132.92C255.45 208.46 263.52 250.43 265.13 258.82C259.34 262.36 255.44 269 255.44 276.62C255.44 287.96 264.08 297.15 274.73 297.15C285.38 297.15 294.02 287.96 294.02 276.62C294.02 270.88 291.8 265.68 288.22 261.96C295.44 261.96 353.21 261.96 360.43 261.96C356.86 265.68 354.64 270.88 354.64 276.62C354.64 287.96 363.27 297.15 373.93 297.15C384.58 297.15 393.22 287.96 393.22 276.62C393.22 268.49 388.78 261.47 382.34 258.14C382.53 257.25 384.05 250.13 384.24 249.24C385.41 243.74 381.48 238.5 376.18 238.5C370.01 238.5 339.2 238.5 283.73 238.5L281.47 226.77C342.05 226.77 375.71 226.77 382.44 226.77C386.3 226.77 389.65 223.92 390.5 219.92C393.76 204.67 405.16 151.29 406.78 143.67Z" id="a6edtsLup8"></path></defs><g><g><g><use xlink:href="#e1tjFi6Pp" opacity="1" fill="' + order_color + '" fill-opacity="1"></use><g><use xlink:href="#e1tjFi6Pp" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#a5U0aaXGF4" opacity="1" fill="#6de039" fill-opacity="0"></use><g><use xlink:href="#a5U0aaXGF4" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#c4rN26gzGj" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#c4rN26gzGj" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g><g><use xlink:href="#cGs9LQreb" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#cGs9LQreb" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#a6edtsLup8" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#a6edtsLup8" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>' ) };
							orderMarker[order_id] =new google.maps.Marker({
								position: LatLng,
								map: pwddm_map,
								title: order_number,
								zIndex:99999999,
								icon : orderMarker_icon[order_id],
							});

							if ( move_to_first_position == false ){
								pwddm_map.panTo(orderMarker[order_id].position);
								move_to_first_position = true;
							}

							if ( jQuery("#pwddm_order_id_" + order_id).is(":checked") ) {
								orderMarker[order_id].setIcon(orderMarker_icon[order_id]);
							}

							orderMarker[order_id].addListener( 'mouseover', function () {
								infowindow.setContent( "<div style='margin:5px'><strong><?php echo esc_html( __( 'Order:', 'pwddm' ) ); ?> #" + order_number + "</strong>  <br>  " + order_address + "</div>" );
								infowindow.open(pwddm_map, this);
							})
							orderMarker[order_id].addListener( 'mouseout', function () {
								infowindow.close();
							})

							orderMarker[order_id].addListener( 'click', function () {
								if (  orderMarker[order_id].title == order_number ) {
									add_to_orders_array(order_id);
									orderMarker[order_id].setTitle( "<?php echo esc_html( __( 'Order #', 'pwddm' ) ); ?>" +  order_number + " <?php echo esc_html( __( 'selected', 'pwddm' ) ); ?>" );
									orderMarker[order_id].setIcon(pwddm_marker_green);
								} else
								{
									remove_from_orders_array(order_id);
									orderMarker[order_id].setTitle( order_number );
									orderMarker[order_id].setIcon(orderMarker_icon[order_id]);
								}
								return false;
							});

							// Mark selected orders.
							if ( orderMarker.length ) {
								if ( typeof(orderMarker[order_id]) != 'undefined' ){
									if ( $this.is(":checked") ) {
										orderMarker[order_id].setIcon(pwddm_marker_green);
										pwddm_map.panTo(orderMarker[order_id].position);
									} else {
										orderMarker[order_id].setIcon(orderMarker_icon[order_id]);
									}
								}
							}

					}
				});
	 }

	 function add_to_orders_array(order){
			var checkbox = jQuery("#pwddm_order_id_" + order);
			checkbox.prop("checked",true);
			checkbox.parents("tr").addClass("active");
		}

		function remove_from_orders_array(order){
			var checkbox = jQuery("#pwddm_order_id_" + order);
			checkbox.prop("checked",false);
			checkbox.parents("tr").removeClass("active");
		}

				// Hide orders markers on map.
				jQuery("body").on("click", "#pwddm_hide_on_map", function(){
					jQuery("#pwddm_show_on_map").show();
					jQuery("#pwddm_hide_on_map").hide();
					pwddm_hide_orders_markers();
					return false;
				});

				// Show orders markers on map.
				jQuery("body").on("click", "#pwddm_show_on_map", function(){
					jQuery("#pwddm_show_on_map").hide();
					jQuery("#pwddm_show_on_map_loading").show();

					var pwddm_orders = '';
					jQuery(".order_checkbox").each(function(){
						var pwddm_checkbox 				= jQuery(this);
						var pwddm_order_id 				= pwddm_checkbox.val();
						var pwddm_geocode_location_type = jQuery( "#geocode_location_type_" + pwddm_order_id ).attr("data-order");
						if ( pwddm_checkbox.attr("coordinates") == '' && pwddm_geocode_location_type != "ZERO_RESULTS" ){
							pwddm_orders = pwddm_orders + pwddm_checkbox.attr("value") + ',';
						}
					});
						if ( pwddm_orders != "" ) {

							jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "html",
							data: {
								action: 'pwddm_ajax',
								pwddm_service: 'pwddm_set_order_geocode',
								pwddm_orders : pwddm_orders,
								pwddm_wpnonce: pwddm_nonce,
							},
							success:function(data){

							},
							error: function(request, status, error) {
								console.log(error);
							}
						}).done(function(data){
								show_orders_on_map = true;
								jQuery("#pwddm_action").val("");
								jQuery("#pwddm_orders_form").submit();
							});
					} else {
						pwddm_show_orders_on_map();
					}
					return false;
				});

				// Orders pagination.
				jQuery("body").on("click", "#pwddm_orders_form a.page-link", function(){
					pwddm_delete_orders_markers();
					jQuery("#pwddm_action").val("");
					var formData= jQuery("#pwddm_orders_form").serializeArray();
					var pwddm_page = jQuery(this).attr("href");
					pwddm_page = pwddm_page.replace('?','');
							formData.push({ name: "action", value: "pwddm_ajax" });
							formData.push({ name: "pwddm_service", value: "pwddm_orders" });
							formData.push({ name: "pwddm_manager_id", value: "<?php echo $pwddm_manager_id; ?>" });
							formData.push({ name: "pwddm_page", value: pwddm_page });

							jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "html",
							data: formData,

							success:function(data){
								jQuery("#orders-panel").html(data);
							},
							error: function(request, status, error) {
								console.log(error);
							}
						})
					return false;
				});

				jQuery("body").on("click", "#pwddm_orders_filter_btn", function(event){
						// Handle submit buttons

						var pwddm_orders_filter_btn = jQuery("#pwddm_orders_filter_btn");
						pwddm_orders_filter_btn.html(pwddm_spinner);
						pwddm_orders_filter_btn.prop('disabled', true);
						jQuery("#pwddm_action").val("");
						jQuery("#pwddm_orders_form").submit();
						return false;
				});

				jQuery("body").on("click", "#pwddm_orders_bulk_btn", function(event){
						// Handle submit buttons

						var pwddm_orders_bulk_btn   = jQuery("#pwddm_orders_bulk_btn");
						pwddm_orders_bulk_btn.html(pwddm_spinner);
						pwddm_orders_bulk_btn.prop('disabled', true);
						jQuery("#pwddm_orders_form").submit();
						return false;
				});

				// Submit orders form.
				jQuery("body").on("submit", "#pwddm_orders_form", function(event){
					event.preventDefault();
					var pwddm_orders_filter_btn = jQuery("#pwddm_orders_filter_btn");
					var pwddm_orders_bulk_btn   = jQuery("#pwddm_orders_bulk_btn");
					pwddm_delete_orders_markers();
							var formData= jQuery(this).serializeArray();
							formData.push({ name: "action", value: "pwddm_ajax" });
							formData.push({ name: "pwddm_service", value: "pwddm_orders" });
							formData.push({ name: "pwddm_manager_id", value: "<?php echo $pwddm_manager_id; ?>" });
							if ( show_orders_on_map && jQuery("#pwddm_orders_form .pagination").length ){
								var pwddm_page = jQuery("#pwddm_orders_form .pagination .page-link.current").text();
								formData.push({ name: "pwddm_page", value: pwddm_page });
							}

							jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "html",
							data: formData,

							success:function( data ){},
							error: function(request, status, error) {
								console.log( error );
							}
						}).done(function( data ){

							jQuery("#orders-panel").html(data);
							// Handle buttons
							pwddm_orders_filter_btn.html( pwddm_orders_filter_btn.attr("data") );
							pwddm_orders_bulk_btn.html( pwddm_orders_bulk_btn.attr("data") );
							pwddm_orders_filter_btn.prop('disabled', false);
							pwddm_orders_bulk_btn.prop('disabled', false);

							if ( show_orders_on_map ){
									pwddm_show_orders_on_map();
									show_orders_on_map = false;
								} else {
									jQuery("#pwddm_show_on_map").show();
									jQuery("#pwddm_show_on_map_loading").hide();
									jQuery("#pwddm_hide_on_map").hide();
								}

							//Refresh screen drivers and routes
							if ( jQuery("#pwddm_action").val() != "" )
							{
								refresh_screen();
							}

						});
							return false;
						});

				function pwddm_drivers()
				{
					// Get current driver on screen if exist.
					var pwddm_current_driver = 0 ;
					if (jQuery("#pwddm_drivers_icons a.current").length){
						pwddm_current_driver = jQuery("#pwddm_drivers_icons a.current").index();
					}

					// Get hidden drivers on screen if exist.
					var hidden_drivers = [];
					if (jQuery(".pwddm_driver_box").length){
						jQuery(".pwddm_driver_box").each(function(){
							 if ( ! jQuery(this).hasClass("active") ){
								hidden_drivers.push( jQuery(this).attr("id") );
							 }
						});
					}

					jQuery("#pwddm_drivers_icons").html("");
					jQuery("#driver-panel").html("");

					jQuery("#pwddm_drivers_icons").append('<a href="#" data="orders" title="<?php echo esc_attr( __( 'Orders', 'pwddm' ) ); ?>" id="pwddm_orders_icon"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="database" class="svg-inline--fa fa-database fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M448 73.143v45.714C448 159.143 347.667 192 224 192S0 159.143 0 118.857V73.143C0 32.857 100.333 0 224 0s224 32.857 224 73.143zM448 176v102.857C448 319.143 347.667 352 224 352S0 319.143 0 278.857V176c48.125 33.143 136.208 48.572 224 48.572S399.874 209.143 448 176zm0 160v102.857C448 479.143 347.667 512 224 512S0 479.143 0 438.857V336c48.125 33.143 136.208 48.572 224 48.572S399.874 369.143 448 336z"></path></svg></a>');


					if( typeof pwddm_json['data'] != 'undefined' ){
						jQuery.each(pwddm_json['data'], function(i, data) {
						if(data['route'] != "") {
							jQuery.each(data['route'], function(i, route) {

							   var pwddm_driver_id   		   = route['driver'][0]['id'];
							   var pwddm_driver_name 		   = route['driver'][0]['name'];
							   var pwddm_driver_image		   = route['driver'][0]['image'];
							   var pwddm_driver_color		   = route['driver'][0]['color'];
							   var pwddm_driver_phone 		   = route['driver'][0]['phone'];
							   var pwddm_driver_whatsapp_phone = route['driver'][0]['whatsapp_phone'];
							   var pwddm_driver_availability   = route['driver'][0]['availability'];
							   var pwddm_driver_licence_plate  = route['driver'][0]['licence_plate'];
							   var pwddm_driver_vehicle 	   = route['driver'][0]['vehicle'];
							   var pwddm_driver_address 	   = route['driver'][0]['address'];
							   var pwddm_driver_travel_mode    = route['driver'][0]['travel_mode'];
							   var pwddm_driver_address_coordinates = route['driver'][0]['address_coordinates'];
							   var pwddm_driver_home_icon = '';
							   var pwddm_driver_travel_mode_icon = '<div class="pwddm_tracking"></div>';

							   if ( pwddm_driver_availability == "1" ) {
									pwddm_driver_availability = '<div class="pwddm_driver_availability" style="background-color:#28a745" title="<?php echo esc_attr( __( 'Available', 'pwddm' ) ); ?>"></div>';
							   }else {
									pwddm_driver_availability = '<div class="pwddm_driver_availability" style="background-color:#dc3545" title="<?php echo esc_attr( __( 'Unavailable', 'pwddm' ) ); ?>"></div>';
							   }

							   var pwddm_driver_img 		= '';
							   if ( pwddm_driver_image != ""){
								pwddm_driver_img = '<div class="pwddm_driver_icon_img" style="border-color:' + pwddm_driver_color + '; background-image:url(\''+pwddm_driver_image+'\');" ></div>' + pwddm_driver_availability;
								pwddm_driver_icon  = '<div class="pwddm_driver_icon_img" style="border-color:' + pwddm_driver_color + '; background-image:url(\''+pwddm_driver_image+'\');" ></div>' + pwddm_driver_availability;
							   } else
							   {
								pwddm_driver_img  = '<div class="pwddm_driver_icon_img" style="border-color:' + pwddm_driver_color + '; background-image:url(\'<?php echo plugins_url() . '/' . PWDDM_FOLDER . '/public/images/user.png?ver=' . PWDDM_VERSION; ?>\');" ></div>' + pwddm_driver_availability;
								pwddm_driver_icon = '<div class="pwddm_driver_icon_img" style="border-color:' + pwddm_driver_color + '; background-image:url(\'<?php echo plugins_url() . '/' . PWDDM_FOLDER . '/public/images/user.png?ver=' . PWDDM_VERSION; ?>\');" ></div>' + pwddm_driver_availability;
							   }

							   if ( pwddm_driver_phone != "" ) {
									pwddm_driver_phone = '<div class="pwddm_driver_phone_number">' + '<a title="<?php echo esc_attr( __( 'Call driver:', 'pwddm' ) ); ?> ' + pwddm_driver_phone + '" href = "tel:' + pwddm_driver_phone + '"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="phone" class="svg-inline--fa fa-phone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M493.4 24.6l-104-24c-11.3-2.6-22.9 3.3-27.5 13.9l-48 112c-4.2 9.8-1.4 21.3 6.9 28l60.6 49.6c-36 76.7-98.9 140.5-177.2 177.2l-49.6-60.6c-6.8-8.3-18.2-11.1-28-6.9l-112 48C3.9 366.5-2 378.1.6 389.4l24 104C27.1 504.2 36.7 512 48 512c256.1 0 464-207.5 464-464 0-11.2-7.7-20.9-18.6-23.4z"></path></svg></a> <a href="https://wa.me/'+pwddm_driver_whatsapp_phone+'" target="_blank" title="<?php echo esc_attr( __( 'Send WhatsApp to driver', 'pwddm' ) ); ?>"><svg style="color:#00e676" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="whatsapp" class="svg-inline--fa fa-whatsapp fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg></a></div>';
							   }

							   if ( pwddm_driver_vehicle != "" ) {
									pwddm_driver_vehicle = '<div class="pwddm_driver_vehicle"><?php echo esc_attr( __( 'Vehicle', 'pwddm' ) ); ?>: ' +  pwddm_driver_vehicle + '</div>';
							   }
							   if ( pwddm_driver_licence_plate != "" ) {
									pwddm_driver_licence_plate = '<div class="pwddm_driver_licence_plate"><?php echo esc_attr( __( 'Licence plate', 'pwddm' ) ); ?>: ' +  pwddm_driver_licence_plate + '</div>';
							   }
							   if ( pwddm_driver_address != "" ) {
									pwddm_driver_address = '<div class="pwddm_driver_address">' +  pwddm_driver_address.replaceAll("+"," ") + '</div>';
							   }

							   if ( pwddm_driver_address_coordinates != "" ){
									pwddm_driver_home_icon = '<div class="pwddm_driver_home_icon" id="pwddm_driver_'+pwddm_driver_id+'_home_icon"><a href="#" data="'+pwddm_driver_id+'" title="<?php echo esc_attr( __( 'Driver home', 'pwddm' ) ); ?>"><svg style="width:24px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="home" class="svg-inline--fa fa-home fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="'+pwddm_driver_color+'" d="M280.37 148.26L96 300.11V464a16 16 0 0 0 16 16l112.06-.29a16 16 0 0 0 15.92-16V368a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v95.64a16 16 0 0 0 16 16.05L464 480a16 16 0 0 0 16-16V300L295.67 148.26a12.19 12.19 0 0 0-15.3 0zM571.6 251.47L488 182.56V44.05a12 12 0 0 0-12-12h-56a12 12 0 0 0-12 12v72.61L318.47 43a48 48 0 0 0-61 0L4.34 251.47a12 12 0 0 0-1.6 16.9l25.5 31A12 12 0 0 0 45.15 301l235.22-193.74a12.19 12.19 0 0 1 15.3 0L530.9 301a12 12 0 0 0 16.9-1.6l25.5-31a12 12 0 0 0-1.7-16.93z"></path></svg></a></div>';
							   }

							   var drivers_tabs = '<ul class="nav nav-tabs" class="pwddm_drivers_tabs" role="tablist" style="border-top:3px solid '+pwddm_driver_color+';"><li class="nav-item" role="presentation"><a class="nav-link active" id="driver-orders-tab-'+pwddm_driver_id+'" data-bs-toggle="tab" data-bs-target="#driver-orders-'+pwddm_driver_id+'" type="button" role="tab" aria-controls="profile" aria-selected="true"><?php echo esc_attr( __( 'Route', 'pwddm' ) ); ?></a></li><li class="nav-item" role="presentation"><a class="nav-link" id="driver-direction-tab-'+pwddm_driver_id+'" data-bs-toggle="tab" data-bs-target="#driver-direction-'+pwddm_driver_id+'" type="button" role="tab" aria-controls="home" aria-selected="false"><?php echo esc_attr( __( 'Direction', 'pwddm' ) ); ?></a></li><li class="nav-item" role="presentation"><a class="nav-link driver_route_edit_orders" href="#" data="'+pwddm_driver_id+'"><?php echo esc_attr( __( 'Orders', 'pwddm' ) ); ?></a></li></ul><div class="tab-content"  ><div class="tab-pane fade " id="driver-direction-'+pwddm_driver_id+'" role="tabpanel" aria-labelledby="driver-direction-tab-'+pwddm_driver_id+'"><div class="pwddm_directions-panel-listing container-fluid"></div></div><div class="tab-pane show active fade" id="driver-orders-'+pwddm_driver_id+'" role="tabpanel" aria-labelledby="driver-orders-tab-'+pwddm_driver_id+'"></div></div>';
							   jQuery("#driver-panel").append("<div class='pwddm_driver_box active' data='"+pwddm_driver_id+"' travel_mode='"+pwddm_driver_travel_mode+"' data_color='"+pwddm_driver_color+"' id='driver_"+pwddm_driver_id+"'><div class='row'><div class='col-2'>" + pwddm_driver_img + "</div><div class='col-10 pwddm_driver_info' data='"+pwddm_driver_id+"'><div class='pwddm_driver_name'>" + pwddm_driver_name + "</div>"  + pwddm_driver_address  + pwddm_driver_vehicle + pwddm_driver_licence_plate + pwddm_driver_home_icon  + pwddm_driver_phone + pwddm_driver_travel_mode_icon + "<div title='<?php echo esc_attr( __( 'Show/Hide route', 'pwddm' ) ); ?>' class='pwddm_button '></div> </div></div>  " + drivers_tabs + " </div>");
							   jQuery("#pwddm_drivers_icons").append('<a href="#" id="pwddm_driver_icon_' + pwddm_driver_id + '" data="' + pwddm_driver_id + '" title="'+pwddm_driver_name+'"  class="pwddm_driver_icon">' + pwddm_driver_icon + '</a>');

							})
							// Set current driver.
							jQuery("#pwddm_drivers_icons a").eq(pwddm_current_driver).trigger("click");

							// Hide drivers
							hidden_drivers.forEach(function (item, index) {
								jQuery('#' + item ).removeClass("active");
							 });

						}
					});

					}
				}

				function pwddm_computeTotalDistance(pwddm_driverid,result) {

					var pwddm_totalDist = 0;
					var pwddm_totalTime = 0;
					var pwddm_distance_text = '';
					var pwddm_distance_array = '';
					var pwddm_distance_type = '';

					var pwddm_myroute = result.routes[0];
					for (i = 0; i < pwddm_myroute.legs.length; i++) {
						pwddm_totalTime += pwddm_myroute.legs[i].duration.value;
						pwddm_distance_text = pwddm_myroute.legs[i].distance.text;
						pwddm_distance_array = pwddm_distance_text.split(" ");
						pwddm_totalDist += parseFloat(pwddm_distance_array[0]);
						pwddm_distance_type = pwddm_distance_array[1];
					}
					pwddm_totalTime = (pwddm_totalTime / 60).toFixed(0);
					pwddm_TotalTimeText = pwddm_timeConvert(pwddm_totalTime);

					jQuery("#driver_" + pwddm_driverid ).find(".pwddm_total_route").html( "<b>" + pwddm_TotalTimeText + "</b> <span>(" + (pwddm_totalDist).toFixed(1) + " " + pwddm_distance_type + ")</span> " );
				}

				function pwddm_timeConvert(n) {

					var pwddm_num = n;
					var pwddm_hours = (pwddm_num / 60);
					var pwddm_rhours = Math.floor(pwddm_hours);
					var pwddm_minutes = (pwddm_hours - pwddm_rhours) * 60;
					var pwddm_rminutes = Math.round(pwddm_minutes);
					var pwddm_result = '';
					if (pwddm_rhours > 1) {
						pwddm_result = pwddm_rhours + " " + pwddm_hours_text + " ";
					}
					if (pwddm_rhours == 1) {
						pwddm_result = pwddm_rhours + " " + pwddm_hour_text + " ";
					}
					if (pwddm_rminutes > 0) {
						pwddm_result += pwddm_rminutes + " " + pwddm_mins_text;
					}
					return pwddm_result;
				}

				function pwddm_numtoletter(pwddm_num) {

						var pwddm_s = '',
							pwddm_t;

						while (pwddm_num > 0) {
							pwddm_t = (pwddm_num - 1) % 26;
							pwddm_s = String.fromCharCode(65 + pwddm_t) + pwddm_s;
							pwddm_num = (pwddm_num - pwddm_t) / 26 | 0;
						}
						return pwddm_s || undefined;
					}

				function pwddm_create_map (){
					//Create map
					var rendererOptions = {
						draggable: false,
						suppressMarkers: true,
					};
					  pwddm_directionsService  = new google.maps.DirectionsService();
					  pwddm_directionsRenderer = new google.maps.DirectionsRenderer(rendererOptions);
						pwddm_map = new google.maps.Map(
							document.getElementById('pwddm_map123'), {
								zoom: 6,
								center: { lat: 41.85, lng: -87.65 }
							}
						);
						pwddm_directionsRenderer.setMap(pwddm_map);
						infowindow = new google.maps.InfoWindow();
				}

				jQuery("body").on("click", ".driver_route_edit_orders", function(){
					var pwddm_driver_id = jQuery(this).attr("data");
					jQuery("#route_orders_table_wrap").html("");
					jQuery("#pwddm_orders_icon").trigger("click");
					jQuery("#pwddm_orders_status").val("<?php echo get_option( 'lddfw_out_for_delivery_status', '' ); ?>");
					jQuery("#pwddm_orders_filter").val(pwddm_driver_id);
					if ( jQuery("#pwddm_hide_on_map").is(':visible') ){
						show_orders_on_map = true;
					}
					jQuery("#pwddm_orders_filter_btn").trigger("click" );
					return false;
				});

				jQuery("body").on("click", ".pwddm_driver_box .pwddm_button", function(){
						var pwddm_driver_box = jQuery(this).parents(".pwddm_driver_box");
						if ( pwddm_driver_box.hasClass("active") )  {
							pwddm_driver_box.removeClass("active");
						} else {
							pwddm_driver_box.addClass("active");
						}
						pwddm_create_map();
						pwddm_initMap();

						<?php
						if ( '1' === $lddfw_drivers_tracking_timing ) {
							?>
								drivers_tracking();
							<?php
						}
						?>

						if ( jQuery("#pwddm_hide_on_map").is(':visible') ){
							jQuery("#pwddm_show_on_map").trigger("click");
						}
						return false;
					});

					jQuery("body").on("click",".order_seller_icon",function(){
						if ( jQuery("#pwddm_show_on_map").is(':visible') ){
							jQuery("#pwddm_show_on_map").trigger("click");
						}
						var pwddm_pickup_id = jQuery(this).attr("data");
						 
						 if ( pickup_marker.length ) {
						 
							if ( typeof(pickup_marker[pwddm_pickup_id]) != 'undefined' ){
								
								pwddm_map.panTo( pickup_marker[pwddm_pickup_id].position );
							}
						}
					});

					jQuery("body").on("click",".order_cart_icon",function(){
						if ( jQuery("#pwddm_show_on_map").is(':visible') ){
							jQuery("#pwddm_show_on_map").trigger("click");
						}
						var pwddm_order_id = jQuery(this).attr("data");
						if ( orderMarker.length ) {
							if ( typeof(orderMarker[pwddm_order_id]) != 'undefined' ){
								pwddm_map.panTo( orderMarker[pwddm_order_id].position );
							}
						}
					});

				jQuery("body").on("click",".pwddm_optimize_route",function(){
					var pwddm_driver_box  	= jQuery(this).parents(".pwddm_driver_box");
					var pwddm_driverid    	= pwddm_driver_box.attr("data");
					var pwddm_color 		= pwddm_driver_box.attr("data_color");
					var pwddm_origin 		= pwddm_driver_box.find(".pwddm_driver_route_origin").val();
					var pwddm_destination 	= pwddm_driver_box.find(".pwddm_driver_route_destination").val();
					var pwddm_optimize_route_button = jQuery(this);
					var pwddm_optimize_route_label = pwddm_optimize_route_button.text();

					jQuery(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
					jQuery.ajax({
						type: "POST",
						url: pwddm_ajax_url,
						dataType: "html",
						data: {
							action: 'pwddm_ajax',
							pwddm_service: 'pwddm_plain_route',
							pwddm_wpnonce: pwddm_nonce,
							pwddm_driver_id: pwddm_driverid,
							pwddm_origin_map_address: pwddm_origin,
							pwddm_destination_map_address: pwddm_destination,
						},
						success:function(data){
							 if ( data.trim() == '1' ) {
								refresh_screen();
							 } else {
								pwddm_driver_box.find(".alert").replaceWith("");
								pwddm_driver_box.append(data);
								pwddm_optimize_route_button.html(pwddm_optimize_route_label);
							 }

						},
						error: function(request, status, error) {
							console.log(error);
						}
					})

					return false;
				});

				jQuery("body").on("click",".pwddm_view_driver_route",function(){
					jQuery(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
					refresh_screen();
					return false;
				});

				// Initial map
				function pwddm_initMap() {
				  if( typeof pwddm_json['data'] != 'undefined' ){

					//Set route
					jQuery.each(pwddm_json['data'], function(i, data) {

						if(data['route'] != "") {
							jQuery.each(data['route'], function(i, route) {

								var pwddm_origin 			 = route['origin'];
								var pwddm_destination 		 = route['destination'];
								var pwddm_last_destination   = "";
								var pwddm_waypts_array  	 = [] ;
								var pwddm_color 			 = route['driver'][0]['color'];
								var pwddm_driverid 			 = route['driver'][0]['id'];
								var pwddm_drivername 		 = route['driver'][0]['name'];
								var pwddm_driver_address 	 = route['driver'][0]['address'];
								var pwddm_driver_travel_mode = route['driver'][0]['travel_mode'];
								var pwddm_driver_address_coordinates= route['driver'][0]['address_coordinates'];

								jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).html("<span style='margin-top:10px;display: block;'><?php echo esc_attr( __( 'Driver doesn\'t have a route.', 'pwddm' ) ); ?></span>");
								jQuery("#driver_" + pwddm_driverid ).find("#driver-direction-"+ pwddm_driverid+ ' .pwddm_directions-panel-listing').html("<?php echo esc_attr( __( 'Driver doesn\'t have a route.', 'pwddm' ) ); ?>");

								var drivers_route_orders = '';
								var waypoints_array = [];
								var pwddm_address_options = [];

								jQuery.each(route['waypoints'], function(i, waypoints) {

									if ( i + 1 < route['waypoints'].length )
									{
										pwddm_waypts_array.push( waypoints["address"] );
									}
									else
									{
										if (pwddm_destination == '' || pwddm_destination == 'last_address_on_route') {
											pwddm_last_destination = waypoints["address"];
										} else {
											pwddm_waypts_array.push( waypoints["address"] );
										}
									}
									var sort_handle = '<div class="lddfw_handle_column" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort" class="svg-inline--fa fa-sort fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41zm255-105L177 64c-9.4-9.4-24.6-9.4-33.9 0L24 183c-15.1 15.1-4.4 41 17 41h238c21.4 0 32.1-25.9 17-41z"></path></svg></div>';
									let formated_address = waypoints["address"].replaceAll("+", " ");
									drivers_route_orders = drivers_route_orders + '<div class="pwddm_route_order" data-order="'+waypoints["order"]+'"  data-address="'+waypoints["address"]+'" ><a href="<?php echo pwddm_manager_page_url( '' ); ?>?pwddm_screen=order&pwddm_orderid=' + waypoints["order"] +'" target="_blank">#' + waypoints["order_number"] + '</a><br>' + waypoints["shipping_name"] + '<br>' + formated_address + sort_handle + '</div>';
								});

								if ( drivers_route_orders != '' ) {
									jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).html("");
									jQuery("#driver_" + pwddm_driverid ).find("#driver-direction-"+ pwddm_driverid+ ' .pwddm_directions-panel-listing').html("");
									jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).append("<div><label><?php echo esc_attr( __( 'Origin', 'pwddm' ) ); ?>:</label> <select id='pwddm_driver_route_origin_" + pwddm_driverid + "' class='form-select pwddm_driver_route_origin'></select></div>");
									jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).append("<div style='margin-bottom:10px'><label><?php echo esc_attr( __( 'Destination', 'pwddm' ) ); ?>:</label> <select id='pwddm_driver_route_destination_" + pwddm_driverid + "' class='form-select pwddm_driver_route_destination'></select></div>");
									jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).append('<div class="pwddm_sortable">');

								// Add store address to options.
								if ( pwddm_google_api_origin != "" ){
									// Add store address to origin option.
									if(jQuery.inArray(pwddm_google_api_origin, pwddm_address_options) === -1) {
										pwddm_address_options.push(pwddm_google_api_origin);
										jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).append('<option value="'+pwddm_google_api_origin+'"><?php echo esc_attr( __( 'Pickup', 'pwddm' ) ); ?> - ' + pwddm_google_api_origin.replaceAll("+", " ") + ' </option>');
									}
									// Add store address to destination option.
									jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).append('<option value="'+pwddm_google_api_origin+'"><?php echo esc_attr( __( 'Pickup', 'pwddm' ) ); ?> - ' + pwddm_google_api_origin.replaceAll("+", " ") + ' </option>');
								}

								// Add pickup addresses to options.
								jQuery.each(route['waypoints'], function(i, waypoints){
								if(jQuery.inArray(waypoints["pickup"], pwddm_address_options) === -1) {
									pwddm_address_options.push(waypoints["pickup"]);
									jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).append('<option value="'+ waypoints["pickup"] +'"><?php echo esc_attr( __( 'Pickup', 'pwddm' ) ); ?> - ' + waypoints["pickup"].replaceAll("+", " ") + ' </option>');
									jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).append('<option value="'+ waypoints["pickup"] +'"><?php echo esc_attr( __( 'Pickup', 'pwddm' ) ); ?> - ' + waypoints["pickup"].replaceAll("+", " ") + ' </option>');
								}
								});

								// Add auto option to destination.
								jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).append('<option value="<?php echo esc_attr( __( 'last_address_on_route', 'pwddm' ) ); ?>"><?php echo esc_attr( __( 'Auto - Farthest / Last address on route.', 'pwddm' ) ); ?></option>');

								if ( '' != pwddm_driver_address ) {
									// Add driver home address to options.
									pwddm_address_options.push(pwddm_driver_address);
									jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).append('<option value="'+pwddm_driver_address+'"><?php echo esc_attr( __( 'Home', 'pwddm' ) ); ?> - ' + pwddm_driver_address.replaceAll("+", " ") + '</option>');
									jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).append('<option value="'+pwddm_driver_address+'"><?php echo esc_attr( __( 'Home', 'pwddm' ) ); ?> - ' + pwddm_driver_address.replaceAll("+", " ") + '</option>');
								}

								// Add origin address to options.
								if ( '' != pwddm_origin ){
									if(jQuery.inArray(pwddm_origin, pwddm_address_options) === -1) {
										pwddm_address_options.push(pwddm_origin);
										jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).append('<option value="'+pwddm_origin+'">' + pwddm_origin.replaceAll("+", " ") + ' </option>');
										// Set driver route origin value.
										jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).val( unescapeHtml( pwddm_origin ) );
									}
								}

								// Set selected value of route_destination selectbox.
								if ( 'last_address_on_route' == pwddm_destination || '' == pwddm_destination ){
									jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).val("last_address_on_route");
								} else {
									jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).val( unescapeHtml( pwddm_destination ) );
								}

								// Set selected value of route_origin selectbox.
								if ( '' != pwddm_origin ){
									jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).val( unescapeHtml( pwddm_origin ) );
								}

								// Add order address to option array.
								jQuery.each(route['waypoints'], function(i, waypoints){
									if(jQuery.inArray(waypoints["address"], pwddm_address_options) === -1) {
										//pwddm_address_options.push(waypoints["address"]);
									}
								});

								// Add orders addresses to options.
								jQuery.each(pwddm_address_options, function(i, el){
									//jQuery("#pwddm_driver_route_origin_" + pwddm_driverid ).append('<option value="'+el+'">'+ el.replaceAll("+", " ") +'</option>');
									//jQuery("#pwddm_driver_route_destination_" + pwddm_driverid ).append('<option value="'+el+'">'+ el.replaceAll("+", " ") +'</option>');
								});

								// Add orders to driver route orders
								jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).append( '<div class="pwddm_sortbale">' + drivers_route_orders + '</div>' );

								// Add save route button.
								jQuery("#driver_" + pwddm_driverid ).find("#driver-orders-"+ pwddm_driverid).append( '</div><div class="row"><div class="col-6 d-grid"><button type="button" class="btn btn-block btn-secondary pwddm_optimize_route" ><?php echo esc_attr( __( 'Optimize route', 'pwddm' ) ); ?></button></div><div class="col-6 d-grid"><button style="display:none" type="button" disabled class="btn btn-block btn-primary pwddm_view_driver_route" data="<?php echo esc_attr( __( 'View route', 'pwddm' ) ); ?>" ><?php echo esc_attr( __( 'View route', 'pwddm' ) ); ?></button></div></div>' );
							}

								pwddm_route_array[pwddm_driverid] = [{ "driver_id" : pwddm_driverid , "color": pwddm_color, "origin" : pwddm_origin, "destination" : pwddm_destination, "waypoints" : pwddm_waypts_array }];

								 if ( jQuery( "#driver_" + pwddm_driverid ).hasClass("active") ) {
									// Add drivers icon to map
									if (pwddm_driver_travel_mode == 'bicycling') {
											// bicycle / bike
											var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' + encodeURIComponent(pwddm_color) + '" cx="245.946" cy="-6.426" r="271.25" transform="matrix(1, 0, 0, 0.999999, 234.174728, 285.838593)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M 519.364 181.991 C 538.328 181.991 550.181 160.313 540.697 142.972 C 531.216 125.629 507.511 125.629 498.032 142.972 C 495.87 146.926 494.732 151.412 494.732 155.978 C 494.732 170.345 505.762 181.991 519.364 181.991 Z M 517.313 247.566 C 520.223 250.029 523.846 251.367 527.578 251.359 L 560.419 251.359 C 573.063 251.359 580.961 236.908 574.643 225.346 C 571.709 219.98 566.286 216.675 560.419 216.675 L 533.336 216.675 L 496.782 185.785 C 490.631 180.567 481.829 180.722 475.842 186.154 L 418.364 238.179 C 410.103 245.658 410.89 259.401 419.945 265.776 L 461.889 295.321 L 461.889 355.412 C 461.889 368.763 475.572 377.106 486.521 370.431 C 491.601 367.333 494.732 361.607 494.732 355.412 L 494.732 286.044 C 494.73 280.248 491.988 274.832 487.42 271.616 L 466.194 256.677 L 496.087 229.622 Z M 576.841 268.702 C 526.273 268.702 494.67 326.508 519.956 372.755 C 545.238 418.999 608.445 418.999 633.729 372.755 C 639.495 362.208 642.53 350.247 642.53 338.071 C 642.53 299.759 613.121 268.702 576.841 268.702 Z M 576.841 372.755 C 551.56 372.755 535.757 343.85 548.398 320.729 C 561.041 297.606 592.644 297.606 605.286 320.729 C 608.168 326 609.684 331.98 609.684 338.071 C 609.684 357.227 594.982 372.755 576.841 372.755 Z M 379.779 268.702 C 329.211 268.702 297.607 326.508 322.889 372.755 C 348.174 418.999 411.383 418.999 436.664 372.755 C 442.43 362.208 445.466 350.247 445.466 338.071 C 445.466 299.759 416.056 268.702 379.779 268.702 Z M 379.779 372.755 C 354.493 372.755 338.692 343.85 351.334 320.729 C 363.976 297.606 395.58 297.606 408.219 320.729 C 411.106 326 412.62 331.98 412.62 338.071 C 412.62 357.227 397.918 372.755 379.779 372.755 Z" style="fill: rgba(255, 255, 255, 0.8);"/></svg>';
										} else if (pwddm_driver_travel_mode == 'walking') {
											// walking.
											var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' + encodeURIComponent(pwddm_color) + '" cx="329.832" cy="83.158" r="271.25" transform="matrix(1, 0, 0, 0.999999, 150.363403, 197.037201)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M506.976,189.965c14.908,0,27.005-12.096,27.005-27.005c0-14.908-12.097-27.004-27.004-27.004  c-14.909,0-27.005,12.096-27.005,27.004C479.971,177.869,492.066,189.965,506.976,189.965z M560.141,273.847l-13.108-6.639  l-5.456-16.54c-8.271-25.092-31.337-42.646-57.497-42.7c-20.254-0.057-31.449,5.682-52.489,14.177  c-12.153,4.896-22.11,14.178-27.961,25.992l-3.77,7.65c-4.388,8.89-0.844,19.69,7.989,24.136c8.776,4.442,19.466,0.844,23.911-8.045  l3.77-7.651c1.97-3.938,5.231-7.032,9.282-8.664l15.078-6.076l-8.552,34.149c-2.925,11.701,0.226,24.135,8.382,33.08l33.7,36.793  c4.05,4.443,6.92,9.789,8.383,15.584l10.295,41.237c2.42,9.62,12.209,15.527,21.829,13.108s15.527-12.209,13.107-21.829  l-12.489-50.069c-1.462-5.796-4.331-11.195-8.383-15.585l-25.599-27.961l9.678-38.648l3.095,9.282  c2.981,9.058,9.396,16.541,17.834,20.815l13.107,6.638c8.775,4.444,19.466,0.845,23.909-8.045  C572.518,289.205,568.973,278.291,560.141,273.847L560.141,273.847z M431.363,353.003c-1.8,4.557-4.501,8.663-7.989,12.096  l-28.129,28.187c-7.033,7.032-7.033,18.452,0,25.484c7.032,7.032,18.396,7.032,25.429,0l33.417-33.417  c3.433-3.434,6.133-7.539,7.99-12.096l7.595-19.018c-31.111-33.924-21.772-23.516-26.667-30.211L431.363,353.003z" style="fill: rgb(255, 255, 255);"/></svg>';
										} else {
											// driving
											var icon = 'data:image/svg+xml,<svg focusable="false" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle style="fill: rgb(255, 255, 255);" cx="475.32" cy="274.687" r="271" transform="matrix(1, 0, 0, 1.000002, 4.736413, 5.999592)"/><circle fill="' + encodeURIComponent(pwddm_color) + '" cx="480.579" cy="283.21" r="249.982"/><path d="M 612.159 236.667 L 579.728 236.667 L 570.715 214.134 C 561.454 190.966 539.349 176 514.394 176 L 445.602 176 C 420.653 176 398.542 190.966 389.274 214.133 L 380.26 236.666 L 347.836 236.666 C 343.606 236.666 340.502 240.642 341.531 244.742 L 344.781 257.742 C 345.501 260.635 348.101 262.666 351.086 262.666 L 361.958 262.666 C 354.684 269.02 349.998 278.255 349.998 288.666 L 349.998 314.666 C 349.998 323.396 353.334 331.277 358.665 337.378 L 358.665 366.666 C 358.665 376.235 366.427 383.998 375.998 383.998 L 393.331 383.998 C 402.902 383.998 410.664 376.235 410.664 366.666 L 410.664 349.332 L 549.331 349.332 L 549.331 366.666 C 549.331 376.235 557.094 383.998 566.665 383.998 L 583.998 383.998 C 593.569 383.998 601.331 376.235 601.331 366.666 L 601.331 337.378 C 606.661 331.283 609.998 323.401 609.998 314.666 L 609.998 288.666 C 609.998 278.254 605.312 269.019 598.044 262.666 L 608.915 262.666 C 611.899 262.666 614.499 260.635 615.22 257.742 L 618.47 244.742 C 619.494 240.643 616.39 236.667 612.159 236.667 Z M 421.46 227.009 C 425.409 217.14 434.969 210.667 445.602 210.667 L 514.394 210.667 C 525.029 210.667 534.588 217.14 538.538 227.009 L 549.331 254 L 410.665 254 L 421.46 227.009 Z M 393.332 314.559 C 382.932 314.559 375.999 307.647 375.999 297.28 C 375.999 286.913 382.932 280.002 393.332 280.002 C 403.732 280.002 419.332 295.553 419.332 305.921 C 419.332 316.285 403.731 314.559 393.332 314.559 L 393.332 314.559 Z M 566.665 314.559 C 556.265 314.559 540.665 316.287 540.665 305.918 C 540.665 295.551 556.265 280 566.665 280 C 577.064 280 583.998 286.911 583.998 297.278 C 583.998 307.647 577.064 314.559 566.665 314.559 Z" style="fill: rgb(255, 255, 255);"/></svg>';
										}

									driverMarker[pwddm_driverid] = new google.maps.Marker({
									map: pwddm_map,
									icon : icon,
									zIndex:99999999,
									});

									// Click on the driver icon
									google.maps.event.addListener(driverMarker[pwddm_driverid], 'click', function () {
										infowindow.setContent( "<div style='margin:5px' ><?php echo esc_attr( __( 'Driver', 'pwddm' ) ); ?>: " + pwddm_drivername + "</div>" );
										infowindow.open(pwddm_map, driverMarker[pwddm_driverid]);
										pwddm_map.setZoom(16);
										pwddm_map.panTo(driverMarker[pwddm_driverid].position);
									});

									if ( '' != pwddm_driver_address_coordinates ) {
											var pwddm_driver_address_coordinates_array = pwddm_driver_address_coordinates.split(",");
											var latlng = new google.maps.LatLng(parseFloat( pwddm_driver_address_coordinates_array[0] ), parseFloat( pwddm_driver_address_coordinates_array[1] ));
											var svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 640 640" width="55" height="55"><defs><path d="M133.95 200.93C133.95 229 142.03 256.69 155.41 282.22C206.38 380.69 307.46 484.13 307.46 612.56C307.46 619.49 313.08 625.11 320.01 625.11C326.94 625.11 332.55 619.5 332.55 612.56C332.55 484.13 433.62 380.68 484.6 282.22C497.98 256.69 506.06 229 506.06 200.93C506.06 98.17 422.77 14.88 320.01 14.88C217.24 14.88 133.95 98.17 133.95 200.93Z" id="c4CdKfyNA"></path><path d="M513.49 200.93C513.49 229.2 505.69 257.98 491.2 285.64C477.57 311.97 476.05 314.38 429.15 386.54C365.78 484.04 339.99 542.03 339.99 612.58C339.99 623.62 331.04 632.57 320 632.57C308.96 632.57 300.01 623.62 300.01 612.58C300.01 542.03 274.22 484.04 210.85 386.54C163.94 314.38 162.43 311.97 148.81 285.67C134.31 257.98 126.51 229.2 126.51 200.93C126.51 94.07 213.13 7.44 320 7.44C426.87 7.44 513.49 94.07 513.49 200.93Z" id="aXIf52Mz"></path><path d="M239.48 218.17C239.48 259.37 239.48 282.26 239.48 286.84C239.48 290.54 242.56 293.55 246.36 293.55C251.18 293.53 289.75 293.44 294.57 293.42C298.36 293.41 301.42 290.41 301.42 286.72C301.42 282.71 301.42 250.63 301.42 246.62C301.42 242.91 304.51 239.91 308.31 239.91C311.06 239.91 333.09 239.91 335.84 239.91C339.64 239.91 342.73 242.91 342.73 246.62C342.73 250.62 342.73 282.68 342.73 286.69C342.71 290.39 345.79 293.4 349.59 293.42C349.59 293.42 349.6 293.42 349.61 293.42C354.43 293.43 392.98 293.53 397.8 293.55C401.61 293.55 404.69 290.54 404.69 286.84C404.69 282.26 404.69 259.35 404.69 218.13C357.1 179.98 330.67 158.78 325.38 154.55C323.46 153.04 320.72 153.04 318.8 154.55C308.22 163.03 281.78 184.24 239.48 218.17ZM408.13 168.92C408.13 134.1 408.13 114.75 408.13 110.88C408.13 108.11 405.82 105.85 402.97 105.85C400.56 105.85 381.28 105.85 378.87 105.85C376.02 105.85 373.71 108.11 373.71 110.88C373.71 112.91 373.71 123.05 373.71 141.31C350.6 122.79 337.76 112.5 335.19 110.44C327.57 104.33 316.57 104.33 308.95 110.44C298.06 119.18 210.93 189.06 200.04 197.79C197.85 199.56 197.54 202.73 199.35 204.87C199.35 204.87 199.36 204.87 199.36 204.87C200.45 206.17 209.23 216.56 210.33 217.86C212.14 220.01 215.39 220.31 217.59 218.55C217.6 218.55 217.6 218.55 217.6 218.54C227.72 210.43 308.68 145.48 318.8 137.37C320.72 135.86 323.46 135.86 325.38 137.37C335.5 145.48 416.47 210.43 426.59 218.54C428.78 220.31 432.04 220.02 433.85 217.88C433.86 217.88 433.86 217.87 433.86 217.87C434.95 216.57 443.73 206.18 444.83 204.88C446.64 202.74 446.32 199.57 444.12 197.81C444.11 197.8 444.1 197.8 444.1 197.79C439.3 193.94 427.31 184.32 408.13 168.92Z" id="e8LjRMauUK"></path></defs><g><g><g><use xlink:href="#c4CdKfyNA" opacity="1" fill="'+pwddm_color+'" fill-opacity="1"></use><g><use xlink:href="#c4CdKfyNA" opacity="1" fill-opacity="0" stroke="#fff" stroke-width="20" stroke-opacity="1"></use></g></g><g><use xlink:href="#aXIf52Mz" opacity="1" fill="#000000" fill-opacity="0"></use><g><use xlink:href="#aXIf52Mz" opacity="1" fill-opacity="0" stroke="#ffffff" stroke-width="1" stroke-opacity="1"></use></g></g><g><use xlink:href="#e8LjRMauUK" opacity="1" fill="#fff" fill-opacity="1"></use><g><use xlink:href="#e8LjRMauUK" opacity="1" fill-opacity="0" stroke="#000000" stroke-width="1" stroke-opacity="0"></use></g></g></g></g></svg>';
											driver_home_marker[pwddm_driverid] = new google.maps.Marker({
											map: pwddm_map,
											zIndex:99999999,
											icon : {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( svg ) },
											position: latlng,
										});
											// mouse over on the driver home icon
											google.maps.event.addListener(driver_home_marker[pwddm_driverid], 'mouseover', function () {
											infowindow.setContent("<strong><?php echo esc_html( __( 'Driver Home:', 'pwddm' ) ); ?> " + pwddm_drivername + '</strong><br>' + pwddm_driver_address.replaceAll( "+", " " ));
											infowindow.open(pwddm_map, driver_home_marker[pwddm_driverid]);

										});

											// click on the driver home icon
											google.maps.event.addListener(driver_home_marker[pwddm_driverid], 'click', function () {
												jQuery( '#pwddm_driver_icon_' + pwddm_driverid ).trigger("click");
										});

										driver_home_marker[pwddm_driverid].addListener( 'mouseout', function () {
											infowindow.close();
										})

									}

									if (pwddm_destination == '' || pwddm_destination == 'last_address_on_route') {
										pwddm_destination = pwddm_last_destination;
									}
									if ( drivers_route_orders != '' )
									{
										pwddm_calculateAndDisplayRoute(pwddm_driver_travel_mode,false,pwddm_driverid,pwddm_color,pwddm_map,pwddm_directionsService,pwddm_destination,pwddm_origin,pwddm_waypts_array);
									}

								 }
							});

								// Save route origin.
								jQuery("body").on("change",".pwddm_driver_route_origin",function(){
									var pwddm_elem 		= jQuery(this).parents(".pwddm_driver_box");
									var pwddm_driver_id = pwddm_elem.attr("data");
									clearTimeout(route_timer);
									pwddm_save_driver_route( pwddm_driver_id );
								});

								// Save route destination.
								jQuery("body").on("change",".pwddm_driver_route_destination",function(){
									var pwddm_elem 		= jQuery(this).parents(".pwddm_driver_box");
									var pwddm_driver_id = pwddm_elem.attr("data");
									clearTimeout(route_timer);
									pwddm_save_driver_route( pwddm_driver_id );
								});

								// Sortable event.
								jQuery(".pwddm_sortbale").sortable(
									{
										start: function( event, ui ) {
											clearTimeout(route_timer);
										},
										update: function( event, ui ) {
											var pwddm_elem = ui.item.parents(".pwddm_driver_box");
											var pwddm_driver_id = pwddm_elem.attr("data");
											pwddm_save_driver_route(pwddm_driver_id);
										}
									}
								);
						}
					});

				}
			}

						  <?php
							if ( '1' === $lddfw_drivers_tracking_timing ) {
								?>
									//Click on driver location icon
									jQuery("body").on("click",".pwddm_driver_info .pwddm_tracking a.active",function(){
										var lddfw_driver_id = jQuery(this).parent().parent().attr("data");
										if (  driverMarker[lddfw_driver_id] ){
											pwddm_map.setZoom(16);
											pwddm_map.panTo(driverMarker[lddfw_driver_id].position);
										}
									return false;
									});
								<?php
							}
							?>

			function unescapeHtml(safe) {
				return safe.replace(/&quot;/g, '"')
					.replace(/&#039;/g, "'");
			}

			function escapeHtml(unsafe) {
				return unsafe.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
			}


				function pwddm_calculateAndDisplayRoute(pwddm_driver_travel_mode,optimize_route,pwddm_driverid,color,pwddm_map,directionsService , pwddm_destination_address,pwddm_google_api_origin,pwddm_waypts_array) {

					var pwddm_waypts = [];

					pwddm_waypts_array.forEach(function (item, index) {

						pwddm_waypts.push({
							location: item,
							stopover: true
						});
					});

					setdirectionsService( pwddm_driver_travel_mode,optimize_route, pwddm_driverid,color,pwddm_map,directionsService,pwddm_waypts,pwddm_destination_address,pwddm_google_api_origin);
				}
				let tracking_timer;
				function drivers_tracking(){
					clearTimeout(tracking_timer);
					jQuery.ajax({
							type: "POST",
							url: pwddm_ajax_url,
							dataType: "json",
							data: {
								action: 'lddfw_ajax',
								lddfw_service: 'lddfw_drivers_locations',
								lddfw_wpnonce: pwddm_nonce.nonce,
								lddfw_data_type: 'json'
							},
							success: function(data) {
								var lddfw_counter = 0;
								jQuery.each( data, function( key, val ) {
									var driver_id = val.driver;
									var latv = val.lat ;
									var lonv = val.long ;
									lddfw_counter = lddfw_counter + 1;
									var tracking_status = val.tracking ;

									var pwddm_driver_travel_mode = jQuery("#driver_" + driver_id ).attr("travel_mode");

									var lddfw_color= "#cdcdcd";
									if ( '1' == tracking_status ) {
									  lddfw_color= jQuery("#driver_" + driver_id ).attr("data_color");
									}

									if (pwddm_driver_travel_mode == 'bicycling') {
											// bicycle / bike
											var icon = '<svg focusable="false" x="0px" y="0px" style="width:26px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' +  lddfw_color  + '" cx="245.946" cy="-6.426" r="271.25" transform="matrix(1, 0, 0, 0.999999, 234.174728, 285.838593)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M 519.364 181.991 C 538.328 181.991 550.181 160.313 540.697 142.972 C 531.216 125.629 507.511 125.629 498.032 142.972 C 495.87 146.926 494.732 151.412 494.732 155.978 C 494.732 170.345 505.762 181.991 519.364 181.991 Z M 517.313 247.566 C 520.223 250.029 523.846 251.367 527.578 251.359 L 560.419 251.359 C 573.063 251.359 580.961 236.908 574.643 225.346 C 571.709 219.98 566.286 216.675 560.419 216.675 L 533.336 216.675 L 496.782 185.785 C 490.631 180.567 481.829 180.722 475.842 186.154 L 418.364 238.179 C 410.103 245.658 410.89 259.401 419.945 265.776 L 461.889 295.321 L 461.889 355.412 C 461.889 368.763 475.572 377.106 486.521 370.431 C 491.601 367.333 494.732 361.607 494.732 355.412 L 494.732 286.044 C 494.73 280.248 491.988 274.832 487.42 271.616 L 466.194 256.677 L 496.087 229.622 Z M 576.841 268.702 C 526.273 268.702 494.67 326.508 519.956 372.755 C 545.238 418.999 608.445 418.999 633.729 372.755 C 639.495 362.208 642.53 350.247 642.53 338.071 C 642.53 299.759 613.121 268.702 576.841 268.702 Z M 576.841 372.755 C 551.56 372.755 535.757 343.85 548.398 320.729 C 561.041 297.606 592.644 297.606 605.286 320.729 C 608.168 326 609.684 331.98 609.684 338.071 C 609.684 357.227 594.982 372.755 576.841 372.755 Z M 379.779 268.702 C 329.211 268.702 297.607 326.508 322.889 372.755 C 348.174 418.999 411.383 418.999 436.664 372.755 C 442.43 362.208 445.466 350.247 445.466 338.071 C 445.466 299.759 416.056 268.702 379.779 268.702 Z M 379.779 372.755 C 354.493 372.755 338.692 343.85 351.334 320.729 C 363.976 297.606 395.58 297.606 408.219 320.729 C 411.106 326 412.62 331.98 412.62 338.071 C 412.62 357.227 397.918 372.755 379.779 372.755 Z" style="fill: rgba(255, 255, 255, 0.8);"/></svg>';
										} else if (pwddm_driver_travel_mode == 'walking') {
											// walking.
											var icon = '<svg focusable="false" x="0px" y="0px"  style="width:26px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle fill="' + lddfw_color  + '" cx="329.832" cy="83.158" r="271.25" transform="matrix(1, 0, 0, 0.999999, 150.363403, 197.037201)"/><path d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" style="fill: rgb(255, 255, 255);"/><path d="M506.976,189.965c14.908,0,27.005-12.096,27.005-27.005c0-14.908-12.097-27.004-27.004-27.004  c-14.909,0-27.005,12.096-27.005,27.004C479.971,177.869,492.066,189.965,506.976,189.965z M560.141,273.847l-13.108-6.639  l-5.456-16.54c-8.271-25.092-31.337-42.646-57.497-42.7c-20.254-0.057-31.449,5.682-52.489,14.177  c-12.153,4.896-22.11,14.178-27.961,25.992l-3.77,7.65c-4.388,8.89-0.844,19.69,7.989,24.136c8.776,4.442,19.466,0.844,23.911-8.045  l3.77-7.651c1.97-3.938,5.231-7.032,9.282-8.664l15.078-6.076l-8.552,34.149c-2.925,11.701,0.226,24.135,8.382,33.08l33.7,36.793  c4.05,4.443,6.92,9.789,8.383,15.584l10.295,41.237c2.42,9.62,12.209,15.527,21.829,13.108s15.527-12.209,13.107-21.829  l-12.489-50.069c-1.462-5.796-4.331-11.195-8.383-15.585l-25.599-27.961l9.678-38.648l3.095,9.282  c2.981,9.058,9.396,16.541,17.834,20.815l13.107,6.638c8.775,4.444,19.466,0.845,23.909-8.045  C572.518,289.205,568.973,278.291,560.141,273.847L560.141,273.847z M431.363,353.003c-1.8,4.557-4.501,8.663-7.989,12.096  l-28.129,28.187c-7.033,7.032-7.033,18.452,0,25.484c7.032,7.032,18.396,7.032,25.429,0l33.417-33.417  c3.433-3.434,6.133-7.539,7.99-12.096l7.595-19.018c-31.111-33.924-21.772-23.516-26.667-30.211L431.363,353.003z" style="fill: rgb(255, 255, 255);"/></svg>';
										} else {
											// driving
											var icon = '<svg focusable="false" x="0px" y="0px" style="width:26px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xmlns="http://www.w3.org/2000/svg"><circle style="fill: rgb(255, 255, 255);" cx="475.32" cy="274.687" r="271" transform="matrix(1, 0, 0, 1.000002, 4.736413, 5.999592)"/><circle fill="' +  lddfw_color  + '" cx="480.579" cy="283.21" r="249.982"/><path d="M 612.159 236.667 L 579.728 236.667 L 570.715 214.134 C 561.454 190.966 539.349 176 514.394 176 L 445.602 176 C 420.653 176 398.542 190.966 389.274 214.133 L 380.26 236.666 L 347.836 236.666 C 343.606 236.666 340.502 240.642 341.531 244.742 L 344.781 257.742 C 345.501 260.635 348.101 262.666 351.086 262.666 L 361.958 262.666 C 354.684 269.02 349.998 278.255 349.998 288.666 L 349.998 314.666 C 349.998 323.396 353.334 331.277 358.665 337.378 L 358.665 366.666 C 358.665 376.235 366.427 383.998 375.998 383.998 L 393.331 383.998 C 402.902 383.998 410.664 376.235 410.664 366.666 L 410.664 349.332 L 549.331 349.332 L 549.331 366.666 C 549.331 376.235 557.094 383.998 566.665 383.998 L 583.998 383.998 C 593.569 383.998 601.331 376.235 601.331 366.666 L 601.331 337.378 C 606.661 331.283 609.998 323.401 609.998 314.666 L 609.998 288.666 C 609.998 278.254 605.312 269.019 598.044 262.666 L 608.915 262.666 C 611.899 262.666 614.499 260.635 615.22 257.742 L 618.47 244.742 C 619.494 240.643 616.39 236.667 612.159 236.667 Z M 421.46 227.009 C 425.409 217.14 434.969 210.667 445.602 210.667 L 514.394 210.667 C 525.029 210.667 534.588 217.14 538.538 227.009 L 549.331 254 L 410.665 254 L 421.46 227.009 Z M 393.332 314.559 C 382.932 314.559 375.999 307.647 375.999 297.28 C 375.999 286.913 382.932 280.002 393.332 280.002 C 403.732 280.002 419.332 295.553 419.332 305.921 C 419.332 316.285 403.731 314.559 393.332 314.559 L 393.332 314.559 Z M 566.665 314.559 C 556.265 314.559 540.665 316.287 540.665 305.918 C 540.665 295.551 556.265 280 566.665 280 C 577.064 280 583.998 286.911 583.998 297.278 C 583.998 307.647 577.064 314.559 566.665 314.559 Z" style="fill: rgb(255, 255, 255);"/></svg>';
										}

									if ( '1' == tracking_status ) {
										jQuery("#driver_" + driver_id + ' .pwddm_tracking').html("<a href='#' title='<?php echo esc_attr( __( 'Tracking is on', 'pwddm' ) ); ?>' class='active'>"+icon+"</a>");
									}
									else
									{
										jQuery("#driver_" + driver_id + ' .pwddm_tracking').html("<a href='#' title='<?php echo esc_attr( __( 'Tracking is off', 'pwddm' ) ); ?>'>"+icon+"</a>");
									}

									if ( latv != '' && lonv != '' && '1' == tracking_status ) {
										var latlng = new google.maps.LatLng(latv,lonv);
										if ( driverMarker[driver_id] )  {
											driverMarker[driver_id].setVisible(true);
											driverMarker[driver_id].setPosition(latlng);
										}
									} else {
										if ( driverMarker[driver_id] )  {
											driverMarker[driver_id].setVisible(false);
										}
									}
								});

								tracking_timer = setInterval(function(){
											drivers_tracking();
										}, '120000' );

							},
							error: function(request, status, error) {}
						})
				}

				function setdirectionsService(pwddm_driver_travel_mode,optimize_route,pwddm_driverid,color,pwddm_map,directionsService,pwddm_waypts,pwddm_destination_address,pwddm_google_api_origin){

					directionsService.route({
						origin: pwddm_google_api_origin,
						destination: pwddm_destination_address,
						waypoints: pwddm_waypts,
						optimizeWaypoints: optimize_route,
						travelMode: pwddm_driver_travel_mode
					},
					function(response, status) {
						if (status == 'OK') {

							var directionsRenderer = new google.maps.DirectionsRenderer(
							{ 	polylineOptions: { strokeColor: color, strokeWeight: 6 } }
							);
							directionsRenderer.setMap(pwddm_map);
							directionsRenderer.setDirections(response);
							var pwddm_route = response.routes[0];
							var pwddm_summaryPanel = jQuery( "#driver_" + pwddm_driverid ).find( ".pwddm_directions-panel-listing" );
							pwddm_summaryPanel.html('<div class="pwddm_total_route"></div>') ;
							var pwddm_last_address = '';

							// For each route, display summary information.
							for (var i = 0; i < pwddm_route.legs.length; i++) {
								var pwddm_routeSegment = i + 1;
								if (pwddm_last_address != pwddm_route.legs[i].start_address) {
									pwddm_summaryPanel.append('<div class="row pwddm_address"><div class="col-2  col-md-3 text-center" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="pwddm_point">' + pwddm_numtoletter(pwddm_routeSegment) + '</span></div><div class="col-10 col-md-9">' + pwddm_route.legs[i].start_address + '</div></div>');
								}
								pwddm_summaryPanel.append( '<div class="row pwddm_drive"><div class="col-2  col-md-3 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa fa-ellipsis-v up fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="car" class="svg-inline--fa fa-car fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M499.99 176h-59.87l-16.64-41.6C406.38 91.63 365.57 64 319.5 64h-127c-46.06 0-86.88 27.63-103.99 70.4L71.87 176H12.01C4.2 176-1.53 183.34.37 190.91l6 24C7.7 220.25 12.5 224 18.01 224h20.07C24.65 235.73 16 252.78 16 272v48c0 16.12 6.16 30.67 16 41.93V416c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-32h256v32c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-54.07c9.84-11.25 16-25.8 16-41.93v-48c0-19.22-8.65-36.27-22.07-48H494c5.51 0 10.31-3.75 11.64-9.09l6-24c1.89-7.57-3.84-14.91-11.65-14.91zm-352.06-17.83c7.29-18.22 24.94-30.17 44.57-30.17h127c19.63 0 37.28 11.95 44.57 30.17L384 208H128l19.93-49.83zM96 319.8c-19.2 0-32-12.76-32-31.9S76.8 256 96 256s48 28.71 48 47.85-28.8 15.95-48 15.95zm320 0c-19.2 0-48 3.19-48-15.95S396.8 256 416 256s32 12.76 32 31.9-12.8 31.9-32 31.9z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa down fa-ellipsis-v fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg> </div><div class="col-10 col-md-9 middle"  ><b>' + pwddm_route.legs[i].duration.text + "</b><br>" + pwddm_route.legs[i].distance.text + '</div></div></div>' );
								pwddm_summaryPanel.append( '<div class="row pwddm_address"><div class="col-2  col-md-3 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="pwddm_point">' + pwddm_numtoletter((pwddm_routeSegment + 1) * 1) + '</span></div><div class="col-10 col-md-9">' + pwddm_route.legs[i].end_address + '</div></div>' );
								pwddm_last_address = pwddm_route.legs[i].end_address;
							}

							pwddm_computeTotalDistance(pwddm_driverid,response);

						} else {
							var pwddm_summaryPanel = jQuery( "#driver_" + pwddm_driverid ).find( ".pwddm_directions-panel-listing" );
							pwddm_summaryPanel.html('<div class="pwddm_total_route"></div>') ;
							pwddm_summaryPanel.append('Directions request failed due to ' + status);
						}
					}
				);
				}

				function refresh_screen(){
					jQuery.when( pwddm_get_routes_json () ).done(function( data ){
						var pwddm_json = data;
						jQuery( "#pwddm_routes_notice").hide();
						pwddm_drivers();
						pwddm_create_map();
						pwddm_initMap();
						jQuery( "#pwddm_routes").show();

					});
				}

				// Sort route orders.
				function pwddm_save_driver_route( driver_id ){

					var view_route_button = jQuery("#driver_" + driver_id ).find(".pwddm_view_driver_route");
					view_route_button.prop('disabled', true);
					view_route_button.html(pwddm_spinner);
					view_route_button.show();

					route_timer 		= setTimeout(function() {
							var route_orders_sorted = '';

							var pwddm_elem = '';
							jQuery(".pwddm_driver_box").each(function(){
								if ( jQuery(this).attr("data") == driver_id ) {
									pwddm_elem = jQuery(this);
								}
							});

							pwddm_elem.find(".pwddm_route_order").each(function(){
								route_orders_sorted = route_orders_sorted + jQuery(this).attr("data-order") + "," ;
							});

							var pwddm_origin_map_address      = pwddm_elem.find(".pwddm_driver_route_origin").val();
							var pwddm_destination_map_address = pwddm_elem.find(".pwddm_driver_route_destination").val();
							var pwddm_origin_address 		  = pwddm_elem.find(".pwddm_driver_route_origin option:selected").text();
							var pwddm_destination_address	  = pwddm_elem.find(".pwddm_driver_route_destination option:selected").text();

							jQuery.ajax({
								type: "POST",
								url: pwddm_ajax_url,
								dataType: "html",
								data: {
									action: 'pwddm_ajax',
									pwddm_service: 'pwddm_save_route',
									pwddm_wpnonce: pwddm_nonce,
									pwddm_orders_list: route_orders_sorted,
									pwddm_driver_id: driver_id,
									pwddm_origin_map_address: pwddm_origin_map_address,
									pwddm_destination_map_address: pwddm_destination_map_address,
									pwddm_origin_address: pwddm_origin_address,
									pwddm_destination_address: pwddm_destination_address,
								},
								success:function(data){
									view_route_button.prop('disabled', false);
									view_route_button.html(view_route_button.attr("data"));
									view_route_button.show();
								},
								error: function(request, status, error) {
									console.log(error);
								}
							})
						}, 3000 );
					}

				function pwddm_screen(){

					var head = document.getElementsByTagName('head')[0];
					var script = document.createElement('script');
					script.type = 'text/javascript';
					script.src = "https://maps.googleapis.com/maps/api/js?v=3&key=<?php echo $pwddm_google_api_key; ?>";
					head.appendChild(script);

					// Create orders
					pwddm_orders();

					//Load routes
					jQuery.when( pwddm_get_routes_json () ).done(function( data ){

					jQuery( "#pwddm_routes_notice").hide();
					var pwddm_json = data;

					//Create drivers
					pwddm_drivers();
					//Create map
					pwddm_create_map();
					//Init map
					pwddm_initMap();

					<?php
					if ( '1' === $lddfw_drivers_tracking_timing ) {
						?>
								drivers_tracking();
						<?php
					}
					?>

					if( typeof pwddm_json['data'] != 'undefined' ){
						 jQuery( "#pwddm_routes").show();
						} else
						{
						 jQuery("#pwddm_orders_icon").trigger("click");
						}
					});
				}
				pwddm_screen();
				</script>
				<?php

	}

	/**
	 * Set order geocode.
	 */
	public function set_order_geocode_service__premium_only() {
		$pwddm_orders = ( isset( $_POST['pwddm_orders'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_orders'] ) ) : '';
		if ( '' != $pwddm_orders ) {
			$array = explode( ',', $pwddm_orders );
			foreach ( $array as $order_id ) {
				if ( '' !== $order_id ) {
					$this->set_order_geocode( $order_id );
					usleep( 100000 );
				}
			}
		}
	}

	/**
	 * Get geocode.
	 *
	 * @param string $map_address address.
	 * @return statement
	 */
	public function get_geocode( $map_address ) {
		$pwddm_google_api_key = $this->pwddm_google_api_key_server;
		if ( '' === $pwddm_google_api_key ) {
			$pwddm_google_api_key = $this->pwddm_google_api_key;
		}

		$coordinations     = '';
		$formatted_address = '';
		$location_type     = '';
		$status            = '';
		$lat               = '';
		$lng               = '';

		if ( '' !== $pwddm_google_api_key ) {
			$url = 'https://maps.google.com/maps/api/geocode/json?sensor=false&language=en&key=' . $pwddm_google_api_key . '&address=' . $map_address;
			$ch  = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_PROXYPORT, 3128 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$response_a = json_decode( $response );

			$coordinations     = '';
			$formatted_address = '';
			$location_type     = '';
			$status            = '';
			$lat               = '';
			$lng               = '';
			if ( json_last_error() === 0 ) {
				$status = $response_a->status;
				if ( 'OK' === $status ) {
					$lat               = $response_a->results[0]->geometry->location->lat;
					$lng               = $response_a->results[0]->geometry->location->lng;
					$coordinations     = $lat . ',' . $lng;
					$formatted_address = $response_a->results[0]->formatted_address;
					$location_type     = $response_a->results[0]->geometry->location_type;
				}
			}
		}
		 return array( $status, $coordinations, $formatted_address, $location_type, $lat, $lng );
	}

	/**
	 * Set store geocode
	 *
	 * @return void
	 */
	public function set_store_geocode() {
		if ( '' === get_option( 'lddfw_store_address_longitude', '' ) || '' === get_option( 'lddfw_store_address_latitude', '' ) ) {
			$store              = new LDDFW_Store();
			$pickup_map_address = $store->lddfw_store_address( 'map_address' );
			if ( ! empty( $pickup_map_address ) ) {
				$geocode = $this->get_geocode( $pickup_map_address );
				if ( 'OK' === $geocode[0] ) {
					update_option( 'lddfw_store_address_longitude', $geocode[4] );
					update_option( 'lddfw_store_address_latitude', $geocode[5] );
				} else {
					if ( '' !== $geocode[0] ) {
						update_option( 'lddfw_store_address_longitude', 0 );
						update_option( 'lddfw_store_address_latitude', 0 );
					}
				}
			}
		}
	}

	/**
	 * Get store geocode.
	 *
	 * @param int $seller_id seller number.
	 * @return statement
	 */
	public function get_store_geocode() {
		$result    = '';
		$longitude = get_option( 'lddfw_store_address_longitude' );
		$latitude  = get_option( 'lddfw_store_address_latitude' );
		if ( '' !== $longitude && '' !== $latitude ) {
			$result = $longitude . ',' . $latitude;
		}
		return $result;
	}

	/**
	 * Get seller geocode.
	 *
	 * @param int $seller_id seller number.
	 * @return statement
	 */
	public function get_seller_geocode( $seller_id ) {
		$result = false;
		if ( '' !== $seller_id ) {
			$latitude  = get_user_meta( $seller_id, 'lddfw_address_latitude', true );
			$longitude = get_user_meta( $seller_id, 'lddfw_address_longitude', true );
			if ( '' !== $latitude && '' !== $longitude ) {
				$result = array( $latitude, $longitude );
			}
		}
		return $result;
	}

	/**
	 * Set seller Geocode.
	 *
	 * @param int $seller_id seller number.
	 * @return void
	 */
	public function set_seller_geocode( $seller_id ) {
		if ( '' !== $seller_id && ( '' === get_user_meta( $seller_id, 'lddfw_address_latitude', true ) || '' === get_user_meta( $seller_id, 'lddfw_address_longitude', true ) ) ) {
			$store              = new LDDFW_Store();
			$pickup_map_address = $store->lddfw_pickup_address( 'map_address', '', $seller_id );
			if ( ! empty( $pickup_map_address ) ) {
				$geocode = $this->get_geocode( $pickup_map_address );
				if ( 'OK' === $geocode[0] ) {
					update_user_meta( $seller_id, 'lddfw_address_latitude', $geocode[4] );
					update_user_meta( $seller_id, 'lddfw_address_longitude', $geocode[5] );
				} else {
					if ( '' !== $geocode[0] ) {
						update_user_meta( $seller_id, 'lddfw_address_latitude', 0 );
						update_user_meta( $seller_id, 'lddfw_address_longitude', 0 );
					}
				}
			}
		}
	}

	/**
	 * Get driver geocode.
	 *
	 * @param int $driver_id driver number.
	 * @return statement
	 */
	public function get_driver_geocode( $driver_id ) {
		$result = false;
		if ( '' !== $driver_id ) {
			$latitude  = get_user_meta( $driver_id, 'lddfw_address_latitude', true );
			$longitude = get_user_meta( $driver_id, 'lddfw_address_longitude', true );
			if ( '' !== $latitude && '' !== $longitude ) {
				$result = array( $latitude, $longitude );
			}
		}
		return $result;
	}

	/**
	 * Set driver geocode.
	 *
	 * @param int $driver_id driver number.
	 * @return void
	 */
	public function set_driver_geocode( $driver_id ) {
		if ( '' !== $driver_id && ( '' === get_user_meta( $driver_id, 'lddfw_address_latitude', true ) || '' === get_user_meta( $driver_id, 'lddfw_address_longitude', true ) ) ) {

			$driver         = new PWDDM_Driver();
			$driver_address = $driver->get_driver_address( $driver_id );
			if ( ! empty( $driver_address ) ) {
				$driver_address = $driver_address[0];
				$geocode        = $this->get_geocode( $driver_address );
				if ( 'OK' === $geocode[0] ) {
					update_user_meta( $driver_id, 'lddfw_address_latitude', $geocode[4] );
					update_user_meta( $driver_id, 'lddfw_address_longitude', $geocode[5] );
				} else {
					if ( '' !== $geocode[0] ) {
						update_user_meta( $driver_id, 'lddfw_address_latitude', 0 );
						update_user_meta( $driver_id, 'lddfw_address_longitude', 0 );
					}
				}
			}
		}
	}

	/**
	 * Delete order geocode.
	 *
	 * @param int $order_id order number.
	 * @return void
	 */
	public function delete_order_geocode( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->delete_meta_data( '_lddfw_address_geocode' );
		$order->save();
	}

	/**
	 * Set order geocode.
	 *
	 * @param int $order_id order number.
	 * @return void
	 */
	public function set_order_geocode( $order_id ) {

		$store               = new LDDFW_Store();
		$lddfw_order         = new LDDFW_Order();
		$order               = wc_get_order( $order_id );
		$shipping_array      = $lddfw_order->lddfw_order_address( 'shipping', $order, $order_id );
		$map_shippingaddress = lddfw_format_address( 'map_address', $shipping_array );

		// Set address by coordinates.
		$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
		if ( '' !== $coordinates ) {
			$map_shippingaddress = $coordinates;
		}

		// Set seller pickup geocode.
		$seller_id = $store->lddfw_order_seller( $order );
		if ( '' !== $seller_id ) {
			$this->set_seller_geocode( $seller_id );
		}

		// Set driver pickup geocode.
		$driver_id = $order->get_meta( 'lddfw_driverid' );
		if ( '' !== $driver_id ) {
			$this->set_driver_geocode( $driver_id );
		}

		do_action( 'pwddm_set_order_geocode', $order_id, $order );

		// Set order geocode.
		$order->update_meta_data( '_lddfw_address_geocode', $this->get_geocode( $map_shippingaddress ) );
		$order->save();
	}
}
