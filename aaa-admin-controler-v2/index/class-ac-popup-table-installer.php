<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/index/class-ac-popup-table-installer.php
 * Purpose: Create/verify network-wide popup logs table aaa_ac_popup_logs
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AC_Popup_Table_Installer {
	public static function table_name(){
		global $wpdb; return $wpdb->base_prefix . ( defined('AAA_AC_TABLE_PREFIX') ? AAA_AC_TABLE_PREFIX : 'aaa_ac_' ) . 'popup_logs';
	}
	protected static function create_sql( $table, $charset ){
		return "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			due_at DATETIME NOT NULL,
			due_hhmm CHAR(5) NOT NULL,
			shown_at DATETIME NOT NULL,
			site_id BIGINT UNSIGNED NULL,
			admin_page VARCHAR(255) DEFAULT '' NOT NULL,
			session_token VARCHAR(64) DEFAULT '' NOT NULL,
			action ENUM('shown','confirmed','switch','timeout') DEFAULT 'shown' NOT NULL,
			handled_at DATETIME NULL DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT '' NOT NULL,
			user_agent VARCHAR(255) DEFAULT '' NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY due_at (due_at),
			KEY action (action)
		) {$charset};";
	}
	public static function install(){
		global $wpdb; $table=self::table_name(); $charset=$wpdb->get_charset_collate();
		require_once ABSPATH.'wp-admin/includes/upgrade.php'; dbDelta( self::create_sql($table,$charset) );
	}
	public static function maybe_install(){
		global $wpdb; $table=self::table_name();
		$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
		if($exists!==$table) self::install();
	}
}
