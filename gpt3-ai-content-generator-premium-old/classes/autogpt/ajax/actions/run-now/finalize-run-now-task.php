<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/finalize-run-now-task.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Event_Processor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Finalizes the "Run Now" action by updating the task's last run time
 * and triggering the queue processor.
 *
 * @param int $task_id The ID of the task.
 * @return void
 */
function finalize_run_now_task_logic(int $task_id): void
{
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';

    // Update last_run_time for the main task
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table is necessary for this action.
    $wpdb->update(
        $tasks_table_name,
        ['last_run_time' => current_time('mysql', 1)],
        ['id' => $task_id],
        ['%s'],
        ['%d']
    );

    // Trigger main queue processing immediately by scheduling a one-off event
    if (class_exists(AIPKit_Automated_Task_Event_Processor::class)) {
        // Schedule a one-off event to start processing the queue almost immediately.
        // This decouples the potentially long-running queue processing from the AJAX request.
        wp_schedule_single_event(time() + 5, AIPKit_Automated_Task_Event_Processor::MAIN_CRON_HOOK);
    }
}
