<?php
/**
 * File: includes/class-adbg-login.php
 * Purpose: On user login, refresh GEO user meta if TTL expired and coords are verified.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class ADBG_Login_Refresh {
    public static function init() {
        add_action( 'wp_login', [ __CLASS__, 'on_login' ], 10, 2 );
    }
    public static function on_login( $user_login, $user ) {
        $uid = is_object($user) ? (int) $user->ID : 0;
        if ( ! $uid ) return;
        $opts = ADBG_Travel::get_options();
        $geo  = ADBG_Travel::get_user_geo( $uid, 'shipping' );
        if ( ! ADBG_Travel::is_fresh( $geo, $opts['browse_ttl'] ) ) {
            ADBG_Travel::compute_for_user( $uid, 'shipping', true );
        }
    }
}
