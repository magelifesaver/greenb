<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/hooks/class-aaa-oc-indexmanager-hooks-users.php
 * Purpose: Reliable login index + logout purge for Users, with fallbacks.
 */
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Hooks_Users {

    /** cache key we set each request for reliable logout purge */
    private static $last_uid_key = 'aaa_oc_im_last_uid';

    public static function boot(){
        $cfg = AAA_OC_IndexManager_Helpers::get_opt('users');
        if ( empty($cfg['enabled']) ) return;

        // --- Capture current user ID as early and as often as possible ---
        add_action('init',              [__CLASS__, 'capture_uid_early'], 1);
        add_action('set_current_user',  [__CLASS__, 'capture_uid_from_event'], 1);

        // --- LOGIN: synchronous upsert (works with/without cron) ---
        if ( ! empty($cfg['login_index']) ) {
            add_action('wp_login', function($login,$user){
                AAA_OC_IndexManager_Table_Indexer::upsert_now('users', (int)$user->ID);
                if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][login] upsert_now id='.$user->ID);
            }, 10, 2);
        }

        // --- LOGOUT: purge reliably ---
        if ( ! empty($cfg['logout_purge']) ) {
            // Primary: wp_logout (may have 0 current user in some flows)
            add_action('wp_logout', [__CLASS__, 'purge_on_logout'], 10);

            // Also purge when cookies are cleared (fires during logout sequence)
            add_action('clear_auth_cookie', [__CLASS__, 'purge_on_cookie_clear'], 1);

            // Admin “Log Out Everywhere” on user profile
            add_action('destroyed_all_user_sessions', [__CLASS__, 'purge_on_logout_everywhere'], 10, 1);

            // Fallback: scheduled purge handler
            add_action('aaa_oc_im_purge_user', [__CLASS__, 'purge_scheduled'], 10, 1);
        }

        // If session-only is ON, ignore additional triggers
        if ( ! empty($cfg['session_only']) ) return;

        // (Optional extra user triggers when session-only is OFF)
        $t = (array)($cfg['triggers'] ?? []);

        if ( in_array('profile_update',$t,true) ) {
            add_action('profile_update', function($uid){
                AAA_OC_IndexManager_Table_Indexer::reindex('users', (int)$uid, 'profile_update');
            }, 10, 1);
        }
        foreach ( ['added_user_meta','updated_user_meta'] as $h ) {
            if ( in_array($h,$t,true) ) {
                add_action($h, function($mid,$uid,$key,$val) use ($h){
                    AAA_OC_IndexManager_Table_Indexer::reindex('users', (int)$uid, $h.':'.$key);
                }, 10, 4);
            }
        }
        if ( in_array('deleted_user_meta',$t,true) ) {
            add_action('deleted_user_meta', function($mids,$uid,$key,$vals){
                AAA_OC_IndexManager_Table_Indexer::reindex('users', (int)$uid, 'deleted_user_meta:'.$key);
            }, 10, 4);
        }
        if ( in_array('set_user_role',$t,true) ) {
            add_action('set_user_role', function($uid){
                AAA_OC_IndexManager_Table_Indexer::reindex('users', (int)$uid, 'set_user_role');
            }, 10, 3);
        }
        if ( function_exists('WC') && in_array('woocommerce_customer_save_address',$t,true) ) {
            add_action('woocommerce_customer_save_address', function($uid){
                AAA_OC_IndexManager_Table_Indexer::reindex('users', (int)$uid, 'wc_save_address');
            }, 10, 2);
        }
    }

    /** Early capture on every request */
    public static function capture_uid_early(){
        $uid = get_current_user_id();
        if ( $uid ) $GLOBALS[self::$last_uid_key] = $uid;
    }

    /** Capture when WP sets the current user explicitly */
    public static function capture_uid_from_event( $uid ){
        $uid = (int)$uid;
        if ( $uid ) $GLOBALS[self::$last_uid_key] = $uid;
    }

    /** Purge handler for wp_logout */
    public static function purge_on_logout(){
        $uid = get_current_user_id();
        if ( ! $uid ) $uid = (int)($GLOBALS[self::$last_uid_key] ?? 0);

        if ( $uid ) {
            AAA_OC_IndexManager_Table_Indexer::purge('users', $uid);
            if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][logout] purge id='.$uid.' (wp_logout)');
        } else {
            // schedule fallback in case we missed it
            $last = (int)($GLOBALS[self::$last_uid_key] ?? 0);
            if ( $last && ! wp_next_scheduled('aaa_oc_im_purge_user') ) {
                wp_schedule_single_event( time()+1, 'aaa_oc_im_purge_user', [ $last ] );
                if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][logout] scheduled fallback purge id='.$last);
            }
        }
    }

    /** Purge handler for clear_auth_cookie (belt & suspenders) */
    public static function purge_on_cookie_clear(){
        $uid = (int)($GLOBALS[self::$last_uid_key] ?? 0);
        if ( $uid ) {
            AAA_OC_IndexManager_Table_Indexer::purge('users', $uid);
            if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][logout] purge id='.$uid.' (clear_auth_cookie)');
        }
    }

    /** Purge handler for Admin → “Log Out Everywhere” on user profile */
    public static function purge_on_logout_everywhere( $user_id ){
        $user_id = (int)$user_id;
        if ( $user_id ) {
            AAA_OC_IndexManager_Table_Indexer::purge('users', $user_id);
            if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][logout-everywhere] purge id='.$user_id);
        }
    }

    /** Fallback scheduled purge */
    public static function purge_scheduled( $uid ){
        $uid = (int)$uid;
        if ( $uid ) {
            AAA_OC_IndexManager_Table_Indexer::purge('users', $uid);
            if ( defined('WP_DEBUG') && WP_DEBUG ) error_log('[IM][users][logout] purge id='.$uid.' (scheduled)');
        }
    }
}
