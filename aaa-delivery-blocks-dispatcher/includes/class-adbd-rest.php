<?php
/**
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/includes/class-adbd-rest.php
 * Version: 0.1.3
 * Purpose: REST API endpoint for the dispatcher. Returns current orders (by chosen statuses),
 *          using billing-first (shipping fallback) coords, optional radius filter, plus drivers.
 *          Robust (HPOS-safe) meta reads and driver-id detection.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ADBD_REST {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() {
	  register_rest_route( 'adbd/v1', '/orders', [
	    'methods'             => 'GET',
	    'callback'            => [ __CLASS__, 'get_orders' ],
	    'permission_callback' => '__return_true', // <— TEMP ONLY for debugging
	  ] );
	}

	/* ---------- helpers ---------- */

	protected static function haversine_miles( $lat1, $lon1, $lat2, $lon2 ) {
		$earth = 3958.7613; // miles
		$dLat = deg2rad( $lat2 - $lat1 );
		$dLon = deg2rad( $lon2 - $lon1 );
		$a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
		$c = 2 * atan2( sqrt($a), sqrt(1-$a) );
		return $earth * $c;
	}

	/**
	 * HPOS-safe order meta read (prefers $order->get_meta, falls back to get_post_meta).
	 */
	protected static function read_order_meta( $order, $order_id, $key ) {
		if ( $order && method_exists( $order, 'get_meta' ) ) {
			$val = $order->get_meta( $key, true );
			if ( $val !== '' && $val !== null ) return $val;
		}
		return get_post_meta( $order_id, $key, true );
	}

	/**
	 * Driver user ID from order meta; tries common variants; coerces numeric; HPOS-safe.
	 * Returns [int $driver_id, array|null $raw_hit].
	 */
	protected static function get_driver_id_from_order( $order, $order_id ) {
		$keys = [ 'lddfw_driverid', '_lddfw_driverid', 'lddfw_driver_id', '_lddfw_driver_id' ];
		$raw_hit = null;
		foreach ( $keys as $k ) {
			$val = self::read_order_meta( $order, $order_id, $k );
			if ( $val === '' || $val === null ) continue;
			$raw_hit = [ 'key' => $k, 'value' => $val ];
			$val = trim( (string) $val );
			if ( is_numeric( $val ) ) {
				$int = (int) $val;
				if ( $int > 0 ) return [ $int, $raw_hit ];
			}
		}
		return [ 0, $raw_hit ];
	}

	/**
	 * Billing-first coords; shipping fallback; NO verified check. HPOS-safe.
	 * Returns [lat, lng] floats or [null, null].
	 */
	protected static function get_order_coords_billing_first( $order, $order_id ) {
		$lat = self::read_order_meta( $order, $order_id, '_wc_billing/aaa-delivery-blocks/latitude' );
		$lng = self::read_order_meta( $order, $order_id, '_wc_billing/aaa-delivery-blocks/longitude' );
		if ( $lat !== '' && $lng !== '' ) return [ (float) $lat, (float) $lng ];

		$lat = self::read_order_meta( $order, $order_id, '_wc_shipping/aaa-delivery-blocks/latitude' );
		$lng = self::read_order_meta( $order, $order_id, '_wc_shipping/aaa-delivery-blocks/longitude' );
		if ( $lat !== '' && $lng !== '' ) return [ (float) $lat, (float) $lng ];

		return [ null, null ];
	}

	/* ---------- endpoint ---------- */

	public static function get_orders( WP_REST_Request $req ) {
		try {
			if ( ! function_exists( 'wc_get_order' ) ) {
				return new WP_Error( 'adbd_no_wc', 'WooCommerce functions not available', [ 'status' => 500 ] );
			}

			$settings = get_option( ADBD_Admin::OPTION_NAME, [] );
			$origin   = ADBD_Admin::get_primary_origin();
			$radius   = isset($settings['radius_miles']) ? floatval( $settings['radius_miles'] ) : 30.0;

			// Selected statuses (stored without 'wc-' prefix)
			$slugs    = ( isset( $settings['order_statuses'] ) && is_array( $settings['order_statuses'] ) ) ? $settings['order_statuses'] : [ 'processing' ];
			$slugs    = array_values( array_filter( array_map( 'sanitize_text_field', $slugs ) ) );
			if ( empty( $slugs ) ) { $slugs = [ 'processing' ]; }
			$statuses = array_map( function( $s ){ return 'wc-' . ltrim( $s, 'wc-' ); }, $slugs );

			$q = new WP_Query( [
				'post_type'      => 'shop_order',
				'post_status'    => $statuses,
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );

			$items = [];
			foreach ( (array) $q->posts as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) continue;

				// Coords (billing-first)
				list( $lat, $lng ) = self::get_order_coords_billing_first( $order, $order_id );
				$has_coords = ( $lat !== null && $lng !== null );

				// Radius filter (unless debug flag set)
				$dist = null; $include = true;
				if ( $has_coords ) {
					$dist = self::haversine_miles( $origin['lat'], $origin['lng'], $lat, $lng );
					if ( ! defined('ADBD_DEBUG_ALL') || ADBD_DEBUG_ALL === false ) {
						if ( $dist > $radius ) $include = false;
					}
				}
				if ( ! $include ) continue;

				// Driver id (HPOS-safe)
				list( $driver_id, $driver_meta_raw ) = self::get_driver_id_from_order( $order, $order_id );

				// Payment & status
				$payment_status = $order->is_paid() ? 'Paid' : 'Unpaid';
				$payment_method = $order->get_payment_method_title();

				// Address (billing-first)
				$addr1 = $order->get_billing_address_1();
				$addr2 = $order->get_billing_address_2();
				$city  = $order->get_billing_city();
				$zip   = $order->get_billing_postcode();
				if ( ! $addr1 ) {
					$addr1 = $order->get_shipping_address_1();
					$addr2 = $order->get_shipping_address_2();
					$city  = $order->get_shipping_city();
					$zip   = $order->get_shipping_postcode();
				}

				// DTR (delivery time range)
				$dtr = self::read_order_meta( $order, $order_id, '_wc_other/adbsa/delivery-time' );
				if ( ! $dtr ) $dtr = get_post_meta( $order_id, 'delivery_time_range', true );
				if ( ! $dtr ) $dtr = get_post_meta( $order_id, 'delivery_time', true );

				// Travel meta (billing first; shipping fallback)
				$dist_m = self::read_order_meta( $order, $order_id, '_wc_billing/aaa-delivery-blocks/distance-meters' );
				$t_secs = self::read_order_meta( $order, $order_id, '_wc_billing/aaa-delivery-blocks/travel-seconds' );
				if ( $dist_m === '' && $t_secs === '' ) {
					$dist_m = self::read_order_meta( $order, $order_id, '_wc_shipping/aaa-delivery-blocks/distance-meters' );
					$t_secs = self::read_order_meta( $order, $order_id, '_wc_shipping/aaa-delivery-blocks/travel-seconds' );
				}
				$dist_m = ($dist_m === '' ? null : (float) $dist_m);
				$t_secs = ($t_secs === '' ? null : (int)   $t_secs);

				$items[] = [
					'id'               => $order_id,
					'number'           => $order->get_order_number(),
					'status'           => $order->get_status(), // e.g. "processing"
					'payment'          => $payment_status,
					'payment_method'   => $payment_method,
					'customer'         => trim( $order->get_formatted_billing_full_name() ?: $order->get_formatted_shipping_full_name() ),
					'address'          => [ 'line1'=>$addr1, 'line2'=>$addr2, 'city'=>$city, 'zip'=>$zip ],
					'dtr'              => $dtr ?: '',
					'lat'              => $lat,
					'lng'              => $lng,
					'has_coords'       => $has_coords,
					'distance_mi'      => ( $dist !== null ? round( $dist, 2 ) : null ),
					'order_distance_m' => $dist_m,
					'order_travel_s'   => $t_secs,
					'driver_id'        => $driver_id ?: 0,
					'driver_meta_raw'  => $driver_meta_raw, // debug aid; remove later if desired
				];
			}

			// Drivers (always include)
			$drivers = [];
			$users = get_users( [
				'meta_key'   => 'lddfw_driver_account',
				'meta_value' => '1',
				'number'     => 1000,
				'fields'     => [ 'ID', 'display_name' ],
			] );
			foreach ( $users as $u ) {
				$availability = get_user_meta( $u->ID, 'lddfw_driver_availability', true ); // '1' or '0'
				$drivers[] = [
					'id'          => $u->ID,
					'name'        => $u->display_name,
					'availability'=> ($availability === '1') ? 'On duty' : 'Off duty',
				];
			}

			return rest_ensure_response( [
				'debug'   => ( defined('ADBD_DEBUG_ALL') && ADBD_DEBUG_ALL === true ),
				'origin'  => $origin,
				'radius'  => $radius,
				'orders'  => $items,
				'drivers' => $drivers,
			] );
		} catch ( \Throwable $e ) {
			if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
				error_log( '[ADBD REST] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
			}
			return new WP_Error( 'adbd_rest_exception', $e->getMessage(), [ 'status' => 500 ] );
		}
	}
}
