<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/ajax/class-aaa-oc-ajax-get-latest.php
 * Purpose: Board feed (FULL rows, no whitelist). Preempts legacy handlers and exits.
 * Version: 1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'AAA_OC_Ajax_Get_Latest' ) ) return;

class AAA_OC_Ajax_Get_Latest {

	private const DEBUG_THIS_FILE = true;
	private const MAX_ROWS        = 200; // safety cap

	public static function boot() {
		// Use a very early priority so we answer before any legacy callbacks.
		add_action( 'wp_ajax_aaa_oc_get_latest_orders',        [ __CLASS__, 'handle' ], -999 );
		add_action( 'wp_ajax_nopriv_aaa_oc_get_latest_orders', [ __CLASS__, 'handle' ], -999 );

		// Best-effort: if a known legacy handler is hooked, remove it so it can’t send trimmed data.
		add_action( 'init', function () {
			// Common legacy class/method names — harmless if missing.
			remove_action( 'wp_ajax_aaa_oc_get_latest_orders', [ 'AAA_OC_Ajax_Core', 'get_latest_orders' ], 10 );
			remove_action( 'wp_ajax_aaa_oc_get_latest_orders', [ 'AAA_OC_Ajax_Core', 'handle' ], 10 );
		}, 1 );
	}

	private static function log( $m ) {
		if ( self::DEBUG_THIS_FILE && function_exists( 'aaa_oc_log' ) ) {
			aaa_oc_log( '[BOARD][AJAX][FULLROW] ' . $m );
		}
	}

	/** Accept board nonces as core does, or allow admins if absent. */
	private static function verify_or_cap() : void {
		$nonce = $_REQUEST['_ajax_nonce'] ?? ($_REQUEST['nonce'] ?? '');
		if ( $nonce ) {
			if ( wp_verify_nonce( $nonce, 'aaa_oc_ajax_nonce' ) || wp_verify_nonce( $nonce, 'aaa_oc_nonce' ) ) {
				return;
			}
			wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
		}
		if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
			wp_send_json_error( [ 'message' => 'unauthorized' ], 403 );
		}
	}

	public static function handle() {
		self::verify_or_cap();

		global $wpdb;
		$oi = $wpdb->prefix . 'aaa_oc_order_index';

		$sortMode = ( isset($_POST['sortMode']) && $_POST['sortMode'] === 'status' ) ? 'status' : 'published';
		$order_by = ( $sortMode === 'status' ) ? 'time_in_status DESC' : 'time_published DESC';

		// SELECT * and fetch as ARRAY_A to preserve every column verbatim.
		$sql  = $wpdb->prepare( "SELECT * FROM {$oi} ORDER BY {$order_by} LIMIT %d", self::MAX_ROWS );
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) $rows = [];

		// Introspect actual columns (server-side) for proof
		$cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$oi}`", 0 );
		self::log( 'rows=' . count($rows) . ' order_by=' . $order_by . ' cols=[' . implode(',', (array)$cols ) . ']' );

		// Mark the response so you can verify in DevTools
		header( 'X-AAA-OC-Feed: fullrow-v104' );

		ob_start();
		printf(
			'<div class="aaa-oc-columns-wrap" data-feed="fullrow-v104" data-oi-colcount="%d" data-oi-cols="%s"><div class="aaa-oc-col" data-col="all">',
			is_array($cols) ? count($cols) : 0,
			esc_attr( implode(',', (array)$cols ) )
		);

		if ( class_exists( 'AAA_OC_Ajax_Cards' ) ) {
			$enabled = self::enabled_statuses_no_wc();
			foreach ( $rows as $row ) {
				echo AAA_OC_Ajax_Cards::build_order_card_html( (object) $row, 1, $enabled );
			}
		} else {
			self::log( 'AAA_OC_Ajax_Cards missing' );
		}

		echo '</div></div>';
		$out = (string) ob_get_clean();

		wp_send_json_success( [ 'columns_html' => $out ] );
	}

	private static function enabled_statuses_no_wc(): array {
		$raw   = get_option( 'aaa_oc_enabled_statuses' );
		$slugs = is_array($raw) ? $raw : ( is_string($raw) && is_serialized($raw) ? maybe_unserialize($raw) : [] );
		if ( empty($slugs) ) $slugs = [ 'wc-scheduled','wc-pending','wc-processing','wc-lkd-packed-ready','wc-in-route','wc-completed' ];
		$out = [];
		foreach ( $slugs as $s ) { $out[] = ( strpos($s,'wc-')===0 ) ? substr($s,3) : (string)$s; }
		return $out;
	}
}
AAA_OC_Ajax_Get_Latest::boot();
