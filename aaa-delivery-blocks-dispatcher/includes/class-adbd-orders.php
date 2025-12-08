<?php
/**
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/includes/class-adbd-orders.php
 * Version: 0.1.0
 * Purpose: Order utilities. On processing status, geocodes the order shipping address using the
 *          server key and caches lat/lng on order meta; provides helpers used by the REST layer.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class ADBD_Orders {
	const LAT_META = '_adbd_lat';
	const LNG_META = '_adbd_lng';

	public static function init() {
		// Geocode when order enters processing (if not already cached)
		add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'maybe_geocode_order' ], 10, 1 );
		// Also allow manual re-save to trigger geocode if missing
		add_action( 'save_post_shop_order', [ __CLASS__, 'maybe_geocode_on_save' ], 20, 3 );
	}

	public static function maybe_geocode_on_save( $post_ID, $post, $update ) {
		if ( 'shop_order' !== $post->post_type ) return;
		$status = function_exists('wc_get_order') ? wc_get_order( $post_ID )->get_status() : '';
		if ( $status === 'processing' ) self::maybe_geocode_order( $post_ID );
	}

	public static function maybe_geocode_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		$lat = get_post_meta( $order_id, self::LAT_META, true );
		$lng = get_post_meta( $order_id, self::LNG_META, true );
		if ( $lat && $lng ) return;

		$addr = trim( sprintf(
			'%s %s, %s, %s %s, %s',
			$order->get_shipping_address_1(),
			$order->get_shipping_address_2(),
			$order->get_shipping_city(),
			$order->get_shipping_state(),
			$order->get_shipping_postcode(),
			$order->get_shipping_country()
		) );

		if ( empty( $addr ) ) return;

		$settings = get_option( ADBD_Admin::OPTION_NAME, [] );
		$key = $settings['server_api_key'] ?? '';
		if ( empty( $key ) ) return;

		$url = add_query_arg( [
			'address' => rawurlencode( $addr ),
			'key'     => $key,
		], 'https://maps.googleapis.com/maps/api/geocode/json' );

		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );
		if ( is_wp_error( $response ) ) return;

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && is_array( $body ) && ! empty( $body['results'][0]['geometry']['location'] ) ) {
			$loc = $body['results'][0]['geometry']['location'];
			update_post_meta( $order_id, self::LAT_META, $loc['lat'] );
			update_post_meta( $order_id, self::LNG_META, $loc['lng'] );
		}
	}
}
