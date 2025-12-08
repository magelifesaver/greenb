<?php
/**
 * File: /wp-content/plugins/aaa-delivery-blocks-coords/includes/class-adbc-ajax.php
 * Purpose: AJAX endpoint to resolve coords for current checkout address on page load.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ADBC_Ajax {
	public static function init() : void {
		add_action( 'wp_ajax_adbc_geocode_address',        [ __CLASS__, 'handle' ] );
		add_action( 'wp_ajax_nopriv_adbc_geocode_address', [ __CLASS__, 'handle' ] );
	}

	private static function user_meta_get( int $uid, string $scope, string $field ) : string {
		$key = '_wc_' . $scope . '/' . $field; // e.g. _wc_shipping/aaa-delivery-blocks/latitude
		$val = get_user_meta( $uid, $key, true );
		return is_scalar( $val ) ? (string) $val : '';
	}
	private static function user_meta_set( int $uid, string $scope, string $field, string $value ) : void {
		update_user_meta( $uid, '_wc_' . $scope . '/' . $field, $value );
	}

	private static function geocode( string $address ) : array {
    if ( empty( $address ) ) return [];

    // Prefer saved option; fallback to constant if present
    $opts = get_option( 'delivery_global', [] );
    $api  = $opts['google_geocode_api_key'] ?? '';
    if ( defined('ADBC_GOOGLE_GEOCODE_API_KEY') && ! $api ) {
        $api = ADBC_GOOGLE_GEOCODE_API_KEY;
    }
    if ( ! $api ) return [];

    $url = add_query_arg( [
        'address' => rawurlencode( $address ),
        'key'     => $api,
    ], 'https://maps.googleapis.com/maps/api/geocode/json' );

    $r = wp_remote_get( $url, [ 'timeout' => 8 ] );
    if ( is_wp_error( $r ) ) return [];
    $j = json_decode( wp_remote_retrieve_body( $r ), true );
    if ( ! isset( $j['status'] ) || $j['status'] !== 'OK' ) return [];
    $loc = $j['results'][0]['geometry']['location'] ?? [];
    return ( isset( $loc['lat'], $loc['lng'] ) ) ? [ (string) $loc['lat'], (string) $loc['lng'] ] : [];
	}

	public static function handle() : void {
		check_ajax_referer( 'adbc_ajax', 'nonce' );
		$scope = in_array( $_POST['scope'] ?? '', [ 'shipping', 'billing' ], true ) ? $_POST['scope'] : 'shipping';

		// 1) If logged in and user has verified coords, return them (no geocode).
		$uid = get_current_user_id();
		if ( $uid ) {
			$flag = self::user_meta_get( $uid, $scope, ADBC_FIELD_FLAG );
			$lat  = self::user_meta_get( $uid, $scope, ADBC_FIELD_LAT );
			$lng  = self::user_meta_get( $uid, $scope, ADBC_FIELD_LNG );
			if ( $flag === 'yes' && $lat !== '' && $lng !== '' ) {
				wp_send_json_success( [ 'lat' => $lat, 'lng' => $lng, 'verified' => 'yes', 'source' => 'user' ] );
			}
		}

		// 2) Build address from POST and geocode if needed.
		$parts = [
			sanitize_text_field( wp_unslash( $_POST['address1'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['address2'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['city']     ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['state']    ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['postcode'] ?? '' ) ),
			sanitize_text_field( wp_unslash( $_POST['country']  ?? '' ) ),
		];
		$addr = trim( implode( ', ', array_filter( array_map( 'trim', $parts ) ) ) );
		if ( $addr === '' ) wp_send_json_error( [ 'message' => 'Empty address' ] );

		[$lat, $lng] = self::geocode( $addr ) ?: [ '', '' ];
		$verified = ( $lat !== '' && $lng !== '' ) ? 'yes' : 'no';

		// 3) Persist to user meta if logged in.
		if ( $uid && $verified === 'yes' ) {
			self::user_meta_set( $uid, $scope, ADBC_FIELD_LAT,  $lat );
			self::user_meta_set( $uid, $scope, ADBC_FIELD_LNG,  $lng );
			self::user_meta_set( $uid, $scope, ADBC_FIELD_FLAG, $verified );
		}

		wp_send_json_success( [ 'lat' => $lat, 'lng' => $lng, 'verified' => $verified, 'source' => $uid ? 'geocode+saved' : 'geocode' ] );
	}
}
