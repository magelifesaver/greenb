<?php

namespace WPAICG\AutoGPT\Cron\Scheduler\Batch;

// Load dependency
use WPAICG\AutoGPT\Cron\Scheduler\Schedule;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Clears all task-specific cron events. Used on plugin deactivation.
*
* @return void
*/
function clear_all_task_events_logic(): void
{
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    if ($wpdb->get_var("SHOW TABLES LIKE '" . esc_sql($tasks_table_name) . "'") != $tasks_table_name) {
        return;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    $task_ids = $wpdb->get_col("SELECT id FROM " . esc_sql($tasks_table_name));
    if ($task_ids) {
        foreach ($task_ids as $task_id) {
            Schedule\clear_task_event_logic(absint($task_id));
        }
    }
}
