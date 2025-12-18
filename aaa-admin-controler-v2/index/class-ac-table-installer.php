<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/index/class-ac-table-installer.php
 * Purpose: Create/verify network-wide session logs table with prefix aaa_ac_
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined('AAA_AC_DEBUG_THIS_FILE') ) define('AAA_AC_DEBUG_THIS_FILE', true);
if ( ! function_exists('aaa_ac_log') ){ function aaa_ac_log($m,$c=[]){ if(AAA_AC_DEBUG_THIS_FILE) error_log('[AAA_AC] '.$m.' '.( $c?wp_json_encode($c):'' )); } }

class AC_Table_Installer {
	public static function table_name(){
		global $wpdb; return $wpdb->base_prefix . AAA_AC_TABLE_PREFIX . 'session_logs';
	}
	protected static function create_sql( $table, $charset ){
		return "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			role_at_login VARCHAR(64) DEFAULT '' NOT NULL,
			ip_address VARCHAR(45) DEFAULT '' NOT NULL,
			user_agent VARCHAR(255) DEFAULT '' NOT NULL,
			session_token VARCHAR(64) DEFAULT '' NOT NULL,
			login_time DATETIME NOT NULL,
			logout_time DATETIME NULL DEFAULT NULL,
			end_trigger ENUM('core','admin','scheduled','user') DEFAULT 'core' NOT NULL,
			session_auto_ended TINYINT(1) DEFAULT 0 NOT NULL,
			user_action ENUM('confirmed','switch','logout','none') DEFAULT 'none' NOT NULL,
			is_online TINYINT(1) DEFAULT 0 NOT NULL,
			blog_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY login_time (login_time),
			KEY is_online (is_online),
			KEY role_at_login (role_at_login)
		) {$charset};";
	}
	public static function install(){
		global $wpdb;
		$table = self::table_name();
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta( self::create_sql($table,$charset) );
		aaa_ac_log('Installed session logs table', ['table'=>$table]);
	}
	public static function maybe_install(){
		global $wpdb;
		$table = self::table_name();
		$exists = $wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', $table) );
		if ( $exists !== $table ){
			self::install();
		}
	}
}
