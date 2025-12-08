<?php
/**
 * File: includes/class-adbg-travel.php
 * Purpose: Core travel/ETA computation (Distance Matrix), TTL checks, and persistence.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class ADBG_Travel {

    /** Load GEO options */
    public static function get_options() : array {
        $o = get_option( 'delivery_geo', [] );
        return [
            'server_key'   => (string) ( $o['server_key'] ?? '' ),
            'origins'      => json_decode( $o['origins_json'] ?? '[]', true ) ?: [],
            'browse_ttl'   => intval( $o['browse_ttl_seconds'] ?? 1200 ),
            'checkout_ttl' => intval( $o['checkout_ttl_seconds'] ?? 120 ),
            'slot'         => max( 60, intval( $o['slot_seconds'] ?? 600 ) ),
        ];
    }

    /** Return [lat,lng,verified] from user meta for given group (shipping|billing) */
    public static function get_user_coords( int $uid, string $group = 'shipping' ) : array {
        $LAT  = defined('ADBC_FIELD_LAT')  ? ADBC_FIELD_LAT  : 'aaa-delivery-blocks/latitude';
        $LNG  = defined('ADBC_FIELD_LNG')  ? ADBC_FIELD_LNG  : 'aaa-delivery-blocks/longitude';
        $FLAG = defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified';

        $lat  = (string) get_user_meta( $uid, '_wc_' . $group . '/' . $LAT, true );
        $lng  = (string) get_user_meta( $uid, '_wc_' . $group . '/' . $LNG, true );
        $flag = (string) get_user_meta( $uid, '_wc_' . $group . '/' . $FLAG, true );

        return [ $lat, $lng, $flag ];
    }

    /** Read latest stored GEO metrics from user meta (if any) */
    public static function get_user_geo( int $uid, string $group = 'shipping' ) : array {
        $eta    = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ETA,       true );
        $range  = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ETA_RANGE, true );
        $origin = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ORIGIN,    true );
        $dist   = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_DISTANCE,  true );
        $trav   = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_TRAVEL,    true );
        $ref    = (string) get_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_REFRESHED, true );

        if ( $eta === '' && $dist === '' && $trav === '' && $origin === '' ) return [];
        return [
            'eta_s'       => intval($eta),
            'eta_range'   => (string) $range,
            'origin_id'   => (string) $origin,
            'distance_m'  => intval($dist),
            'travel_s'    => intval($trav),
            'refreshed'   => intval($ref ?: 0),
        ];
    }

    /** Decide if user geo is fresh under given ttl */
    public static function is_fresh( array $geo, int $ttl ) : bool {
        if ( empty($geo) ) return false;
        $ref = intval( $geo['refreshed'] ?? 0 );
        if ( $ref <= 0 ) return false;
        return ( time() - $ref ) < max( 0, $ttl );
    }

    /** Distance Matrix for one origin */
    protected static function distance_matrix( array $origin, float $destLat, float $destLng, string $key ) : array {
        if ( empty( $key ) ) {
            ADBG_Logger::log('No server key set');
            return [];
        }
        $mode = isset($origin['mode']) && is_string($origin['mode']) ? $origin['mode'] : 'driving';
        $q = [
            'origins'        => $origin['lat'] . ',' . $origin['lng'],
            'destinations'   => $destLat . ',' . $destLng,
            'mode'           => $mode,
            'departure_time' => 'now',
            'key'            => $key,
            'traffic_model'  => 'best_guess',
        ];
        $url = add_query_arg( $q, 'https://maps.googleapis.com/maps/api/distancematrix/json' );
        $r   = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $r ) ) {
            ADBG_Logger::log('wp_remote_get error', [ 'err' => $r->get_error_message(), 'url'=>$url ]);
            return [];
        }
        $body = wp_remote_retrieve_body( $r );
        $j = json_decode( $body, true );

        if ( ! is_array( $j ) ) {
            ADBG_Logger::log('Bad JSON response', [ 'body' => $body ]);
            return [];
        }
        if ( ($j['status'] ?? '') !== 'OK' ) {
            ADBG_Logger::log('Google status not OK', $j);
            return [];
        }
        $el = $j['rows'][0]['elements'][0] ?? null;
        if ( ! $el || ( $el['status'] ?? '' ) !== 'OK' ) {
            ADBG_Logger::log('Element not OK', $j);
            return [];
        }

        $distance = intval( $el['distance']['value'] ?? 0 );
        $duration = intval( $el['duration_in_traffic']['value'] ?? $el['duration']['value'] ?? 0 );
        return $distance > 0 && $duration > 0 ? [ 'distance_m' => $distance, 'duration_s' => $duration ] : [];
    }

    /** Compute payload for a given user + group; persists to user meta when $persist */
    public static function compute_for_user( int $uid, string $group, bool $persist = true ) : array {
        $opts = self::get_options();
        ADBG_Logger::log('compute_for_user start', $opts);

        if ( empty( $opts['server_key'] ) ) {
            ADBG_Logger::log('Missing server key');
            return [ 'error' => 'missing_server_key' ];
        }

        [ $latS, $lngS, $flag ] = self::get_user_coords( $uid, $group );
        if ( $flag !== 'yes' || $latS === '' || $lngS === '' ) {
            ADBG_Logger::log('No verified coords', ['lat'=>$latS, 'lng'=>$lngS, 'flag'=>$flag]);
            return [ 'error' => 'no_verified_coords' ];
        }
        $lat = (float) $latS; $lng = (float) $lngS;

        $best = null;
        foreach ( $opts['origins'] as $o ) {
            if ( ! isset($o['lat'],$o['lng'],$o['id']) ) {
                ADBG_Logger::log('Skipping invalid origin', $o);
                continue;
            }
            $res = self::distance_matrix( $o, $lat, $lng, $opts['server_key'] );
            if ( ! $res ) {
                ADBG_Logger::log('Distance matrix failed for origin', $o);
                continue;
            }
            $cand = $res + [ 'origin_id' => (string) $o['id'] ];
            if ( ! $best || $cand['duration_s'] < $best['duration_s'] ) $best = $cand;
        }

        if ( ! $best ) {
            ADBG_Logger::log('Matrix failed – no valid results', [
                'lat' => $lat, 'lng' => $lng, 'origins'=>$opts['origins']
            ]);
            return [ 'error' => 'matrix_failed' ];
        }

        $slot  = max(60, intval($opts['slot']));
        $eta_s = intval( $best['duration_s'] );
        $lo    = max( 0, $eta_s - $slot );
        $hi    = $eta_s + $slot;

        $payload = [
            'distance_m'   => intval($best['distance_m']),
            'travel_s'     => intval($best['duration_s']),
            'eta_s'        => $eta_s,
            'eta_range_s'  => [ $lo, $hi ],
            'eta_range'    => $lo . ',' . $hi,
            'origin_id'    => $best['origin_id'],
            'refreshed'    => time(),
        ];

        ADBG_Logger::log('Computed payload', $payload);

        if ( $persist ) {
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_DISTANCE,  (string) $payload['distance_m'] );
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_TRAVEL,    (string) $payload['travel_s'] );
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ETA,       (string) $payload['eta_s'] );
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ETA_RANGE, (string) $payload['eta_range'] );
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_ORIGIN,    (string) $payload['origin_id'] );
            update_user_meta( $uid, '_wc_' . $group . '/' . ADBG_FIELD_REFRESHED, (string) $payload['refreshed'] );
        }
        return $payload;
    }
}
