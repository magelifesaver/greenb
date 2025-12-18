<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/run-now-comment-reply.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

use WPAICG\AutoGPT\Cron\EventProcessor\Trigger;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues items for a "Run Now" action on a comment reply task.
 * This is essentially the same as a scheduled trigger.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @param string|null $last_run_time The last time the task ran.
 * @return void
 */
function run_now_comment_reply_logic(int $task_id, array $task_config, ?string $last_run_time): void
{
    // The logic to queue comments is the same whether it's a scheduled run or a "Run Now" trigger.
    // It checks for comments created since the last run time.
    if (function_exists('\WPAICG\AutoGPT\Cron\EventProcessor\Trigger\trigger_comment_reply_task_logic')) {
        Trigger\trigger_comment_reply_task_logic($task_id, $task_config, null);
    }
}