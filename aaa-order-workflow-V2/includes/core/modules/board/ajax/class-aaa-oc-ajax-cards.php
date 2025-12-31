<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/ajax/class-aaa-oc-ajax-cards.php
 * Purpose: Build a board card using the FULL order_index row (no column whitelist).
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Ajax_Cards' ) ) return;

class AAA_OC_Ajax_Cards {

	private const DEBUG_THIS_FILE = true;

	public static function build_order_card_html( $r, int $ccount = 1, array $statuses_no_wc = [] ): string {
		// Accept stdClass or array
		$row = is_object($r) ? get_object_vars($r) : (array) $r;

		// Preserve all keys/values exactly as delivered from SQL
		$oi = [];
		foreach ( $row as $k => $v ) {
			$oi[ is_string($k) ? $k : (string)$k ] = $v;
		}

		$order_id = 0;
		if ( isset( $oi['order_id'] ) )      $order_id = (int)$oi['order_id'];
		elseif ( isset( $oi['ID'] ) )        $order_id = (int)$oi['ID'];
		elseif ( isset( $oi['id'] ) )        $order_id = (int)$oi['id'];

		$oi_keys = array_keys($oi); // expose for verification

		$ctx = [
			'oi'        => (object) $oi,     // full snapshot
			'oi_keys'   => $oi_keys,         // keys list for quick inspection
			'oi_raw'    => $row,             // raw input (array) for deep diffing if needed
			'ccount'    => $ccount,
			'statuses'  => $statuses_no_wc,
			'order_id'  => $order_id,
		];

		if ( self::DEBUG_THIS_FILE && function_exists('aaa_oc_log') ) {
			aaa_oc_log( '[Cards][FULL] #' . $order_id . ' keys=' . implode(',', $oi_keys) );
		}

		ob_start();
		$path = ( defined('AAA_OC_VIEWS_DIR') ? trailingslashit(AAA_OC_VIEWS_DIR) : dirname(__DIR__) . '/views/' ) . 'board-card-layout-shell.php';
		include $path; // expects $order_id and $ctx
		return (string) ob_get_clean();
	}
}
