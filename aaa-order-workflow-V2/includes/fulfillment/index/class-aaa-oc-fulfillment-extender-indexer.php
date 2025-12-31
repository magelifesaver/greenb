<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/index/class-aaa-oc-fulfillment-extender-indexer.php
 * Purpose: Provide Fulfillment-only slice for order_index upserts (hook into existing collectors).
 * Version: 1.0.0
 * Notes:
 *  - Only returns fulfillment_status, picked_items, usbs_order_fulfillment_data.
 *  - Reads flexible metas; no new reindex flows are created.
 *  - Logging at creation per project rule.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Fulfillment_Extender_Indexer {

    private static $did = false;

    public static function boot(): void {
        if ( self::$did ) return;
        self::$did = true;

        if ( function_exists('aaa_oc_log') ) {
            aaa_oc_log('[FulfillExtIdx] boot');
        }

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

        $get = static function(string $k, $default = null) use ($order_id) {
            $v = get_post_meta($order_id, $k, true);
            if ($v === '' || $v === null) {
                $v = get_post_meta($order_id, '_' . ltrim($k, '_'), true);
            }
            return $v !== '' && $v !== null ? $v : $default;
        };

        $slice['fulfillment_status']          = (string) $get('fulfillment_status', 'not_picked');
        $slice['picked_items']                = (string) $get('picked_items', '');
        $slice['usbs_order_fulfillment_data'] = (string) $get('usbs_order_fulfillment_data', '');

        // Normalize empties to null for TEXT/LONGTEXT
        if ($slice['picked_items'] === '')                $slice['picked_items'] = null;
        if ($slice['usbs_order_fulfillment_data'] === '') $slice['usbs_order_fulfillment_data'] = null;

        return $slice;
    }
}
AAA_OC_Fulfillment_Extender_Indexer::boot();
