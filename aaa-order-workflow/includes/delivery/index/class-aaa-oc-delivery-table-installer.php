<?php
// File: includes/delivery/aaa-oc-adbdel-table-installer.php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_OC_ADBDEL_VERSION' ) ) { define( 'AAA_OC_ADBDEL_VERSION', '0.1.0-dev' ); }
if ( ! defined( 'AAA_OC_ADBDEL_ENABLE_LOG' ) ) { define( 'AAA_OC_ADBDEL_ENABLE_LOG', true ); }
if ( ! function_exists( 'aaa_oc_adbdel_log' ) ) {
	function aaa_oc_adbdel_log( $msg ) {
		if ( AAA_OC_ADBDEL_ENABLE_LOG ) {
			if ( is_array( $msg ) || is_object( $msg ) ) { $msg = wp_json_encode( $msg ); }
			error_log( '[ADBDEL] ' . $msg );
		}
	}
}

if ( ! class_exists( 'AAA_OC_AdbDel_Table_Installer' ) ) :

final class AAA_OC_AdbDel_Table_Installer {
	public function activate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$route = $wpdb->prefix . 'aaa_oc_delivery_route';
		$map   = $wpdb->prefix . 'aaa_oc_delivery_route_order';
		$sql1 = "CREATE TABLE {$route} (
			route_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED DEFAULT 0,
			envelope_id VARCHAR(64) NOT NULL,
			driver_id BIGINT UNSIGNED DEFAULT 0,
			delivery_date DATE NULL,
			delivery_time TIME NULL,
			delivery_time_range VARCHAR(50) NULL,
			delivery_note TEXT NULL,
			route_sort INT DEFAULT 0,
			route_status VARCHAR(50) NULL,
			route_note TEXT NULL,
			dispatched_by_user_id BIGINT UNSIGNED DEFAULT 0,
			delivery_started DATETIME NULL,
			delivery_completed DATETIME NULL,
			task_info TEXT NULL,
			printed_at DATETIME NULL,
			PRIMARY KEY (route_id),
			KEY order_id (order_id),
			UNIQUE KEY envelope_id (envelope_id)
		) {$charset};";
		$sql2 = "CREATE TABLE {$map} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			route_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			sort_index INT DEFAULT 0,
			shipping_address TEXT NULL,
			total_amount DECIMAL(18,2) DEFAULT 0.00,
			payment_method VARCHAR(100) NULL,
			payment_status VARCHAR(100) NULL,
			account_status VARCHAR(100) NULL,
			warning_flag TINYINT(1) DEFAULT 0,
			band VARCHAR(50) NULL,
			band_length VARCHAR(50) NULL,
			special_needs_text TEXT NULL,
			customer_warnings_text TEXT NULL,
			customer_banned TINYINT(1) DEFAULT 0,
			customer_ban_length VARCHAR(50) NULL,
			order_note TEXT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY route_id (route_id),
			KEY order_id (order_id),
			UNIQUE KEY route_order (route_id, order_id)
		) {$charset};";
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		update_option( 'aaa_oc_adbdel_tables_version', AAA_OC_ADBDEL_VERSION );
	}
}

endif;
