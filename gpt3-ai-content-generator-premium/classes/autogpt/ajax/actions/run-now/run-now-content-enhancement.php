<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/run-now-content-enhancement.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

use WPAICG\AutoGPT\Cron\EventProcessor\Trigger;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues items for a "Run Now" action on a content enhancement task.
 * This is the same logic as the scheduled trigger.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @return void
 */
function run_now_content_enhancement_logic(int $task_id, array $task_config): void
{
    // The logic to queue posts for enhancement is the same whether it's a scheduled run or a "Run Now" trigger.
    if (function_exists('\WPAICG\AutoGPT\Cron\EventProcessor\Trigger\trigger_content_enhancement_task_logic')) {
        // For "Run Now", we always pass null for last_run_time to get all posts matching criteria.
        Trigger\trigger_content_enhancement_task_logic($task_id, $task_config, null);
    }
}
