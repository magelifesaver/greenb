<?php

namespace WPAICG\AutoGPT\Cron\Init;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evaluates the current state of active tasks and pending queue items against the previously known state.
 *
 * @param \wpdb $wpdb The WordPress database object.
 * @param string $tasks_table_name The name of the tasks table.
 * @return array An associative array with 'active_task_count', 'pending_queue_count', and 'did_active_tasks_exist'.
 */
function evaluate_status_flags_logic(\wpdb $wpdb, string $tasks_table_name): array
{
    $option_key_tasks_exist = 'aipkit_active_tasks_exist';
    $queue_table_name = $wpdb->prefix . 'aipkit_automated_task_queue';
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    $active_task_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE status = %s", $tasks_table_name, 'active'));
    
    // Also check for pending items in the queue - these need processing even if no tasks are active
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    $pending_queue_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE status = %s", $queue_table_name, 'pending'));
    
    $did_active_tasks_exist = (bool) get_option($option_key_tasks_exist, false);

    return [
        'active_task_count' => $active_task_count,
        'pending_queue_count' => $pending_queue_count,
        'did_active_tasks_exist' => $did_active_tasks_exist,
    ];
}
