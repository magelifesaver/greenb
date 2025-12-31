<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/index/class-aaa-oc-customer-table-extender.php
 * Purpose: Extend aaa_oc_order_index with customer flags/identity snapshots needed by the board.
 * Version: 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Customer_Table_Extender {

	public static function declared_columns(): array {
		return [
			'customer_banned',
			'customer_ban_length',
			'customer_warnings_text',
			'customer_special_needs_text',
			'lkd_upload_med',
			'lkd_upload_selfie',
			'lkd_upload_id',
			'lkd_birthday',
			'lkd_dl_exp',
			'lkd_dln', // NEW: ID Number snapshot on order_index
		];
	}

	public static function maybe_install(): void {
		global $wpdb; $t = $wpdb->prefix.'aaa_oc_order_index';

		foreach ( self::declared_columns() as $col ) {
			switch ( $col ) {
				case 'customer_banned':              self::col($t,"customer_banned TINYINT(1) DEFAULT 0"); break;
				case 'customer_ban_length':          self::col($t,"customer_ban_length VARCHAR(50) DEFAULT NULL"); break;
				case 'customer_warnings_text':       self::col($t,"customer_warnings_text TEXT DEFAULT NULL"); break;
				case 'customer_special_needs_text':  self::col($t,"customer_special_needs_text TEXT DEFAULT NULL"); break;
				case 'lkd_upload_med':               self::col($t,"lkd_upload_med TEXT DEFAULT NULL"); break;
				case 'lkd_upload_selfie':            self::col($t,"lkd_upload_selfie TEXT DEFAULT NULL"); break;
				case 'lkd_upload_id':                self::col($t,"lkd_upload_id TEXT DEFAULT NULL"); break;
				case 'lkd_birthday':                 self::col($t,"lkd_birthday DATE DEFAULT NULL"); break;
				case 'lkd_dl_exp':                   self::col($t,"lkd_dl_exp DATE DEFAULT NULL"); break;
				case 'lkd_dln':                      self::col($t,"lkd_dln VARCHAR(100) DEFAULT NULL"); break; // NEW
			}
		}
		self::idx($t,'idx_customer_banned','(customer_banned)');
	}

	private static function col($t,$def){ global $wpdb; $c=preg_split('/\s+/',trim($def))[0];
		if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t} LIKE %s",$c))){
			$wpdb->query("ALTER TABLE {$t} ADD COLUMN {$def}");}}
	private static function idx($t,$n,$cols){ global $wpdb;
		if(!$wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$t} WHERE Key_name=%s",$n))){
			$wpdb->query("ALTER TABLE {$t} ADD KEY {$n} {$cols}");}}
}
