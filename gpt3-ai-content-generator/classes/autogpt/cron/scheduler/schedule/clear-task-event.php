<?php

namespace WPAICG\AutoGPT\Cron\Scheduler\Schedule;

// Load dependency
require_once __DIR__ . '/get-task-specific-cron-hook.php';

if (!defined('ABSPATH')) {
    exit;
}

/**
* Clears the scheduled cron event for a specific task and updates the database.
*
* @param int $task_id The ID of the task.
* @return void
*/
function clear_task_event_logic(int $task_id): void
{
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';
    $hook = get_task_specific_cron_hook_logic($task_id);
    $current_schedule_args = [$task_id];

    // Clear the hook. wp_clear_scheduled_hook is idempotent and handles non-existent hooks gracefully.
    wp_clear_scheduled_hook($hook, $current_schedule_args);

    // Update the database to reflect the change
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table. Caches will be invalidated.
    $wpdb->update(
        $tasks_table_name,
        ['next_run_time' => null],
        ['id' => $task_id],
        ['%s'],
        ['%d']
    );
}
