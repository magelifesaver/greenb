<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/index/class-ac-logger.php
 * Purpose: Log session start/end events into the network-wide aaa_ac_session_logs table.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Logger {

	public static function init(){
		add_action('set_logged_in_cookie', [__CLASS__, 'on_set_logged_in_cookie'], 10, 6);
		add_action('clear_auth_cookie', [__CLASS__, 'on_clear_auth_cookie']);
		add_action('wp_logout', [__CLASS__, 'on_logout'], 10, 1);
		add_action('init', [__CLASS__, 'backfill_current_token']);
	}

	protected static function table(){
		global $wpdb;
		return $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'session_logs';
	}
	protected static function now(){ return wp_date('Y-m-d H:i:s'); }
	protected static function ip(){
		$ip=''; if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ $ip=trim(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])[0]); }
		if(!$ip && !empty($_SERVER['REMOTE_ADDR'])) $ip=$_SERVER['REMOTE_ADDR'];
		return substr((string)$ip,0,45);
	}
	protected static function ua(){ return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,255); }
	protected static function role_slug($user){ return ($user instanceof WP_User && !empty($user->roles)) ? sanitize_key(reset($user->roles)) : ''; }
	protected static function allow_log($user_id,$trigger='core'){ if(in_array($trigger,['admin','scheduled'],true)) return true; return get_user_meta((int)$user_id, defined('AAA_AC_META_INCLUDE')?AAA_AC_META_INCLUDE:'ac_include_logs', true) === 'yes'; }
	protected static function is_valid_hash($h){ return is_string($h) && preg_match('/^[a-f0-9]{40,64}$/i', $h); }
	protected static function hash_session_token( $raw ){ return (is_string($raw) && $raw!=='') ? hash_hmac('sha256',$raw, wp_salt('session')) : ''; }
	protected static function parse_logged_in_cookie(){
		if ( empty($_COOKIE[ LOGGED_IN_COOKIE ]) ) return array('',0,'');
		$parts = explode('|', $_COOKIE[ LOGGED_IN_COOKIE ]);
		if ( count($parts) < 3 ) return array('',0,'');
		return array( (string)$parts[0], (int)$parts[1], (string)$parts[2] );
	}
	protected static function find_hashed_token_fallback( $user_id, $expected_exp = 0 ){
		if ( ! class_exists('WP_Session_Tokens') ) return '';
		$mgr = WP_Session_Tokens::get_instance( (int)$user_id );
		$tokens = is_object($mgr) ? $mgr->get_all() : [];
		if ( ! is_array($tokens) || empty($tokens) ) return '';
		$expected_exp = (int)$expected_exp;
		if ( $expected_exp > 0 ){
			foreach( $tokens as $hash=>$t ){
				if ( isset($t['expiration']) && (int)$t['expiration'] === $expected_exp ) return (string)$hash;
			}
		}
		$bestHash=''; $bestLogin=0;
		foreach( $tokens as $hash=>$t ){
			$login = isset($t['login']) ? (int)$t['login'] : 0;
			if ( $login>$bestLogin ){ $bestLogin=$login; $bestHash=(string)$hash; }
		}
		return self::is_valid_hash($bestHash) ? $bestHash : '';
	}

	public static function on_set_logged_in_cookie( $logged_in_cookie, $expire, $expiration, $user_id, $scheme, $raw_token = '' ){
		$user_id = (int)$user_id; if(!$user_id) return;
		$user = get_user_by('id',$user_id);
		if ( ! self::allow_log($user_id,'core') ) return;

		$hashed = self::hash_session_token( $raw_token );
		if ( ! self::is_valid_hash($hashed) ) $hashed = self::find_hashed_token_fallback( $user_id, (int)$expiration );
		$tok_ok = self::is_valid_hash($hashed);

		global $wpdb; $table=self::table(); $now=self::now();

		if ( $tok_ok ){
			$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id=%d AND session_token=%s LIMIT 1", $user_id, $hashed
			) );
			if ( $existing_id ){
				$wpdb->update($table, ['is_online'=>1,'updated_at'=>$now], ['id'=>$existing_id], ['%d','%s'], ['%d']);
				return;
			}
		}

		$wpdb->insert($table, [
			'user_id'            => $user_id,
			'role_at_login'      => self::role_slug($user),
			'ip_address'         => self::ip(),
			'user_agent'         => self::ua(),
			'session_token'      => $tok_ok ? $hashed : '',
			'login_time'         => $now,
			'logout_time'        => null,
			'end_trigger'        => 'core',
			'session_auto_ended' => 0,
			'user_action'        => 'none',
			'is_online'          => 1,
			'blog_id'            => get_current_blog_id(),
			'created_at'         => $now,
			'updated_at'         => $now,
		]);
	}

	public static function on_clear_auth_cookie(){
		$user_id = get_current_user_id(); if(!$user_id) return;
		list($u,$exp,$raw) = self::parse_logged_in_cookie();
		$hashed = self::hash_session_token($raw);
		if ( ! self::is_valid_hash($hashed) ) $hashed = self::find_hashed_token_fallback($user_id,(int)$exp);
		if ( self::is_valid_hash($hashed) ) self::mark_ended_by_token($user_id,$hashed,'user',0,'logout');
		else self::mark_ended_latest($user_id,'user',0,'logout');
	}

	public static function on_logout( $user_id = 0 ){
		$user_id = (int)($user_id ?: get_current_user_id()); if(!$user_id) return;
		self::mark_ended_latest($user_id,'user',0,'logout');
	}

	public static function backfill_current_token(){
		if ( ! is_user_logged_in() ) return;
		$user_id = get_current_user_id();
		list($u,$exp,$raw) = self::parse_logged_in_cookie();
		$hashed = self::hash_session_token($raw);
		if ( ! self::is_valid_hash($hashed) ) $hashed = self::find_hashed_token_fallback($user_id,(int)$exp);
		if ( ! self::is_valid_hash($hashed) ) return;
		global $wpdb; $table=self::table(); $now=self::now();
		$wpdb->update($table, ['session_token'=>$hashed,'updated_at'=>$now], ['user_id'=>$user_id,'is_online'=>1,'session_token'=>''], ['%s','%s'], ['%d','%d','%s']);
	}

	public static function mark_ended_by_token($user_id,$hashed_token,$trigger='admin',$auto=1,$user_action='none'){
		$user_id=(int)$user_id; $now=self::now(); if(!$user_id || !self::is_valid_hash($hashed_token)) return;
		global $wpdb; $table=self::table();
		$updated = $wpdb->update($table, [
			'logout_time'=>$now,'end_trigger'=>$trigger,'session_auto_ended'=>$auto?1:0,'user_action'=>$user_action,'is_online'=>0,'updated_at'=>$now
		], ['user_id'=>$user_id,'session_token'=>$hashed_token,'is_online'=>1],
		['%s','%s','%d','%s','%d','%s'], ['%d','%s','%d'] );
		if( ! $updated ) self::mark_ended_latest($user_id,$trigger,$auto,$user_action);
	}
	public static function mark_ended_latest($user_id,$trigger='admin',$auto=1,$user_action='none'){
		$user_id=(int)$user_id; if(!$user_id) return;
		global $wpdb; $table=self::table(); $now=self::now();
		$row_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND is_online=1 ORDER BY login_time DESC LIMIT 1",$user_id));
		if(!$row_id) return;
		$wpdb->update($table, [
			'logout_time'=>$now,'end_trigger'=>$trigger,'session_auto_ended'=>$auto?1:0,'user_action'=>$user_action,'is_online'=>0,'updated_at'=>$now
		], ['id'=>$row_id], ['%s','%s','%d','%s','%d','%s'], ['%d']);
	}
	public static function mark_ended_all($user_id,$trigger='admin',$auto=1,$user_action='none'){
		$user_id=(int)$user_id; if(!$user_id) return;
		global $wpdb; $table=self::table(); $now=self::now();
		$wpdb->query($wpdb->prepare(
			"UPDATE {$table} SET logout_time=%s,end_trigger=%s,session_auto_ended=%d,user_action=%s,is_online=0,updated_at=%s WHERE user_id=%d AND is_online=1",
			$now,$trigger,$auto?1:0,$user_action,$now,$user_id
		));
	}
}
