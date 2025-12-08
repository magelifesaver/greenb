<?php

namespace WPAICG\AutoGPT\Cron\Init;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schedules or unschedules the main queue processing cron hook based on active task status and pending queue items.
 *
 * @param string $main_cron_hook The name of the main cron hook.
 * @param int $active_task_count The current number of active tasks.
 * @param int $pending_queue_count The current number of pending queue items.
 * @param bool $did_active_tasks_exist Whether active tasks existed during the last check.
 * @return void
 */
function register_main_cron_hook_logic(string $main_cron_hook, int $active_task_count, int $pending_queue_count, bool $did_active_tasks_exist): void
{
    $option_key_tasks_exist = 'aipkit_active_tasks_exist';
    $has_work_to_do = ($active_task_count > 0) || ($pending_queue_count > 0);

    if (!$has_work_to_do) {
        // Only clear the cron if tasks existed before but now there are none AND no pending queue items.
        if ($did_active_tasks_exist && wp_next_scheduled($main_cron_hook)) {
            wp_clear_scheduled_hook($main_cron_hook);
        }
        update_option($option_key_tasks_exist, false, 'no');
    } else { // Active tasks exist OR pending queue items exist
        // Update the flag if state has changed.
        if (!$did_active_tasks_exist) {
            update_option($option_key_tasks_exist, true, 'no');
        }
        // Schedule the main queue processing event if it's not already scheduled.
        if (!wp_next_scheduled($main_cron_hook)) {
            wp_schedule_event(time(), 'hourly', $main_cron_hook);
        }
    }
}
