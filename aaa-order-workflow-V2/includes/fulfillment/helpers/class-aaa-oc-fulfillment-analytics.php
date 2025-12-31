<?php
/**
 * FilePath: plugins/aaa-order-workflow/includes/helpers/class-aaa-oc-fulfillment-analytics.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fulfillment logging + index syncing
 * - Creates/updates [wp_]aaa_oc_fulfillment_logs with richer columns
 * - AJAX endpoint to record fulfillment (picked lines, scanned/manual counts)
 * - Updates BOTH:
 *     a) order meta (_aaa_picked_items, _aaa_fulfillment_status)
 *     b) order index row (picked_items, fulfillment_status)
 *   so reindexers won’t “forget” fulfillment later.
 */
class AAA_OC_Fulfillment_Analytics {

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->get_blog_prefix() . 'aaa_oc_fulfillment_logs';
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Added columns: fulfillment_status, picked_json, started_at, updated_at
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            scanned_count INT NOT NULL DEFAULT 0,
            manual_count INT NOT NULL DEFAULT 0,
            fulfillment_status VARCHAR(32) NOT NULL DEFAULT 'not_picked',
            picked_json LONGTEXT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY fulfillment_status (fulfillment_status)
        ) ENGINE=InnoDB {$charset};";
        dbDelta($sql);
    }

    public static function init() {
        add_action('wp_ajax_aaa_oc_record_fulfillment', [__CLASS__, 'ajax_record_fulfillment']);
    }

    /**
     * Expected POST:
     *  - order_id (int)               [required]
     *  - picked_json (json string)    [{"sku":"ABC","picked":2,"max":2}, ...]
     *  - scanned_count (int)          optional (defaults 0)
     *  - manual_count  (int)          optional (defaults 0)
     *  - started_at (Y-m-d H:i:s)     optional
     *  - completed_at (Y-m-d H:i:s)   optional (defaults now if all items picked)
     */
    public static function ajax_record_fulfillment() {
        check_ajax_referer('aaa_oc_ajax_nonce', 'security');

        global $wpdb;
        $order_id      = absint($_POST['order_id'] ?? 0);
        $picked_json   = isset($_POST['picked_json']) ? wp_unslash($_POST['picked_json']) : '';
        $scanned_count = max(0, intval($_POST['scanned_count'] ?? 0));
        $manual_count  = max(0, intval($_POST['manual_count']  ?? 0));
        $started_at    = sanitize_text_field($_POST['started_at'] ?? '');
        $completed_at  = sanitize_text_field($_POST['completed_at'] ?? '');

        if (!$order_id) {
            wp_send_json_error('Missing or invalid order_id.');
        }

        // Parse line items and determine fulfillment_status
        $decoded = json_decode($picked_json, true);
        $decoded = is_array($decoded) ? $decoded : [];

        $all_picked = true;
        foreach ($decoded as $row) {
            $picked = intval($row['picked'] ?? 0);
            $max    = intval($row['max'] ?? 0);
            if ($max > 0 && $picked < $max) { $all_picked = false; break; }
        }
        $fulfillment_status = $all_picked && !empty($decoded) ? 'fully_picked' : 'not_picked';

        // Timestamps
        $now = current_time('mysql');
        if (!$completed_at && $fulfillment_status === 'fully_picked') {
            $completed_at = $now;
        }

        // Persist to permanent logs
        $logs_table = $wpdb->get_blog_prefix() . 'aaa_oc_fulfillment_logs';
        $user_id    = get_current_user_id();
        $notes      = $decoded ? ('Line Picks: ' . $picked_json) : null;

        $wpdb->insert($logs_table, [
            'order_id'           => $order_id,
            'user_id'            => $user_id,
            'scanned_count'      => $scanned_count,
            'manual_count'       => $manual_count,
            'fulfillment_status' => $fulfillment_status,
            'picked_json'        => $picked_json ?: null,
            'started_at'         => $started_at ?: null,
            'completed_at'       => $completed_at ?: null,
            'notes'              => $notes,
        ], ['%d','%d','%d','%d','%s','%s','%s','%s','%s']);

        // Add concise WC order note
        if ($order = wc_get_order($order_id)) {
            $current_user = wp_get_current_user();
            $username     = $current_user->user_login ?: "User #{$user_id}";
            $lines        = [];
            foreach ($decoded as $r) {
                $sku = $r['sku'] ?? '';
                $p   = intval($r['picked'] ?? 0);
                $m   = intval($r['max'] ?? 0);
                $lines[] = "{$sku}={$p}/{$m}";
            }
            $lineList = $lines ? implode(', ', $lines) : 'None';
            $note     = sprintf(
                "Fulfillment %s by %s at %s. Items: %s | scanned=%d, manual=%d",
                $fulfillment_status === 'fully_picked' ? 'completed' : 'updated',
                $username,
                $completed_at ?: $now,
                $lineList,
                $scanned_count,
                $manual_count
            );
            $order->add_order_note($note);
            $order->save();
        }

        // Write to ORDER META so reindexers won’t wipe pills on later saves
        // Store a compact map { sku => picked }
        $sku_map = [];
        foreach ($decoded as $r) {
            if (!empty($r['sku'])) {
                $sku_map[$r['sku']] = intval($r['picked'] ?? 0);
            }
        }
        update_post_meta($order_id, '_aaa_picked_items', $sku_map);
        update_post_meta($order_id, '_aaa_fulfillment_status', $fulfillment_status);

        // Update the WORKFLOW ORDER INDEX row directly (authoritative for pills)
        $index_table = $wpdb->get_blog_prefix() . 'aaa_oc_order_index';
        $wpdb->update(
            $index_table,
            [
                'fulfillment_status' => $fulfillment_status,
                'picked_items'       => $picked_json ?: null,
                'last_updated'       => $now,
            ],
            [ 'order_id' => $order_id ],
            [ '%s','%s','%s' ],
            [ '%d' ]
        );

        wp_send_json_success([
            'message'              => 'Fulfillment recorded.',
            'fulfillment_status'   => $fulfillment_status,
            'completed_at'         => $completed_at,
        ]);
    }
}
