<?php
// File: classes/core/stream/cache/fn-run-db-cleanup.php

namespace WPAICG\Core\Stream\Cache;

use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cron callback function to delete expired cache entries from the DB table.
 * Only runs if external object cache is NOT being used.
 *
 * @return void
 */
function run_db_cleanup_logic(): void {

    if (wp_using_ext_object_cache()) {
        return;
    }

    global $wpdb;
    // Access DB_TABLE_SUFFIX via a constant or pass it in. For now, hardcoding for simplicity.
    $table = $wpdb->prefix . 'aipkit_sse_message_cache';
    $now_utc = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H-i-s');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: This is a cron job for bulk deletion of expired rows from a custom table. Caching is not applicable here.
    $deleted_count = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE expires_at <= %s", $now_utc));

}