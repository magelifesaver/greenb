<?php
/**
 * Filepath: sfwf/index/table-queue.php
 * ---------------------------------------------------------------------------
 * Queue table used to process forecast jobs in batches.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function sfwf_queue_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'sfwf_forecast_queue';
}

function sfwf_create_queue_table() {
	global $wpdb;

	$table = sfwf_queue_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		product_id BIGINT(20) UNSIGNED NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		attempts SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
		last_error TEXT NULL,
		queued_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (product_id),
		KEY status (status),
		KEY queued_at (queued_at)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

function sfwf_maybe_upgrade_queue_table() {
	$installed = get_option( 'sfwf_queue_db_ver', '' );
	if ( $installed === SFWF_QUEUE_DB_VER ) return;

	sfwf_create_queue_table();
	update_option( 'sfwf_queue_db_ver', SFWF_QUEUE_DB_VER, false );
}
