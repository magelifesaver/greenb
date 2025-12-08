<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/geodata/inc/class-adbc-account-save.php
 * Purpose: When a customer saves Billing/Shipping on My Account, geocode and update _wc_{scope}/ coords + verified flag.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class ADBC_Account_Save {

    public static function init() : void {
        // Fires after WooCommerce saves the address fields
        add_action( 'woocommerce_customer_save_address', [ __CLASS__, 'on_save' ], 10, 2 );
    }

    /** Build full address from POST (billing_* or shipping_*) */
    private static function read_post_address( string $scope ) : array {
        $pfx = $scope . '_';
        $get = function( $k ){
            return isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
        };
        $a1 = $get( $pfx . 'address_1' );
        $a2 = $get( $pfx . 'address_2' );
        $city = $get( $pfx . 'city' );
        $state = $get( $pfx . 'state' );
        $zip = $get( $pfx . 'postcode' );
        $ctry = $get( $pfx . 'country' );
        $full = trim( implode( ', ', array_filter( array_map( 'trim', [ $a1, $a2, $city, $state, $zip, $ctry ] ) ) ) );
        return [ $full, compact( 'a1','a2','city','state','zip','ctry' ) ];
    }

    /** Use option key first; fallback to constant */
    private static function geocode( string $address ) : array {
        if ( $address === '' ) return [];
        $opts = get_option( 'delivery_global', [] );
        $api  = $opts['google_geocode_api_key'] ?? '';
        if ( defined( 'ADBC_GOOGLE_GEOCODE_API_KEY' ) && ! $api ) {
            $api = ADBC_GOOGLE_GEOCODE_API_KEY;
        }
        if ( ! $api ) return [];

        $url = add_query_arg(
            [ 'address' => rawurlencode( $address ), 'key' => $api ],
            'https://maps.googleapis.com/maps/api/geocode/json'
        );
        $r = wp_remote_get( $url, [ 'timeout' => 8 ] );
        if ( is_wp_error( $r ) ) return [];
        $j = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( ! isset( $j['status'] ) || $j['status'] !== 'OK' ) return [];
        $loc = $j['results'][0]['geometry']['location'] ?? null;
        if ( ! $loc || ! isset( $loc['lat'], $loc['lng'] ) ) return [];
        return [ (string) $loc['lat'], (string) $loc['lng'] ];
    }

    public static function on_save( int $user_id, string $load_address ) : void {
        // $load_address is 'billing' or 'shipping'
        if ( ! in_array( $load_address, [ 'billing', 'shipping' ], true ) ) return;

        list( $address, $parts ) = self::read_post_address( $load_address );
        if ( $address === '' ) {
            // No address -> clear verified flag (keep old coords intact)
            update_user_meta( $user_id, '_wc_' . $load_address . '/' . ADBC_FIELD_FLAG, 'no' );
            return;
        }

        $coords = self::geocode( $address );
        $lat = $coords[0] ?? '';
        $lng = $coords[1] ?? '';
        $verified = ( $lat !== '' && $lng !== '' ) ? 'yes' : 'no';

        // Persist new coords + flag
        update_user_meta( $user_id, '_wc_' . $load_address . '/' . ADBC_FIELD_LAT,  $lat );
        update_user_meta( $user_id, '_wc_' . $load_address . '/' . ADBC_FIELD_LNG,  $lng );
        update_user_meta( $user_id, '_wc_' . $load_address . '/' . ADBC_FIELD_FLAG, $verified );
    }
}
