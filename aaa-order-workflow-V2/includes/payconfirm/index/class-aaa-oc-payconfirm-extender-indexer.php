<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-extender-indexer.php
 * Purpose: Provide PayConfirm-only slice for order_index upserts (mirror metas -> pc_* + alias snapshot fields).
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_PayConfirm_Extender_Indexer {
	private static $did = false;

	public static function boot(): void {
		if ( self::$did ) return;
		self::$did = true;

		if ( has_filter('aaa_oc_order_index_slice') ) {
			add_filter('aaa_oc_order_index_slice', [__CLASS__, 'slice'], 10, 2);
		}
		if ( has_filter('aaa_oc_collect_order_index') ) {
			add_filter('aaa_oc_collect_order_index', [__CLASS__, 'slice'], 10, 2);
		}
		if ( has_filter('aaa_oc_order_index_collect_slice') ) {
			add_filter('aaa_oc_order_index_collect_slice', [__CLASS__, 'slice'], 10, 2);
		}
	}

	public static function slice( array $slice, int $order_id ): array {
		if ( $order_id <= 0 ) return $slice;

		$g = static function(string $k, $default = null) use ($order_id) {
			$v = get_post_meta($order_id, $k, true);
			if ($v === '' || $v === null) $v = get_post_meta($order_id, '_' . ltrim($k, '_'), true);
			return $v !== '' && $v !== null ? $v : $default;
		};

		// Existing pc_* mirrors
		$slice['pc_post_id']          = (int)    $g('pc_post_id', 0) ?: null;
		$slice['pc_matched_order_id'] = (int)    $g('pc_matched_order_id', 0);
		$slice['pc_txn']              = (string) $g('pc_txn', '') ?: null;
		$slice['pc_amount']           = ($amt = $g('pc_amount', null)) !== null ? (float) $amt : null;
		$slice['pc_match_status']     = (string) $g('pc_match_status', 'unmatched');

		// NEW: alias snapshot + timestamp
		$map = $g('aaa_oc_pay_accounts', null);
		$slice['pc_aliases'] = is_array($map) ? wp_json_encode($map) : ( $map ? (string) $map : null );

		$ts  = (string) $g('_pc_alias_snapshot_ts', '');
		if ( $ts !== '' && is_numeric($ts) ) {
			$ts = gmdate('Y-m-d H:i:s', (int) $ts);
		}
		$slice['pc_alias_snapshot_ts'] = $ts !== '' ? $ts : null;

		return $slice;
	}
}
AAA_OC_PayConfirm_Extender_Indexer::boot();
