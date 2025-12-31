<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/compat/index/class-aaa-oc-compat-table-extender.php
 * Purpose: Extend aaa_oc_order_index with compatibility columns.
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Compat_Table_Extender {

	public static function declared_columns(): array {
		return [
			'af_funds_used','af_funds_balance',
			'sc_credit_used','sc_credit_balance',
			'crm_contact_id','crm_reg_source','crm_lists','crm_tags',
		];
	}

	public static function maybe_install(): void {
		global $wpdb; $t = $wpdb->prefix . 'aaa_oc_order_index';

		foreach ( self::declared_columns() as $col ) {
			switch ( $col ) {
				case 'af_funds_used':    self::col($t,"af_funds_used DECIMAL(10,2) DEFAULT 0"); break;
				case 'af_funds_balance': self::col($t,"af_funds_balance DECIMAL(10,2) DEFAULT NULL"); break;
				case 'sc_credit_used':   self::col($t,"sc_credit_used DECIMAL(10,2) DEFAULT 0"); break;
				case 'sc_credit_balance':self::col($t,"sc_credit_balance DECIMAL(10,2) DEFAULT NULL"); break;
				case 'crm_contact_id':   self::col($t,"crm_contact_id BIGINT(20) DEFAULT NULL"); break;
				case 'crm_reg_source':   self::col($t,"crm_reg_source VARCHAR(100) DEFAULT NULL"); break;
				case 'crm_lists':        self::col($t,"crm_lists TEXT DEFAULT NULL"); break;
				case 'crm_tags':         self::col($t,"crm_tags TEXT DEFAULT NULL"); break;
			}
		}

		self::idx($t,'idx_af_funds_used','(af_funds_used)');
		self::idx($t,'idx_sc_credit_used','(sc_credit_used)');
		self::idx($t,'idx_crm_contact_id','(crm_contact_id)');
	}

	private static function col($t,$def){ global $wpdb; $c=preg_split('/\s+/',trim($def))[0];
		if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t} LIKE %s",$c))){
			$wpdb->query("ALTER TABLE {$t} ADD COLUMN {$def}");}}
	private static function idx($t,$n,$cols){ global $wpdb;
		if(!$wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$t} WHERE Key_name=%s",$n))){
			$wpdb->query("ALTER TABLE {$t} ADD KEY {$n} {$cols}");}}
}
