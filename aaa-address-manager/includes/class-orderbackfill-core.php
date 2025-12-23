<?php
/**
 * Core routines for backfilling order coordinates.
 *
 * Provides geocoding via Google or Sunshine, updates order meta and user meta
 * with latitude/longitude, and exposes helper methods to process multiple
 * orders. This module contains no admin interfaces; those live in
 * AAA_OrderBackfill_Admin and AAA_OrderBackfill_UserActions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OrderBackfill_Core {
    /**
     * Initialise the core. Currently no hooks are required but this method
     * exists to maintain a consistent API with other modules.
     */
    public static function init() : void {
        // Intentionally empty – no actions or filters to register.
    }
    /**
     * Geocode an address. Uses the Sunshine filter if enabled; otherwise
     * falls back to the settings option or a constant defined in wp-config.php.
     * Returns [lat,lng,status].
     *
     * @param string $address Address string to geocode.
     * @return array Tuple of lat, lng and status.
     */
    public static function geocode( string $address ) : array {
        $opts = get_option( 'aaa_adbc_settings', [] );
        // Try Sunshine integration if enabled.
        if ( ! empty( $opts['use_sunshine'] ) ) {
            $coords = apply_filters( 'aaa_adbc_geocode', null, $address );
            if ( is_array( $coords ) && ! empty( $coords[0] ) && ! empty( $coords[1] ) ) {
                return [ $coords[0], $coords[1], 'OK' ];
            }
        }
        // Determine API key from constant or settings.
        $api = '';
        if ( defined( 'AAA_GOOGLE_API_KEY' ) && AAA_GOOGLE_API_KEY ) {
            $api = AAA_GOOGLE_API_KEY;
        } elseif ( ! empty( $opts['google_api_key'] ) ) {
            $api = $opts['google_api_key'];
        }
        if ( empty( $api ) ) {
            return [ '', '', 'No API key' ];
        }
        $url = add_query_arg( [
            'address' => rawurlencode( $address ),
            'key'     => $api,
        ], 'https://maps.googleapis.com/maps/api/geocode/json' );
        $r = wp_remote_get( $url, [ 'timeout' => 8 ] );
        if ( is_wp_error( $r ) ) {
            return [ '', '', $r->get_error_message() ];
        }
        $j = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( ! isset( $j['status'] ) || 'OK' !== $j['status'] ) {
            return [ '', '', $j['status'] ?? 'ERR' ];
        }
        $loc = $j['results'][0]['geometry']['location'] ?? [];
        return [ $loc['lat'] ?? '', $loc['lng'] ?? '', 'OK' ];
    }

    /**
     * Backfill coordinates for a given order scope (billing or shipping).
     * Updates both order meta and, if available, the associated user meta.
     * Returns true on success and false on failure.
     *
     * @param WC_Order $order WooCommerce order instance.
     * @param string   $scope 'billing', 'shipping' or 'both'.
     * @return bool Success flag.
     */
    public static function backfill_order_scope( WC_Order $order, string $scope ) : bool {
        $scopes = ( 'both' === $scope ) ? [ 'billing', 'shipping' ] : [ $scope ];
        $ok_any = false;
        foreach ( $scopes as $s ) {
            // Build address string.
            $addr = [
                $order->{"get_{$s}_address_1"}(),
                $order->{"get_{$s}_address_2"}(),
                $order->{"get_{$s}_city"}(),
                $order->{"get_{$s}_state"}(),
                $order->{"get_{$s}_postcode"}(),
                $order->{"get_{$s}_country"}(),
            ];
            $addr_str = trim( implode( ', ', array_filter( array_map( 'trim', $addr ) ) ) );
            if ( '' === $addr_str ) {
                continue;
            }
            [ $lat, $lng, $status ] = self::geocode( $addr_str );
            if ( $lat && $lng ) {
                // Save to order meta.
                $order->update_meta_data( "_wc_{$s}/aaa-delivery-blocks/latitude", $lat );
                $order->update_meta_data( "_wc_{$s}/aaa-delivery-blocks/longitude", $lng );
                $order->update_meta_data( "_wc_{$s}/aaa-delivery-blocks/coords-verified", 'yes' );
                // Save to user meta if user exists.
                if ( $uid = $order->get_user_id() ) {
                    update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/latitude", $lat );
                    update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/longitude", $lng );
                    update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/coords-verified", 'yes' );
                }
                $ok_any = true;
            }
        }
        return $ok_any;
    }

    /**
     * Process a list of orders for backfilling. Returns the count of order
     * scopes successfully updated. The scope parameter may be 'billing',
     * 'shipping' or 'both'.
     *
     * @param array  $order_ids Order IDs to process.
     * @param string $scope Scope to backfill.
     * @return int Number of order scopes backfilled.
     */
    public static function process_orders( array $order_ids, string $scope ) : int {
        $count = 0;
        foreach ( array_map( 'intval', $order_ids ) as $oid ) {
            $order = wc_get_order( $oid );
            if ( ! $order ) {
                continue;
            }
            $scopes = ( 'both' === $scope ) ? [ 'billing', 'shipping' ] : [ $scope ];
            foreach ( $scopes as $s ) {
                if ( self::backfill_order_scope( $order, $s ) ) {
                    $count++;
                }
            }
            $order->save();
        }
        return $count;
    }
}