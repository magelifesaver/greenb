<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/create-task/schedule-task-if-active.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Scheduler;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Schedules the cron event for the new task if its status is 'active'.
*
* @param int $task_id The ID of the newly created task.
* @param string $task_status The status of the task ('active' or 'paused').
* @param string $task_frequency The scheduling frequency ('hourly', 'daily', etc.).
* @return void
*/
function schedule_task_if_active_logic(int $task_id, string $task_status, string $task_frequency): void
{
    if ($task_status === 'active' && class_exists(AIPKit_Automated_Task_Scheduler::class)) {
        AIPKit_Automated_Task_Scheduler::schedule_task_event($task_id, $task_frequency, 'active');
    }
}
