<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/index/class-aaa-oc-delivery-extender-indexer.php
 * Purpose: Provide Delivery-only slice for order_index upserts (no new flows; hooks into existing collectors).
 * Version: 1.0.0
 * Notes:
 *  - Only returns the Delivery columns added by the Delivery extender.
 *  - Tries to read from sensible metas; computes formatted strings when possible.
 *  - Logging at creation per project rule.
 */
if ( ! defined('ABSPATH') ) exit;

final class AAA_OC_Delivery_Extender_Indexer {

    /** one-per-request guard for boot() */
    private static $did = false;

    public static function boot(): void {
        if ( self::$did ) return;
        self::$did = true;

        // Log (creation point instrumentation)
        if ( function_exists('aaa_oc_log') ) {
            aaa_oc_log('[DeliveryExtIdx] boot');
        }

        // Attach to whichever collector your core exposes (no new flows).
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

    /**
     * Return Delivery-only columns for the given order.
     * @param array $slice accumulator from core
     * @param int   $order_id
     * @return array
     */
    public static function slice( array $slice, int $order_id ): array {
        if ( $order_id <= 0 ) return $slice;

        // Pull what we can from metas (both underscored and non-underscored just in case).
        $get = static function(string $k, $default = null) use ($order_id) {
            $v = get_post_meta($order_id, $k, true);
            if ($v === '' || $v === null) {
                $v = get_post_meta($order_id, '_' . ltrim($k, '_'), true);
            }
            return $v !== '' && $v !== null ? $v : $default;
        };

        $ts = (int) $get('delivery_date_ts', 0);

        // Try to derive a date if only a string/meta exists
        if ( ! $ts ) {
            $raw = $get('delivery_date_raw');
            if ( $raw ) {
                $try = strtotime($raw);
                if ( $try ) $ts = $try;
            }
        }

        $date_formatted = $ts ? gmdate('Y-m-d', $ts) : null;
        $date_locale    = $ts ? date_i18n( get_option('date_format', 'M j, Y'), $ts ) : null;

        // Driver id: earlier spec mentions lddfw_driverid meta
        $driver_id = (int) $get('lddfw_driverid', 0);
        if ( ! $driver_id ) {
            $driver_id = (int) $get('driver_id', 0);
        }

        $delivery_time       = (string) $get('delivery_time', '');
        $delivery_time_range = (string) $get('delivery_time_range', '');

        $is_scheduled = (int) (bool) $get('is_scheduled', 0);
        $is_same_day  = (int) (bool) $get('is_same_day', 0);
        $is_asap      = (int) (bool) $get('is_asap', 0);

        $slice['delivery_date_ts']        = $ts ?: null;
        $slice['delivery_date_formatted'] = $date_formatted;
        $slice['delivery_date_locale']    = $date_locale;
        $slice['delivery_time']           = $delivery_time ?: null;
        $slice['delivery_time_range']     = $delivery_time_range ?: null;
        $slice['driver_id']               = $driver_id ?: null;
        $slice['is_scheduled']            = $is_scheduled;
        $slice['is_same_day']             = $is_same_day;
        $slice['is_asap']                 = $is_asap;

        return $slice;
    }
}
AAA_OC_Delivery_Extender_Indexer::boot();
