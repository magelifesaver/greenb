<?php
/**
 * File: includes/class-adbg-ajax.php
 * Purpose: AJAX endpoint to fetch travel/ETA at checkout; returns JSON and updates user meta.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class ADBG_Ajax {

    public static function init() : void {
        add_action( 'wp_ajax_adbg_get_travel',        [ __CLASS__, 'handle' ] );
        add_action( 'wp_ajax_nopriv_adbg_get_travel', [ __CLASS__, 'handle' ] );
    }

    public static function handle() : void {
        check_ajax_referer( 'adbg_ajax', 'nonce' );
        $scope = in_array( $_POST['scope'] ?? '', [ 'shipping','billing' ], true ) ? $_POST['scope'] : 'shipping';

        $uid = get_current_user_id();
        if ( ! $uid ) {
            wp_send_json_error( [ 'message' => 'not_logged_in' ] );
        }

        // TTL for checkout
        $opts = ADBG_Travel::get_options();
        $geo  = ADBG_Travel::get_user_geo( $uid, $scope );

        if ( ! ADBG_Travel::is_fresh( $geo, $opts['checkout_ttl'] ) ) {
            $payload = ADBG_Travel::compute_for_user( $uid, $scope, true );
            if ( isset($payload['error']) ) wp_send_json_error( $payload );
        } else {
            $lohi = array_map('intval', array_map('trim', explode(',', (string) ($geo['eta_range']??'') )) );
            $payload = [
                'distance_m'  => intval($geo['distance_m'] ?? 0),
                'travel_s'    => intval($geo['travel_s'] ?? 0),
                'eta_s'       => intval($geo['eta_s'] ?? 0),
                'eta_range_s' => count($lohi)===2 ? [ $lohi[0], $lohi[1] ] : [0,0],
                'origin_id'   => (string) ($geo['origin_id'] ?? ''),
                'refreshed'   => intval($geo['refreshed'] ?? 0),
            ];
        }

        wp_send_json_success( $payload );
    }
}
