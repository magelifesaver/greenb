<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/aaa-admin-controler-v2.php
 * Plugin Name: AAA Admin Controller v2
 * Description: Network‑only tool to view/limit staff sessions, schedule forced ends, and verify identity via admin popup. Includes reports.
 * Version: 2.5.2
 * Author: Workflow
 * Network: true
 * Text Domain: aaa-ac
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/*
 * NOTE: This file mirrors the original plugin’s loader but bumps the version
 * constant to reflect our changes. All logic is identical to the previous
 * version. See other files for UI and AJAX changes.
 */

// Bump the plugin version to reflect the extended sessions functionality.
define( 'AAA_AC_VER', '2.5.0' );
define( 'AAA_AC_TEXTDOMAIN', 'aaa-ac' );
define( 'AAA_AC_PATH', plugin_dir_path( __FILE__ ) );
define( 'AAA_AC_URL',  plugin_dir_url( __FILE__ ) );
define( 'AAA_AC_TABLE_PREFIX', 'aaa_ac_' );
define( 'AAA_AC_OPTION_ROLES', 'aaa_ac_roles_enabled' );
define( 'AAA_AC_OPTION_CRON_MODE', 'aaa_ac_cron_mode' ); // 'dev' or 'live'
if ( ! defined('AAA_AC_DEBUG_THIS_FILE') ) define('AAA_AC_DEBUG_THIS_FILE', false);

/** User meta keys */
define( 'AAA_AC_META_FORCE',   'ac_force_times' );   // CSV HH:MM
define( 'AAA_AC_META_POPUP',   'ac_popup_times' );   // CSV HH:MM
define( 'AAA_AC_META_INCLUDE', 'ac_include_logs' );  // yes|no

function aaa_ac_log( $msg, $ctx = [] ){
        if ( ! AAA_AC_DEBUG_THIS_FILE ) return;
        if ( is_array($ctx) || is_object($ctx) ) $msg .= ' ' . wp_json_encode($ctx);
        error_log('[AAA_AC] '.$msg);
}

/** ---------- Includes / Installers ---------- */
require_once AAA_AC_PATH . 'index/class-ac-table-installer.php';
register_activation_hook( __FILE__, ['AC_Table_Installer','install'] );
add_action('plugins_loaded', ['AC_Table_Installer','maybe_install']);

/** Popup logs table */
require_once AAA_AC_PATH . 'index/class-ac-popup-table-installer.php';
register_activation_hook( __FILE__, ['AC_Popup_Table_Installer','install'] );
add_action('plugins_loaded', ['AC_Popup_Table_Installer','maybe_install']);

/** AJAX (sessions + settings) */
require_once AAA_AC_PATH . 'ajax/class-ac-ajax-v2.php';
add_action('plugins_loaded', ['AC_Ajax_V2','init']);

/** Reports (sessions) */
require_once AAA_AC_PATH . 'ajax/class-ac-reports.php';
add_action('plugins_loaded', ['AC_Reports_Ajax','init']);

/** Popup AJAX (check/confirm/switch) */
require_once AAA_AC_PATH . 'ajax/class-ac-popup.php';
add_action('plugins_loaded', ['AC_Popup_Ajax','init']);

/** Popup Reports */
require_once AAA_AC_PATH . 'ajax/class-ac-popup-reports.php';
add_action('plugins_loaded', ['AC_Popup_Reports_Ajax','init']);

/** Cron (forced end) */
require_once AAA_AC_PATH . 'cron/class-ac-cron.php';
add_action('plugins_loaded', ['AC_Cron','init']);

/** Logger (session lifecycle) */
require_once AAA_AC_PATH . 'index/class-ac-logger.php';
add_action('plugins_loaded', ['AC_Logger','init']);

/** Extended Sessions (bulk end + extra columns) */
// Load our extended sessions handler which introduces bulk actions and extra
// columns (last activity, customer, cart) for the sessions tab. This class
// registers its own AJAX endpoints via AC_Sessions_Extended::init().
require_once AAA_AC_PATH . 'ajax/class-ac-sessions-extended.php';
add_action('plugins_loaded', ['AC_Sessions_Extended','init']);

// require_once AAA_AC_PATH . 'timeclock/aaa-ac-timeclock-admin.php';
// add_action('plugins_loaded', ['AAA_AC_Timeclock_Admin','init']);

/** ---------- Helpers ---------- */
function aaa_ac_get_all_roles(){
        $editable = get_editable_roles();
        $out = [];
        foreach( $editable as $slug=>$def ){ $out[$slug] = translate_user_role( $def['name'] ); }
        // union with saved roles (keep roles with no users)
        $saved = get_site_option( AAA_AC_OPTION_ROLES, [] );
        if ( is_array($saved) ){
                foreach( $saved as $slug ){
                        $slug = sanitize_key($slug);
                        if ( ! isset($out[$slug]) ) $out[$slug] = ucwords( str_replace(['_','-'],' ',$slug) );
                }
        }
        asort($out);
        return $out;
}
function aaa_ac_get_enabled_roles(){
        $roles = get_site_option( AAA_AC_OPTION_ROLES, ['administrator'] );
        if ( ! is_array($roles) ) $roles = ['administrator'];
        return array_values( array_unique( array_map('sanitize_key',$roles) ) );
}
function aaa_ac_is_user_online( $user_id ){
        $tokens = WP_Session_Tokens::get_instance( $user_id )->get_all();
        $now=time();
        foreach( $tokens as $t ){
                if( !empty($t['expiration']) && (int)$t['expiration']>$now ) return true;
        }
        return false;
}
function aaa_ac_latest_session_login( $user_id ){
        $tokens = WP_Session_Tokens::get_instance( $user_id )->get_all();
        $login=0;
        foreach( $tokens as $t ){
                if( !empty($t['login']) && (int)$t['login']>$login ) $login=(int)$t['login'];
        }
        return $login ? get_date_from_gmt( gmdate('Y-m-d H:i:s',$login), 'Y-m-d H:i:s' ) : '';
}
function aaa_ac_sanitize_csv_times( $csv ){
        $csv = (string)$csv;
        if ( $csv === '' ) return '';
        $parts = array_filter(array_map('trim', explode(',', $csv )));
        $norm  = [];
        foreach( $parts as $p ){
                $p = preg_replace('/\s+/', '', $p);
                if ( preg_match('/^(\d{1,2}):(\d{2})$/', $p, $m) ){
                        $h=(int)$m[1]; $i=(int)$m[2];
                        if ( $h>=0 && $h<=23 && $i>=0 && $i<=59 ) $norm[] = sprintf('%02d:%02d', $h, $i);
                }
        }
        $norm = array_values(array_unique($norm));
        sort($norm, SORT_STRING);
        return implode(',', array_slice($norm, 0, 24));
}

/** ---------- Network Admin Menu ---------- */
add_action('network_admin_menu', function(){
        add_menu_page(
                __('Online Users Across the Network','aaa-ac'),
                __('Online Users','aaa-ac'),
                'manage_network_users',
                'aaa-ac-online',
                function(){ require AAA_AC_PATH.'admin/admin-page.php'; },
                'dashicons-admin-users',
                25
        );
});

/** ---------- Assets for Online Users page ---------- */
add_action('admin_enqueue_scripts', function($hook){
        if ( ! is_network_admin() ) return;
        if ( isset($_GET['page']) && $_GET['page'] === 'aaa-ac-online' ) {
                wp_enqueue_style( 'aaa-ac-admin', AAA_AC_URL . 'assets/css/admin.css', array(), AAA_AC_VER );
                // Use the extended admin script which includes bulk selection and extra columns.
                wp_enqueue_script( 'aaa-ac-admin-extended',  AAA_AC_URL . 'assets/js/admin-extended.js',  array(), AAA_AC_VER, true );
                wp_enqueue_script( 'aaa-ac-reports',   AAA_AC_URL . 'assets/js/reports.js',   array(), AAA_AC_VER, true );
                wp_localize_script(
                        'aaa-ac-admin-extended',
                        'AAA_AC',
                        array(
                                'ajax'        => admin_url('admin-ajax.php'),
                                'nonce'       => wp_create_nonce('aaa_ac_ajax'),
                                'now_display' => wp_date('H:i'),
                                'tz'          => wp_timezone_string(),
                        )
                );
        }
});

/** ---------- Popup watcher on ALL wp-admin pages (enabled roles only) ---------- */
add_action('admin_enqueue_scripts', function ($hook) {
        if ( ! is_user_logged_in() ) return;

        $enabled = aaa_ac_get_enabled_roles();
        $user    = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) return;

        $ok = false;
        foreach ( (array)$user->roles as $r ) {
                if ( in_array( $r, $enabled, true ) ) { $ok=true; break; }
        }
        if ( ! $ok ) return;

        wp_enqueue_script( 'aaa-ac-popup', AAA_AC_URL . 'assets/js/popup.js', array(), AAA_AC_VER, true );
        wp_localize_script( 'aaa-ac-popup', 'AAA_AC_POPUP', array(
                'ajax'      => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('aaa_ac_popup'),
                'name'      => $user->display_name,
                'shortName' => $user->first_name ? $user->first_name : $user->display_name,
                'interval'  => 60000, // 60s poll
        ));
});

/** ---------- Save: Roles ---------- */
add_action('network_admin_edit_aaa_ac_save_roles', function(){
        check_admin_referer('aaa_ac_save_roles');
        if ( ! current_user_can('manage_network_users') ) wp_die('No permissions.');
        $roles = isset($_POST['aaa_roles']) && is_array($_POST['aaa_roles']) ? array_map('sanitize_key', $_POST['aaa_roles']) : [];
        update_site_option( AAA_AC_OPTION_ROLES, $roles );
        aaa_ac_log('Saved roles', ['roles'=>$roles]);
        wp_redirect( add_query_arg(['page'=>'aaa-ac-online','updated'=>'1','tab'=>'settings'], network_admin_url('admin.php')) );
        exit;
});

/** ---------- Save: Cron mode ---------- */
add_action('network_admin_edit_aaa_ac_save_cron', function(){
        check_admin_referer('aaa_ac_save_cron');
        if ( ! current_user_can('manage_network_users') ) wp_die('No permissions.');
        $mode = isset($_POST['aaa_ac_cron_mode']) ? sanitize_key($_POST['aaa_ac_cron_mode']) : 'dev';
        if ( ! in_array($mode, ['dev','live'], true) ) $mode = 'dev';
        update_site_option( AAA_AC_OPTION_CRON_MODE, $mode );
        if ( class_exists('AC_Cron') ) AC_Cron::reschedule();
        aaa_ac_log('Saved cron mode', ['mode'=>$mode]);
        wp_redirect( add_query_arg(['page'=>'aaa-ac-online','tab'=>'settings','cronupdated'=>'1'], network_admin_url('admin.php')) );
        exit;
});

/** ---------- Manual End Session (records admin actor) ---------- */
add_action('admin_init', function(){
        if ( ! is_network_admin() ) return;
        if ( empty($_GET['aaa_ac_end_session']) ) return;
        $user_id = absint($_GET['aaa_ac_end_session']);
        check_admin_referer('aaa_ac_end_'.$user_id);
        if ( ! current_user_can('manage_network_users') ) wp_die('No permissions.');

        $actor = wp_get_current_user();
        $actor_name = ( $actor && $actor->exists() ) ? $actor->display_name : 'unknown';

        if ( class_exists('AC_Logger') ) {
                AC_Logger::mark_ended_all( $user_id, 'admin', 1, 'admin:' . $actor_name );
        }
        WP_Session_Tokens::get_instance($user_id)->destroy_all();
        aaa_ac_log('Admin ended session',['user_id'=>$user_id,'actor'=>$actor_name]);

        wp_safe_redirect( add_query_arg(['page'=>'aaa-ac-online','tab'=>'sessions','ended'=>'1'], network_admin_url('admin.php')) );
        exit;
});