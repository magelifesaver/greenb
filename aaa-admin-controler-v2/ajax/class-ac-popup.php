<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/ajax/class-ac-popup.php
 * Purpose: Popup check + confirm + switch
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Popup_Ajax {

	public static function init(){
		add_action('wp_ajax_aaa_ac_popup_check',   [__CLASS__, 'check']);
		add_action('wp_ajax_aaa_ac_popup_confirm', [__CLASS__, 'confirm']);
		add_action('wp_ajax_aaa_ac_popup_switch',  [__CLASS__, 'switch_user']);
	}

	protected static function enabled_roles(){ return function_exists('aaa_ac_get_enabled_roles') ? aaa_ac_get_enabled_roles() : ['administrator']; }
	protected static function user_enabled(){
		$user = wp_get_current_user(); if ( ! $user || ! $user->exists() ) return false;
		$enabled = self::enabled_roles();
		foreach( (array) $user->roles as $r ){ if ( in_array( $r, $enabled, true ) ) return true; }
		return false;
	}
	protected static function popup_times_for( $user_id ){
		$csv = (string) get_user_meta( (int)$user_id, defined('AAA_AC_META_POPUP')?AAA_AC_META_POPUP:'ac_popup_times', true );
		if ( $csv === '' ) return [];
		$parts = array_filter(array_map('trim', explode(',', $csv )));
		$norm  = [];
		foreach( $parts as $p ){
			if ( preg_match('/^(\d{1,2}):(\d{2})$/', $p, $m) ){
				$h=(int)$m[1]; $i=(int)$m[2];
				if ( $h>=0 && $h<=23 && $i>=0 && $i<=59 ) $norm[] = sprintf('%02d:%02d', $h, $i);
			}
		}
		return array_values(array_unique($norm));
	}
	protected static function now_hhmm(){ return wp_date('H:i'); }
	protected static function now_key(){ return wp_date('YmdHi'); }
	protected static function popup_log_table(){ global $wpdb; return $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'popup_logs'; }

	protected static function ip(){
		$ip=''; if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ $ip=trim(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])[0]); }
		if(!$ip && !empty($_SERVER['REMOTE_ADDR'])) $ip=$_SERVER['REMOTE_ADDR'];
		return substr((string)$ip,0,45);
	}
	protected static function ua(){ return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,255); }
	protected static function hash_session_token( $raw ){ return (is_string($raw) && $raw!=='') ? hash_hmac('sha256',$raw, wp_salt('session')) : ''; }
	protected static function raw_cookie_token(){
		if ( empty($_COOKIE[ LOGGED_IN_COOKIE ]) ) return '';
		$p = explode('|', $_COOKIE[ LOGGED_IN_COOKIE ]);
		return ( count($p)>=3 ? (string)$p[2] : '' );
	}
	protected static function lock_key( $user_id, $key ){ return 'aaa_ac_popup_lock_' . (int)$user_id . '_' . preg_replace('/[^0-9]/','',$key); }

	public static function check(){
		if ( ! is_user_logged_in() ) wp_send_json_success(['show'=>false]);
		check_ajax_referer('aaa_ac_popup','nonce');
		if ( ! is_admin() || ! self::user_enabled() ) wp_send_json_success(['show'=>false]);

		$user = wp_get_current_user(); $user_id = (int)$user->ID;

		$times = self::popup_times_for( $user_id );
		if ( empty($times) ) wp_send_json_success(['show'=>false]);

		$hhmm = self::now_hhmm();
		if ( ! in_array($hhmm, $times, true) ) wp_send_json_success(['show'=>false]);

		$key   = self::now_key();
		$lock  = self::lock_key($user_id, $key);
		if ( get_site_transient( $lock ) ) wp_send_json_success(['show'=>false]);
		$last  = (string) get_user_meta( $user_id, 'ac_popup_last_key', true );
		if ( $last === $key ) wp_send_json_success(['show'=>false]);

		global $wpdb;
		$table = self::popup_log_table();
		$now   = wp_date('Y-m-d H:i:s');
		$raw   = self::raw_cookie_token();
		$hash  = self::hash_session_token( $raw );

		$wpdb->insert( $table, [
			'user_id'      => $user_id,
			'due_at'       => $now,
			'due_hhmm'     => $hhmm,
			'shown_at'     => $now,
			'site_id'      => get_current_blog_id(),
			'admin_page'   => isset($_SERVER['REQUEST_URI']) ? substr((string)$_SERVER['REQUEST_URI'],0,255) : '',
			'session_token'=> $hash,
			'action'       => 'shown',
			'handled_at'   => null,
			'ip_address'   => self::ip(),
			'user_agent'   => self::ua(),
			'created_at'   => $now,
			'updated_at'   => $now,
		] );
		$popup_id = (int)$wpdb->insert_id;

		set_site_transient( $lock, 1, 120 );
		update_user_meta( $user_id, 'ac_popup_last_key', $key );

		wp_send_json_success([
			'show'      => true,
			'popup_id'  => $popup_id,
			'name'      => $user->display_name,
			'msg'       => sprintf( __('You are currently logged in as %s','aaa-ac'), $user->display_name ),
		]);
	}

	public static function confirm(){
		if ( ! is_user_logged_in() ) wp_send_json_error('no_user');
		check_ajax_referer('aaa_ac_popup','nonce');
		$popup_id = isset($_POST['popup_id']) ? absint($_POST['popup_id']) : 0;
		if ( ! $popup_id ) wp_send_json_error('bad_id');

		global $wpdb;
		$table = self::popup_log_table();
		$now   = wp_date('Y-m-d H:i:s');

		$wpdb->update( $table,
			[ 'action'=>'confirmed', 'handled_at'=>$now, 'updated_at'=>$now ],
			[ 'id'=>$popup_id, 'user_id'=>get_current_user_id() ],
			[ '%s','%s','%s' ],
			[ '%d','%d' ]
		);
		wp_send_json_success(['ok'=>1]);
	}

	public static function switch_user(){
		if ( ! is_user_logged_in() ) wp_send_json_error('no_user');
		check_ajax_referer('aaa_ac_popup','nonce');
		$popup_id = isset($_POST['popup_id']) ? absint($_POST['popup_id']) : 0;
		if ( ! $popup_id ) wp_send_json_error('bad_id');

		$user_id = get_current_user_id();
		$raw     = self::raw_cookie_token();
		$hash    = self::hash_session_token( $raw );

		global $wpdb;
		$table = self::popup_log_table();
		$now   = wp_date('Y-m-d H:i:s');
		$wpdb->update( $table,
			[ 'action'=>'switch', 'handled_at'=>$now, 'updated_at'=>$now ],
			[ 'id'=>$popup_id, 'user_id'=>$user_id ],
			[ '%s','%s','%s' ],
			[ '%d','%d' ]
		);

		if ( class_exists('WP_Session_Tokens') && $hash ){
			WP_Session_Tokens::get_instance( $user_id )->destroy( $hash );
		} else {
			WP_Session_Tokens::get_instance( $user_id )->destroy_all();
		}

		wp_send_json_success([ 'ok'=>1, 'redirect'=> wp_login_url() ]);
	}
}
