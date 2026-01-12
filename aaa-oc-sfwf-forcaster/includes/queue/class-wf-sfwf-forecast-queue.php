<?php
/**
 * Filepath: sfwf/includes/queue/class-wf-sfwf-forecast-queue.php
 * ---------------------------------------------------------------------------
 * Forecast queue manager (DB-backed).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Forecast_Queue {

	private static function table() {
		if ( function_exists('sfwf_queue_table_name') ) return sfwf_queue_table_name();
		global $wpdb;
		return $wpdb->prefix . 'sfwf_forecast_queue';
	}

	private static function now() {
		return current_time( 'mysql' );
	}

	public static function enqueue( $product_id, $source = '' ) {
		global $wpdb;
		$product_id = absint( $product_id );
		if ( ! $product_id ) return false;

		$now = self::now();
		$table = self::table();

		// REPLACE keeps 1 row per product_id and resets attempts/status when re-queued.
		$sql = $wpdb->prepare(
			"REPLACE INTO {$table} (product_id,status,attempts,last_error,queued_at,updated_at)
			 VALUES (%d,'pending',0,'',%s,%s)",
			$product_id,
			$now,
			$now
		);

		return (bool) $wpdb->query( $sql );
	}

	public static function dequeue( $product_id ) {
		global $wpdb;
		$product_id = absint( $product_id );
		if ( ! $product_id ) return 0;

		return (int) $wpdb->delete( self::table(), array( 'product_id' => $product_id ), array( '%d' ) );
	}

	public static function count( $status = 'pending' ) {
		global $wpdb;
		$status = sanitize_key( $status );
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table() . " WHERE status = %s", $status );
		return (int) $wpdb->get_var( $sql );
	}

	public static function list_items( $status = 'pending', $limit = 200 ) {
		global $wpdb;
		$status = sanitize_key( $status );
		$limit  = max( 1, absint($limit) );
		$sql = $wpdb->prepare(
			"SELECT product_id,status,attempts,last_error,queued_at,updated_at
			 FROM " . self::table() . "
			 WHERE status = %s
			 ORDER BY queued_at ASC
			 LIMIT %d",
			$status,
			$limit
		);
		return (array) $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function clear_failed() {
		global $wpdb;
		return (int) $wpdb->query( "DELETE FROM " . self::table() . " WHERE status = 'failed'" );
	}

	public static function sync_from_enabled_products() {
		if ( ! function_exists('wc_get_products') ) return 0;

		$products = wc_get_products( array(
			'limit'      => -1,
			'status'     => array( 'publish', 'private' ),
			'return'     => 'ids',
			'meta_key'   => 'forecast_enable_reorder',
			'meta_value' => 'yes',
		) );

		$added = 0;
		foreach ( (array) $products as $product_id ) {
			if ( self::enqueue( $product_id, 'sync' ) ) $added++;
		}
		return $added;
	}

	public static function process( $limit = 25 ) {
		global $wpdb;

		$limit = max( 1, absint( $limit ) );
		$rows = self::list_items( 'pending', $limit );

		$out = array( 'processed' => 0, 'failed' => 0, 'skipped' => 0 );
		if ( empty($rows) ) return $out;

		foreach ( $rows as $row ) {
			$product_id = absint( $row['product_id'] ?? 0 );
			if ( ! $product_id ) { $out['skipped']++; continue; }

			$attempts = absint( $row['attempts'] ?? 0 ) + 1;
			$locked = $wpdb->update(
				self::table(),
				array( 'status' => 'processing', 'attempts' => $attempts, 'updated_at' => self::now() ),
				array( 'product_id' => $product_id, 'status' => 'pending' ),
				array( '%s', '%d', '%s' ),
				array( '%d', '%s' )
			);
			if ( ! $locked ) { $out['skipped']++; continue; }

			$ok = false;
			$err = '';

			try {
				$ok = class_exists('WF_SFWF_Forecast_Runner') ? (bool) WF_SFWF_Forecast_Runner::update_single_product( $product_id ) : false;
				if ( ! $ok ) $err = 'Skipped: not eligible (missing product / not managing stock).';
			} catch ( Throwable $e ) {
				$ok = false;
				$err = $e->getMessage();
			}

			if ( $ok ) {
				self::dequeue( $product_id );
				$out['processed']++;
			} else {
				$wpdb->update(
					self::table(),
					array( 'status' => 'failed', 'last_error' => $err, 'updated_at' => self::now() ),
					array( 'product_id' => $product_id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);
				$out['failed']++;
			}
		}

		return $out;
	}
}
