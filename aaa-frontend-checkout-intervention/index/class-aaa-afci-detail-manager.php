<?php
/**
 * File: /index/class-aaa-afci-detail-manager.php
 * Purpose: Companion table for granular event metadata.
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_AFCI_Detail_Manager {

	public static function install() {
		global $wpdb;
		$table   = $wpdb->prefix . 'aaa_checkout_event_details';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT UNSIGNED NOT NULL,
			field VARCHAR(100) NULL,
			context VARCHAR(100) NULL,
			value LONGTEXT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_id (event_id),
			KEY field (field)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'Detail table ensured', [ 'table' => $table ] );
		}
		error_log('[AFCI] Detail table ensured: ' . $table);
	}

	public static function insert_detail( $event_id, $field, $context, $value ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_event_details';
		$row = [
			'event_id'   => absint( $event_id ),
			'field'      => sanitize_text_field( $field ),
			'context'    => sanitize_text_field( $context ),
			'value'      => maybe_serialize( $value ),
			'created_at' => current_time( 'mysql', true ),
		];
		$wpdb->insert( $table, $row );
		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'Insert detail', [ 'event_id' => (int) $event_id, 'field' => (string) $field, 'context' => (string) $context ] );
		}
		return $wpdb->insert_id;
	}

	public static function get_details_by_event( $event_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_checkout_event_details';
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d ORDER BY id ASC", $event_id )
		);
	}
}
