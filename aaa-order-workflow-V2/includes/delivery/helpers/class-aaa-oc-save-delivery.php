<?php
/**
 * File: plugins/aaa-order-workflow/includes/delivery/helpers/class-aaa-oc-save-delivery.php
 * Purpose: Save Delivery Date/Time from the Workflow Board.
 * Version: 2.3.0
 *
 * Canonical format saved to metas:
 *  - delivery_date            : midnight timestamp (site TZ)
 *  - delivery_date_formatted  : YYYY-MM-DD
 *  - delivery_date_locale     : e.g., Thursday, October 2, 2025
 *  - delivery_time            : start time, 12h (e.g., "3:00 pm")
 *  - delivery_time_range      : "From {START} - To {END}" (12h)
 *  - _wc_other/adbsa/delivery-date : YYYY-MM-DD
 *  - _wc_other/adbsa/delivery-time : same as delivery_time_range
 */

if ( ! defined('ABSPATH') ) exit;

class AAA_OC_Save_Delivery {

    public static function init() {
        add_action('wp_ajax_aaa_oc_save_delivery', [__CLASS__, 'handle']);
    }

    /** Build "From {start} - To {end}" with 12h tokens. */
    private static function format_range( string $from12, string $to12 ): string {
        $from12 = trim($from12);
        $to12   = trim($to12);
        if ($from12 !== '' && $to12 !== '') {
            return sprintf('From %s - To %s', $from12, $to12);
        }
        if ($from12 !== '') return sprintf('From %s', $from12);
        if ($to12   !== '') return sprintf('To %s',   $to12);
        return '';
    }

    public static function handle() {
        $order_id = absint($_POST['order_id'] ?? 0);
        $dateYmd  = sanitize_text_field($_POST['date_ymd'] ?? ''); // YYYY-MM-DD
        $fromH24  = sanitize_text_field($_POST['from_val'] ?? ''); // HH:mm
        $toH24    = sanitize_text_field($_POST['to_val']   ?? ''); // HH:mm

        if ( ! current_user_can('edit_shop_orders') ) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        if ( ! wp_verify_nonce($_POST['nonce'] ?? '', 'aaa_oc_ajax_nonce') ) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }
        if ( ! $order_id ) {
            wp_send_json_error(['message' => 'Missing order_id.'], 400);
        }

        $order = wc_get_order($order_id);
        if ( ! $order ) {
            wp_send_json_error(['message' => 'Order not found.'], 404);
        }

        $tz = wp_timezone();
        $changed = [];

        // ---- DATE ----
        if ( $dateYmd && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd) ) {
            // midnight in site timezone
            $ts = strtotime($dateYmd . ' 00:00:00 ' . wp_timezone_string());
            if ($ts) {
                $loc = wp_date('l, F j, Y', $ts, $tz);

                $order->update_meta_data('delivery_date',            $ts);
                $order->update_meta_data('delivery_date_formatted',  $dateYmd);
                $order->update_meta_data('delivery_date_locale',     $loc);
                $order->update_meta_data('_wc_other/adbsa/delivery-date', $dateYmd);

                $changed[] = 'date ' . $loc;
            }
        }

        // ---- TIME ----
        $from12 = '';
        $to12   = '';

        if ($fromH24) {
            $fromDT = DateTime::createFromFormat('H:i', $fromH24, $tz);
            if ($fromDT) $from12 = strtolower($fromDT->format('g:i a'));
        }
        if ($toH24) {
            $toDT = DateTime::createFromFormat('H:i', $toH24, $tz);
            if ($toDT) $to12 = strtolower($toDT->format('g:i a'));
        }

        $range = self::format_range($from12, $to12);

        if ($range !== '') {
            if ($from12 !== '') {
                $order->update_meta_data('delivery_time', $from12);
            }
            $order->update_meta_data('delivery_time_range', $range);
            $order->update_meta_data('_wc_other/adbsa/delivery-time', $range);

            $changed[] = 'time ' . $range;
        }

        // Save once
        $order->save();

        // ---- Notes (WC + Dispatch) ----
        if ($changed) {
            $admin = wp_get_current_user()->display_name;
            $when  = current_time('mysql'); // site TZ
            $msg   = sprintf(
                'Delivery updated on board by %s at %s: %s.',
                $admin, $when, implode(', ', $changed)
            );

            // 1) WooCommerce order note
            $order->add_order_note($msg);

            // 2) Append to Dispatch Notes (payment index)
            global $wpdb;
            $payment_table = $wpdb->prefix . 'aaa_oc_payment_index';
            $current_notes = $wpdb->get_var(
                $wpdb->prepare("SELECT payment_admin_notes FROM {$payment_table} WHERE order_id = %d", $order_id)
            );
            $entry = sprintf('[%s] %s', $when, $msg);
            $wpdb->update(
                $payment_table,
                ['payment_admin_notes' => $current_notes ? "{$current_notes}\n{$entry}" : $entry],
                ['order_id' => $order_id],
                ['%s'],
                ['%d']
            );
        }

        // ---- Reindex (if available) ----
        if ( class_exists('AAA_OC_Indexing') )        (new AAA_OC_Indexing())->index_order($order_id);
        if ( class_exists('AAA_OC_Payment_Indexer') ) AAA_OC_Payment_Indexer::sync_payment_totals($order_id);

        wp_send_json_success(['message' => 'Delivery saved.']);
        wp_die();
    }
}

AAA_OC_Save_Delivery::init();
