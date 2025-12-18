<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/run-now-content-indexing.php
// Status: NEW FILE

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Content_Queuer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues all existing matching content for a "Run Now" action on a content indexing task.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @return void
 */
function run_now_content_indexing_logic(int $task_id, array $task_config): void
{
    if (class_exists(AIPKit_Automated_Task_Content_Queuer::class)) {
        // For "Run Now", we want to queue all existing content that matches, ignoring last run time.
        AIPKit_Automated_Task_Content_Queuer::maybe_queue_initial_indexing_content($task_id, $task_config, true);
    }
}
