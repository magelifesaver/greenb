<?php

namespace WPAICG\AutoGPT\Cron\Init;

use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Event_Processor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attaches the main cron hook action to its callback function.
 *
 * @param string $main_cron_hook The name of the main cron hook.
 * @return void
 */
function attach_main_hook_action_logic(string $main_cron_hook): void
{
    if (!class_exists(AIPKit_Automated_Task_Event_Processor::class)) {
        return;
    }

    // Ensure action is only added once per request lifecycle.
    if (!has_action($main_cron_hook, [AIPKit_Automated_Task_Event_Processor::class, 'process_task_queue_event'])) {
        add_action($main_cron_hook, [AIPKit_Automated_Task_Event_Processor::class, 'process_task_queue_event']);
    }
}
