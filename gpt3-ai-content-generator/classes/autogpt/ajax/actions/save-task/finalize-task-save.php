<?php

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Scheduler;
use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Content_Queuer;
use WPAICG\AutoGPT\Cron\EventProcessor\Trigger;

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/event-processor/trigger/trigger-content-enhancement-task.php')) {
    require_once WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/event-processor/trigger/trigger-content-enhancement-task.php';
}

/**
* Finalizes the task saving process by scheduling the cron event and queueing initial content if necessary.
*
* @param int $task_id The ID of the saved task.
* @param array $task_config The configuration of the task.
* @param string $task_status The status of the task ('active' or 'paused').
* @param bool $is_new_task Whether the task was just created.
* @return void
*/
function finalize_task_save_logic(int $task_id, array $task_config, string $task_status, bool $is_new_task): void
{
    if (class_exists(AIPKit_Automated_Task_Scheduler::class)) {
        $frequency = $task_config['task_frequency'] ?? ($task_config['indexing_frequency'] ?? 'daily');
        AIPKit_Automated_Task_Scheduler::schedule_task_event($task_id, $frequency, $task_status);
    }
    if (
        $is_new_task &&
        $task_status === 'active' &&
        ($task_config['task_type'] ?? '') === 'content_indexing' &&
        ($task_config['index_existing_now_flag'] ?? '0') === '1' &&
        class_exists(AIPKit_Automated_Task_Content_Queuer::class)
    ) {
        AIPKit_Automated_Task_Content_Queuer::maybe_queue_initial_indexing_content($task_id, $task_config);
    } elseif (
        $is_new_task &&
        $task_status === 'active' &&
        ($task_config['task_type'] ?? '') === 'enhance_existing_content' &&
        ($task_config['enhance_existing_now_flag'] ?? '0') === '1' &&
        function_exists('\WPAICG\AutoGPT\Cron\EventProcessor\Trigger\trigger_content_enhancement_task_logic')
    ) {
        // Queue all existing content immediately. The last_run_time is null.
        Trigger\trigger_content_enhancement_task_logic($task_id, $task_config, null);

        // Disable the flag so it doesn't run again on the next cron trigger.
        global $wpdb;
        $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';
        $task_config['enhance_existing_now_flag'] = '0';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table is necessary to update task state.
        $wpdb->update(
            $tasks_table_name,
            ['task_config' => wp_json_encode($task_config)],
            ['id' => $task_id],
            ['%s'],
            ['%d']
        );
    }
}