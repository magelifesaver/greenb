<?php

namespace WPAICG\AutoGPT\Cron\Init;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Scheduler;
use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Event_Processor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attaches the WordPress action for each individual task-specific cron hook.
 *
 * @param \wpdb $wpdb The WordPress database object.
 * @param string $tasks_table_name The name of the automated tasks table.
 * @return void
 */
function attach_individual_task_hooks_logic(\wpdb $wpdb, string $tasks_table_name): void
{
    if (!class_exists(AIPKit_Automated_Task_Scheduler::class) || !class_exists(AIPKit_Automated_Task_Event_Processor::class)) {
        return;
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table. Caches will be invalidated.
    $all_tasks = $wpdb->get_results("SELECT id FROM " . esc_sql($tasks_table_name), ARRAY_A);

    if ($all_tasks) {
        foreach ($all_tasks as $task) {
            $task_id = (int)$task['id'];
            $task_specific_hook = AIPKit_Automated_Task_Scheduler::get_task_specific_cron_hook($task_id);

            // Ensure action is only added once per request lifecycle.
            if (!has_action($task_specific_hook, [AIPKit_Automated_Task_Event_Processor::class, 'trigger_task_event'])) {
                add_action($task_specific_hook, [AIPKit_Automated_Task_Event_Processor::class, 'trigger_task_event'], 10, 1);
            }
        }
    }
}
