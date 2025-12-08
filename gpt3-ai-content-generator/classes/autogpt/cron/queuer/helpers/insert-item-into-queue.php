<?php

// File: classes/autogpt/cron/queuer/helpers/insert-item-into-queue.php

namespace WPAICG\AutoGPT\Cron\Queuer\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inserts a single item into the automated task queue.
 *
 * @param \wpdb $wpdb The WordPress database object.
 * @param string $queue_table_name The name of the queue table.
 * @param int $task_id The ID of the parent task.
 * @param int $post_id The ID of the post to be processed.
 * @param string $task_type The type of task (e.g., 'content_indexing').
 * @param array $item_config The specific configuration for this queue item.
 * @return bool True on successful insertion, false otherwise.
 */
function insert_item_into_queue_logic(
    \wpdb $wpdb,
    string $queue_table_name,
    int $task_id,
    int $post_id,
    string $task_type,
    array $item_config
): bool {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct insert to a custom table. Caches will be invalidated.
    $inserted = $wpdb->insert(
        $queue_table_name,
        [
            'task_id' => $task_id,
            'target_identifier' => $post_id,
            'task_type' => $task_type,
            'item_config' => wp_json_encode($item_config),
            'status' => 'pending',
            'added_at' => current_time('mysql', 1)
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s']
    );
    return (bool) $inserted;
}
