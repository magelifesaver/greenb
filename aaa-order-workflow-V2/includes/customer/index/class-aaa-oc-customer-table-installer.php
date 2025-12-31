<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/index/class-aaa-oc-customer-table-installer.php
 * Purpose: Create/upgrade Customer tables (profile + per-order link).
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Customer_Table_Installer {
	const SCHEMA_VERSION = '1.0.1';

	public static function maybe_install(): void {
		global $wpdb;

		$opt_key = 'aaa_oc_customer_schema';
		$have_ver = get_option( $opt_key );

		$tbl_profile = $wpdb->prefix . 'aaa_oc_customer';
		$tbl_link    = $wpdb->prefix . 'aaa_oc_customer_order';

		$profile_exists = self::table_exists( $tbl_profile );
		$link_exists    = self::table_exists( $tbl_link );

		// Only skip when version matches AND both tables already exist.
		if ( $have_ver === self::SCHEMA_VERSION && $profile_exists && $link_exists ) {
			return;
		}

		self::install();

		// If both created successfully, record the schema version.
		if ( self::table_exists( $tbl_profile ) && self::table_exists( $tbl_link ) ) {
			update_option( $opt_key, self::SCHEMA_VERSION, true );
		}
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$profile = $wpdb->prefix . 'aaa_oc_customer';
		$link    = $wpdb->prefix . 'aaa_oc_customer_order';

		$sql1 = "CREATE TABLE {$profile} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			crm_contact_id BIGINT(20) DEFAULT NULL,
			customer_banned TINYINT(1) DEFAULT 0,
			customer_ban_length VARCHAR(50) DEFAULT NULL,
			customer_warnings_text TEXT DEFAULT NULL,
			customer_special_needs_text TEXT DEFAULT NULL,
			dob DATE DEFAULT NULL,
			dl_number VARCHAR(100) DEFAULT NULL,
			dl_exp DATE DEFAULT NULL,
			upload_med TEXT DEFAULT NULL,
			upload_selfie TEXT DEFAULT NULL,
			upload_id TEXT DEFAULT NULL,
			last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_user (user_id),
			KEY idx_crm (crm_contact_id)
		) ENGINE=InnoDB {$charset};";

		$sql2 = "CREATE TABLE {$link} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			applied_profile_id BIGINT(20) UNSIGNED DEFAULT NULL,
			applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_order (order_id),
			KEY idx_user (user_id)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	private static function table_exists( string $table ) : bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		return ( $found === $table );
	}
}
